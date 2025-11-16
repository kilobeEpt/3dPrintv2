<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $content = $db->fetchAll('SELECT * FROM site_content WHERE active = 1');
    
    $result = [];
    foreach ($content as $item) {
        $result[$item['key']] = $item['value'];
    }
    
    Response::success($result);
}

$auth = new Auth();
$auth->checkAuth();

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input) || !is_array($input)) {
        Response::badRequest('Content data is required');
    }
    
    try {
        foreach ($input as $key => $value) {
            $existing = $db->fetchOne('SELECT id FROM site_content WHERE `key` = ?', [$key]);
            
            if ($existing) {
                $db->execute('UPDATE site_content SET value = ? WHERE `key` = ?', [$value, $key]);
            } else {
                $db->execute('INSERT INTO site_content (`key`, value, active) VALUES (?, ?, 1)', [$key, $value]);
            }
        }
        
        Response::success(null, 'Content updated successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
