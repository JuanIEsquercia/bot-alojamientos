<?php
/**
 * Página de inicio del bot de WhatsApp
 * El webhook está en /webhook.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot de WhatsApp - Alojamientos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #25D366;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .status.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .status.info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
        .endpoint {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            word-break: break-all;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Bot de WhatsApp - Alojamientos</h1>
        
        <div class="status success">
            <strong>✅ Servidor activo</strong><br>
            El servidor está funcionando correctamente.
        </div>

        <div class="status info">
            <strong>📡 Endpoint del webhook:</strong>
            <div class="endpoint">/webhook.php</div>
            <small>Este es el endpoint que Meta usa para enviar mensajes.</small>
        </div>

        <h2>📋 Información del sistema</h2>
        <ul>
            <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
            <li><strong>Servidor:</strong> PHP Development Server</li>
            <li><strong>Puerto:</strong> 8000</li>
            <li><strong>Hora del servidor:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
        </ul>

        <h2>🔧 Configuración</h2>
        <p>Para que el bot funcione, asegúrate de:</p>
        <ol>
            <li>✅ Tener <code>ngrok</code> corriendo apuntando al puerto 8000</li>
            <li>✅ Configurar el webhook en Meta con la URL de ngrok + <code>/webhook.php</code></li>
            <li>✅ Tener el evento <code>messages</code> suscrito en Meta</li>
            <li>✅ Configurar correctamente las variables de entorno en <code>.env</code></li>
        </ol>

        <h2>📝 Logs</h2>
        <p>Los logs del bot aparecerán en la consola donde ejecutaste <code>php -S localhost:8000</code></p>
        <p>También puedes revisar el dashboard de ngrok en: <a href="http://localhost:4040" target="_blank">http://localhost:4040</a></p>
    </div>
</body>
</html>

