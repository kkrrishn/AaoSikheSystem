<?php

declare(strict_types=1);

namespace AaoSikheSystem\view;

/**
 * AaoSikheSystem Secure - View Renderer
 * 
 * @package AaoSikheSystem
 */

class View
{
    private string $viewsPath;
    private string $cachePath;
    private array $data = [];
    private bool $autoEscape = true;
    
    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
    }
    
    public function render(string $template, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        
        $templateFile = $this->viewsPath . '/' . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("View template not found: {$template}");
        }
        
        extract($this->data);
        ob_start();
        
        include $templateFile;
        
        return ob_get_clean();
    }
    
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public function raw(string $value): string
    {
        return $value;
    }
    
    public function setAutoEscape(bool $autoEscape): void
    {
        $this->autoEscape = $autoEscape;
    }
    
    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }
    
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
    
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
}