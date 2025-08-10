<?php
// 最小限のテストスクリプト
echo "PHP is working!\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script path: " . __FILE__ . "\n";

// ファイル存在確認 - testsディレクトリから実行する場合
$test_paths = [
    '../config/config.php',     // tests/ から見た場合
    'config/config.php',        // ルートから見た場合
];

$config_path = null;
foreach ($test_paths as $path) {
    echo "Checking path: $path - " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') . "\n";
    if (file_exists($path)) {
        $config_path = $path;
        echo "Config file found at: $path\n";
        echo "Real path: " . realpath($path) . "\n";
        break;
    }
}

// ディレクトリ構造を確認
echo "\nDirectory structure:\n";
echo "Current directory contents:\n";
system('ls -la .');
echo "\nParent directory contents:\n";
system('ls -la ../');
echo "\nline-translate directory contents:\n";
system('ls -la ../line-translate/ 2>/dev/null || echo "line-translate directory not found"');
echo "\nLooking for config in line-translate:\n";
system('ls -la ../line-translate/config/ 2>/dev/null || echo "Config directory not found in line-translate"');

if ($config_path) {
    echo "\nAttempting to include config...\n";
    require_once $config_path;
    echo "Config included successfully!\n";
    echo "DEBUG_MODE defined: " . (defined('DEBUG_MODE') ? 'YES' : 'NO') . "\n";
} else {
    echo "\nConfig file not found in any location!\n";
}

echo "Test completed.\n";
?>