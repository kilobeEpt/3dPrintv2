#!/usr/bin/env php
<?php

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

require_once __DIR__ . '/helpers/Database.php';

$login = $argv[1] ?? $_ENV['ADMIN_LOGIN'] ?? 'admin';
$password = $argv[2] ?? $_ENV['ADMIN_PASSWORD'] ?? 'admin123';
$name = $argv[3] ?? $_ENV['ADMIN_NAME'] ?? 'Administrator';
$email = $argv[4] ?? $_ENV['ADMIN_EMAIL'] ?? 'admin@3dprint-omsk.ru';

echo "Creating admin user...\n";
echo "Login: {$login}\n";
echo "Password: {$password}\n";
echo "Name: {$name}\n";
echo "Email: {$email}\n\n";

try {
    $db = Database::getInstance();
    
    $existing = $db->fetchOne('SELECT id FROM users WHERE login = ?', [$login]);
    
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    if ($existing) {
        echo "User already exists. Updating...\n";
        $db->execute(
            'UPDATE users SET password_hash = ?, name = ?, email = ?, role = "admin", active = 1 WHERE login = ?',
            [$passwordHash, $name, $email, $login]
        );
        echo "✅ Admin user updated successfully!\n";
    } else {
        echo "Creating new user...\n";
        $db->execute(
            'INSERT INTO users (login, password_hash, name, email, role, active) VALUES (?, ?, ?, ?, "admin", 1)',
            [$login, $passwordHash, $name, $email]
        );
        echo "✅ Admin user created successfully!\n";
    }
    
    echo "\nYou can now login with:\n";
    echo "Login: {$login}\n";
    echo "Password: {$password}\n";
    echo "\n⚠️  IMPORTANT: Change the password after first login!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
