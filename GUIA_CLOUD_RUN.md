# 🚀 Desplegar en Cloud Run - Pasos Rápidos

## 1. Preparar archivos
- ✅ `Dockerfile` (creado)
- ✅ `.dockerignore` (creado)
- ✅ `cloudbuild.yaml` (opcional, para CI/CD)

## 2. En Cloud Console

### Opción A: Desde la terminal (gcloud CLI)

```bash
# 1. Autenticarse
gcloud auth login

# 2. Configurar proyecto
gcloud config set project TU_PROJECT_ID

# 3. Construir imagen
gcloud builds submit --tag gcr.io/TU_PROJECT_ID/bot-whatsapp

# 4. Desplegar
gcloud run deploy bot-whatsapp \
  --image gcr.io/TU_PROJECT_ID/bot-whatsapp \
  --region us-central1 \
  --platform managed \
  --allow-unauthenticated
```

### Opción B: Desde la consola web

1. **Cloud Build** → Crear build
2. **Cloud Run** → Crear servicio
   - Imagen: `gcr.io/TU_PROJECT_ID/bot-whatsapp`
   - Puerto: 80
   - Permitir tráfico no autenticado: ✅

## 3. Variables de entorno

En Cloud Run → Configuración → Variables de entorno:
- Agrega todas las variables de tu `.env`
- O usa Secret Manager (más seguro)

## 4. Dominio personalizado

Cloud Run → Gestionar dominios personalizados:
- Agrega `bot.alojamientocorrientes.com`
- Configura DNS en Hostinger

## ⚠️ Importante

- El archivo `.env` debe estar en el contenedor
- O usa variables de entorno de Cloud Run
- `firebase-credentials.json` debe estar incluido

