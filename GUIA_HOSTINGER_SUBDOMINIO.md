# 📦 Guía: Desplegar Bot en Hostinger

## Opción 1: Subcarpeta (RECOMENDADO - Más Simple) ✅

Esta es la opción más fácil y rápida. No necesitas crear un subdominio.

### Pasos:

1. **Acceder a File Manager en hPanel:**
   - Inicia sesión en hPanel: https://hpanel.hostinger.com
   - Busca la sección **"Archivos"** o **"File Manager"**
   - Haz clic en **"Administrador de archivos"**

2. **Navegar a tu sitio:**
   - Ve a la carpeta `public_html` (o `domains/tu-dominio.com/public_html`)
   - Esta es la carpeta raíz de tu sitio web

3. **Crear carpeta para el bot:**
   - Haz clic en **"Nueva carpeta"** o **"Crear carpeta"**
   - Nombre: `bot` (o el que prefieras)
   - Presiona Enter o haz clic en "Crear"

4. **Subir archivos:**
   - Entra a la carpeta `bot` que acabas de crear
   - Sube todos los archivos del proyecto:
     - `webhook.php`
     - `autoload.php`
     - `index.php`
     - `.htaccess`
     - Carpeta `src/` (con todo su contenido)
     - `.env` (con tus credenciales)
     - `firebase-credentials.json`

5. **URL del webhook:**
   ```
   https://www.alojamientocorrientes.com/bot/webhook.php
   ```

---

## Opción 2: Subdominio (Más Profesional)

Si prefieres un subdominio como `bot.alojamientocorrientes.com`:

### Método A: Desde hPanel (si está disponible)

1. **En hPanel:**
   - Busca la sección **"Dominios"** o **"Dominios y subdominios"**
   - Haz clic en **"Subdominios"** o **"Gestionar subdominios"**
   - Haz clic en **"Crear subdominio"** o **"Añadir subdominio"**

2. **Configurar:**
   - **Nombre del subdominio:** `bot`
   - **Dominio principal:** `alojamientocorrientes.com`
   - **Directorio:** `public_html/bot` (o deja el predeterminado)
   - Haz clic en **"Crear"** o **"Añadir"**

3. **Esperar propagación:**
   - Puede tardar 5-30 minutos en activarse
   - Verifica que funcione: `https://bot.alojamientocorrientes.com`

### Método B: Si no aparece la opción en hPanel

Algunos planes de Hostinger no permiten crear subdominios desde hPanel. Alternativas:

#### Opción B1: Contactar Soporte
- Abre un ticket en Hostinger
- Pide que creen el subdominio `bot.alojamientocorrientes.com`
- Apunta a `public_html/bot`

#### Opción B2: Usar DNS Manual (Avanzado)
1. Ve a **"DNS"** o **"Zona DNS"** en hPanel
2. Agrega un registro **A** o **CNAME**:
   - **Tipo:** A
   - **Nombre:** `bot`
   - **Valor:** IP de tu servidor (Hostinger te la dará)
   - **TTL:** 3600

---

## Comparación: Subcarpeta vs Subdominio

| Característica | Subcarpeta | Subdominio |
|---------------|------------|------------|
| **Facilidad** | ⭐⭐⭐⭐⭐ Muy fácil | ⭐⭐⭐ Requiere configuración |
| **URL** | `dominio.com/bot/` | `bot.dominio.com` |
| **Tiempo** | Inmediato | 5-30 min (propagación DNS) |
| **Soporte necesario** | No | A veces sí |
| **Recomendado para** | Inicio rápido | Producción profesional |

---

## Recomendación

**Para empezar rápido:** Usa **Subcarpeta** (Opción 1)
- Es más simple
- Funciona inmediatamente
- No requiere configuración DNS
- URL: `https://www.alojamientocorrientes.com/bot/webhook.php`

**Para producción profesional:** Usa **Subdominio** (Opción 2)
- URL más limpia: `https://bot.alojamientocorrientes.com/webhook.php`
- Mejor organización
- Más fácil de mantener

---

## Pasos Comunes (Ambas Opciones)

### 1. Subir Archivos

**Opción A: File Manager (Web)**
- Arrastra y suelta archivos
- O usa "Subir archivos"

**Opción B: FTP (Más rápido para muchos archivos)**
- Usa FileZilla o similar
- Datos FTP en hPanel → **"FTP Accounts"**
- Conecta y sube los archivos

### 2. Configurar Permisos

En File Manager, selecciona los archivos y cambia permisos:

```
.env → 600 (solo lectura para propietario)
firebase-credentials.json → 600
.htaccess → 644
webhook.php → 755
```

**Cómo hacerlo:**
- Click derecho en el archivo → **"Cambiar permisos"** o **"Chmod"**
- Ingresa el número (600, 644, 755)

### 3. Verificar PHP

En hPanel:
- Ve a **"PHP"** o **"Select PHP Version"**
- Asegúrate de tener **PHP 8.0 o superior**
- Verifica que estén habilitadas:
  - ✅ `curl`
  - ✅ `openssl`
  - ✅ `json`

### 4. Probar el Bot

1. Abre en navegador:
   ```
   https://www.alojamientocorrientes.com/bot/
   ```
   Deberías ver la página de información del bot.

2. Verifica el webhook:
   ```
   https://www.alojamientocorrientes.com/bot/webhook.php
   ```
   Meta debería poder acceder a esta URL.

---

## Configuración Final

### Actualizar `.env` para Producción

Cuando subas a Hostinger, edita el `.env` y agrega:

```env
APP_ENV=production
```

Esto activará todas las protecciones de seguridad.

### Actualizar Webhook en Meta

1. Ve a Meta for Developers
2. WhatsApp → Configuración → Webhooks
3. Cambia la URL a:
   - **Subcarpeta:** `https://www.alojamientocorrientes.com/bot/webhook.php`
   - **Subdominio:** `https://bot.alojamientocorrientes.com/webhook.php`
4. Guarda los cambios

---

## Solución de Problemas

### "No puedo encontrar la opción de subdominios"
→ Usa **Subcarpeta** (Opción 1). Es más simple y funciona igual.

### "El webhook no responde"
- Verifica que `webhook.php` esté en la carpeta correcta
- Verifica permisos (755 para `webhook.php`)
- Revisa los logs en `bot.log`

### "Error 500 en el webhook"
- Verifica que `.env` tenga todas las variables
- Verifica permisos de `firebase-credentials.json` (600)
- Revisa los logs del servidor en hPanel

### "No puedo subir archivos grandes"
- Usa FTP en lugar de File Manager
- O comprime los archivos y descomprime en el servidor

---

## Checklist Final

- [ ] Archivos subidos a Hostinger
- [ ] Permisos configurados (600 para `.env` y credenciales)
- [ ] `.env` actualizado con `APP_ENV=production`
- [ ] PHP 8.0+ verificado
- [ ] Extensiones PHP habilitadas (curl, openssl, json)
- [ ] Webhook actualizado en Meta
- [ ] Prueba enviando un mensaje desde WhatsApp

---

**¿Necesitas ayuda con algún paso específico?** Avísame y te guío paso a paso.

