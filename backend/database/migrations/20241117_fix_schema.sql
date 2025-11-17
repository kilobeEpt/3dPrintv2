-- ========================================
-- Fix Schema for Missing Columns
-- ========================================
-- This migration adds columns that endpoints expect but are missing

USE ch167436_3dprint;

-- Add calculator_config, form_config, telegram_config, general_config to site_settings
ALTER TABLE site_settings 
ADD COLUMN IF NOT EXISTS calculator_config JSON NULL COMMENT 'Calculator configuration',
ADD COLUMN IF NOT EXISTS form_config JSON NULL COMMENT 'Form configuration', 
ADD COLUMN IF NOT EXISTS telegram_config JSON NULL COMMENT 'Telegram configuration',
ADD COLUMN IF NOT EXISTS general_config JSON NULL COMMENT 'General configuration';

-- Initialize with empty JSON objects if null
UPDATE site_settings 
SET 
    calculator_config = COALESCE(calculator_config, '{}'),
    form_config = COALESCE(form_config, '{}'),
    telegram_config = COALESCE(telegram_config, '{}'),
    general_config = COALESCE(general_config, '{}')
WHERE id > 0;
