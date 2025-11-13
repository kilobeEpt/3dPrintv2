<?php

namespace App\Helpers;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $fieldRules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;

            foreach ($fieldRules as $rule) {
                if (is_callable($rule)) {
                    $result = $rule($value, $data);
                    if ($result !== true) {
                        $this->errors[$field][] = $result;
                    }
                } else {
                    $this->validateRule($field, $value, $rule, $data);
                }
            }
        }

        return empty($this->errors);
    }

    private function validateRule(string $field, $value, string $rule, array $data): void
    {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $param = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a string';
                }
                break;

            case 'integer':
                if ($value !== null && !is_numeric($value) && !is_int($value)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be an integer';
                }
                break;

            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be numeric';
                }
                break;

            case 'boolean':
                if ($value !== null && !is_bool($value) && $value !== 0 && $value !== 1 && $value !== '0' && $value !== '1') {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a boolean';
                }
                break;

            case 'email':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid email address';
                }
                break;

            case 'url':
                if ($value !== null && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid URL';
                }
                break;

            case 'min':
                if ($value !== null) {
                    if (is_string($value) && strlen($value) < $param) {
                        $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$param} characters";
                    } elseif (is_numeric($value) && $value < $param) {
                        $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$param}";
                    }
                }
                break;

            case 'max':
                if ($value !== null) {
                    if (is_string($value) && strlen($value) > $param) {
                        $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$param} characters";
                    } elseif (is_numeric($value) && $value > $param) {
                        $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$param}";
                    }
                }
                break;

            case 'in':
                $allowedValues = explode(',', $param);
                if ($value !== null && !in_array($value, $allowedValues, true)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be one of: ' . implode(', ', $allowedValues);
                }
                break;

            case 'array':
                if ($value !== null && !is_array($value)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be an array';
                }
                break;

            case 'min_items':
                if ($value !== null && is_array($value) && count($value) < $param) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must contain at least {$param} items";
                }
                break;

            case 'max_items':
                if ($value !== null && is_array($value) && count($value) > $param) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must not contain more than {$param} items";
                }
                break;

            case 'between':
                $range = explode(',', $param);
                $min = $range[0];
                $max = $range[1];
                if ($value !== null) {
                    if (is_string($value)) {
                        $len = strlen($value);
                        if ($len < $min || $len > $max) {
                            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be between {$min} and {$max} characters";
                        }
                    } elseif (is_numeric($value) && ($value < $min || $value > $max)) {
                        $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be between {$min} and {$max}";
                    }
                }
                break;
        }
    }

    public function getErrors(): array
    {
        return array_map(function ($errors) {
            return is_array($errors) ? implode(', ', $errors) : $errors;
        }, $this->errors);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
