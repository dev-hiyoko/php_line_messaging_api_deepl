<?php
require_once 'config/config.php';
require_once 'config/error_log_config.php';
require_once 'translate.php';

// デバッグモードでのアクセスログ（強制実行）
// DEBUG_MODE設定値: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : 'undefined')
file_put_contents('logs/force_debug.log', date('Y-m-d H:i:s') . " - Force debug start\n", FILE_APPEND);

if (true) { // 一時的に強制実行
    $access_log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    ];
    
    // 複数の方法でログを出力
    writeLog("ACCESS: " . json_encode($access_log), "ACCESS");
    error_log("WEBHOOK ACCESS: " . json_encode($access_log));
    
    // ログファイルに直接書き込みもテスト
    file_put_contents('logs/debug.log', date('Y-m-d H:i:s') . " - Direct write test\n", FILE_APPEND);
}

/**
 * LINE署名を検証する
 * @param string $channelSecret チャンネルシークレット
 * @param string $httpRequestBody リクエストボディ
 * @param string $signature LINE署名
 * @return bool 検証結果
 */
function validateLineSignature($channelSecret, $httpRequestBody, $signature) {
    $hash = hash_hmac('sha256', $httpRequestBody, $channelSecret, true);
    $expectedSignature = base64_encode($hash);
    return hash_equals($signature, $expectedSignature);
}

/**
 * LINEにメッセージを送信する
 * @param string $replyToken 返信トークン
 * @param string $message 送信メッセージ
 * @return bool 送信結果
 */
function sendLineMessage($replyToken, $message) {
    $postData = [
        'replyToken' => $replyToken,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message
            ]
        ]
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . LINE_CHANNEL_ACCESS_TOKEN
            ],
            'content' => json_encode($postData)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents(LINE_API_URL, false, $context);
    
    if (DEBUG_MODE) {
        error_log("LINE API Response: " . $response);
    }
    
    return $response !== false;
}

/**
 * メンションされているかチェックする
 * @param string $text メッセージテキスト
 * @param array $event LINEイベントデータ
 * @return bool メンションされているかどうか
 */
function isMentioned($text, $event) {
    // 1対1チャットの場合は常にメンションされているとみなす
    if ($event['source']['type'] === 'user') {
        return true;
    }
    
    // グループ・ルームチャットの場合は公式アカウント（Bot）へのメンションのみチェック
    if (isset($event['message']['mention']['mentionees'])) {
        foreach ($event['message']['mention']['mentionees'] as $mentionee) {
            // isSelfがtrueの場合のみ反応（公式アカウント自身がメンションされた場合）
            if (isset($mentionee['isSelf']) && $mentionee['isSelf'] === true) {
                return true;
            }
        }
    }
    
    // 他のユーザーへのメンションや一般的な@記号には反応しない
    return false;
}

/**
 * メンション文字列を除去する
 * @param string $text メッセージテキスト
 * @return string メンション除去後のテキスト
 */
function removeMention($text) {
    // LINEのメンション情報がある場合、先頭15文字を除去
    if (strpos($text, '@') === 0) {
        // "@a++ translater " (15文字) を除去
        $text = substr($text, 15);
    }
    
    // LINE特殊文字（メンション）を除去
    $text = preg_replace('/[\x{E000}-\x{E0FF}]+/u', '', $text);
    
    return trim($text);
}

/**
 * イベント情報を使用してメンション文字列を除去する
 * @param string $text メッセージテキスト
 * @param array $event LINEイベントデータ
 * @return string メンション除去後のテキスト
 */
function removeMentionFromEvent($text, $event) {
    if (isset($event['message']['mention']['mentionees'])) {
        foreach ($event['message']['mention']['mentionees'] as $mentionee) {
            if (isset($mentionee['isSelf']) && $mentionee['isSelf'] === true) {
                $index = $mentionee['index'];
                $length = $mentionee['length'];
                
                // メンション部分を除去
                $before = mb_substr($text, 0, $index);
                $after = mb_substr($text, $index + $length);
                $text = $before . $after;
                break;
            }
        }
    }
    
    // LINE特殊文字（メンション）を除去
    $text = preg_replace('/[\x{E000}-\x{E0FF}]+/u', '', $text);
    
    return trim($text);
}

/**
 * Webhookイベントを処理する
 * @param array $event LINEイベントデータ
 */
function handleWebhookEvent($event) {
    if ($event['type'] !== 'message' || $event['message']['type'] !== 'text') {
        return;
    }
    
    $replyToken = $event['replyToken'];
    $inputText = $event['message']['text'];
    
    if (DEBUG_MODE) {
        error_log("Received message: " . $inputText);
    }
    
    // メンション判定
    if (!isMentioned($inputText, $event)) {
        return; // メンションされていない場合は何もしない
    }
    
    // メンション文字列を除去
    $cleanText = removeMentionFromEvent($inputText, $event);
    
    if (empty(trim($cleanText))) {
        sendLineMessage($replyToken, ERROR_EMPTY_MESSAGE);
        return;
    }
    
    // 翻訳処理（タイムアウト対策）
    if (DEBUG_MODE) {
        error_log("Starting translation for: " . $cleanText);
    }
    
    try {
        // 最大実行時間を設定（20秒）
        set_time_limit(20);
        
        $translationResult = processTranslation($cleanText);
        
        if (DEBUG_MODE) {
            error_log("Translation complete: " . json_encode($translationResult));
        }
        
        if ($translationResult['success']) {
            $responseMessage = $translationResult['translated_text'];
        } else {
            $responseMessage = $translationResult['error'];
        }
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log("Translation exception: " . $e->getMessage());
        }
        $responseMessage = ERROR_TRANSLATION_FAILED;
    }
    
    // エラーが発生しても必ずLINEに返信
    if (empty($responseMessage)) {
        $responseMessage = ERROR_TRANSLATION_FAILED;
    }
    
    // LINE返信
    if (DEBUG_MODE) {
        error_log("Sending LINE message: " . $responseMessage);
    }
    
    $lineResult = sendLineMessage($replyToken, $responseMessage);
    
    if (DEBUG_MODE) {
        error_log("LINE send result: " . ($lineResult ? "success" : "failed"));
    }
}

// メイン処理
header('Content-Type: application/json');

// リクエストボディを取得
$httpRequestBody = file_get_contents('php://input');
$requestData = json_decode($httpRequestBody, true);

if (DEBUG_MODE) {
    error_log("Webhook received: " . $httpRequestBody);
}

// 署名検証
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
if (!validateLineSignature(LINE_CHANNEL_SECRET, $httpRequestBody, $signature)) {
    if (DEBUG_MODE) {
        error_log("Invalid signature - bypassing for debug");
        // デバッグモードでは署名エラーをログに記録するだけで続行
    } else {
        http_response_code(400);
        exit('Invalid signature');
    }
}

// イベント処理
if (isset($requestData['events'])) {
    foreach ($requestData['events'] as $event) {
        handleWebhookEvent($event);
    }
}

// 正常終了
http_response_code(200);
echo json_encode(['status' => 'ok']);
