<?php
session_start();

// --- CONFIGURACIÓN FIRESTORE ---
$proyectoID = "hostingimage1";

// 1. VALIDACIÓN DE TOKEN (URL O SESIÓN)
$tokenActual = $_GET['phpsession'] ?? $_SESSION['PHPSESS_MOTOR'] ?? '';

if (empty($tokenActual)) {
    header("Location: /login.php");
    exit();
}

// 2. VALIDACIÓN DEL USUARIO Y OBTENCIÓN DE SU ID
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
$resUser = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($resUser) || !isset($resUser[0]['document'])) {
    exit("ACCESO DENEGADO");
}

$pathPartes = explode('/', $resUser[0]['document']['name']);
$idUsuarioDB = end($pathPartes);

// 3. LÓGICA DE CREACIÓN/ACTUALIZACIÓN DE API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombreEmpresa'])) {
    $nombreEmp = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['nombreEmpresa']); 
    $nuevaApiClave = "HI-" . strtoupper(bin2hex(random_bytes(8))); 

    // URL para crear/reemplazar el documento específico del usuario: apis/{idUsuarioDB}
    $urlUpdate = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($idUsuarioDB);
    
    $newData = [
        'fields' => [
            'api' => ['stringValue' => $nuevaApiClave],
            'nombreEmpresa' => ['stringValue' => $nombreEmp]
        ]
    ];

    $ch = curl_init($urlUpdate);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH"); // PATCH crea o actualiza
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);

    header("Location: " . $_SERVER['PHP_SELF'] . "?phpsession=" . urlencode($tokenActual));
    exit();
}

