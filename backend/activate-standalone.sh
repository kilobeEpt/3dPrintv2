#!/bin/bash

# ============================================
# ACTIVATE STANDALONE MODE
# ============================================
# This script switches the backend to standalone mode
# (no Composer dependencies required)

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PUBLIC_DIR="$SCRIPT_DIR/public"

echo "============================================"
echo "  ACTIVATING STANDALONE MODE"
echo "============================================"
echo ""

# Check if we're in the right directory
if [ ! -f "$PUBLIC_DIR/index-standalone.php" ]; then
    echo "❌ Error: index-standalone.php not found"
    echo "   Make sure you're in the backend directory"
    exit 1
fi

# Backup Composer version (if exists)
if [ -f "$PUBLIC_DIR/index.php" ] && [ ! -f "$PUBLIC_DIR/index-composer-backup.php" ]; then
    echo "📦 Backing up Composer version..."
    mv "$PUBLIC_DIR/index.php" "$PUBLIC_DIR/index-composer-backup.php"
    echo "   ✓ Saved as index-composer-backup.php"
fi

if [ -f "$PUBLIC_DIR/.htaccess" ] && [ ! -f "$PUBLIC_DIR/.htaccess-composer-backup" ]; then
    echo "📦 Backing up .htaccess..."
    mv "$PUBLIC_DIR/.htaccess" "$PUBLIC_DIR/.htaccess-composer-backup"
    echo "   ✓ Saved as .htaccess-composer-backup"
fi

# Activate standalone version
echo ""
echo "🔄 Activating standalone mode..."

if [ ! -f "$PUBLIC_DIR/.htaccess" ]; then
    cp "$PUBLIC_DIR/.htaccess-standalone" "$PUBLIC_DIR/.htaccess"
    echo "   ✓ .htaccess activated"
else
    echo "   ⚠ .htaccess already exists (skipped)"
fi

if [ ! -f "$PUBLIC_DIR/index.php" ]; then
    cp "$PUBLIC_DIR/index-standalone.php" "$PUBLIC_DIR/index.php"
    echo "   ✓ index.php activated"
else
    echo "   ⚠ index.php already exists (skipped)"
fi

echo ""
echo "============================================"
echo "  ✓ STANDALONE MODE ACTIVATED"
echo "============================================"
echo ""
echo "Next steps:"
echo "1. Configure .env file"
echo "2. Set up database"
echo "3. Create admin user: php database/seeds/seed-admin-user.php"
echo "4. Test API: php ultimate-final-check.php [url]"
echo ""
echo "To revert to Composer version:"
echo "  mv public/index-composer-backup.php public/index.php"
echo "  mv public/.htaccess-composer-backup public/.htaccess"
echo ""
