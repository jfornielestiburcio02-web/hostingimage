<?php
session_start();

// --- CONFIGURACIÓN FIRESTORE ---
$proyectoID = "hostingimage1";

// 1. ANCLAR EL PHPSESSION DE LA URL A LA SESIÓN DE PHP
if (isset($_GET['phpsession'])) {
    $_SESSION['PHPSESS_MOTOR'] = $_GET['phpsession'];
    $PARAMS = $_GET;
    unset($PARAMS['phpsession']);
    $NUEVA_URL = $_SERVER['PHP_SELF'] . (count($PARAMS) ? '?' . http_build_query($PARAMS) : '');
    header("Location: $NUEVA_URL");
    exit();
}

if (!isset($_SESSION['PHPSESS_MOTOR'])) {
    header("Location: /login.php");
    exit();
}

$tokenActual = $_SESSION['PHPSESS_MOTOR'];

// 2. VALIDACIÓN DEL USUARIO Y OBTENCIÓN DE ID
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

$ch = curl_init($urlFirestore);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$resData = json_decode($response, true);
curl_close($ch);

if (empty($resData) || !isset($resData[0]['document'])) {
    session_destroy();
    exit("ACCESO DENEGADO");
}

$docPath = $resData[0]['document']['name'];
$pathPartes = explode('/', $docPath);
$usuarioID = end($pathPartes);

