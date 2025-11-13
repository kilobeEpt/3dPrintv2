<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class FaqRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM faq';
        
        if ($activeOnly) {
            $sql .= ' WHERE active = TRUE';
        }
        
        $sql .= ' ORDER BY display_order ASC, id ASC';
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM faq WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO faq (question, answer, active, display_order)
            VALUES (:question, :answer, :active, :display_order)
        ');
        
        $stmt->execute([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'active' => $data['active'] ?? true,
            'display_order' => $data['display_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['question', 'answer', 'active', 'display_order'])) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE faq SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM faq WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
