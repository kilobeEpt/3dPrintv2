<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Settings API Integration Tests
 */
class SettingsTest extends TestCase
{
    /**
     * Test public settings endpoint does not require authentication
     */
    public function testPublicSettingsAccessible(): void
    {
        $response = $this->makeRequest('GET', '/api/settings/public');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
    }

    /**
     * Test public settings excludes sensitive data
     */
    public function testPublicSettingsExcludesSensitiveData(): void
    {
        $response = $this->makeRequest('GET', '/api/settings/public');
        
        $this->assertEquals(200, $response['status']);
        $data = $response['body']['data'];
        
        // Should not contain bot tokens or sensitive config
        $this->assertArrayNotHasKey('bot_token', $data);
        $this->assertArrayNotHasKey('notification_preferences', $data);
        
        // Should contain public calculator and form settings
        $this->assertArrayHasKey('calculator', $data);
        $this->assertArrayHasKey('forms', $data);
    }

    /**
     * Test public settings includes calculator configuration
     */
    public function testPublicSettingsIncludesCalculatorConfig(): void
    {
        $response = $this->makeRequest('GET', '/api/settings/public');
        
        $this->assertEquals(200, $response['status']);
        $calculator = $response['body']['data']['calculator'];
        
        $this->assertArrayHasKey('materials', $calculator);
        $this->assertArrayHasKey('services', $calculator);
        $this->assertArrayHasKey('quality_levels', $calculator);
        $this->assertArrayHasKey('volume_discounts', $calculator);
        
        $this->assertIsArray($calculator['materials']);
        $this->assertIsArray($calculator['services']);
    }

    /**
     * Test public settings includes form field definitions
     */
    public function testPublicSettingsIncludesFormFields(): void
    {
        $response = $this->makeRequest('GET', '/api/settings/public');
        
        $this->assertEquals(200, $response['status']);
        $forms = $response['body']['data']['forms'];
        
        $this->assertArrayHasKey('fields', $forms);
        $this->assertIsArray($forms['fields']);
    }

    /**
     * Test admin settings endpoint requires authentication
     */
    public function testAdminSettingsRequiresAuth(): void
    {
        $response = $this->makeRequest('GET', '/api/settings');
        
        $this->assertEquals(401, $response['status']);
        $this->assertErrorResponse($response);
    }

    /**
     * Test admin can access full settings
     */
    public function testAdminCanAccessFullSettings(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/settings');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        
        $data = $response['body']['data'];
        $this->assertArrayHasKey('site', $data);
        $this->assertArrayHasKey('calculator', $data);
        $this->assertArrayHasKey('forms', $data);
        $this->assertArrayHasKey('integrations', $data);
    }

    /**
     * Test admin settings redacts sensitive tokens
     */
    public function testAdminSettingsRedactsSensitiveTokens(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/settings');
        
        $this->assertEquals(200, $response['status']);
        
        if (isset($response['body']['data']['integrations']['telegram']['bot_token'])) {
            $token = $response['body']['data']['integrations']['telegram']['bot_token'];
            
            // Token should be redacted (first 6 + last 3 chars, rest asterisks)
            if (!empty($token)) {
                $this->assertMatchesRegularExpression('/^\d{6}:\*+[A-Za-z0-9_-]{3}$/', $token,
                    'Bot token should be partially redacted');
            }
        }
    }

    /**
     * Test admin can update general settings
     */
    public function testAdminCanUpdateGeneralSettings(): void
    {
        // Get current settings
        $getResponse = $this->authenticatedRequest('GET', '/api/settings');
        $currentSiteName = $getResponse['body']['data']['site']['site_name'] ?? 'Default';
        
        // Update settings
        $newSiteName = 'Test Site Name ' . uniqid();
        $response = $this->authenticatedRequest('PUT', '/api/settings', [
            'site_name' => $newSiteName,
            'contact_email' => 'test@example.com'
        ]);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        
        // Verify update
        $verifyResponse = $this->authenticatedRequest('GET', '/api/settings');
        $this->assertEquals($newSiteName, $verifyResponse['body']['data']['site']['site_name']);
        
        // Restore original
        $this->authenticatedRequest('PUT', '/api/settings', [
            'site_name' => $currentSiteName
        ]);
    }

