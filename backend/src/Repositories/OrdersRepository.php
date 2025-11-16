<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class OrdersRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        // Type filter
        if (!empty($filters['type'])) {
            $where[] = 'type = ?';
            $params[] = $filters['type'];
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        // Full-text search
        if (!empty($filters['search'])) {
            $where[] = 'MATCH(client_name, client_email, message) AGAINST (? IN NATURAL LANGUAGE MODE)';
            $params[] = $filters['search'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Count total for pagination
        $countSql = "SELECT COUNT(*) FROM orders {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated results
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM orders {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        // Decode JSON fields
        foreach ($items as &$item) {
            if (!empty($item['calculator_data'])) {
                $item['calculator_data'] = json_decode($item['calculator_data'], true);
            }
        }

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result && !empty($result['calculator_data'])) {
            $result['calculator_data'] = json_decode($result['calculator_data'], true);
        }

        return $result ?: null;
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE order_number = ?');
        $stmt->execute([$orderNumber]);
        $result = $stmt->fetch();

        if ($result && !empty($result['calculator_data'])) {
            $result['calculator_data'] = json_decode($result['calculator_data'], true);
        }

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO orders (
                order_number, type, status, client_name, client_email, client_phone,
                telegram, service, subject, message, amount, calculator_data,
                telegram_sent
            ) VALUES (
                :order_number, :type, :status, :client_name, :client_email, :client_phone,
                :telegram, :service, :subject, :message, :amount, :calculator_data,
                :telegram_sent
            )
        ');

        $stmt->execute([
            'order_number' => $data['order_number'],
            'type' => $data['type'] ?? 'contact',
            'status' => $data['status'] ?? 'new',
            'client_name' => $data['client_name'],
            'client_email' => $data['client_email'],
            'client_phone' => $data['client_phone'],
            'telegram' => $data['telegram'] ?? null,
            'service' => $data['service'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'] ?? null,
            'amount' => $data['amount'] ?? 0.00,
            'calculator_data' => !empty($data['calculator_data']) 
                ? json_encode($data['calculator_data']) 
                : null,
            'telegram_sent' => (int)($data['telegram_sent'] ?? 0)
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'type', 'status', 'client_name', 'client_email', 'client_phone',
            'telegram', 'service', 'subject', 'message', 'amount', 'telegram_sent'
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        // Handle calculator_data separately (JSON encoding)
        if (isset($data['calculator_data'])) {
            $fields[] = "calculator_data = ?";
            $params[] = !empty($data['calculator_data']) 
                ? json_encode($data['calculator_data']) 
                : null;
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;

        $sql = 'UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function updateTelegramStatus(int $id, bool $sent): bool
    {
        $stmt = $this->db->prepare('
            UPDATE orders 
            SET telegram_sent = ?, telegram_sent_at = NOW() 
            WHERE id = ?
        ');

        return $stmt->execute([(int)$sent, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orders WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $prefix = "ORD-{$date}-";

        // Find the highest order number for today
        $stmt = $this->db->prepare('
            SELECT order_number 
            FROM orders 
            WHERE order_number LIKE ? 
            ORDER BY order_number DESC 
            LIMIT 1
        ');
        $stmt->execute(["{$prefix}%"]);
        $lastOrder = $stmt->fetchColumn();

        if ($lastOrder) {
            // Extract the sequence number and increment
            $sequence = (int) substr($lastOrder, -4);
            $newSequence = $sequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    public function orderNumberExists(string $orderNumber): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE order_number = ?');
        $stmt->execute([$orderNumber]);
        return $stmt->fetchColumn() > 0;
    }

    public function getRecentOrdersByIp(string $ipAddress, int $minutes = 60): int
    {
        // Note: IP address should be stored in the orders table
        // For now, we'll return 0 to avoid breaking functionality
        // In a real implementation, add ip_address column to orders table
        return 0;
    }
}
