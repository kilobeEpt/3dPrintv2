#!/usr/bin/env php
<?php

/**
 * ========================================
 * 3D Print Pro - Data Migration Importer
 * ========================================
 * 
 * Imports localStorage export JSON into MySQL database
 * 
 * Usage:
 *   php import_local_data.php --file=export.json [options]
 * 
 * Options:
 *   --file=<path>          Path to JSON export file (required)
 *   --dry-run              Show what would be imported without making changes
 *   --skip-orders          Skip importing orders table
 *   --skip-portfolio       Skip importing portfolio table
 *   --skip-services        Skip importing services table
 *   --skip-testimonials    Skip importing testimonials table
 *   --skip-faq             Skip importing FAQ table
 *   --skip-settings        Skip importing settings table
 *   --skip-content         Skip importing content table
 *   --skip-stats           Skip importing stats table
 *   --force                Overwrite existing data (use with caution)
 *   --verbose              Show detailed import progress
 *   --help                 Show this help message
 * 
 * Examples:
 *   php import_local_data.php --file=export.json --dry-run
 *   php import_local_data.php --file=export.json --skip-orders
 *   php import_local_data.php --file=export.json --force --verbose
 */

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Config\Database;

// ========================================
// IMPORT CONTROLLER CLASS
// ========================================

class DataImporter
{
    private PDO $pdo;
    private array $options;
    private array $stats = [
        'services' => 0,
        'service_features' => 0,
        'portfolio' => 0,
        'testimonials' => 0,
        'faq' => 0,
        'orders' => 0,
        'settings' => 0,
        'content' => 0,
        'stats' => 0,
        'materials' => 0,
        'additional_services' => 0,
        'quality_levels' => 0,
        'volume_discounts' => 0,
        'form_fields' => 0,
        'integrations' => 0
    ];
    private array $errors = [];

    public function __construct(array $options)
    {
        $this->options = $options;
        
        // Initialize database connection
        Database::init([
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? '',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4'
        ]);
        
        $this->pdo = Database::getConnection();
    }

    public function import(array $data): bool
    {
        $this->log("🚀 Starting data import...\n", 'info');

        if ($this->options['dry-run']) {
            $this->log("🔍 DRY RUN MODE - No changes will be made\n", 'warning');
        }

        try {
            // Start transaction for data consistency
            if (!$this->options['dry-run']) {
                $this->pdo->beginTransaction();
            }

            // Import in correct order (respecting foreign key constraints)
            if (!$this->shouldSkip('services')) {
                $this->importServices($data['services'] ?? []);
            }

            if (!$this->shouldSkip('portfolio')) {
                $this->importPortfolio($data['portfolio'] ?? []);
            }

            if (!$this->shouldSkip('testimonials')) {
                $this->importTestimonials($data['testimonials'] ?? []);
            }

            if (!$this->shouldSkip('faq')) {
                $this->importFaq($data['faq'] ?? []);
            }

            if (!$this->shouldSkip('settings')) {
                $this->importSettings($data['settings'] ?? []);
            }

            if (!$this->shouldSkip('content')) {
                $this->importContent($data['content'] ?? []);
            }

            if (!$this->shouldSkip('stats')) {
                $this->importStats($data['stats'] ?? null);
            }

            if (!$this->shouldSkip('orders')) {
                $this->importOrders($data['orders'] ?? []);
            }

            // Commit transaction
            if (!$this->options['dry-run']) {
                $this->pdo->commit();
                $this->log("\n✅ Transaction committed successfully\n", 'success');
            }

            // Print summary
            $this->printSummary();

            return count($this->errors) === 0;

        } catch (Exception $e) {
            if (!$this->options['dry-run']) {
                $this->pdo->rollBack();
            }
            $this->log("\n❌ Import failed: " . $e->getMessage() . "\n", 'error');
            $this->log("Stack trace:\n" . $e->getTraceAsString() . "\n", 'error');
            return false;
        }
    }

