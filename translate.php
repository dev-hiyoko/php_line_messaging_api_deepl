<?php
require_once 'config.php';

/**
 * 言語を自動判定する
 * @param string $text 判定するテキスト
 * @return string 'ja' または 'zh-tw'
 */
function detectLanguage($text) {
    // 日本語の文字（ひらがな、カタカナ、漢字）を含むかチェック
    if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $text)) {
        // さらに詳細に日本語かどうかをチェック
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text)) {
            // ひらがなまたはカタカナが含まれている場合は確実に日本語
            return 'ja';
        }
        // 漢字のみの場合は文脈で判断（簡易的に日本語として扱う）
        return 'ja';
    }
    
    // その他の場合は繁体中文として扱う
    return 'zh-tw';
}

/**
 * DeepL APIを使用してテキストを翻訳する
 * @param string $text 翻訳するテキスト
 * @param string $sourceLang 元言語
 * @param string $targetLang 翻訳先言語
 * @return array 翻訳結果またはエラー情報
 */
function translateText($text, $sourceLang, $targetLang) {
    if (empty(trim($text))) {
        return [
            'success' => false,
            'error' => ERROR_EMPTY_MESSAGE
        ];
    }
    
    $postData = json_encode([
        'text' => [$text],
        'source_lang' => strtoupper($sourceLang),
        'target_lang' => strtoupper($targetLang)
    ]);
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: DeepL-Auth-Key ' . DEEPL_API_KEY
            ],
            'content' => $postData
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents(DEEPL_API_URL, false, $context);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => ERROR_TRANSLATION_FAILED
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (!$responseData || !isset($responseData['translations'])) {
        // エラーレスポンスをチェック
        if (isset($responseData['message'])) {
            if (strpos($responseData['message'], 'quota') !== false) {
                return [
                    'success' => false,
                    'error' => ERROR_CHARACTER_LIMIT
                ];
            }
        }
        
        return [
            'success' => false,
            'error' => ERROR_TRANSLATION_FAILED
        ];
    }
    
    return [
        'success' => true,
        'translated_text' => $responseData['translations'][0]['text'],
        'detected_source_language' => $responseData['translations'][0]['detected_source_language'] ?? $sourceLang
    ];
}

/**
 * メインの翻訳処理
 * @param string $inputText 入力テキスト
 * @return array 翻訳結果
 */
function processTranslation($inputText) {
    $detectedLang = detectLanguage($inputText);
    
    if ($detectedLang === 'ja') {
        // 日本語 → 中文
        $result = translateText($inputText, 'ja', 'zh');
    } else {
        // 中文 → 日本語
        $result = translateText($inputText, 'zh', 'ja');
    }
    
    if (DEBUG_MODE) {
        error_log("Translation - Input: $inputText, Detected: $detectedLang, Result: " . json_encode($result));
    }
    
    return $result;
}
?>