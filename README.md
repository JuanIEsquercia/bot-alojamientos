# Bot de WhatsApp para Alojamientos

Bot de WhatsApp desarrollado en PHP que consulta una base de datos de Firebase Firestore para validar usuarios y proporcionar acceso a reportes.

## Características

- ✅ Validación de usuarios por número de teléfono
- ✅ Consulta de reportes desde Firebase Firestore
- ✅ Interfaz conversacional mediante comandos de WhatsApp
- ✅ Integración con Meta Business API (WhatsApp Business API oficial)

## Requisitos

- PHP 8.0 o superior
- Composer
- Cuenta de Meta Business con WhatsApp Business API habilitada
- Proyecto de Firebase con Firestore configurado
- Credenciales de Firebase (archivo JSON)
- Servidor con HTTPS (requerido para webhooks de Meta)

## Instalación

1. **Clonar o descargar el proyecto**

2. **Instalar dependencias con Composer:**
```bash
composer install
```

3. **Configurar variables de entorno:**

Crea un archivo `.env` en la raíz del proyecto:

```env
# Configuración de Meta Business API (WhatsApp)
WHATSAPP_ACCESS_TOKEN=tu_access_token_aqui
WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id_aqui
WHATSAPP_BUSINESS_ACCOUNT_ID=tu_business_account_id_aqui
WHATSAPP_WEBHOOK_VERIFY_TOKEN=tu_token_secreto_para_verificacion
WHATSAPP_WEBHOOK_SECRET=tu_webhook_secret_aqui

# Configuración de Firebase
GOOGLE_APPLICATION_CREDENTIALS=path/to/your/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id
```

