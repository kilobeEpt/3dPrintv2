<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $message = $input['message'] ?? '';
    $calculator_data = isset($input['calculator_data']) ? json_encode($input['calculator_data']) : null;
    
    if (empty($name) || empty($phone)) {
        Response::unprocessable('Name and phone are required', ['name' => 'Name is required', 'phone' => 'Phone is required']);
    }
    
    try {
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        $db->execute('
            INSERT INTO orders (order_number, client_name, client_email, client_phone, message, calculator_data, status)
            VALUES (?, ?, ?, ?, ?, ?, "new")
        ', [$orderNumber, $name, $email, $phone, $message, $calculator_data]);
        
        $orderId = $db->lastInsertId();
        
        $telegramEnabled = !empty($_ENV['TELEGRAM_BOT_TOKEN']) && !empty($_ENV['TELEGRAM_CHAT_ID']);
        
        if ($telegramEnabled) {
            $text = "🆕 Новый заказ #{$orderNumber}\n\n";
            $text .= "👤 Имя: {$name}\n";
            $text .= "📧 Email: {$email}\n";
            $text .= "📱 Телефон: {$phone}\n";
            if (!empty($message)) {
                $text .= "💬 Сообщение: {$message}\n";
            }
            
            $url = "https://api.telegram.org/bot" . $_ENV['TELEGRAM_BOT_TOKEN'] . "/sendMessage";
            $data = [
                'chat_id' => $_ENV['TELEGRAM_CHAT_ID'],
                'text' => $text,
                'parse_mode' => 'HTML'
            ];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                ]
            ];
            
            @file_get_contents($url, false, stream_context_create($options));
        }
        
        Response::success([
            'id' => $orderId,
            'order_number' => $orderNumber
        ], 'Order created successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

$auth = new Auth();
$auth->checkAuth();

if ($method === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    $status = $_GET['status'] ?? '';
    
    $where = '1=1';
    $params = [];
    
    if (!empty($status)) {
        $where .= ' AND status = ?';
        $params[] = $status;
    }
    
    $total = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE {$where}", $params);
    
    $orders = $db->fetchAll("
        SELECT *
        FROM orders
        WHERE {$where}
        ORDER BY created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ", $params);
    
    foreach ($orders as &$order) {
        if (!empty($order['calculator_data'])) {
            $order['calculator_data'] = json_decode($order['calculator_data'], true);
        }
    }
    
    Response::success([
        'orders' => $orders,
        'pagination' => [
            'total' => $total['count'],
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total['count'] / $limit)
        ]
    ]);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? 0;
    $status = $input['status'] ?? '';
    $notes = $input['notes'] ?? '';
    
    if (empty($id)) {
        Response::badRequest('ID is required');
    }
    
    try {
        $updates = [];
        $params = [];
        
        if (!empty($status)) {
            $updates[] = 'status = ?';
            $params[] = $status;
        }
        
        if (isset($input['notes'])) {
            $updates[] = 'notes = ?';
            $params[] = $notes;
        }
        
        if (empty($updates)) {
            Response::badRequest('Nothing to update');
        }
        
        $params[] = $id;
        
        $db->execute('UPDATE orders SET ' . implode(', ', $updates) . ' WHERE id = ?', $params);
        
        Response::success(null, 'Order updated successfully');
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
        $db->execute('DELETE FROM orders WHERE id = ?', [$id]);
        Response::success(null, 'Order deleted successfully');
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}
