<?php

declare(strict_types=1);

namespace AaoSikheSystem\lib\view\helper;

/**
 * Validation View Helper - Template-level validation display with full features
 * 
 * @package AaoSikheSystem
 */
class ValidationHelper
{
    private static array $errors = [];
    private static array $oldInput = [];
    private static array $uploadedFiles = [];
    private static string $errorClass = 'is-invalid';
    private static string $successClass = 'is-valid';
    private static string $errorMessageClass = 'invalid-feedback';
    private static string $successMessageClass = 'valid-feedback';
    private static string $errorSummaryClass = 'alert alert-danger';
    private static string $successSummaryClass = 'alert alert-success';
    private static string $formGroupClass = 'form-group';
    private static string $formCheckClass = 'form-check';
    private static string $formCheckInputClass = 'form-check-input';
    private static string $formCheckLabelClass = 'form-check-label';

    /**
     * Initialize validation helper
     */
    public static function init(array $errors = [], array $oldInput = [], array $uploadedFiles = []): void
    {
        self::$errors = $errors;
        self::$oldInput = $oldInput;
        self::$uploadedFiles = $uploadedFiles;
    }

    /**
     * Set custom CSS classes
     */
    public static function setClasses(
        string $errorClass = 'is-invalid',
        string $successClass = 'is-valid',
        string $errorMessageClass = 'invalid-feedback',
        string $successMessageClass = 'valid-feedback',
        string $errorSummaryClass = 'alert alert-danger',
        string $successSummaryClass = 'alert alert-success',
        string $formGroupClass = 'form-group',
        string $formCheckClass = 'form-check',
        string $formCheckInputClass = 'form-check-input',
        string $formCheckLabelClass = 'form-check-label'
    ): void {
        self::$errorClass = $errorClass;
        self::$successClass = $successClass;
        self::$errorMessageClass = $errorMessageClass;
        self::$successMessageClass = $successMessageClass;
        self::$errorSummaryClass = $errorSummaryClass;
        self::$successSummaryClass = $successSummaryClass;
        self::$formGroupClass = $formGroupClass;
        self::$formCheckClass = $formCheckClass;
        self::$formCheckInputClass = $formCheckInputClass;
        self::$formCheckLabelClass = $formCheckLabelClass;
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
    public static function showError(string $field, string $wrapper = 'div', string $customClass = ''): string
    {
        $error = self::firstError($field);
        
        if (!$error) {
            return '';
        }

        $class = $customClass ?: self::$errorMessageClass;
        
        if ($wrapper === 'div') {
            return "<div class=\"{$class}\">{$error}</div>";
        }

        return "<span class=\"{$class}\">{$error}</span>";
    }

    /**
     * Display all errors for field
     */
    public static function showErrors(string $field, string $wrapper = 'div', string $customClass = ''): string
    {
        $errors = self::getErrors($field);
        
        if (empty($errors)) {
            return '';
        }

        $output = '';
        $class = $customClass ?: self::$errorMessageClass;
        
        foreach ($errors as $error) {
            if ($wrapper === 'div') {
                $output .= "<div class=\"{$class}\">{$error}</div>";
            } else {
                $output .= "<span class=\"{$class}\">{$error}</span>";
            }
        }

        return $output;
    }

    /**
     * Display success message for field
     */
    public static function showSuccess(string $message, string $wrapper = 'div', string $customClass = ''): string
    {
        if (!$message) {
            return '';
        }

        $class = $customClass ?: self::$successMessageClass;
        
        if ($wrapper === 'div') {
            return "<div class=\"{$class}\">{$message}</div>";
        }

        return "<span class=\"{$class}\">{$message}</span>";
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
     * Get old input as array (for multiple selects/checkboxes)
     */
    public static function oldArray(string $field, array $default = []): array
    {
        $value = self::$oldInput[$field] ?? $default;
        return is_array($value) ? $value : [$value];
    }

    /**
     * Check if old input exists for field
     */
    public static function hasOld(string $field): bool
    {
        return isset(self::$oldInput[$field]);
    }

    /**
     * Check if value was selected in old input
     */
    public static function wasSelected(string $field, $value): bool
    {
        $oldValue = self::getValue($field);
        
        if (is_array($oldValue)) {
            return in_array($value, $oldValue);
        }
        
        return $oldValue == $value;
    }

    /**
     * Check if value was checked in old input
     */
    public static function wasChecked(string $field, $value = true): bool
    {
        return self::wasSelected($field, $value);
    }

    /**
     * Display form field with validation
     */
    public static function formGroup(string $field, string $label, string $input, string $helpText = '', string $successMessage = ''): string
    {
        $error = self::showError($field);
        $success = $successMessage ? self::showSuccess($successMessage) : '';
        $helpHtml = $helpText ? "<small class=\"form-text text-muted\">{$helpText}</small>" : '';
        
        return "
            <div class=\"" . self::$formGroupClass . "\">
                <label for=\"{$field}\">{$label}</label>
                {$input}
                {$error}
                {$success}
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

        // Handle min/max for number and date inputs
        if (($type === 'number' || $type === 'date' || $type === 'datetime-local') && isset($attributes['min'])) {
            $attrs['min'] = $attributes['min'];
        }
        
        if (($type === 'number' || $type === 'date' || $type === 'datetime-local') && isset($attributes['max'])) {
            $attrs['max'] = $attributes['max'];
        }

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
     * Generate tel input
     */
    public static function tel(string $field, array $attributes = []): string
    {
        return self::input('tel', $field, $attributes);
    }

    /**
     * Generate url input
     */
    public static function url(string $field, array $attributes = []): string
    {
        return self::input('url', $field, $attributes);
    }

    /**
     * Generate date input
     */
    public static function date(string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        if ($value && strtotime($value)) {
            $attributes['value'] = date('Y-m-d', strtotime($value));
        }
        return self::input('date', $field, $attributes);
    }

    /**
     * Generate datetime-local input
     */
    public static function datetime(string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        if ($value && strtotime($value)) {
            $attributes['value'] = date('Y-m-d\TH:i', strtotime($value));
        }
        return self::input('datetime-local', $field, $attributes);
    }

    /**
     * Generate time input
     */
    public static function time(string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        if ($value && strtotime($value)) {
            $attributes['value'] = date('H:i', strtotime($value));
        }
        return self::input('time', $field, $attributes);
    }

    /**
     * Generate month input
     */
    public static function month(string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        if ($value && strtotime($value)) {
            $attributes['value'] = date('Y-m', strtotime($value));
        }
        return self::input('month', $field, $attributes);
    }

    /**
     * Generate week input
     */
    public static function week(string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        if ($value && strtotime($value)) {
            $attributes['value'] = date('Y-\WW', strtotime($value));
        }
        return self::input('week', $field, $attributes);
    }

    /**
     * Generate color input
     */
    public static function color(string $field, array $attributes = []): string
    {
        return self::input('color', $field, $attributes);
    }

    /**
     * Generate range input
     */
    public static function range(string $field, array $attributes = []): string
    {
        return self::input('range', $field, $attributes);
    }

    /**
     * Generate search input
     */
    public static function search(string $field, array $attributes = []): string
    {
        return self::input('search', $field, $attributes);
    }

    /**
     * Generate hidden input
     */
    public static function hidden(string $field, array $attributes = []): string
    {
        $value = self::getValue($field);
        $attrs = array_merge($attributes, [
            'type' => 'hidden',
            'name' => $field,
            'value' => $value
        ]);

        $htmlAttrs = self::buildAttributes($attrs);
        return "<input{$htmlAttrs}>";
    }

    /**
     * Generate file input
     */
    public static function file(string $field, array $attributes = []): string
    {
        $class = self::fieldClass($field, $attributes['class'] ?? 'form-control-file');
        
        $attrs = array_merge($attributes, [
            'type' => 'file',
            'name' => $field,
            'id' => $attributes['id'] ?? $field,
            'class' => $class
        ]);

        $htmlAttrs = self::buildAttributes($attrs);
        return "<input{$htmlAttrs}>";
    }

    /**
     * Show uploaded file info
     */
    public static function fileInfo(string $field, string $downloadUrl = ''): string
    {
        if (!isset(self::$uploadedFiles[$field])) {
            return '';
        }

        $file = self::$uploadedFiles[$field];
        $filename = $file['name'] ?? '';
        
        if (!$filename) {
            return '';
        }

        if ($downloadUrl) {
            return "<div class=\"form-text\">Uploaded file: <a href=\"{$downloadUrl}\" target=\"_blank\">{$filename}</a></div>";
        }

        return "<div class=\"form-text\">Uploaded file: {$filename}</div>";
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

        // Handle multiple select
        if (isset($attributes['multiple']) && $attributes['multiple']) {
            $attrs['name'] = $field . '[]';
        }

        $htmlAttrs = self::buildAttributes($attrs);
        
        $html = "<select{$htmlAttrs}>";
        
        // Add empty option if specified
        if (isset($attributes['placeholder'])) {
            $selected = empty($value) ? ' selected' : '';
            $html .= "<option value=\"\"{$selected}>{$attributes['placeholder']}</option>";
        }

        foreach ($options as $key => $label) {
            $selected = '';
            
            if (is_array($value)) {
                $selected = in_array($key, $value) ? ' selected' : '';
            } else {
                $selected = ($key == $value) ? ' selected' : '';
            }
            
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
        $isChecked = $checked || self::wasChecked($field, $value);
        
        $attrs = array_merge($attributes, [
            'type' => 'checkbox',
            'name' => $field,
            'value' => $value,
            'id' => $attributes['id'] ?? $field,
            'class' => self::$formCheckInputClass . ' ' . ($attributes['class'] ?? '')
        ]);

        if ($isChecked) {
            $attrs['checked'] = 'checked';
        }

        $htmlAttrs = self::buildAttributes($attrs);
        
        return "<input{$htmlAttrs}>";
    }

    /**
     * Generate checkbox group
     */
    public static function checkboxGroup(string $field, array $options, array $attributes = []): string
    {
        $oldValues = self::oldArray($field);
        $html = '';
        
        foreach ($options as $value => $label) {
            $isChecked = in_array($value, $oldValues);
            $checkboxId = $field . '_' . $value;
            
            $html .= "
                <div class=\"" . self::$formCheckClass . "\">
                    " . self::checkbox($field . '[]', $value, $isChecked, ['id' => $checkboxId]) . "
                    <label class=\"" . self::$formCheckLabelClass . "\" for=\"{$checkboxId}\">{$label}</label>
                </div>
            ";
        }
        
        return $html;
    }

    /**
     * Generate radio button
     */
    public static function radio(string $field, $value, bool $checked = false, array $attributes = []): string
    {
        $oldValue = self::getValue($field);
        $isChecked = $checked || self::wasSelected($field, $value);
        
        $attrs = array_merge($attributes, [
            'type' => 'radio',
            'name' => $field,
            'value' => $value,
            'id' => $attributes['id'] ?? $field . '_' . $value,
            'class' => self::$formCheckInputClass . ' ' . ($attributes['class'] ?? '')
        ]);

        if ($isChecked) {
            $attrs['checked'] = 'checked';
        }

        $htmlAttrs = self::buildAttributes($attrs);
        
        return "<input{$htmlAttrs}>";
    }

    /**
     * Generate radio group
     */
    public static function radioGroup(string $field, array $options, array $attributes = []): string
    {
        $oldValue = self::getValue($field);
        $html = '';
        
        foreach ($options as $value => $label) {
            $isChecked = ($value == $oldValue);
            $radioId = $field . '_' . $value;
            
            $html .= "
                <div class=\"" . self::$formCheckClass . "\">
                    " . self::radio($field, $value, $isChecked, ['id' => $radioId]) . "
                    <label class=\"" . self::$formCheckLabelClass . "\" for=\"{$radioId}\">{$label}</label>
                </div>
            ";
        }
        
        return $html;
    }

    /**
     * Generate custom input with any type
     */
    public static function custom(string $type, string $field, array $attributes = []): string
    {
        return self::input($type, $field, $attributes);
    }

    /**
     * Display validation errors summary
     */
    public static function errorSummary(string $title = 'Please fix the following errors:', string $customClass = ''): string
    {
        if (empty(self::$errors)) {
            return '';
        }

        $class = $customClass ?: self::$errorSummaryClass;
        $html = "<div class=\"{$class}\">";
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
     * Display success summary
     */
    public static function successSummary(string $message, string $title = 'Success!', string $customClass = ''): string
    {
        if (!$message) {
            return '';
        }

        $class = $customClass ?: self::$successSummaryClass;
        $html = "<div class=\"{$class}\">";
        
        if ($title) {
            $html .= "<strong>{$title}</strong><br>";
        }
        
        $html .= $message;
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
     * Get error count
     */
    public static function count(): int
    {
        $count = 0;
        foreach (self::$errors as $errors) {
            $count += count($errors);
        }
        return $count;
    }

    /**
     * Clear all errors and old input
     */
    public static function clear(): void
    {
        self::$errors = [];
        self::$oldInput = [];
        self::$uploadedFiles = [];
    }

    /**
     * Add custom error
     */
    public static function addError(string $field, string $message): void
    {
        if (!isset(self::$errors[$field])) {
            self::$errors[$field] = [];
        }
        
        self::$errors[$field][] = $message;
    }

    /**
     * Remove error for field
     */
    public static function removeError(string $field): void
    {
        unset(self::$errors[$field]);
    }

    /**
     * Check if form was submitted successfully
     */
    public static function success(): bool
    {
        return empty(self::$errors) && !empty(self::$oldInput);
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

    /**
     * Generate CSRF token field
     */
    public static function csrf(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return self::hidden('csrf_token', ['value' => $token]);
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate form with validation
     */
    public static function formOpen(string $action = '', string $method = 'POST', array $attributes = []): string
    {
        $attrs = array_merge([
            'method' => strtoupper($method),
            'action' => $action,
            'enctype' => 'multipart/form-data'
        ], $attributes);

        $htmlAttrs = self::buildAttributes($attrs);
        $csrf = $method === 'POST' ? self::csrf() : '';
        
        return "<form{$htmlAttrs}>\n{$csrf}";
    }

    /**
     * Close form
     */
    public static function formClose(): string
    {
        return "</form>";
    }

    /**
     * Generate label with optional required asterisk
     */
    public static function label(string $for, string $text, bool $required = false, array $attributes = []): string
    {
        $requiredHtml = $required ? ' <span class="text-danger">*</span>' : '';
        $attrs = self::buildAttributes($attributes);
        
        return "<label for=\"{$for}\"{$attrs}>{$text}{$requiredHtml}</label>";
    }

    /**
     * Generate help text
     */
    public static function help(string $text, string $class = 'form-text text-muted'): string
    {
        return "<small class=\"{$class}\">{$text}</small>";
    }

    /**
     * Generate form group with custom content
     */
    public static function customFormGroup(string $field, string $content, string $helpText = '', string $successMessage = ''): string
    {
        $error = self::showError($field);
        $success = $successMessage ? self::showSuccess($successMessage) : '';
        $helpHtml = $helpText ? self::help($helpText) : '';
        
        return "
            <div class=\"" . self::$formGroupClass . "\">
                {$content}
                {$error}
                {$success}
                {$helpHtml}
            </div>
        ";
    }
}