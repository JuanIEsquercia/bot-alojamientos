<?php

namespace BotAlojamientos\Bot;

use BotAlojamientos\Services\WhatsAppService;
use BotAlojamientos\Services\FirebaseService;
use BotAlojamientos\Services\MessageInterpreter;
use BotAlojamientos\Validators\MessageValidator;
use Exception;

class WhatsAppBot
{
    private WhatsAppService $whatsappService;
    private FirebaseService $firebaseService;
    private ?array $currentUser = null;

    public function __construct()
    {
        $this->whatsappService = new WhatsAppService();
        $this->firebaseService = new FirebaseService();
    }

    /**
     * Procesa un mensaje entrante de WhatsApp
     */
    public function processMessage(string $from, string $body): void
    {
        try {
            // Validar tamaño de entrada (protección DoS)
            if (strlen($body) > 4096) {
                error_log("⚠️ Mensaje demasiado largo rechazado de: $from");
                $this->sendMessage($from, "El mensaje es demasiado largo. Por favor, envía un mensaje más corto.");
                return;
            }
            
            // PASO 1: Validar y sanitizar el texto del mensaje
            $textValidation = MessageValidator::validateText($body);
            
            if (!$textValidation['valid']) {
                $this->sendMessage(
                    $from,
                    "❌ " . $textValidation['error'] . "\n\n"
                    . "Por favor, envía un mensaje válido."
                );
                return;
            }

            $validatedText = $textValidation['text'];

            // PASO 2: Extraer número de teléfono (últimos 10 dígitos para búsqueda)
            $phoneNumber = $this->whatsappService->extractPhoneNumber($from);
            error_log("Número extraído para búsqueda: $phoneNumber (de: $from)");
            
            // PASO 3: Validar que el usuario esté registrado
            error_log("Buscando usuario en Firebase...");
            $user = $this->firebaseService->validateUser($phoneNumber);
            
            if ($user === null) {
                error_log("❌ Usuario no encontrado para número: $phoneNumber");
                error_log("Intentando enviar mensaje de acceso denegado...");
                try {
                    $this->sendMessage(
                        $from,
                        "🔒 *No tenes una cuenta activa en Alojamiento Corrientes*\n\n"
                        . "Create una y cuando estes aprobado podrás escribirme 😍\n\n"
                        . "🌐 https://www.alojamientocorrientes.com/"
                    );
                    error_log("✅ Mensaje de acceso denegado enviado");
                } catch (Exception $e) {
                    error_log("❌ ERROR al enviar mensaje de acceso denegado: " . $e->getMessage());
                }
                return;
            }
            
            error_log("✅ Usuario encontrado: " . ($user['email'] ?? 'N/A') . " - Estado: " . ($user['status'] ?? 'N/A'));

            // Verificar que el usuario esté activo
            if (isset($user['status']) && $user['status'] !== 'ACTIVO') {
                $this->sendMessage(
                    $from,
                    "⚠️ *Cuenta Inactiva*\n\n"
                    . "Tu cuenta no está activa en este momento.\n\n"
                    . "Estado actual: *" . ($user['status'] ?? 'DESCONOCIDO') . "*\n\n"
                    . "Por favor, contacta con el administrador para activar tu cuenta."
                );
                return;
            }
            
            error_log("✅ Usuario validado y activo: " . ($user['email'] ?? 'N/A'));

            // Usuario válido, guardar en contexto
            $this->currentUser = $user;

            // PASO 4: Interpretar el mensaje usando el nuevo sistema
            error_log("Interpretando mensaje: '$validatedText'");
            $interpretacion = MessageInterpreter::interpretarMensaje($validatedText);
            error_log("Interpretación - Tipo: " . $interpretacion['tipo'] . ", Valor: " . (is_array($interpretacion['valor']) ? json_encode($interpretacion['valor']) : $interpretacion['valor']));

            // Si hay mensaje de error o saludo, enviarlo directamente
            if (!empty($interpretacion['mensaje'])) {
                $this->sendMessage($from, $interpretacion['mensaje']);
                return;
            }

            // PASO 5: Ejecutar búsqueda según el tipo
            $this->ejecutarBusqueda($from, $interpretacion);

        } catch (Exception $e) {
            error_log("❌ Error procesando mensaje: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Intentar enviar mensaje de error, pero si falla, no hacer nada más
            try {
                $this->sendMessage(
                    $from,
                    "⚠️ *Error del Sistema*\n\n"
                    . "Ocurrió un error al procesar tu mensaje.\n\n"
                    . "Por favor, intenta nuevamente en unos momentos.\n\n"
                    . "Si el problema persiste, contacta con el administrador."
                );
            } catch (Exception $sendError) {
                error_log("Error crítico: No se pudo enviar mensaje de error al usuario: " . $sendError->getMessage());
            }
        }
    }

    /**
     * Ejecuta la búsqueda según la interpretación del mensaje
     */
    private function ejecutarBusqueda(string $to, array $interpretacion): void
    {
        $tipo = $interpretacion['tipo'];
        $valor = $interpretacion['valor'];
        
        switch ($tipo) {
            case 'dni':
                $this->searchByDni($to, $valor);
                break;
                
            case 'telefono':
                $this->searchByPhone($to, $valor);
                break;
                
            case 'nombre':
                $this->searchByName($to, $valor);
                break;
                
            case 'mixto':
                // Buscar por ambos y priorizar coincidencias
                $this->searchByMixed($to, $valor);
                break;
                
            default:
                $this->sendMessage(
                    $to,
                    "No pude entender tu mensaje. Escribí un Nombre, DNI o Teléfono."
                );
        }
    }
    
    /**
     * Busca por datos mixtos (números + letras)
     */
    private function searchByMixed(string $to, array $valores): void
    {
        $resultados = [];
        $tipoNumeros = $valores['tipo_numeros'] ?? '';
        
        // Buscar por números primero
        if ($tipoNumeros === 'dni') {
            $resultadosDni = $this->firebaseService->searchByDni($valores['numeros']);
            $resultados = array_merge($resultados, $resultadosDni);
        } elseif ($tipoNumeros === 'telefono') {
            $resultadosTel = $this->firebaseService->searchByPhone($valores['numeros']);
            $resultados = array_merge($resultados, $resultadosTel);
        }
        
        // Buscar por nombre
        $resultadosNombre = $this->firebaseService->searchByName($valores['letras']);
        
        // Priorizar resultados que coincidan en ambos
        $coincidencias = [];
        foreach ($resultados as $resultado) {
            foreach ($resultadosNombre as $resultadoNombre) {
                if (($resultado['id'] ?? '') === ($resultadoNombre['id'] ?? '')) {
                    $coincidencias[] = $resultado;
                    break;
                }
            }
        }
        
        // Si hay coincidencias, mostrar solo esas
        if (!empty($coincidencias)) {
            $this->formatReportsResponse($to, $coincidencias, "Búsqueda combinada");
            return;
        }
        
        // Si no hay coincidencias, mostrar todos los resultados
        $todosResultados = array_merge($resultados, $resultadosNombre);
        if (!empty($todosResultados)) {
            $this->formatReportsResponse($to, $todosResultados, "Búsqueda combinada");
        } else {
            $this->sendMessage(
                $to,
                "✅ Todo limpio. No encontré reportes con esos datos. Probá escribirlo de otra forma."
            );
        }
    }

    /**
     * Ejecuta un comando validado (método antiguo - mantener por compatibilidad)
     */
    private function executeCommand(string $to, array $user, array $commandData): void
    {
        $commandType = $commandData['type'];
        
        if ($commandType === 'simple') {
            $command = $commandData['command'];
            
            if (in_array($command, ['menu', 'ayuda', 'help'])) {
                $this->showMenu($to);
            }
        } elseif ($commandType === 'search_dni') {
            $dni = $commandData['params'][0] ?? '';
            $this->searchByDni($to, $dni);
        } elseif ($commandType === 'search_phone') {
            $phone = $commandData['params'][0] ?? '';
            $this->searchByPhone($to, $phone);
        } elseif ($commandType === 'search_name') {
            $name = $commandData['params'][0] ?? '';
            $this->searchByName($to, $name);
        } else {
            // Texto libre - mostrar bienvenida y opciones de búsqueda
            $this->showWelcome($to, $user);
        }
    }

    /**
     * Muestra mensaje de bienvenida con opciones de búsqueda
     */
    private function showWelcome(string $to, array $user): void
    {
        $userName = $user['nombre'] ?? $user['email'] ?? 'Usuario';
        $welcome = "¡Hola " . $userName . "! 👋\n\n";
        $welcome .= "🏨 *Bienvenido a Alojamiento Corrientes*\n\n";
        $welcome .= "Puedes buscar huéspedes reportados de forma simple:\n\n";
        $welcome .= "🔍 *Escribe directamente:*\n\n";
        $welcome .= "• Un *DNI* (ej: 12345678)\n";
        $welcome .= "• Un *teléfono* (ej: 3794267780)\n";
        $welcome .= "• Un *nombre* (ej: Juan Pérez)\n\n";
        $welcome .= "El bot detectará automáticamente qué tipo de búsqueda hacer.";

        $this->sendMessage($to, $welcome);
    }

    /**
     * Muestra el menú de opciones
     */
    private function showMenu(string $to): void
    {
        $menu = "📋 *MENÚ DE OPCIONES*\n\n";
        $menu .= "🔍 *Búsquedas disponibles:*\n\n";
        $menu .= "• Escribe un *DNI* directamente\n";
        $menu .= "• Escribe un *teléfono* directamente\n";
        $menu .= "• Escribe un *nombre* directamente\n\n";
        $menu .= "El bot detectará automáticamente el tipo de búsqueda.";

        $this->sendMessage($to, $menu);
    }

    /**
     * Busca reportes por DNI
     */
    private function searchByDni(string $to, string $dni): void
    {
        if (empty($dni)) {
            $this->sendMessage(
                $to,
                "❌ Por favor, especifica el DNI a buscar.\n\n"
                . "Ejemplo: *BUSCAR DNI 12345678*"
            );
            return;
        }

        $reports = $this->firebaseService->searchByDni($dni);

        if (empty($reports)) {
            $this->sendMessage(
                $to,
                "✅ Todo limpio. No encontré reportes con el DNI *$dni*.\n\n"
                . "Verificá que no haya errores. Si tenés dudas, consultame de vuelta o probá buscando por nombre o teléfono."
            );
            return;
        }

        // Manejar respuestas según cantidad de resultados
        if (count($reports) === 1) {
            $this->formatReportsResponse($to, $reports, "DNI: $dni");
        } else {
            $this->sendMessage(
                $to,
                "⚠️ Encontré " . count($reports) . " reportes con el DNI *$dni*:\n\n"
            );
            $this->formatReportsResponse($to, $reports, "DNI: $dni");
        }
    }

    /**
     * Busca reportes por teléfono
     */
    private function searchByPhone(string $to, string $phone): void
    {
        if (empty($phone)) {
            $this->sendMessage(
                $to,
                "❌ Por favor, especifica el teléfono a buscar.\n\n"
                . "Ejemplo: *BUSCAR TELEFONO 1234567890*"
            );
            return;
        }

        $reports = $this->firebaseService->searchByPhone($phone);

        if (empty($reports)) {
            $this->sendMessage(
                $to,
                "✅ Todo limpio. No encontré reportes con el teléfono *$phone*.\n\n"
                . "Verificá que no haya errores. Si tenés dudas, consultame de vuelta o probá buscando por DNI o nombre."
            );
            return;
        }

        // Manejar respuestas según cantidad de resultados
        if (count($reports) === 1) {
            $this->formatReportsResponse($to, $reports, "Teléfono: $phone");
        } else {
            $this->sendMessage(
                $to,
                "⚠️ Encontré " . count($reports) . " reportes con el teléfono *$phone*:\n\n"
            );
            $this->formatReportsResponse($to, $reports, "Teléfono: $phone");
        }
    }

    /**
     * Busca reportes por nombre
     */
    private function searchByName(string $to, string $name): void
    {
        if (empty($name) || strlen($name) < 3) {
            $this->sendMessage(
                $to,
                "❌ Por favor, especifica un nombre (mínimo 3 caracteres).\n\n"
                . "Ejemplo: *BUSCAR NOMBRE Juan Pérez*"
            );
            return;
        }

        $reports = $this->firebaseService->searchByName($name);

        if (empty($reports)) {
            $this->sendMessage(
                $to,
                "✅ Todo limpio. No encontré reportes con el nombre *$name*.\n\n"
                . "Verificá que no haya errores. Si tenés dudas, consultame de vuelta o probá buscando por DNI para una búsqueda más exacta."
            );
            return;
        }

        // Manejar ambigüedad
        if (count($reports) > 1) {
            $this->sendMessage(
                $to,
                "⚠️ Encontré " . count($reports) . " personas llamadas '*$name*'. ¿Tenés el DNI para afinar la búsqueda?\n\n"
            );
        }
        
        $this->formatReportsResponse($to, $reports, "Nombre: $name");
    }

    /**
     * Formatea y envía la respuesta con los reportes encontrados
     */
    private function formatReportsResponse(string $to, array $reports, string $searchTerm): void
    {
        $message = "⚠️ *REPORTES ENCONTRADOS*\n\n";
        $message .= "🔍 Búsqueda: $searchTerm\n";
        $message .= "📊 Total: " . count($reports) . " reporte(s)\n\n";
        $message .= str_repeat("─", 35) . "\n\n";

        foreach ($reports as $index => $report) {
            $message .= "📄 *Reporte #" . ($index + 1) . "*\n\n";
            
            // Nombre
            if (isset($report['nombre'])) {
                $message .= "👤 Nombre: *" . $report['nombre'] . "*\n";
            }
            
            // DNI
            if (isset($report['dni'])) {
                $message .= "🆔 DNI: " . $report['dni'] . "\n";
            }
            
            // Teléfono
            if (isset($report['telefono'])) {
                $message .= "📱 Teléfono: " . $report['telefono'] . "\n";
            }
            
            // Motivo o descripción
            if (isset($report['motivo'])) {
                $message .= "📝 Motivo: " . $report['motivo'] . "\n";
            } elseif (isset($report['descripcion'])) {
                $message .= "📝 Descripción: " . $report['descripcion'] . "\n";
            } elseif (isset($report['observaciones'])) {
                $message .= "📝 Observaciones: " . $report['observaciones'] . "\n";
            }
            
            // Fecha del reporte
            if (isset($report['fechaReporte'])) {
                $fecha = $report['fechaReporte'];
                if ($fecha instanceof \DateTime) {
                    $message .= "📅 Fecha: " . $fecha->format('d/m/Y H:i') . "\n";
                } elseif (is_string($fecha)) {
                    $message .= "📅 Fecha: " . date('d/m/Y H:i', strtotime($fecha)) . "\n";
                }
            }

            if ($index < count($reports) - 1) {
                $message .= "\n" . str_repeat("─", 30) . "\n\n";
            }
        }

        $this->sendMessage($to, $message);
    }

    /**
     * Envía un mensaje usando el servicio de WhatsApp
     */
    private function sendMessage(string $to, string $message): void
    {
        error_log("Enviando mensaje a: $to");
        $result = $this->whatsappService->sendMessage($to, $message);
        if ($result) {
            error_log("✅ Mensaje enviado exitosamente");
        } else {
            error_log("❌ Error al enviar mensaje");
        }
    }
}
