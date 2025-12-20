<?php

/**
 * Webhook para recibir mensajes de WhatsApp desde Meta Business API
 * 
 * Configura este archivo como webhook en Meta for Developers:
 * https://developers.facebook.com/apps/
 * 
 * El webhook debe manejar:
 * - GET: Verificación del webhook (con challenge y verify_token)
 * - POST: Recepción de eventos (mensajes, estados, etc.)
 */

require_once __DIR__ . '/autoload.php';

use BotAlojamientos\Bot\WhatsAppBot;
use BotAlojamientos\Config\Config;

// Configurar logging: SIEMPRE a stderr para ver todo en Cloud Logging y en consola local
ini_set('log_errors', 1);
ini_set('error_log', 'php://stderr');

// Marca de vida del webhook
error_log('PING webhook.php cargado');
error_log('Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
error_log('Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log('APP_ENV: ' . (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'not set')));
error_log('X-Hub-Signature-256 header: ' . ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? 'not present'));

// Configurar headers para JSON
header('Content-Type: application/json');

$config = Config::getInstance();
$verifyToken = $config->get('whatsapp.webhook_verify_token');

// Validar que el verify token esté configurado
if (empty($verifyToken)) {
    error_log("ERROR: WHATSAPP_WEBHOOK_VERIFY_TOKEN no está configurado en .env");
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit;
}

// Manejar verificación del webhook (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Meta envía los parámetros con puntos: hub.mode, hub.verify_token, hub.challenge
    // PHP los convierte a guiones bajos en $_GET, pero también podemos leerlos directamente
    // Intentar leer de múltiples formas para asegurar compatibilidad
    $mode = $_GET['hub.mode'] ?? $_GET['hub_mode'] ?? '';
    $token = $_GET['hub.verify_token'] ?? $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub.challenge'] ?? $_GET['hub_challenge'] ?? '';
    
    // Log completo para debugging
    error_log("═══════════════════════════════════════");
    error_log("🔍 VERIFICACIÓN WEBHOOK (GET)");
    error_log("Query String: " . ($_SERVER['QUERY_STRING'] ?? 'N/A'));
    error_log("GET completo: " . json_encode($_GET));
    error_log("Mode: '$mode'");
    error_log("Token recibido: '$token'");
    error_log("Token esperado: '$verifyToken'");
    error_log("Challenge: '$challenge'");
    error_log("═══════════════════════════════════════");

    if ($mode === 'subscribe' && $token === $verifyToken) {
        // Verificación exitosa
        error_log("✅ Verificación exitosa del webhook");
        http_response_code(200);
        echo $challenge;
        exit;
    } else {
        // Verificación fallida
        error_log("❌ Verificación fallida - Mode: $mode, Token coincide: " . ($token === $verifyToken ? 'Sí' : 'No'));
        http_response_code(403);
        echo json_encode(['error' => 'Verification failed']);
        exit;
    }
}

// Manejar eventos del webhook (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log inmediato cuando se recibe un POST
    error_log("═══════════════════════════════════════");
    error_log("📨 POST RECIBIDO EN WEBHOOK");
    error_log("Timestamp: " . date('Y-m-d H:i:s'));
    error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));
    error_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
    error_log("X-Hub-Signature-256: " . ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? 'NO PRESENTE'));
    error_log("APP_ENV: " . (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'not set')));
    error_log("═══════════════════════════════════════");
    
    try {
        // Obtener el cuerpo de la petición
        $rawBody = file_get_contents('php://input');
        error_log("Tamaño del body: " . strlen($rawBody) . " bytes");
        
        if (empty($rawBody)) {
            error_log("⚠️ ADVERTENCIA: Body vacío recibido");
        }
        
        $data = json_decode($rawBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("❌ ERROR: JSON inválido - " . json_last_error_msg());
            error_log("Body recibido: " . substr($rawBody, 0, 500));
        } else {
            error_log("✅ JSON válido recibido");
            error_log("Object type: " . ($data['object'] ?? 'unknown'));
        }

        // Verificar la firma del webhook (OBLIGATORIO en producción)
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        $isProduction = getenv('APP_ENV') === 'production' || $_ENV['APP_ENV'] === 'production';
        
        if (!empty($signature)) {
            $whatsappService = new \BotAlojamientos\Services\WhatsAppService();
            // Remover el prefijo "sha256=" de la firma
            $signature = str_replace('sha256=', '', $signature);
            if (!$whatsappService->verifyWebhookSignature($rawBody, $signature)) {
                error_log("❌ Firma del webhook inválida");
                http_response_code(403);
                echo json_encode(['error' => 'Invalid signature']);
                exit;
            } else {
                error_log("✅ Firma del webhook válida");
            }
        } elseif ($isProduction) {
            // En producción, la firma es obligatoria
            error_log("❌ No se recibió firma del webhook en producción");
            http_response_code(403);
            echo json_encode(['error' => 'Missing signature']);
            exit;
        } else {
            error_log("⚠️ No se recibió firma del webhook (modo desarrollo)");
        }

        // Log del evento recibido (sin información sensible)
        $isProduction = getenv('APP_ENV') === 'production' || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
        if (!$isProduction) {
            // Solo en desarrollo: log completo
            error_log("Evento recibido: " . json_encode($data));
        } else {
            // En producción: log mínimo (sin datos sensibles)
            error_log("Evento recibido: object=" . ($data['object'] ?? 'unknown'));
        }

        // Procesar el evento
        if (isset($data['object']) && $data['object'] === 'whatsapp_business_account') {
            foreach ($data['entry'] ?? [] as $entry) {
                $changes = $entry['changes'] ?? [];
                
                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];
                    
                    // Procesar mensajes
                    if (isset($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            // Solo procesar mensajes de texto entrantes
                            if (isset($message['type']) && $message['type'] === 'text') {
                                $from = $message['from'] ?? '';
                                $body = $message['text']['body'] ?? '';
                                $messageId = $message['id'] ?? '';

                                if (!empty($from) && !empty($body)) {
                                    error_log("═══════════════════════════════════════");
                                    error_log("📨 MENSAJE RECIBIDO");
                                    error_log("De: $from");
                                    error_log("Texto: $body");
                                    error_log("ID: $messageId");
                                    error_log("═══════════════════════════════════════");

                                    try {
                                        // Procesar el mensaje con el bot
                                        $bot = new WhatsAppBot();
                                        error_log("🤖 Iniciando procesamiento del mensaje...");
                                        $bot->processMessage($from, $body);
                                        error_log("✅ Mensaje procesado correctamente");
                                    } catch (Exception $e) {
                                        error_log("❌ ERROR procesando mensaje: " . $e->getMessage());
                                        error_log("Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
                                        error_log("Stack trace: " . $e->getTraceAsString());
                                    }
                                } else {
                                    error_log("⚠️ Mensaje recibido pero falta 'from' o 'body'");
                                    error_log("From: " . ($from ?? 'vacío'));
                                    error_log("Body: " . ($body ?? 'vacío'));
                                }
                            }
                        }
                    }

                    // Procesar estados de mensajes (opcional, para tracking)
                    if (isset($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            $messageId = $status['id'] ?? '';
                            $statusType = $status['status'] ?? '';
                            error_log("Estado del mensaje $messageId: $statusType");
                        }
                    }
                }
            }
        }

        // Responder a Meta (debe ser 200 OK)
        http_response_code(200);
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        error_log("Error en webhook: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
