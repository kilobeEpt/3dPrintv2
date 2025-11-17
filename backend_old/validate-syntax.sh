#!/bin/bash
# Quick PHP syntax validation script

echo "Validating PHP syntax for authentication system..."
echo ""

FILES=(
    "src/Services/AuthService.php"
    "src/Controllers/AuthController.php"
    "src/Middleware/AuthMiddleware.php"
    "src/Bootstrap/App.php"
    "database/seeds/seed-admin-user.php"
    "bin/reset-password.php"
)

ERRORS=0

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -n "Checking $file... "
        if php -l "$file" > /dev/null 2>&1; then
            echo "✓ OK"
        else
            echo "✗ SYNTAX ERROR"
            php -l "$file"
            ERRORS=$((ERRORS + 1))
        fi
    else
        echo "✗ File not found: $file"
        ERRORS=$((ERRORS + 1))
    fi
done

echo ""
if [ $ERRORS -eq 0 ]; then
    echo "✓ All files have valid PHP syntax!"
    exit 0
else
    echo "✗ Found $ERRORS error(s)"
    exit 1
fi
