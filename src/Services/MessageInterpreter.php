<?php

namespace BotAlojamientos\Services;

class MessageInterpreter
{
    /**
     * Interpreta el mensaje del usuario y determina qué buscar
     * 
     * @param string $texto Texto del mensaje del usuario
     * @return array ['tipo' => 'dni'|'telefono'|'nombre'|'saludo'|'error', 'valor' => string, 'mensaje' => string]
     */
    public static function interpretarMensaje(string $texto): array
    {
        // Limpieza agresiva
        $textoLimpiado = self::limpiarTexto($texto);
        
        if (empty($textoLimpiado)) {
            return [
                'tipo' => 'error',
                'valor' => '',
                'mensaje' => 'No pude entender tu mensaje. Escribí un Nombre, DNI o Teléfono.'
            ];
        }
        
        // Detectar saludos
        if (self::esSaludo($textoLimpiado)) {
            return [
                'tipo' => 'saludo',
                'valor' => '',
                'mensaje' => "¡Hola! 👋 Soy el Asistente de Seguridad de Alojamiento Corrientes.\n\n"
                    . "Antes de entregar la llave 🔑, consultá si tu futuro huésped tiene reportes por falta de pago o incidentes en nuestra comunidad.\n\n"
                    . "👉 Escribí acá abajo el NOMBRE, DNI o TELÉFONO del inquilino para verificarlo.\n\n\n\n"
                    . "💡 Tip: Si tenés el DNI, la búsqueda es más exacta. Si solo tenés el nombre, te mostraré las posibles coincidencias."
            ];
        }
        
        // Validar longitud del mensaje (máximo 5 palabras para búsqueda por nombre)
        $palabras = preg_split('/\s+/', trim($textoLimpiado));
        $cantidadPalabras = count(array_filter($palabras, function($palabra) {
            return !empty(trim($palabra));
        }));
        
        // Si tiene más de 5 palabras y no es solo números, pedir que simplifique
        if ($cantidadPalabras > 5) {
            // Verificar si es solo números (DNI o teléfono largo)
            $soloNumeros = preg_replace('/[^0-9]/', '', $textoLimpiado);
            $tieneSoloNumeros = !empty($soloNumeros) && strlen($soloNumeros) >= 7;
            
            // Si no es solo números, pedir que simplifique
            if (!$tieneSoloNumeros) {
                return [
                    'tipo' => 'error',
                    'valor' => '',
                    'mensaje' => "📝 *Mensaje muy largo*\n\n"
                        . "Enviá solamente el *nombre* para facilitar la búsqueda.\n\n"
                        . "Podés escribir:\n"
                        . "• Nombre completo (ej: Juan Pérez)\n"
                        . "• Un solo nombre (ej: Juan)\n\n"
                        . "Yo buscaré en la base de datos 🔍"
                ];
            }
        }
        
        // Extraer números y letras
        $soloNumeros = preg_replace('/[^0-9]/', '', $textoLimpiado);
        $soloLetras = preg_replace('/[^a-z\s]/', '', $textoLimpiado);
        $tieneLetras = !empty(trim($soloLetras));
        $tieneNumeros = !empty($soloNumeros);
        
        // CASO A: Solo números
        if ($tieneNumeros && !$tieneLetras) {
            $longitud = strlen($soloNumeros);
            
            if ($longitud < 6) {
                return [
                    'tipo' => 'error',
                    'valor' => '',
                    'mensaje' => 'El número es muy corto, por favor revisalo.'
                ];
            }
            
            if ($longitud >= 7 && $longitud <= 8) {
                return [
                    'tipo' => 'dni',
                    'valor' => $soloNumeros,
                    'mensaje' => ''
                ];
            }
            
            if ($longitud >= 10) {
                return [
                    'tipo' => 'telefono',
                    'valor' => $soloNumeros,
                    'mensaje' => ''
                ];
            }
            
            // Entre 6 y 9 dígitos (puede ser DNI o teléfono corto)
            if ($longitud == 9) {
                return [
                    'tipo' => 'dni',
                    'valor' => $soloNumeros,
                    'mensaje' => ''
                ];
            }
        }
        
        // CASO B: Solo letras (o letras + espacios)
        if ($tieneLetras && !$tieneNumeros) {
            // Extraer solo palabras relevantes (4+ caracteres)
            $palabras = preg_split('/\s+/', trim($soloLetras));
            $palabrasRelevantes = array_filter($palabras, function($palabra) {
                return strlen(trim($palabra)) >= 4;
            });
            
            // Si no hay palabras de 4+ caracteres, usar todas las palabras de 3+
            if (empty($palabrasRelevantes)) {
                $palabrasRelevantes = array_filter($palabras, function($palabra) {
                    return strlen(trim($palabra)) >= 3;
                });
            }
            
            $textoFinal = implode(' ', $palabrasRelevantes);
            
            if (empty($textoFinal) || strlen($textoFinal) < 3) {
                return [
                    'tipo' => 'error',
                    'valor' => '',
                    'mensaje' => 'El nombre es muy corto. Escribí al menos 3 letras.'
                ];
            }
            
            return [
                'tipo' => 'nombre',
                'valor' => $textoFinal,
                'mensaje' => ''
            ];
        }
        
        // CASO C: Alfanumérico (mezcla)
        if ($tieneLetras && $tieneNumeros) {
            $longitudNumeros = strlen($soloNumeros);
            
            // PRIORIZAR NÚMEROS: Si hay un DNI o teléfono válido, ignorar el texto
            if ($longitudNumeros >= 7 && $longitudNumeros <= 9) {
                // DNI válido encontrado - priorizar sobre el texto
                return [
                    'tipo' => 'dni',
                    'valor' => $soloNumeros,
                    'mensaje' => ''
                ];
            }
            
            if ($longitudNumeros >= 10) {
                // Teléfono válido encontrado - priorizar sobre el texto
                return [
                    'tipo' => 'telefono',
                    'valor' => $soloNumeros,
                    'mensaje' => ''
                ];
            }
            
            // Si los números no son válidos (muy cortos), buscar por nombre
            // Extraer solo palabras relevantes (4+ caracteres)
            $palabras = preg_split('/\s+/', trim($soloLetras));
            $palabrasRelevantes = array_filter($palabras, function($palabra) {
                return strlen(trim($palabra)) >= 4;
            });
            
            // Si no hay palabras de 4+ caracteres, usar todas las palabras de 3+
            if (empty($palabrasRelevantes)) {
                $palabrasRelevantes = array_filter($palabras, function($palabra) {
                    return strlen(trim($palabra)) >= 3;
                });
            }
            
            $textoFinal = implode(' ', $palabrasRelevantes);
            if (strlen($textoFinal) >= 3) {
                return [
                    'tipo' => 'nombre',
                    'valor' => $textoFinal,
                    'mensaje' => ''
                ];
            }
            
            // Si tampoco hay nombre válido, error
            return [
                'tipo' => 'error',
                'valor' => '',
                'mensaje' => 'No pude entender tu mensaje. Escribí un Nombre, DNI o Teléfono.'
            ];
        }
        
        // No se pudo determinar
        return [
            'tipo' => 'error',
            'valor' => '',
            'mensaje' => 'No pude entender tu mensaje. Escribí un Nombre, DNI o Teléfono.'
        ];
    }
    
