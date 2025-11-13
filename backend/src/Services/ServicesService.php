<?php

namespace App\Services;

use App\Repositories\ServicesRepository;
use App\Helpers\Validator;

class ServicesService
{
    private ServicesRepository $repository;

    public function __construct()
    {
        $this->repository = new ServicesRepository();
    }

    public function getAll(bool $activeOnly = false): array
    {
        $services = $this->repository->findAll($activeOnly);
        
        foreach ($services as &$service) {
            $service['features'] = $this->repository->getFeatures($service['id']);
        }
        
        return $services;
    }

    public function getById(int $id): ?array
    {
        $service = $this->repository->findById($id);
        
        if ($service) {
            $service['features'] = $this->repository->getFeatures($service['id']);
        }
        
        return $service;
    }

    public function getBySlug(string $slug): ?array
    {
        $service = $this->repository->findBySlug($slug);
        
        if ($service) {
            $service['features'] = $this->repository->getFeatures($service['id']);
        }
        
        return $service;
    }

    public function create(array $data): array
    {
        $validator = new Validator();
        
        if (!$validator->validate($data, $this->getValidationRules())) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $slug = $data['slug'] ?? $this->generateSlug($data['name']);
        
        if ($this->repository->slugExists($slug)) {
            return ['success' => false, 'errors' => ['slug' => 'Slug already exists']];
        }

        $data['slug'] = $slug;
        
        $serviceId = $this->repository->create($data);
        
        if (isset($data['features']) && is_array($data['features'])) {
            foreach ($data['features'] as $index => $feature) {
                if (is_string($feature)) {
                    $this->repository->createFeature($serviceId, $feature, $index);
                } elseif (is_array($feature) && isset($feature['text'])) {
                    $this->repository->createFeature(
                        $serviceId,
                        $feature['text'],
                        $feature['order'] ?? $index
                    );
                }
            }
        }
        
        return ['success' => true, 'id' => $serviceId];
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repository->findById($id);
        
        if (!$existing) {
            return ['success' => false, 'error' => 'Service not found'];
        }

        $validator = new Validator();
        
        if (!$validator->validate($data, $this->getValidationRules(true))) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        if (isset($data['slug']) && $this->repository->slugExists($data['slug'], $id)) {
            return ['success' => false, 'errors' => ['slug' => 'Slug already exists']];
        }

        $this->repository->update($id, $data);
        
        if (isset($data['features']) && is_array($data['features'])) {
            $this->repository->deleteServiceFeatures($id);
            
            foreach ($data['features'] as $index => $feature) {
                if (is_string($feature)) {
                    $this->repository->createFeature($id, $feature, $index);
                } elseif (is_array($feature) && isset($feature['text'])) {
                    $this->repository->createFeature(
                        $id,
                        $feature['text'],
                        $feature['order'] ?? $index
                    );
                }
            }
        }
        
        return ['success' => true];
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    private function getValidationRules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? '' : 'required|';
        
        return [
            'name' => $required . 'string|min:1|max:100',
            'slug' => 'string|min:1|max:100',
            'icon' => $required . 'string|min:1|max:50',
            'description' => $required . 'string|min:1',
            'price' => $required . 'string|min:1|max:50',
            'active' => 'boolean',
            'featured' => 'boolean',
            'display_order' => 'integer',
            'features' => 'array'
        ];
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}
