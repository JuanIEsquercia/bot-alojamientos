# Suscribirse a Eventos en Meta

## Después de Verificar el Webhook

Una vez que Meta acepta tu webhook, necesitas **suscribirte a los eventos** que quieres recibir.

---

## Pasos para Suscribirse

1. **En la página de configuración del webhook de Meta:**
   - Deberías ver una sección que dice algo como "Subscription fields" o "Campos de suscripción"

2. **Busca la lista desplegable o checkboxes:**
   - Deberías ver opciones como:
     - `messages` - Para recibir mensajes entrantes ✅ **NECESARIO**
     - `message_status` - Para recibir estados de mensajes (opcional)
     - `user` - Para eventos de usuario (no necesario para nuestro bot)

3. **Selecciona los eventos:**
   - ✅ **Marca `messages`** (esto es obligatorio para que el bot funcione)
   - Opcional: Marca `message_status` si quieres trackear el estado de los mensajes enviados

4. **Guarda los cambios:**
   - Haz clic en "Save" o "Guardar"

---

## Verificación

Después de suscribirte:

1. **Verifica en ngrok:**
   - Ve a http://127.0.0.1:4040
   - Deberías ver las peticiones de verificación

2. **Prueba enviando un mensaje:**
   - Envía un mensaje al número de prueba de WhatsApp
   - Deberías ver una nueva petición POST en ngrok
   - Revisa los logs de tu servidor PHP

---

## Si No Ves la Opción de Suscribirte

A veces la interfaz de Meta puede ser confusa. Busca:
- Una sección que diga "Webhook fields" o "Campos del webhook"
- Checkboxes o un dropdown con las opciones
- Un botón que diga "Subscribe" o "Suscribirse"

Si no encuentras la opción, puede que ya esté suscrito automáticamente. Prueba enviando un mensaje para verificar.

