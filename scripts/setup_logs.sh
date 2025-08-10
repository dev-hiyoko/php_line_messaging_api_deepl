#!/bin/bash
# さくらインターネットでログファイルを設定するスクリプト

echo "Setting up log files for Sakura Internet..."

# ログディレクトリを作成
mkdir -p ../logs

# ログファイルを作成
touch ../logs/error.log
touch ../logs/app_error.log

# 権限を設定（PHPが書き込めるように）
chmod 666 ../logs/error.log
chmod 666 ../logs/app_error.log

echo "Log files created with proper permissions:"
ls -la ../logs/*.log

echo "Setup complete!"