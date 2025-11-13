<?php

namespace App\Services;

use App\Repositories\FaqRepository;
use App\Helpers\Validator;

class FaqService
{
    private FaqRepository $repository;

    public function __construct()
    {
        $this->repository = new FaqRepository();
    }

    public function getAll(bool $activeOnly = false): array
    {
        return $this->repository->findAll($activeOnly);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
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
            return ['success' => false, 'error' => 'FAQ not found'];
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
            'question' => $required . 'string|min:1|max:500',
            'answer' => $required . 'string|min:1',
            'active' => 'boolean',
            'display_order' => 'integer'
        ];
    }
}
