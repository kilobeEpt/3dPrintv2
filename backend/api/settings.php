<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

$auth = new Auth();
$auth->checkAuth();

if ($method === 'GET') {
    $settings = $db->fetchOne('SELECT * FROM site_settings LIMIT 1');
    
    if (!$settings) {
        $settings = [
            'id' => 0,
            'calculator_config' => json_encode([]),
            'form_config' => json_encode([]),
            'telegram_config' => json_encode([]),
            'general_config' => json_encode([])
        ];
    }
    
    $settings['calculator_config'] = json_decode($settings['calculator_config'], true);
    $settings['form_config'] = json_decode($settings['form_config'], true);
    $settings['telegram_config'] = json_decode($settings['telegram_config'], true);
    $settings['general_config'] = json_decode($settings['general_config'], true);
    
    Response::success($settings);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $calculator_config = json_encode($input['calculator_config'] ?? []);
    $form_config = json_encode($input['form_config'] ?? []);
    $telegram_config = json_encode($input['telegram_config'] ?? []);
    $general_config = json_encode($input['general_config'] ?? []);
    
    try {
        $existing = $db->fetchOne('SELECT id FROM site_settings LIMIT 1');
        
        if ($existing) {
            $db->execute('
                UPDATE site_settings 
                SET calculator_config = ?, form_config = ?, telegram_config = ?, general_config = ?
                WHERE id = ?
            ', [$calculator_config, $form_config, $telegram_config, $general_config, $existing['id']]);
        } else {
            $db->execute('
                INSERT INTO site_settings (calculator_config, form_config, telegram_config, general_config)
                VALUES (?, ?, ?, ?)
            ', [$calculator_config, $form_config, $telegram_config, $general_config]);
        }
        
        Response::success(null, 'Settings updated successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