    /**
     * Test admin settings validation - invalid email
     */
    public function testUpdateGeneralSettingsInvalidEmail(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings', [
            'contact_email' => 'invalid-email'
        ]);
        
        $this->assertValidationError($response, 'contact_email');
    }

    /**
     * Test admin can update calculator settings
     */
    public function testAdminCanUpdateCalculatorSettings(): void
    {
        // Get current settings
        $getResponse = $this->authenticatedRequest('GET', '/api/settings');
        $currentMaterials = $getResponse['body']['data']['calculator']['materials'] ?? [];
        
        // Update calculator settings
        $testMaterials = [
            [
                'material_key' => 'test_material_' . uniqid(),
                'name' => 'Test Material',
                'price' => 100,
                'technology' => 'fdm'
            ]
        ];
        
        $response = $this->authenticatedRequest('PUT', '/api/settings/calculator', [
            'materials' => array_merge($currentMaterials, $testMaterials)
        ]);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
    }

    /**
     * Test calculator settings validation - negative price
     */
    public function testUpdateCalculatorSettingsNegativePrice(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings/calculator', [
            'materials' => [
                [
                    'material_key' => 'test',
                    'name' => 'Test',
                    'price' => -10, // Invalid negative price
                    'technology' => 'fdm'
                ]
            ]
        ]);
        
        // Expecting validation error
        $this->assertContains($response['status'], [400, 422]);
    }

    /**
     * Test calculator settings validation - invalid technology
     */
    public function testUpdateCalculatorSettingsInvalidTechnology(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings/calculator', [
            'materials' => [
                [
                    'material_key' => 'test',
                    'name' => 'Test',
                    'price' => 100,
                    'technology' => 'invalid_tech' // Invalid technology
                ]
            ]
        ]);
        
        // Expecting validation error
        $this->assertContains($response['status'], [400, 422]);
    }

    /**
     * Test admin can update form settings
     */
    public function testAdminCanUpdateFormSettings(): void
    {
        // Get current settings
        $getResponse = $this->authenticatedRequest('GET', '/api/settings');
        $currentFields = $getResponse['body']['data']['forms']['fields'] ?? [];
        
        // Add custom field
        $testField = [
            'form_type' => 'contact',
            'field_name' => 'test_field_' . uniqid(),
            'label' => 'Test Field',
            'field_type' => 'text',
            'required' => false,
            'enabled' => true,
            'display_order' => 99
        ];
        
        $response = $this->authenticatedRequest('PUT', '/api/settings/forms', [
            'fields' => array_merge($currentFields, [$testField])
        ]);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
    }

    /**
     * Test form settings validation - invalid field type
     */
    public function testUpdateFormSettingsInvalidFieldType(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings/forms', [
            'fields' => [
                [
                    'form_type' => 'contact',
                    'field_name' => 'test',
                    'label' => 'Test',
                    'field_type' => 'invalid_type', // Invalid field type
                    'required' => false,
                    'enabled' => true
                ]
            ]
        ]);
        
        // Expecting validation error
        $this->assertContains($response['status'], [400, 422]);
    }

    /**
     * Test admin can update Telegram settings
     */
    public function testAdminCanUpdateTelegramSettings(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings/telegram', [
            'chat_id' => '12345678'
        ]);
        
        // Should succeed or return validation error if bot token not configured
        $this->assertContains($response['status'], [200, 400, 422]);
    }

    /**
     * Test Telegram settings validation - invalid bot token format
     */
    public function testUpdateTelegramSettingsInvalidTokenFormat(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings/telegram', [
            'bot_token' => 'invalid-token-format'
        ]);
        
        // Expecting validation error
        $this->assertContains($response['status'], [400, 422]);
    }

    /**
     * Test quality multiplier validation - must be positive
     */
    public function testQualityMultiplierMustBePositive(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings/calculator', [
            'quality_levels' => [
                [
                    'quality_key' => 'test',
                    'name' => 'Test',
                    'price_multiplier' => 0, // Must be > 0
                    'time_multiplier' => 1
                ]
            ]
        ]);
        
        // Expecting validation error
        $this->assertContains($response['status'], [400, 422]);
    }

    /**
     * Test volume discount validation - discount percentage 0-100
     */
    public function testVolumeDiscountValidation(): void
    {
        $response = $this->authenticatedRequest('PUT', '/api/settings/calculator', [
            'volume_discounts' => [
                [
                    'min_quantity' => 100,
                    'discount_percent' => 150 // Invalid > 100
                ]
            ]
        ]);
        
        // Expecting validation error
        $this->assertContains($response['status'], [400, 422]);
    }

    /**
     * Test settings update persists to database
     */
    public function testSettingsUpdatePersistsToDatabase(): void
    {
        $uniqueValue = 'Test Value ' . uniqid();
        
        // Update settings
        $updateResponse = $this->authenticatedRequest('PUT', '/api/settings', [
            'site_name' => $uniqueValue
        ]);
        
        $this->assertEquals(200, $updateResponse['status']);
        
        // Verify via new GET request (not cached)
        $getResponse = $this->authenticatedRequest('GET', '/api/settings');
        $this->assertEquals($uniqueValue, $getResponse['body']['data']['site']['site_name']);
        
        // Verify public endpoint also reflects change
        $publicResponse = $this->makeRequest('GET', '/api/settings/public');
        if (isset($publicResponse['body']['data']['site']['site_name'])) {
            $this->assertEquals($uniqueValue, $publicResponse['body']['data']['site']['site_name']);
        }
    }

    /**
     * Test public cannot update settings
     */
    public function testPublicCannotUpdateSettings(): void
    {
        $endpoints = [
            ['PUT', '/api/settings'],
            ['PUT', '/api/settings/calculator'],
            ['PUT', '/api/settings/forms'],
            ['PUT', '/api/settings/telegram']
        ];
        
        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->makeRequest($method, $endpoint, []);
            $this->assertEquals(401, $response['status'],
                "Public should not be able to update $endpoint");
        }
    }
}
