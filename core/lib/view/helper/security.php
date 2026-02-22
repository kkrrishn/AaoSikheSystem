<?php

declare(strict_types=1);

namespace AaoSikheSystem\View\Helper;

/**
 * AaoSikheSystem Secure - Security View Helper
 * 
 * @package AaoSikheSystem
 */

class SecurityHelper
{
    public static function csrfField(): string
    {
        $token = \AaoSikheSystem\Security\SecurityHelper::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
    
    public static function csrfMeta(): string
    {
        $token = \AaoSikheSystem\Security\SecurityHelper::generateCsrfToken();
        return '<meta name="csrf-token" content="' . $token . '">';
    }
    
    public static function nonce(): string
    {
        return \AaoSikheSystem\Security\SecurityHelper::generateNonce();
    }
}