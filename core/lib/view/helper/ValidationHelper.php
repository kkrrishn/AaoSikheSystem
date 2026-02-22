<?php

declare(strict_types=1);

namespace AaoSikheSystem\lib\view\helper;

/**
 * Validation View Helper - Template-level validation display
 * 
 * @package AaoSikheSystem
 */
class ValidationHelper
{
    private static array $errors = [];
    private static array $oldInput = [];
    private static string $errorClass = 'is-invalid';
    private static string $successClass = 'is-valid';
    private static string $errorMessageClass = 'invalid-feedback';
    private static string $errorContainerClass = '';

    /**
     * Initialize validation helper
     */
    public static function init(array $errors = [], array $oldInput = []): void
    {
        self::$errors = $errors;
        self::$oldInput = $oldInput;
    }

    /**
     * Set custom CSS classes
     */
    public static function setClasses(string $errorClass, string $successClass, string $errorMessageClass): void
    {
        self::$errorClass = $errorClass;
        self::$successClass = $successClass;
        self::$errorMessageClass = $errorMessageClass;
    }

    /**
     * Check if field has error
     */
    public static function hasError(string $field): bool
    {
        return isset(self::$errors[$field]);
    }

    /**
     * Get all errors for field
     */
    public static function getErrors(string $field): array
    {
        return self::$errors[$field] ?? [];
    }

    /**
     * Get first error for field
     */
    public static function firstError(string $field): string
    {
        return self::$errors[$field][0] ?? '';
    }

    /**
     * Display error message for field
     */
    public static function showError(string $field, string $wrapper = 'div'): string
    {
        $error = self::firstError($field);
        
        if (!$error) {
            return '';
        }

        $class = self::$errorMessageClass;
        
        if ($wrapper === 'div') {
            return "<div class=\"{$class}\">{$error}</div>";
        }

        return "<span class=\"{$class}\">{$error}</span>";
    }

    /**
     * Display all errors for field
     */
    public static function showErrors(string $field): string
    {
        $errors = self::getErrors($field);
        
        if (empty($errors)) {
            return '';
        }

        $output = '';
        $class = self::$errorMessageClass;
        
        foreach ($errors as $error) {
            $output .= "<div class=\"{$class}\">{$error}</div>";
        }

        return $output;
    }

    /**
     * Get CSS class for field based on validation state
     */
    public static function fieldClass(string $field, string $baseClass = ''): string
    {
        $classes = [$baseClass];

        if (self::hasError($field)) {
            $classes[] = self::$errorClass;
        } elseif (!empty(self::getValue($field))) {
            $classes[] = self::$successClass;
        }

        return trim(implode(' ', $classes));
    }

    /**
     * Get old input value
     */
    public static function old(string $field, $default = ''): string
    {
        return self::$oldInput[$field] ?? $default;
    }

    /**
     * Get value from old input or default
     */
    public static function value(string $field, $default = ''): string
    {
        return self::old($field, $default);
    }

    /**
     * Check if old input exists for field
     */
    public static function hasOld(string $field): bool
    {
        return isset(self::$oldInput[$field]);
    }

    /**
     * Display form field with validation
     */
    public static function formGroup(string $field, string $label, string $input, string $helpText = ''): string
    {
        $error = self::showError($field);
        $hasError = self::hasError($field);
        
        $helpHtml = $helpText ? "<small class=\"form-text text-muted\">{$helpText}</small>" : '';
        
        return "
            <div class=\"form-group\">
                <label for=\"{$field}\">{$label}</label>
                {$input}
                {$error}
                {$helpHtml}
            </div>
        ";
    }

    /**
     * Generate input field with validation
     */
    public static function input(string $type, string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        $class = self::fieldClass($field, $attributes['class'] ?? 'form-control');
        
        $attrs = array_merge($attributes, [
            'type' => $type,
            'name' => $field,
            'id' => $attributes['id'] ?? $field,
            'value' => $type !== 'password' ? $value : '',
            'class' => $class
        ]);

        $htmlAttrs = self::buildAttributes($attrs);
        
        return "<input{$htmlAttrs}>";
    }

    /**
     * Generate text input
     */
    public static function text(string $field, array $attributes = []): string
    {
        return self::input('text', $field, $attributes);
    }

