<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\Env;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class EnvTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/fixtures';
        if (!is_dir($this->fixturesPath)) {
            mkdir($this->fixturesPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $files = glob($this->fixturesPath . '/.env*') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($this->fixturesPath);
    }

    private function writeEnvFile(string $content, string $filename = '.env'): void
    {
        file_put_contents($this->fixturesPath . '/' . $filename, $content);
    }

    #[Test]
    public function is_not_loaded_by_default(): void
    {
        $this->assertFalse(Env::isLoaded());
    }

    #[Test]
    public function is_loaded_after_load(): void
    {
        $this->writeEnvFile('TEST_VAR=value');
        Env::load($this->fixturesPath);

        $this->assertTrue(Env::isLoaded());
    }

    #[Test]
    public function does_not_mark_loaded_when_the_file_is_missing(): void
    {
        try {
            Env::load($this->fixturesPath . '/missing');
        } catch (\Throwable) {
            // Loading a non-existent path throws – the flag must stay false so
            // a later attempt (once the file exists) can still load
        }

        $this->assertFalse(Env::isLoaded());
    }

    #[Test]
    public function load_returns_the_parsed_variables(): void
    {
        $this->writeEnvFile("FOO=bar\nBAZ=qux");

        $result = Env::load($this->fixturesPath);

        $this->assertSame(['FOO' => 'bar', 'BAZ' => 'qux'], $result);
    }

    #[Test]
    public function loads_from_a_custom_filename(): void
    {
        $this->writeEnvFile('CUSTOM_VAR=custom', '.env.production');

        Env::load($this->fixturesPath, '.env.production');

        $this->assertSame('custom', Env::get('CUSTOM_VAR'));
    }

    #[Test]
    public function returns_null_for_a_missing_key(): void
    {
        $this->writeEnvFile('');
        Env::load($this->fixturesPath);

        $this->assertNull(Env::get('NONEXISTENT'));
    }

    #[Test]
    public function returns_the_default_for_a_missing_key(): void
    {
        $this->writeEnvFile('');
        Env::load($this->fixturesPath);

        $this->assertSame('fallback', Env::get('NONEXISTENT', 'fallback'));
    }

    #[Test]
    public function executes_a_closure_default_only_when_the_key_is_missing(): void
    {
        $this->writeEnvFile('EXISTS=value');
        Env::load($this->fixturesPath);

        $this->assertSame('computed', Env::get('MISSING', fn () => 'computed'));
        $this->assertSame('value', Env::get('EXISTS', fn () => 'unused'));
    }

    /** @return array<string, array{0: string, 1: mixed}> */
    public static function envValues(): array
    {
        return [
            'lowercase true' => ['true', true],
            'uppercase true' => ['TRUE', true],
            'mixed-case true' => ['True', true],
            'parenthesized true' => ['(true)', true],
            'lowercase false' => ['false', false],
            'uppercase false' => ['FALSE', false],
            'parenthesized false' => ['(false)', false],
            'lowercase null' => ['null', null],
            'uppercase null' => ['NULL', null],
            'parenthesized null' => ['(null)', null],
            'lowercase empty' => ['empty', ''],
            'parenthesized empty' => ['(empty)', ''],
            'double quoted' => ['"hello world"', 'hello world'],
            'single quoted' => ["'hello world'", 'hello world'],
            'plain string' => ['hello', 'hello'],
            'numeric string' => ['3000', '3000'],
        ];
    }

    #[Test]
    #[DataProvider('envValues')]
    public function parses_typed_values(string $raw, mixed $expected): void
    {
        $this->writeEnvFile("VALUE=$raw");
        Env::load($this->fixturesPath);

        $this->assertSame($expected, Env::get('VALUE'));
    }
}
