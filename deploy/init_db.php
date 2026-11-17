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

$exists = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
if ($exists) {
    echo "[init_db] tables already present, nothing to import\n";
    exit(0);
}

$sql = str_replace("\r\n", "\n", file_get_contents(__DIR__ . '/../Database/medicore.sql'));

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

echo "[init_db] importing schema + seed data (" . count($primaryKeys) . " tables)\n";
$conn->multi_query($sql);
do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

echo "[init_db] import finished\n";
