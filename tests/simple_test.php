<?php
// 最小限のテストスクリプト
echo "PHP is working!\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script path: " . __FILE__ . "\n";

// ファイル存在確認（サーバー上の実際の構造に基づく）
$config_paths = [
    '../config.php',           // ルートにある場合
    '../config/config.php',    // configディレクトリ内にある場合
];

$config_path = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        echo "Config file found at: $path\n";
        echo "Real path: " . realpath($path) . "\n";
        break;
    }
}

if (!$config_path) {
    echo "Config file not found in any expected location!\n";
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
    echo "Attempting to include config...\n";
    require_once $config_path;
    echo "Config included successfully!\n";
    echo "DEBUG_MODE defined: " . (defined('DEBUG_MODE') ? 'YES' : 'NO') . "\n";
} else {
    echo "Config file not found!\n";
}

echo "Test completed.\n";
?>