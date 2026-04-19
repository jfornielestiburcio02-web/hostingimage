<?php
// 1. CABECERAS DE PODER (CORS) - Deben ir ANTES de cualquier otra cosa
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

// Manejo de preflight (cuando el navegador pregunta antes de enviar)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- CONFIGURACIÓN SEGURA DESDE VERCEL ---
$githubToken = getenv('CREA_IMAGEN_HTML'); 
$githubRepo  = "jfornielestiburcio02-web/hostingimage"; 
$proyectoID  = "hostingimage1";
$miDominio   = "https://imagehostinger.vercel.app";

// 2. CAPTURA DE DATOS
// Usamos trim() para evitar espacios invisibles que rompen la API
$apiKey    = isset($_POST['apiKey']) ? trim($_POST['apiKey']) : '';
$usuarioID = isset($_POST['usuarioID']) ? trim($_POST['usuarioID']) : ''; 
$path      = isset($_POST['path']) ? trim($_POST['path']) : '/imagenes/default/';
$image     = $_FILES['image'] ?? null;

// Validación de seguridad básica
if (empty($githubToken)) {
    echo json_encode(["success" => false, "error" => "Configuración incompleta en el servidor (Token)."]);
    exit;
}

if (empty($apiKey) || empty($usuarioID) || !$image) {
    echo json_encode(["success" => false, "error" => "Faltan datos (apiKey, usuarioID o imagen)."]);
    exit;
}

// 3. VALIDACIÓN EN FIRESTORE
$urlFirestore = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($usuarioID);

$ch = curl_init($urlFirestore);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resFS = json_decode(curl_exec($ch), true);
$httpCodeFS = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$apiGuardada = $resFS['fields']['api']['stringValue'] ?? '';

if ($httpCodeFS !== 200 || $apiGuardada !== $apiKey) {
    echo json_encode(["success" => false, "error" => "Credenciales de API no válidas o usuario inexistente."]);
    exit;
}

// Extraemos el nombre de la empresa del path
$partesPath = explode('/', trim($path, '/'));
$nombreEmpresa = $partesPath[1] ?? 'default';

// 4. SUBIDA A GITHUB
$nombreArchivo = time() . "_" . str_replace(' ', '_', basename($image['name']));
$githubFullPath = trim($path, '/') . "/" . $nombreArchivo;
$base64Content = base64_encode(file_get_contents($image['tmp_name']));

$urlGithub = "https://api.github.com/repos/{$githubRepo}/contents/{$githubFullPath}";
$payloadGH = [
    "message" => "Upload from user ID: " . $usuarioID,
    "content" => $base64Content,
    "branch"  => "main"
];

$ch = curl_init($urlGithub);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadGH));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: PHP-Uploader",
    "Authorization: token $githubToken",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$responseGH = curl_exec($ch);
$httpCodeGH = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCodeGH !== 201) {
    $errorGH = json_decode($responseGH, true);
    echo json_encode(["success" => false, "error" => "GitHub Error: " . ($errorGH['message'] ?? 'Fallo de subida')]);
    exit;
}

// 5. CONSTRUCCIÓN DE URL FINAL
$finalUrl = "{$miDominio}/imagenes/{$nombreEmpresa}/{$nombreArchivo}";

// 6. REGISTRO EN FIRESTORE (Historial)
$urlStoreImg = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/lista";

$registro = [
    'fields' => [
        'url' => ['stringValue' => $finalUrl],
        'nombre' => ['stringValue' => $nombreArchivo],
        'empresa' => ['stringValue' => $nombreEmpresa],
        'fecha' => ['timestampValue' => gmdate("Y-m-d\TH:i:s\Z")]
    ]
];

$ch = curl_init($urlStoreImg);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registro));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// 7. RESPUESTA FINAL
echo json_encode([
    "success" => true,
    "url" => $finalUrl,
    "nombre" => $nombreArchivo
]);
exit;
