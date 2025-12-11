<?php

namespace BotAlojamientos\Config;

class Config
{
    private static ?Config $instance = null;
    private array $config;

    private function __construct()
    {
        // Cargar variables de entorno desde .env manualmente
        $this->loadEnv(__DIR__ . '/../../.env');

        $this->config = [
            'whatsapp' => [
                'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? getenv('WHATSAPP_ACCESS_TOKEN'),
                'phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? getenv('WHATSAPP_PHONE_NUMBER_ID'),
                'business_account_id' => $_ENV['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? getenv('WHATSAPP_BUSINESS_ACCOUNT_ID'),
                'webhook_verify_token' => $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
                'webhook_secret' => $_ENV['WHATSAPP_WEBHOOK_SECRET'] ?? getenv('WHATSAPP_WEBHOOK_SECRET'),
            ],
            'firebase' => [
                'project_id' => $_ENV['FIREBASE_PROJECT_ID'] ?? getenv('FIREBASE_PROJECT_ID'),
                'credentials_path' => $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? getenv('GOOGLE_APPLICATION_CREDENTIALS'),
            ],
        ];
    }

    /**
     * Carga variables de entorno desde archivo .env
     */
    private function loadEnv(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Separar clave y valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si las tiene
                $value = trim($value, '"\'');
                
                // Solo establecer si no está ya definida
                if (!isset($_ENV[$key]) && !getenv($key)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
