<?php

declare(strict_types = 1);

use JohannSchopplich\Helpers\Vite;
use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ViteTest extends TestCase
{
    private string $prodRoot;

    protected function setUp(): void
    {
        $this->prodRoot = __DIR__ . '/tmp-vite';
        $manifestDir = $this->prodRoot . '/dist/.vite';
        if (!is_dir($manifestDir)) {
            mkdir($manifestDir, 0755, true);
        }

        file_put_contents($manifestDir . '/manifest.json', json_encode([
            'src/main.js' => [
                'file' => 'assets/main-abc.js',
                'css' => ['assets/main-abc.css'],
                'imports' => ['_shared-xyz.js'],
            ],
            '_shared-xyz.js' => [
                'file' => 'assets/shared-xyz.js',
                'css' => ['assets/shared-xyz.css'],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->prodRoot . '/dist/.vite/manifest.json');
        @rmdir($this->prodRoot . '/dist/.vite');
        @rmdir($this->prodRoot . '/dist');
        @rmdir($this->prodRoot);
        App::destroy();
    }

    private function prodVite(array $options = []): Vite
    {
        new App([
            'roots' => ['index' => $this->prodRoot],
            'urls' => ['index' => 'https://example.com'],
            'options' => $options,
        ]);

        return new Vite();
    }

    private function devVite(array $options = []): Vite
    {
        new App([
            'roots' => ['index' => __DIR__ . '/tmp-vite-dev'],
            'options' => $options,
        ]);

        return new Vite();
    }

    #[Test]
    public function is_dev_when_the_manifest_is_missing(): void
    {
        $this->assertTrue($this->devVite()->isDev());
    }

    #[Test]
    public function is_not_dev_when_the_manifest_is_present(): void
    {
        $this->assertFalse($this->prodVite()->isDev());
    }

    #[Test]
    public function builds_the_dev_url_from_server_options(): void
    {
        $vite = $this->devVite([
            'johannschopplich.helpers.vite.server.host' => '0.0.0.0',
            'johannschopplich.helpers.vite.server.port' => 3000,
            'johannschopplich.helpers.vite.server.https' => true,
        ]);

        $this->assertSame('https://0.0.0.0:3000/src/main.js', $vite->devUrl('src/main.js'));
    }

    #[Test]
    public function prefixes_the_out_dir_for_prod_urls(): void
    {
        $this->assertSame(
            'https://example.com/dist/assets/main-abc.js',
            $this->prodVite()->prodUrl('assets/main-abc.js')
        );
    }

    #[Test]
    public function injects_the_vite_client_once_in_dev(): void
    {
        $vite = $this->devVite();

        $first = $vite->js('src/main.js');
        $second = $vite->js('src/other.js');

        $this->assertStringContainsString('http://localhost:5173/@vite/client', $first);
        $this->assertStringContainsString('http://localhost:5173/src/main.js', $first);
        $this->assertStringNotContainsString('@vite/client', $second);
    }

    #[Test]
    public function emits_the_hashed_entry_file_in_prod(): void
    {
        $html = $this->prodVite()->js('src/main.js');

        $this->assertStringContainsString('https://example.com/dist/assets/main-abc.js', $html);
        $this->assertStringNotContainsString('@vite/client', $html);
    }

    #[Test]
    public function skips_a_missing_entry_in_prod_instead_of_crashing(): void
    {
        $this->assertSame('', $this->prodVite()->js('src/missing.js'));
    }

    #[Test]
    public function collects_imported_module_css_in_prod(): void
    {
        $html = $this->prodVite()->css('src/main.js');

        $this->assertStringContainsString('https://example.com/dist/assets/main-abc.css', $html);
        $this->assertStringContainsString('https://example.com/dist/assets/shared-xyz.css', $html);
    }

    #[Test]
    public function returns_no_css_in_dev(): void
    {
        $this->assertNull($this->devVite()->css('src/main.js'));
    }

    #[Test]
    public function file_resolves_dev_and_prod_urls(): void
    {
        $this->assertSame('http://localhost:5173/src/main.js', $this->devVite()->file('src/main.js'));
        $this->assertSame('https://example.com/dist/assets/main-abc.js', $this->prodVite()->file('src/main.js'));
    }
}
