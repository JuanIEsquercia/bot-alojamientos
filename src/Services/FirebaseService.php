<?php

namespace BotAlojamientos\Services;

use BotAlojamientos\Config\Config;
use Exception;

class FirebaseService
{
    private string $projectId;
    private string $credentialsPath;
    private ?array $credentials = null;
    private ?string $accessToken = null;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->projectId = $config->get('firebase.project_id');
        $this->credentialsPath = $config->get('firebase.credentials_path');

        if (empty($this->projectId)) {
            throw new Exception('FIREBASE_PROJECT_ID no está configurado');
        }

        // Validar que GOOGLE_APPLICATION_CREDENTIALS esté configurado
        if (empty($this->credentialsPath)) {
            throw new Exception('GOOGLE_APPLICATION_CREDENTIALS no está configurado');
        }

        // Validar que el archivo exista y la ruta sea real (seguridad básica)
        $realPath = realpath($this->credentialsPath);
        if ($realPath === false || !file_exists($realPath)) {
            throw new Exception('Ruta de credenciales inválida o no accesible');
        }

        // Cargar credenciales
        $this->loadCredentials();
    }

    /**
     * Carga las credenciales desde el archivo JSON
     */
    private function loadCredentials(): void
    {
        // Validar que el archivo no sea demasiado grande (protección DoS)
        $fileSize = filesize($this->credentialsPath);
        if ($fileSize > 10240) { // Máximo 10KB
            throw new Exception('Archivo de credenciales demasiado grande');
        }
        
        $jsonContent = file_get_contents($this->credentialsPath);
        
        // Validar que el contenido no esté vacío
        if (empty($jsonContent)) {
            throw new Exception('Archivo de credenciales vacío');
        }
        
        $this->credentials = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al leer las credenciales de Firebase: ' . json_last_error_msg());
        }
        
        // Validar estructura básica de credenciales
        if (empty($this->credentials['client_email']) || empty($this->credentials['private_key'])) {
            throw new Exception('Credenciales de Firebase incompletas');
        }
    }

    /**
     * Obtiene un token de acceso OAuth2 para autenticarse con Firestore
     */
    private function getAccessToken(): string
    {
        // Si ya tenemos un token válido, usarlo
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        try {
            // Crear JWT para solicitar token
            $jwt = $this->createJWT();
        } catch (Exception $e) {
            throw new Exception('Error al crear JWT: ' . $e->getMessage());
        }
        
        // Solicitar token de acceso
        $ch = curl_init('https://oauth2.googleapis.com/token');
        
        // Detectar si estamos en producción
        $isProduction = getenv('APP_ENV') === 'production' || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
        
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
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
        }
        
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Error cURL al obtener token: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error_description'] ?? $errorData['error'] ?? $response;
            throw new Exception("Error al obtener token de acceso (HTTP $httpCode): $errorMsg");
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'] ?? null;

        if (empty($this->accessToken)) {
            throw new Exception('No se pudo obtener el token de acceso. Respuesta: ' . $response);
        }

        return $this->accessToken;
    }

    /**
     * Crea un JWT para autenticación OAuth2
     */
    private function createJWT(): string
    {
        if (empty($this->credentials['client_email'])) {
            throw new Exception('client_email no encontrado en las credenciales');
        }

        if (empty($this->credentials['private_key'])) {
            throw new Exception('private_key no encontrado en las credenciales');
        }

        $now = time();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = '';
        $privateKey = $this->credentials['private_key'];
        
        // Verificar que OpenSSL puede usar la clave
        $keyResource = openssl_pkey_get_private($privateKey);
        if ($keyResource === false) {
            $opensslError = openssl_error_string();
            throw new Exception('Error al procesar la clave privada: ' . ($opensslError ?: 'Clave inválida'));
        }

        $dataToSign = $base64UrlHeader . '.' . $base64UrlPayload;
        $signResult = openssl_sign($dataToSign, $signature, $keyResource, OPENSSL_ALGO_SHA256);
        
        // En PHP 8.0+, los recursos OpenSSL se liberan automáticamente
        // openssl_free_key() está deprecado, no es necesario llamarlo

        if (!$signResult) {
            $opensslError = openssl_error_string();
            throw new Exception('Error al firmar JWT: ' . ($opensslError ?: 'Error desconocido'));
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    /**
     * Codifica en Base64 URL-safe
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Realiza una consulta a la API REST de Firestore
     */
    private function queryFirestore(string $collection, array $filters = [], int $limit = 1000): array
    {
        $token = $this->getAccessToken();
        
        // Si hay filtros, usar runQuery (más complejo pero necesario para WHERE)
        if (!empty($filters)) {
            return $this->runQuery($collection, $filters);
        }

        // Sin filtros, usar listDocuments (más simple)
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}?pageSize={$limit}";

        $ch = curl_init($url);
        
        // Detectar si estamos en producción
        $isProduction = getenv('APP_ENV') === 'production' || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
        
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
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
        }
        
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Error en consulta Firestore: HTTP $httpCode - $response");
            return [];
        }

        $data = json_decode($response, true);
        $documents = [];

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                $docData = $this->parseFirestoreDocument($doc);
                $documents[] = $docData;
            }
        }

        return $documents;
    }

    /**
     * Ejecuta una query con filtros usando runQuery
     */
    private function runQuery(string $collection, array $filters): array
    {
        $token = $this->getAccessToken();
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";

        // Construir la query estructurada
        $query = [
            'structuredQuery' => [
                'from' => [['collectionId' => $collection]]
            ]
        ];

        // Agregar filtros WHERE
        if (!empty($filters)) {
            $fieldFilters = [];
            foreach ($filters as $field => $value) {
                $fieldFilters[] = [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => $field],
                        'op' => 'EQUAL',
                        'value' => ['stringValue' => $value]
                    ]
                ];
            }
            
            if (count($fieldFilters) === 1) {
                $query['structuredQuery']['where'] = $fieldFilters[0];
            } else {
                // Múltiples filtros con AND
                $query['structuredQuery']['where'] = [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => $fieldFilters
                    ]
                ];
            }
        }

        $ch = curl_init($url);
        
        // Detectar si estamos en producción
        $isProduction = getenv('APP_ENV') === 'production' || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
        
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($query),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
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
        }
        
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Error en runQuery Firestore: HTTP $httpCode - $response");
            return [];
        }

        $data = json_decode($response, true);
        $documents = [];

        if (is_array($data)) {
            foreach ($data as $item) {
                if (isset($item['document'])) {
                    $docData = $this->parseFirestoreDocument($item['document']);
                    $documents[] = $docData;
                }
            }
        }

        return $documents;
    }

    /**
     * Parsea un documento de Firestore a un array PHP
     */
    private function parseFirestoreDocument(array $doc): array
    {
        $result = [];
        
        // Extraer ID del documento
        if (isset($doc['name'])) {
            $parts = explode('/', $doc['name']);
            $result['id'] = end($parts);
        }

        // Parsear campos
        if (isset($doc['fields'])) {
            foreach ($doc['fields'] as $key => $field) {
                $result[$key] = $this->parseFirestoreValue($field);
            }
        }

        return $result;
    }

    /**
     * Parsea un valor de Firestore
     */
    private function parseFirestoreValue(array $field): mixed
    {
        // Firestore puede tener diferentes tipos
        if (isset($field['stringValue'])) {
            return $field['stringValue'];
        } elseif (isset($field['integerValue'])) {
            return (int)$field['integerValue'];
        } elseif (isset($field['doubleValue'])) {
            return (float)$field['doubleValue'];
        } elseif (isset($field['booleanValue'])) {
            return (bool)$field['booleanValue'];
        } elseif (isset($field['timestampValue'])) {
            return $field['timestampValue'];
        } elseif (isset($field['nullValue'])) {
            return null;
        }
        
        return null;
    }

    /**
     * Valida si un número de teléfono está registrado como usuario
     */
    public function validateUser(string $phoneNumber): ?array
    {
        try {
            $normalizedPhone = $this->normalizePhoneForUsers($phoneNumber);
            error_log("🔍 Validando usuario - Número recibido: $phoneNumber, Normalizado: $normalizedPhone");
            
            if (empty($normalizedPhone)) {
                error_log("⚠️ Número normalizado vacío. Número original: $phoneNumber");
                return null;
            }
            
            // Obtener todos los usuarios y filtrar (Firestore REST API tiene limitaciones)
            $users = $this->queryFirestore('users');
            error_log("📊 Total de usuarios obtenidos de Firebase: " . count($users));
            
            if (empty($users)) {
                error_log("⚠️ No se encontraron usuarios en la colección 'users'");
                return null;
            }
            
            $comparaciones = [];
            foreach ($users as $index => $user) {
                $userPhoneRaw = $user['telefono'] ?? '';
                $userPhone = $this->normalizePhoneForUsers($userPhoneRaw);
                
                $comparaciones[] = "Usuario #$index: Raw='$userPhoneRaw', Normalizado='$userPhone'";
                
                if ($userPhone === $normalizedPhone) {
                    error_log("✅ Usuario encontrado! Email: " . ($user['email'] ?? 'N/A') . ", Status: " . ($user['status'] ?? 'N/A'));
                    return $user;
                }
            }
            
            // Log detallado de todas las comparaciones (solo si no se encontró)
            error_log("❌ Usuario NO encontrado. Comparaciones realizadas:");
            foreach ($comparaciones as $comp) {
                error_log("   - $comp");
            }
            error_log("   - Buscado: '$normalizedPhone'");

            return null;
        } catch (Exception $e) {
            error_log("❌ ERROR validando usuario: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Busca reportes por DNI (búsqueda flexible)
     */
    public function searchByDni(string $dni): array
    {
        try {
            $normalizedDni = preg_replace('/[^0-9]/', '', $dni);
            
            // Aceptar DNI de 7 a 9 dígitos (más flexible)
            if (strlen($normalizedDni) < 7 || strlen($normalizedDni) > 9) {
                error_log("⚠️ DNI fuera de rango válido: $normalizedDni (longitud: " . strlen($normalizedDni) . ")");
                return [];
            }

            // Intentar búsqueda exacta primero
            $results = $this->queryFirestore('huespedesReportados', ['dni' => $normalizedDni]);
            
            // Si no hay resultados, buscar en todos y comparar (por si hay espacios o formato diferente)
            if (empty($results)) {
                $allReports = $this->queryFirestore('huespedesReportados');
                foreach ($allReports as $report) {
                    $reportDni = preg_replace('/[^0-9]/', '', $report['dni'] ?? '');
                    if ($reportDni === $normalizedDni) {
                        $results[] = $report;
                    }
                }
            }

            return $results;
        } catch (Exception $e) {
            error_log("Error buscando por DNI: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca reportes por teléfono (búsqueda flexible)
     */
    public function searchByPhone(string $phone): array
    {
        try {
            $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
            
            // Tomar los últimos 10 dígitos para búsqueda (como hacemos con usuarios)
            if (strlen($normalizedPhone) >= 10) {
                $normalizedPhone = substr($normalizedPhone, -10);
            } elseif (strlen($normalizedPhone) < 10) {
                error_log("⚠️ Teléfono muy corto para búsqueda: $normalizedPhone");
                return [];
            }

            // Obtener todos los reportes y filtrar por los últimos 10 dígitos
            $reports = $this->queryFirestore('huespedesReportados');
            
            $results = [];
            foreach ($reports as $report) {
                $reportPhone = $this->normalizePhoneForUsers($report['telefono'] ?? '');
                if ($reportPhone === $normalizedPhone) {
                    $results[] = $report;
                }
            }

            return $results;
        } catch (Exception $e) {
            error_log("Error buscando por teléfono: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca reportes por nombre (búsqueda parcial con múltiples palabras)
     */
    public function searchByName(string $name): array
    {
        try {
            $nameClean = $this->cleanText($name);
            
            if (empty($nameClean) || strlen($nameClean) < 3) {
                return [];
            }

            // Dividir en palabras y filtrar solo las relevantes (4+ caracteres)
            $words = preg_split('/\s+/', $nameClean);
            $words = array_filter($words, function($word) {
                $word = trim($word);
                return strlen($word) >= 4; // Solo palabras de 4+ caracteres
            });
            
            // Si no hay palabras de 4+, usar palabras de 3+ como fallback
            if (empty($words)) {
                $words = preg_split('/\s+/', $nameClean);
                $words = array_filter($words, function($word) {
                    $word = trim($word);
                    return strlen($word) >= 3;
                });
            }

            if (empty($words)) {
                return [];
            }

            // Obtener todos y filtrar en memoria (Firestore no soporta búsqueda parcial)
            $reports = $this->queryFirestore('huespedesReportados');
            
            $results = [];
            foreach ($reports as $report) {
                $reportName = $this->cleanText($report['nombre'] ?? '');
                
                // Verificar que todas las palabras estén presentes (sin importar orden)
                $allWordsFound = true;
                foreach ($words as $word) {
                    if (strpos($reportName, $word) === false) {
                        $allWordsFound = false;
                        break;
                    }
                }
                
                if ($allWordsFound) {
                    $results[] = $report;
                }
            }

            return $results;
        } catch (Exception $e) {
            error_log("Error buscando por nombre: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpia texto: minúsculas, sin acentos, sin emojis
     */
    private function cleanText(string $text): string
    {
        // Convertir a minúsculas
        $text = strtolower(trim($text));
        
        // Reemplazar acentos
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $text
        );
        
        // Eliminar emojis y caracteres especiales, dejar solo letras, números y espacios
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        
        // Normalizar espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Normaliza el número de teléfono para búsqueda en la colección 'users'
     * Toma los últimos 10 dígitos para comparar (ignora código de país y formato)
     */
    private function normalizePhoneForUsers(string $phoneNumber): string
    {
        // Validar entrada
        if (empty($phoneNumber) || !is_string($phoneNumber)) {
            return '';
        }
        
        // Remover todo excepto números
        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Si no hay dígitos, retornar vacío
        if (empty($digits)) {
            return '';
        }
        
        // Tomar los últimos 10 dígitos
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }
        
        // Si tiene menos de 10 dígitos, devolverlo tal cual
        return $digits;
    }
}
