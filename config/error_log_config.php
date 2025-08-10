<?php
/**
 * エラーログ設定ファイル
 * さくらインターネット対応
 */

// ログディレクトリの絶対パスを取得
$logDir = dirname(__DIR__) . '/logs';

// ログディレクトリが存在しない場合は作成
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// エラーログの出力先を明示的に指定
ini_set('error_log', $logDir . '/error.log');

// エラーレポーティングレベルを設定
error_reporting(E_ALL);

// エラーログを有効化
ini_set('log_errors', 1);

// 画面へのエラー表示（本番環境では0に設定）
ini_set('display_errors', DEBUG_MODE ? 1 : 0);

// カスタムエラーログ関数
function writeLog($message, $level = 'INFO') {
    $logDir = dirname(__DIR__) . '/logs';
    
    // ログディレクトリが存在しない場合は作成
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/app_error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // ファイルに書き込み（追記モード）
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// error_log関数のラッパー
function customErrorLog($message) {
    // 標準のerror_log
    error_log($message);
    
    // カスタムログファイルにも出力
    writeLog($message, 'ERROR');
}
?>