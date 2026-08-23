<?php

declare(strict_types=1);

namespace ProjectSetup;

use RuntimeException;
use Throwable;

function readEnvironment(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        throw new RuntimeException("Unable to read environment file: {$path}");
    }

    $environment = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        if (! preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $trimmed, $matches)) {
            continue;
        }

        $value = trim($matches[2]);

        if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        $environment[$matches[1]] = $value;
    }

    return $environment;
}

function sqliteDatabasePath(string $root, array $environment): ?string
{
    $databaseUrl = trim((string) ($environment['DB_URL'] ?? ''));

    if ($databaseUrl !== '') {
        $normalizedUrl = preg_replace('#^(sqlite3?):///#', '$1://null/', $databaseUrl);
        $components = parse_url((string) $normalizedUrl);

        if ($components === false || ! in_array($components['scheme'] ?? '', ['sqlite', 'sqlite3'], true)) {
            return null;
        }

        $path = $components['path'] ?? '';
        $database = $path !== '' && $path !== '/' ? rawurldecode(substr($path, 1)) : '';
    } else {
        if (strtolower((string) ($environment['DB_CONNECTION'] ?? 'sqlite')) !== 'sqlite') {
            return null;
        }

        $database = trim((string) ($environment['DB_DATABASE'] ?? ''));
    }

    if ($database === '' || $database === 'null') {
        return $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';
    }

    if ($database === ':memory:' || str_contains($database, 'mode=memory')) {
        return null;
    }

    if (isAbsolutePath($database)) {
        return $database;
    }

    return $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $database);
}

function isAbsolutePath(string $path): bool
{
    return str_starts_with($path, '/')
        || str_starts_with($path, '\\')
        || preg_match('/^[A-Z]:[\\\\\/]/i', $path) === 1;
}

function ensureSqliteDatabase(string $root, array $environment): void
{
    $path = sqliteDatabasePath($root, $environment);

    if ($path === null) {
        return;
    }

    $directory = dirname($path);

    if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create SQLite directory: {$directory}");
    }

    if (! file_exists($path)) {
        $handle = fopen($path, 'c');

        if ($handle === false) {
            throw new RuntimeException("Unable to create SQLite database: {$path}");
        }

        fclose($handle);
    }

    if (! is_file($path)) {
        throw new RuntimeException("SQLite database path is not a file: {$path}");
    }

    echo "SQLite database ready: {$path}".PHP_EOL;
}

function setEnvironmentValue(string $path, string $key, string $value): void
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Unable to read environment file: {$path}");
    }

    $entry = "{$key}={$value}";
    $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

    if (preg_match($pattern, $contents) === 1) {
        $updated = preg_replace_callback($pattern, static fn (): string => $entry, $contents, 1);
    } else {
        $updated = rtrim($contents).PHP_EOL.$entry.PHP_EOL;
    }

    if ($updated === null || file_put_contents($path, $updated) === false) {
        throw new RuntimeException("Unable to update environment file: {$path}");
    }
}

function runArtisan(string $root, array $arguments): void
{
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.DIRECTORY_SEPARATOR.'artisan');

    foreach ($arguments as $argument) {
        $command .= ' '.escapeshellarg($argument);
    }

    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        throw new RuntimeException('Artisan command failed: '.implode(' ', $arguments));
    }
}

function main(): int
{
    $root = dirname(__DIR__);
    $environmentPath = $root.DIRECTORY_SEPARATOR.'.env';

    try {
        if (! is_file($environmentPath)) {
            throw new RuntimeException('Missing .env file. Copy .env.example before running setup.');
        }

        $environment = readEnvironment($environmentPath);

        foreach (['APP_KEY', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL', 'JWT_SECRET'] as $key) {
            $value = getenv($key);

            if ($value !== false) {
                $environment[$key] = $value;
            }
        }

        ensureSqliteDatabase($root, $environment);
        runArtisan($root, ['config:clear', '--ansi', '--no-interaction']);

        if (trim((string) ($environment['APP_KEY'] ?? '')) === '') {
            runArtisan($root, ['key:generate', '--force', '--ansi', '--no-interaction']);
        }

        if (is_file($root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'jwt.php')
            && trim((string) ($environment['JWT_SECRET'] ?? '')) === '') {
            setEnvironmentValue(
                $environmentPath,
                'JWT_SECRET',
                rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '='),
            );
            echo 'JWT secret generated.'.PHP_EOL;
        }

        runArtisan($root, ['migrate', '--force', '--ansi', '--no-interaction']);
        runArtisan($root, ['db:seed', '--force', '--ansi', '--no-interaction']);
        runArtisan($root, ['import:placement-questions', '--ansi', '--no-interaction']);

        $storageLink = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'storage';

        if (! file_exists($storageLink) && ! is_link($storageLink)) {
            runArtisan($root, ['storage:link', '--ansi', '--no-interaction']);
        }

        return 0;
    } catch (Throwable $exception) {
        fwrite(STDERR, 'Setup failed: '.$exception->getMessage().PHP_EOL);

        return 1;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(main());
}
