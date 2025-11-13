<?php

namespace App\Services;

use App\Repositories\ContentRepository;
use App\Helpers\Validator;

class ContentService
{
    private ContentRepository $repository;

    public function __construct()
    {
        $this->repository = new ContentRepository();
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    public function getBySection(string $sectionKey): ?array
    {
        return $this->repository->findBySection($sectionKey);
    }

    public function upsert(string $sectionKey, array $data): array
    {
        $validator = new Validator();
        
        $rules = [
            'title' => 'string|max:255',
            'content' => 'required'
        ];
        
        if (!$validator->validate($data, $rules)) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        if (!is_array($data['content'])) {
            return ['success' => false, 'errors' => ['content' => 'Content must be an object/array']];
        }

        $this->repository->upsert($sectionKey, $data['title'] ?? null, $data['content']);
        
        return ['success' => true];
    }

    public function delete(string $sectionKey): bool
    {
        return $this->repository->delete($sectionKey);
    }

    public function getStats(): ?array
    {
        return $this->repository->getStats();
    }

    public function updateStats(array $data): array
    {
        $validator = new Validator();
        
        $rules = [
            'total_projects' => 'integer|min:0',
            'happy_clients' => 'integer|min:0',
            'years_experience' => 'integer|min:0',
            'awards' => 'integer|min:0'
        ];
        
        if (!$validator->validate($data, $rules)) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $this->repository->updateStats($data);
        
        return ['success' => true];
    }
}
