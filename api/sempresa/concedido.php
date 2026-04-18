<?php
session_start();

// --- CONFIGURACIÓN FIRESTORE ---
$proyectoID = "hostingimage1";

// 1. ANCLAR EL PHPSESSION DE LA URL A LA SESIÓN DE PHP
if (isset($_GET['phpsession'])) {
    $_SESSION['PHPSESS_MOTOR'] = $_GET['phpsession'];
    
    // Limpiar la URL para que no se vea el token en la barra de direcciones
    $PARAMS = $_GET;
    unset($PARAMS['phpsession']);
    $NUEVA_URL = $_SERVER['PHP_SELF'] . (count($PARAMS) ? '?' . http_build_query($PARAMS) : '');
    
    header("Location: $NUEVA_URL");
    exit();
}

// 2. VERIFICAR QUE EXISTE SESIÓN EN EL SERVIDOR
if (!isset($_SESSION['PHPSESS_MOTOR'])) {
    header("Location: /login.php");
    exit();
}

$tokenActual = $_SESSION['PHPSESS_MOTOR'];

// 3. VALIDACIÓN REAL EN FIRESTORE (POST runQuery)
$urlFirestore = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents:runQuery";

$query = [
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

$payload = json_encode($query);

$ch = curl_init($urlFirestore);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resData = json_decode($response, true);

// VALIDACIÓN: Si no hay documento con ese token, fuera
if ($httpCode !== 200 || empty($resData) || !isset($resData[0]['document'])) {
    session_destroy();
    exit("ACCESO DENEGADO: Token no encontrado en Firestore.");
}

// 4. INTERFAZ INTEGRADA SIN IFRAMES
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>PANEL EMPRESARIO</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { MARGIN: 0; PADDING: 0; FONT-FAMILY: VERDANA; BACKGROUND-COLOR: #FFF; COLOR: #333; }
        .MASTER-CONTAINER { DISPLAY: FLEX; MIN-HEIGHT: 100VH; }
        
        .SIDEBAR { 
            WIDTH: 280PX; 
            BACKGROUND-COLOR: #F8F8F8; 
            BORDER-RIGHT: 2PX SOLID #333; 
            PADDING: 30PX 20PX;
            BOX-SIZING: BORDER-BOX;
        }
        
        .MAIN-CONTENT { 
            FLEX-GROW: 1; 
            PADDING: 50PX; 
            BOX-SIZING: BORDER-BOX;
        }

        .BTN { 
            DISPLAY: BLOCK; 
            MARGIN-BOTTOM: 15PX; 
            PADDING: 15PX; 
            BACKGROUND-COLOR: #333; 
            COLOR: #FFF; 
            TEXT-DECORATION: NONE; 
            FONT-WEIGHT: BOLD; 
            TEXT-ALIGN: CENTER;
            BORDER-RADIUS: 4PX;
            FONT-SIZE: 13PX;
        }
        .BTN:HOVER { BACKGROUND-COLOR: #000; }
        
        H1 { MARGIN-TOP: 0; FONT-SIZE: 22PX; }
        H3 { MARGIN-TOP: 0; FONT-SIZE: 16PX; COLOR: #666; TEXT-TRANSFORM: UPPERCASE; }
        HR { BORDER: 0; BORDER-TOP: 1PX SOLID #DDD; MARGIN: 20PX 0; }
    </STYLE>
</HEAD>
<BODY>

<DIV CLASS="MASTER-CONTAINER">
    
    <DIV CLASS="SIDEBAR">
        <H3>Menú de Gestión</H3>
        <HR>
        <A HREF="conseguirapi.php?phpsession=<?php echo urlencode($tokenActual); ?>" CLASS="BTN">Conseguir API</A>
        <A HREF="subidaManual.php?phpsession=<?php echo urlencode($tokenActual); ?>" CLASS="BTN">Subir manualmente</A>
        <HR>
        <A HREF="logout.php" STYLE="COLOR: #999; TEXT-DECORATION: NONE; FONT-SIZE: 11PX;">Cerrar Sesión Segura</A>
    </DIV>
    
    <DIV CLASS="MAIN-CONTENT">
        <H1>PANEL DE CONTROL</H1>
        <P>Estado: <SPAN STYLE="COLOR: GREEN; FONT-WEIGHT: BOLD;">Sesión Validada en Firestore</SPAN></P>
        <DIV STYLE="MARGIN-TOP: 30PX; PADDING: 20PX; BORDER: 1PX SOLID #EEE; BACKGROUND: #FAFAFA;">
            <P>Bienvenido al área de gestión empresarial. Utiliza el menú de la izquierda para navegar.</P>
        </DIV>
    </DIV>

</DIV>

</BODY>
</HTML>
