<?php
declare(strict_types=1);

namespace App\helpers;

class ValidationHelper
{
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }

    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public static function sanitizeUrl(string $url): string
    {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }

    public static function validateName(string $name): bool
    {
        return preg_match('/^[a-zA-Z\s\-\']{2,50}$/', $name) === 1;
    }

    public static function validatePhone(string $phone): bool
    {
        // Basic phone validation - adjust regex for your needs
        return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/\D/', '', $phone)) === 1;
    }

    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public static function validateFile(array $file, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        $errors = [];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadErrorMessage($file['error']);
            return $errors;
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = "File size must be less than " . round($maxSize / 1048576, 1) . "MB";
        }

        // Check file type
        if (!empty($allowedTypes)) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileMime = mime_content_type($file['tmp_name']);

            if (!in_array($fileExtension, $allowedTypes) && !in_array($fileMime, $allowedTypes)) {
                $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowedTypes);
            }
        }

        return $errors;
    }

    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    public static function validateArray(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $fieldErrors = self::validateField($value, $rule, $field);

            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            }
        }

        return $errors;
    }

    private static function validateField($value, string $rule, string $fieldName): array
    {
        $errors = [];
        $rules = explode('|', $rule);

        foreach ($rules as $singleRule) {
            $ruleParts = explode(':', $singleRule);
            $ruleName = $ruleParts[0];
            $ruleValue = $ruleParts[1] ?? null;

            switch ($ruleName) {
                case 'required':
                    if (empty($value)) {
                        $errors[] = "{$fieldName} is required";
                    }
                    break;

                case 'email':
                    if (!empty($value) && !self::validateEmail($value)) {
                        $errors[] = "{$fieldName} must be a valid email address";
                    }
                    break;

                case 'min':
                    if (!empty($value) && strlen($value) < (int)$ruleValue) {
                        $errors[] = "{$fieldName} must be at least {$ruleValue} characters";
                    }
                    break;

                case 'max':
                    if (!empty($value) && strlen($value) > (int)$ruleValue) {
                        $errors[] = "{$fieldName} must be less than {$ruleValue} characters";
                    }
                    break;

                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $errors[] = "{$fieldName} must be a number";
                    }
                    break;

                case 'url':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[] = "{$fieldName} must be a valid URL";
                    }
                    break;
            }
        }

        return $errors;
    }
}