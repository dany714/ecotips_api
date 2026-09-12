# 💻 Guía de Consumo Frontend para EcoAmigo

Esta guía te muestra cómo conectar el chatbot de **EcoAmigo** directamente a tu página web (HTML/JS/React) consumiendo el endpoint del puente PHP en Hostinger o de la API en Python (FastAPI).

---

## 1. Código HTML/JS Completo (Chat Widget Listo para Usar)

Puedes integrar este widget de chat en la esquina de tu página web. Solo copia y pega este código en un archivo `index.html` o agrégalo a tu plantilla de Hostinger.

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoAmigo Chatbot</title>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f0f4f1;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        #chat-container {
            width: 400px;
            height: 550px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e1e7e2;
        }
        #chat-header {
            background: #2e7d32;
            color: white;
            padding: 16px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1em;
        }
        #chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .message {
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.95em;
            line-height: 1.4;
        }
        .user-message {
            background: #e8f5e9;
            color: #1b5e20;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }
        .bot-message {
            background: #f1f8e9;
            color: #33691e;
            align-self: flex-start;
            border-bottom-left-radius: 2px;
            border: 1px solid #dcedc8;
        }
        #chat-input-area {
            display: flex;
            border-top: 1px solid #e1e7e2;
            padding: 8px;
            background: #fafafa;
        }
        #chat-input {
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 20px;
            padding: 10px 16px;
            outline: none;
            font-size: 0.95em;
        }
        #chat-input:focus {
            border-color: #2e7d32;
        }
        #send-btn {
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin-left: 8px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            transition: background 0.2s;
        }
        #send-btn:hover {
            background: #1b5e20;
        }
        #send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<div id="chat-container">
    <div id="chat-header">🌿 EcoAmigo - Consejos de Ecología</div>
    <div id="chat-messages">
        <div class="message bot-message">¡Hola! Soy EcoAmigo. 😊 ¿En qué puedo ayudarte a cuidar el planeta hoy?</div>
    </div>
    <div id="chat-input-area">
        <input type="text" id="chat-input" placeholder="Pregúntame algo..." autocomplete="off">
        <button id="send-btn" onclick="sendMessage()">➔</button>
    </div>
</div>

<script>
    // URL de tu API en producción (PHP en Hostinger o FastAPI en la nube)
    // Cambia esto a "https://tu-dominio.com/api.php" si lo subes a Hostinger
    const API_URL = "http://127.0.0.1:8000/generate"; 

    const chatInput = document.getElementById("chat-input");
    const chatMessages = document.getElementById("chat-messages");
    const sendBtn = document.getElementById("send-btn");

    // Enviar mensaje al pulsar Enter
    chatInput.addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            sendMessage();
        }
    });

    async function sendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        // Desactivar controles
        chatInput.value = "";
        chatInput.disabled = true;
        sendBtn.disabled = true;

        // Agregar mensaje de usuario en pantalla
        appendMessage(text, "user-message");

        // Agregar indicador de carga
        const loadingDiv = appendMessage("Escribiendo consejos...", "bot-message");

        try {
            const response = await fetch(API_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ prompt: text })
            });

            const data = await response.json();
            
            // Reemplazar cargando con la respuesta de la IA
            if (data.status === "success" && data.response) {
                loadingDiv.innerText = data.response;
            } else {
                loadingDiv.innerText = "⚠️ Lo siento, no pude procesar la respuesta.";
            }
        } catch (error) {
            console.error("Error:", error);
            loadingDiv.innerText = "❌ Error al conectar con el servidor.";
        }

        // Reactivar controles
        chatInput.disabled = false;
        sendBtn.disabled = false;
        chatInput.focus();
        
        // Auto scroll abajo
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function appendMessage(text, className) {
        const div = document.createElement("div");
        div.className = `message ${className}`;
        div.innerText = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return div;
    }
</script>

</body>
</html>
```

---

## 2. Buenas Prácticas de Frontend

1.  **Manejo de CORS:** Ambas APIs creadas (la de PHP en `api.php` y la de FastAPI en Python) están configuradas con `Access-Control-Allow-Origin: *`. Esto permite conectarte directamente desde cualquier web. En producción, puedes cambiar `*` por el dominio específico de tu frontend para mayor seguridad.
2.  **Prevención de Doble Envío:** Desactiva siempre el campo de entrada de texto y el botón de enviar mientras se espera la respuesta (tal como está implementado en la función `sendMessage()` anterior). Esto evita que los usuarios envíen múltiples peticiones simultáneas, saturando tus límites de API.
3.  **Límite de caracteres en Frontend:** Limita los prompts de los usuarios a un máximo razonable (por ejemplo, 500 caracteres) utilizando el atributo `maxlength` en el `<input>` para ahorrar tokens de tu API y evitar abusos.
