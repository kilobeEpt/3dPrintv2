<?php

namespace App\Services;

use App\Repositories\SettingsRepository;
use App\Helpers\Validator;

class SettingsService
{
    private SettingsRepository $repository;

    public function __construct()
    {
        $this->repository = new SettingsRepository();
    }

    public function getPublicSettings(): array
    {
        $settings = $this->repository->getSiteSettings();
        $materials = $this->repository->getAllMaterials();
        $additionalServices = $this->repository->getAllAdditionalServices();
        $qualityLevels = $this->repository->getAllQualityLevels();
        $volumeDiscounts = $this->repository->getAllVolumeDiscounts();
        $formFields = $this->repository->getAllFormFields();
        $telegramIntegration = $this->repository->getIntegration('telegram');

        $publicSettings = [
            'site' => [
                'name' => $settings['site_name'] ?? '3D Print Pro',
                'description' => $settings['site_description'] ?? '',
                'contact' => [
                    'email' => $settings['contact_email'] ?? '',
                    'phone' => $settings['contact_phone'] ?? '',
                    'address' => $settings['address'] ?? '',
                    'working_hours' => $settings['working_hours'] ?? ''
                ],
                'social_links' => $settings['social_links'] ?? [],
                'theme' => [
                    'mode' => $settings['theme'] ?? 'light',
                    'color_primary' => $settings['color_primary'] ?? '#6366f1',
                    'color_secondary' => $settings['color_secondary'] ?? '#ec4899'
                ]
            ],
            'calculator' => [
                'materials' => $this->formatMaterialsForPublic($materials),
                'additional_services' => $this->formatAdditionalServicesForPublic($additionalServices),
                'quality_levels' => $this->formatQualityLevelsForPublic($qualityLevels),
                'volume_discounts' => $this->formatVolumeDiscountsForPublic($volumeDiscounts)
            ],
            'forms' => $this->formatFormFieldsForPublic($formFields),
            'integrations' => [
                'telegram' => [
                    'enabled' => $telegramIntegration['enabled'] ?? false,
                    'contact_url' => $telegramIntegration['config']['contactUrl'] ?? ''
                ]
            ]
        ];

        return $publicSettings;
    }

    public function getAdminSettings(): array
    {
        $settings = $this->repository->getSiteSettings();
        $materials = $this->repository->getAllMaterials();
        $additionalServices = $this->repository->getAllAdditionalServices();
        $qualityLevels = $this->repository->getAllQualityLevels();
        $volumeDiscounts = $this->repository->getAllVolumeDiscounts();
        $formFields = $this->repository->getAllFormFields();
        $telegramIntegration = $this->repository->getIntegration('telegram');

        $adminSettings = [
            'site' => [
                'name' => $settings['site_name'] ?? '3D Print Pro',
                'description' => $settings['site_description'] ?? '',
                'contact' => [
                    'email' => $settings['contact_email'] ?? '',
                    'phone' => $settings['contact_phone'] ?? '',
                    'address' => $settings['address'] ?? '',
                    'working_hours' => $settings['working_hours'] ?? ''
                ],
                'social_links' => $settings['social_links'] ?? [],
                'theme' => [
                    'mode' => $settings['theme'] ?? 'light',
                    'color_primary' => $settings['color_primary'] ?? '#6366f1',
                    'color_secondary' => $settings['color_secondary'] ?? '#ec4899'
                ],
                'notifications' => $settings['notifications'] ?? [],
                'timezone' => $settings['timezone'] ?? 'Europe/Moscow'
            ],
            'calculator' => [
                'materials' => $materials,
                'additional_services' => $additionalServices,
                'quality_levels' => $qualityLevels,
                'volume_discounts' => $volumeDiscounts
            ],
            'forms' => $formFields,
            'integrations' => [
                'telegram' => [
                    'enabled' => $telegramIntegration['enabled'] ?? false,
                    'bot_token' => $this->redactToken($telegramIntegration['config']['botToken'] ?? ''),
                    'chat_id' => $telegramIntegration['config']['chatId'] ?? '',
                    'api_url' => $telegramIntegration['config']['apiUrl'] ?? '',
                    'contact_url' => $telegramIntegration['config']['contactUrl'] ?? ''
                ]
            ]
        ];

        return $adminSettings;
    }

