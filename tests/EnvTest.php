<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\Env;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
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
        $this->resetEnvState();
        $this->cleanupFixtures();
    }

    private function resetEnvState(): void
    {
        $reflection = new ReflectionClass(Env::class);

        $loadedProperty = $reflection->getProperty('loaded');
        $loadedProperty->setValue(null, false);

        $repositoryProperty = $reflection->getProperty('repository');
        $repositoryProperty->setValue(null, null);
    }

    private function cleanupFixtures(): void
    {
        $files = glob($this->fixturesPath . '/.env*');
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($this->fixturesPath);
    }

    private function createEnvFile(string $content, string $filename = '.env'): void
    {
        file_put_contents($this->fixturesPath . '/' . $filename, $content);
    }

    // --- isLoaded() ---

    #[Test]
    public function isLoadedReturnsFalseByDefault(): void
    {
        $this->assertFalse(Env::isLoaded());
    }

    #[Test]
    public function isLoadedReturnsTrueAfterLoad(): void
    {
        $this->createEnvFile('TEST_VAR=value');
        Env::load($this->fixturesPath);

        $this->assertTrue(Env::isLoaded());
    }

    // --- getRepository() ---

    #[Test]
    public function getRepositoryReturnsSameInstance(): void
    {
        $first = Env::getRepository();
        $second = Env::getRepository();

        $this->assertSame($first, $second);
    }

    // --- load() ---

    #[Test]
    public function loadReturnsArrayOfVariables(): void
    {
        $this->createEnvFile("FOO=bar\nBAZ=qux");

        $result = Env::load($this->fixturesPath);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('FOO', $result);
        $this->assertArrayHasKey('BAZ', $result);
    }

    #[Test]
    public function loadWithCustomFilename(): void
    {
        $this->createEnvFile('CUSTOM_VAR=custom', '.env.production');

        $result = Env::load($this->fixturesPath, '.env.production');

        $this->assertArrayHasKey('CUSTOM_VAR', $result);
        $this->assertEquals('custom', Env::get('CUSTOM_VAR'));
    }

    // --- get() with defaults ---

    #[Test]
    public function getReturnsNullForMissingKeyWithNoDefault(): void
    {
        $this->createEnvFile('');
        Env::load($this->fixturesPath);

        $this->assertNull(Env::get('NONEXISTENT'));
    }

    #[Test]
    public function getReturnsDefaultForMissingKey(): void
    {
        $this->createEnvFile('');
        Env::load($this->fixturesPath);

        $this->assertEquals('fallback', Env::get('NONEXISTENT', 'fallback'));
    }

    #[Test]
    public function getExecutesClosureDefault(): void
    {
        $this->createEnvFile('');
        Env::load($this->fixturesPath);
        $called = false;

        $result = Env::get('NONEXISTENT', function () use (&$called) {
            $called = true;
            return 'computed';
        });

        $this->assertTrue($called);
        $this->assertEquals('computed', $result);
    }

    #[Test]
    public function getDoesNotExecuteClosureWhenValueExists(): void
    {
        $this->createEnvFile('EXISTS=value');
        Env::load($this->fixturesPath);
        $called = false;

        $result = Env::get('EXISTS', function () use (&$called) {
            $called = true;
            return 'should not be called';
        });

        $this->assertFalse($called);
        $this->assertEquals('value', $result);
    }

    // --- get() value parsing ---

    #[Test]
    public function getParsesBooleanValues(): void
    {
        $this->createEnvFile(implode("\n", [
            'BOOL_TRUE_LOWER=true',
            'BOOL_TRUE_UPPER=TRUE',
            'BOOL_TRUE_MIXED=True',
            'BOOL_TRUE_PAREN=(true)',
            'BOOL_TRUE_PAREN_UPPER=(TRUE)',
            'BOOL_FALSE_LOWER=false',
            'BOOL_FALSE_UPPER=FALSE',
            'BOOL_FALSE_MIXED=False',
            'BOOL_FALSE_PAREN=(false)',
            'BOOL_FALSE_PAREN_UPPER=(FALSE)',
        ]));
        Env::load($this->fixturesPath);

        // True values
        $this->assertTrue(Env::get('BOOL_TRUE_LOWER'), 'lowercase true');
        $this->assertTrue(Env::get('BOOL_TRUE_UPPER'), 'uppercase TRUE');
        $this->assertTrue(Env::get('BOOL_TRUE_MIXED'), 'mixed case True');
        $this->assertTrue(Env::get('BOOL_TRUE_PAREN'), 'parenthesized (true)');
        $this->assertTrue(Env::get('BOOL_TRUE_PAREN_UPPER'), 'parenthesized uppercase (TRUE)');

        // False values
        $this->assertFalse(Env::get('BOOL_FALSE_LOWER'), 'lowercase false');
        $this->assertFalse(Env::get('BOOL_FALSE_UPPER'), 'uppercase FALSE');
        $this->assertFalse(Env::get('BOOL_FALSE_MIXED'), 'mixed case False');
        $this->assertFalse(Env::get('BOOL_FALSE_PAREN'), 'parenthesized (false)');
        $this->assertFalse(Env::get('BOOL_FALSE_PAREN_UPPER'), 'parenthesized uppercase (FALSE)');
    }

    #[Test]
    public function getParsesNullValues(): void
    {
        $this->createEnvFile(implode("\n", [
            'NULL_LOWER=null',
            'NULL_UPPER=NULL',
            'NULL_PAREN=(null)',
        ]));
        Env::load($this->fixturesPath);

        $this->assertNull(Env::get('NULL_LOWER'), 'lowercase null');
        $this->assertNull(Env::get('NULL_UPPER'), 'uppercase NULL');
        $this->assertNull(Env::get('NULL_PAREN'), 'parenthesized (null)');
    }

    #[Test]
    public function getParsesEmptyValues(): void
    {
        $this->createEnvFile(implode("\n", [
            'EMPTY_LOWER=empty',
            'EMPTY_UPPER=EMPTY',
            'EMPTY_PAREN=(empty)',
        ]));
        Env::load($this->fixturesPath);

        $this->assertSame('', Env::get('EMPTY_LOWER'), 'lowercase empty');
        $this->assertSame('', Env::get('EMPTY_UPPER'), 'uppercase EMPTY');
        $this->assertSame('', Env::get('EMPTY_PAREN'), 'parenthesized (empty)');
    }

    #[Test]
    public function getStripsQuotes(): void
    {
        $this->createEnvFile(implode("\n", [
            'DOUBLE_QUOTED="hello world"',
            "SINGLE_QUOTED='hello world'",
        ]));
        Env::load($this->fixturesPath);

        $this->assertEquals('hello world', Env::get('DOUBLE_QUOTED'), 'double quotes');
        $this->assertEquals('hello world', Env::get('SINGLE_QUOTED'), 'single quotes');
    }

    #[Test]
    public function getPreservesPlainStrings(): void
    {
        $this->createEnvFile(implode("\n", [
            'PLAIN_STRING=hello',
            'NUMERIC_STRING=3000',
        ]));
        Env::load($this->fixturesPath);

        $this->assertEquals('hello', Env::get('PLAIN_STRING'));
        $this->assertSame('3000', Env::get('NUMERIC_STRING'));
    }
}
