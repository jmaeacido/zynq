<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\Process\Process;

abstract class DuskTestCase extends BaseTestCase
{
    protected static ?Process $serverProcess = null;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        static::ensureDuskDatabaseExists();
        static::startApplicationServer();

        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    #[AfterClass]
    public static function stopApplicationServer(): void
    {
        static::$serverProcess?->stop();
        static::$serverProcess = null;
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--ignore-certificate-errors',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    protected static function ensureDuskDatabaseExists(): void
    {
        $database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: null;

        if (! $database || $database === ':memory:') {
            return;
        }

        $path = realpath($database) ?: static::basePath($database);

        if (! file_exists($path)) {
            touch($path);
        }
    }

    protected static function startApplicationServer(): void
    {
        $url = rtrim($_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://127.0.0.1:9516', '/');

        if (@file_get_contents($url) !== false) {
            return;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: '127.0.0.1';
        $port = (string) (parse_url($url, PHP_URL_PORT) ?: 80);

        static::$serverProcess = new Process([PHP_BINARY, 'artisan', 'serve', '--host='.$host, '--port='.$port], static::basePath());
        static::$serverProcess->setTimeout(null)->start();

        $started = false;
        $deadline = microtime(true) + 15;

        while (microtime(true) < $deadline) {
            if (@file_get_contents($url) !== false) {
                $started = true;
                break;
            }

            usleep(250000);
        }

        if (! $started) {
            static::$serverProcess->stop();
            static::$serverProcess = null;
        }
    }

    protected static function basePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__);

        return $path ? $basePath.DIRECTORY_SEPARATOR.$path : $basePath;
    }
}
