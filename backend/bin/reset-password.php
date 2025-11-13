#!/usr/bin/env php
<?php
/**
 * Reset User Password Script
 * 
 * This CLI script allows you to reset a user's password
 * Useful for recovering admin access or rotating credentials
 * 
 * Usage:
 *   php reset-password.php <login> <new_password>
 *   php reset-password.php admin newSecurePassword123
 * 
 * Interactive mode (password hidden):
 *   php reset-password.php <login>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;

function readPassword(string $prompt = "Enter new password: "): string
{
    echo $prompt;
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $password = stream_get_line(STDIN, 1024, PHP_EOL);
    } else {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    }
    
    return $password;
}

function confirmPassword(): string
{
    $password = readPassword("Enter new password: ");
    $confirm = readPassword("Confirm new password: ");
    
    if ($password !== $confirm) {
        echo "✗ Passwords do not match\n";
        exit(1);
    }
    
    return $password;
}

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->load();
} catch (Exception $e) {
    echo "Warning: .env file not found, using default values\n";
}

// Parse command line arguments
$login = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$login) {
    echo "Usage: php reset-password.php <login> [new_password]\n";
    echo "Example: php reset-password.php admin\n";
    exit(1);
}

// Initialize database
Database::init([
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'ch167436_3dprint',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
]);

try {
    $db = Database::getConnection();
    
    // Check if user exists
    $stmt = $db->prepare('SELECT id, login, name, email, role FROM users WHERE login = ? OR email = ?');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "✗ User not found: {$login}\n";
        exit(1);
    }
    
    echo "Found user:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Login: {$user['login']}\n";
    echo "  Name: {$user['name']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Role: {$user['role']}\n\n";
    
    // Get password (from command line or interactively)
    if (!$password) {
        $password = confirmPassword();
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        echo "✗ Password must be at least 8 characters long\n";
        exit(1);
    }
    
    // Hash and update password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $db->prepare('
        UPDATE users 
        SET password_hash = ?, updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$passwordHash, $user['id']]);
    
    echo "✓ Password updated successfully for user: {$user['login']}\n";
    echo "\nYou can now login with:\n";
    echo "  Login: {$user['login']}\n";
    echo "  Password: [the one you just set]\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
