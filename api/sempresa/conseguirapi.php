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

// 3. LÓGICA DE CREACIÓN (PROCESAR FORMULARIO EN EL MISMO PHP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombreEmpresa'])) {
    $nombreEmp = $_POST['nombreEmpresa'];
    $nuevaApiClave = bin2hex(random_bytes(16)); // Genera clave aleatoria

    $urlCreate = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($idUsuarioDB) . "/claves";
    
    $newData = [
        'fields' => [
            'api' => ['stringValue' => $nuevaApiClave],
            'nombreEmpresa' => ['stringValue' => $nombreEmp]
        ]
    ];

    $ch = curl_init($urlCreate);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);

    // Redirigir para limpiar el POST
    header("Location: " . $_SERVER['PHP_SELF'] . "?phpsession=" . urlencode($tokenActual));
    exit();
}

// 4. OBTENER APIS EXISTENTES
$urlGetApis = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($idUsuarioDB) . "/claves";
$ch = curl_init($urlGetApis);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$apisData = json_decode(curl_exec($ch), true);
curl_close($ch);
$listaApis = $apisData['documents'] ?? [];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>CONSEGUIR API</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { MARGIN: 0; PADDING: 0; FONT-FAMILY: VERDANA; BACKGROUND-COLOR: #FFF; }
        .MASTER-CONTAINER { DISPLAY: FLEX; MIN-HEIGHT: 100VH; }
        
        /* SIDEBAR CONSTANTE */
        .SIDEBAR { WIDTH: 280PX; BACKGROUND-COLOR: #F8F8F8; BORDER-RIGHT: 2PX SOLID #333; PADDING: 30PX 20PX; BOX-SIZING: BORDER-BOX; }
        .BTN-NAV { DISPLAY: BLOCK; MARGIN-BOTTOM: 15PX; PADDING: 15PX; BACKGROUND-COLOR: #333; COLOR: #FFF; TEXT-DECORATION: NONE; FONT-WEIGHT: BOLD; TEXT-ALIGN: CENTER; BORDER-RADIUS: 4PX; FONT-SIZE: 13PX; }
        .BTN-NAV:HOVER { BACKGROUND-COLOR: #000; }

        /* CONTENIDO PRINCIPAL */
        .MAIN-CONTENT { FLEX-GROW: 1; PADDING: 50PX; BOX-SIZING: BORDER-BOX; }
        H1 { FONT-SIZE: 24PX; MARGIN-BOTTOM: 10PX; }
        H2 { FONT-SIZE: 18PX; MARGIN-TOP: 35PX; BORDER-BOTTOM: 2PX SOLID #333; PADDING-BOTTOM: 5PX; }
        .UTILIDADES { BACKGROUND: #F9F9F9; PADDING: 15PX; BORDER-LEFT: 4PX SOLID #333; MARGIN: 20PX 0; }
        
        .API-ITEM { BACKGROUND: #EEE; PADDING: 15PX; MARGIN-TOP: 10PX; BORDER-RADIUS: 4PX; DISPLAY: FLEX; JUSTIFY-CONTENT: SPACE-BETWEEN; }
        .API-SECRET { FONT-FAMILY: MONOSPACE; COLOR: #666; }
        
        .FORM-CREAR { MARGIN-TOP: 20PX; PADDING: 20PX; BORDER: 1PX SOLID #CCC; BACKGROUND: #FAFAFA; }
        .INPUT-TXT { PADDING: 10PX; WIDTH: 250PX; FONT-FAMILY: VERDANA; }
        .BTN-NUEVA { PADDING: 10PX 20PX; BACKGROUND: #333; COLOR: #FFF; BORDER: NONE; CURSOR: POINTER; FONT-WEIGHT: BOLD; }
        
        .DUDAS { MARGIN-TOP: 40PX; FONT-SIZE: 10PX; TEXT-ALIGN: CENTER; }
        .DUDAS A { COLOR: #999; TEXT-DECORATION: NONE; }
        .VER-BTN { CURSOR: POINTER; COLOR: BLUE; TEXT-DECORATION: UNDERLINE; FONT-SIZE: 11PX; MARGIN-LEFT: 10PX; }
    </STYLE>
    <SCRIPT TYPE="TEXT/JAVASCRIPT">
        function verApi(id, realValue) {
            var span = document.getElementById(id);
            if (span.innerHTML === '**********') {
                span.innerHTML = realValue;
            } else {
                span.innerHTML = '**********';
            }
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
                <LI>Puedes subir imágenes desde tu web</LI>
                <LI>Carpeta con nombre de tu empresa</LI>
            </UL>
        </DIV>

        <H2>APIS PERTENECIENTES</H2>
        <?php if (empty($listaApis)): ?>
            <P STYLE="COLOR:RED;">Pulse para crear una api</P>
        <?php else: ?>
            <?php foreach ($listaApis as $i => $doc): 
                $val = $doc['fields']['api']['stringValue'] ?? '';
                $nom = $doc['fields']['nombreEmpresa']['stringValue'] ?? 'Sin Nombre';
                $idSpan = "key_".$i;
            ?>
                <DIV CLASS="API-ITEM">
                    <SPAN><STRONG><?php echo htmlspecialchars($nom); ?>:</STRONG> <SPAN ID="<?php echo $idSpan; ?>" CLASS="API-SECRET">**********</SPAN></SPAN>
                    <SPAN CLASS="VER-BTN" ONCLICK="verApi('<?php echo $idSpan; ?>', '<?php echo $val; ?>')">Ver</SPAN>
                </DIV>
            <?php endforeach; ?>
        <?php endif; ?>

        <H2>CREAR NUEVA</H2>
        <DIV CLASS="FORM-CREAR">
            <FORM METHOD="POST">
                <P>Nombre de la empresa:</P>
                <INPUT TYPE="TEXT" NAME="nombreEmpresa" CLASS="INPUT-TXT" REQUIRED>
                <BUTTON TYPE="SUBMIT" CLASS="BTN-NUEVA">Nueva</BUTTON>
            </FORM>
        </DIV>

        <DIV CLASS="DUDAS">
            <A HREF="#">dudas y ayuda</A>
        </DIV>
    </DIV>
</DIV>

</BODY>
</HTML>
