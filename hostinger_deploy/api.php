<?php
/**
 * EcoBot API - Backend Resiliente y Ligero para Hostinger
 * Plataforma: EcoTips
 * 
 * Características:
 * 1. Auto-descubrimiento dinámico de modelos de Groq (inmune a deprecaciones de modelos).
 * 2. Redundancia multi-proveedor (Groq y Google Gemini).
 * 3. Integración nativa con la plataforma EcoTips (sabe en qué web está).
 * 4. Respuestas a prueba de caídas (siempre responde de forma amigable sin romper el frontend).
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Content-Type: application/json; charset=UTF-8");

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'POST';

// Responder a peticiones OPTIONS (CORS preflight)
if ($requestMethod === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ==========================================
// CONFIGURACIÓN DE CLAVES DE API
// ==========================================
if (file_exists(__DIR__ . '/credentials.php')) {
    require_once __DIR__ . '/credentials.php';
}

if (!defined('GROQ_API_KEY')) {
    define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: 'TU_API_KEY_DE_GROQ');
}
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'TU_API_KEY_DE_GEMINI');
} 

// ==========================================
// PERSONALIDAD INTEGRADA DE ECOTIPS
// ==========================================
$systemInstruction = "Eres EcoBot, el asistente inteligente y compañero oficial de EcoTips (la plataforma comunitaria donde compartimos consejos sobre ecología, sustentabilidad y cuidado del planeta). "
    . "Tu forma de hablar es natural, cercana, cálida y humana, como un miembro más de la comunidad EcoTips conversando entre amigos. "
    . "CONOCIMIENTO DE LA PLATAFORMA ECOTIPS:\n"
    . "- EcoTips cuenta con 4 categorías principales de tips: Residuos, Reciclaje, Energía y Naturaleza.\n"
    . "- Los usuarios pueden publicar sus propios consejos haciendo clic en el botón '+', comentar, dar 'Me gusta', seguir a otros miembros y subir de nivel de impacto en su Perfil.\n"
    . "- Siéntete parte activa de la web: cuando des un consejo práctico, puedes sugerir de forma natural que el usuario lo comparta como un EcoTip o revise la categoría correspondiente dentro de EcoTips.\n"
    . "REGLAS DE CONVERSACIÓN QUE DEBES CUMPLIR SIEMPRE:\n"
    . "1. NUNCA uses listas con viñetas, guiones ni encabezados (#, *, -). Escribe todo en prosa fluida como mensajes reales de chat.\n"
    . "2. Habla de forma sencilla, entusiasta y sin tecnicismos.\n"
    . "3. Responde DIRECTO a lo que pregunta el usuario. Si plantea una duda u objeción, reconócela primero con empatía.\n"
    . "4. Mantén las respuestas cortas: 2 a 3 oraciones como máximo. Termina frecuentemente con una pregunta o invitación amigable para continuar la conversación.\n"
    . "5. No uses frases de relleno robóticas como '¡Excelente pregunta!' o 'Como inteligencia artificial'. Ve directo al grano.\n"
    . "6. Tu especialidad es la ecología, la vida verde y la comunidad de EcoTips. Si preguntan algo fuera de tema, responde con simpatía y redirige suavemente la charla hacia un hábito sustentable.";

// ==========================================
// PROCESAMIENTO DE PETICIÓN
// ==========================================
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

$prompt = isset($input['prompt']) ? trim($input['prompt']) : '';
$history = isset($input['history']) ? $input['history'] : null;

// Si no hay historia pero hay prompt, construimos una básica
if (!$history && !empty($prompt)) {
    $history = [["role" => "user", "text" => $prompt]];
}

// Si la petición es GET o no trae prompt/history
if (empty($history)) {
    if ($requestMethod === 'GET') {
        echo json_encode([
            "status" => "online",
            "name" => "EcoBot API - EcoTips",
            "message" => "El servicio de EcoBot está activo y listo para recibir mensajes por POST."
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "El prompt o historial es obligatorio."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Intentar con Groq (con detección automática y fallback de modelos)
$respuesta = tryGroq($history, $systemInstruction);

// 2. Si Groq no responde, intentar con Gemini
if ($respuesta === null) {
    $respuesta = tryGemini($history, $systemInstruction);
}

// 3. Respuesta final
if ($respuesta !== null) {
    echo json_encode([
        "response" => $respuesta,
        "status" => "success",
        "provider" => "cloud_api"
    ], JSON_UNESCAPED_UNICODE);
} else {
    // Respuesta de respaldo amigable e integrada con EcoTips
    echo json_encode([
        "response" => "¡Hola! En este momento estoy actualizando mis fuentes de datos verdes. Mientras tanto, puedes explorar las categorías de Reciclaje y Energía en EcoTips o compartir un consejo con el botón '+'. ¿De qué tema te gustaría charlar?",
        "status" => "success",
        "provider" => "fallback"
    ], JSON_UNESCAPED_UNICODE);
}

// ==========================================
// FUNCIONES AUXILIARES ROBUSTAS
// ==========================================

/**
 * Llama a Groq API con lista prioritaria y auto-descubrimiento dinámico
 */
