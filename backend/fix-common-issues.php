<?php
/**
 * FIX COMMON DEPLOYMENT ISSUES
 * Automated fixes for typical problems
 * 
 * Usage: php fix-common-issues.php [--auto]
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$RESET = "\033[0m";

$autoFix = in_array('--auto', $argv);

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "   FIX COMMON DEPLOYMENT ISSUES\n";
echo "═══════════════════════════════════════════════════\n\n";

$fixes = [];
$errors = [];

// ============================================
// 1. CHECK VENDOR/ ISSUE
// ============================================

echo "{$BLUE}[1] Checking for vendor/ dependencies...{$RESET}\n";

if (!is_dir(__DIR__ . '/vendor')) {
    echo "   {$YELLOW}⚠ vendor/ not found - Composer not installed{$RESET}\n";
    echo "   {$GREEN}✓ Solution: Use standalone mode{$RESET}\n";
    
    if ($autoFix || askConfirmation('   Activate standalone mode now?')) {
        activateStandaloneMode();
        $fixes[] = 'Activated standalone mode';
    }
} else {
    echo "   {$GREEN}✓ vendor/ exists{$RESET}\n";
}

// ============================================
// 2. CHECK .HTACCESS REDIRECTS
// ============================================

echo "\n{$BLUE}[2] Checking .htaccess for redirect issues...{$RESET}\n";

$htaccessPath = __DIR__ . '/public/.htaccess';

if (!file_exists($htaccessPath)) {
    echo "   {$RED}✗ .htaccess not found{$RESET}\n";
    
    if ($autoFix || askConfirmation('   Create .htaccess from standalone template?')) {
        if (copy(__DIR__ . '/public/.htaccess-standalone', $htaccessPath)) {
            echo "   {$GREEN}✓ Created .htaccess{$RESET}\n";
            $fixes[] = 'Created .htaccess';
        }
    }
} else {
    $htaccessContent = file_get_contents($htaccessPath);
    
    // Check for redirect flags
    if (preg_match('/\[.*R=30[12].*\]/', $htaccessContent)) {
        echo "   {$RED}✗ Found redirect flags (R=301/R=302){$RESET}\n";
        echo "   {$YELLOW}This causes API to return redirects instead of JSON{$RESET}\n";
        
        if ($autoFix || askConfirmation('   Replace with fixed .htaccess?')) {
            if (copy($htaccessPath, $htaccessPath . '.backup')) {
                if (copy(__DIR__ . '/public/.htaccess-standalone', $htaccessPath)) {
                    echo "   {$GREEN}✓ Fixed .htaccess (backup saved){$RESET}\n";
                    $fixes[] = 'Fixed .htaccess redirect issue';
                }
            }
        }
    } else {
        echo "   {$GREEN}✓ No redirect flags found{$RESET}\n";
    }
    
    // Check RewriteBase
    if (preg_match('/RewriteBase\s+(.+)/', $htaccessContent, $matches)) {
        $rewriteBase = trim($matches[1]);
        echo "   {$BLUE}ℹ RewriteBase: $rewriteBase{$RESET}\n";
        echo "   {$YELLOW}Make sure this matches your actual path{$RESET}\n";
    }
}

// ============================================
// 3. CHECK .ENV CONFIGURATION
// ============================================

echo "\n{$BLUE}[3] Checking .env configuration...{$RESET}\n";

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    echo "   {$RED}✗ .env not found{$RESET}\n";
    
    if ($autoFix || askConfirmation('   Create .env from example?')) {
        if (copy(__DIR__ . '/.env.example', $envPath)) {
            echo "   {$GREEN}✓ Created .env{$RESET}\n";
            echo "   {$YELLOW}⚠ IMPORTANT: Edit .env with your database credentials!{$RESET}\n";
            $fixes[] = 'Created .env file';
        }
    }
} else {
    echo "   {$GREEN}✓ .env exists{$RESET}\n";
    
    // Check critical values
    $envContent = file_get_contents($envPath);
    $envVars = parse_ini_string($envContent);
    
    $warnings = [];
    
    if (!isset($envVars['JWT_SECRET']) || strlen($envVars['JWT_SECRET']) < 32) {
        $warnings[] = 'JWT_SECRET is too short or missing';
    }
    
    if (isset($envVars['JWT_SECRET']) && $envVars['JWT_SECRET'] === 'change-this-secret-key') {
        $warnings[] = 'JWT_SECRET is still default value';
    }
    
    if (!isset($envVars['DB_DATABASE']) || empty($envVars['DB_DATABASE'])) {
        $warnings[] = 'DB_DATABASE is not set';
    }
    
    if (isset($envVars['APP_DEBUG']) && $envVars['APP_DEBUG'] === 'true') {
        $warnings[] = 'APP_DEBUG is enabled (should be false in production)';
    }
    
    if (!empty($warnings)) {
        echo "   {$YELLOW}⚠ Configuration warnings:{$RESET}\n";
        foreach ($warnings as $warning) {
            echo "     • $warning\n";
        }
    }
}

// ============================================
// 4. CHECK DATABASE CONNECTION
// ============================================

echo "\n{$BLUE}[4] Checking database connection...{$RESET}\n";

if (file_exists($envPath)) {
    // Load env
    $envContent = file_get_contents($envPath);
    $envVars = parse_ini_string($envContent);
    
    $dbHost = $envVars['DB_HOST'] ?? 'localhost';
    $dbPort = $envVars['DB_PORT'] ?? '3306';
    $dbName = $envVars['DB_DATABASE'] ?? '';
    $dbUser = $envVars['DB_USERNAME'] ?? '';
    $dbPass = $envVars['DB_PASSWORD'] ?? '';
    
    if (empty($dbName)) {
        echo "   {$YELLOW}⚠ Database not configured in .env{$RESET}\n";
    } else {
        try {
            $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            echo "   {$GREEN}✓ Database connection successful{$RESET}\n";
            
            // Check tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredTables = ['users', 'services', 'orders', 'settings'];
            $missingTables = array_diff($requiredTables, $tables);
            
            if (!empty($missingTables)) {
                echo "   {$YELLOW}⚠ Missing tables: " . implode(', ', $missingTables) . "{$RESET}\n";
                echo "   {$YELLOW}Run: mysql -u $dbUser -p $dbName < database/migrations/20231113_initial.sql{$RESET}\n";
            } else {
                echo "   {$GREEN}✓ All required tables exist{$RESET}\n";
            }
            
            // Check admin user
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                echo "   {$YELLOW}⚠ No admin users found{$RESET}\n";
                echo "   {$YELLOW}Run: php database/seeds/seed-admin-user.php{$RESET}\n";
            }
            
        } catch (PDOException $e) {
            echo "   {$RED}✗ Database connection failed: " . $e->getMessage() . "{$RESET}\n";
            $errors[] = 'Database connection failed';
        }
    }
}

// ============================================
// 5. CHECK FILE PERMISSIONS
// ============================================

echo "\n{$BLUE}[5] Checking file permissions...{$RESET}\n";

$storagePath = __DIR__ . '/storage';

if (!is_dir($storagePath)) {
    echo "   {$YELLOW}⚠ storage/ directory not found{$RESET}\n";
    
    if ($autoFix || askConfirmation('   Create storage directories?')) {
        @mkdir($storagePath, 0775, true);
        @mkdir($storagePath . '/logs', 0775, true);
        @mkdir($storagePath . '/cache', 0775, true);
        echo "   {$GREEN}✓ Created storage directories{$RESET}\n";
        $fixes[] = 'Created storage directories';
    }
} else {
    if (!is_writable($storagePath)) {
        echo "   {$YELLOW}⚠ storage/ is not writable{$RESET}\n";
        
        if ($autoFix || askConfirmation('   Fix permissions (chmod 775)?')) {
            @chmod($storagePath, 0775);
            @chmod($storagePath . '/logs', 0775);
            @chmod($storagePath . '/cache', 0775);
            echo "   {$GREEN}✓ Fixed permissions{$RESET}\n";
            $fixes[] = 'Fixed storage permissions';
        }
    } else {
        echo "   {$GREEN}✓ storage/ is writable{$RESET}\n";
    }
}

// ============================================
// 6. CHECK PUBLIC INDEX
// ============================================

echo "\n{$BLUE}[6] Checking public/index.php...{$RESET}\n";

$indexPath = __DIR__ . '/public/index.php';

if (!file_exists($indexPath)) {
    echo "   {$RED}✗ public/index.php not found{$RESET}\n";
    
    if (file_exists(__DIR__ . '/public/index-standalone.php')) {
        if ($autoFix || askConfirmation('   Use standalone index.php?')) {
            copy(__DIR__ . '/public/index-standalone.php', $indexPath);
            echo "   {$GREEN}✓ Created index.php from standalone{$RESET}\n";
            $fixes[] = 'Created index.php';
        }
    }
} else {
    $indexContent = file_get_contents($indexPath);
    
    if (strpos($indexContent, 'vendor/autoload.php') !== false && !is_dir(__DIR__ . '/vendor')) {
        echo "   {$YELLOW}⚠ index.php requires Composer but vendor/ not found{$RESET}\n";
        
        if ($autoFix || askConfirmation('   Switch to standalone version?')) {
            activateStandaloneMode();
            $fixes[] = 'Switched to standalone index.php';
        }
    } else {
        echo "   {$GREEN}✓ index.php is compatible{$RESET}\n";
    }
}

// ============================================
// SUMMARY
// ============================================

echo "\n═══════════════════════════════════════════════════\n";
echo "   SUMMARY\n";
echo "═══════════════════════════════════════════════════\n";

if (!empty($fixes)) {
    echo "{$GREEN}Applied Fixes ({" . count($fixes) . "}){$RESET}\n";
    foreach ($fixes as $fix) {
        echo "  ✓ $fix\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "{$RED}Errors Found (" . count($errors) . "){$RESET}\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "{$GREEN}✓ All checks passed!{$RESET}\n\n";
    echo "Next steps:\n";
    echo "1. Test API: php ultimate-final-check.php [url]\n";
    echo "2. Open admin panel and login\n";
    echo "3. Check that all features work\n";
} else {
    echo "{$YELLOW}⚠ Some issues need manual attention{$RESET}\n\n";
    echo "See ULTIMATE_DEPLOYMENT_GUIDE.md for detailed instructions\n";
}

echo "\n";

// ============================================
// HELPER FUNCTIONS
// ============================================

function askConfirmation($question) {
    echo "$question (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return trim(strtolower($line)) === 'y';
}

function activateStandaloneMode() {
    global $GREEN, $RESET, $fixes;
    
    $publicDir = __DIR__ . '/public';
    
    // Backup Composer version
    if (file_exists("$publicDir/index.php") && !file_exists("$publicDir/index-composer-backup.php")) {
        @rename("$publicDir/index.php", "$publicDir/index-composer-backup.php");
    }
    
    if (file_exists("$publicDir/.htaccess") && !file_exists("$publicDir/.htaccess-composer-backup")) {
        @rename("$publicDir/.htaccess", "$publicDir/.htaccess-composer-backup");
    }
    
    // Activate standalone
    @copy("$publicDir/index-standalone.php", "$publicDir/index.php");
    @copy("$publicDir/.htaccess-standalone", "$publicDir/.htaccess");
    
    echo "   {$GREEN}✓ Standalone mode activated{$RESET}\n";
}
