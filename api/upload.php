<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// --- CONFIGURACIÓN SEGURA DESDE VERCEL ---
$githubToken = getenv('CREA_IMAGEN_HTML'); // Variable de entorno en Vercel
$githubRepo  = "jfornielestiburcio02-web"; // CAMBIA ESTO
$proyectoID  = "hostingimage1";
$miDominio   = "https://hostingimage-bice.vercel.app";

// 1. CAPTURA DE DATOS
$apiKey    = $_POST['apiKey'] ?? '';
$usuarioID = $_POST['usuarioID'] ?? ''; 
$path      = $_POST['path'] ?? '/imagenes/default/';
$image     = $_FILES['image'] ?? null;

// Validación de seguridad básica
if (empty($githubToken)) {
    echo json_encode(["success" => false, "error" => "Configuración incompleta en el servidor."]);
    exit;
}

if (empty($apiKey) || empty($usuarioID) || !$image) {
    echo json_encode(["success" => false, "error" => "Faltan datos (apiKey, usuarioID o imagen)."]);
    exit;
}

// 2. VALIDACIÓN EN FIRESTORE (Colección simplificada: apis/{usuarioID})
$urlFirestore = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($usuarioID);

$ch = curl_init($urlFirestore);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resFS = json_decode(curl_exec($ch), true);
$httpCodeFS = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$apiGuardada = $resFS['fields']['api']['stringValue'] ?? '';

if ($httpCodeFS !== 200 || $apiGuardada !== $apiKey) {
    echo json_encode(["success" => false, "error" => "Credenciales de API no válidas."]);
    exit;
}

// Extraemos el nombre de la empresa del path para la URL final
// Si el path es /imagenes/EmpresaEjemplar/, extrae "EmpresaEjemplar"
$partesPath = explode('/', trim($path, '/'));
$nombreEmpresa = $partesPath[1] ?? 'default';

// 3. SUBIDA A GITHUB
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
    echo json_encode(["success" => false, "error" => "Error al guardar en almacenamiento."]);
    exit;
}

// 4. CONSTRUCCIÓN DE TU URL PERSONALIZADA
// Esto devuelve: https://hostingimage-bice.vercel.app/imagenes/Empresa/12345_foto.jpg
$finalUrl = "{$miDominio}/imagenes/{$nombreEmpresa}/{$nombreArchivo}";

// 5. REGISTRO EN LA COLECCIÓN DE IMÁGENES DEL USUARIO
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

// 6. RESPUESTA AL FRONTEND
echo json_encode([
    "success" => true,
    "url" => $finalUrl,
    "nombre" => $nombreArchivo
]);