    private function importServices(array $services): void
    {
        $this->log("\n📦 Importing services...", 'info');

        foreach ($services as $service) {
            try {
                // Extract features before inserting service
                $features = $service['features'] ?? [];

                // Map localStorage fields to database fields
                $data = [
                    'name' => $service['name'] ?? '',
                    'slug' => $service['slug'] ?? $this->generateSlug($service['name'] ?? ''),
                    'icon' => $service['icon'] ?? 'fa-cube',
                    'description' => $service['description'] ?? '',
                    'price' => $service['price'] ?? 'от 0₽',
                    'active' => $service['active'] ?? true,
                    'featured' => $service['featured'] ?? false,
                    'display_order' => $service['order'] ?? 0
                ];

                if ($this->options['dry-run']) {
                    $this->log("  [DRY RUN] Would insert service: {$data['name']}", 'info');
                    $serviceId = rand(1, 1000); // Fake ID for dry run
                } else {
                    $serviceId = $this->insertService($data);
                }

                if ($serviceId && !empty($features)) {
                    $this->importServiceFeatures($serviceId, $features);
                }

                $this->stats['services']++;

            } catch (Exception $e) {
                $this->errors[] = "Service '{$service['name']}': {$e->getMessage()}";
                $this->log("  ❌ Error: {$e->getMessage()}", 'error');
            }
        }

        $this->log("  ✅ Imported {$this->stats['services']} services", 'success');
    }