// 4. OBTENER LA API DEL USUARIO (Documento único)
$urlGetApi = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($idUsuarioDB);
$ch = curl_init($urlGetApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$apiDoc = json_decode(curl_exec($ch), true);
curl_close($ch);

// Adaptamos la lista para que el resto del HTML funcione (aunque ahora sea solo uno)
$existeApi = isset($apiDoc['fields']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>CONSEGUIR API - HOSTING IMAGE</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { MARGIN: 0; PADDING: 0; FONT-FAMILY: VERDANA; BACKGROUND-COLOR: #FFF; }
        .MASTER-CONTAINER { DISPLAY: FLEX; MIN-HEIGHT: 100VH; }
        .SIDEBAR { WIDTH: 280PX; BACKGROUND-COLOR: #F8F8F8; BORDER-RIGHT: 2PX SOLID #333; PADDING: 30PX 20PX; BOX-SIZING: BORDER-BOX; }
        .BTN-NAV { DISPLAY: BLOCK; MARGIN-BOTTOM: 15PX; PADDING: 15PX; BACKGROUND-COLOR: #333; COLOR: #FFF; TEXT-DECORATION: NONE; FONT-WEIGHT: BOLD; TEXT-ALIGN: CENTER; BORDER-RADIUS: 4PX; FONT-SIZE: 13PX; }
        .MAIN-CONTENT { FLEX-GROW: 1; PADDING: 50PX; BOX-SIZING: BORDER-BOX; }
        .UTILIDADES { BACKGROUND: #F4F4F4; PADDING: 15PX; BORDER-LEFT: 4PX SOLID #333; MARGIN: 20PX 0; }
        .CODE-BLOCK { BACKGROUND: #222; COLOR: #ADFF2F; PADDING: 15PX; FONT-FAMILY: MONOSPACE; FONT-SIZE: 12PX; MARGIN-TOP: 10PX; BORDER-RADIUS: 5PX; OVERFLOW-X: AUTO; }
        .API-ITEM { BORDER: 1PX SOLID #DDD; PADDING: 15PX; MARGIN-TOP: 15PX; BORDER-RADIUS: 4PX; BACKGROUND-COLOR: #FAFAFA; }
        .INPUT-TXT { PADDING: 10PX; WIDTH: 250PX; FONT-FAMILY: VERDANA; }
        .BTN-NUEVA { PADDING: 10PX 20PX; BACKGROUND: #333; COLOR: #FFF; BORDER: NONE; CURSOR: POINTER; FONT-WEIGHT: BOLD; }
        .VER-BTN { CURSOR: POINTER; COLOR: #007BFF; TEXT-DECORATION: UNDERLINE; FONT-SIZE: 11PX; }
    </STYLE>
    <SCRIPT TYPE="TEXT/JAVASCRIPT">
        function toggleCode(id) {
            var el = document.getElementById(id);
            el.style.display = (el.style.display === 'none') ? 'block' : 'none';
        }
    </SCRIPT>
</HEAD>
<BODY>

<DIV CLASS="MASTER-CONTAINER">
    <DIV CLASS="SIDEBAR">
        <H3 STYLE="FONT-SIZE:14PX; COLOR:#666;">MENÚ</H3>
        <HR>
        <A HREF="conseguirapi.php?phpsession=<?php echo urlencode($tokenActual); ?>" CLASS="BTN-NAV">Conseguir API</A>
        <A HREF="subidaManual.php?phpsession=<?php echo urlencode($tokenActual); ?>" CLASS="BTN-NAV">Subir manualmente</A>
    </DIV>

    <DIV CLASS="MAIN-CONTENT">
        <H1>Consigue tu API</H1>
        
        <DIV CLASS="UTILIDADES">
            <STRONG>Utilidades:</STRONG>
            <UL>
                <LI>Solo puedes tener **una API activa** por cuenta.</LI>
                <LI>Al crear una nueva, la anterior dejará de funcionar.</LI>
            </UL>
        </DIV>

        <H2 STYLE="FONT-SIZE: 18PX; BORDER-BOTTOM: 2PX SOLID #333;">TU CONFIGURACIÓN ACTUAL</H2>
        
        <?php if (!$existeApi): ?>
            <P STYLE="COLOR:RED; FONT-WEIGHT:BOLD; MARGIN-TOP:20PX;">No tienes ninguna API generada todavía.</P>
        <?php else: 
            $val = $apiDoc['fields']['api']['stringValue'] ?? '';
            $nom = $apiDoc['fields']['nombreEmpresa']['stringValue'] ?? 'empresa';
        ?>
            <DIV CLASS="API-ITEM">
                <STRONG>Empresa:</STRONG> <?php echo htmlspecialchars($nom); ?><BR>
                <STRONG>API Key:</STRONG> <?php echo htmlspecialchars($val); ?> 
                <BR><BR>
                <SPAN CLASS="VER-BTN" ONCLICK="toggleCode('bloqueUnico')">Mostrar código para tu Web</SPAN>
                
                <DIV ID="bloqueUnico" CLASS="CODE-BLOCK" STYLE="DISPLAY:NONE;">
                    const hostingImageConfig = {<BR>
                    &nbsp;&nbsp;apiKey: "<?php echo htmlspecialchars($val); ?>",<BR>
                    &nbsp;&nbsp;usuarioID: "<?php echo htmlspecialchars($idUsuarioDB); ?>",<BR>
                    &nbsp;&nbsp;storagePath: "/imagenes/<?php echo htmlspecialchars($nom); ?>/"<BR>
                    };
                </DIV>
            </DIV>
        <?php endif; ?>

        <H2 STYLE="FONT-SIZE: 18PX; BORDER-BOTTOM: 2PX SOLID #333; MARGIN-TOP:40PX;">GENERAR / REEMPLAZAR API</H2>
        <DIV STYLE="MARGIN-TOP: 20PX; PADDING: 20PX; BORDER: 1PX SOLID #CCC;">
            <FORM METHOD="POST">
                <P>Nombre de la empresa:</P>
                <INPUT TYPE="TEXT" NAME="nombreEmpresa" CLASS="INPUT-TXT" REQUIRED PLACEHOLDER="mi_empresa_sl">
                <BUTTON TYPE="SUBMIT" CLASS="BTN-NUEVA"><?php echo $existeApi ? 'Regenerar API' : 'Crear API'; ?></BUTTON>
            </FORM>
        </DIV>
    </DIV>
</DIV>

</BODY>
</HTML>
