<?php

/**
 * Script de prueba para verificar la conexión a Firebase
 * Ejecutar: php test_firebase.php
 */

require_once __DIR__ . '/autoload.php';

echo "=== PRUEBA DE CONEXIÓN A FIREBASE ===\n\n";

try {
    // 1. Verificar configuración
    echo "1. Verificando configuración...\n";
    $config = \BotAlojamientos\Config\Config::getInstance();
    
    $projectId = $config->get('firebase.project_id');
    $credentialsPath = $config->get('firebase.credentials_path');
    
    if (empty($projectId)) {
        throw new Exception("❌ FIREBASE_PROJECT_ID no está configurado en .env");
    }
    echo "   ✅ Project ID: $projectId\n";
    
    if (empty($credentialsPath) || !file_exists($credentialsPath)) {
        throw new Exception("❌ GOOGLE_APPLICATION_CREDENTIALS no configurado o archivo no existe");
    }
    echo "   ✅ Credentials path: $credentialsPath\n";
    
    // 2. Probar conexión a Firebase
    echo "\n2. Conectando a Firebase...\n";
    $firebase = new \BotAlojamientos\Services\FirebaseService();
    echo "   ✅ FirebaseService creado correctamente\n";
    
    // 3. Probar obtención de token (esto prueba la autenticación)
    echo "\n3. Probando autenticación (obtención de token)...\n";
    // Esto se hará internamente cuando hagamos una consulta
    
    // 4. Probar búsqueda de usuario (con un número de prueba)
    echo "\n4. Probando búsqueda de usuario...\n";
    echo "   (Usa un número de teléfono que SEPA que existe en tu base de datos)\n";
    echo "   Ingresa un número de teléfono para probar (o presiona Enter para saltar): ";
    
    $handle = fopen("php://stdin", "r");
    $testPhone = trim(fgets($handle));
    fclose($handle);
    
    if (!empty($testPhone)) {
        $user = $firebase->validateUser($testPhone);
        if ($user) {
            echo "   ✅ Usuario encontrado:\n";
            echo "      ID: " . ($user['id'] ?? 'N/A') . "\n";
            echo "      Email: " . ($user['email'] ?? 'N/A') . "\n";
            echo "      Teléfono: " . ($user['telefono'] ?? 'N/A') . "\n";
            echo "      Estado: " . ($user['status'] ?? 'N/A') . "\n";
        } else {
            echo "   ℹ️ Usuario no encontrado (el número no está registrado)\n";
        }
    } else {
        echo "   ⏭️ Prueba de usuario saltada\n";
    }
    
    // 5. Probar búsqueda por DNI (opcional)
    echo "\n5. Probando búsqueda por DNI...\n";
    echo "   Ingresa un DNI para probar (8 dígitos, o Enter para saltar): ";
    
    $handle = fopen("php://stdin", "r");
    $testDni = trim(fgets($handle));
    fclose($handle);
    
    if (!empty($testDni)) {
        $reports = $firebase->searchByDni($testDni);
        if (!empty($reports)) {
            echo "   ✅ Se encontraron " . count($reports) . " reporte(s):\n";
            foreach ($reports as $index => $report) {
                echo "      Reporte " . ($index + 1) . ":\n";
                echo "         Nombre: " . ($report['nombre'] ?? 'N/A') . "\n";
                echo "         DNI: " . ($report['dni'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   ℹ️ No se encontraron reportes para ese DNI\n";
        }
    } else {
        echo "   ⏭️ Prueba de DNI saltada\n";
    }
    
    echo "\n=== ✅ TODAS LAS PRUEBAS COMPLETADAS ===\n";
    echo "Si llegaste hasta aquí sin errores, Firebase está funcionando correctamente.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

