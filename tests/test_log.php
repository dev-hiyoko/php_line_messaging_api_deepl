<?php
// エラーログのテストスクリプト
echo "Starting log test...\n";

// パスを動的に検出
$config_paths = [
    '../config/config.php',
    'config/config.php',
];

$error_config_paths = [
    '../config/error_log_config.php',
    'config/error_log_config.php',
];

$config_path = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}

$error_config_path = null;
foreach ($error_config_paths as $path) {
    if (file_exists($path)) {
        $error_config_path = $path;
        break;
    }
}

if (!$config_path) {
    die("Config file not found!\n");
}

if (!$error_config_path) {
    die("Error config file not found!\n");
}

echo "Loading config from: $config_path\n";
echo "Loading error config from: $error_config_path\n";

require_once $config_path;
require_once $error_config_path;

echo "Testing error logging on Sakura Internet...\n\n";

// 0. 設定確認
echo "0. Configuration check:\n";
echo "   DEBUG_MODE: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : 'undefined') . "\n";
echo "   Current error_log setting: " . ini_get('error_log') . "\n\n";

// 1. 標準のerror_log関数をテスト
echo "1. Testing standard error_log function...\n";
$error_log_result = error_log("Test message from error_log function - " . date('Y-m-d H:i:s'));
echo "   error_log() returned: " . ($error_log_result ? 'true' : 'false') . "\n";

// ログファイルの内容をすぐに確認
$error_log_path = ini_get('error_log');
if (file_exists($error_log_path)) {
    $content = file_get_contents($error_log_path);
    $lines = explode("\n", trim($content));
    echo "   Last line in error.log: " . end($lines) . "\n";
} else {
    echo "   error.log does not exist at: $error_log_path\n";
}

echo "\n2. Testing custom writeLog function...\n";
try {
    writeLog("Test message from writeLog function - " . date('Y-m-d H:i:s'), "TEST");
    echo "   writeLog() executed successfully\n";
    
    // app_error.logの内容を確認
    $app_log_path = dirname(__DIR__) . '/logs/app_error.log';
    if (file_exists($app_log_path)) {
        $content = file_get_contents($app_log_path);
        $lines = explode("\n", trim($content));
        echo "   Last line in app_error.log: " . end($lines) . "\n";
    } else {
        echo "   app_error.log does not exist at: $app_log_path\n";
    }
} catch (Exception $e) {
    echo "   writeLog() failed with error: " . $e->getMessage() . "\n";
}

// 3. ファイル権限の確認
echo "\n3. File permissions:\n";
$files = ['../logs/error.log', '../logs/app_error.log'];
foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        echo "   $file: " . substr(sprintf('%o', $perms), -4) . "\n";
    } else {
        echo "   $file: Does not exist\n";
    }
}

// 4. PHPの設定確認
echo "\n4. PHP settings:\n";
echo "   error_log: " . ini_get('error_log') . "\n";
echo "   log_errors: " . ini_get('log_errors') . "\n";
echo "   error_reporting: " . ini_get('error_reporting') . "\n";

// 5. 書き込みテスト
echo "\n5. Write test:\n";
$testFile = 'test_write.txt';
if (file_put_contents($testFile, "Test write at " . date('Y-m-d H:i:s') . "\n") !== false) {
    echo "   Successfully wrote to $testFile\n";
    unlink($testFile);
} else {
    echo "   Failed to write test file\n";
}

echo "\nTest complete. Please check the log files.\n";
?>