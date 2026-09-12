# -*- coding: utf-8 -*-
import httpx
from config import settings

class ModelManager:
    """Clase para manejar la generación de respuestas usando APIs en la nube con redundancia y contexto."""
    def __init__(self):
        # Siempre marcado como cargado ya que las llamadas a APIs externas no requieren carga local a RAM
        self.is_loaded = True

    def load_model(self):
        """No hace nada, se mantiene por compatibilidad con la estructura anterior."""
        pass

    async def generate(self, prompt: str = None, history: list = None, max_tokens: int = None, temperature: float = None) -> str:
        """Genera una respuesta intentando primero con Groq y luego con Gemini si falla."""
        # Si no hay historia pero hay prompt, construimos una historia básica con un mensaje
        if not history and prompt:
            history = [{"role": "user", "content": prompt}]
        
        if not history:
            raise ValueError("Debes proporcionar al menos un prompt o un historial de conversacion.")

        # 1. Intentar con Groq
        respuesta = await self.try_groq(history, max_tokens, temperature)
        if respuesta:
            return respuesta

        # 2. Si falla o no está configurado, intentar con Gemini
        respuesta = await self.try_gemini(history, max_tokens, temperature)
        if respuesta:
            return respuesta

        # 3. Si ambos fallan
        raise RuntimeError(
            "Ambos proveedores de IA (Groq y Gemini) fallaron o no están configurados correctamente. "
            "Por favor, revisa tus llaves API en el archivo .env"
        )

    async def try_groq(self, history: list, max_tokens: int = None, temperature: float = None) -> str | None:
        """Consulta la API de Groq (Llama 3.1 8B). Límite gratuito: 30 RPM, 14400 RPD."""
        if not settings.GROQ_API_KEY or settings.GROQ_API_KEY == "TU_API_KEY_DE_GROQ":
            return None

        url = "https://api.groq.com/openai/v1/chat/completions"
        headers = {
            "Authorization": f"Bearer {settings.GROQ_API_KEY}",
            "Content-Type": "application/json"
        }
        temp = temperature if temperature is not None else settings.TEMPERATURE
        temp = max(0.0, min(2.0, temp))
        max_tok = max_tokens if (max_tokens is not None and max_tokens > 0) else settings.MAX_NEW_TOKENS

        # Formatear el historial para el estándar OpenAI de Groq
        formatted_messages = [{"role": "system", "content": settings.SYSTEM_INSTRUCTION}]
        for msg in history:
            role = "assistant" if msg.get("role") in ["assistant", "bot"] else "user"
            formatted_messages.append({"role": role, "content": msg.get("content") or msg.get("text") or ""})

        models_to_try = ["openai/gpt-oss-20b", "groq/compound-mini", "llama-3.3-70b-versatile"]

        for model_name in models_to_try:
            payload = {
                "model": model_name,
                "messages": formatted_messages,
                "temperature": temp,
                "max_tokens": max_tok
            }

            try:
                async with httpx.AsyncClient(timeout=8.0) as client:
                    response = await client.post(url, json=payload, headers=headers)
                    if response.status_code == 200:
                        data = response.json()
                        return data["choices"][0]["message"]["content"].strip()
            except Exception as e:
                print(f"[ERROR] Al conectar con Groq API ({model_name}): {e}")
        return None

    async def try_gemini(self, history: list, max_tokens: int = None, temperature: float = None) -> str | None:
        """Consulta la API de Google Gemini (Gemini 1.5 Flash). Límite gratuito: 15 RPM, 1500 RPD."""
        if not settings.GEMINI_API_KEY or settings.GEMINI_API_KEY == "TU_API_KEY_DE_GEMINI":
            return None

        url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={settings.GEMINI_API_KEY}"
        headers = {
            "Content-Type": "application/json"
        }
        temp = temperature if temperature is not None else settings.TEMPERATURE
        temp = max(0.0, min(2.0, temp))
        max_tok = max_tokens if (max_tokens is not None and max_tokens > 0) else settings.MAX_NEW_TOKENS

        # Formatear el historial para Gemini (roles: 'user' y 'model')
        formatted_contents = []
        for msg in history:
            role = "model" if msg.get("role") in ["assistant", "bot"] else "user"
            formatted_contents.append({
                "role": role,
                "parts": [{"text": msg.get("content") or msg.get("text") or ""}]
            })

        payload = {
            "contents": formatted_contents,
            "systemInstruction": {
                "parts": [
                    {"text": settings.SYSTEM_INSTRUCTION}
                ]
            },
            "generationConfig": {
                "temperature": temp,
                "maxOutputTokens": max_tok
            }
        }

        try:
            async with httpx.AsyncClient(timeout=8.0) as client:
                response = await client.post(url, json=payload, headers=headers)
                if response.status_code == 200:
                    data = response.json()
                    return data["candidates"][0]["content"]["parts"][0]["text"].strip()
                else:
                    print(f"[ADVERTENCIA] Gemini API retorno codigo {response.status_code}: {response.text}")
        except Exception as e:
            print(f"[ERROR] Al conectar con Gemini API: {e}")
        return None

# Instancia global principal que será usada por el servidor Web FastAPI
ai_model = ModelManager()
