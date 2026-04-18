<?php
session_start();

// 1. CONFIGURACIÓN DE DATOS
$proyectoID = "hostingimage1";
$usuario    = $_SESSION['usuario'] ?? ''; // El nombre del documento en la colección 'usuarios'
$tokenURL   = $_GET['ALEATORIO'] ?? '';   // Capturamos ?ALEATORIO= de la URL

// 2. PROTECCIÓN INICIAL: Si no hay usuario en sesión o el parámetro está vacío, fuera
if (empty($usuario) || empty($tokenURL)) {
    exit("ACCESO DENEGADO: SESIÓN O PARÁMETRO AUSENTE");
}

// 3. CONSULTA REST A FIRESTORE (PHP Puro mediante cURL)
// Buscamos en: usuarios / {usuario}
$url = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/usuarios/" . urlencode($usuario);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// 4. VALIDACIÓN DEL STRING 'phpsession'
// Extraemos el valor stringValue del JSON devuelto por Firebase
$tokenEnDB = $data['fields']['phpsession']['stringValue'] ?? null;

if ($httpCode !== 200 || $tokenEnDB === null || $tokenEnDB !== $tokenURL) {
    // Si el usuario no existe, o el token de la URL no coincide con el de la DB: FUERA
    exit("ACCESO DENEGADO: TOKEN INVÁLIDO EN BASE DE DATOS");
}

// SI LA VALIDACIÓN ES CORRECTA, SE MUESTRA EL CONTENIDO
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { FONT-FAMILY: VERDANA; PADDING: 50PX; BACKGROUND-COLOR: #FFF; COLOR: #333; TEXT-ALIGN: CENTER; }
        .BOX { BORDER: 2PX SOLID #333; PADDING: 30PX; DISPLAY: INLINE-BLOCK; BORDER-RADIUS: 5PX; }
        .BTN { 
            DISPLAY: BLOCK; 
            MARGIN: 15PX AUTO; 
            PADDING: 12PX 25PX; 
            BACKGROUND-COLOR: #333; 
            COLOR: #FFF; 
            TEXT-DECORATION: NONE; 
            FONT-WEIGHT: BOLD;
            WIDTH: 220PX;
            BORDER-RADIUS: 3PX;
        }
        .BTN:HOVER { BACKGROUND-COLOR: #000; }
        H1 { MARGIN-TOP: 0; COLOR: #000; }
        HR { BORDER: 0; BORDER-TOP: 1PX SOLID #CCC; MARGIN: 20PX 0; }
    </STYLE>
</HEAD>
<BODY>

    <DIV CLASS="BOX">
        <H1>ÁREA SEGURA</H1>
        <P>Validación Firestore: <strong>EXITOSA</strong></P>
        <HR>
        
        <A HREF="conseguirapi.php" CLASS="BTN">Conseguir API</A>
        <A HREF="subidaManual.php" CLASS="BTN">Subir manualmente</A>
        
    </DIV>

</BODY>
</HTML>
