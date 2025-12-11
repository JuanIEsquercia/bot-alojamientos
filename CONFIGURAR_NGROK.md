# Configurar ngrok para Pruebas Locales

## ¿Qué es ngrok?
ngrok crea un túnel seguro (HTTPS) desde internet hacia tu servidor local, permitiendo que Meta pueda enviar eventos a tu webhook mientras desarrollas localmente.

---

## Paso 1: Descargar e Instalar ngrok

### Opción A: Descarga Directa (Recomendado)
1. Ve a: https://ngrok.com/download
2. Descarga la versión para Windows
3. Extrae el archivo `ngrok.exe`
4. Colócalo en una carpeta (ej: `C:\ngrok\`)

### Opción B: Con Chocolatey (Si lo tienes)
```powershell
choco install ngrok
```

---

## Paso 2: Autenticarte en ngrok

1. **Crea una cuenta gratuita:**
   - Ve a: https://dashboard.ngrok.com/signup
   - Crea tu cuenta (es gratis)

2. **Obtén tu authtoken:**
   - Ve a: https://dashboard.ngrok.com/get-started/your-authtoken
   - Copia tu authtoken

3. **Configura ngrok:**
   ```powershell
   ngrok config add-authtoken TU_AUTHTOKEN_AQUI
   ```

---

## Paso 3: Iniciar Servidor Local PHP

Necesitas un servidor web local. Opciones:

### Opción A: Servidor PHP Built-in (Más Simple)
```powershell
# Desde la carpeta del proyecto
php -S localhost:8000
```

### Opción B: XAMPP/WAMP
- Inicia Apache
- Coloca los archivos en `htdocs`
- Accede desde: `http://localhost/bot-alojamientos/webhook.php`

### Opción C: Laragon (Si lo tienes)
- Inicia Laragon
- Coloca los archivos en la carpeta del proyecto

---

## Paso 4: Iniciar ngrok

En una **nueva ventana de PowerShell**:

```powershell
# Si usas servidor PHP built-in (puerto 8000)
ngrok http 8000

# Si usas XAMPP/WAMP (puerto 80)
ngrok http 80

# Si usas otro puerto, ajusta el número
ngrok http PUERTO
```

**Verás algo como:**
```
Forwarding  https://abc123def456.ngrok.io -> http://localhost:8000
```

**Copia la URL HTTPS** (la que empieza con `https://`)

---

## Paso 5: Configurar Webhook en Meta

1. **Ve a Meta for Developers:**
   - https://developers.facebook.com/apps/
   - Tu app > WhatsApp > Configuration > Webhook

2. **Configura el webhook:**
   - **Callback URL:** `https://abc123def456.ngrok.io/webhook.php`
     (Usa la URL que te dio ngrok)
   - **Verify Token:** El mismo que pusiste en `.env`
   - Haz clic en **"Verify and Save"**

3. **Meta enviará una petición GET:**
   - Si todo está bien, verás "Webhook verificado" ✅
   - Si falla, revisa los logs

4. **Suscribirte a eventos:**
   - Marca `messages`
   - Opcional: `message_status`

---

## Paso 6: Probar

1. **Envía un mensaje** al número de prueba de WhatsApp
2. **Verifica en la terminal** donde corre tu servidor PHP
3. **Verifica en ngrok:**
   - Ve a: http://localhost:4040 (interfaz web de ngrok)
   - Verás todas las peticiones en tiempo real

---

## ⚠️ Importante

- **ngrok es para PRUEBAS**: La URL cambia cada vez que reinicias ngrok (en plan gratuito)
- **Para producción**: Necesitas un servidor real con dominio propio
- **Mantén ngrok corriendo**: Si lo cierras, el webhook dejará de funcionar

---

## Solución de Problemas

### Error: "ngrok no se reconoce"
- Agrega ngrok al PATH, o usa la ruta completa: `C:\ngrok\ngrok.exe http 8000`

### Error: "Webhook verification failed"
- Verifica que el `WHATSAPP_WEBHOOK_VERIFY_TOKEN` en `.env` coincida con el de Meta
- Verifica que tu servidor PHP esté corriendo
- Verifica que la URL de ngrok sea correcta

### Error: "Connection refused"
- Verifica que tu servidor PHP esté corriendo
- Verifica que el puerto sea correcto

---

## Comandos Rápidos

```powershell
# Terminal 1: Servidor PHP
cd C:\Users\esque\OneDrive\Documentos\bot-alojamientos
php -S localhost:8000

# Terminal 2: ngrok
ngrok http 8000
```

