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
            $textoLetras = trim($soloLetras);
            
            if (strlen($textoLetras) < 3) {
                return [
                    'tipo' => 'error',
                    'valor' => '',
                    'mensaje' => 'El nombre es muy corto. Escribí al menos 3 letras.'
                ];
            }
            
            return [
                'tipo' => 'nombre',
                'valor' => $textoLetras,
                'mensaje' => ''
            ];
        }
        
        // CASO C: Alfanumérico (mezcla)
        if ($tieneLetras && $tieneNumeros) {
            // Intentar ambos: primero números, luego letras
            $longitudNumeros = strlen($soloNumeros);
            
            $resultado = [
                'tipo' => 'mixto',
                'valor' => [
                    'numeros' => $soloNumeros,
                    'letras' => trim($soloLetras)
                ],
                'mensaje' => ''
            ];
            
            // Determinar tipo de número
            if ($longitudNumeros >= 7 && $longitudNumeros <= 9) {
                $resultado['tipo_numeros'] = 'dni';
            } elseif ($longitudNumeros >= 10) {
                $resultado['tipo_numeros'] = 'telefono';
            }
            
            return $resultado;
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