4. **Obtener credenciales de Meta Business:**

   a. Ve a [Meta for Developers](https://developers.facebook.com/apps/)
   
   b. Crea una nueva app o selecciona una existente
   
   c. Agrega el producto "WhatsApp" a tu app
   
   d. Obtén los siguientes valores:
      - **Access Token**: En "WhatsApp" > "Getting Started" o "API Setup"
      - **Phone Number ID**: En "WhatsApp" > "Getting Started" (formato: números)
      - **Business Account ID**: En "WhatsApp" > "Getting Started" (formato: números)
      - **Webhook Verify Token**: Crea un token secreto (puede ser cualquier string)
      - **Webhook Secret**: En "WhatsApp" > "Configuration" > "Webhook" > "App Secret"

5. **Configurar Firebase:**

   - Descarga el archivo de credenciales JSON desde la consola de Firebase
   - Colócalo en una ubicación segura del servidor
   - Actualiza la ruta en `.env`

6. **Configurar la estructura de Firestore:**

   El bot espera las siguientes colecciones en Firestore:

   - **Colección `users`:**
     - Campo `phone`: Número de teléfono del usuario (formato: solo números, sin + ni espacios)
       - Ejemplo: `34612345678` (España) o `1234567890` (EEUU)
     - Campo `name`: Nombre del usuario (opcional)
     - Otros campos personalizados según necesites

   - **Colección `reports`:**
     - Campo `userId`: ID del documento del usuario propietario
     - Campo `name` o `title`: Nombre/título del reporte
     - Campo `date`: Fecha del reporte (timestamp o string ISO)
     - Campo `description`: Descripción del reporte (opcional)
     - Campo `data`: Objeto con datos adicionales del reporte (opcional)

7. **Configurar webhook en Meta:**

   a. Ve a tu app en [Meta for Developers](https://developers.facebook.com/apps/)
   
   b. Navega a "WhatsApp" > "Configuration" > "Webhook"
   
   c. Haz clic en "Edit" o "Add Callback URL"
   
   d. Configura:
      - **Callback URL**: `https://tu-dominio.com/webhook.php`
      - **Verify Token**: El mismo que configuraste en `.env` (`WHATSAPP_WEBHOOK_VERIFY_TOKEN`)
      - **Subscription Fields**: Selecciona `messages` (y opcionalmente `message_status`)
   
   e. Haz clic en "Verify and Save"
   
   f. Meta enviará una petición GET a tu webhook para verificación
   
   g. Una vez verificado, suscríbete a los eventos necesarios

8. **Probar con número de prueba:**

   - Meta proporciona un número de teléfono de prueba
   - Puedes enviar mensajes a este número desde WhatsApp
   - El número aparece en "WhatsApp" > "Getting Started" > "Send and receive messages"

## Uso

### Comandos disponibles:

- `MENU` o `AYUDA` o `HELP` - Muestra el menú de opciones
- `REPORTES` o `REPORTS` - Lista todos los reportes disponibles del usuario
- `REPORTE [ID]` o `REPORT [ID]` - Muestra los detalles de un reporte específico

### Ejemplo de conversación:

```
Usuario: Hola
Bot: ¡Hola Usuario! 👋
     Escribe *MENU* para ver las opciones disponibles.
     Escribe *REPORTES* para ver tus reportes disponibles.

Usuario: MENU
Bot: 📋 MENÚ PRINCIPAL
     Escribe uno de los siguientes comandos:
     • REPORTES - Ver lista de reportes disponibles
     • REPORTE [ID] - Ver un reporte específico
     • MENU - Mostrar este menú
     Ejemplo: REPORTE ABC123

Usuario: REPORTES
Bot: 📊 TUS REPORTES
     1. Reporte de Ventas
        ID: ABC123
        Fecha: 15/01/2024
     ...
     Escribe *REPORTE [ID]* para ver los detalles de un reporte específico.

Usuario: REPORTE ABC123
Bot: 📄 REPORTE: Reporte de Ventas
     📅 Fecha: 15/01/2024 10:30
     ...
```

## Estructura del Proyecto

```
bot-alojamientos/
├── src/
│   ├── Bot/
│   │   └── WhatsAppBot.php          # Lógica principal del bot
│   ├── Config/
│   │   └── Config.php                # Gestión de configuración
│   └── Services/
│       ├── FirebaseService.php       # Servicio para Firestore
│       └── WhatsAppService.php       # Servicio para Meta Business API
├── vendor/                           # Dependencias de Composer
├── webhook.php                       # Endpoint para recibir mensajes
├── composer.json
├── .env                              # Variables de entorno (no versionado)
└── README.md
```

## Seguridad

- ⚠️ **Nunca subas el archivo `.env` o credenciales de Firebase a un repositorio público**
- ⚠️ Mantén el archivo de credenciales de Firebase en una ubicación segura
- ⚠️ **El webhook debe estar en HTTPS** (requerido por Meta)
- ⚠️ La verificación de firma del webhook está implementada (usa `WHATSAPP_WEBHOOK_SECRET`)
- ⚠️ Valida y sanitiza todas las entradas del usuario
- ⚠️ Mantén tu Access Token seguro y no lo compartas

## Personalización

### Agregar nuevos comandos:

Edita `src/Bot/WhatsAppBot.php` en el método `processMessage()` para agregar nuevos comandos.

### Modificar formato de mensajes:

Los métodos `showMenu()`, `listReports()`, y `getReport()` pueden ser modificados para cambiar el formato de los mensajes.

### Normalización de números de teléfono:

Ajusta el método `normalizePhoneNumber()` en `FirebaseService.php` según el formato que uses en tu base de datos. La API de Meta envía números sin el símbolo `+`, solo dígitos.

### Enviar otros tipos de mensajes:

Puedes extender `WhatsAppService.php` para enviar:
- Imágenes (`type: 'image'`)
- Documentos (`type: 'document'`)
- Ubicaciones (`type: 'location'`)
- Botones interactivos (`type: 'interactive'`)

Consulta la [documentación de Meta](https://developers.facebook.com/docs/whatsapp/cloud-api) para más detalles.

## Solución de Problemas

### El bot no responde:
- Verifica que el webhook esté correctamente configurado y verificado en Meta
- Revisa los logs del servidor para errores
- Verifica que las credenciales de Meta y Firebase sean correctas
- Asegúrate de que el servidor tenga HTTPS habilitado
- Verifica que el `Phone Number ID` sea correcto

### Error de verificación del webhook:
- Asegúrate de que el `WHATSAPP_WEBHOOK_VERIFY_TOKEN` en `.env` coincida con el configurado en Meta
- Verifica que el webhook esté accesible públicamente (no localhost)
- Revisa que el método GET esté funcionando correctamente

### Usuario no encontrado:
- Verifica que el número de teléfono esté en el formato correcto en Firestore (solo números, sin +)
- Los números de Meta vienen sin el símbolo `+`, solo dígitos
- Revisa la normalización de números en `FirebaseService.php`
- Verifica que el campo `phone` en Firestore coincida exactamente con el formato recibido

### Error de conexión a Firebase:
- Verifica que el archivo de credenciales exista y sea válido
- Confirma que el `FIREBASE_PROJECT_ID` sea correcto
- Verifica los permisos del archivo de credenciales
- Asegúrate de que el servicio de Firestore esté habilitado en Firebase

### Error 401 (Unauthorized) al enviar mensajes:
- Verifica que el Access Token sea válido y no haya expirado
- Los tokens de prueba tienen una duración limitada
- Para producción, necesitarás un token permanente o implementar renovación de tokens

### Error 403 (Forbidden):
- Verifica que el número de teléfono de destino esté en la lista de números de prueba (modo desarrollo)
- Para producción, necesitarás aprobar tu app y números de teléfono

## Desarrollo Local

Para desarrollo local, puedes usar herramientas como:
- [ngrok](https://ngrok.com/) para exponer tu servidor local con HTTPS
- [localtunnel](https://localtunnel.github.io/www/) como alternativa a ngrok

Ejemplo con ngrok:
```bash
ngrok http 80
# Usa la URL HTTPS proporcionada como Callback URL en Meta
```

## Recursos

- [Documentación de Meta WhatsApp Business API](https://developers.facebook.com/docs/whatsapp)
- [Guía de inicio rápido](https://developers.facebook.com/docs/whatsapp/cloud-api/get-started)
- [Referencia de la API](https://developers.facebook.com/docs/whatsapp/cloud-api/reference)

## Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

## Soporte

Para problemas o preguntas, por favor abre un issue en el repositorio del proyecto.
