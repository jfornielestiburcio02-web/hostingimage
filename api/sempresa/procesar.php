<?PHP
// CARGAR CONFIGURACIÓN DESDE LA RUTA PRIVADA
INCLUDE $_SERVER['DOCUMENT_ROOT'] . '/api/config.php';

IF ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // RECOGIDA DE DATOS DEL FORMULARIO
    $USER_LOGIN = $_POST['USUARIO'];
    $PASS_LOGIN = $_POST['CONTRASENA'];

    // URL PARA FIRESTORE REST API (GOOGLE CLOUD)
    $URL_BASE = "https://firestore.googleapis.com/v1/projects/{$FIREBASE_CONFIG['projectId']}/databases/(default)/documents/usuarios/{$USER_LOGIN}";

    // --- PASO 1: CONSULTAR EL DOCUMENTO DEL USUARIO ---
    $CH = CURL_INIT();
    CURL_SETOPT($CH, CURLOPT_URL, $URL_BASE);
    CURL_SETOPT($CH, CURLOPT_RETURNTRANSFER, TRUE);
    CURL_SETOPT($CH, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $RESPONSE = CURL_EXEC($CH);
    // CORRECCIÓN: USO CORRECTO DE LA CONSTANTE DENTRO DE CURL_GETINFO
    $HTTP_CODE = CURL_GETINFO($CH, CURLINFO_HTTP_CODE); 
    CURL_CLOSE($CH);

    $DATA = JSON_DECODE($RESPONSE, TRUE);

    // --- PASO 2: VALIDACIÓN DE CREDENCIALES ---
    // FIRESTORE ENCAPSULA LOS DATOS EN [fields][nombre][stringValue]
    IF ($HTTP_CODE == 200 && ISSET($DATA['fields']['contrasena']['stringValue'])) {
        
        $DB_PASS = $DATA['fields']['contrasena']['stringValue'];

        IF ($DB_PASS === $PASS_LOGIN) {
            
            // GENERAR NUEVO PHPSESSIONID (MAYÚSCULAS Y NÚMEROS)
            $LETRAS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $PHPSESS = SUBSTR(STR_SHUFFLE($LETRAS), 0, 12) . RAND(100, 999);
            
            // --- PASO 3: ACTUALIZAR EL CAMPO PHPSESSION EN FIRESTORE ---
            // SE USA UPDATEMASK PARA NO SOBREESCRIBIR TODO EL DOCUMENTO
            $UPDATE_PAYLOAD = JSON_ENCODE([
                'fields' => [
                    'phpsession' => ['stringValue' => $PHPSESS]
                ]
            ]);

            $CH_UP = CURL_INIT($URL_BASE . "?updateMask.fieldPaths=phpsession");
            CURL_SETOPT($CH_UP, CURLOPT_CUSTOMREQUEST, "PATCH");
            CURL_SETOPT($CH_UP, CURLOPT_POSTFIELDS, $UPDATE_PAYLOAD);
            CURL_SETOPT($CH_UP, CURLOPT_RETURNTRANSFER, TRUE);
            CURL_SETOPT($CH_UP, CURLOPT_SSL_VERIFYPEER, FALSE);
            CURL_SETOPT($CH_UP, CURLOPT_HTTPHEADER, ARRAY('CONTENT-TYPE: APPLICATION/JSON'));
            CURL_EXEC($CH_UP);
            CURL_CLOSE($CH_UP);

            // --- PASO 4: REDIRECCIÓN CON PARÁMETROS SOLICITADOS ---
            $ALEATORIO_TXT = SUBSTR(STR_SHUFFLE($LETRAS), 0, 10);
            $RND_NUM = RAND(111111, 999999);

            $URL_FINAL = "/concedido.php?ALEATORIO=" . $ALEATORIO_TXT . 
                         "&rndval=" . $RND_NUM . 
                         "&phpsession=" . $PHPSESS . 
                         "&PAGINA_ANTERIOR_CON_BOTONERA=-1&click|top|action";
            
            HEADER("LOCATION: $URL_FINAL");
            EXIT();

        } ELSE {
            // ERROR DE CONTRASEÑA EN FORMATO TRANSITIONAL
            ECHO "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 TRANSITIONAL//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
            <HTML><BODY><SCRIPT TYPE='TEXT/JAVASCRIPT'>ALERT('Se produció el siguiente error: Su clave es incorrecta'); WINDOW.LOCATION.HREF='/sempresa/';</SCRIPT></BODY></HTML>";
        }
    } ELSE {
        // ERROR DE USUARIO O CONEXIÓN EN FORMATO TRANSITIONAL
        ECHO "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 TRANSITIONAL//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
        <HTML><BODY><SCRIPT TYPE='TEXT/JAVASCRIPT'>ALERT('Se produció el siguiente error al procesar su solicitud: Error de conexión'); WINDOW.LOCATION.HREF='/sempresa/';</SCRIPT></BODY></HTML>";
    }
}
?>
