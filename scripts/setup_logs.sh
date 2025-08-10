#!/bin/bash
# さくらインターネットでログファイルを設定するスクリプト

echo "Setting up log files for Sakura Internet..."

# スクリプトの場所を基準にプロジェクトルートを取得
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOGS_DIR="$PROJECT_ROOT/logs"

echo "Project root: $PROJECT_ROOT"
echo "Logs directory: $LOGS_DIR"

# ログディレクトリを作成
mkdir -p "$LOGS_DIR"

# ログファイルを作成
touch "$LOGS_DIR/error.log"
touch "$LOGS_DIR/app_error.log"

# 権限を設定（PHPが書き込めるように）
chmod 666 "$LOGS_DIR/error.log"
chmod 666 "$LOGS_DIR/app_error.log"

echo "Log files created with proper permissions:"
ls -la "$LOGS_DIR"/*.log

echo "Setup complete!"