<?php
// Base URL of the application.
// Production (Render): APP_URL, or Render's own RENDER_EXTERNAL_URL.
// Local XAMPP: falls back to http://localhost/medicore.
if (!defined('BASE_URL')) {
    $appUrl = getenv('APP_URL') ?: getenv('RENDER_EXTERNAL_URL') ?: 'http://localhost/medicore';
    define('BASE_URL', rtrim($appUrl, '/'));
}
?>
