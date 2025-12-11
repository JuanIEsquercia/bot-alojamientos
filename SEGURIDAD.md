# Seguridad del Proyecto - Checklist

## ✅ Protecciones Implementadas

### 1. Archivos Protegidos en .gitignore

Los siguientes archivos **NUNCA** se subirán a Git:

- ✅ `.env` - Variables de entorno con credenciales
- ✅ `firebase-credentials.json` - Credenciales de Firebase
- ✅ `*-firebase-adminsdk-*.json` - Cualquier archivo de credenciales de Firebase
- ✅ `*firebase*.json` - Archivos JSON relacionados con Firebase
- ✅ `vendor/` - Dependencias (se instalan con composer)
- ✅ `*.log` - Archivos de log

### 2. Sin Credenciales Hardcodeadas

✅ **Todas las credenciales están en variables de entorno:**
- WhatsApp Access Token → `WHATSAPP_ACCESS_TOKEN`
- WhatsApp Phone Number ID → `WHATSAPP_PHONE_NUMBER_ID`
- WhatsApp Webhook Secret → `WHATSAPP_WEBHOOK_SECRET`
- Firebase Project ID → `FIREBASE_PROJECT_ID`
- Firebase Credentials → `GOOGLE_APPLICATION_CREDENTIALS`

✅ **Ninguna credencial está en el código fuente**

### 3. Validación de Configuración

✅ El webhook valida que el verify token esté configurado
✅ Los servicios validan que las credenciales existan antes de usarlas

### 4. Verificación de Firma del Webhook

✅ El webhook verifica la firma de las peticiones de Meta usando HMAC SHA256

---

## ⚠️ Recomendaciones de Seguridad

### Para Producción:

1. **HTTPS Obligatorio:**
   - El webhook DEBE estar en HTTPS (requerido por Meta)
   - Usa certificados SSL válidos

2. **Permisos de Archivos:**
   - `.env` debe tener permisos 600 (solo lectura/escritura para el propietario)
   - `firebase-credentials.json` debe tener permisos 600

3. **Variables de Entorno en el Servidor:**
   - Considera usar variables de entorno del sistema en lugar de `.env` en producción
   - Muchos servidores (Heroku, AWS, etc.) tienen sistemas de variables de entorno

4. **Logs:**
   - No loguees credenciales completas
   - Los logs actuales solo muestran errores, no credenciales

5. **Firebase Security Rules:**
   - Asegúrate de que las reglas de seguridad de Firestore estén bien configuradas
   - La colección `users` tiene permisos de lectura pública (según tu especificación)
   - La colección `huespedesReportados` requiere autenticación

6. **Rate Limiting:**
   - Considera implementar rate limiting en el webhook
   - Meta tiene sus propios límites, pero es buena práctica

7. **Validación de Entrada:**
   - ✅ Ya implementada: validación de texto, sanitización, normalización

---

## 🔒 Archivos Sensibles - Verificación

Ejecuta estos comandos para verificar que los archivos sensibles NO están en Git:

```bash
# Si tienes Git inicializado:
git status
git ls-files | grep -E "(\.env|firebase.*\.json|credentials)"

# No debería mostrar ningún archivo sensible
```

---

## 📋 Checklist Pre-Deploy

Antes de subir a producción, verifica:

- [ ] `.env` NO está en el repositorio
- [ ] `firebase-credentials.json` NO está en el repositorio
- [ ] Todas las credenciales están en variables de entorno
- [ ] El servidor tiene HTTPS configurado
- [ ] Los permisos de archivos son correctos (600 para archivos sensibles)
- [ ] Las reglas de seguridad de Firestore están configuradas
- [ ] El webhook está configurado correctamente en Meta
- [ ] Los logs no contienen credenciales

---

## 🚨 Si Comprometes Credenciales

Si accidentalmente subes credenciales a Git:

1. **Inmediatamente:**
   - Revoca/regenera TODAS las credenciales comprometidas
   - Elimina el historial de Git (si es posible)
   - O crea un nuevo repositorio

2. **Credenciales a regenerar:**
   - WhatsApp Access Token (desde Meta)
   - WhatsApp Webhook Secret (desde Meta)
   - Firebase Service Account Key (desde Firebase Console)

3. **Prevención:**
   - Verifica `.gitignore` antes de cada commit
   - Usa `git status` antes de `git add`
   - Considera usar `git-secrets` o herramientas similares

---

## ✅ Estado Actual: SEGURO

El proyecto está configurado de forma segura:
- ✅ Sin credenciales en el código
- ✅ Archivos sensibles en .gitignore
- ✅ Validaciones de seguridad implementadas
- ✅ Uso correcto de variables de entorno


