<?php
// LINE Messaging API設定
const LINE_CHANNEL_SECRET = 'your_line_channel_secret_here';
const LINE_CHANNEL_ACCESS_TOKEN = 'your_line_channel_access_token_here';
const LINE_API_URL = 'https://api.line.me/v2/bot/message/reply';

// DeepL API設定
const DEEPL_API_KEY = 'your_deepl_api_key_here';
const DEEPL_API_URL = 'https://api-free.deepl.com/v2/translate'; // 無料版の場合

// エラーメッセージ
const ERROR_TRANSLATION_FAILED = '翻訳に失敗しました。しばらく時間をおいて再度お試しください。';
const ERROR_CHARACTER_LIMIT = 'DeepL APIの月間文字数制限に達しました。';
const ERROR_EMPTY_MESSAGE = 'メッセージが空です。';

// デバッグモード（本番環境では false に設定）
const DEBUG_MODE = true;
