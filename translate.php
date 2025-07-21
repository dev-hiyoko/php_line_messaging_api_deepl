<?php
require_once 'config.php';

/**
 * 言語を自動判定する
 * @param string $text 判定するテキスト
 * @return string 'ja' または 'zh-tw'
 */
function detectLanguage($text) {
    // ひらがな・カタカナが含まれている場合は確実に日本語
    if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text)) {
        return 'ja';
    }
    
    // 漢字のみの場合
    if (preg_match('/[\x{4E00}-\x{9FAF}]/u', $text)) {
        // 繁体中文特有の文字をチェック
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text) && 
            !preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text)) {
            
            // 簡易的に文字数で判断（短い場合は中文として扱う）
            if (mb_strlen($text) <= 10) {
                return 'zh';
            }
            
            // 長い場合はデフォルトで中文
            return 'zh';
        }
        return 'ja';
    }
    
    // その他の場合は中国語として扱う
    return 'zh';
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
    
    $postData = [
        'text' => [$text],
        'target_lang' => strtoupper($targetLang)
    ];
    
    // source_langは省略可能（DeepLが自動検出）
    if ($sourceLang !== 'auto') {
        $postData['source_lang'] = strtoupper($sourceLang);
    }
    
    $postData = json_encode($postData);
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                       "Authorization: DeepL-Auth-Key " . DEEPL_API_KEY . "\r\n",
            'content' => $postData
        ]
    ];
    
    if (DEBUG_MODE) {
        error_log("DeepL Request: URL=" . DEEPL_API_URL . ", Data=" . $postData);
    }
    
    $context = stream_context_create($options);
    $response = file_get_contents(DEEPL_API_URL, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        if (DEBUG_MODE) {
            error_log("DeepL API Error: " . json_encode($error));
            $http_response_header_string = isset($http_response_header) ? implode("\n", $http_response_header) : "No headers";
            error_log("HTTP Headers: " . $http_response_header_string);
        }
        return [
            'success' => false,
            'error' => ERROR_TRANSLATION_FAILED
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (DEBUG_MODE) {
        error_log("DeepL API Response: " . $response);
    }
    
    if (!$responseData || !isset($responseData['translations'])) {
        // エラーレスポンスをチェック
        if (isset($responseData['message'])) {
            if (DEBUG_MODE) {
                error_log("DeepL Error Message: " . $responseData['message']);
            }
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
        // 日本語 → 繁体中文
        $result = translateText($inputText, 'JA', 'ZH-HANT');
    } else {
        // 中国語 → 日本語（source_langは自動検出）
        $result = translateText($inputText, 'auto', 'JA');
    }
    
    if (DEBUG_MODE) {
        error_log("Translation - Input: $inputText, Detected: $detectedLang, Result: " . json_encode($result));
    }
    
    return $result;
}
?>