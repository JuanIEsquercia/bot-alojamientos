<?php

/**
 * Autoloader simple sin Composer
 * Carga automáticamente las clases del proyecto
 */

spl_autoload_register(function ($className) {
    // Convertir namespace a ruta de archivo
    $className = str_replace('BotAlojamientos\\', '', $className);
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    
    // Ruta base del proyecto
    $basePath = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    
    // Ruta completa del archivo
    $filePath = $basePath . $className . '.php';
    
    // Cargar el archivo si existe
    if (file_exists($filePath)) {
        require_once $filePath;
        return true;
    }
    
    return false;
});

