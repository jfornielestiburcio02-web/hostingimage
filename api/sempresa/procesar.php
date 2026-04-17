<?php
// CARGAR CONFIGURACIÓN DESDE LA RUTA PRIVADA
include $_SERVER['DOCUMENT_ROOT'] . '/api/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['USUARIO'];
    $pass = $_POST['CONTRASENA'];

    // URL DE LA REST API DE FIREBASE (JSON)
    // USAMOS EL PROJECTID DE TU CONFIG.PHP
    $url = "https://{$FIREBASE_CONFIG['projectId']}.firebaseio.com/usuarios/{$user}.json";

    // CONSULTA MEDIANTE CURL (INVISIBLE AL NAVEGADOR)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // VALIDACIÓN
    if ($data && isset($data['contrasena']) && $data['contrasena'] === $pass) {
        
        // GENERAR NUEVA PHPSESSION
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $phpsess = substr(str_shuffle($letras), 0, 10) . rand(100, 999);
        
        // ACTUALIZAR PHPSESSION EN FIREBASE (PATCH)
        $updateData = json_encode(['phpsession' => $phpsess]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $updateData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        // GENERAR VALORES PARA REDIRECCIÓN
        $aleatorio_letras = substr(str_shuffle($letras), 0, 8);
        $rndval = rand(100000, 999999);

        // REDIRECCIÓN FINAL
        $destino = "/concedido.php?ALEATORIO=$aleatorio_letras&rndval=$rndval&phpsession=$phpsess&PAGINA_ANTERIOR_CON_BOTONERA=-1&click|top|action";
        header("Location: $destino");
        exit();

    } else {
        echo "<script>alert('ERROR: ACCESO DENEGADO'); window.location.href='login.php';</script>";
    }
}
?>
