<?php

/**
 * Actualiza o agrega claves en un archivo .env sin dependencias.
 *
 * Uso:
 *   php scripts/set-env.php .env APP_URL=http://mi-app.test DB_DATABASE=mi_db
 */
$file = $argv[1] ?? null;

if (! $file || ! is_file($file)) {
    fwrite(STDERR, "Uso: php set-env.php <archivo.env> CLAVE=valor [CLAVE=valor...]\n");
    exit(1);
}

$content = file_get_contents($file);

foreach (array_slice($argv, 2) as $pair) {
    $pos = strpos($pair, '=');

    if ($pos === false || $pos === 0) {
        continue;
    }

    $key = substr($pair, 0, $pos);
    $value = substr($pair, $pos + 1);
    $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $key.'='.$value, $content, 1);
    } else {
        $content = rtrim($content, "\r\n").PHP_EOL.$key.'='.$value.PHP_EOL;
    }
}

file_put_contents($file, $content);

echo "OK: {$file} actualizado\n";
