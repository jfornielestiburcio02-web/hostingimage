<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// --- CONFIGURACIÓN MAESTRA ---
$githubToken = "TU_TOKEN_GITHUB";
$githubRepo  = "USUARIO/REPO"; 
$proyectoID  = "hostingimage1";

// 1. RECEPCIÓN
$apiKey = $_POST['apiKey'] ?? '';
$path   = $_POST['path'] ?? '/imagenes/default/';
$image  = $_FILES['image'] ?? null;

if (empty($apiKey) || !$image) {
    echo json_encode(["success" => false, "error" => "Datos incompletos"]);
    exit;
}

// 2. BUSCAR DUEÑO DE LA API EN FIRESTORE
$urlQuery = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents:runQuery";
$queryData = [
    'structuredQuery' => [
        'from' => [['collectionId' => 'claves', 'allDescendants' => true]],
        'where' => ['fieldFilter' => ['field' => ['fieldPath' => 'api'], 'op' => 'EQUAL', 'value' => ['stringValue' => $apiKey]]],
        'limit' => 1
    ]
];

$ch = curl_init($urlQuery);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resFS = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($resFS) || !isset($resFS[0]['document'])) {
    echo json_encode(["success" => false, "error" => "API Key no valida"]);
    exit;
}

// Extraemos el ID del usuario (está en la ruta del documento encontrado)
// Ruta: projects/.../databases/.../documents/apis/USUARIO_ID/claves/ID_DOC
$fullPathDoc = $resFS[0]['document']['name'];
$partes = explode('/', $fullPathDoc);
$usuarioID = $partes[count($partes) - 3]; // El ID del usuario es el antepenúltimo

// 3. SUBIDA A GITHUB
$nombreArchivo = time() . "_" . $image['name'];
$fullPathGithub = trim($path, '/') . "/" . $nombreArchivo;
$base64Image = base64_encode(file_get_contents($image['tmp_name']));

$urlGithub = "https://api.github.com/repos/{$githubRepo}/contents/{$fullPathGithub}";
$payloadGH = [
    "message" => "Upload by API",
    "content" => $base64Image
];

$ch = curl_init($urlGithub);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadGH));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: PHP", "Authorization: token $githubToken", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resGH = curl_exec($ch);
$httpCodeGH = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCodeGH !== 201) {
    echo json_encode(["success" => false, "error" => "Error GitHub"]);
    exit;
}

$finalUrl = "https://cdn.jsdelivr.net/gh/{$githubRepo}/{$fullPathGithub}";

// 4. GUARDAR REGISTRO EN FIRESTORE (imagenes / {usuario} / )
// Usamos POST para que Firebase genere un ID de documento automático
$urlStoreImg = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/lista";

$registroImg = [
    'fields' => [
        'url' => ['stringValue' => $finalUrl],
        'fecha' => ['timestampValue' => gmdate("Y-m-d\TH:i:s\Z")],
        'nombre' => ['stringValue' => $nombreArchivo]
    ]
];

$ch = curl_init($urlStoreImg);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registroImg));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_exec($ch);
curl_close($ch);

// 5. RESPUESTA AL JS
echo json_encode([
    "success" => true,
    "url" => $finalUrl
]);
