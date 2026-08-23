<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ListeningViewSecurityTest extends TestCase
{
    public function test_listening_results_use_safe_dom_apis_and_preserve_null_state(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/listening/show.blade.php');

        $this->assertIsString($view);
        $this->assertStringNotContainsString('innerHTML', $view);
        $this->assertStringNotContainsString('insertAdjacentHTML', $view);
        $this->assertStringContainsString('textContent', $view);
        $this->assertStringContainsString('result.is_correct === null', $view);
        $this->assertStringContainsString('setInputsDisabled(true)', $view);
    }
}