function tryGroq($history, $systemInstruction) {
    if (GROQ_API_KEY === 'TU_API_KEY_DE_GROQ' || empty(GROQ_API_KEY)) {
        return null;
    }

    $url = "https://api.groq.com/openai/v1/chat/completions";
    
    // Formatear mensajes al estándar OpenAI/Groq
    $messages = [["role" => "system", "content" => $systemInstruction]];
    foreach ($history as $msg) {
        $role = ($msg['role'] === 'assistant' || $msg['role'] === 'bot') ? 'assistant' : 'user';
        $content = $msg['content'] ?? $msg['text'] ?? '';
        if (!empty($content)) {
            $messages[] = ["role" => $role, "content" => $content];
        }
    }

    // Modelos prioritarios conocidos
    $modelsToTry = [
        "openai/gpt-oss-20b",
        "groq/compound-mini",
        "openai/gpt-oss-120b",
        "llama-3.3-70b-versatile",
        "llama-3.1-8b-instant"
    ];

    // Intentar con los modelos prioritarios
    foreach ($modelsToTry as $modelName) {
        $result = sendGroqRequest($url, $modelName, $messages);
        if ($result !== null) {
            return $result;
        }
    }

    // AUTO-DESCUBRIMIENTO DINÁMICO: Si los modelos fijos cambiaron o fueron deprecados,
    // consultamos la lista en tiempo real de Groq para encontrar los modelos activos.
    $discoveredModels = discoverGroqModels();
    foreach ($discoveredModels as $modelName) {
        $result = sendGroqRequest($url, $modelName, $messages);
        if ($result !== null) {
            return $result;
        }
    }

    return null;
}

/**
 * Envía una petición de chat a Groq
 */
function sendGroqRequest($url, $modelName, $messages) {
    $data = [
        "model" => $modelName,
        "messages" => $messages,
        "temperature" => 0.75,
        "max_tokens" => 350
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 7);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $resDecoded = json_decode($response, true);
        $text = $resDecoded['choices'][0]['message']['content'] ?? null;
        if (!empty($text)) {
            return trim($text);
        }
    }
    return null;
}

/**
 * Consulta a Groq los modelos de chat actualmente disponibles
 */
function discoverGroqModels() {
    $ch = curl_init("https://api.groq.com/openai/v1/models");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $chatModels = [];
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $m) {
                $id = $m['id'] ?? '';
                // Filtrar modelos que no son de chat (audio, guardrails, embeddings)
                $lower = strtolower($id);
                if (
                    strpos($lower, 'whisper') === false &&
                    strpos($lower, 'guard') === false &&
                    strpos($lower, 'embed') === false &&
                    strpos($lower, 'orpheus') === false
                ) {
                    $chatModels[] = $id;
                }
            }
        }
    }
    return $chatModels;
}

/**
 * Llama a Google Gemini API (con soporte para 1.5-flash y 2.0-flash)
 */
function tryGemini($history, $systemInstruction) {
    if (GEMINI_API_KEY === 'TU_API_KEY_DE_GEMINI' || empty(GEMINI_API_KEY)) {
        return null;
    }

    // Formatear historial
    $contents = [];
    foreach ($history as $msg) {
        $role = ($msg['role'] === 'assistant' || $msg['role'] === 'bot') ? 'model' : 'user';
        $content = $msg['content'] ?? $msg['text'] ?? '';
        if (!empty($content)) {
            $contents[] = [
                "role" => $role,
                "parts" => [["text" => $content]]
            ];
        }
    }

    $geminiModels = ["gemini-1.5-flash", "gemini-2.0-flash", "gemini-1.5-pro"];

    foreach ($geminiModels as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . GEMINI_API_KEY;

        $data = [
            "contents" => $contents,
            "systemInstruction" => [
                "parts" => [["text" => $systemInstruction]]
            ],
            "generationConfig" => [
                "temperature" => 0.75,
                "maxOutputTokens" => 350
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resDecoded = json_decode($response, true);
            $text = $resDecoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!empty($text)) {
                return trim($text);
            }
        }
    }

    return null;
}
?>