    private function insertService(array $data): int
    {
        $sql = "INSERT INTO services (name, slug, icon, description, price, active, featured, display_order)
                VALUES (:name, :slug, :icon, :description, :price, :active, :featured, :display_order)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'icon' => $data['icon'],
            'description' => $data['description'],
            'price' => $data['price'],
            'active' => $data['active'] ? 1 : 0,
            'featured' => $data['featured'] ? 1 : 0,
            'display_order' => $data['display_order']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function importServiceFeatures(int $serviceId, array $features): void
    {
        $order = 0;
        foreach ($features as $feature) {
            $order++;
            
            // Handle both string and object feature formats
            $featureText = is_string($feature) ? $feature : ($feature['text'] ?? '');
            
            if (empty($featureText)) {
                continue;
            }

            if ($this->options['dry-run']) {
                $this->log("    [DRY RUN] Would insert feature: {$featureText}", 'info');
            } else {
                $sql = "INSERT INTO service_features (service_id, feature_text, display_order)
                        VALUES (:service_id, :feature_text, :display_order)";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'service_id' => $serviceId,
                    'feature_text' => $featureText,
                    'display_order' => $order
                ]);
            }

            $this->stats['service_features']++;
        }
    }

    private function importPortfolio(array $portfolio): void
    {
        $this->log("\n🖼️  Importing portfolio...", 'info');

        foreach ($portfolio as $item) {
            try {
                $data = [
                    'title' => $item['title'] ?? '',
                    'category' => $this->mapPortfolioCategory($item['category'] ?? 'prototype'),
                    'description' => $item['description'] ?? '',
                    'image_url' => $item['image'] ?? ($item['image_url'] ?? ''),
                    'details' => $item['details'] ?? null
                ];

                if ($this->options['dry-run']) {
                    $this->log("  [DRY RUN] Would insert portfolio: {$data['title']}", 'info');
                } else {
                    $sql = "INSERT INTO portfolio (title, category, description, image_url, details)
                            VALUES (:title, :category, :description, :image_url, :details)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($data);
                }

                $this->stats['portfolio']++;

            } catch (Exception $e) {
                $this->errors[] = "Portfolio '{$item['title']}': {$e->getMessage()}";
                $this->log("  ❌ Error: {$e->getMessage()}", 'error');
            }
        }

        $this->log("  ✅ Imported {$this->stats['portfolio']} portfolio items", 'success');
    }

    private function importTestimonials(array $testimonials): void
    {
        $this->log("\n💬 Importing testimonials...", 'info');

        foreach ($testimonials as $testimonial) {
            try {
                $data = [
                    'name' => $testimonial['name'] ?? '',
                    'position' => $testimonial['position'] ?? '',
                    'avatar_url' => $testimonial['avatar'] ?? ($testimonial['avatar_url'] ?? ''),
                    'rating' => $testimonial['rating'] ?? 5,
                    'text' => $testimonial['text'] ?? '',
                    'approved' => $testimonial['approved'] ?? true,
                    'display_order' => $testimonial['order'] ?? 0
                ];

                if ($this->options['dry-run']) {
                    $this->log("  [DRY RUN] Would insert testimonial: {$data['name']}", 'info');
                } else {
                    $sql = "INSERT INTO testimonials (name, position, avatar_url, rating, text, approved, display_order)
                            VALUES (:name, :position, :avatar_url, :rating, :text, :approved, :display_order)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'name' => $data['name'],
                        'position' => $data['position'],
                        'avatar_url' => $data['avatar_url'],
                        'rating' => $data['rating'],
                        'text' => $data['text'],
                        'approved' => $data['approved'] ? 1 : 0,
                        'display_order' => $data['display_order']
                    ]);
                }

                $this->stats['testimonials']++;

            } catch (Exception $e) {
                $this->errors[] = "Testimonial '{$testimonial['name']}': {$e->getMessage()}";
                $this->log("  ❌ Error: {$e->getMessage()}", 'error');
            }
        }

        $this->log("  ✅ Imported {$this->stats['testimonials']} testimonials", 'success');
    }

    private function importFaq(array $faq): void
    {
        $this->log("\n❓ Importing FAQ...", 'info');

        foreach ($faq as $item) {
            try {
                $data = [
                    'question' => $item['question'] ?? '',
                    'answer' => $item['answer'] ?? '',
                    'active' => $item['active'] ?? true,
                    'display_order' => $item['order'] ?? 0
                ];

                if ($this->options['dry-run']) {
                    $this->log("  [DRY RUN] Would insert FAQ: {$data['question']}", 'info');
                } else {
                    $sql = "INSERT INTO faq (question, answer, active, display_order)
                            VALUES (:question, :answer, :active, :display_order)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'question' => $data['question'],
                        'answer' => $data['answer'],
                        'active' => $data['active'] ? 1 : 0,
                        'display_order' => $data['display_order']
                    ]);
                }

                $this->stats['faq']++;

            } catch (Exception $e) {
                $this->errors[] = "FAQ '{$item['question']}': {$e->getMessage()}";
                $this->log("  ❌ Error: {$e->getMessage()}", 'error');
            }
        }

        $this->log("  ✅ Imported {$this->stats['faq']} FAQ items", 'success');
    }

    private function importSettings(array $settings): void
    {
        $this->log("\n⚙️  Importing settings...", 'info');

        // Settings can be either array with single item or object
        $setting = is_array($settings) && isset($settings[0]) ? $settings[0] : $settings;

        if (empty($setting)) {
            $this->log("  ⚠️  No settings data to import", 'warning');
            return;
        }

        try {
            // Import site_settings table
            $siteData = [
                'site_name' => $setting['siteName'] ?? '3D Print Pro',
                'site_description' => $setting['siteDescription'] ?? '',
                'contact_email' => $setting['contactEmail'] ?? '',
                'contact_phone' => $setting['contactPhone'] ?? '',
                'address' => $setting['address'] ?? '',
                'working_hours' => $setting['workingHours'] ?? '',
                'timezone' => $setting['timezone'] ?? 'Europe/Moscow',
                'social_links' => json_encode($setting['socialLinks'] ?? []),
                'theme' => $setting['theme'] ?? 'light',
                'color_primary' => $setting['colorPrimary'] ?? '#6366f1',
                'color_secondary' => $setting['colorSecondary'] ?? '#ec4899',
                'notifications' => json_encode($setting['notifications'] ?? [])
            ];

            if ($this->options['dry-run']) {
                $this->log("  [DRY RUN] Would insert/update site_settings", 'info');
            } else {
                // Check if settings exist
                $exists = $this->pdo->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
                
                if ($exists > 0 && !$this->options['force']) {
                    $this->log("  ⚠️  Site settings already exist. Use --force to overwrite.", 'warning');
                } else {
                    // Clear existing settings if force mode
                    if ($exists > 0) {
                        $this->pdo->exec("DELETE FROM site_settings");
                    }

                    $sql = "INSERT INTO site_settings (site_name, site_description, contact_email, contact_phone, 
                            address, working_hours, timezone, social_links, theme, color_primary, color_secondary, notifications)
                            VALUES (:site_name, :site_description, :contact_email, :contact_phone, 
                            :address, :working_hours, :timezone, :social_links, :theme, :color_primary, :color_secondary, :notifications)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($siteData);
                    
                    $this->stats['settings']++;
                }
            }

            // Import calculator config to separate tables
            if (isset($setting['calculator'])) {
                $this->importCalculatorConfig($setting['calculator']);
            }

            // Import form fields
            if (isset($setting['formFields'])) {
                $this->importFormFields($setting['formFields']);
            }

            // Import Telegram integration
            if (isset($setting['telegram'])) {
                $this->importTelegramIntegration($setting['telegram']);
            }

        } catch (Exception $e) {
            $this->errors[] = "Settings: {$e->getMessage()}";
            $this->log("  ❌ Error: {$e->getMessage()}", 'error');
        }

        $this->log("  ✅ Settings imported", 'success');
    }

    private function importCalculatorConfig(array $calculator): void
    {
        // Import materials
        if (isset($calculator['materialPrices'])) {
            $order = 0;
            foreach ($calculator['materialPrices'] as $key => $material) {
                $order++;
                
                if ($this->options['dry-run']) {
                    $this->log("    [DRY RUN] Would insert material: {$material['name']}", 'info');
                } else {
                    $sql = "INSERT INTO materials (material_key, name, price, technology, active, display_order)
                            VALUES (:material_key, :name, :price, :technology, :active, :display_order)
                            ON DUPLICATE KEY UPDATE 
                                name = VALUES(name), 
                                price = VALUES(price), 
                                technology = VALUES(technology)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'material_key' => $key,
                        'name' => $material['name'],
                        'price' => $material['price'],
                        'technology' => $material['technology'],
                        'active' => 1,
                        'display_order' => $order
                    ]);
                }
                
                $this->stats['materials']++;
            }
        }

        // Import additional services
        if (isset($calculator['servicePrices'])) {
            $order = 0;
            foreach ($calculator['servicePrices'] as $key => $service) {
                $order++;
                
                if ($this->options['dry-run']) {
                    $this->log("    [DRY RUN] Would insert service: {$service['name']}", 'info');
                } else {
                    $sql = "INSERT INTO additional_services (service_key, name, price, unit, active, display_order)
                            VALUES (:service_key, :name, :price, :unit, :active, :display_order)
                            ON DUPLICATE KEY UPDATE 
                                name = VALUES(name), 
                                price = VALUES(price), 
                                unit = VALUES(unit)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'service_key' => $key,
                        'name' => $service['name'],
                        'price' => $service['price'],
                        'unit' => $service['unit'],
                        'active' => 1,
                        'display_order' => $order
                    ]);
                }
                
                $this->stats['additional_services']++;
            }
        }

        // Import quality levels
        if (isset($calculator['qualityMultipliers'])) {
            $order = 0;
            foreach ($calculator['qualityMultipliers'] as $key => $quality) {
                $order++;
                
                if ($this->options['dry-run']) {
                    $this->log("    [DRY RUN] Would insert quality level: {$quality['name']}", 'info');
                } else {
                    $sql = "INSERT INTO quality_levels (quality_key, name, price_multiplier, time_multiplier, active, display_order)
                            VALUES (:quality_key, :name, :price_multiplier, :time_multiplier, :active, :display_order)
                            ON DUPLICATE KEY UPDATE 
                                name = VALUES(name), 
                                price_multiplier = VALUES(price_multiplier), 
                                time_multiplier = VALUES(time_multiplier)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'quality_key' => $key,
                        'name' => $quality['name'],
                        'price_multiplier' => $quality['multiplier'],
                        'time_multiplier' => $quality['time'],
                        'active' => 1,
                        'display_order' => $order
                    ]);
                }
                
                $this->stats['quality_levels']++;
            }
        }

        // Import volume discounts
        if (isset($calculator['discounts'])) {
            foreach ($calculator['discounts'] as $discount) {
                if ($this->options['dry-run']) {
                    $this->log("    [DRY RUN] Would insert discount: {$discount['percent']}% at {$discount['minQuantity']} items", 'info');
                } else {
                    $sql = "INSERT INTO volume_discounts (min_quantity, discount_percent, active)
                            VALUES (:min_quantity, :discount_percent, :active)
                            ON DUPLICATE KEY UPDATE 
                                discount_percent = VALUES(discount_percent)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'min_quantity' => $discount['minQuantity'],
                        'discount_percent' => $discount['percent'],
                        'active' => 1
                    ]);
                }
                
                $this->stats['volume_discounts']++;
            }
        }
    }

    private function importFormFields(array $formFields): void
    {
        foreach ($formFields as $formType => $fields) {
            if (!is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if ($this->options['dry-run']) {
                    $this->log("    [DRY RUN] Would insert form field: {$field['label']}", 'info');
                } else {
                    $sql = "INSERT INTO form_fields (form_type, field_name, label, field_type, required, enabled, placeholder, display_order, options)
                            VALUES (:form_type, :field_name, :label, :field_type, :required, :enabled, :placeholder, :display_order, :options)
                            ON DUPLICATE KEY UPDATE 
                                label = VALUES(label), 
                                field_type = VALUES(field_type), 
                                required = VALUES(required), 
                                enabled = VALUES(enabled), 
                                placeholder = VALUES(placeholder), 
                                display_order = VALUES(display_order), 
                                options = VALUES(options)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'form_type' => $formType,
                        'field_name' => $field['name'],
                        'label' => $field['label'],
                        'field_type' => $field['type'],
                        'required' => $field['required'] ? 1 : 0,
                        'enabled' => $field['enabled'] ?? true ? 1 : 0,
                        'placeholder' => $field['placeholder'] ?? null,
                        'display_order' => $field['order'] ?? 0,
                        'options' => isset($field['options']) ? json_encode($field['options']) : null
                    ]);
                }
                
                $this->stats['form_fields']++;
            }
        }
    }

    private function importTelegramIntegration(array $telegram): void
    {
        if ($this->options['dry-run']) {
            $this->log("    [DRY RUN] Would insert/update Telegram integration", 'info');
        } else {
            $config = [
                'botToken' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? ($telegram['botToken'] ?? ''),
                'chatId' => $telegram['chatId'] ?? '',
                'apiUrl' => 'https://api.telegram.org/bot',
                'contactUrl' => $_ENV['TELEGRAM_CONTACT_URL'] ?? ($telegram['contactUrl'] ?? '')
            ];

            $sql = "INSERT INTO integrations (integration_name, enabled, config)
                    VALUES ('telegram', :enabled, :config)
                    ON DUPLICATE KEY UPDATE 
                        enabled = VALUES(enabled), 
                        config = VALUES(config)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'enabled' => !empty($config['botToken']) && !empty($config['chatId']) ? 1 : 0,
                'config' => json_encode($config)
            ]);
        }
        
        $this->stats['integrations']++;
    }

    private function importContent(array $content): void
    {
        $this->log("\n📄 Importing content...", 'info');

        // Content can be either array with single item or object
        $contentData = is_array($content) && isset($content[0]) ? $content[0] : $content;

        if (empty($contentData)) {
            $this->log("  ⚠️  No content data to import", 'warning');
            return;
        }

        try {
            foreach ($contentData as $sectionKey => $sectionData) {
                $data = [
                    'section_key' => $sectionKey,
                    'title' => $sectionData['title'] ?? null,
                    'content' => json_encode($sectionData)
                ];

                if ($this->options['dry-run']) {
                    $this->log("  [DRY RUN] Would insert/update content section: {$sectionKey}", 'info');
                } else {
                    $sql = "INSERT INTO site_content (section_key, title, content)
                            VALUES (:section_key, :title, :content)
                            ON DUPLICATE KEY UPDATE 
                                title = VALUES(title), 
                                content = VALUES(content)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($data);
                }

                $this->stats['content']++;
            }

        } catch (Exception $e) {
            $this->errors[] = "Content: {$e->getMessage()}";
            $this->log("  ❌ Error: {$e->getMessage()}", 'error');
        }

        $this->log("  ✅ Imported {$this->stats['content']} content sections", 'success');
    }

    private function importStats($stats): void
    {
        $this->log("\n📊 Importing stats...", 'info');

        if (empty($stats)) {
            $this->log("  ⚠️  No stats data to import", 'warning');
            return;
        }

        try {
            $data = [
                'total_projects' => $stats['totalProjects'] ?? 0,
                'happy_clients' => $stats['happyClients'] ?? 0,
                'years_experience' => $stats['yearsExperience'] ?? 0,
                'awards' => $stats['awards'] ?? 0
            ];

            if ($this->options['dry-run']) {
                $this->log("  [DRY RUN] Would insert/update stats", 'info');
            } else {
                // Check if stats exist
                $exists = $this->pdo->query("SELECT COUNT(*) FROM site_stats")->fetchColumn();
                
                if ($exists > 0 && !$this->options['force']) {
                    $this->log("  ⚠️  Stats already exist. Use --force to overwrite.", 'warning');
                } else {
                    // Clear existing stats if force mode
                    if ($exists > 0) {
                        $this->pdo->exec("DELETE FROM site_stats");
                    }

                    $sql = "INSERT INTO site_stats (total_projects, happy_clients, years_experience, awards)
                            VALUES (:total_projects, :happy_clients, :years_experience, :awards)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($data);
                }
            }

            $this->stats['stats']++;

        } catch (Exception $e) {
            $this->errors[] = "Stats: {$e->getMessage()}";
            $this->log("  ❌ Error: {$e->getMessage()}", 'error');
        }

        $this->log("  ✅ Stats imported", 'success');
    }

    private function importOrders(array $orders): void
    {
        $this->log("\n📝 Importing orders...", 'info');

        foreach ($orders as $order) {
            try {
                // Generate unique order number if not present
                $orderNumber = $order['orderNumber'] ?? $this->generateOrderNumber();

                $data = [
                    'order_number' => $orderNumber,
                    'type' => $order['type'] ?? 'contact',
                    'status' => $order['status'] ?? 'new',
                    'client_name' => $order['name'] ?? ($order['client_name'] ?? ''),
                    'client_email' => $order['email'] ?? ($order['client_email'] ?? ''),
                    'client_phone' => $order['phone'] ?? ($order['client_phone'] ?? ''),
                    'telegram' => $order['telegram'] ?? null,
                    'service' => $order['service'] ?? null,
                    'subject' => $order['subject'] ?? null,
                    'message' => $order['message'] ?? null,
                    'amount' => $order['amount'] ?? 0,
                    'calculator_data' => isset($order['calculator_data']) ? json_encode($order['calculator_data']) : null,
                    'telegram_sent' => $order['telegram_sent'] ?? false
                ];

                if ($this->options['dry-run']) {
                    $this->log("  [DRY RUN] Would insert order: {$orderNumber}", 'info');
                } else {
                    $sql = "INSERT INTO orders (order_number, type, status, client_name, client_email, client_phone, 
                            telegram, service, subject, message, amount, calculator_data, telegram_sent)
                            VALUES (:order_number, :type, :status, :client_name, :client_email, :client_phone, 
                            :telegram, :service, :subject, :message, :amount, :calculator_data, :telegram_sent)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'order_number' => $data['order_number'],
                        'type' => $data['type'],
                        'status' => $data['status'],
                        'client_name' => $data['client_name'],
                        'client_email' => $data['client_email'],
                        'client_phone' => $data['client_phone'],
                        'telegram' => $data['telegram'],
                        'service' => $data['service'],
                        'subject' => $data['subject'],
                        'message' => $data['message'],
                        'amount' => $data['amount'],
                        'calculator_data' => $data['calculator_data'],
                        'telegram_sent' => $data['telegram_sent'] ? 1 : 0
                    ]);
                }

                $this->stats['orders']++;

            } catch (Exception $e) {
                $this->errors[] = "Order '{$order['orderNumber'] ?? 'unknown'}': {$e->getMessage()}";
                $this->log("  ❌ Error: {$e->getMessage()}", 'error');
            }
        }

        $this->log("  ✅ Imported {$this->stats['orders']} orders", 'success');
    }

    private function shouldSkip(string $table): bool
    {
        return isset($this->options["skip-{$table}"]) && $this->options["skip-{$table}"];
    }

    private function generateSlug(string $text): string
    {
        // Transliterate Russian to Latin
        $transliteration = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
        ];

        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, $transliteration);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }

    private function mapPortfolioCategory(string $category): string
    {
        $mapping = [
            'prototype' => 'prototype',
            'functional' => 'functional',
            'art' => 'art',
            'industrial' => 'industrial'
        ];

        return $mapping[$category] ?? 'prototype';
    }

    private function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "ORD-{$date}-{$random}";
    }

    private function log(string $message, string $level = 'info'): void
    {
        if (!$this->options['verbose'] && $level === 'info' && strpos($message, '[DRY RUN]') === false) {
            return;
        }

        $colors = [
            'info' => "\033[0m",      // Default
            'success' => "\033[32m",  // Green
            'warning' => "\033[33m",  // Yellow
            'error' => "\033[31m"     // Red
        ];

        $color = $colors[$level] ?? $colors['info'];
        $reset = "\033[0m";

        echo "{$color}{$message}{$reset}\n";
    }

    private function printSummary(): void
    {
        $this->log("\n" . str_repeat("=", 60), 'info');
        $this->log("📊 IMPORT SUMMARY", 'info');
        $this->log(str_repeat("=", 60), 'info');

        $total = 0;
        foreach ($this->stats as $table => $count) {
            if ($count > 0) {
                $this->log(sprintf("  %-25s %5d", ucfirst(str_replace('_', ' ', $table)) . ':', $count), 'success');
                $total += $count;
            }
        }

        $this->log(str_repeat("-", 60), 'info');
        $this->log(sprintf("  %-25s %5d", 'Total records:', $total), 'success');
        $this->log(str_repeat("=", 60), 'info');

        if (!empty($this->errors)) {
            $this->log("\n⚠️  ERRORS ({count}):", 'warning');
            foreach ($this->errors as $error) {
                $this->log("  • {$error}", 'error');
            }
        }

        if ($this->options['dry-run']) {
            $this->log("\n💡 This was a dry run. No data was actually imported.", 'warning');
            $this->log("   Run without --dry-run to perform the actual import.", 'info');
        }
    }
}

