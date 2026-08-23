<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/scripts/setup.php';

class SetupScriptTest extends TestCase
{
    public function test_sqlite_path_is_created_only_for_file_backed_sqlite_connections(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertSame(
            $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite',
            \ProjectSetup\sqliteDatabasePath($root, ['DB_CONNECTION' => 'sqlite']),
        );
        $this->assertSame(
            $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'test.sqlite',
            \ProjectSetup\sqliteDatabasePath($root, [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => 'storage/test.sqlite',
            ]),
        );
        $this->assertNull(\ProjectSetup\sqliteDatabasePath($root, ['DB_CONNECTION' => 'mysql']));
        $this->assertNull(\ProjectSetup\sqliteDatabasePath($root, [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]));
    }

    public function test_environment_values_are_added_and_replaced_without_duplicates(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'setup-env-');
        $this->assertNotFalse($path);

        try {
            file_put_contents($path, "APP_KEY=old\nDB_CONNECTION=sqlite\n");

            \ProjectSetup\setEnvironmentValue($path, 'APP_KEY', 'new');
            \ProjectSetup\setEnvironmentValue($path, 'JWT_SECRET', 'secret');

            $environment = \ProjectSetup\readEnvironment($path);

            $this->assertSame('new', $environment['APP_KEY']);
            $this->assertSame('secret', $environment['JWT_SECRET']);
            $this->assertSame(1, substr_count((string) file_get_contents($path), 'APP_KEY='));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
