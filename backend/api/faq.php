<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $faq = $db->fetchAll('
        SELECT *
        FROM faq
        WHERE active = 1
        ORDER BY display_order ASC, id ASC
    ');
    
    Response::success($faq);
}

$auth = new Auth();
$auth->checkAuth();

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $question = $input['question'] ?? '';
    $answer = $input['answer'] ?? '';
    $category = $input['category'] ?? 'general';
    
    if (empty($question) || empty($answer)) {
        Response::badRequest('Question and answer are required');
    }
    
    try {
        $db->execute('
            INSERT INTO faq (question, answer, category, active, display_order)
            VALUES (?, ?, ?, 1, 0)
        ', [$question, $answer, $category]);
        
        $id = $db->lastInsertId();
        
        Response::success(['id' => $id], 'FAQ created successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? 0;
    $question = $input['question'] ?? '';
    $answer = $input['answer'] ?? '';
    $category = $input['category'] ?? 'general';
    
    if (empty($id) || empty($question) || empty($answer)) {
        Response::badRequest('ID, question and answer are required');
    }
    
    try {
        $db->execute('
            UPDATE faq 
            SET question = ?, answer = ?, category = ?
            WHERE id = ?
        ', [$question, $answer, $category, $id]);
        
        Response::success(null, 'FAQ updated successfully');
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
        $db->execute('UPDATE faq SET active = 0 WHERE id = ?', [$id]);
        Response::success(null, 'FAQ deleted successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
