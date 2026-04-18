<?php
header("Content-Type: application/json");

$usuario   = $_POST['USUARIO'] ?? '';
$pass      = $_POST['CONTRASENA'] ?? '';
$passRep   = $_POST['REPETIR_CONTRASENA'] ?? '';
$proyectoID = "hostingimage1";

if (empty($usuario) || empty($pass) || $pass !== $passRep) {
    die("Error: Las contraseñas no coinciden o faltan datos.");
}

// Generar un phpsession único para el usuario
$phpsession = bin2hex(random_bytes(16));

// URL para crear el documento con ID específico (el nombre de usuario)
$urlFirestore = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/usuarios?documentId=" . urlencode($usuario);

$campos = [
    'fields' => [
        'contrasena' => ['stringValue' => $pass],
        'phpsession' => ['stringValue' => $phpsession]
    ]
];

$ch = curl_init($urlFirestore);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($campos));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    // Registro exitoso, redirigir al login o al panel
    header("Location: login.php?registro=exito");
} else {
    echo "Error al registrar: " . $response;
}
?>
