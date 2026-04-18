<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// --- CONFIGURACIÓN MAESTRA ---
$githubToken = "TU_TOKEN_GITHUB_AQUI"; // Tu Personal Access Token
$githubRepo  = "USUARIO/REPOSITORIO";  // Tu repo de GitHub
$proyectoID  = "hostingimage1";

// 1. RECEPCIÓN DE DATOS
$apiKey = $_POST['apiKey'] ?? '';
$path   = $_POST['path'] ?? '/imagenes/default/';
$image  = $_FILES['image'] ?? null;

if (empty($apiKey) || !$image) {
    echo json_encode(["success" => false, "error" => "Faltan parámetros o imagen."]);
    exit;
}

// 2. VALIDAR API KEY Y OBTENER USUARIO (Búsqueda profunda en 'claves')
$urlQuery = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents:runQuery";

$queryData = [
    'structuredQuery' => [
        'from' => [['collectionId' => 'claves', 'allDescendants' => true]],
        'where' => [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'api'],
                'op' => 'EQUAL',
                'value' => ['stringValue' => $apiKey]
            ]
        ],
        'limit' => 1
    ]
];

$ch = curl_init($urlQuery);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resFS = json_decode(curl_exec($ch), true);
$httpCodeFS = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verificación de existencia
if ($httpCodeFS !== 200 || empty($resFS) || !isset($resFS[0]['document'])) {
    echo json_encode(["success" => false, "error" => "API Key no valida o no autorizada."]);
    exit;
}

// EXTRAEMOS EL ID DEL USUARIO DESDE EL PATH DEL DOCUMENTO
// El path suele ser: projects/.../databases/(default)/documents/apis/{ID_USUARIO}/claves/{ID_DOC}
$fullPath = $resFS[0]['document']['name'];
$partes = explode('/', $fullPath);
$usuarioID = $partes[count($partes) - 3]; // Es el ID que está detrás de 'claves'

// 3. PROCESAR SUBIDA A GITHUB
$nombreArchivo = time() . "_" . basename($image['name']);
$pathLimpio = trim($path, '/') . "/" . $nombreArchivo;
$base64Image = base64_encode(file_get_contents($image['tmp_name']));

$urlGithub = "https://api.github.com/repos/{$githubRepo}/contents/{$pathLimpio}";

$payloadGH = [
    "message" => "Upload via API Key: " . $apiKey,
    "content" => $base64Image,
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
$resGH = curl_exec($ch);
$httpCodeGH = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCodeGH !== 201) {
    echo json_encode(["success" => false, "error" => "Error al guardar en GitHub."]);
    exit;
}

// URL FINAL USANDO CDN PARA MEJOR RENDIMIENTO
$finalUrl = "https://cdn.jsdelivr.net/gh/{$githubRepo}/{$pathLimpio}";

// 4. VINCULAR EN FIRESTORE: imagenes / {usuarioID} / {documentoAuto}
$urlStore = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/archivos";

$registroFirestore = [
    'fields' => [
        'url' => ['stringValue' => $finalUrl],
        'nombre' => ['stringValue' => $nombreArchivo],
        'apiKeyUsed' => ['stringValue' => $apiKey],
        'fecha' => ['timestampValue' => gmdate("Y-m-d\TH:i:s\Z")]
    ]
];

$ch = curl_init($urlStore);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registroFirestore));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// 5. RESPUESTA EXITOSA AL JS
echo json_encode([
    "success" => true,
    "url" => $finalUrl
]);
