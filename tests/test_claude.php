<?php
// Claude API翻訳機能のテストスクリプト
require_once '../config/config.php';
require_once '../translate.php';

// テスト用のテキストサンプル
$testCases = [
    'こんにちは、世界！' => 'Japanese to Chinese',
    'Hello, World!' => 'English to Japanese',
    '你好，世界！' => 'Chinese to Japanese',
    '今日はいい天気ですね。' => 'Japanese to Chinese',
    'How are you today?' => 'English to Japanese'
];

echo "Claude API Translation Test\n";
echo "============================\n\n";

// 設定の確認
if (!defined('CLAUDE_API_KEY') || CLAUDE_API_KEY === 'your_claude_api_key_here') {
    echo "Error: CLAUDE_API_KEY is not configured in config.php\n";
    exit(1);
}

if (!defined('CLAUDE_API_URL')) {
    echo "Error: CLAUDE_API_URL is not defined in config.php\n";
    exit(1);
}

if (!defined('CLAUDE_MODEL')) {
    echo "Error: CLAUDE_MODEL is not defined in config.php\n";
    exit(1);
}

echo "Configuration:\n";
echo "- API URL: " . CLAUDE_API_URL . "\n";
echo "- Model: " . CLAUDE_MODEL . "\n";
echo "- Translation Method: " . TRANSLATION_METHOD . "\n\n";

// 各テストケースを実行
foreach ($testCases as $text => $description) {
    echo "Test: $description\n";
    echo "Input: $text\n";
    
    // 言語を検出
    $detectedLang = detectLanguage($text);
    echo "Detected Language: $detectedLang\n";
    
    // 翻訳を実行
    if ($detectedLang === 'ja') {
        $result = translateWithClaude($text, 'ja', 'zh-hant');
    } else if ($detectedLang === 'en') {
        $result = translateWithClaude($text, 'en', 'ja');
    } else {
        $result = translateWithClaude($text, 'zh', 'ja');
    }
    
    if ($result['success']) {
        echo "Translation: " . $result['translated_text'] . "\n";
        echo "Status: ✓ Success\n";
    } else {
        echo "Error: " . $result['error'] . "\n";
        echo "Status: ✗ Failed\n";
    }
    
    echo "----------------------------\n\n";
    
    // API レート制限のため少し待つ
    sleep(1);
}

echo "Test completed.\n";
?>