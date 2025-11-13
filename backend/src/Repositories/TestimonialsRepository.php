<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class TestimonialsRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(bool $approvedOnly = false): array
    {
        $sql = 'SELECT * FROM testimonials';
        
        if ($approvedOnly) {
            $sql .= ' WHERE approved = TRUE';
        }
        
        $sql .= ' ORDER BY display_order ASC, created_at DESC';
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM testimonials WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO testimonials (name, position, avatar_url, rating, text, approved, display_order)
            VALUES (:name, :position, :avatar_url, :rating, :text, :approved, :display_order)
        ');
        
        $stmt->execute([
            'name' => $data['name'],
            'position' => $data['position'],
            'avatar_url' => $data['avatar_url'],
            'rating' => $data['rating'] ?? 5,
            'text' => $data['text'],
            'approved' => $data['approved'] ?? false,
            'display_order' => $data['display_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'position', 'avatar_url', 'rating', 'text', 'approved', 'display_order'])) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE testimonials SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM testimonials WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
