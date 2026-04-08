<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$targetPath = $argv[1] ?? 'H:\\2026\\Assets\\prop\\11111';

try {
 $lib = new App\Module\AssetLibrary();
 $result = $lib->verifyDirectoryCreation($targetPath);

 echo json_encode([
 'ok' => true,
 'result' => $result,
 'runtime_realpath' => realpath($result['runtime_path']) ?: null,
 ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
 echo json_encode([
 'ok' => false,
 'error' => $e->getMessage(),
 'type' => get_class($e),
 'target' => $targetPath,
 ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
 exit(1);
}
