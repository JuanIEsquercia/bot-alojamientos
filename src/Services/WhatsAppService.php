<?php

namespace BotAlojamientos\Services;

use BotAlojamientos\Config\Config;
use Exception;

class WhatsAppService
{
    private string $accessToken;
    private string $phoneNumberId;
    private string $apiVersion = 'v22.0';
    private string $graphApiUrl = 'https://graph.facebook.com';

    public function __construct()
    {
        $config = Config::getInstance();
        $this->accessToken = $config->get('whatsapp.access_token');
        $this->phoneNumberId = $config->get('whatsapp.phone_number_id');

        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            throw new Exception('Credenciales de WhatsApp no configuradas');
        }
    }

    /**
     * Envía un mensaje de WhatsApp usando la API de Meta
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            // El número 'to' viene del formato de Meta (ej: 5493794267780 o +5493794267780)
            // Meta espera el número completo SIN el símbolo + para enviar mensajes
            // IMPORTANTE: Meta elimina el 9 cuando detecta el patrón 549
            // Ejemplo: 5493794267780 -> Meta lo almacena como 543794267780
            
            // Remover todo excepto números
            $to = preg_replace('/[^0-9]/', '', $to);
            
            // Si el número empieza con 549, eliminar el 9 (Meta lo hace automáticamente)
            // 5493794267780 -> 543794267780
            if (strlen($to) >= 3 && substr($to, 0, 3) === '549') {
                $to = '54' . substr($to, 3);
                error_log("⚠️ Número ajustado: eliminado el 9 del patrón 549. Nuevo formato: $to");
            }

            $url = "{$this->graphApiUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            // Detectar si estamos en producción
            $isProduction = getenv('APP_ENV') === 'production' || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
            
            if (!$isProduction) {
                // Solo en desarrollo: logs detallados
                error_log("═══════════════════════════════════════");
                error_log("📤 ENVIANDO MENSAJE");
                error_log("A: $to");
                error_log("URL: $url");
                error_log("Token: " . substr($this->accessToken, 0, 20) . "...");
                error_log("Datos: " . json_encode($data, JSON_UNESCAPED_UNICODE));
                error_log("═══════════════════════════════════════");
            } else {
                // En producción: logs mínimos (sin tokens ni datos sensibles)
                error_log("📤 Enviando mensaje a: " . substr($to, -4) . " (últimos 4 dígitos)");
            }

            $ch = curl_init($url);
            
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json'
                ]
            ];
            
            // En producción, habilitar verificación SSL
            if ($isProduction) {
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
            } else {
                // Solo en desarrollo: deshabilitar verificación SSL (Windows local)
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
                error_log("⚠️ SSL verification deshabilitada (modo desarrollo)");
            }
            
            curl_setopt_array($ch, $curlOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            error_log("═══════════════════════════════════════");
            error_log("📥 RESPUESTA DE META API");
            error_log("HTTP Code: $httpCode");
            error_log("Respuesta: $response");
            if ($error) {
                error_log("❌ Error cURL: $error");
            }
            error_log("═══════════════════════════════════════");

            if ($error) {
                error_log("❌ Error cURL enviando mensaje: $error");
                return false;
            }

            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['messages'][0]['id'])) {
                    error_log("✅ Mensaje enviado correctamente. ID: " . $responseData['messages'][0]['id']);
                    return true;
                } else {
                    error_log("⚠️ Respuesta 200 pero sin message ID. Respuesta: " . json_encode($responseData));
                }
            } else {
                $responseData = json_decode($response, true);
                $errorMsg = $responseData['error']['message'] ?? $response;
                error_log("❌ Error enviando mensaje. Status: $httpCode, Error: $errorMsg");
            }

            return false;

        } catch (Exception $e) {
            error_log("❌ Excepción enviando mensaje de WhatsApp: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Normaliza el número de teléfono para la API de Meta
     */
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    /**
     * Extrae el número de teléfono del formato de Meta
     * Toma los últimos 10 dígitos del número (ignora código de país)
     */
    public function extractPhoneNumber(string $metaPhone): string
    {
        // Remover todo excepto números
        $digits = preg_replace('/[^0-9]/', '', $metaPhone);
        
        // Tomar los últimos 10 dígitos
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }
        
        // Si tiene menos de 10 dígitos, devolverlo tal cual
        return $digits;
    }

    /**
     * Verifica la firma del webhook de Meta
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $config = Config::getInstance();
        $secret = $config->get('whatsapp.webhook_secret');

        if (empty($secret)) {
            // En producción, el secret es obligatorio
            $isProduction = getenv('APP_ENV') === 'production' || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
            if ($isProduction) {
                error_log("❌ WHATSAPP_WEBHOOK_SECRET no configurado en producción");
                return false;
            }
            // Solo en desarrollo: permitir sin secret
            error_log("⚠️ WHATSAPP_WEBHOOK_SECRET no configurado (modo desarrollo)");
            return true;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
