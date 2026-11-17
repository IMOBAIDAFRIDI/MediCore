<?php
/**
 * Database settings, read from environment variables in production
 * (Render + Aiven MySQL) with XAMPP-friendly defaults for local use.
 *
 *   DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME, DB_SSL=1
 */
if (!function_exists('medicore_db_config')) {
    function medicore_db_config(): array
    {
        $env = function (string $key, $default = null) {
            $v = getenv($key);
            // Values pasted into hosting dashboards often carry stray spaces/newlines.
            $v = ($v === false) ? '' : trim($v);
            return $v === '' ? $default : $v;
        };
        return [
            'host' => $env('DB_HOST', 'localhost'),
            'port' => (int) $env('DB_PORT', 3306),
            'user' => $env('DB_USER', 'root'),
            'pass' => $env('DB_PASS', ''),
            'name' => $env('DB_NAME', 'medicore'),
            'ssl'  => in_array(strtolower((string) $env('DB_SSL', '0')), ['1', 'true', 'yes', 'required'], true),
        ];
    }
}
