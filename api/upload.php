<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// --- CONFIGURACIÓN DESDE VERCEL ---
// getenv lee la variable que configuraste en el panel de Vercel
$githubToken = getenv('CREA_IMAGEN_HTML'); 
$githubRepo  = "jfornielestiburcio02-web/hostingimage"; // Cambia esto por tu repo real
$proyectoID  = "hostingimage1";

// 1. CAPTURA DE DATOS
$apiKey    = $_POST['apiKey'] ?? '';
$usuarioID = $_POST['usuarioID'] ?? ''; 
$path      = $_POST['path'] ?? '/imagenes/default/';
$image     = $_FILES['image'] ?? null;

if (empty($githubToken)) {
    echo json_encode(["success" => false, "error" => "Error interno: Token no configurado en Vercel."]);
    exit;
}

if (empty($apiKey) || empty($usuarioID) || !$image) {
    echo json_encode(["success" => false, "error" => "Faltan datos en la subida."]);
    exit;
}

// 2. VALIDACIÓN EN FIRESTORE (apis/{usuarioID})
$urlFirestore = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($usuarioID);

$ch = curl_init($urlFirestore);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = json_decode(curl_exec($ch), true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$apiGuardada = $data['fields']['api']['stringValue'] ?? '';

if ($httpCode !== 200 || $apiGuardada !== $apiKey) {
    echo json_encode(["success" => false, "error" => "API Key no válida."]);
    exit;
}

// 3. SUBIDA A GITHUB (REPOSITORIO PRIVADO)
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
    echo json_encode(["success" => false, "error" => "GitHub rechazo la subida. Revisa permisos del Token."]);
    exit;
}

// 4. LA URL FINAL
// IMPORTANTE: Al ser repo PRIVADO, no puedes usar jsDelivr.
// Si quieres que la imagen se vea, el repo debería ser PÚBLICO.
// Si lo mantienes PRIVADO, esta URL solo funcionará si tienes activado GitHub Pages (Público).
$finalUrl = "https://raw.githubusercontent.com/{$githubRepo}/main/{$githubPath}";

// 5. REGISTRO EN FIRESTORE
$urlStoreImg = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/lista";
$registro = [
    'fields' => [
        'url' => ['stringValue' => $finalUrl],
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

echo json_encode(["success" => true, "url" => $finalUrl]);
