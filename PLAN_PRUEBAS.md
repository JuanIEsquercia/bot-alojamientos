# Plan de Pruebas y Despliegue

## 📊 Análisis del Sistema Actual

### ✅ Lo que tenemos:
1. **Código completo** - Bot funcional con validaciones
2. **Credenciales configuradas** - Meta y Firebase en `.env`
3. **Sin dependencias externas** - Todo con PHP nativo
4. **Webhook implementado** - Maneja GET (verificación) y POST (mensajes)

### ⚠️ Lo que necesitamos:
1. **Servidor con HTTPS** - Meta requiere HTTPS para webhooks
2. **URL pública accesible** - Para que Meta pueda enviar eventos
3. **Pruebas locales primero** - Validar que todo funciona antes de producción

---

## 🎯 Pasos para Probar el Sistema

### FASE 1: Pruebas Locales (Sin servidor público)

#### Paso 1.1: Verificar Configuración
- [ ] Verificar que `.env` tiene todas las credenciales
- [ ] Verificar que `firebase-credentials.json` existe y es válido
- [ ] Probar carga de configuración: `php -r "require 'autoload.php'; \$c = \BotAlojamientos\Config\Config::getInstance(); var_dump(\$c->get('firebase.project_id'));"`

#### Paso 1.2: Probar Conexión a Firebase
Crear archivo `test_firebase.php`:
```php
<?php
require 'autoload.php';
try {
    $firebase = new \BotAlojamientos\Services\FirebaseService();
    echo "✅ Firebase conectado correctamente\n";
    
    // Probar búsqueda de usuario
    $user = $firebase->validateUser('1234567890'); // Usa un número de prueba
    if ($user) {
        echo "✅ Usuario encontrado: " . json_encode($user) . "\n";
    } else {
        echo "ℹ️ Usuario no encontrado (esto es normal si el número no existe)\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

#### Paso 1.3: Probar Envío de Mensajes (Opcional)
Solo si tienes un servidor de prueba, puedes probar enviar mensajes directamente.

---

### FASE 2: Configurar URL Pública (Para Webhook)

#### Opción A: Servidor de Producción (Recomendado)
**Requisitos:**
- Servidor con PHP 8.0+
- HTTPS habilitado (certificado SSL válido)
- Acceso FTP/SSH para subir archivos

**Pasos:**
1. Subir todos los archivos al servidor
2. Configurar `.env` en el servidor
3. Configurar permisos (600 para `.env` y `firebase-credentials.json`)
4. Obtener URL: `https://tu-dominio.com/webhook.php`

#### Opción B: ngrok (Para Pruebas Locales)
**Ideal para desarrollo y pruebas antes de producción**

**Pasos:**
1. Descargar ngrok: https://ngrok.com/download
2. Instalar y autenticarte
3. Ejecutar: `ngrok http 80` (o el puerto de tu servidor local)
4. Copiar la URL HTTPS que te da (ej: `https://abc123.ngrok.io`)
5. Usar esa URL en Meta: `https://abc123.ngrok.io/webhook.php`

**Ventajas:**
- ✅ HTTPS automático
- ✅ URL pública temporal
- ✅ Perfecto para pruebas
- ✅ Gratis para uso básico

#### Opción C: Servicios Cloud (Alternativas)
- **Heroku** - Gratis con limitaciones
- **Railway** - Fácil de usar
- **Render** - Similar a Heroku
- **000webhost / InfinityFree** - Hosting gratuito con PHP

---

### FASE 3: Configurar Webhook en Meta

Una vez que tengas la URL pública:

1. **Ve a Meta for Developers:**
   - https://developers.facebook.com/apps/
   - Tu app > WhatsApp > Configuration > Webhook

2. **Configura:**
   - **Callback URL:** `https://tu-url.com/webhook.php`
   - **Verify Token:** El mismo que pusiste en `.env` (`WHATSAPP_WEBHOOK_VERIFY_TOKEN`)
   - Haz clic en **"Verify and Save"**

3. **Meta enviará una petición GET:**
   - Tu servidor debe responder con el `challenge`
   - Si funciona, verás "Webhook verificado" ✅

4. **Suscribirte a eventos:**
   - Marca la casilla `messages`
   - Opcional: `message_status`

---

### FASE 4: Probar el Bot

1. **Envía un mensaje** al número de prueba de WhatsApp
2. **Verifica los logs** del servidor
3. **Prueba los comandos:**
   - `MENU`
   - `BUSCAR DNI 12345678`
   - `BUSCAR TELEFONO 1234567890`
   - `BUSCAR NOMBRE Juan`

---

## 🔍 Verificación de Funcionamiento

### Checklist Pre-Despliegue:
- [ ] `.env` completo con todas las credenciales
- [ ] `firebase-credentials.json` en el servidor
- [ ] Permisos correctos (600 para archivos sensibles)
- [ ] PHP 8.0+ instalado
- [ ] OpenSSL y cURL habilitados
- [ ] HTTPS configurado
- [ ] URL pública accesible
- [ ] Webhook verificado en Meta

### Logs a Revisar:
- Logs de PHP (error_log)
- Logs del servidor web
- Respuestas de Meta en el webhook

---

## 🚀 Recomendación

**Para empezar rápido:**
1. Usa **ngrok** para pruebas locales
2. Prueba que todo funciona
3. Luego sube a un servidor de producción

**Para producción:**
- Servidor con HTTPS válido
- Dominio propio
- Backup de credenciales
- Monitoreo de logs

---

## ❓ Preguntas para Decidir

1. **¿Tienes un servidor web ya configurado?**
   - Si sí: ¿Qué dominio/servidor?
   - Si no: ¿Prefieres ngrok para pruebas o contratar hosting?

2. **¿Tienes certificado SSL?**
   - Meta requiere HTTPS obligatorio

3. **¿Prefieres probar localmente primero?**
   - ngrok es la mejor opción para esto

