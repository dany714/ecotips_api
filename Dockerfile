# Usa una imagen oficial de Python súper ligera
FROM python:3.10-slim

# Evita que Python escriba archivos .pyc en el disco (ahorrar espacio)
ENV PYTHONDONTWRITEBYTECODE 1
# Evita que Python guarde en búfer la salida estándar y de error (logs en tiempo real)
ENV PYTHONUNBUFFERED 1

# Crea y muévete al directorio de la app dentro del contenedor
WORKDIR /app

# Copia solo requirements.txt primero (Para aprovechar la caché de Docker)
COPY requirements.txt .

# Instala las dependencias de Python
# El flag --no-cache-dir reduce drásticamente el peso de la imagen final
RUN pip install --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

# Copia TODO el código de la API al contenedor
COPY . .

# Expone el puerto que usa FastAPI
EXPOSE 8000

# Comando para arrancar el servidor web (Optimizado para producción)
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "1"]
