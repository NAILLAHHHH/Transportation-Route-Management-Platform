<?php

/**
 * Patches Laravel framework database config for PHP 8.5 PDO deprecation.
 * Re-run automatically after composer update via post-autoload-dump.
 */

$file = dirname(__DIR__).'/vendor/laravel/framework/config/database.php';

if (! file_exists($file)) {
    exit(0);
}

$content = file_get_contents($file);

if (str_contains($content, '$mysqlSslCa')) {
    exit(0);
}

$content = str_replace(
    "<?php\n\nuse Illuminate\\Support\\Str;\n\nreturn [",
    "<?php\n\nuse Illuminate\\Support\\Str;\n\n\$mysqlSslCa = defined('Pdo\\Mysql::ATTR_SSL_CA')\n    ? \\Pdo\\Mysql::ATTR_SSL_CA\n    : \\PDO::MYSQL_ATTR_SSL_CA;\n\nreturn [",
    $content
);

$content = str_replace(
    'PDO::MYSQL_ATTR_SSL_CA => env(\'MYSQL_ATTR_SSL_CA\')',
    '$mysqlSslCa => env(\'MYSQL_ATTR_SSL_CA\')',
    $content
);

file_put_contents($file, $content);
