<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class PortfolioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(?string $category = null): array
    {
        if ($category) {
            $stmt = $this->db->prepare('
                SELECT * FROM portfolio 
                WHERE category = ? 
                ORDER BY created_at DESC
            ');
            $stmt->execute([$category]);
        } else {
            $stmt = $this->db->query('SELECT * FROM portfolio ORDER BY created_at DESC');
        }
        
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM portfolio WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO portfolio (title, category, description, image_url, details)
            VALUES (:title, :category, :description, :image_url, :details)
        ');
        
        $stmt->execute([
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'],
            'image_url' => $data['image_url'],
            'details' => $data['details'] ?? null
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'category', 'description', 'image_url', 'details'])) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE portfolio SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM portfolio WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getCategories(): array
    {
        $stmt = $this->db->query('
            SELECT DISTINCT category 
            FROM portfolio 
            ORDER BY category
        ');
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
