<?php
/**
 * Standalone Autoloader - No Composer Required
 * Simple PSR-4 autoloader for App\ namespace
 */

spl_autoload_register(function ($class) {
    // Only handle App\ namespace
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
