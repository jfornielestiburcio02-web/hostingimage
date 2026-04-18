<?php
session_start();

// --- CONFIGURACIÓN FIRESTORE ---
$proyectoID = "hostingimage1";

// 1. GESTIÓN DE SESIÓN (Tal cual pediste)
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

// 2. VALIDACIÓN DEL USUARIO Y OBTENCIÓN DE DATOS
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

// Sacamos el ID del documento de usuario (ej: e816...)
$docPath = $resData[0]['document']['name'];
$pathPartes = explode('/', $docPath);
$usuarioID = end($pathPartes);

// 3. OBTENER API KEY Y NOMBRE DE EMPRESA DESDE LA COLECCIÓN 'apis'
$urlApi = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/apis/" . urlencode($usuarioID);
$ch = curl_init($urlApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$apiDoc = json_decode(curl_exec($ch), true);
curl_close($ch);

$apiKey = $apiDoc['fields']['api']['stringValue'] ?? '';
$nombreEmpresa = $apiDoc['fields']['nombreEmpresa']['stringValue'] ?? 'empresa';

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

// 5. LISTAR IMÁGENES GUARDADAS
$urlLista = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents/imagenes/" . urlencode($usuarioID) . "/lista";
$ch = curl_init($urlLista);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resLista = json_decode(curl_exec($ch), true);
curl_close($ch);
$imagenes = $resLista['documents'] ?? [];
?>

<!DOCTYPE HTML>
<HTML>
<HEAD>
    <TITLE>SUBIDA MANUAL</TITLE>
    <STYLE>
        BODY { MARGIN: 0; FONT-FAMILY: VERDANA; BACKGROUND: #FFF; }
        .MASTER-CONTAINER { DISPLAY: FLEX; MIN-HEIGHT: 100VH; }
        .SIDEBAR { WIDTH: 280PX; BACKGROUND: #F8F8F8; BORDER-RIGHT: 2PX SOLID #333; PADDING: 30PX 20PX; }
        .MAIN-CONTENT { FLEX-GROW: 1; PADDING: 40PX; }
        .BTN { DISPLAY: BLOCK; MARGIN-BOTTOM: 10PX; PADDING: 12PX; BACKGROUND: #333; COLOR: #FFF; TEXT-DECORATION: NONE; TEXT-ALIGN: CENTER; BORDER-RADIUS: 4PX; FONT-SIZE: 12PX; }
        
        .UPLOAD-BOX { BORDER: 2PX DASHED #DDD; PADDING: 30PX; TEXT-ALIGN: CENTER; MARGIN-BOTTOM: 30PX; BACKGROUND: #FAFAFA; }
        .GRID { DISPLAY: GRID; GRID-TEMPLATE-COLUMNS: REPEAT(AUTO-FILL, MINMAX(180PX, 1FR)); GAP: 20PX; }
        .ITEM { BORDER: 1PX SOLID #EEE; PADDING: 10PX; BACKGROUND: #FFF; BOX-SHADOW: 0 2PX 5PX RGBA(0,0,0,0.05); POSITION: RELATIVE; }
        .ITEM IMG { WIDTH: 100%; HEIGHT: 120PX; OBJECT-FIT: COVER; BORDER-RADIUS: 3PX; }
        .URL-LABEL { FONT-SIZE: 10PX; BACKGROUND: #F0F0F0; PADDING: 5PX; DISPLAY: BLOCK; MARGIN: 10PX 0; WORD-BREAK: BREAK-ALL; CURSOR: POINTER; }
        .DELETE-LINK { COLOR: #CC0000; TEXT-DECORATION: NONE; FONT-SIZE: 10PX; FONT-WEIGHT: BOLD; }
        H3 { COLOR: #666; FONT-SIZE: 14PX; TEXT-TRANSFORM: UPPERCASE; }
    </STYLE>
</HEAD>
<BODY>

<DIV CLASS="MASTER-CONTAINER">
    <DIV CLASS="SIDEBAR">
        <H3>Menú</H3>
        <A HREF="conseguirapi.php?phpsession=<?php echo urlencode($tokenActual); ?>" CLASS="BTN">CONSEGUIR API</A>
        <A HREF="subidaManual.php?phpsession=<?php echo urlencode($tokenActual); ?>" CLASS="BTN">SUBIDA MANUAL</A>
        <HR>
        <A HREF="logout.php" STYLE="FONT-SIZE: 11PX; COLOR: #999;">Cerrar Sesión</A>
    </DIV>

    <DIV CLASS="MAIN-CONTENT">
        <H1>Subir Imagen</H1>
        
        <DIV CLASS="UPLOAD-BOX">
            <FORM ID="frmSubir">
                <INPUT TYPE="FILE" ID="archivo" ACCEPT="image/*" REQUIRED>
                <BUTTON TYPE="SUBMIT" CLASS="BTN" STYLE="DISPLAY: INLINE; MARGIN-LEFT: 10PX;">SUBIR AHORA</BUTTON>
            </FORM>
            <DIV ID="status" STYLE="MARGIN-TOP: 10PX; FONT-SIZE: 12PX; COLOR: #666;"></DIV>
        </DIV>

        <H3>Mis Imágenes en la Nube</H3>
        <DIV CLASS="GRID">
            <?php IF(EMPTY($imagenes)): ?>
                <P>No hay imágenes.</P>
            <?php ELSE: ?>
                <?php FOREACH($imagenes AS $img): 
                    $url = $img['fields']['url']['stringValue'];
                    $nombre = $img['fields']['nombre']['stringValue'];
                    $partes = EXPLODE('/', $img['name']);
                    $idDoc = END($partes);
                ?>
                <DIV CLASS="ITEM">
                    <IMG SRC="<?php ECHO $url; ?>">
                    <SPAN CLASS="URL-LABEL" ONCLICK="navigator.clipboard.writeText('<?php ECHO $url; ?>'); alert('URL Copiada');">
                        <?php ECHO $url; ?>
                    </SPAN>
                    <A HREF="?eliminar=<?php ECHO $idDoc; ?>" CLASS="DELETE-LINK" ONCLICK="RETURN CONFIRM('¿Borrar registro?')">ELIMINAR</A>
                </DIV>
                <?php ENDFOREACH; ?>
            <?php ENDIF; ?>
        </DIV>
    </DIV>
</DIV>

<SCRIPT>
document.getElementById('frmSubir').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const status = document.getElementById('status');
    const file = document.getElementById('archivo').files[0];

    btn.disabled = true;
    status.innerText = "Subiendo...";

    const fd = new FormData();
    fd.append('image', file);
    fd.append('apiKey', '<?php echo $apiKey; ?>');
    fd.append('usuarioID', '<?php echo $usuarioID; ?>');
    fd.append('path', '/imagenes/<?php echo $nombreEmpresa; ?>/');

    try {
        const resp = await fetch('https://hostingimage-bice.vercel.app/upload.php', {
            method: 'POST',
            body: fd
        });
        const data = await resp.json();

        if (data.success) {
            location.reload();
        } else {
            status.innerText = "Error: " + data.error;
            btn.disabled = false;
        }
    } catch (err) {
        status.innerText = "Error de conexión";
        btn.disabled = false;
    }
});
</SCRIPT>

</BODY>
</HTML>
