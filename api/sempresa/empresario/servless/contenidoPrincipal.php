<?PHP
SESSION_START();

// VALIDACIÓN DE SEGURIDAD ABSOLUTA
$TOKEN_URL = $_GET['ALEATORIO'] ?? '';
$TOKEN_SESION = $_SESSION['TOKEN_IFRAME'] ?? '';

IF (EMPTY($TOKEN_URL) || $TOKEN_URL !== $TOKEN_SESION || !ISSET($_SESSION['PHPSESS_MOTOR'])) {
    // PROTECCIÓN: NO SE RENDERIZA NADA SIN SESIÓN
    EXIT();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <STYLE TYPE="TEXT/CSS">
        BODY { FONT-FAMILY: VERDANA; PADDING: 50PX; BACKGROUND-COLOR: #FFF; COLOR: #333; }
        .BOX { BORDER: 2PX SOLID #333; PADDING: 20PX; }
    </STYLE>
</HEAD>
<BODY>
    <DIV CLASS="BOX">
        <H1>CONTENIDO PRINCIPAL</H1>
        <P>Sesión validada mediante PHPSESSID y Token Volátil.</P>
        <HR>
        <P>Bienvenido al área segura de gestión empresarial.</P>
    </DIV>
</BODY>
</HTML>