    /**
     * Limpia el texto: minúsculas, sin acentos, sin emojis
     */
    private static function limpiarTexto(string $texto): string
    {
        // Convertir a minúsculas
        $texto = strtolower(trim($texto));
        
        // Reemplazar acentos
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $texto
        );
        
        // Eliminar emojis y caracteres especiales, dejar solo letras, números y espacios
        $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);
        
        // Normalizar espacios múltiples
        $texto = preg_replace('/\s+/', ' ', $texto);
        
        return trim($texto);
    }
    
    /**
     * Detecta si el texto es un saludo
     */
    private static function esSaludo(string $texto): bool
    {
        $saludos = [
            'hola', 'holi', 'holis', 'hola como estas', 'hola como estas',
            'buen dia', 'buenos dias', 'buen dia', 'buenas tardes', 'buenas noches',
            'gracias', 'gracias por todo', 'muchas gracias',
            'chau', 'chao', 'adios', 'hasta luego',
            'buen dia', 'buenos dias'
        ];
        
        $texto = trim($texto);
        
        // Verificar si es exactamente un saludo
        if (in_array($texto, $saludos)) {
            return true;
        }
        
        // Verificar si empieza con saludo
        foreach ($saludos as $saludo) {
            if (strpos($texto, $saludo) === 0 && strlen($texto) <= strlen($saludo) + 10) {
                return true;
            }
        }
        
        return false;
    }
}

