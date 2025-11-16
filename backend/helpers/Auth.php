<?php

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($login, $password) {
        $user = $this->db->fetchOne(
            'SELECT * FROM users WHERE login = ? AND active = 1',
            [$login]
        );
        
        if (!$user) {
            throw new Exception('Invalid credentials');
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }
        
        $this->db->execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = ?',
            [$user['id']]
        );
        
        $accessToken = $this->generateToken($user, 3600);
        $refreshToken = $this->generateToken($user, 2592000);
        
        return [
            'user' => [
                'id' => $user['id'],
                'login' => $user['login'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ];
    }
    
    public function generateToken($user, $expiresIn = 3600) {
        return JWT::encode([
            'user_id' => $user['id'],
            'login' => $user['login'],
            'role' => $user['role']
        ], $expiresIn);
    }
    
    public function verifyToken($token) {
        try {
            return JWT::decode($token);
        } catch (Exception $e) {
            throw new Exception('Invalid or expired token');
        }
    }
    
    public function checkAuth() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            Response::unauthorized('Missing authorization token');
        }
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            Response::unauthorized('Invalid authorization format');
        }
        
        $token = $matches[1];
        
        try {
            $payload = $this->verifyToken($token);
            return $payload;
        } catch (Exception $e) {
            Response::unauthorized($e->getMessage());
        }
    }
    
    public function getCurrentUser($userId) {
        $user = $this->db->fetchOne(
            'SELECT id, login, name, email, role, last_login_at, created_at FROM users WHERE id = ? AND active = 1',
            [$userId]
        );
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        return $user;
    }
}
