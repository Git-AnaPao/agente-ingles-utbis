<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProjectConfigurationTest extends TestCase
{
    public function test_dependency_manifests_use_one_tailwind_branch_and_a_bounded_spreadsheet_version(): void
    {
        $root = dirname(__DIR__, 2);
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $package = json_decode((string) file_get_contents($root.'/package.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('^5.9', $composer['require']['phpoffice/phpspreadsheet']);
        $this->assertContains('@php scripts/setup.php', $composer['scripts']['setup']);
        $this->assertSame('^20.19.0 || >=22.12.0', $package['engines']['node']);
        $this->assertSame('^3.1.0', $package['devDependencies']['tailwindcss']);
        $this->assertArrayNotHasKey('@tailwindcss/vite', $package['devDependencies']);
    }

    public function test_example_environment_contains_optional_google_and_blank_generated_secrets(): void
    {
        $environment = (string) file_get_contents(dirname(__DIR__, 2).'/.env.example');

        $this->assertStringContainsString("APP_KEY=\n", $environment);
        $this->assertStringContainsString("JWT_SECRET=\n", $environment);
        $this->assertStringContainsString("GOOGLE_SERVICE_ACCOUNT_PATH=\n", $environment);
    }
}
