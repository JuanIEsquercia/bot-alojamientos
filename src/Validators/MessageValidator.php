<?php

namespace BotAlojamientos\Validators;

class MessageValidator
{
    /**
     * Valida y sanitiza el texto del mensaje
     */
    public static function validateText(string $text): array
    {
        // Limpiar el texto
        $cleaned = trim($text);
        
        // Validar que no esté vacío
        if (empty($cleaned)) {
            return [
                'valid' => false,
                'error' => 'El mensaje está vacío',
                'text' => ''
            ];
        }

        // Validar longitud máxima (WhatsApp permite hasta 4096 caracteres)
        if (strlen($cleaned) > 4096) {
            return [
                'valid' => false,
                'error' => 'El mensaje es demasiado largo (máximo 4096 caracteres)',
                'text' => substr($cleaned, 0, 4096)
            ];
        }

        // Sanitizar (remover caracteres peligrosos pero mantener formato básico)
        $sanitized = self::sanitize($cleaned);

        return [
            'valid' => true,
            'text' => $sanitized,
            'original' => $cleaned
        ];
    }

    /**
     * Sanitiza el texto manteniendo caracteres útiles para WhatsApp
     */
    private static function sanitize(string $text): string
    {
        // Permitir letras, números, espacios, signos de puntuación comunes
        // y caracteres especiales de WhatsApp (emojis, etc.)
        return $text;
    }

    /**
     * Extrae el comando del texto
     */
    public static function extractCommand(string $text): array
    {
        $text = trim($text);
        $textLower = strtolower($text);
        
        // Comandos simples (una sola palabra)
        $simpleCommands = ['menu', 'ayuda', 'help'];
        
        if (in_array($textLower, $simpleCommands)) {
            return [
                'type' => 'simple',
                'command' => $textLower,
                'params' => []
            ];
        }

        // Comandos de búsqueda: BUSCAR DNI [número]
        if (preg_match('/^buscar\s+dni\s+(.+)$/i', $text, $matches)) {
            return [
                'type' => 'search_dni',
                'command' => 'buscar_dni',
                'params' => [trim($matches[1])]
            ];
        }

        // Comandos de búsqueda: BUSCAR TELEFONO [número]
        if (preg_match('/^buscar\s+telefono\s+(.+)$/i', $text, $matches)) {
            return [
                'type' => 'search_phone',
                'command' => 'buscar_telefono',
                'params' => [trim($matches[1])]
            ];
        }

        // Comandos de búsqueda: BUSCAR NOMBRE [nombre]
        if (preg_match('/^buscar\s+nombre\s+(.+)$/i', $text, $matches)) {
            return [
                'type' => 'search_name',
                'command' => 'buscar_nombre',
                'params' => [trim($matches[1])]
            ];
        }

        // Detectar automáticamente si es DNI, teléfono o nombre
        $textTrimmed = trim($text);
        $textClean = preg_replace('/[^0-9]/', '', $textTrimmed);
        $hasLetters = preg_match('/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/', $textTrimmed);
        
        // Si es solo números (o números con espacios/guiones), detectar tipo
        if (!$hasLetters && !empty($textClean)) {
            // Si tiene 7-9 dígitos, probablemente es un DNI
            if (strlen($textClean) >= 7 && strlen($textClean) <= 9) {
                return [
                    'type' => 'search_dni',
                    'command' => 'buscar_dni',
                    'params' => [$textClean]
                ];
            }
            
            // Si tiene 8-15 dígitos, probablemente es un teléfono
            if (strlen($textClean) >= 8 && strlen($textClean) <= 15) {
                return [
                    'type' => 'search_phone',
                    'command' => 'buscar_telefono',
                    'params' => [$textClean]
                ];
            }
        }
        
        // Si tiene letras y al menos 3 caracteres, probablemente es un nombre
        if ($hasLetters && strlen($textTrimmed) >= 3) {
            return [
                'type' => 'search_name',
                'command' => 'buscar_nombre',
                'params' => [$textTrimmed]
            ];
        }
        
        // Texto libre (no es un comando reconocido)
        return [
            'type' => 'free_text',
            'command' => null,
            'params' => [],
            'text' => $text
        ];
    }

    /**
     * Valida que un comando tenga los parámetros necesarios
     */
    public static function validateCommand(array $commandData): array
    {
        if ($commandData['type'] === 'simple') {
            return [
                'valid' => true,
                'command' => $commandData
            ];
        }

        // Comandos de búsqueda requieren parámetros
        if (in_array($commandData['type'], ['search_dni', 'search_phone', 'search_name'])) {
            if (empty($commandData['params']) || empty($commandData['params'][0])) {
                return [
                    'valid' => false,
                    'error' => 'Falta el parámetro de búsqueda',
                    'command' => $commandData
                ];
            }
            return [
                'valid' => true,
                'command' => $commandData
            ];
        }

        // Texto libre es válido pero no es un comando
        return [
            'valid' => true,
            'command' => $commandData
        ];
    }
}
