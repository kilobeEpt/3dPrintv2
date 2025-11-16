<?php

try {
    $auth = new Auth();
    $payload = $auth->checkAuth();
    
    $user = $auth->getCurrentUser($payload['user_id']);
    
    Response::success($user);
} catch (Exception $e) {
    Response::unauthorized($e->getMessage());
}
