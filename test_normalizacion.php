<?php

/**
 * Script para probar la normalización de números
 */

require_once __DIR__ . '/autoload.php';

echo "=== PRUEBA DE NORMALIZACIÓN DE NÚMEROS ===\n\n";

$whatsappService = new \BotAlojamientos\Services\WhatsAppService();

// Ejemplos de números que vienen de WhatsApp
$testNumbers = [
    '+5493794267780',  // Formato internacional completo
    '5493794267780',   // Sin el +
    '3794267780',      // Ya normalizado
    '93794267780',     // Con código de país pero sin +
];

echo "Números de prueba desde WhatsApp:\n";
foreach ($testNumbers as $testNum) {
    $normalized = $whatsappService->extractPhoneNumber($testNum);
    echo "  $testNum -> $normalized (últimos 10 dígitos)\n";
}

echo "\n=== PRUEBA DE BÚSQUEDA EN FIREBASE ===\n\n";

// Probar con un número real
echo "Ingresa un número de teléfono para probar (ej: +5493794267780): ";
$handle = fopen("php://stdin", "r");
$testPhone = trim(fgets($handle));
fclose($handle);

if (!empty($testPhone)) {
    $normalized = $whatsappService->extractPhoneNumber($testPhone);
    echo "Número normalizado: $normalized\n\n";
    
    try {
        $firebase = new \BotAlojamientos\Services\FirebaseService();
        $user = $firebase->validateUser($testPhone);
        
        if ($user) {
            echo "✅ Usuario encontrado:\n";
            echo "   ID: " . ($user['id'] ?? 'N/A') . "\n";
            echo "   Email: " . ($user['email'] ?? 'N/A') . "\n";
            echo "   Teléfono en BD: " . ($user['telefono'] ?? 'N/A') . "\n";
            echo "   Estado: " . ($user['status'] ?? 'N/A') . "\n";
        } else {
            echo "❌ Usuario no encontrado\n";
            echo "   Número normalizado buscado: $normalized\n";
            echo "   Verifica que este número esté en la base de datos\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

