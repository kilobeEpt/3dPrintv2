<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ContentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM site_content ORDER BY section_key ASC');
        $results = $stmt->fetchAll();
        
        foreach ($results as &$row) {
            if (isset($row['content'])) {
                $row['content'] = json_decode($row['content'], true);
            }
        }
        
        return $results;
    }

    public function findBySection(string $sectionKey): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM site_content WHERE section_key = ?');
        $stmt->execute([$sectionKey]);
        $result = $stmt->fetch();
        
        if ($result && isset($result['content'])) {
            $result['content'] = json_decode($result['content'], true);
        }
        
        return $result ?: null;
    }

    public function create(string $sectionKey, ?string $title, array $content): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO site_content (section_key, title, content)
            VALUES (:section_key, :title, :content)
        ');
        
        $stmt->execute([
            'section_key' => $sectionKey,
            'title' => $title,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE)
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function update(string $sectionKey, ?string $title, array $content): bool
    {
        $stmt = $this->db->prepare('
            UPDATE site_content 
            SET title = :title, content = :content
            WHERE section_key = :section_key
        ');
        
        return $stmt->execute([
            'section_key' => $sectionKey,
            'title' => $title,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function upsert(string $sectionKey, ?string $title, array $content): bool
    {
        $existing = $this->findBySection($sectionKey);
        
        if ($existing) {
            return $this->update($sectionKey, $title, $content);
        } else {
            $this->create($sectionKey, $title, $content);
            return true;
        }
    }

    public function delete(string $sectionKey): bool
    {
        $stmt = $this->db->prepare('DELETE FROM site_content WHERE section_key = ?');
        return $stmt->execute([$sectionKey]);
    }

    public function getStats(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM site_stats LIMIT 1');
        return $stmt->fetch() ?: null;
    }

    public function updateStats(array $data): bool
    {
        $existing = $this->getStats();
        
        if ($existing) {
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['total_projects', 'happy_clients', 'years_experience', 'awards'])) {
                    $fields[] = "{$key} = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $existing['id'];
            
            $sql = 'UPDATE site_stats SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } else {
            $stmt = $this->db->prepare('
                INSERT INTO site_stats (total_projects, happy_clients, years_experience, awards)
                VALUES (:total_projects, :happy_clients, :years_experience, :awards)
            ');
            
            return $stmt->execute([
                'total_projects' => $data['total_projects'] ?? 0,
                'happy_clients' => $data['happy_clients'] ?? 0,
                'years_experience' => $data['years_experience'] ?? 0,
                'awards' => $data['awards'] ?? 0
            ]);
        }
    }
}
