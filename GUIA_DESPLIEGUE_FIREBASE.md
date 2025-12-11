# 🚀 Guía: Desplegar Bot en Firebase/Google Cloud

## ⚠️ Situación Actual

- **Dominio:** Hostinger (alojamientocorrientes.com)
- **Hosting:** Firebase
- **Bot:** PHP (requiere servidor que ejecute PHP)

## 🔍 Problema

**Firebase Hosting NO ejecuta PHP directamente.** Firebase Hosting es para:
- Sitios estáticos (HTML, CSS, JS)
- Aplicaciones web estáticas
- No ejecuta código del servidor como PHP

## ✅ Soluciones para tu Bot PHP

### Opción 1: Google Cloud Run (RECOMENDADO) ⭐

**Cloud Run puede ejecutar PHP** y se integra perfectamente con Firebase.

#### Ventajas:
- ✅ Ejecuta PHP nativamente
- ✅ Escalable automáticamente
- ✅ Integrado con Google Cloud (mismo ecosistema que Firebase)
- ✅ Puedes usar tu dominio de Hostinger
- ✅ Plan gratuito generoso

#### Pasos:

1. **Crear proyecto en Google Cloud:**
   - Ve a: https://console.cloud.google.com
   - Crea un proyecto nuevo (o usa el mismo de Firebase)

2. **Habilitar Cloud Run:**
   - En la consola, busca "Cloud Run"
   - Habilita la API

3. **Preparar Dockerfile:**
   - Necesitamos crear un contenedor Docker con PHP
   - Te ayudo a crear el archivo

4. **Desplegar:**
   - Subir el código a Cloud Run
   - Configurar el dominio

---

### Opción 2: Hostinger (Hosting PHP) 💰

**Usar Hostinger solo para el bot** (aunque tengas Firebase para el sitio principal).

#### Ventajas:
- ✅ PHP nativo (sin configuración extra)
- ✅ Muy fácil de configurar
- ✅ Mismo dominio (subcarpeta o subdominio)
- ✅ Precio bajo

#### Pasos:

1. **En Hostinger:**
   - Aunque el dominio está ahí, puedes contratar hosting PHP
   - O usar el hosting que ya tienes (si lo tienes)

2. **Configurar subcarpeta o subdominio:**
   - `bot.alojamientocorrientes.com` (subdominio)
   - O `alojamientocorrientes.com/bot` (subcarpeta)

3. **Subir archivos:**
   - File Manager o FTP
   - Listo para usar

---

### Opción 3: Firebase Functions + Rewrite (Complejo) ⚠️

**Convertir el bot a Node.js** y usar Firebase Functions.

#### Desventajas:
- ❌ Requiere reescribir todo el código en Node.js
- ❌ Más complejo
- ❌ No recomendado para este caso

---

### Opción 4: Servicio PHP Gratuito (Alternativa) 🆓

**Servicios gratuitos que soportan PHP:**

1. **Render.com** (Gratis con limitaciones)
   - Soporta PHP
   - Fácil de desplegar
   - URL: `bot-alojamientos.onrender.com`

2. **Railway.app** (Gratis con créditos)
   - Soporta PHP
   - Muy fácil
   - URL personalizable

3. **Heroku** (Ya no es gratis, pero tiene plan bajo costo)

---

## 🎯 Mi Recomendación

### Para tu caso específico:

**Opción A: Si tienes presupuesto**
→ **Hostinger Hosting PHP** (más simple, ~$2-5/mes)
- Mismo dominio
- PHP nativo
- Muy fácil

**Opción B: Si quieres gratis**
→ **Google Cloud Run** (gratis hasta cierto límite)
- Integrado con Firebase
- Escalable
- Requiere configuración Docker

**Opción C: Si quieres rápido y gratis**
→ **Render.com** (gratis)
- Muy fácil
- Soporta PHP
- URL diferente (pero puedes usar dominio personalizado)

---

## 📋 ¿Qué necesito saber?

Para darte la mejor solución, necesito saber:

1. **¿Tienes hosting PHP en Hostinger?**
   - ¿O solo compraste el dominio?

2. **¿Prefieres gratis o pagar un poco?**
   - Hostinger: ~$2-5/mes
   - Cloud Run: Gratis (hasta cierto límite)
   - Render: Gratis (con limitaciones)

3. **¿Quieres usar el mismo dominio?**
   - `bot.alojamientocorrientes.com`
   - O puede ser otra URL

---

## 🚀 Próximos Pasos

**Dime qué opción prefieres y te guío paso a paso:**

1. **Hostinger Hosting** → Te guío para contratar y configurar
2. **Cloud Run** → Te ayudo a crear Dockerfile y desplegar
3. **Render.com** → Te guío para desplegar gratis
4. **Otra opción** → La evaluamos juntos

---

## 📝 Nota Importante

**Firebase Hosting NO puede ejecutar PHP directamente.**

Si quieres mantener todo en Firebase/Google Cloud, la mejor opción es **Cloud Run**, que:
- Ejecuta PHP en contenedores Docker
- Se integra con Firebase
- Puede usar tu dominio de Hostinger
- Tiene plan gratuito generoso

¿Cuál opción prefieres? Te guío paso a paso.

