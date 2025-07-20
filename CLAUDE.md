# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A LINE Bot that provides bidirectional translation between Japanese and Traditional Chinese (Taiwan) using DeepL API and PHP.

## Core Architecture

- **webhook.php**: Main entry point for LINE Messaging API webhooks, handles message reception and response
- **translate.php**: Contains DeepL API integration and language detection logic
- **config.php**: Stores API keys and configuration (not in repository)

## Development Commands

This is a PHP project deployed on rental servers, so no build process is required.

### Testing
```bash
# Test webhook locally (if using ngrok)
curl -X POST https://your-ngrok-url.ngrok.io/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"events": [{"type": "message", "message": {"type": "text", "text": "こんにちは"}}]}'
```

### Deployment
- Upload files via FTP/SFTP to web server
- Ensure webhook.php is accessible via HTTPS
- Update LINE Developers console with webhook URL

## API Integration Points

### LINE Messaging API
- Uses Channel Access Token for sending messages
- Webhook URL must be HTTPS
- Signature verification using Channel Secret

### DeepL API
- Language detection: Japanese (ja) ↔ Traditional Chinese (zh-tw)
- Character limit monitoring for free tier (500K chars/month)
- Error handling for API failures

## Language Detection Logic

- Contains Japanese characters (hiragana/katakana/kanji): Translate to Traditional Chinese
- Otherwise: Assume Traditional Chinese, translate to Japanese

## Configuration Management

- All sensitive data in config.php (excluded from repository)
- Use config.sample.php as template
- Required constants: LINE_CHANNEL_ACCESS_TOKEN, LINE_CHANNEL_SECRET, DEEPL_API_KEY

## Error Handling

- DeepL API failures: Return error message to user
- Invalid LINE signatures: Return 400 status
- Character limit exceeded: Inform user of monthly limit

## Security Considerations

- Webhook signature verification is mandatory
- HTTPS required for all endpoints
- API keys stored in config.php (not in version control)