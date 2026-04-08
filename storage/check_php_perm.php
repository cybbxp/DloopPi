<?php
$path = '/mnt/h';
$testDir = '/mnt/h/2026/Assets/prop/perm_check_' . date('Ymd_His');
$result = [
 'base_exists' => is_dir($path),
 'base_writable' => is_writable($path),
 'base_readable' => is_readable($path),
 'php_user' => function_exists('posix_geteuid') ? posix_geteuid() : null,
];
$ok = @mkdir($testDir,0777, true);
$result['mkdir_ok'] = $ok;
$result['test_dir'] = $testDir;
$result['test_dir_exists'] = is_dir($testDir);
$result['test_dir_writable'] = is_writable($testDir);
if ($ok) {
 @rmdir($testDir);
}
echo json_encode($result, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . PHP_EOL;
