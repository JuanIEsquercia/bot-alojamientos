<?php
/**
 * Script de prueba para verificar que el bot sigue funcionando
 * después de los cambios de seguridad
 */

require_once __DIR__ . '/autoload.php';

use BotAlojamientos\Config\Config;

echo "🔍 Verificando configuración de seguridad...\n\n";

// 1. Verificar que APP_ENV no esté en producción
$appEnv = getenv('APP_ENV') ?: (isset($_ENV['APP_ENV']) ? $_ENV['APP_ENV'] : null);
if ($appEnv === 'production') {
    echo "⚠️  APP_ENV=production detectado\n";
    echo "   En producción, la verificación de firma es obligatoria.\n\n";
} else {
    echo "✅ Modo desarrollo detectado (APP_ENV no configurado)\n";
    echo "   El bot funcionará con validaciones relajadas para desarrollo.\n\n";
}

// 2. Verificar configuración
try {
    $config = Config::getInstance();
    
    echo "📋 Verificando variables de entorno:\n";
    
    $vars = [
        'whatsapp.access_token' => 'WHATSAPP_ACCESS_TOKEN',
        'whatsapp.phone_number_id' => 'WHATSAPP_PHONE_NUMBER_ID',
        'whatsapp.webhook_verify_token' => 'WHATSAPP_WEBHOOK_VERIFY_TOKEN',
        'whatsapp.webhook_secret' => 'WHATSAPP_WEBHOOK_SECRET',
        'firebase.project_id' => 'FIREBASE_PROJECT_ID',
        'firebase.credentials_path' => 'GOOGLE_APPLICATION_CREDENTIALS',
    ];
    
    $allOk = true;
    foreach ($vars as $key => $name) {
        $value = $config->get($key);
        if (empty($value)) {
            echo "   ❌ $name: NO CONFIGURADO\n";
            $allOk = false;
        } else {
            // Ocultar valores sensibles
            if (strpos($name, 'TOKEN') !== false || strpos($name, 'SECRET') !== false) {
                $display = substr($value, 0, 10) . '...';
            } else {
                $display = $value;
            }
            echo "   ✅ $name: $display\n";
        }
    }
    
    echo "\n";
    
    if (!$allOk) {
        echo "⚠️  Algunas variables no están configuradas.\n";
        echo "   El bot puede no funcionar correctamente.\n\n";
    } else {
        echo "✅ Todas las variables están configuradas.\n\n";
    }
    
    // 3. Verificar archivo de credenciales
    $credentialsPath = $config->get('firebase.credentials_path');
    if ($credentialsPath && file_exists($credentialsPath)) {
        echo "✅ Archivo de credenciales existe: $credentialsPath\n";
        $size = filesize($credentialsPath);
        echo "   Tamaño: " . number_format($size) . " bytes\n";
        
        if ($size > 10240) {
            echo "   ⚠️  El archivo es muy grande (>10KB)\n";
        }
    } else {
        echo "❌ Archivo de credenciales no encontrado\n";
    }
    
    echo "\n";
    
    // 4. Verificar servicios
    echo "🔧 Verificando servicios:\n";
    
    try {
        $whatsappService = new \BotAlojamientos\Services\WhatsAppService();
        echo "   ✅ WhatsAppService: OK\n";
    } catch (Exception $e) {
        echo "   ❌ WhatsAppService: " . $e->getMessage() . "\n";
    }
    
    try {
        $firebaseService = new \BotAlojamientos\Services\FirebaseService();
        echo "   ✅ FirebaseService: OK\n";
    } catch (Exception $e) {
        echo "   ❌ FirebaseService: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 5. Resumen de seguridad
    echo "🔒 Estado de seguridad:\n";
    if ($appEnv === 'production') {
        echo "   ⚠️  MODO PRODUCCIÓN\n";
        echo "   - Verificación de firma: OBLIGATORIA\n";
        echo "   - SSL verification: HABILITADA\n";
        echo "   - Logs: MÍNIMOS (sin información sensible)\n";
    } else {
        echo "   ✅ MODO DESARROLLO\n";
        echo "   - Verificación de firma: OPCIONAL\n";
        echo "   - SSL verification: DESHABILITADA (Windows local)\n";
        echo "   - Logs: DETALLADOS (para debugging)\n";
    }
    
    echo "\n✅ El bot está listo para usar en modo desarrollo.\n";
    echo "   Para producción, configura APP_ENV=production en .env\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

