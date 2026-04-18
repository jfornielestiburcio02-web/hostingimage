<?php
session_start();

// --- CONFIGURACIÓN FIRESTORE ---
$proyectoID = "hostingimage1";

// 1. VALIDACIÓN DE SEGURIDAD (TOKEN OBLIGATORIO)
$tokenActual = $_GET['phpsession'] ?? $_SESSION['PHPSESS_MOTOR'] ?? '';

if (empty($tokenActual)) {
    header("Location: /login.php");
    exit();
}

// 2. VALIDACIÓN REAL EN FIRESTORE Y OBTENCIÓN DEL USUARIO
$urlQuery = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents:runQuery";

$queryUser = [
    'structuredQuery' => [
        'from' => [['collectionId' => 'usuarios']],
        'where' => [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'phpsession'],
                'op' => 'EQUAL',
                'value' => ['stringValue' => $tokenActual]
            ]
        ],
        'limit' => 1
    ]
];

$ch = curl_init($urlQuery);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryUser));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$responseUser = curl_exec($ch);
$resUserData = json_decode($responseUser, true);
curl_close($ch);

// Si el token no existe, fuera
if (empty($resUserData) || !isset($resUserData[0]['document'])) {
    exit("ACCESO DENEGADO: Sesión inválida.");
}

// EXTRAEMOS EL ID DEL DOCUMENTO (Nombre del usuario en la DB)
$pathCompleto = $resUserData[0]['document']['name'];
$partesPath = explode('/', $pathCompleto);
$idUsuarioDB = end($partesPath); 

// 3. OBTENER LAS APIS PERTENECIENTES AL USUARIO
// Buscamos en apis / {usuario} / ...
$urlApis = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($idUsuarioDB) . "/claves";

$ch = curl_init($urlApis);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$responseApis = curl_exec($ch);
$apisData = json_decode($responseApis, true);
curl_close($ch);

$listaApis = $apisData['documents'] ?? [];
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>CONSEGUIR API</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { MARGIN: 0; PADDING: 50PX; FONT-FAMILY: VERDANA; BACKGROUND-COLOR: #FFF; COLOR: #333; }
        .CONTAINER { MAX-WIDTH: 800PX; MARGIN: AUTO; }
        H1 { FONT-SIZE: 24PX; MARGIN-BOTTOM: 10PX; TEXT-TRANSFORM: UPPERCASE; }
        H2 { FONT-SIZE: 18PX; MARGIN-TOP: 30PX; BORDER-BOTTOM: 2PX SOLID #333; PADDING-BOTTOM: 5PX; }
        .UTILIDADES { BACKGROUND: #F9F9F9; PADDING: 15PX; BORDER-LEFT: 4PX SOLID #333; MARGIN: 20PX 0; }
        .UTILIDADES UL { MARGIN: 10PX 0; PADDING-LEFT: 20PX; }
        
        .API-BOX { BACKGROUND: #EEE; PADDING: 15PX; MARGIN-TOP: 10PX; DISPLAY: FLEX; JUSTIFY-CONTENT: BETWEEN; ALIGN-ITEMS: CENTER; BORDER-RADIUS: 4PX; }
        .API-HIDDEN { FONT-FAMILY: MONOSPACE; BACKGROUND: #DDD; PADDING: 5PX; COLOR: #666; }
        
        .FORM-GROUP { MARGIN-TOP: 20PX; PADDING: 20PX; BORDER: 1PX SOLID #CCC; }
        .INPUT-TXT { PADDING: 10PX; WIDTH: 250PX; FONT-FAMILY: VERDANA; }
        .BTN-CREAR { PADDING: 10PX 20PX; BACKGROUND: #333; COLOR: #FFF; BORDER: NONE; CURSOR: POINTER; FONT-WEIGHT: BOLD; }
        .BTN-CREAR:HOVER { BACKGROUND: #000; }
        
        .FOOTER-LINK { MARGIN-TOP: 50PX; FONT-SIZE: 10PX; COLOR: #999; TEXT-ALIGN: CENTER; }
        .FOOTER-LINK A { COLOR: #999; TEXT-DECORATION: NONE; }
        
        .EYE-BTN { CURSOR: POINTER; MARGIN-LEFT: 10PX; FONT-SIZE: 12PX; TEXT-DECORATION: UNDERLINE; COLOR: BLUE; }
    </STYLE>
    <SCRIPT TYPE="TEXT/JAVASCRIPT">
        function toggleApi(id) {
            var el = document.getElementById(id);
            var btn = document.getElementById('btn-'+id);
            if (el.getAttribute('data-hidden') == 'true') {
                el.innerHTML = el.getAttribute('data-real');
                el.setAttribute('data-hidden', 'false');
                btn.innerHTML = '[Ocultar]';
            } else {
                el.innerHTML = '**********';
                el.setAttribute('data-hidden', 'true');
                btn.innerHTML = '[Ver]';
            }
        }
    </SCRIPT>
</HEAD>
<BODY>

<DIV CLASS="CONTAINER">
    <H1>Consigue tu API</H1>
    
    <DIV CLASS="UTILIDADES">
        <STRONG>Utilidades:</STRONG>
        <UL>
            <LI>Puedes subir imágenes desde tu web</LI>
            <LI>Carpeta con nombre de tu empresa</LI>
        </UL>
    </DIV>

    <H2>APIS PERTENECIENTES</H2>
    <?php if (empty($listaApis)): ?>
        <P STYLE="MARGIN-TOP:15PX;">No tienes ninguna API. <STRONG>Pulse para crear una api</STRONG> abajo.</P>
    <?php else: 
        foreach ($listaApis as $index => $doc): 
            $apiValue = $doc['fields']['api']['stringValue'] ?? 'N/A';
            $nombreEmp = $doc['fields']['nombreEmpresa']['stringValue'] ?? 'Sin nombre';
            $idUnico = "api_" . $index;
    ?>
        <DIV CLASS="API-BOX">
            <DIV>
                <STRONG><?php echo htmlspecialchars($nombreEmp); ?>:</STRONG> 
                <SPAN ID="<?php echo $idUnico; ?>" CLASS="API-HIDDEN" data-real="<?php echo htmlspecialchars($apiValue); ?>" data-hidden="true">**********</SPAN>
                <SPAN ID="btn-<?php echo $idUnico; ?>" CLASS="EYE-BTN" ONCLICK="toggleApi('<?php echo $idUnico; ?>')">[Ver]</SPAN>
            </DIV>
        </DIV>
    <?php endforeach; endif; ?>

    <H2>CREAR NUEVA</H2>
    <DIV CLASS="FORM-GROUP">
        <FORM METHOD="POST" ACTION="procesar_nueva_api.php">
            <P>Nombre de la empresa:</P>
            <INPUT TYPE="TEXT" NAME="nombreEmpresa" CLASS="INPUT-TXT" PLACEHOLDER="Ej: Mi Empresa S.L.">
            <INPUT TYPE="HIDDEN" NAME="phpsession" VALUE="<?php echo htmlspecialchars($tokenActual); ?>">
            <BUTTON TYPE="SUBMIT" CLASS="BTN-CREAR">Nueva</BUTTON>
        </FORM>
    </DIV>

    <DIV CLASS="FOOTER-LINK">
        <A HREF="#">dudas y ayuda</A>
    </DIV>
</DIV>

</BODY>
</HTML>