// ========================================
// CLI EXECUTION
// ========================================

function parseArgs(): array
{
    global $argv;
    
    $options = [
        'file' => null,
        'dry-run' => false,
        'skip-orders' => false,
        'skip-portfolio' => false,
        'skip-services' => false,
        'skip-testimonials' => false,
        'skip-faq' => false,
        'skip-settings' => false,
        'skip-content' => false,
        'skip-stats' => false,
        'force' => false,
        'verbose' => false,
        'help' => false
    ];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        
        if (strpos($arg, '--file=') === 0) {
            $options['file'] = substr($arg, 7);
        } elseif ($arg === '--dry-run') {
            $options['dry-run'] = true;
        } elseif ($arg === '--skip-orders') {
            $options['skip-orders'] = true;
        } elseif ($arg === '--skip-portfolio') {
            $options['skip-portfolio'] = true;
        } elseif ($arg === '--skip-services') {
            $options['skip-services'] = true;
        } elseif ($arg === '--skip-testimonials') {
            $options['skip-testimonials'] = true;
        } elseif ($arg === '--skip-faq') {
            $options['skip-faq'] = true;
        } elseif ($arg === '--skip-settings') {
            $options['skip-settings'] = true;
        } elseif ($arg === '--skip-content') {
            $options['skip-content'] = true;
        } elseif ($arg === '--skip-stats') {
            $options['skip-stats'] = true;
        } elseif ($arg === '--force') {
            $options['force'] = true;
        } elseif ($arg === '--verbose') {
            $options['verbose'] = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        }
    }

    return $options;
}

