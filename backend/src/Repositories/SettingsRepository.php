<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class SettingsRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Site Settings Methods
    public function getSiteSettings(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM site_settings LIMIT 1');
        $result = $stmt->fetch();
        
        if ($result) {
            if (isset($result['social_links'])) {
                $result['social_links'] = json_decode($result['social_links'], true);
            }
            if (isset($result['notifications'])) {
                $result['notifications'] = json_decode($result['notifications'], true);
            }
        }
        
        return $result ?: null;
    }

    public function updateSiteSettings(array $data): bool
    {
        $existing = $this->getSiteSettings();
        
        if ($existing) {
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'site_name', 'site_description', 'contact_email', 'contact_phone', 
                'address', 'working_hours', 'timezone', 'social_links', 
                'theme', 'color_primary', 'color_secondary', 'notifications'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "{$key} = ?";
                    
                    if (in_array($key, ['social_links', 'notifications']) && is_array($value)) {
                        $params[] = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } else {
                        $params[] = $value;
                    }
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $existing['id'];
            
            $sql = 'UPDATE site_settings SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } else {
            return $this->createSiteSettings($data);
        }
    }

    private function createSiteSettings(array $data): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO site_settings (
                site_name, site_description, contact_email, contact_phone,
                address, working_hours, timezone, social_links,
                theme, color_primary, color_secondary, notifications
            ) VALUES (
                :site_name, :site_description, :contact_email, :contact_phone,
                :address, :working_hours, :timezone, :social_links,
                :theme, :color_primary, :color_secondary, :notifications
            )
        ');
        
        return $stmt->execute([
            'site_name' => $data['site_name'] ?? '3D Print Pro',
            'site_description' => $data['site_description'] ?? '',
            'contact_email' => $data['contact_email'] ?? '',
            'contact_phone' => $data['contact_phone'] ?? '',
            'address' => $data['address'] ?? '',
            'working_hours' => $data['working_hours'] ?? '',
            'timezone' => $data['timezone'] ?? 'Europe/Moscow',
            'social_links' => json_encode($data['social_links'] ?? [], JSON_UNESCAPED_UNICODE),
            'theme' => $data['theme'] ?? 'light',
            'color_primary' => $data['color_primary'] ?? '#6366f1',
            'color_secondary' => $data['color_secondary'] ?? '#ec4899',
            'notifications' => json_encode($data['notifications'] ?? [], JSON_UNESCAPED_UNICODE)
        ]);
    }

    // Integrations Methods
    public function getIntegration(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM integrations WHERE integration_name = ?');
        $stmt->execute([$name]);
        $result = $stmt->fetch();
        
        if ($result && isset($result['config'])) {
            $result['config'] = json_decode($result['config'], true);
        }
        
        return $result ?: null;
    }

    public function updateIntegration(string $name, bool $enabled, array $config): bool
    {
        $existing = $this->getIntegration($name);
        
        if ($existing) {
            $stmt = $this->db->prepare('
                UPDATE integrations 
                SET enabled = :enabled, config = :config
                WHERE integration_name = :name
            ');
            
            return $stmt->execute([
                'name' => $name,
                'enabled' => $enabled ? 1 : 0,
                'config' => json_encode($config, JSON_UNESCAPED_UNICODE)
            ]);
        } else {
            $stmt = $this->db->prepare('
                INSERT INTO integrations (integration_name, enabled, config)
                VALUES (:name, :enabled, :config)
            ');
            
            return $stmt->execute([
                'name' => $name,
                'enabled' => $enabled ? 1 : 0,
                'config' => json_encode($config, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    // Calculator Materials Methods
    public function getAllMaterials(): array
    {
        $stmt = $this->db->query('
            SELECT * FROM materials 
            WHERE active = 1 
            ORDER BY display_order ASC, id ASC
        ');
        return $stmt->fetchAll();
    }

    public function updateMaterial(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['material_key', 'name', 'price', 'technology', 'active', 'display_order'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE materials SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function createMaterial(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO materials (material_key, name, price, technology, active, display_order)
            VALUES (:material_key, :name, :price, :technology, :active, :display_order)
        ');
        
        $stmt->execute([
            'material_key' => $data['material_key'],
            'name' => $data['name'],
            'price' => $data['price'],
            'technology' => $data['technology'],
            'active' => $data['active'] ?? 1,
            'display_order' => $data['display_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function deleteMaterial(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM materials WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Additional Services Methods
    public function getAllAdditionalServices(): array
    {
        $stmt = $this->db->query('
            SELECT * FROM additional_services 
            WHERE active = 1 
            ORDER BY display_order ASC, id ASC
        ');
        return $stmt->fetchAll();
    }

    public function updateAdditionalService(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['service_key', 'name', 'price', 'unit', 'active', 'display_order'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE additional_services SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function createAdditionalService(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO additional_services (service_key, name, price, unit, active, display_order)
            VALUES (:service_key, :name, :price, :unit, :active, :display_order)
        ');
        
        $stmt->execute([
            'service_key' => $data['service_key'],
            'name' => $data['name'],
            'price' => $data['price'],
            'unit' => $data['unit'],
            'active' => $data['active'] ?? 1,
            'display_order' => $data['display_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function deleteAdditionalService(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM additional_services WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Quality Levels Methods
    public function getAllQualityLevels(): array
    {
        $stmt = $this->db->query('
            SELECT * FROM quality_levels 
            WHERE active = 1 
            ORDER BY display_order ASC, id ASC
        ');
        return $stmt->fetchAll();
    }

    public function updateQualityLevel(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['quality_key', 'name', 'price_multiplier', 'time_multiplier', 'active', 'display_order'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE quality_levels SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function createQualityLevel(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO quality_levels (quality_key, name, price_multiplier, time_multiplier, active, display_order)
            VALUES (:quality_key, :name, :price_multiplier, :time_multiplier, :active, :display_order)
        ');
        
        $stmt->execute([
            'quality_key' => $data['quality_key'],
            'name' => $data['name'],
            'price_multiplier' => $data['price_multiplier'],
            'time_multiplier' => $data['time_multiplier'],
            'active' => $data['active'] ?? 1,
            'display_order' => $data['display_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function deleteQualityLevel(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM quality_levels WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Volume Discounts Methods
    public function getAllVolumeDiscounts(): array
    {
        $stmt = $this->db->query('
            SELECT * FROM volume_discounts 
            WHERE active = 1 
            ORDER BY min_quantity ASC
        ');
        return $stmt->fetchAll();
    }

    public function updateVolumeDiscount(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['min_quantity', 'discount_percent', 'active'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE volume_discounts SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function createVolumeDiscount(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO volume_discounts (min_quantity, discount_percent, active)
            VALUES (:min_quantity, :discount_percent, :active)
        ');
        
        $stmt->execute([
            'min_quantity' => $data['min_quantity'],
            'discount_percent' => $data['discount_percent'],
            'active' => $data['active'] ?? 1
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function deleteVolumeDiscount(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM volume_discounts WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Form Fields Methods
    public function getAllFormFields(): array
    {
        $stmt = $this->db->query('
            SELECT * FROM form_fields 
            WHERE enabled = 1 
            ORDER BY form_type ASC, display_order ASC, id ASC
        ');
        $results = $stmt->fetchAll();
        
        foreach ($results as &$row) {
            if (isset($row['options']) && $row['options']) {
                $row['options'] = json_decode($row['options'], true);
            }
        }
        
        return $results;
    }

    public function getFormFieldsByType(string $formType): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM form_fields 
            WHERE form_type = ? AND enabled = 1 
            ORDER BY display_order ASC, id ASC
        ');
        $stmt->execute([$formType]);
        $results = $stmt->fetchAll();
        
        foreach ($results as &$row) {
            if (isset($row['options']) && $row['options']) {
                $row['options'] = json_decode($row['options'], true);
            }
        }
        
        return $results;
    }

    public function updateFormField(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['form_type', 'field_name', 'label', 'field_type', 'required', 'enabled', 'placeholder', 'display_order', 'options'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                
                if ($key === 'options' && is_array($value)) {
                    $params[] = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    $params[] = $value;
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = 'UPDATE form_fields SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function createFormField(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO form_fields (form_type, field_name, label, field_type, required, enabled, placeholder, display_order, options)
            VALUES (:form_type, :field_name, :label, :field_type, :required, :enabled, :placeholder, :display_order, :options)
        ');
        
        $stmt->execute([
            'form_type' => $data['form_type'],
            'field_name' => $data['field_name'],
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'required' => $data['required'] ?? 0,
            'enabled' => $data['enabled'] ?? 1,
            'placeholder' => $data['placeholder'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
            'options' => isset($data['options']) && is_array($data['options']) 
                ? json_encode($data['options'], JSON_UNESCAPED_UNICODE) 
                : null
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function deleteFormField(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM form_fields WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
