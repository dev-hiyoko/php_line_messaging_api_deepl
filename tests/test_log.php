<?php
// エラーログのテストスクリプト
require_once '../config/config.php';
require_once '../config/error_log_config.php';

echo "Testing error logging on Sakura Internet...\n\n";

// 1. 標準のerror_log関数をテスト
error_log("Test message from error_log function");
echo "1. Standard error_log test - Check error.log\n";

// 2. カスタムログ関数をテスト
writeLog("Test message from writeLog function", "TEST");
echo "2. Custom writeLog test - Check app_error.log\n";

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