function showHelp(): void
{
    $help = <<<HELP

========================================
3D Print Pro - Data Migration Importer
========================================

Imports localStorage export JSON into MySQL database

Usage:
  php import_local_data.php --file=export.json [options]

Options:
  --file=<path>          Path to JSON export file (required)
  --dry-run              Show what would be imported without making changes
  --skip-orders          Skip importing orders table
  --skip-portfolio       Skip importing portfolio table
  --skip-services        Skip importing services table
  --skip-testimonials    Skip importing testimonials table
  --skip-faq             Skip importing FAQ table
  --skip-settings        Skip importing settings table
  --skip-content         Skip importing content table
  --skip-stats           Skip importing stats table
  --force                Overwrite existing data (use with caution)
  --verbose              Show detailed import progress
  --help, -h             Show this help message

Examples:
  # Dry run to see what would be imported
  php import_local_data.php --file=export.json --dry-run

  # Import everything except orders
  php import_local_data.php --file=export.json --skip-orders

  # Force import with verbose output
  php import_local_data.php --file=export.json --force --verbose

  # Import only services and portfolio
  php import_local_data.php --file=export.json \\
    --skip-orders --skip-testimonials --skip-faq \\
    --skip-settings --skip-content --skip-stats

Notes:
  - Always run with --dry-run first to verify data
  - Existing data will not be overwritten unless --force is used
  - Calculator config (materials, services, quality, discounts) is always merged
  - Timestamps are automatically generated
  - Service features are imported as separate records

HELP;

    echo $help;
}

// Main execution
try {
    $options = parseArgs();

    if ($options['help']) {
        showHelp();
        exit(0);
    }

    if (empty($options['file'])) {
        echo "❌ Error: --file parameter is required\n";
        echo "Run with --help for usage information\n";
        exit(1);
    }

    if (!file_exists($options['file'])) {
        echo "❌ Error: File not found: {$options['file']}\n";
        exit(1);
    }

    // Read and parse JSON file
    $jsonContent = file_get_contents($options['file']);
    $data = json_decode($jsonContent, true);

    if ($data === null) {
        echo "❌ Error: Invalid JSON file\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
        exit(1);
    }

    // Create importer and run
    $importer = new DataImporter($options);
    $success = $importer->import($data);

    exit($success ? 0 : 1);

} catch (Exception $e) {
    echo "\n❌ Fatal error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
