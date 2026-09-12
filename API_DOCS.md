# 📖 Documentación de la API de EcoAmigo

La API de EcoAmigo funciona con el mismo formato JSON de entrada y salida, ya sea que utilices la versión en **Python (FastAPI)** o el puente en **PHP (`api.php`)** alojado en Hostinger.

---

## 🟢 1. Endpoint `/generate` (o `api.php`)

Es la ruta principal para enviar preguntas al chatbot.

*   **Método:** `POST`
*   **Encabezados (Headers):** `Content-Type: application/json`
*   **URLs de producción típicas:**
    *   *Opción PHP en Hostinger:* `https://tupagina.com/api.php`
    *   *Opción FastAPI en local:* `http://localhost:8000/generate`

### 📥 Formato de Entrada (Cuerpo JSON)

| Parámetro | Tipo | Obligatorio | Descripción |
| :--- | :--- | :--- | :--- |
| `prompt` | String | **Sí** | El mensaje o consulta que el usuario le envía a EcoAmigo. |
| `max_tokens` | Entero | No | Límite máximo de tokens (palabras) para la respuesta (por defecto 300). |
| `temperature` | Decimal | No | Grado de creatividad (0.1 = preciso, 0.9 = muy creativo. Por defecto 0.7). |

**Ejemplo de Petición (Request):**
```json
{
  "prompt": "¿Cómo puedo reciclar botellas de plástico en mi casa?",
  "max_tokens": 150,
  "temperature": 0.5
}
```

### 📤 Formato de Salida (Respuesta JSON)

*   **Código HTTP:** `200 OK`

**Ejemplo de Respuesta exitosa (Response):**
```json
{
  "response": "¡Hola! Reciclar botellas de plástico en casa es súper fácil. Primero, asegúrate de enjuagarlas para quitar residuos de líquidos. Luego, aplástalas para ahorrar espacio en tu contenedor de reciclaje y colócalas en el cesto amarillo o llévalas a un punto limpio de tu vecindario. ¡Pequeñas acciones hacen una gran diferencia! 🌿",
  "status": "success"
}
```

**Ejemplo de Respuesta con Error:**
```json
{
  "status": "error",
  "message": "Ambos servicios de IA gratuitos están saturados en este momento. Por favor, intenta de nuevo en unos segundos."
}
```

---

## 🟢 2. Endpoint `/status` (Solo en versión Python FastAPI)

Sirve para verificar la salud y el estado del servidor.

*   **Método:** `GET`
*   **URL:** `http://localhost:8000/status`

**Respuesta de Ejemplo:**
```json
{
  "status": "online",
  "api_name": "EcoAmigo API",
  "is_ai_loaded": true,
  "groq_configured": true,
  "gemini_configured": true
}
```
