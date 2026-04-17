<?PHP
// 1. FORZAR VISUALIZACIÓN DE ERRORES (SOLO PARA PRUEBAS)
ERROR_REPORTING(E_ALL);
INI_SET('DISPLAY_ERRORS', 1);

// 2. CARGAR CONFIGURACIÓN
// SI EL ARCHIVO ESTÁ EN /api/config.php PRUEBA ESTA RUTA ABSOLUTA
$RUTA_CONFIG = $_SERVER['DOCUMENT_ROOT'] . '/api/config.php';

IF (!FILE_EXISTS($RUTA_CONFIG)) {
    DIE("ERROR CRÍTICO: NO SE ENCUENTRA EL ARCHIVO CONFIG.PHP EN: " . $RUTA_CONFIG);
}

INCLUDE $RUTA_CONFIG;

// 3. PROCESAR LOGIN
IF ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $USER_LOGIN = $_POST['USUARIO'] ?? '';
    $PASS_LOGIN = $_POST['CONTRASENA'] ?? '';

    // VALIDAR QUE NO ESTÉN VACÍOS
    IF (EMPTY($USER_LOGIN) || EMPTY($PASS_LOGIN)) {
        DIE("ERROR: CAMPOS VACÍOS");
    }

    // URL PARA FIRESTORE
    $PROJECT_ID = $FIREBASE_CONFIG['projectId'];
    $URL_BASE = "https://firestore.googleapis.com/v1/projects/{$PROJECT_ID}/databases/(default)/documents/usuarios/{$USER_LOGIN}";

    // --- PASO 1: CONSULTA ---
    $CH = CURL_INIT();
    CURL_SETOPT($CH, CURLOPT_URL, $URL_BASE);
    CURL_SETOPT($CH, CURLOPT_RETURNTRANSFER, TRUE);
    CURL_SETOPT($CH, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $RESPONSE = CURL_EXEC($CH);
    $HTTP_CODE = CURL_GETINFO($CH, CURLINFO_HTTP_CODE); 
    
    IF (CURL_ERRNO($CH)) {
        DIE("ERROR DE CURL: " . CURL_ERROR($CH));
    }
    CURL_CLOSE($CH);

    $DATA = JSON_DECODE($RESPONSE, TRUE);

    // --- PASO 2: VALIDACIÓN ---
    IF ($HTTP_CODE == 200 && ISSET($DATA['fields']['contrasena']['stringValue'])) {
        
        $DB_PASS = $DATA['fields']['contrasena']['stringValue'];

        IF ($DB_PASS === $PASS_LOGIN) {
            
            // GENERAR PHPSESSIONID
            $LETRAS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $PHPSESS = SUBSTR(STR_SHUFFLE($LETRAS), 0, 12) . RAND(100, 999);
            
            // --- PASO 3: ACTUALIZAR ---
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
            CURL_SETOPT($CH_UP, CURLOPT_HTTPHEADER, ARRAY('Content-Type: application/json'));
            CURL_EXEC($CH_UP);
            CURL_CLOSE($CH_UP);

            // --- PASO 4: REDIRECCIÓN ---
            $ALEATORIO_TXT = SUBSTR(STR_SHUFFLE($LETRAS), 0, 10);
            $RND_NUM = RAND(111111, 999999);

            $URL_FINAL = "/concedido.php?ALEATORIO=" . $ALEATORIO_TXT . 
                         "&rndval=" . $RND_NUM . 
                         "&phpsession=" . $PHPSESS . 
                         "&PAGINA_ANTERIOR_CON_BOTONERA=-1&click|top|action";
            
            HEADER("LOCATION: $URL_FINAL");
            EXIT();

        } ELSE {
            ECHO "<HTML><BODY><SCRIPT>ALERT('Usuario y/o Contraseña incorrectos'); WINDOW.LOCATION.HREF='/sempresa/';</SCRIPT></BODY></HTML>";
        }
    } ELSE {
        ECHO "<HTML><BODY><SCRIPT>ALERT('Se produció un error de conexión (CODE: $HTTP_CODE)'); WINDOW.LOCATION.HREF='/sempresa/';</SCRIPT></BODY></HTML>";
    }
} ELSE {
    HEADER("LOCATION: /sempresa/");
}
?>