// 3. OBTENER DATOS DE LA API (Para el upload manual)
$urlApi = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($usuarioID);
$ch = curl_init($urlApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$apiData = json_decode(curl_exec($ch), true);
curl_close($ch);

$apiKey = $apiData['fields']['api']['stringValue'] ?? '';
$nombreEmpresa = $apiData['fields']['nombreEmpresa']['stringValue'] ?? 'default';

// 4. LÓGICA DE ELIMINACIÓN
if (isset($_GET['eliminar'])) {
    $docID = $_GET['eliminar'];
    $urlDel = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/lista/" . $docID;
    
    $ch = curl_init($urlDel);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
    
    header("Location: subidaManual.php");
    exit();
}

// 5. OBTENER LISTADO DE IMÁGENES
$urlLista = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/lista?orderBy=fecha desc";
$ch = curl_init($urlLista);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resLista = json_decode(curl_exec($ch), true);
curl_close($ch);
$imagenes = $resLista['documents'] ?? [];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>SUBIDA MANUAL - HOSTING IMAGE</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { MARGIN: 0; PADDING: 0; FONT-FAMILY: VERDANA; BACKGROUND-COLOR: #FFF; COLOR: #333; }
        .MASTER-CONTAINER { DISPLAY: FLEX; MIN-HEIGHT: 100VH; }
        .SIDEBAR { WIDTH: 280PX; BACKGROUND-COLOR: #F8F8F8; BORDER-RIGHT: 2PX SOLID #333; PADDING: 30PX 20PX; BOX-SIZING: BORDER-BOX; }
        .MAIN-CONTENT { FLEX-GROW: 1; PADDING: 50PX; BOX-SIZING: BORDER-BOX; }
        .BTN { DISPLAY: BLOCK; MARGIN-BOTTOM: 15PX; PADDING: 15PX; BACKGROUND-COLOR: #333; COLOR: #FFF; TEXT-DECORATION: NONE; FONT-WEIGHT: BOLD; TEXT-ALIGN: CENTER; BORDER-RADIUS: 4PX; FONT-SIZE: 13PX; }
        .BTN:HOVER { BACKGROUND-COLOR: #000; }
        
        .ZONA-SUBIDA { BACKGROUND: #F9F9F9; PADDING: 20PX; BORDER: 2PX DASHED #CCC; TEXT-ALIGN: CENTER; MARGIN-BOTTOM: 40PX; }
        .GRID-IMAGENES { DISPLAY: GRID; GRID-TEMPLATE-COLUMNS: REPEAT(AUTO-FILL, MINMAX(200PX, 1FR)); GRID-GAP: 20PX; }
        .CARD-IMG { BORDER: 1PX SOLID #DDD; PADDING: 10PX; TEXT-ALIGN: CENTER; BACKGROUND: #FFF; }
        .CARD-IMG IMG { WIDTH: 100%; HEIGHT: 150PX; OBJECT-FIT: COVER; MARGIN-BOTTOM: 10PX; }
        .URL-TXT { FONT-SIZE: 10PX; WORD-BREAK: BREAK-ALL; COLOR: #666; BACKGROUND: #EEE; PADDING: 5PX; DISPLAY: BLOCK; MARGIN-BOTTOM: 10PX; }
        .BTN-DEL { COLOR: RED; TEXT-DECORATION: NONE; FONT-SIZE: 11PX; FONT-WEIGHT: BOLD; }
        
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
        <H1>Gestión de Imágenes</H1>

        <?php if(empty($apiKey)): ?>
            <DIV STYLE="COLOR: RED; FONT-WEIGHT: BOLD; PADDING: 20PX; BORDER: 1PX SOLID RED;">
                Debes generar una API Key primero en el menú "Conseguir API".
            </DIV>
        <?php else: ?>
            
            <DIV CLASS="ZONA-SUBIDA">
                <H2 STYLE="FONT-SIZE: 16PX;">Subir Nueva Imagen</H2>
                <FORM ID="uploadForm">
                    <INPUT TYPE="FILE" NAME="image" ID="fileInput" ACCEPT="image/*" REQUIRED>
                    <BUTTON TYPE="SUBMIT" CLASS="BTN" STYLE="DISPLAY: INLINE-BLOCK; MARGIN: 0; MARGIN-LEFT: 10PX;">SUBIR AHORA</BUTTON>
                </FORM>
                <DIV ID="uploadStatus" STYLE="MARGIN-TOP: 10PX; FONT-SIZE: 12PX;"></DIV>
            </DIV>

            <H2 STYLE="FONT-SIZE: 18PX; BORDER-BOTTOM: 2PX SOLID #333; PADDING-BOTTOM: 10PX;">Mis Imágenes Guardadas</H2>
            
            <DIV CLASS="GRID-IMAGENES">
                <?php if(empty($imagenes)): ?>
                    <P>No hay imágenes subidas todavía.</P>
                <?php else: ?>
                    <?php foreach($imagenes as $img): 
                        $url = $img['fields']['url']['stringValue'] ?? '';
                        $nombre = $img['fields']['nombre']['stringValue'] ?? 'Sin nombre';
                        $pathFull = $img['name'];
                        $idDoc = end(explode('/', $pathFull));
                    ?>
                        <DIV CLASS="CARD-IMG">
                            <IMG SRC="<?php echo htmlspecialchars($url); ?>" ALT="Vista previa">
                            <STRONG STYLE="FONT-SIZE: 11PX;"><?php echo htmlspecialchars($nombre); ?></STRONG>
                            <CODE CLASS="URL-TXT"><?php echo htmlspecialchars($url); ?></CODE>
                            <A HREF="?eliminar=<?php echo $idDoc; ?>" CLASS="BTN-DEL" ONCLICK="return confirm('¿Eliminar esta imagen?')">ELIMINAR</A>
                        </DIV>
                    <?php endforeach; ?>
                <?php endif; ?>
            </DIV>

        <?php endif; ?>
    </DIV>
</DIV>

<SCRIPT>
document.getElementById('uploadForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const status = document.getElementById('uploadStatus');
    const file = document.getElementById('fileInput').files[0];
    
    if(!file) return;

    status.innerText = "Procesando subida...";
    status.style.color = "blue";

    const formData = new FormData();
    formData.append('image', file);
    formData.append('apiKey', '<?php echo $apiKey; ?>');
    formData.append('usuarioID', '<?php echo $usuarioID; ?>');
    formData.append('path', '/imagenes/<?php echo $nombreEmpresa; ?>/');

    try {
        const response = await fetch('https://hostingimage-bice.vercel.app/upload.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();

        if(res.success) {
            status.innerText = "¡Subido con éxito! Recargando...";
            status.style.color = "green";
            setTimeout(() => location.reload(), 1500);
        } else {
            status.innerText = "Error: " + res.error;
            status.style.color = "red";
        }
    } catch(err) {
        status.innerText = "Error de conexión.";
        status.style.color = "red";
    }
});
</SCRIPT>

</BODY>
</HTML>
