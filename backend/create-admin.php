#!/usr/bin/env php
<?php
/**
 * Create Admin User Script - STANDALONE MODE
 * 
 * Creates or updates an admin user with proper password hashing
 * NO COMPOSER REQUIRED - uses standalone components
 * 
 * Usage: 
 *   php create-admin.php
 *   php create-admin.php <login> <password>
 *   php create-admin.php admin mypassword "Admin Name" admin@example.com
 */

declare(strict_types=1);

// Load standalone components
require_once __DIR__ . '/standalone/autoload.php';
require_once __DIR__ . '/standalone/SimpleEnv.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    SimpleEnv::load($envFile);
}

// Initialize database
use App\Config\Database;

$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'ch167436_3dprint',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
];

Database::init($dbConfig);

// Get credentials from command line or .env
$adminLogin = $argv[1] ?? $_ENV['ADMIN_LOGIN'] ?? 'admin';
$adminPassword = $argv[2] ?? $_ENV['ADMIN_PASSWORD'] ?? 'admin123';
$adminName = $argv[3] ?? $_ENV['ADMIN_NAME'] ?? 'Administrator';
$adminEmail = $argv[4] ?? $_ENV['ADMIN_EMAIL'] ?? 'admin@3dprintpro.ru';

// Validate inputs
if (empty($adminLogin) || empty($adminPassword)) {
    echo "ERROR: Login and password are required\n";
    echo "\nUsage:\n";
    echo "  php create-admin.php [login] [password] [name] [email]\n";
    echo "\nExamples:\n";
    echo "  php create-admin.php\n";
    echo "  php create-admin.php admin mypassword\n";
    echo "  php create-admin.php admin mypassword \"Admin Name\" admin@example.com\n";
    exit(1);
}

echo "\n";
echo "==============================================\n";
echo "   3D Print Pro - Create Admin User\n";
echo "==============================================\n";
echo "\n";

try {
    $db = Database::getConnection();
    
    // Hash the password
    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);
    
    // Check if admin user already exists
    $stmt = $db->prepare('SELECT id, login, email FROM users WHERE login = ? OR email = ?');
    $stmt->execute([$adminLogin, $adminEmail]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing admin user
        echo "ℹ️  User already exists, updating...\n\n";
        
        $stmt = $db->prepare('
            UPDATE users 
            SET password_hash = ?, name = ?, email = ?, role = ?, active = TRUE, updated_at = NOW()
            WHERE login = ?
        ');
        $stmt->execute([$passwordHash, $adminName, $adminEmail, 'admin', $adminLogin]);
        
        echo "✅ Admin user UPDATED successfully!\n";
        echo "\n";
        echo "  ID:    {$existingUser['id']}\n";
        echo "  Login: {$adminLogin}\n";
        echo "  Name:  {$adminName}\n";
        echo "  Email: {$adminEmail}\n";
        echo "  Role:  admin\n";
        echo "\n";
        echo "⚠️  Password has been changed.\n";
    } else {
        // Insert new admin user
        echo "📝 Creating new admin user...\n\n";
        
        $stmt = $db->prepare('
            INSERT INTO users (login, password_hash, name, email, role, active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, TRUE, NOW(), NOW())
        ');
        $stmt->execute([$adminLogin, $passwordHash, $adminName, $adminEmail, 'admin']);
        
        $userId = $db->lastInsertId();
        
        echo "✅ Admin user CREATED successfully!\n";
        echo "\n";
        echo "  ID:    {$userId}\n";
        echo "  Login: {$adminLogin}\n";
        echo "  Name:  {$adminName}\n";
        echo "  Email: {$adminEmail}\n";
        echo "  Role:  admin\n";
    }
    
    echo "\n";
    echo "==============================================\n";
    echo "   You can now login to the admin panel!\n";
    echo "==============================================\n";
    echo "\n";
    echo "📌 IMPORTANT SECURITY NOTES:\n";
    echo "  • Change the default password immediately after first login\n";
    echo "  • Generate a strong JWT_SECRET in .env file\n";
    echo "  • Set APP_DEBUG=false in production\n";
    echo "  • Configure CORS_ORIGIN with your actual domain\n";
    echo "\n";
    
    // Test the password verification
    echo "🔍 Testing password verification...\n";
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE login = ?');
    $stmt->execute([$adminLogin]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($adminPassword, $user['password_hash'])) {
        echo "✅ Password verification test PASSED\n";
    } else {
        echo "❌ Password verification test FAILED\n";
        echo "   This should not happen. Please check your database.\n";
    }
    
    echo "\n";
    exit(0);
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Please check:\n";
    echo "  • Database credentials in .env file\n";
    echo "  • Database server is running\n";
    echo "  • Database '{$dbConfig['database']}' exists\n";
    echo "  • Users table exists (run migrations first)\n";
    echo "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
