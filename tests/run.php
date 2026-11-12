<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Tests\Support\TestContext;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$started = microtime(true);
$passed = 0;
$failed = 0;
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (str_ends_with($file->getFilename(), 'Test.php')) {
        $files[] = $file->getPathname();
    }
}
sort($files);

foreach ($files as $file) {
    echo "\n" . substr($file, strlen(__DIR__) + 1) . "\n";
    try {
        $tests = require $file;
        if (!is_array($tests) || $tests === []) {
            throw new RuntimeException('The file must return a list of tests.');
        }
    } catch (Throwable $exception) {
        $failed++;
        echo "  [FAIL] Loading: {$exception->getMessage()}\n";
        continue;
    }
    foreach ($tests as $name => $test) {
        try {
            $test(new TestContext());
            $passed++;
            echo "  [OK] {$name}\n";
        } catch (Throwable $exception) {
            $failed++;
            echo "  [FAIL] {$name}\n    {$exception->getMessage()}\n    {$exception->getFile()}:{$exception->getLine()}\n";
        }
    }
}

if ($passed + $failed === 0) {
    fwrite(STDERR, "No tests found.\n");
    exit(1);
}
printf("\n%d tests | %d passed | %d failed | %.2fs\n", $passed + $failed, $passed, $failed, microtime(true) - $started);
exit($failed > 0 ? 1 : 0);
