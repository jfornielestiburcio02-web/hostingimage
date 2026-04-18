<?php
// Permitir que cualquier web externa use esta API
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Si es una petición preflight (OPTIONS), salir rápido
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// --- CONFIGURACIÓN MAESTRA (EDITA ESTO) ---
$githubToken = "TU_TOKEN_DE_GITHUB_AQUI"; 
$githubRepo  = "TU_USUARIO/TU_REPOSITORIO"; // Ejemplo: "pepe/imagenes-hosting"
$proyectoID  = "hostingimage1";

// 1. RECEPCIÓN DE DATOS
$apiKey = $_POST['apiKey'] ?? '';
$path   = $_POST['path'] ?? '/imagenes/default/';
$image  = $_FILES['image'] ?? null;

if (empty($apiKey) || empty($image)) {
    echo json_encode(["success" => false, "error" => "Faltan datos obligatorios (apiKey o imagen)."]);
    exit;
}

// 2. VALIDAR API KEY EN FIRESTORE (Buscamos en la colección 'apis' el campo 'api')
// Nota: Ajustamos la consulta para buscar dentro de subcolecciones si es necesario, 
// pero siguiendo tu lógica de búsqueda global:
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
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resFirestore = json_decode(curl_exec($ch), true);
$httpCodeFS = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verificamos si Firestore encontró el documento con esa API
if ($httpCodeFS !== 200 || empty($resFirestore) || !isset($resFirestore[0]['document'])) {
    echo json_encode(["success" => false, "error" => "API Key no valida o no autorizada."]);
    exit;
}

// 3. PREPARAR LA IMAGEN PARA GITHUB
$extension = pathinfo($image['name'], PATHINFO_EXTENSION);
$nombreLimpio = time() . "_" . uniqid() . "." . $extension;

// Limpiamos el path y unimos con el nombre del archivo
$fullPathGithub = trim($path, '/') . "/" . $nombreLimpio;

// Convertir imagen a Base64 para la API de GitHub
$base64Image = base64_encode(file_get_contents($image['tmp_name']));

// 4. SUBIDA A GITHUB (API REST)
$urlGithub = "https://api.github.com/repos/{$githubRepo}/contents/{$fullPathGithub}";

$payloadGithub = [
    "message" => "Upload by HostingImage API - " . $nombreLimpio,
    "content" => $base64Image,
    "branch"  => "main" // Cambia a 'master' si tu repo usa la vieja nomenclatura
];

$ch = curl_init($urlGithub);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadGithub));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: HostingImage-App",
    "Authorization: token $githubToken",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$resGithub = curl_exec($ch);
$httpCodeGH = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 5. RESPUESTA FINAL
if ($httpCodeGH === 201) {
    // Generar la URL pública (usando jsDelivr para que cargue rápido)
    $cdnUrl = "https://cdn.jsdelivr.net/gh/{$githubRepo}/{$fullPathGithub}";
    echo json_encode([
        "success" => true,
        "url" => $cdnUrl,
        "path" => $fullPathGithub
    ]);
} else {
    $errorDetails = json_decode($resGithub, true);
    echo json_encode([
        "success" => false, 
        "error" => "Error al guardar en repositorio.",
        "details" => $errorDetails['message'] ?? 'Unknown GitHub Error'
    ]);
}
?>
