# 翻訳LINE Bot

日本語と繁体中文（台湾）の双方向翻訳を行うLINE Botです。DeepL APIまたはClaude APIを使用して高精度な翻訳を提供します。

## 📁 ディレクトリ構成

```
transrate_messaging_api/
├── config/                 # 設定ファイル
│   ├── config.php         # API設定（要作成）
│   ├── config.sample.php  # 設定サンプル
│   └── error_log_config.php # ログ設定
├── logs/                   # ログファイル
│   ├── error.log          # PHPエラーログ
│   └── app_error.log      # アプリケーションログ
├── tests/                  # テストスクリプト
│   ├── test_claude.php    # Claude API テスト
│   └── test_log.php       # ログ出力テスト
├── scripts/                # スクリプト
│   └── setup_logs.sh      # ログ設定スクリプト
├── webhook.php            # LINE Webhook受信処理
├── translate.php          # 翻訳処理
├── .htaccess             # Apache設定
└── README.md             # このファイル
```

## 🚀 セットアップ

### 1. 設定ファイルの作成

```bash
cp config/config.sample.php config/config.php
```

### 2. APIキーの設定

`config/config.php`を編集：

```php
// LINE設定
const LINE_CHANNEL_SECRET = 'your_line_channel_secret';
const LINE_CHANNEL_ACCESS_TOKEN = 'your_line_channel_access_token';

// 翻訳API設定（以下のいずれかを選択）
// DeepL使用時
const DEEPL_API_KEY = 'your_deepl_api_key';

// Claude使用時
const CLAUDE_API_KEY = 'your_claude_api_key';
const CLAUDE_MODEL = 'claude-3-haiku-20240307';

// 翻訳方法を選択
const TRANSLATION_METHOD = 'deepl'; // または 'claude'
```

### 3. ログディレクトリのセットアップ

```bash
cd scripts
bash setup_logs.sh
```

### 4. WebhookURLの設定

LINE Developersコンソールで以下のURLを設定：
```
https://yourdomain.com/webhook.php
```

## 🧪 テスト

### Claude APIのテスト
```bash
cd tests
php test_claude.php
```

### ログ出力のテスト
```bash
cd tests
php test_log.php
```

## 📝 使用方法

1. LINE BotをLINEアプリで友だち追加
2. メッセージを送信
   - 日本語 → 繁体中文（台湾）に翻訳
   - 繁体中文 → 日本語に翻訳
   - 英語 → 日本語に翻訳
3. 翻訳結果が返信される

## 🔧 トラブルシューティング

### ログが出力されない場合

1. ログディレクトリの権限を確認：
```bash
ls -la logs/
chmod 666 logs/*.log
```

2. PHPの設定を確認：
```bash
php tests/test_log.php
```

### Claude APIエラー

- クレジット残高を確認: https://console.anthropic.com
- APIキーが正しいか確認
- モデル名が正しいか確認（`claude-3-haiku-20240307`など）

## 📋 必要な環境

- PHP 7.4以上
- Webサーバー（Apache/Nginx）
- SSL証明書（HTTPS必須）
- LINE Messaging API アカウント
- DeepL APIまたはClaude API アカウント