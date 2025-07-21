<?php
require_once 'config.php';
require_once 'translate.php';

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
    
    // グループ・ルームチャットの場合はメンション情報をチェック
    if (isset($event['message']['mention']['mentionees'])) {
        foreach ($event['message']['mention']['mentionees'] as $mentionee) {
            if (isset($mentionee['isSelf']) && $mentionee['isSelf'] === true) {
                return true; // Botがメンションされている
            }
        }
    }
    
    // メンション記号での簡易判定（@で始まる場合）
    if (preg_match('/^@\S+\s+/', $text)) {
        return true;
    }
    
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
        sendLineMessage($replyToken, '翻訳したいテキストを入力してください。');
        return;
    }
    
    // 翻訳処理
    $translationResult = processTranslation($cleanText);
    
    if ($translationResult['success']) {
        $responseMessage = $translationResult['translated_text'];
    } else {
        $responseMessage = $translationResult['error'];
    }
    
    // LINE返信
    sendLineMessage($replyToken, $responseMessage);
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
        error_log("Invalid signature");
    }
    http_response_code(400);
    exit('Invalid signature');
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
