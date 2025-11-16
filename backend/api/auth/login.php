<?php

$input = json_decode(file_get_contents('php://input'), true);

$login = $input['login'] ?? '';
$password = $input['password'] ?? '';

if (empty($login) || empty($password)) {
    Response::badRequest('Login and password are required');
}

try {
    $auth = new Auth();
    $result = $auth->login($login, $password);
    
    Response::success($result, 'Login successful');
} catch (Exception $e) {
    Response::unauthorized($e->getMessage());
}
