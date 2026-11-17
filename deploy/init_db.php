<?php
/**
 * Runs once at container start (see deploy/entrypoint.sh).
 * If the database is empty, imports Database/medicore.sql.
 *
 * The phpMyAdmin dump creates tables first and adds PRIMARY KEYs later with
 * ALTER TABLE. Managed MySQL (e.g. Aiven) enforces sql_require_primary_key,
 * so the keys are moved into each CREATE TABLE before importing.
 */
require __DIR__ . '/../db_config.php';

$db = medicore_db_config();
if (getenv('DB_HOST') === false || getenv('DB_HOST') === '') {
    fwrite(STDERR, "[init_db] DB_HOST not set, skipping database bootstrap\n");
    exit(0);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$flags = 0;
if ($db['ssl']) {
    $conn->ssl_set(null, null, '/etc/ssl/certs/ca-certificates.crt', null, null);
    $flags = MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
}

$connected = false;
for ($i = 1; $i <= 5 && !$connected; $i++) {
    try {
        $conn->real_connect($db['host'], $db['user'], $db['pass'], $db['name'], $db['port'], null, $flags);
        $connected = true;
    } catch (mysqli_sql_exception $e) {
        fwrite(STDERR, "[init_db] connect attempt $i failed: {$e->getMessage()}\n");
        sleep(3);
    }
}
if (!$connected) {
    fwrite(STDERR, "[init_db] could not connect; the site will show a DB error until settings are fixed\n");
    exit(0);
}

/**
 * Keep the admin password private: when ADMIN_PASSWORD is set (e.g. generated
 * by Render), apply it to the admin account on every boot. Nothing changes
 * when it is not set.
 */
function medicore_sync_admin_password(mysqli $conn): void
{
    $password = trim((string) getenv('ADMIN_PASSWORD'));
    if ($password === '') {
        return;
    }
    $email = trim((string) (getenv('ADMIN_EMAIL') ?: 'admin@medicore'));
    $current = $conn->prepare("SELECT id, password FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $current->bind_param('s', $email);
    $current->execute();
    $row = $current->get_result()->fetch_assoc();
    if (!$row) {
        fwrite(STDERR, "[init_db] admin account $email not found; password not applied\n");
        return;
    }
    if (password_verify($password, $row['password'])) {
        return; // already up to date
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param('si', $hash, $row['id']);
    $update->execute();
    echo "[init_db] admin password updated from ADMIN_PASSWORD\n";
}

// A marker table is written only after a complete import, so a half-finished
// earlier attempt is detected and redone instead of being left broken.
$done = $conn->query("SHOW TABLES LIKE '_medicore_init'")->num_rows > 0;
if ($done) {
    echo "[init_db] database already initialised, nothing to import\n";
    medicore_sync_admin_password($conn);
    exit(0);
}

$sql = str_replace("\r\n", "\n", file_get_contents(__DIR__ . '/../Database/medicore.sql'));

// Drop tables left by an incomplete previous attempt.
preg_match_all('/CREATE TABLE `(\w+)`/', $sql, $tm);
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
foreach ($tm[1] as $t) {
    $conn->query("DROP TABLE IF EXISTS `$t`");
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 1. Collect "ALTER TABLE `t` ADD PRIMARY KEY (...)" and strip that clause.
$primaryKeys = [];
$sql = preg_replace_callback(
    '/ALTER TABLE `(\w+)`\s+ADD PRIMARY KEY \(([^)]*)\)\s*(,|;)/',
    function ($m) use (&$primaryKeys) {
        $primaryKeys[$m[1]] = $m[2];
        // keep the ALTER if more clauses follow, otherwise drop it entirely
        return $m[3] === ',' ? "ALTER TABLE `{$m[1]}`\n " : '';
    },
    $sql
);

// 2. Put each primary key inside its CREATE TABLE.
foreach ($primaryKeys as $table => $cols) {
    $sql = preg_replace(
        '/(CREATE TABLE `' . preg_quote($table, '/') . '` \(.*?)\n\)( ENGINE=)/s',
        "$1,\n  PRIMARY KEY ($cols)\n)$2",
        $sql,
        1
    );
}

// 3. "ALTER TABLE `t` MODIFY `id` ... AUTO_INCREMENT, AUTO_INCREMENT=N;" is not
//    supported by TiDB. Mark the column AUTO_INCREMENT inside CREATE TABLE and
//    keep only the counter as "ALTER TABLE `t` AUTO_INCREMENT=N".
$sql = preg_replace_callback(
    '/ALTER TABLE `(\w+)`\s+MODIFY `(\w+)` ([^;]*?) AUTO_INCREMENT(?:, AUTO_INCREMENT=(\d+))?;/',
    function ($m) {
        [$all, $table, $col, , $next] = $m + [4 => null];
        $GLOBALS['__autoinc'][$table] = $col;
        return $next ? "ALTER TABLE `$table` AUTO_INCREMENT=$next;" : '';
    },
    $sql
);
foreach ($GLOBALS['__autoinc'] ?? [] as $table => $col) {
    $sql = preg_replace(
        '/(CREATE TABLE `' . preg_quote($table, '/') . '` \(.*?\n  `' . preg_quote($col, '/') . '` [^\n,]*?NOT NULL)/s',
        '$1 AUTO_INCREMENT',
        $sql,
        1
    );
}

// 4. Run statement by statement so one incompatible line is logged, not fatal.
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)), function ($s) {
    $s = preg_replace('/^(--[^\n]*\n|\s)+/', '', $s);
    return $s !== '' && strpos($s, '--') !== 0;
});

echo "[init_db] importing " . count($statements) . " statements (" . count($primaryKeys) . " tables)\n";
mysqli_report(MYSQLI_REPORT_OFF);
$failed = 0;
foreach ($statements as $stmt) {
    if (!$conn->query($stmt)) {
        $failed++;
        fwrite(STDERR, "[init_db] statement failed ({$conn->errno} {$conn->error}): " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 160) . "\n");
    }
}

if ($failed === 0) {
    $conn->query("CREATE TABLE `_medicore_init` (id INT PRIMARY KEY, done_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conn->query("INSERT INTO `_medicore_init` (id) VALUES (1)");
    echo "[init_db] import finished successfully\n";
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    medicore_sync_admin_password($conn);
} else {
    fwrite(STDERR, "[init_db] import finished with $failed failed statement(s); will retry on next start\n");
}
