<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// --- CONFIGURACIÓN ---
$githubToken = "CREA_IMAGEN_HTML";
$githubRepo  = "jfornielestiburcio02-web/hostingimage"; 
$proyectoID  = "hostingimage1";

// 1. CAPTURA
$apiKey    = $_POST['apiKey'] ?? '';
$usuarioID = $_POST['usuarioID'] ?? ''; // El cliente debe enviar su ID de usuario
$path      = $_POST['path'] ?? '/imagenes/default/';
$image     = $_FILES['image'] ?? null;

if (empty($apiKey) || empty($usuarioID) || !$image) {
    echo json_encode(["success" => false, "error" => "Faltan datos (apiKey, usuarioID o imagen)"]);
    exit;
}

// 2. VALIDACIÓN DIRECTA EN FIRESTORE
// Buscamos el documento del usuario directamente: apis/{usuarioID}
$urlFirestore = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($usuarioID);

$ch = curl_init($urlFirestore);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$data = json_decode($response, true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Comprobamos si el documento existe y si la clave coincide
$apiGuardada = $data['fields']['api']['stringValue'] ?? '';

if ($httpCode !== 200 || $apiGuardada !== $apiKey) {
    echo json_encode(["success" => false, "error" => "API Key no válida para este usuario."]);
    exit;
}

// 3. SUBIDA A GITHUB
$nombreArchivo = time() . "_" . str_replace(' ', '_', $image['name']);
$githubPath = trim($path, '/') . "/" . $nombreArchivo;
$base64Content = base64_encode(file_get_contents($image['tmp_name']));

$urlGithub = "https://api.github.com/repos/{$githubRepo}/contents/{$githubPath}";
$payloadGH = [
    "message" => "Upload from user: " . $usuarioID,
    "content" => $base64Content
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
$resGH = curl_exec($ch);
$httpCodeGH = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCodeGH !== 201) {
    echo json_encode(["success" => false, "error" => "Error en GitHub"]);
    exit;
}

// 4. REGISTRO EN LA GALERÍA (imagenes/{usuario}/lista)
$urlFinal = "https://cdn.jsdelivr.net/gh/{$githubRepo}/{$githubPath}";
$urlStoreImg = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/lista";

$registro = [
    'fields' => [
        'url' => ['stringValue' => $urlFinal],
        'nombre' => ['stringValue' => $nombreArchivo],
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

echo json_encode(["success" => true, "url" => $urlFinal]);
