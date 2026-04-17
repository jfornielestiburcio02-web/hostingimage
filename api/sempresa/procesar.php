<?PHP
INCLUDE $_SERVER['DOCUMENT_ROOT'] . '/api/config.php';

IF ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $USER_LOGIN = $_POST['USUARIO'];
    $PASS_LOGIN = $_POST['CONTRASENA'];

    // URL PARA FIRESTORE REST API
    // ESTRUCTURA: projects/{project_id}/databases/(default)/documents/{coleccion}/{documento}
    $URL = "https://firestore.googleapis.com/v1/projects/{$FIREBASE_CONFIG['projectId']}/databases/(default)/documents/usuarios/{$USER_LOGIN}";

    // 1. CONSULTAR EL DOCUMENTO DEL USUARIO
    $CH = CURL_INIT();
    CURL_SETOPT($CH, CURLOPT_URL, $URL);
    CURL_SETOPT($CH, CURLOPT_RETURNTRANSFER, TRUE);
    CURL_SETOPT($CH, CURLOPT_SSL_VERIFYPEER, FALSE);
    $RESPONSE = CURL_EXEC($CH);
    $HTTP_CODE = CURLINFO_HTTP_CODE($CH);
    CURL_CLOSE($CH);

    $DATA = JSON_DECODE($RESPONSE, TRUE);

    // EN FIRESTORE LOS CAMPOS VIENEN DENTRO DE ["fields"]["nombre_campo"]["stringValue"]
    IF ($HTTP_CODE == 200 && ISSET($DATA['fields']['contrasena']['stringValue'])) {
        
        $DB_PASS = $DATA['fields']['contrasena']['stringValue'];

        IF ($DB_PASS === $PASS_LOGIN) {
            
            // GENERAR PHPSESSIONID
            $LETRAS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $PHPSESS = SUBSTR(STR_SHUFFLE($LETRAS), 0, 12) . RAND(100, 999);
            
            // 2. ACTUALIZAR PHPSESSION EN FIRESTORE (METODO PATCH)
            // DOCUMENTACION: currentDoc requiere el campo a actualizar y el tipo
            $UPDATE_PAYLOAD = JSON_ENCODE([
                'fields' => [
                    'phpsession' => ['stringValue' => $PHPSESS],
                    'contrasena' => ['stringValue' => $DB_PASS] // Mantenemos la contraseña
                ]
            ]);

            $CH_UP = CURL_INIT($URL . "?updateMask.fieldPaths=phpsession");
            CURL_SETOPT($CH_UP, CURLOPT_CUSTOMREQUEST, "PATCH");
            CURL_SETOPT($CH_UP, CURLOPT_POSTFIELDS, $UPDATE_PAYLOAD);
            CURL_SETOPT($CH_UP, CURLOPT_RETURNTRANSFER, TRUE);
            CURL_SETOPT($CH_UP, CURLOPT_SSL_VERIFYPEER, FALSE);
            CURL_EXEC($CH_UP);
            CURL_CLOSE($CH_UP);

            // VALORES ALEATORIOS PARA REDIRECCIÓN
            $ALEATORIO_TXT = SUBSTR(STR_SHUFFLE($LETRAS), 0, 10);
            $RND_NUM = RAND(111111, 999999);

            // REDIRECCIÓN FINAL
            $URL_FINAL = "/concedido.php?ALEATORIO=" . $ALEATORIO_TXT . 
                         "&rndval=" . $RND_NUM . 
                         "&phpsession=" . $PHPSESS . 
                         "&PAGINA_ANTERIOR_CON_BOTONERA=-1&click|top|action";
            
            HEADER("LOCATION: $URL_FINAL");
            EXIT();

        } ELSE {
            ECHO "<SCRIPT>ALERT('ERROR: CONTRASEÑA INCORRECTA'); WINDOW.LOCATION.HREF='login.php';</SCRIPT>";
        }
    } ELSE {
        ECHO "<SCRIPT>ALERT('ERROR: EL USUARIO NO EXISTE'); WINDOW.LOCATION.HREF='login.php';</SCRIPT>";
    }
}
?><?php
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
        echo "<script>alert('Se produció un error al procesar con su solicitud.'); window.location.href='/sempresa/';</script>";
    }
}
?>
