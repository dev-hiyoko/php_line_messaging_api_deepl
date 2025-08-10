# 翻訳LINE Bot

日本語と繁体中文（台湾）の双方向翻訳を行うLINE Botです。DeepL APIまたはClaude APIを使用して高精度な翻訳を提供します。

## 機能

- 日本語 → 繁体中文（台湾）翻訳
- 繁体中文（台湾） → 日本語翻訳
- 英語 → 日本語翻訳（オプション）
- LINE Messaging APIによるチャット形式での翻訳

## 必要な環境

- PHP 7.4以上
- Webサーバー（Apache/Nginx等）
- SSL証明書（HTTPS必須）

## 必要なAPIキー

1. **LINE Messaging API**
   - Channel Secret
   - Channel Access Token
   - Webhook URL設定

2. **翻訳API（以下のいずれか）**
   - **DeepL API**: API Key（無料版または有料版）
   - **Claude API**: API Key（Anthropic APIキー）

## セットアップ

1. リポジトリをクローン
```bash
git clone [repository-url]
cd transrate_messaging_api
```

2. 設定ファイルを作成
```bash
cp config.sample.php config.php
```

3. `config.php`にAPIキーを設定
```php
// LINE設定
const LINE_CHANNEL_SECRET = 'your_line_channel_secret';
const LINE_CHANNEL_ACCESS_TOKEN = 'your_line_channel_access_token';

// DeepL設定（DeepLを使用する場合）
const DEEPL_API_KEY = 'your_deepl_api_key';

// Claude設定（Claudeを使用する場合）
const CLAUDE_API_KEY = 'your_claude_api_key';
const CLAUDE_MODEL = 'claude-3-haiku-20240307'; // または他のモデル

// 翻訳方法を選択（'deepl' または 'claude'）
const TRANSLATION_METHOD = 'deepl';
```

4. WebhookURLをLINE Developersコンソールに設定
```
https://yourdomain.com/webhook.php
```

## ファイル構成

```
transrate_messaging_api/
├── webhook.php          # LINE Webhook受信処理
├── translate.php        # DeepL/Claude API翻訳処理
├── config.php          # 設定ファイル
├── config.sample.php   # 設定ファイルサンプル
├── test_claude.php     # Claude API テストスクリプト
└── README.md
```

## 使用方法

1. LINE BotをLINEアプリで友だち追加
2. メッセージを送信
   - 日本語を送信 → 繁体中文に翻訳
   - 繁体中文を送信 → 日本語に翻訳
3. 翻訳結果が返信される
