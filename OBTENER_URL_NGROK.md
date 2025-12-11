# Cómo Obtener la URL de ngrok

## Método 1: Desde la Terminal
En la terminal donde ejecutaste ngrok, busca la línea que dice:
```
Forwarding    https://XXXXXXXX.ngrok.io -> http://localhost:8000
```

La parte `https://XXXXXXXX.ngrok.io` es tu URL.

---

## Método 2: Interfaz Web de ngrok (Más Fácil)
1. Abre tu navegador
2. Ve a: http://127.0.0.1:4040
3. Verás el dashboard de ngrok
4. En la parte superior verás tu URL HTTPS
5. Haz clic en "Copy" para copiarla

---

## Método 3: Desde PowerShell
Ejecuta este comando en otra terminal:
```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:4040/api/tunnels" -UseBasicParsing | ConvertFrom-Json | Select-Object -ExpandProperty tunnels | Select-Object -ExpandProperty public_url
```

Esto te dará directamente la URL HTTPS.

