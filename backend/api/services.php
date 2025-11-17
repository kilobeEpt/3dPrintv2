<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $services = $db->fetchAll('
        SELECT s.*, 
               GROUP_CONCAT(sf.feature ORDER BY sf.display_order SEPARATOR "|||") as features
        FROM services s
        LEFT JOIN service_features sf ON s.id = sf.service_id AND sf.active = 1
        WHERE s.active = 1
        GROUP BY s.id
        ORDER BY s.display_order ASC, s.id ASC
    ');
    
    foreach ($services as &$service) {
        if (!empty($service['features'])) {
            $service['features'] = explode('|||', $service['features']);
        } else {
            $service['features'] = [];
        }
    }
    
    Response::success($services);
}

$auth = new Auth();
$auth->checkAuth();

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $slug = $input['slug'] ?? '';
    $icon = $input['icon'] ?? '';
    $description = $input['description'] ?? '';
    $price = $input['price'] ?? '';
    $features = $input['features'] ?? [];
    
    if (empty($name) || empty($slug)) {
        Response::badRequest('Name and slug are required');
    }
    
    try {
        $db->execute('
            INSERT INTO services (name, slug, icon, description, price, active, featured, display_order)
            VALUES (?, ?, ?, ?, ?, 1, 0, 0)
        ', [$name, $slug, $icon, $description, $price]);
        
        $serviceId = $db->lastInsertId();
        
        foreach ($features as $index => $feature) {
            if (!empty($feature)) {
                $db->execute('
                    INSERT INTO service_features (service_id, feature, display_order, active)
                    VALUES (?, ?, ?, 1)
                ', [$serviceId, $feature, $index]);
            }
        }
        
        Response::success(['id' => $serviceId], 'Service created successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? 0;
    $name = $input['name'] ?? '';
    $slug = $input['slug'] ?? '';
    $icon = $input['icon'] ?? '';
    $description = $input['description'] ?? '';
    $price = $input['price'] ?? '';
    $features = $input['features'] ?? [];
    
    if (empty($id) || empty($name) || empty($slug)) {
        Response::badRequest('ID, name and slug are required');
    }
    
    try {
        $db->execute('
            UPDATE services 
            SET name = ?, slug = ?, icon = ?, description = ?, price = ?
            WHERE id = ?
        ', [$name, $slug, $icon, $description, $price, $id]);
        
        $db->execute('DELETE FROM service_features WHERE service_id = ?', [$id]);
        
        foreach ($features as $index => $feature) {
            if (!empty($feature)) {
                $db->execute('
                    INSERT INTO service_features (service_id, feature, display_order, active)
                    VALUES (?, ?, ?, 1)
                ', [$id, $feature, $index]);
            }
        }
        
        Response::success(null, 'Service updated successfully');
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
        $db->execute('UPDATE services SET active = 0 WHERE id = ?', [$id]);
        Response::success(null, 'Service deleted successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
