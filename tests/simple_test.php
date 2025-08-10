<?php
// 最小限のテストスクリプト
echo "PHP is working!\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script path: " . __FILE__ . "\n";

// ファイル存在確認
$config_path = '../config/config.php';
echo "Config file exists: " . (file_exists($config_path) ? 'YES' : 'NO') . "\n";
echo "Config file path: " . realpath($config_path) . "\n";

// ディレクトリ構造を確認
echo "\nDirectory structure:\n";
echo "Parent directory contents:\n";
system('ls -la ../');
echo "\nConfig directory contents:\n";
system('ls -la ../config/ 2>/dev/null || echo "Config directory not found"');

if (file_exists($config_path)) {
    echo "Attempting to include config...\n";
    require_once $config_path;
    echo "Config included successfully!\n";
    echo "DEBUG_MODE defined: " . (defined('DEBUG_MODE') ? 'YES' : 'NO') . "\n";
} else {
    echo "Config file not found!\n";
}

echo "Test completed.\n";
?>