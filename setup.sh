#!/bin/bash

# Simple setup script for the ebook web application
# Usage: ./setup.sh /path/to/install

set -e

TARGET_DIR="$1"
if [ -z "$TARGET_DIR" ]; then
  echo "Usage: $0 <target_directory>" >&2
  exit 1
fi

# Install required packages
if [ "$EUID" -ne 0 ]; then
  SUDO=sudo
else
  SUDO=""
fi

$SUDO apt-get update
$SUDO apt-get install -y php-cli pandoc texlive texlive-xetex make fonts-dejavu

# Copy application files
mkdir -p "$TARGET_DIR"
rsync -a --exclude='.git' ./ "$TARGET_DIR/"

# Create runtime directories with writable permissions
mkdir -p "$TARGET_DIR/uploads" "$TARGET_DIR/output"
chmod 777 "$TARGET_DIR/uploads" "$TARGET_DIR/output"

# Set default permissions
find "$TARGET_DIR" -type d -exec chmod 755 {} \;
find "$TARGET_DIR" -type f -exec chmod 644 {} \;

# Reapply writable permissions
chmod 777 "$TARGET_DIR/uploads" "$TARGET_DIR/output"

echo "Installation complete in $TARGET_DIR"
