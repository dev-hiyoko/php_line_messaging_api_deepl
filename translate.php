<?php
require_once 'config/config.php';
require_once 'config/error_log_config.php';

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
    
    // 英語パターン（アルファベットのみ、または一般的な英語記号を含む）
    if (preg_match('/^[a-zA-Z0-9\s\.,!?\'";\-:()]+$/u', $text)) {
        return 'en';
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
            'content' => $postData,
            'timeout' => 10 // 10秒のタイムアウト
        ]
    ];
    
    if (DEBUG_MODE) {
        error_log("DeepL Request: URL=" . DEEPL_API_URL . ", Data=" . $postData);
    }
    
    $context = stream_context_create($options);
    $response = @file_get_contents(DEEPL_API_URL, false, $context);
    
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
 * Claude APIを使用して翻訳する
 * @param string $text 翻訳するテキスト
 * @param string $sourceLang 元言語
 * @param string $targetLang 翻訳先言語
 * @return array 翻訳結果またはエラー情報
 */
function translateWithClaude($text, $sourceLang, $targetLang) {
    if (empty(trim($text))) {
        return [
            'success' => false,
            'error' => ERROR_EMPTY_MESSAGE
        ];
    }
    
    // 翻訳プロンプトを作成
    $prompt = "Translate the following text ";
    
    if ($sourceLang === 'ja' && $targetLang === 'zh-hant') {
        $prompt .= "from Japanese to Traditional Chinese (Taiwan): \"$text\"";
    } else if ($sourceLang === 'en' && $targetLang === 'ja') {
        $prompt .= "from English to Japanese: \"$text\"";
    } else if ($targetLang === 'ja') {
        $prompt .= "from Chinese to Japanese: \"$text\"";
    } else {
        $prompt .= "to the appropriate language: \"$text\"";
    }
    
    $prompt .= ". Return only the translated text without any explanation.";
    
    // Claude APIリクエストを準備
    $postData = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 1024,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                       "x-api-key: " . CLAUDE_API_KEY . "\r\n" .
                       "anthropic-version: 2023-06-01\r\n",
            'content' => json_encode($postData),
            'timeout' => 15,
            'ignore_errors' => true  // エラー時もレスポンスボディを取得
        ]
    ];
    
    if (DEBUG_MODE) {
        error_log("Claude API Request: " . json_encode($postData));
    }
    
    $context = stream_context_create($options);
    $response = file_get_contents(CLAUDE_API_URL, false, $context);
    
    // HTTPステータスコードをチェック
    $http_status = 200;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\d\.\d (\d+)/', $header, $matches)) {
                $http_status = intval($matches[1]);
                break;
            }
        }
    }
    
    if (DEBUG_MODE && $response) {
        error_log("Claude API Response (Status: $http_status): " . $response);
    }
    
    if ($response === false || $http_status >= 400) {
        if (DEBUG_MODE) {
            $error = error_get_last();
            error_log("Claude API Error: " . json_encode($error));
            $http_response_header_string = isset($http_response_header) ? implode("\n", $http_response_header) : "No headers";
            error_log("HTTP Headers: " . $http_response_header_string);
        }
        
        // エラーレスポンスを解析
        if ($response) {
            $errorData = json_decode($response, true);
            if (isset($errorData['error'])) {
                if (DEBUG_MODE) {
                    error_log("Claude API Error Details: " . json_encode($errorData['error']));
                }
                
                // クレジット不足エラーの場合
                if (isset($errorData['error']['message']) && 
                    strpos($errorData['error']['message'], 'credit balance') !== false) {
                    return [
                        'success' => false,
                        'error' => 'Claude API credit balance is insufficient. Please check your account.'
                    ];
                }
            }
        }
        
        return [
            'success' => false,
            'error' => ERROR_TRANSLATION_FAILED
        ];
    }
    
    $responseData = json_decode($response, true);
    
    if (!$responseData || !isset($responseData['content'])) {
        if (DEBUG_MODE) {
            error_log("Invalid Claude API Response format");
        }
        return [
            'success' => false,
            'error' => ERROR_TRANSLATION_FAILED
        ];
    }
    
    $translatedText = trim($responseData['content'][0]['text']);
    
    return [
        'success' => true,
        'translated_text' => $translatedText,
        'detected_source_language' => $sourceLang
    ];
}

/**
 * メインの翻訳処理
 * @param string $inputText 入力テキスト
 * @return array 翻訳結果
 */
function processTranslation($inputText) {
    $detectedLang = detectLanguage($inputText);
    
    // 翻訳方法を選択
    if (TRANSLATION_METHOD === 'claude') {
        if ($detectedLang === 'ja') {
            $result = translateWithClaude($inputText, 'ja', 'zh-hant');
        } else if ($detectedLang === 'en') {
            $result = translateWithClaude($inputText, 'en', 'ja');
        } else {
            $result = translateWithClaude($inputText, 'zh', 'ja');
        }
    } else {
        // DeepL翻訳
        if ($detectedLang === 'ja') {
            $result = translateText($inputText, 'JA', 'ZH-HANT');
        } else if ($detectedLang === 'en') {
            $result = translateText($inputText, 'EN', 'JA');
        } else {
            $result = translateText($inputText, 'auto', 'JA');
        }
    }
    
    if (DEBUG_MODE) {
        error_log("Translation - Method: " . TRANSLATION_METHOD . ", Input: $inputText, Detected: $detectedLang, Result: " . json_encode($result));
    }
    
    return $result;
}
?>