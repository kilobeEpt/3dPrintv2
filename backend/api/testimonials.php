<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $testimonials = $db->fetchAll('
        SELECT *
        FROM testimonials
        WHERE active = 1
        ORDER BY display_order ASC, created_at DESC
    ');
    
    Response::success($testimonials);
}

$auth = new Auth();
$auth->checkAuth();

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $author = $input['author'] ?? '';
    $position = $input['position'] ?? '';
    $content = $input['content'] ?? '';
    $rating = $input['rating'] ?? 5;
    $avatar_url = $input['avatar_url'] ?? '';
    
    if (empty($author) || empty($content)) {
        Response::badRequest('Author and content are required');
    }
    
    try {
        $db->execute('
            INSERT INTO testimonials (author, position, content, rating, avatar_url, active, featured, display_order)
            VALUES (?, ?, ?, ?, ?, 1, 0, 0)
        ', [$author, $position, $content, $rating, $avatar_url]);
        
        $id = $db->lastInsertId();
        
        Response::success(['id' => $id], 'Testimonial created successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? 0;
    $author = $input['author'] ?? '';
    $position = $input['position'] ?? '';
    $content = $input['content'] ?? '';
    $rating = $input['rating'] ?? 5;
    $avatar_url = $input['avatar_url'] ?? '';
    
    if (empty($id) || empty($author) || empty($content)) {
        Response::badRequest('ID, author and content are required');
    }
    
    try {
        $db->execute('
            UPDATE testimonials 
            SET author = ?, position = ?, content = ?, rating = ?, avatar_url = ?
            WHERE id = ?
        ', [$author, $position, $content, $rating, $avatar_url, $id]);
        
        Response::success(null, 'Testimonial updated successfully');
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
        $db->execute('UPDATE testimonials SET active = 0 WHERE id = ?', [$id]);
        Response::success(null, 'Testimonial deleted successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
