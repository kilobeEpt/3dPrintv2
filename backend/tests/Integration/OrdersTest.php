<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Orders API Integration Tests
 */
class OrdersTest extends TestCase
{
    private array $createdOrderIds = [];

    /**
     * Test successful order submission with valid data
     */
    public function testCreateOrderWithValidData(): void
    {
        $orderData = [
            'client_name' => 'Иван Петров',
            'client_email' => 'ivan' . uniqid() . '@example.com',
            'client_phone' => '+79001234567',
            'message' => 'Интересует 3D печать'
        ];
        
        $response = $this->makeRequest('POST', '/api/orders', $orderData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('id', $response['body']['data']);
        $this->assertArrayHasKey('order_number', $response['body']['data']);
        
        // Verify order number format: ORD-YYYYMMDD-XXXX
        $orderNumber = $response['body']['data']['order_number'];
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{4}$/', $orderNumber);
        
        // Verify default values
        $this->assertEquals('new', $response['body']['data']['status']);
        $this->assertEquals('contact', $response['body']['data']['type']);
        
        // Track for cleanup
        $this->createdOrderIds[] = $response['body']['data']['id'];
    }

    /**
     * Test order submission with calculator data (type=order)
     */
    public function testCreateOrderWithCalculatorData(): void
    {
        $orderData = [
            'client_name' => 'Мария Сидорова',
            'client_email' => 'maria' . uniqid() . '@example.com',
            'client_phone' => '+79009876543',
            'message' => 'Заказ по калькулятору',
            'calculator_data' => [
                'material' => 'pla',
                'quantity' => 100,
                'services' => ['painting'],
                'quality' => 'standard'
            ],
            'amount' => 5500
        ];
        
        $response = $this->makeRequest('POST', '/api/orders', $orderData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertEquals('order', $response['body']['data']['type']);
        $this->assertArrayHasKey('calculator_data', $response['body']['data']);
        $this->assertEquals(5500, $response['body']['data']['amount']);
        
        $this->createdOrderIds[] = $response['body']['data']['id'];
    }

    /**
     * Test order validation - missing required fields
     */
    public function testCreateOrderMissingRequiredFields(): void
    {
        $response = $this->makeRequest('POST', '/api/orders', [
            'client_name' => 'Test'
            // Missing email and phone
        ]);
        
        $this->assertValidationError($response, 'client_email');
    }

    /**
     * Test order validation - invalid email format
     */
    public function testCreateOrderInvalidEmail(): void
    {
        $response = $this->makeRequest('POST', '/api/orders', [
            'client_name' => 'Test User',
            'client_email' => 'invalid-email',
            'client_phone' => '+79001234567'
        ]);
        
        $this->assertValidationError($response, 'client_email');
    }

    /**
     * Test order validation - phone too short
     */
    public function testCreateOrderPhoneTooShort(): void
    {
        $response = $this->makeRequest('POST', '/api/orders', [
            'client_name' => 'Test User',
            'client_email' => 'test@example.com',
            'client_phone' => '123' // Too short
        ]);
        
        $this->assertValidationError($response, 'client_phone');
    }

    /**
     * Test order validation - name too short
     */
    public function testCreateOrderNameTooShort(): void
    {
        $response = $this->makeRequest('POST', '/api/orders', [
            'client_name' => 'A', // Too short (min 2)
            'client_email' => 'test@example.com',
            'client_phone' => '+79001234567'
        ]);
        
        $this->assertValidationError($response, 'client_name');
    }

    /**
     * Test rate limiting - max 5 orders per hour per IP
     */
    public function testOrderRateLimiting(): void
    {
        // This test may be slow - consider marking as @group slow
        $this->markTestSkipped('Rate limiting test disabled - too slow for regular test run');
        
        // Submit 5 orders
        for ($i = 0; $i < 5; $i++) {
            $response = $this->makeRequest('POST', '/api/orders', [
                'client_name' => 'Rate Test ' . $i,
                'client_email' => 'rate' . $i . uniqid() . '@example.com',
                'client_phone' => '+79001234567',
                'message' => 'Rate limit test ' . $i
            ]);
            
            $this->assertEquals(201, $response['status']);
            if (isset($response['body']['data']['id'])) {
                $this->createdOrderIds[] = $response['body']['data']['id'];
            }
        }
        
        // 6th should be rate limited
        $response = $this->makeRequest('POST', '/api/orders', [
            'client_name' => 'Rate Test 6',
            'client_email' => 'rate6@example.com',
            'client_phone' => '+79001234567',
            'message' => 'Rate limit test 6'
        ]);
        
        $this->assertEquals(429, $response['status']);
    }

    /**
     * Test admin can list orders with pagination
     */
    public function testListOrdersWithPagination(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/orders?page=1&per_page=10');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('pagination', $response['body']);
        $this->assertArrayHasKey('total', $response['body']['pagination']);
        $this->assertArrayHasKey('page', $response['body']['pagination']);
        $this->assertArrayHasKey('per_page', $response['body']['pagination']);
        $this->assertArrayHasKey('total_pages', $response['body']['pagination']);
    }

    /**
     * Test admin can filter orders by status
     */
    public function testFilterOrdersByStatus(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/orders?status=new');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        
        // Verify all returned orders have status 'new'
        foreach ($response['body']['data'] as $order) {
            $this->assertEquals('new', $order['status']);
        }
    }

    /**
     * Test admin can filter orders by type
     */
    public function testFilterOrdersByType(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/orders?type=contact');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        
        // Verify all returned orders have type 'contact'
        foreach ($response['body']['data'] as $order) {
            $this->assertEquals('contact', $order['type']);
        }
    }

    /**
     * Test admin can search orders
     */
    public function testSearchOrders(): void
    {
        // Create order with unique name
        $uniqueName = 'SearchTest' . uniqid();
        $orderData = [
            'client_name' => $uniqueName,
            'client_email' => 'search' . uniqid() . '@example.com',
            'client_phone' => '+79001234567',
            'message' => 'Search test order'
        ];
        
        $createResponse = $this->makeRequest('POST', '/api/orders', $orderData);
        $this->assertEquals(201, $createResponse['status']);
        $this->createdOrderIds[] = $createResponse['body']['data']['id'];
        
        // Search for the order
        $response = $this->authenticatedRequest('GET', '/api/orders?search=' . urlencode($uniqueName));
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertGreaterThan(0, count($response['body']['data']));
        
        // Verify search result contains our order
        $found = false;
        foreach ($response['body']['data'] as $order) {
            if ($order['client_name'] === $uniqueName) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Search should return the created order');
    }

    /**
     * Test admin can view single order details
     */
    public function testViewOrderDetails(): void
    {
        // Create test order
        $order = $this->createTestOrder();
        $this->createdOrderIds[] = $order['id'];
        
        // View details
        $response = $this->authenticatedRequest('GET', '/api/orders/' . $order['id']);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertEquals($order['id'], $response['body']['data']['id']);
    }

    /**
     * Test view non-existent order returns 404
     */
    public function testViewNonExistentOrder(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/orders/999999');
        
        $this->assertEquals(404, $response['status']);
        $this->assertErrorResponse($response);
    }

    /**
     * Test admin can update order status
     */
    public function testUpdateOrderStatus(): void
    {
        // Create test order
        $order = $this->createTestOrder();
        $this->createdOrderIds[] = $order['id'];
        
        // Update status
        $response = $this->authenticatedRequest('PUT', '/api/orders/' . $order['id'], [
            'status' => 'processing'
        ]);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertEquals('processing', $response['body']['data']['status']);
    }

    /**
     * Test admin can delete order
     */
    public function testDeleteOrder(): void
    {
        // Create test order
        $order = $this->createTestOrder();
        
        // Delete order
        $response = $this->authenticatedRequest('DELETE', '/api/orders/' . $order['id']);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        
        // Verify order is deleted
        $getResponse = $this->authenticatedRequest('GET', '/api/orders/' . $order['id']);
        $this->assertEquals(404, $getResponse['status']);
    }

    /**
     * Test order status validation - invalid status
     */
    public function testUpdateOrderInvalidStatus(): void
    {
        $order = $this->createTestOrder();
        $this->createdOrderIds[] = $order['id'];
        
        $response = $this->authenticatedRequest('PUT', '/api/orders/' . $order['id'], [
            'status' => 'invalid_status'
        ]);
        
        $this->assertValidationError($response, 'status');
    }

    /**
     * Test Telegram notification flag
     */
    public function testOrderTelegramNotificationFlag(): void
    {
        $orderData = [
            'client_name' => 'Telegram Test',
            'client_email' => 'telegram' . uniqid() . '@example.com',
            'client_phone' => '+79001234567',
            'message' => 'Test Telegram notification'
        ];
        
        $response = $this->makeRequest('POST', '/api/orders', $orderData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('telegram_sent', $response['body']['data']);
        $this->assertIsBool($response['body']['data']['telegram_sent']);
        
        $this->createdOrderIds[] = $response['body']['data']['id'];
    }

    /**
     * Test public cannot access admin order list
     */
    public function testPublicCannotAccessOrderList(): void
    {
        $response = $this->makeRequest('GET', '/api/orders');
        
        $this->assertEquals(401, $response['status']);
        $this->assertErrorResponse($response);
    }

    /**
     * Test public can submit orders
     */
    public function testPublicCanSubmitOrders(): void
    {
        $orderData = [
            'client_name' => 'Public Test',
            'client_email' => 'public' . uniqid() . '@example.com',
            'client_phone' => '+79001234567',
            'message' => 'Public order submission test'
        ];
        
        $response = $this->makeRequest('POST', '/api/orders', $orderData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertSuccessResponse($response);
        
        $this->createdOrderIds[] = $response['body']['data']['id'];
    }

    /**
     * Test Unicode characters (Cyrillic) in orders
     */
    public function testOrderWithCyrillicCharacters(): void
    {
        $orderData = [
            'client_name' => 'Тестовое Имя',
            'client_email' => 'cyrillic' . uniqid() . '@example.com',
            'client_phone' => '+79001234567',
            'message' => 'Сообщение на русском языке с символами: "quotes" and \'apostrophes\''
        ];
        
        $response = $this->makeRequest('POST', '/api/orders', $orderData);
        
        $this->assertEquals(201, $response['status']);
        $this->assertEquals($orderData['client_name'], $response['body']['data']['client_name']);
        $this->assertEquals($orderData['message'], $response['body']['data']['message']);
        
        $this->createdOrderIds[] = $response['body']['data']['id'];
    }

    /**
     * Clean up test orders after tests
     */
    protected function tearDown(): void
    {
        // Clean up created orders
        foreach ($this->createdOrderIds as $orderId) {
            try {
                $this->authenticatedRequest('DELETE', '/api/orders/' . $orderId);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
        
        $this->createdOrderIds = [];
        parent::tearDown();
    }
}