    public function updateGeneralSettings(array $data): array
    {
        $validator = new Validator();
        
        $rules = [
            'site_name' => 'string|min:1|max:255',
            'site_description' => 'string',
            'contact_email' => 'email',
            'contact_phone' => 'string|max:30',
            'address' => 'string',
            'working_hours' => 'string',
            'timezone' => 'string|max:50',
            'social_links' => 'array',
            'theme' => 'in:light,dark',
            'color_primary' => 'string|min:4|max:7',
            'color_secondary' => 'string|min:4|max:7',
            'notifications' => 'array'
        ];
        
        if (!$validator->validate($data, $rules)) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        if (isset($data['social_links'])) {
            foreach ($data['social_links'] as $key => $url) {
                if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                    return ['success' => false, 'errors' => ['social_links' => "Invalid URL for {$key}"]];
                }
            }
        }

        $this->repository->updateSiteSettings($data);
        
        return ['success' => true];
    }

    public function updateCalculatorSettings(array $data): array
    {
        $validator = new Validator();
        $errors = [];

        if (isset($data['materials']) && is_array($data['materials'])) {
            foreach ($data['materials'] as $index => $material) {
                $materialRules = [
                    'material_key' => 'required|string|max:50',
                    'name' => 'required|string|max:100',
                    'price' => 'required|numeric|min:0',
                    'technology' => 'required|in:fdm,sla,sls',
                    'active' => 'boolean',
                    'display_order' => 'integer'
                ];
                
                if (!$validator->validate($material, $materialRules)) {
                    $errors["materials[{$index}]"] = $validator->getErrors();
                }
            }
        }

        if (isset($data['additional_services']) && is_array($data['additional_services'])) {
            foreach ($data['additional_services'] as $index => $service) {
                $serviceRules = [
                    'service_key' => 'required|string|max:50',
                    'name' => 'required|string|max:100',
                    'price' => 'required|numeric|min:0',
                    'unit' => 'required|string|max:20',
                    'active' => 'boolean',
                    'display_order' => 'integer'
                ];
                
                if (!$validator->validate($service, $serviceRules)) {
                    $errors["additional_services[{$index}]"] = $validator->getErrors();
                }
            }
        }

        if (isset($data['quality_levels']) && is_array($data['quality_levels'])) {
            foreach ($data['quality_levels'] as $index => $quality) {
                $qualityRules = [
                    'quality_key' => 'required|string|max:50',
                    'name' => 'required|string|max:100',
                    'price_multiplier' => 'required|numeric|min:0.01',
                    'time_multiplier' => 'required|numeric|min:0.01',
                    'active' => 'boolean',
                    'display_order' => 'integer'
                ];
                
                if (!$validator->validate($quality, $qualityRules)) {
                    $errors["quality_levels[{$index}]"] = $validator->getErrors();
                }
            }
        }

        if (isset($data['volume_discounts']) && is_array($data['volume_discounts'])) {
            foreach ($data['volume_discounts'] as $index => $discount) {
                $discountRules = [
                    'min_quantity' => 'required|integer|min:1',
                    'discount_percent' => 'required|numeric|min:0|max:100',
                    'active' => 'boolean'
                ];
                
                if (!$validator->validate($discount, $discountRules)) {
                    $errors["volume_discounts[{$index}]"] = $validator->getErrors();
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true];
    }

    public function updateFormSettings(array $data): array
    {
        $validator = new Validator();
        $errors = [];

        if (!isset($data['fields']) || !is_array($data['fields'])) {
            return ['success' => false, 'errors' => ['fields' => 'Fields array is required']];
        }

        foreach ($data['fields'] as $index => $field) {
            $fieldRules = [
                'form_type' => 'required|in:contact,order',
                'field_name' => 'required|string|max:50',
                'label' => 'required|string|max:100',
                'field_type' => 'required|in:text,email,tel,textarea,select,checkbox,file,number,url,date',
                'required' => 'boolean',
                'enabled' => 'boolean',
                'placeholder' => 'string|max:255',
                'display_order' => 'integer',
                'options' => 'array'
            ];
            
            if (!$validator->validate($field, $fieldRules)) {
                $errors["fields[{$index}]"] = $validator->getErrors();
                continue;
            }

            if (isset($field['options'])) {
                if (!is_array($field['options'])) {
                    $errors["fields[{$index}][options]"] = 'Options must be an array';
                } else {
                    foreach ($field['options'] as $option) {
                        if (!is_string($option)) {
                            $errors["fields[{$index}][options]"] = 'All options must be strings';
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true];
    }

    public function updateTelegramSettings(array $data): array
    {
        $validator = new Validator();
        
        $rules = [
            'enabled' => 'boolean',
            'bot_token' => 'string',
            'chat_id' => 'string',
            'api_url' => 'url',
            'contact_url' => 'url'
        ];
        
        if (!$validator->validate($data, $rules)) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        if (isset($data['bot_token']) && !empty($data['bot_token'])) {
            if (!preg_match('/^\d+:[A-Za-z0-9_-]+$/', $data['bot_token'])) {
                return ['success' => false, 'errors' => ['bot_token' => 'Invalid Telegram bot token format']];
            }
        }

        $config = [
            'botToken' => $data['bot_token'] ?? '',
            'chatId' => $data['chat_id'] ?? '',
            'apiUrl' => $data['api_url'] ?? 'https://api.telegram.org/bot',
            'contactUrl' => $data['contact_url'] ?? ''
        ];

        $this->repository->updateIntegration('telegram', $data['enabled'] ?? false, $config);
        
        return ['success' => true];
    }

    private function formatMaterialsForPublic(array $materials): array
    {
        return array_map(function ($material) {
            return [
                'key' => $material['material_key'],
                'name' => $material['name'],
                'price' => (float) $material['price'],
                'technology' => $material['technology']
            ];
        }, $materials);
    }

    private function formatAdditionalServicesForPublic(array $services): array
    {
        return array_map(function ($service) {
            return [
                'key' => $service['service_key'],
                'name' => $service['name'],
                'price' => (float) $service['price'],
                'unit' => $service['unit']
            ];
        }, $services);
    }

    private function formatQualityLevelsForPublic(array $levels): array
    {
        return array_map(function ($level) {
            return [
                'key' => $level['quality_key'],
                'name' => $level['name'],
                'price_multiplier' => (float) $level['price_multiplier'],
                'time_multiplier' => (float) $level['time_multiplier']
            ];
        }, $levels);
    }

    private function formatVolumeDiscountsForPublic(array $discounts): array
    {
        return array_map(function ($discount) {
            return [
                'min_quantity' => (int) $discount['min_quantity'],
                'discount_percent' => (float) $discount['discount_percent']
            ];
        }, $discounts);
    }

    private function formatFormFieldsForPublic(array $fields): array
    {
        $formatted = [];
        
        foreach ($fields as $field) {
            $formType = $field['form_type'];
            
            if (!isset($formatted[$formType])) {
                $formatted[$formType] = [];
            }
            
            $formatted[$formType][] = [
                'name' => $field['field_name'],
                'label' => $field['label'],
                'type' => $field['field_type'],
                'required' => (bool) $field['required'],
                'placeholder' => $field['placeholder'] ?? '',
                'options' => $field['options'] ?? []
            ];
        }
        
        return $formatted;
    }

    private function redactToken(string $token): string
    {
        if (empty($token) || strlen($token) < 10) {
            return '';
        }
        
        $length = strlen($token);
        $visibleChars = 6;
        $start = substr($token, 0, $visibleChars);
        $end = substr($token, -3);
        
        return $start . '...' . $end;
    }
}
