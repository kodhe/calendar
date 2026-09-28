<?php

declare(strict_types=1);

/**
 * Test bootstrap for the Kodhe Calendar package.
 *
 * Registers a PSR-4 style autoloader for both the library (src/) and
 * the tests (tests/) namespaces, and provides a minimal PHPUnit TestCase
 * shim so the test suite can run without composer/vendor installed.
 */

spl_autoload_register(function ($class) {
    $prefixes = [
        'Kodhe\\Framework\\Calendar\\Tests\\' => __DIR__ . '/',
        'Kodhe\\Framework\\Calendar\\'        => dirname(__DIR__) . '/src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
});

// Minimal PHPUnit shim (only when real PHPUnit is not available)
if (!class_exists('PHPUnit\\Framework\\TestCase', false)) {
    eval(<<<'SHIM'
namespace PHPUnit\Framework;

class AssertionFailedError extends \Exception {}

abstract class TestCase
{
    public static int $passedCount = 0;
    public static int $failedCount = 0;
    /** @var string[] */
    public array $failures = [];

    protected function setUp(): void {}
    protected function tearDown(): void {}

    public function assertSame($expected, $actual, string $message = ''): void
    {
        $this->evaluate($expected === $actual, $message ?: 'Failed asserting that two values are identical.');
    }

    public function assertEquals($expected, $actual, string $message = ''): void
    {
        $this->evaluate($expected == $actual, $message ?: 'Failed asserting that two values are equal.');
    }

    public function assertTrue($value, string $message = ''): void
    {
        $this->evaluate($value === true, $message ?: 'Failed asserting that value is true.');
    }

    public function assertFalse($value, string $message = ''): void
    {
        $this->evaluate($value === false, $message ?: 'Failed asserting that value is false.');
    }

    public function assertCount(int $expected, $countable, string $message = ''): void
    {
        $this->evaluate(count($countable) === $expected, $message ?: 'Failed asserting that actual size matches expected size.');
    }

    public function assertEmpty($value, string $message = ''): void
    {
        $this->evaluate(empty($value), $message ?: 'Failed asserting that value is empty.');
    }

    public function assertNotEmpty($value, string $message = ''): void
    {
        $this->evaluate(!empty($value), $message ?: 'Failed asserting that value is not empty.');
    }

    public function assertNull($value, string $message = ''): void
    {
        $this->evaluate($value === null, $message ?: 'Failed asserting that value is null.');
    }

    public function assertNotNull($value, string $message = ''): void
    {
        $this->evaluate($value !== null, $message ?: 'Failed asserting that value is not null.');
    }

    public function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        $this->evaluate(str_contains($haystack, $needle), $message ?: "Failed asserting that string contains '{$needle}'.");
    }

    public function assertMatchesRegularExpression(string $pattern, string $subject, string $message = ''): void
    {
        $this->evaluate((bool) preg_match($pattern, $subject), $message ?: "Failed asserting that string matches pattern.");
    }

    public function expectException(string $class): void
    {
        $this->expectedException = $class;
    }

    protected ?string $expectedException = null;

    private function evaluate(bool $ok, string $message): void
    {
        if (!$ok) {
            self::$failedCount++;
            $this->failures[] = $message;
            throw new AssertionFailedError($message);
        }
    }
}
SHIM);
}
