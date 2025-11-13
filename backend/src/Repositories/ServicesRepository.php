<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ServicesRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM services';
        
        if ($activeOnly) {
            $sql .= ' WHERE active = TRUE';
        }
        
        $sql .= ' ORDER BY display_order ASC, id ASC';
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE slug = ?');
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO services (name, slug, icon, description, price, active, featured, display_order)
            VALUES (:name, :slug, :icon, :description, :price, :active, :featured, :display_order)
        ');
        
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'icon' => $data['icon'],
            'description' => $data['description'],
            'price' => $data['price'],
            'active' => $data['active'] ?? true,
            'featured' => $data['featured'] ?? false,
            'display_order' => $data['display_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'slug', 'icon', 'description', 'price', 'active', 'featured', 'display_order'])) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE services SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM services WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getFeatures(int $serviceId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM service_features 
            WHERE service_id = ? 
            ORDER BY display_order ASC, id ASC
        ');
        $stmt->execute([$serviceId]);
        
        return $stmt->fetchAll();
    }

    public function createFeature(int $serviceId, string $featureText, int $displayOrder = 0): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO service_features (service_id, feature_text, display_order)
            VALUES (?, ?, ?)
        ');
        
        $stmt->execute([$serviceId, $featureText, $displayOrder]);
        
        return (int) $this->db->lastInsertId();
    }

    public function updateFeature(int $featureId, string $featureText, int $displayOrder): bool
    {
        $stmt = $this->db->prepare('
            UPDATE service_features 
            SET feature_text = ?, display_order = ?
            WHERE id = ?
        ');
        
        return $stmt->execute([$featureText, $displayOrder, $featureId]);
    }

    public function deleteFeature(int $featureId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM service_features WHERE id = ?');
        return $stmt->execute([$featureId]);
    }

    public function deleteServiceFeatures(int $serviceId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM service_features WHERE service_id = ?');
        return $stmt->execute([$serviceId]);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM services WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM services WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        
        return $stmt->fetchColumn() > 0;
    }
}
