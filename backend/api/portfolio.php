<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $portfolio = $db->fetchAll('
        SELECT *
        FROM portfolio
        WHERE active = 1
        ORDER BY display_order ASC, created_at DESC
    ');
    
    Response::success($portfolio);
}

$auth = new Auth();
$auth->checkAuth();

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = $input['title'] ?? '';
    $category = $input['category'] ?? '';
    $description = $input['description'] ?? '';
    $image_url = $input['image_url'] ?? '';
    $details = $input['details'] ?? '';
    
    if (empty($title) || empty($category)) {
        Response::badRequest('Title and category are required');
    }
    
    try {
        $db->execute('
            INSERT INTO portfolio (title, category, description, image_url, details, active, display_order)
            VALUES (?, ?, ?, ?, ?, 1, 0)
        ', [$title, $category, $description, $image_url, $details]);
        
        $id = $db->lastInsertId();
        
        Response::success(['id' => $id], 'Portfolio item created successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? 0;
    $title = $input['title'] ?? '';
    $category = $input['category'] ?? '';
    $description = $input['description'] ?? '';
    $image_url = $input['image_url'] ?? '';
    $details = $input['details'] ?? '';
    
    if (empty($id) || empty($title) || empty($category)) {
        Response::badRequest('ID, title and category are required');
    }
    
    try {
        $db->execute('
            UPDATE portfolio 
            SET title = ?, category = ?, description = ?, image_url = ?, details = ?
            WHERE id = ?
        ', [$title, $category, $description, $image_url, $details, $id]);
        
        Response::success(null, 'Portfolio item updated successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? 0;
    
    if (empty($id)) {
        Response::badRequest('ID is required');
    }
    
    try {
        $db->execute('UPDATE portfolio SET active = 0 WHERE id = ?', [$id]);
        Response::success(null, 'Portfolio item deleted successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
