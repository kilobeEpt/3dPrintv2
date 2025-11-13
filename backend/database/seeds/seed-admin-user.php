#!/usr/bin/env php
<?php
/**
 * Seed Admin User Script
 * 
 * This script creates or updates the default admin user using credentials from .env
 * Run this script after database migrations to create the initial admin account
 * 
 * Usage: php seed-admin-user.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Get admin credentials from environment
$adminLogin = $_ENV['ADMIN_LOGIN'] ?? 'admin';
$adminPassword = $_ENV['ADMIN_PASSWORD'] ?? 'admin123';
$adminName = $_ENV['ADMIN_NAME'] ?? 'Administrator';
$adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@3dprintpro.ru';

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
    
    // Hash the password
    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);
    
    // Check if admin user already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE login = ? OR email = ?');
    $stmt->execute([$adminLogin, $adminEmail]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing admin user
        $stmt = $db->prepare('
            UPDATE users 
            SET password_hash = ?, name = ?, email = ?, role = ?, active = TRUE, updated_at = NOW()
            WHERE login = ?
        ');
        $stmt->execute([$passwordHash, $adminName, $adminEmail, 'admin', $adminLogin]);
        
        echo "✓ Admin user updated successfully\n";
        echo "  Login: {$adminLogin}\n";
        echo "  Email: {$adminEmail}\n";
        echo "  Password: [hidden]\n";
    } else {
        // Insert new admin user
        $stmt = $db->prepare('
            INSERT INTO users (login, password_hash, name, email, role, active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, TRUE, NOW(), NOW())
        ');
        $stmt->execute([$adminLogin, $passwordHash, $adminName, $adminEmail, 'admin']);
        
        echo "✓ Admin user created successfully\n";
        echo "  Login: {$adminLogin}\n";
        echo "  Email: {$adminEmail}\n";
        echo "  Password: [hidden]\n";
    }
    
    echo "\nYou can now login to the admin panel with these credentials.\n";
    echo "IMPORTANT: Change the default password immediately after first login!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
