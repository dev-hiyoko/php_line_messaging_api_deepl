<?php
// LINE Messaging API設定
const LINE_CHANNEL_SECRET = 'your_line_channel_secret_here';
const LINE_CHANNEL_ACCESS_TOKEN = 'your_line_channel_access_token_here';
const LINE_API_URL = 'https://api.line.me/v2/bot/message/reply';

// DeepL API設定
const DEEPL_API_KEY = 'your_deepl_api_key_here';
const DEEPL_API_URL = 'https://api-free.deepl.com/v2/translate'; // 無料版の場合

// Claude API設定
const CLAUDE_API_KEY = 'your_claude_api_key_here';
const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
const CLAUDE_MODEL = 'claude-3-haiku-20240307'; // またはその他のモデル名

// 翻訳方法の設定 ('deepl' または 'claude')
const TRANSLATION_METHOD = 'claude';

// エラーメッセージ
const ERROR_TRANSLATION_FAILED = 'Translation failed. Please wait a while and try again. Now';
const ERROR_CHARACTER_LIMIT = 'DeepL API\'s monthly character limit has been reached. Now';
const ERROR_EMPTY_MESSAGE = 'Message is empty.';

// デバッグモード（本番環境では false に設定）
const DEBUG_MODE = true;
