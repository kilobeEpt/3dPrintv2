<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $content = $db->fetchAll('SELECT section_key, title, content FROM site_content');
    
    $result = [];
    foreach ($content as $item) {
        $result[$item['section_key']] = [
            'title' => $item['title'],
            'content' => json_decode($item['content'], true)
        ];
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
        foreach ($input as $key => $data) {
            $title = $data['title'] ?? '';
            $content = is_array($data['content']) ? json_encode($data['content'], JSON_UNESCAPED_UNICODE) : $data['content'];
            
            $existing = $db->fetchOne('SELECT id FROM site_content WHERE section_key = ?', [$key]);
            
            if ($existing) {
                $db->execute('UPDATE site_content SET title = ?, content = ? WHERE section_key = ?', [$title, $content, $key]);
            } else {
                $db->execute('INSERT INTO site_content (section_key, title, content) VALUES (?, ?, ?)', [$key, $title, $content]);
            }
        }
        
        Response::success(null, 'Content updated successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
