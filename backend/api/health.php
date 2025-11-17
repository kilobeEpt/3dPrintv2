<?php

try {
    $db = Database::getInstance();
    $dbStatus = $db->testConnection();
    
    Response::success([
        'status' => 'healthy',
        'database' => $dbStatus ? 'connected' : 'disconnected',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    Response::success([
        'status' => 'healthy',
        'database' => 'error',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