    /**
     * Generate email input
     */
    public static function email(string $field, array $attributes = []): string
    {
        return self::input('email', $field, $attributes);
    }

    /**
     * Generate password input
     */
    public static function password(string $field, array $attributes = []): string
    {
        return self::input('password', $field, $attributes);
    }

    /**
     * Generate number input
     */
    public static function number(string $field, array $attributes = []): string
    {
        return self::input('number', $field, $attributes);
    }

    /**
     * Generate textarea
     */
    public static function textarea(string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        $class = self::fieldClass($field, $attributes['class'] ?? 'form-control');
        
        $attrs = array_merge($attributes, [
            'name' => $field,
            'id' => $attributes['id'] ?? $field,
            'class' => $class
        ]);

        unset($attrs['value']);
        
        $htmlAttrs = self::buildAttributes($attrs);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return "<textarea{$htmlAttrs}>{$value}</textarea>";
    }

    /**
     * Generate select dropdown
     */
    public static function select(string $field, array $options, array $attributes = []): string
    {
        $value = self::getValue($field);
        $class = self::fieldClass($field, $attributes['class'] ?? 'form-control');
        
        $attrs = array_merge($attributes, [
            'name' => $field,
            'id' => $attributes['id'] ?? $field,
            'class' => $class
        ]);

        $htmlAttrs = self::buildAttributes($attrs);
        
        $html = "<select{$htmlAttrs}>";
        
        // Add empty option if specified
        if (isset($attributes['placeholder'])) {
            $html .= "<option value=\"\">{$attributes['placeholder']}</option>";
        }

        foreach ($options as $key => $label) {
            $selected = ($key == $value) ? ' selected' : '';
            $html .= "<option value=\"{$key}\"{$selected}>{$label}</option>";
        }

        $html .= "</select>";
        
        return $html;
    }

    /**
     * Generate checkbox
     */
    public static function checkbox(string $field, $value = 1, bool $checked = false, array $attributes = []): string
    {
        $oldValue = self::getValue($field);
        $isChecked = $checked || $oldValue == $value;
        
        $attrs = array_merge($attributes, [
            'type' => 'checkbox',
            'name' => $field,
            'value' => $value,
            'id' => $attributes['id'] ?? $field
        ]);

        if ($isChecked) {
            $attrs['checked'] = 'checked';
        }

        $htmlAttrs = self::buildAttributes($attrs);
        
        return "<input{$htmlAttrs}>";
    }

    /**
     * Generate radio button
     */
    public static function radio(string $field, $value, bool $checked = false, array $attributes = []): string
    {
        $oldValue = self::getValue($field);
        $isChecked = $checked || $oldValue == $value;
        
        $attrs = array_merge($attributes, [
            'type' => 'radio',
            'name' => $field,
            'value' => $value,
            'id' => $attributes['id'] ?? $field . '_' . $value
        ]);

        if ($isChecked) {
            $attrs['checked'] = 'checked';
        }

        $htmlAttrs = self::buildAttributes($attrs);
        
        return "<input{$htmlAttrs}>";
    }

    /**
     * Display validation errors summary
     */
    public static function errorSummary(string $title = 'Please fix the following errors:'): string
    {
        if (empty(self::$errors)) {
            return '';
        }

        $html = "<div class=\"alert alert-danger\">";
        $html .= "<strong>{$title}</strong>";
        $html .= "<ul class=\"mb-0\">";

        foreach (self::$errors as $field => $errors) {
            foreach ($errors as $error) {
                $html .= "<li>{$error}</li>";
            }
        }

        $html .= "</ul>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Check if there are any errors
     */
    public static function any(): bool
    {
        return !empty(self::$errors);
    }

    /**
     * Get all errors
     */
    public static function all(): array
    {
        return self::$errors;
    }

    /**
     * Clear all errors and old input
     */
    public static function clear(): void
    {
        self::$errors = [];
        self::$oldInput = [];
    }

    /**
     * Get value from old input with dot notation support
     */
    private static function getValue(string $field)
    {
        $keys = explode('.', $field);
        $value = self::$oldInput;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return '';
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Build HTML attributes string
     */
    private static function buildAttributes(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $html = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html[] = $key;
                }
            } elseif ($value !== null) {
                $html[] = $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return $html ? ' ' . implode(' ', $html) : '';
    }
}