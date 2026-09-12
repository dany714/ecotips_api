#  EcoAmigo - API de IA de Alta Concurrencia (Costo $0)

> **API de Inteligencia Artificial para el chatbot "EcoAmigo"**, diseñada para ser rápida, funcional, soportar múltiples usuarios simultáneos y funcionar con **costo de hosting de $0** mediante las APIs de **Groq** y **Google Gemini**.

##  Características

*   **100% Gratis y de Alta Concurrencia:** Utiliza las APIs en la nube de Groq y Google Gemini en sus capas gratuitas.
*   **Redundancia Automática (Fallback):** Si un servicio está caído o supera su límite, el sistema cambia automáticamente al otro en milisegundos.
*   **Doble Opción de Despliegue:**
    1.  **Puente PHP (`api.php`):** Ideal para subir directamente a tu hosting en **Hostinger** (costo extra $0, sin arranques en frío, siempre activo).
    2.  **FastAPI en Python (`main.py`):** Servidor asíncrono y ultraligero (< 150MB en Docker), listo para alojarse gratis en Vercel o Render.
*   **Inteligencia de Nivel Humano:** Utiliza modelos profesionales de lenguaje (`Llama 3.1 8B` y `Gemini 1.5 Flash`) optimizados para español.

---

## ⚙️ 1. Configuración de Llaves de API (Gratis)

Antes de iniciar, debes obtener tus llaves API gratuitas:
1.  **Groq API Key:** Regístrate y genérala en [Groq Console](https://console.groq.com/) (Gratis, hasta 30 peticiones/minuto).
2.  **Gemini API Key:** Regístrate y genérala en [Google AI Studio](https://aistudio.google.com/) (Gratis, hasta 15 peticiones/minuto).

---

## 🚀 2. Opciones de Despliegue en Producción

### Opción A: Despliegue Directo en Hostinger (Recomendado - PHP)
Si ya cuentas con un plan de hosting en Hostinger:
1.  Abre el archivo [api.php](api.php) y coloca tus claves de API en las constantes `GROQ_API_KEY` y `GEMINI_API_KEY`.
2.  Sube el archivo `api.php` a la raíz o carpeta pública de tu hosting en Hostinger.
3.  ¡Listo! Tu API estará disponible 24/7 en `https://tupagina.com/api.php` sin pagar hosting extra y sin retrasos.

### Opción B: Ejecución Local o Despliegue en la Nube (Python - FastAPI)
Si deseas ejecutarlo localmente o en servicios como Render/Vercel:
1.  Instala las dependencias ligeras (tarda menos de 5 segundos):
    ```bash
    pip install -r requirements.txt
    ```
2.  Renombra o edita tu archivo `.env` y añade tus claves:
    ```env
    GROQ_API_KEY=tu_clave_aqui
    GEMINI_API_KEY=otra_clave_aqui
    ```
3.  Inicia la API localmente:
    ```bash
    python main.py
    ```
4.  La API iniciará en `http://127.0.0.1:8000`. Puedes probarla y ver la documentación interactiva en `http://127.0.0.1:8000/docs`.

---

## 🧠 3. Archivos de Guía

*   📙 [**API_DOCS.md**](API_DOCS.md): Detalle de endpoints, formatos de petición y respuestas JSON.
*   💻 [**CONSUMPTION_GUIDE.md**](CONSUMPTION_GUIDE.md): Ejemplos prácticos en JavaScript/Fetch para conectar el chatbot de EcoAmigo a tu página web de Hostinger.
