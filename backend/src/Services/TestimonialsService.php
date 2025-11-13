<?php

namespace App\Services;

use App\Repositories\TestimonialsRepository;
use App\Helpers\Validator;

class TestimonialsService
{
    private TestimonialsRepository $repository;

    public function __construct()
    {
        $this->repository = new TestimonialsRepository();
    }

    public function getAll(bool $approvedOnly = false): array
    {
        return $this->repository->findAll($approvedOnly);
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
            return ['success' => false, 'error' => 'Testimonial not found'];
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
            'name' => $required . 'string|min:1|max:100',
            'position' => $required . 'string|min:1|max:100',
            'avatar_url' => $required . 'url|max:500',
            'rating' => 'integer|between:1,5',
            'text' => $required . 'string|min:1',
            'approved' => 'boolean',
            'display_order' => 'integer'
        ];
    }
}
