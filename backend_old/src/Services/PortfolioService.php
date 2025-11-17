<?php

namespace App\Services;

use App\Repositories\PortfolioRepository;
use App\Helpers\Validator;

class PortfolioService
{
    private PortfolioRepository $repository;

    public function __construct()
    {
        $this->repository = new PortfolioRepository();
    }

    public function getAll(?string $category = null): array
    {
        return $this->repository->findAll($category);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function getCategories(): array
    {
        return $this->repository->getCategories();
    }

    public function create(array $data): array
    {
        $validator = new Validator();
        
        if (!$validator->validate($data, $this->getValidationRules())) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $id = $this->repository->create($data);
        
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repository->findById($id);
        
        if (!$existing) {
            return ['success' => false, 'error' => 'Portfolio item not found'];
        }

        $validator = new Validator();
        
        if (!$validator->validate($data, $this->getValidationRules(true))) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $this->repository->update($id, $data);
        
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
            'title' => $required . 'string|min:1|max:200',
            'category' => $required . 'in:prototype,functional,art,industrial',
            'description' => $required . 'string|min:1',
            'image_url' => $required . 'url|max:500',
            'details' => 'string'
        ];
    }
}
