<?php
session_start();

// 1. CONFIGURACIÓN Y DATOS DE SESIÓN LOCAL
$proyectoID = "hostingimage1";
$usuario    = $_SESSION['usuario'] ?? ''; // Asumo que guardas el nombre del doc aquí
$tokenLocal = $_SESSION['phpsession'] ?? '';

// Si ni siquiera hay sesión local, cerramos
if (empty($usuario) || empty($tokenLocal)) {
    exit("ACCESO DENEGADO: SESIÓN LOCAL INEXISTENTE");
}

// 2. VALIDACIÓN CONTRA FIRESTORE (API REST)
// Ruta: usuarios/{usuario}
$url = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/usuarios/{$usuario}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// 3. LÓGICA DE VALIDACIÓN DEL TOKEN EN LA DB
// Verificamos si el documento existe (HTTP 200) y si el campo 'phpsession' coincide
$tokenEnDB = $data['fields']['phpsession']['stringValue'] ?? null;

if ($httpCode !== 200 || $tokenEnDB !== $tokenLocal) {
    // Si el usuario no existe o el token es distinto, fuera
    exit("ACCESO DENEGADO: TOKEN INVÁLIDO EN BASE DE DATOS");
}

// SI LLEGA AQUÍ, LA SESIÓN ES VÁLIDA
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { FONT-FAMILY: VERDANA; PADDING: 50PX; BACKGROUND-COLOR: #FFF; COLOR: #333; TEXT-ALIGN: CENTER; }
        .BOX { BORDER: 2PX SOLID #333; PADDING: 30PX; DISPLAY: INLINE-BLOCK; }
        .BTN { 
            DISPLAY: BLOCK; 
            MARGIN: 15PX AUTO; 
            PADDING: 10PX 20PX; 
            BACKGROUND-COLOR: #000; 
            COLOR: #FFF; 
            TEXT-DECORATION: NONE; 
            FONT-WEIGHT: BOLD;
            WIDTH: 200PX;
        }
        .BTN:HOVER { BACKGROUND-COLOR: #444; }
    </STYLE>
</HEAD>
<BODY>
    <DIV CLASS="BOX">
        <H1>PANEL DE CONTROL</H1>
        <P>Sesión verificada correctamente en Firestore.</P>
        <HR>
        
        <A HREF="conseguirapi.php" CLASS="BTN">Conseguir API</A>
        <A HREF="subidaManual.php" CLASS="BTN">Subir manualmente</A>
        
    </DIV>
</BODY>
</HTML><?PHP
SESSION_START();

// VALIDACIÓN DE SEGURIDAD ABSOLUTA
$TOKEN_URL = $_GET['ALEATORIO'] ?? '';
$TOKEN_SESION = $_SESSION['TOKEN_IFRAME'] ?? '';

IF (EMPTY($TOKEN_URL) || $TOKEN_URL !== $TOKEN_SESION || !ISSET($_SESSION['PHPSESS_MOTOR'])) {
    // EL HTML NO SE ENVÍA SI NO HAY SESIÓN VÁLIDA
    EXIT(); 
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <STYLE TYPE="TEXT/CSS">
        BODY { FONT-FAMILY: VERDANA; BACKGROUND-COLOR: #222; COLOR: #FFF; MARGIN: 0; PADDING: 20PX; }
        A { COLOR: #00AAFF; TEXT-DECORATION: NONE; DISPLAY: BLOCK; MARGIN: 15PX 0; FONT-SIZE: 12PX; }
        A:HOVER { TEXT-DECORATION: UNDERLINE; }
    </STYLE>
</HEAD>
<BODY>
    <B>EMPRESARIO</B>
    <HR>
    <A HREF="contenidoPrincipal.php?ALEATORIO=<?PHP ECHO $TOKEN_URL; ?>" TARGET="CONTENIDO">DASHBOARD</A>
    <A HREF="/configuracion.php?ALEATORIO=<?PHP ECHO $TOKEN_URL; ?>" TARGET="CONTENIDO">AJUSTES</A>
    <BR>
    <A HREF="/logout.php" TARGET="_TOP" STYLE="COLOR:RED;">CERRAR SISTEMA</A>
</BODY>
</HTML>
