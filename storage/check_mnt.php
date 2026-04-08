<?php

$path = '/mnt/h';

echo json_encode([
    'exists' => is_dir($path),
    'is_link' => is_link($path),
    'realpath' => realpath($path) ?: null,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
