#!/usr/bin/env php
<?php

/**
 * ========================================
 * Export File Validation Script
 * ========================================
 * 
 * Validates JSON export files before import
 * Checks structure, required fields, data types
 * 
 * Usage:
 *   php validate-export.php <export-file.json>
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// ========================================
// CLI Arguments
// ========================================

if ($argc < 2) {
    echo "Usage: php validate-export.php <export-file.json>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "❌ Error: File not found: {$filePath}\n";
    exit(1);
}

// ========================================
// Validation Class
// ========================================

class ExportValidator
{
    private array $data;
    private array $errors = [];
    private array $warnings = [];
    private array $stats = [];

    public function __construct(string $jsonContent)
    {
        $this->data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }
    }

    public function validate(): bool
    {
        $this->log("🔍 Validating export file structure...\n", 'info');

        // Validate main structure
        $this->validateStructure();

        // Validate each table
        if (isset($this->data['services'])) {
            $this->validateServices($this->data['services']);
        }

        if (isset($this->data['portfolio'])) {
            $this->validatePortfolio($this->data['portfolio']);
        }

        if (isset($this->data['testimonials'])) {
            $this->validateTestimonials($this->data['testimonials']);
        }

        if (isset($this->data['faq'])) {
            $this->validateFaq($this->data['faq']);
        }

        if (isset($this->data['settings'])) {
            $this->validateSettings($this->data['settings']);
        }

        if (isset($this->data['content'])) {
            $this->validateContent($this->data['content']);
        }

        if (isset($this->data['stats'])) {
            $this->validateStats($this->data['stats']);
        }

        if (isset($this->data['orders'])) {
            $this->validateOrders($this->data['orders']);
        }

        // Print results
        $this->printResults();

        return count($this->errors) === 0;
    }

    private function validateStructure(): void
    {
        $expectedTables = ['services', 'portfolio', 'testimonials', 'faq', 'settings', 'content', 'stats', 'orders'];
        
        foreach ($expectedTables as $table) {
            if (!isset($this->data[$table])) {
                $this->warnings[] = "Missing optional table: {$table}";
            }
        }

        if (empty($this->data)) {
            $this->errors[] = "Export file is empty";
        }
    }

    private function validateServices(array $services): void
    {
        $this->log("📦 Validating services...", 'info');
        
        if (!is_array($services)) {
            $this->errors[] = "Services must be an array";
            return;
        }

        $this->stats['services'] = count($services);

        foreach ($services as $index => $service) {
            $context = "Service #{$index}";
            
            $this->validateRequired($service, 'name', $context);
            $this->validateRequired($service, 'icon', $context);
            $this->validateRequired($service, 'description', $context);
            $this->validateRequired($service, 'price', $context);

            if (isset($service['features'])) {
                if (!is_array($service['features'])) {
                    $this->errors[] = "{$context}: Features must be an array";
                } else {
                    $this->stats['service_features'] = ($this->stats['service_features'] ?? 0) + count($service['features']);
                }
            }
        }

        $this->log("  ✅ {$this->stats['services']} services validated", 'success');
    }

    private function validatePortfolio(array $portfolio): void
    {
        $this->log("🖼️  Validating portfolio...", 'info');
        
        if (!is_array($portfolio)) {
            $this->errors[] = "Portfolio must be an array";
            return;
        }

        $this->stats['portfolio'] = count($portfolio);
        $validCategories = ['prototype', 'functional', 'art', 'industrial'];

        foreach ($portfolio as $index => $item) {
            $context = "Portfolio #{$index}";
            
            $this->validateRequired($item, 'title', $context);
            $this->validateRequired($item, 'description', $context);
            
            if (isset($item['image']) || isset($item['image_url'])) {
                // OK - at least one image field present
            } else {
                $this->warnings[] = "{$context}: Missing image/image_url field";
            }

            if (isset($item['category']) && !in_array($item['category'], $validCategories)) {
                $this->errors[] = "{$context}: Invalid category '{$item['category']}'. Must be: " . implode(', ', $validCategories);
            }
        }

        $this->log("  ✅ {$this->stats['portfolio']} portfolio items validated", 'success');
    }

    private function validateTestimonials(array $testimonials): void
    {
        $this->log("💬 Validating testimonials...", 'info');
        
        if (!is_array($testimonials)) {
            $this->errors[] = "Testimonials must be an array";
            return;
        }

        $this->stats['testimonials'] = count($testimonials);

        foreach ($testimonials as $index => $testimonial) {
            $context = "Testimonial #{$index}";
            
            $this->validateRequired($testimonial, 'name', $context);
            $this->validateRequired($testimonial, 'position', $context);
            $this->validateRequired($testimonial, 'text', $context);

            if (isset($testimonial['rating'])) {
                if (!is_numeric($testimonial['rating']) || $testimonial['rating'] < 1 || $testimonial['rating'] > 5) {
                    $this->errors[] = "{$context}: Rating must be between 1 and 5";
                }
            }
        }

        $this->log("  ✅ {$this->stats['testimonials']} testimonials validated", 'success');
    }

    private function validateFaq(array $faq): void
    {
        $this->log("❓ Validating FAQ...", 'info');
        
        if (!is_array($faq)) {
            $this->errors[] = "FAQ must be an array";
            return;
        }

        $this->stats['faq'] = count($faq);

        foreach ($faq as $index => $item) {
            $context = "FAQ #{$index}";
            
            $this->validateRequired($item, 'question', $context);
            $this->validateRequired($item, 'answer', $context);
        }

        $this->log("  ✅ {$this->stats['faq']} FAQ items validated", 'success');
    }

    private function validateSettings($settings): void
    {
        $this->log("⚙️  Validating settings...", 'info');
        
        // Settings can be array with single item or object
        $setting = is_array($settings) && isset($settings[0]) ? $settings[0] : $settings;

        if (empty($setting)) {
            $this->warnings[] = "Settings is empty";
            return;
        }

        $this->stats['settings'] = 1;

        // Check calculator config
        if (isset($setting['calculator'])) {
            $calc = $setting['calculator'];
            
            if (isset($calc['materialPrices'])) {
                $count = is_array($calc['materialPrices']) ? count($calc['materialPrices']) : 0;
                $this->stats['materials'] = $count;
            }

            if (isset($calc['servicePrices'])) {
                $count = is_array($calc['servicePrices']) ? count($calc['servicePrices']) : 0;
                $this->stats['additional_services'] = $count;
            }

            if (isset($calc['qualityMultipliers'])) {
                $count = is_array($calc['qualityMultipliers']) ? count($calc['qualityMultipliers']) : 0;
                $this->stats['quality_levels'] = $count;
            }

            if (isset($calc['discounts'])) {
                $count = is_array($calc['discounts']) ? count($calc['discounts']) : 0;
                $this->stats['volume_discounts'] = $count;
            }
        }

        // Check form fields
        if (isset($setting['formFields'])) {
            $totalFields = 0;
            foreach ($setting['formFields'] as $formType => $fields) {
                if (is_array($fields)) {
                    $totalFields += count($fields);
                }
            }
            $this->stats['form_fields'] = $totalFields;
        }

        $this->log("  ✅ Settings validated", 'success');
    }

    private function validateContent($content): void
    {
        $this->log("📄 Validating content...", 'info');
        
        // Content can be array with single item or object
        $contentData = is_array($content) && isset($content[0]) ? $content[0] : $content;

        if (empty($contentData)) {
            $this->warnings[] = "Content is empty";
            return;
        }

        $this->stats['content'] = is_array($contentData) ? count($contentData) : 0;

        $this->log("  ✅ {$this->stats['content']} content sections validated", 'success');
    }

    private function validateStats($stats): void
    {
        $this->log("📊 Validating stats...", 'info');
        
        if (empty($stats)) {
            $this->warnings[] = "Stats is empty";
            return;
        }

        $this->stats['stats'] = 1;

        $requiredFields = ['totalProjects', 'happyClients', 'yearsExperience', 'awards'];
        foreach ($requiredFields as $field) {
            if (!isset($stats[$field])) {
                $this->warnings[] = "Stats: Missing recommended field '{$field}'";
            } elseif (!is_numeric($stats[$field]) || $stats[$field] < 0) {
                $this->errors[] = "Stats: Field '{$field}' must be a positive number";
            }
        }

        $this->log("  ✅ Stats validated", 'success');
    }

    private function validateOrders(array $orders): void
    {
        $this->log("📝 Validating orders...", 'info');
        
        if (!is_array($orders)) {
            $this->errors[] = "Orders must be an array";
            return;
        }

        $this->stats['orders'] = count($orders);

        foreach ($orders as $index => $order) {
            $context = "Order #{$index}";
            
            // Check for either old or new field names
            $hasName = isset($order['name']) || isset($order['client_name']);
            $hasEmail = isset($order['email']) || isset($order['client_email']);
            $hasPhone = isset($order['phone']) || isset($order['client_phone']);

            if (!$hasName) {
                $this->errors[] = "{$context}: Missing 'name' or 'client_name' field";
            }
            if (!$hasEmail) {
                $this->errors[] = "{$context}: Missing 'email' or 'client_email' field";
            }
            if (!$hasPhone) {
                $this->errors[] = "{$context}: Missing 'phone' or 'client_phone' field";
            }

            if (isset($order['type']) && !in_array($order['type'], ['order', 'contact'])) {
                $this->errors[] = "{$context}: Invalid type '{$order['type']}'. Must be 'order' or 'contact'";
            }

            if (isset($order['status']) && !in_array($order['status'], ['new', 'processing', 'completed', 'cancelled'])) {
                $this->errors[] = "{$context}: Invalid status '{$order['status']}'";
            }
        }

        $this->log("  ✅ {$this->stats['orders']} orders validated", 'success');
    }

    private function validateRequired(array $data, string $field, string $context): void
    {
        if (!isset($data[$field]) || empty($data[$field])) {
            $this->errors[] = "{$context}: Missing required field '{$field}'";
        }
    }

    private function log(string $message, string $level = 'info'): void
    {
        $colors = [
            'info' => "\033[0m",
            'success' => "\033[32m",
            'warning' => "\033[33m",
            'error' => "\033[31m"
        ];

        $color = $colors[$level] ?? $colors['info'];
        $reset = "\033[0m";

        echo "{$color}{$message}{$reset}\n";
    }

    private function printResults(): void
    {
        $this->log("\n" . str_repeat("=", 60), 'info');
        $this->log("📊 VALIDATION RESULTS", 'info');
        $this->log(str_repeat("=", 60), 'info');

        // Print stats
        if (!empty($this->stats)) {
            $this->log("\nRecords found:", 'info');
            foreach ($this->stats as $table => $count) {
                $this->log(sprintf("  %-25s %5d", ucfirst(str_replace('_', ' ', $table)) . ':', $count), 'info');
            }
        }

        // Print warnings
        if (!empty($this->warnings)) {
            $this->log("\n⚠️  WARNINGS (" . count($this->warnings) . "):", 'warning');
            foreach ($this->warnings as $warning) {
                $this->log("  • {$warning}", 'warning');
            }
        }

        // Print errors
        if (!empty($this->errors)) {
            $this->log("\n❌ ERRORS (" . count($this->errors) . "):", 'error');
            foreach ($this->errors as $error) {
                $this->log("  • {$error}", 'error');
            }
        }

        $this->log("\n" . str_repeat("=", 60), 'info');
        
        if (empty($this->errors)) {
            $this->log("✅ Validation passed! File is ready for import.", 'success');
        } else {
            $this->log("❌ Validation failed. Please fix errors before importing.", 'error');
        }
        
        $this->log(str_repeat("=", 60), 'info');
    }
}

// ========================================
// Main Execution
// ========================================

try {
    echo "🔍 Loading export file: {$filePath}\n\n";
    
    $jsonContent = file_get_contents($filePath);
    
    if ($jsonContent === false) {
        throw new Exception("Failed to read file: {$filePath}");
    }

    $validator = new ExportValidator($jsonContent);
    $success = $validator->validate();

    exit($success ? 0 : 1);

} catch (Exception $e) {
    echo "\n❌ Fatal error: {$e->getMessage()}\n";
    exit(1);
}
