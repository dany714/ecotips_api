# -*- coding: utf-8 -*-
import asyncio
from contextlib import asynccontextmanager
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import RedirectResponse
from pydantic import BaseModel
import uvicorn
from config import settings
from model_manager import ai_model

# 1. Manejador de ciclo de vida
@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup
    print("Verificando claves de API...")
    if not settings.GROQ_API_KEY and not settings.GEMINI_API_KEY:
        print("[ADVERTENCIA] No se detecto GROQ_API_KEY ni GEMINI_API_KEY.")
    else:
        if settings.GROQ_API_KEY:
            print("[INFO] Clave de Groq API detectada.")
        if settings.GEMINI_API_KEY:
            print("[INFO] Clave de Gemini API detectada.")
    yield

# 2. Iniciar la aplicación web
app = FastAPI(
    title=settings.API_TITLE,
    version=settings.API_VERSION,
    description="EcoBot - Asistente de IA sobre sostenibilidad y ecologia. Usa APIs en la nube con redundancia y memoria.",
    lifespan=lifespan
)

# 3. Habilitar CORS para permitir peticiones desde cualquier Frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

# 4. Modelos de datos para Entrada y Salida
class Message(BaseModel):
    role: str
    text: str | None = None
    content: str | None = None

class GenerateRequest(BaseModel):
    prompt: str | None = None
    history: list[Message] | None = None
    max_tokens: int | None = None
    temperature: float | None = None

    class Config:
        json_schema_extra = {
            "example": {
                "prompt": "Como puedo reducir el desperdicio de agua?",
                "history": [
                    {"role": "user", "content": "Hola"},
                    {"role": "assistant", "content": "Hola! Soy EcoBot. ¿En qué te ayudo?"}
                ]
            }
        }

class GenerateResponse(BaseModel):
    response: str
    status: str = "success"

@app.get("/", include_in_schema=False)
async def root():
    """Redirige la ruta principal a la documentación."""
    return RedirectResponse(url="/docs")

@app.get("/status")
async def check_status():
    """Ruta para ver si el servidor y las conexiones están activas."""
    return {
        "status": "online",
        "api_name": settings.API_TITLE,
        "is_ai_loaded": ai_model.is_loaded,
        "groq_configured": bool(settings.GROQ_API_KEY),
        "gemini_configured": bool(settings.GEMINI_API_KEY)
    }

@app.post("/generate", response_model=GenerateResponse)
async def generate_text(request: GenerateRequest):
    """Ruta principal donde envías tus preguntas o historial (POST)."""
    if not settings.GROQ_API_KEY and not settings.GEMINI_API_KEY:
        raise HTTPException(
            status_code=500, 
            detail="La API no tiene llaves configuradas. Por favor, define GROQ_API_KEY o GEMINI_API_KEY en tu entorno."
        )
    
    try:
        # Convertir mensajes de Pydantic a diccionarios simples para la clase ModelManager
        formatted_history = None
        if request.history:
            formatted_history = []
            for item in request.history:
                # Soporta tanto 'content' como 'text' para mayor flexibilidad
                content_val = item.content if item.content is not None else item.text
                formatted_history.append({
                    "role": item.role,
                    "content": content_val or ""
                })

        respuesta_ia = await ai_model.generate(
            prompt=request.prompt,
            history=formatted_history,
            max_tokens=request.max_tokens,
            temperature=request.temperature
        )
        return GenerateResponse(response=respuesta_ia)
    except Exception as e:
        print(f"Error procesando la solicitud: {e}")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    print(f"Iniciando servidor {settings.API_TITLE}...")
    print(f"La API va a estar en: http://{settings.HOST}:{settings.PORT}")
    print(f"Mira la documentacion automatica en: http://{settings.HOST}:{settings.PORT}/docs")
    uvicorn.run("main:app", host=settings.HOST, port=settings.PORT, reload=True)
