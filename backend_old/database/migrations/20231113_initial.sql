-- ========================================
-- 3D Print Pro - Initial Database Schema
-- ========================================
-- MySQL 8.0+ required
-- Database: ch167436_3dprint
-- Character Set: utf8mb4 (full Unicode support including emojis)
-- Collation: utf8mb4_unicode_ci
-- ========================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ch167436_3dprint 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE ch167436_3dprint;

-- ========================================
-- 1. USERS TABLE (Admin Authentication)
-- ========================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'manager', 'user') NOT NULL DEFAULT 'user',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_login (login),
    INDEX idx_email (email),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User accounts for admin panel authentication';

-- ========================================
-- 2. SERVICES TABLE
-- ========================================
CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) NOT NULL COMMENT 'FontAwesome class, e.g., fa-cube',
    description TEXT NOT NULL,
    price VARCHAR(50) NOT NULL COMMENT 'Display text, e.g., "от 50₽/г"',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    featured BOOLEAN NOT NULL DEFAULT FALSE,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_active (active),
    INDEX idx_featured (featured),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Service catalog displayed on main site';

-- ========================================
-- 3. SERVICE_FEATURES TABLE
-- ========================================
CREATE TABLE service_features (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT UNSIGNED NOT NULL,
    feature_text VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_service_order (service_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Features/benefits for each service';

-- ========================================
-- 4. PORTFOLIO TABLE
-- ========================================
CREATE TABLE portfolio (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category ENUM('prototype', 'functional', 'art', 'industrial') NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(500) NOT NULL COMMENT 'CDN or relative path',
    details TEXT NULL COMMENT 'Extended project details',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Portfolio of completed projects';

-- ========================================
-- 5. TESTIMONIALS TABLE
-- ========================================
CREATE TABLE testimonials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL COMMENT 'Job title or company',
    avatar_url VARCHAR(500) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5 CHECK (rating BETWEEN 1 AND 5),
    text TEXT NOT NULL,
    approved BOOLEAN NOT NULL DEFAULT FALSE,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_approved (approved),
    INDEX idx_rating (rating),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer testimonials and reviews';

-- ========================================
-- 6. FAQ TABLE
-- ========================================
CREATE TABLE faq (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Frequently Asked Questions';

-- ========================================
-- 7. ORDERS TABLE
-- ========================================
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('order', 'contact') NOT NULL DEFAULT 'contact',
    status ENUM('new', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
    
    -- Client information
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(255) NOT NULL,
    client_phone VARCHAR(30) NOT NULL,
    telegram VARCHAR(100) NULL,
    
    -- Order details
    service VARCHAR(255) NULL COMMENT 'Service name or type',
    subject VARCHAR(255) NULL COMMENT 'Contact form subject',
    message TEXT NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total amount in rubles',
    
    -- Calculator data (stored as JSON for flexibility)
    calculator_data JSON NULL COMMENT 'Full calculator results and configuration',
    
    -- Integration status
    telegram_sent BOOLEAN NOT NULL DEFAULT FALSE,
    telegram_sent_at TIMESTAMP NULL,
    
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order_number (order_number),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_client_email (client_email),
    INDEX idx_created_at (created_at DESC),
    INDEX idx_status_created (status, created_at DESC),
    FULLTEXT idx_search (client_name, client_email, message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer orders and contact form submissions';

-- ========================================
-- 8. MATERIALS TABLE (Calculator Configuration)
-- ========================================
CREATE TABLE materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identifier like pla, abs, petg',
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL COMMENT 'Price per gram in rubles',
    technology ENUM('fdm', 'sla', 'sls') NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_material_key (material_key),
    INDEX idx_technology (technology),
    INDEX idx_active (active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='3D printing materials and pricing';

-- ========================================
-- 9. ADDITIONAL_SERVICES TABLE (Calculator Configuration)
-- ========================================
CREATE TABLE additional_services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identifier like modeling, postProcessing',
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(20) NOT NULL COMMENT 'час, шт, заказ',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_service_key (service_key),
    INDEX idx_active (active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Additional services for calculator';

-- ========================================
-- 10. QUALITY_LEVELS TABLE (Calculator Configuration)
-- ========================================
CREATE TABLE quality_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identifier like draft, normal, high, ultra',
    name VARCHAR(100) NOT NULL,
    price_multiplier DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    time_multiplier DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_quality_key (quality_key),
    INDEX idx_active (active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Quality levels with pricing multipliers';

-- ========================================
-- 11. VOLUME_DISCOUNTS TABLE (Calculator Configuration)
-- ========================================
CREATE TABLE volume_discounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    min_quantity INT UNSIGNED NOT NULL,
    discount_percent DECIMAL(5, 2) NOT NULL COMMENT 'Discount percentage',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_min_quantity (min_quantity),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Volume-based discount tiers';

-- ========================================
-- 12. FORM_FIELDS TABLE
-- ========================================
CREATE TABLE form_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_type ENUM('contact', 'order') NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'email', 'tel', 'textarea', 'select', 'checkbox', 'file', 'number', 'url', 'date') NOT NULL,
    required BOOLEAN NOT NULL DEFAULT FALSE,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    placeholder VARCHAR(255) NULL,
    display_order INT NOT NULL DEFAULT 0,
    options JSON NULL COMMENT 'Array of options for select fields',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY idx_form_field (form_type, field_name),
    INDEX idx_form_type (form_type),
    INDEX idx_enabled (enabled),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dynamic form field configuration';

-- ========================================
-- 13. SITE_SETTINGS TABLE (Singleton)
-- ========================================
CREATE TABLE site_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(255) NOT NULL,
    site_description TEXT NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(30) NOT NULL,
    address TEXT NOT NULL,
    working_hours TEXT NOT NULL,
    timezone VARCHAR(50) NOT NULL DEFAULT 'Europe/Moscow',
    
    -- Social links (JSON)
    social_links JSON NULL COMMENT 'Object with vk, telegram, whatsapp, youtube URLs',
    
    -- Theme settings
    theme VARCHAR(20) NOT NULL DEFAULT 'light',
    color_primary VARCHAR(7) NOT NULL DEFAULT '#6366f1',
    color_secondary VARCHAR(7) NOT NULL DEFAULT '#ec4899',
    
    -- Notification preferences
    notifications JSON NULL COMMENT 'Object with newOrders, newReviews, newMessages flags',
    
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Global site settings (singleton - should have only one row)';

-- ========================================
-- 14. INTEGRATIONS TABLE
-- ========================================
CREATE TABLE integrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'telegram, email, sms, etc.',
    enabled BOOLEAN NOT NULL DEFAULT FALSE,
    config JSON NOT NULL COMMENT 'Integration-specific configuration',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_integration_name (integration_name),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='External integration settings (Telegram, email services, etc.)';

-- ========================================
-- 15. SITE_CONTENT TABLE (Singleton)
-- ========================================
CREATE TABLE site_content (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'hero, about, etc.',
    title VARCHAR(255) NULL,
    content JSON NOT NULL COMMENT 'Section-specific content structure',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_section_key (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Editable site content sections';

-- ========================================
-- 16. SITE_STATS TABLE (Singleton)
-- ========================================
CREATE TABLE site_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    total_projects INT UNSIGNED NOT NULL DEFAULT 0,
    happy_clients INT UNSIGNED NOT NULL DEFAULT 0,
    years_experience INT UNSIGNED NOT NULL DEFAULT 0,
    awards INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Site statistics (singleton - should have only one row)';

-- ========================================
-- 17. AUDIT_LOGS TABLE (Optional - for tracking changes)
-- ========================================
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(50) NOT NULL COMMENT 'orders, services, settings, etc.',
    entity_id BIGINT UNSIGNED NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    field_name VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for tracking changes to critical data';

-- ========================================
-- DATABASE SETUP COMPLETE
-- ========================================
