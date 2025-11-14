<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Content API Integration Tests (Services, Portfolio, Testimonials, FAQ)
 */
class ContentTest extends TestCase
{
    private array $createdServiceIds = [];
    private array $createdPortfolioIds = [];

    /**
     * Test GET /api/services returns active services
     */
    public function testGetPublicServices(): void
    {
        $response = $this->makeRequest('GET', '/api/services');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertIsArray($response['body']['data']);
        
        // Verify all returned services are active
        foreach ($response['body']['data'] as $service) {
            $this->assertTrue($service['active'] ?? false);
            $this->assertArrayHasKey('name', $service);
            $this->assertArrayHasKey('icon', $service);
            $this->assertArrayHasKey('description', $service);
        }
    }

    /**
     * Test GET /api/admin/services returns all services
     */
    public function testGetAdminServicesIncludesInactive(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/admin/services');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertIsArray($response['body']['data']);
    }

    /**
     * Test admin can create service
     */
    public function testCreateService(): void
    {
        $serviceData = [
            'name' => 'Test Service ' . uniqid(),
            'icon' => 'fa-test',
            'description' => 'Test service description',
            'price' => '1000₽/шт',
            'features' => ['Feature 1', 'Feature 2', 'Feature 3'],
            'active' => true,
            'featured' => false,
            'display_order' => 0
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/admin/services', $serviceData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('id', $response['body']['data']);
        $this->assertArrayHasKey('slug', $response['body']['data']);
        
        // Verify features are returned
        $this->assertArrayHasKey('features', $response['body']['data']);
        $this->assertCount(3, $response['body']['data']['features']);
        
        $this->createdServiceIds[] = $response['body']['data']['id'];
    }

    /**
     * Test service slug auto-generation
     */
    public function testServiceSlugAutoGeneration(): void
    {
        $serviceName = 'Тестовая Услуга ' . uniqid();
        
        $response = $this->authenticatedRequest('POST', '/api/admin/services', [
            'name' => $serviceName,
            'icon' => 'fa-test',
            'description' => 'Test',
            'price' => '1000₽'
        ]);
        
        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('slug', $response['body']['data']);
        $this->assertNotEmpty($response['body']['data']['slug']);
        
        $this->createdServiceIds[] = $response['body']['data']['id'];
    }

    /**
     * Test admin can update service
     */
    public function testUpdateService(): void
    {
        // Create test service
        $service = $this->createTestService();
        $this->createdServiceIds[] = $service['id'];
        
        // Update service
        $newName = 'Updated Service ' . uniqid();
        $response = $this->authenticatedRequest('PUT', '/api/admin/services/' . $service['id'], [
            'name' => $newName,
            'price' => '2000₽'
        ]);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertEquals($newName, $response['body']['data']['name']);
        $this->assertEquals('2000₽', $response['body']['data']['price']);
    }

    /**
     * Test admin can delete service
     */
    public function testDeleteService(): void
    {
        // Create test service
        $service = $this->createTestService();
        
        // Delete service
        $response = $this->authenticatedRequest('DELETE', '/api/admin/services/' . $service['id']);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        
        // Verify service is deleted
        $getResponse = $this->authenticatedRequest('GET', '/api/admin/services/' . $service['id']);
        $this->assertEquals(404, $getResponse['status']);
    }

    /**
     * Test service validation - required fields
     */
    public function testServiceValidationRequiredFields(): void
    {
        $response = $this->authenticatedRequest('POST', '/api/admin/services', [
            // Missing required fields
        ]);
        
        $this->assertValidationError($response);
    }

    /**
     * Test GET /api/portfolio returns portfolio items
     */
    public function testGetPublicPortfolio(): void
    {
        $response = $this->makeRequest('GET', '/api/portfolio');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertIsArray($response['body']['data']);
    }

    /**
     * Test portfolio category filter
     */
    public function testPortfolioCategoryFilter(): void
    {
        $response = $this->makeRequest('GET', '/api/portfolio?category=prototype');
        
        $this->assertEquals(200, $response['status']);
        
        // Verify all returned items have category 'prototype'
        foreach ($response['body']['data'] as $item) {
            $this->assertEquals('prototype', $item['category']);
        }
    }

    /**
     * Test admin can create portfolio item
     */
    public function testCreatePortfolioItem(): void
    {
        $portfolioData = [
            'title' => 'Test Portfolio ' . uniqid(),
            'category' => 'functional',
            'description' => 'Test portfolio description',
            'image_url' => 'https://example.com/test.jpg',
            'details' => 'Test details'
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/admin/portfolio', $portfolioData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('id', $response['body']['data']);
        
        $this->createdPortfolioIds[] = $response['body']['data']['id'];
    }

    /**
     * Test portfolio category validation
     */
    public function testPortfolioCategoryValidation(): void
    {
        $response = $this->authenticatedRequest('POST', '/api/admin/portfolio', [
            'title' => 'Test',
            'category' => 'invalid_category',
            'description' => 'Test',
            'image_url' => 'https://example.com/test.jpg'
        ]);
        
        $this->assertValidationError($response, 'category');
    }

    /**
     * Test portfolio image_url validation
     */
    public function testPortfolioImageUrlValidation(): void
    {
        $response = $this->authenticatedRequest('POST', '/api/admin/portfolio', [
            'title' => 'Test',
            'category' => 'prototype',
            'description' => 'Test',
            'image_url' => 'not-a-url'
        ]);
        
        $this->assertValidationError($response, 'image_url');
    }

    /**
     * Test GET /api/testimonials returns approved only
     */
    public function testGetPublicTestimonialsApprovedOnly(): void
    {
        $response = $this->makeRequest('GET', '/api/testimonials');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        
        // Verify all returned testimonials are approved
        foreach ($response['body']['data'] as $testimonial) {
            $this->assertTrue($testimonial['approved'] ?? false);
            $this->assertArrayHasKey('name', $testimonial);
            $this->assertArrayHasKey('rating', $testimonial);
            $this->assertArrayHasKey('text', $testimonial);
        }
    }

    /**
     * Test GET /api/admin/testimonials returns all
     */
    public function testGetAdminTestimonialsIncludesPending(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/admin/testimonials');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertIsArray($response['body']['data']);
    }

    /**
     * Test testimonial rating validation (1-5)
     */
    public function testTestimonialRatingValidation(): void
    {
        $response = $this->authenticatedRequest('POST', '/api/admin/testimonials', [
            'name' => 'Test',
            'position' => 'Test',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'rating' => 6, // Invalid, must be 1-5
            'text' => 'Test testimonial'
        ]);
        
        $this->assertValidationError($response, 'rating');
    }

    /**
     * Test GET /api/faq returns active FAQ items
     */
    public function testGetPublicFAQActiveOnly(): void
    {
        $response = $this->makeRequest('GET', '/api/faq');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        
        // Verify all returned FAQ items are active
        foreach ($response['body']['data'] as $faq) {
            $this->assertTrue($faq['active'] ?? false);
            $this->assertArrayHasKey('question', $faq);
            $this->assertArrayHasKey('answer', $faq);
        }
    }

    /**
     * Test GET /api/admin/faq returns all FAQ items
     */
    public function testGetAdminFAQIncludesInactive(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/admin/faq');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertIsArray($response['body']['data']);
    }

    /**
     * Test GET /api/content returns all content sections
     */
    public function testGetAllContent(): void
    {
        $response = $this->makeRequest('GET', '/api/content');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertIsArray($response['body']['data']);
    }

    /**
     * Test GET /api/stats returns site statistics
     */
    public function testGetSiteStats(): void
    {
        $response = $this->makeRequest('GET', '/api/stats');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        
        $stats = $response['body']['data'];
        $this->assertArrayHasKey('total_projects', $stats);
        $this->assertArrayHasKey('happy_clients', $stats);
        $this->assertArrayHasKey('years_experience', $stats);
        $this->assertArrayHasKey('awards', $stats);
    }

    /**
     * Test public cannot create services
     */
    public function testPublicCannotCreateService(): void
    {
        $response = $this->makeRequest('POST', '/api/admin/services', [
            'name' => 'Test',
            'icon' => 'fa-test',
            'description' => 'Test',
            'price' => '1000₽'
        ]);
        
        $this->assertEquals(401, $response['status']);
    }

    /**
     * Test public cannot update portfolio
     */
    public function testPublicCannotUpdatePortfolio(): void
    {
        $response = $this->makeRequest('PUT', '/api/admin/portfolio/1', [
            'title' => 'Test'
        ]);
        
        $this->assertEquals(401, $response['status']);
    }

    /**
     * Test public cannot delete testimonials
     */
    public function testPublicCannotDeleteTestimonial(): void
    {
        $response = $this->makeRequest('DELETE', '/api/admin/testimonials/1');
        
        $this->assertEquals(401, $response['status']);
    }

    /**
     * Test Cyrillic characters in service name
     */
    public function testServiceWithCyrillicName(): void
    {
        $serviceName = 'Тестовая Услуга ' . uniqid();
        
        $response = $this->authenticatedRequest('POST', '/api/admin/services', [
            'name' => $serviceName,
            'icon' => 'fa-test',
            'description' => 'Описание на русском языке',
            'price' => '1000₽'
        ]);
        
        $this->assertEquals(201, $response['status']);
        $this->assertEquals($serviceName, $response['body']['data']['name']);
        
        $this->createdServiceIds[] = $response['body']['data']['id'];
    }

    /**
     * Clean up test data
     */
    protected function tearDown(): void
    {
        // Clean up services
        foreach ($this->createdServiceIds as $serviceId) {
            try {
                $this->authenticatedRequest('DELETE', '/api/admin/services/' . $serviceId);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
        
        // Clean up portfolio
        foreach ($this->createdPortfolioIds as $portfolioId) {
            try {
                $this->authenticatedRequest('DELETE', '/api/admin/portfolio/' . $portfolioId);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
        
        $this->createdServiceIds = [];
        $this->createdPortfolioIds = [];
        parent::tearDown();
    }
}
