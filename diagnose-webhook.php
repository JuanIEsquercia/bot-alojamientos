<?php

/**
 * Script para diagnosticar por qué Meta no está enviando mensajes al webhook
 */

require_once __DIR__ . '/autoload.php';

use BotAlojamientos\Config\Config;

echo "═══════════════════════════════════════\n";
echo "🔍 DIAGNÓSTICO: Por qué no llegan mensajes\n";
echo "═══════════════════════════════════════\n\n";

$config = Config::getInstance();
$accessToken = $config->get('whatsapp.access_token');
$businessAccountId = $config->get('whatsapp.business_account_id');
$phoneNumberId = $config->get('whatsapp.phone_number_id');
$apiVersion = 'v22.0';
$graphApiUrl = 'https://graph.facebook.com';

if (empty($accessToken) || empty($businessAccountId)) {
    echo "❌ ERROR: Credenciales no configuradas\n";
    exit(1);
}

echo "1️⃣ Verificando suscripciones del webhook usando la API...\n\n";

// Consultar las suscripciones del webhook usando la API
$url = "$graphApiUrl/$apiVersion/$businessAccountId/subscribed_apps";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken,
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Error cURL: $error\n\n";
} elseif ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
        echo "✅ Apps suscritas encontradas:\n\n";
        foreach ($data['data'] as $app) {
            $appId = $app['app_id'] ?? 'N/A';
            $status = $app['status'] ?? 'N/A';
            echo "   App ID: $appId\n";
            echo "   Estado: $status\n";
            
            // Consultar campos suscritos para esta app
            if (isset($app['app_id'])) {
                $fieldsUrl = "$graphApiUrl/$apiVersion/$businessAccountId/subscribed_apps/{$app['app_id']}?fields=webhook_fields";
                $ch2 = curl_init($fieldsUrl);
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $accessToken,
                    ],
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ]);
                
                $fieldsResponse = curl_exec($ch2);
                $fieldsHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
                
                if ($fieldsHttpCode === 200) {
                    $fieldsData = json_decode($fieldsResponse, true);
                    $webhookFields = $fieldsData['webhook_fields'] ?? [];
                    if (!empty($webhookFields)) {
                        echo "   Campos suscritos: " . implode(', ', $webhookFields) . "\n";
                        if (in_array('messages', $webhookFields)) {
                            echo "   ✅ 'messages' está suscrito\n";
                        } else {
                            echo "   ❌ 'messages' NO está suscrito\n";
                        }
                    } else {
                        echo "   ⚠️ No se encontraron campos suscritos\n";
                    }
                }
            }
            echo "\n";
        }
    } else {
        echo "⚠️ No se encontraron apps suscritas\n";
        echo "   Esto puede explicar por qué no recibes mensajes\n\n";
    }
} else {
    echo "⚠️ No se pudo consultar las suscripciones (HTTP $httpCode)\n";
    echo "   Respuesta: $response\n\n";
}

echo "2️⃣ Verificando estado del número...\n\n";

if (!empty($phoneNumberId)) {
    $url2 = "$graphApiUrl/$apiVersion/$phoneNumberId?fields=display_phone_number,code_verification_status,account_mode";
    
    $ch2 = curl_init($url2);
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    
    $response2 = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    if ($httpCode2 === 200) {
        $data2 = json_decode($response2, true);
        $codeStatus = $data2['code_verification_status'] ?? 'N/A';
        $accountMode = $data2['account_mode'] ?? 'N/A';
        
        echo "   Número: " . ($data2['display_phone_number'] ?? 'N/A') . "\n";
        echo "   Estado: $codeStatus\n";
        echo "   Modo: $accountMode\n\n";
        
        if ($codeStatus === 'VERIFIED' && $accountMode === 'LIVE') {
            echo "   ✅ El número está listo para recibir mensajes\n\n";
        } else {
            echo "   ⚠️ El número puede no estar completamente listo\n\n";
        }
    }
}

echo "═══════════════════════════════════════\n";
echo "📋 POSIBLES SOLUCIONES\n";
echo "═══════════════════════════════════════\n\n";

echo "1. Re-suscribir el webhook manualmente:\n";
echo "   → Ve a Meta for Developers → WhatsApp → Configuration → Webhook\n";
echo "   → Haz clic en 'Manage' en Subscription Fields\n";
echo "   → Desmarca 'messages', guarda\n";
echo "   → Vuelve a marcar 'messages', guarda\n\n";

echo "2. Re-verificar el webhook:\n";
echo "   → En Meta for Developers → WhatsApp → Configuration → Webhook\n";
echo "   → Haz clic en 'Verify and Save'\n";
echo "   → Ingresa el Verify Token: to_aloja_ctes1739\n\n";

echo "3. Verificar que la URL sea accesible:\n";
echo "   → Prueba: https://bot-alojamientos-1052642934060.europe-west1.run.app/webhook.php\n";
echo "   → Debe responder (aunque sea con error de verificación)\n\n";

echo "4. Esperar unos minutos:\n";
echo "   → Meta puede tener un delay después de la verificación\n";
echo "   → Espera 5-10 minutos y prueba de nuevo\n\n";

echo "5. Verificar en Meta Business Manager:\n";
echo "   → WhatsApp Accounts → Tu cuenta → Phone Numbers\n";
echo "   → Verifica que el número esté 'Verificado' (no 'Pendiente')\n\n";

echo "\n";

