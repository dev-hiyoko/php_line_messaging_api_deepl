#!/bin/bash

# Claude CLI wrapper script for MixHost environment
# Usage: ./claude_wrapper.sh "prompt text"

if [ $# -eq 0 ]; then
    echo "Usage: $0 \"prompt text\""
    exit 1
fi

# Set Node.js memory options
export NODE_OPTIONS="--max-old-space-size=1024"

# Set PATH to include nodebrew
export PATH="/home/$(whoami)/.nodebrew/current/bin:$PATH"

# Get the prompt from the first argument
PROMPT="$1"

# Find Claude CLI path
CLAUDE_PATH=""
POSSIBLE_PATHS=(
    "/home/$(whoami)/.nodebrew/current/bin/claude"
    "/usr/local/bin/claude"
    "/usr/bin/claude"
    "claude"
)

for path in "${POSSIBLE_PATHS[@]}"; do
    if [ "$path" = "claude" ] || [ -f "$path" ]; then
        CLAUDE_PATH="$path"
        break
    fi
done

if [ -z "$CLAUDE_PATH" ]; then
    echo "Error: Claude CLI not found"
    exit 1
fi

# Execute Claude with timeout
timeout 30s "$CLAUDE_PATH" -p "$PROMPT" 2>&1