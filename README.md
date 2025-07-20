# 翻訳LINE Bot

日本語と繁体中文（台湾）の双方向翻訳を行うLINE Botです。DeepL APIを使用して高精度な翻訳を提供します。

## 機能

- 日本語 → 繁体中文（台湾）翻訳
- 繁体中文（台湾） → 日本語翻訳
- LINE Messaging APIによるチャット形式での翻訳

## 必要な環境

- PHP 7.4以上
- Webサーバー（Apache/Nginx等）
- SSL証明書（HTTPS必須）

## 必要なAPIキー

1. **LINE Messaging API**
   - Channel Secret
   - Webhook URL設定

2. **DeepL API**
   - API Key（無料版または有料版）

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
const LINE_CHANNEL_SECRET = 'your_line_channel_secret';
const DEEPL_API_KEY = 'your_deepl_api_key';
```

4. WebhookURLをLINE Developersコンソールに設定
```
https://yourdomain.com/webhook.php
```

## ファイル構成

```
transrate_messaging_api/
├── webhook.php          # LINE Webhook受信処理
├── translate.php        # DeepL API翻訳処理
├── config.php          # 設定ファイル
├── config.sample.php   # 設定ファイルサンプル
└── README.md
```

## 使用方法

1. LINE BotをLINEアプリで友だち追加
2. メッセージを送信
   - 日本語を送信 → 繁体中文に翻訳
   - 繁体中文を送信 → 日本語に翻訳
3. 翻訳結果が返信される
