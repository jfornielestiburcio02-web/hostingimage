<?php
session_start();

// 1. CONFIGURACIÓN
$proyectoID = "hostingimage1";
$tokenURL   = $_GET['ALEATORIO'] ?? '';

if (empty($tokenURL)) {
    exit("ACCESO DENEGADO: Falta el parámetro ALEATORIO.");
}

// 2. CONSTRUCCIÓN DE LA CONSULTA (Buscamos en toda la colección 'usuarios')
$url = "https://firestore.googleapis.com/v1/projects/{$proyectoID}/databases/(default)/documents:runQuery";

// Estructura JSON para buscar el campo 'phpsession' que sea igual al token de la URL
$query = [
    'structuredQuery' => [
        'from' => [['collectionId' => 'usuarios']],
        'where' => [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'phpsession'],
                'op' => 'EQUAL',
                'value' => ['stringValue' => $tokenURL]
            ]
        ],
        'limit' => 1
    ]
];

$payload = json_encode($query);

// 3. EJECUCIÓN MEDIANTE CURL (POST)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

// 4. VALIDACIÓN DE RESULTADOS
// Firebase devuelve un array vacío [] si no encuentra nada, o un array con el documento.
// Si no hay 'document', es que no existe nadie con ese phpsession.
if ($httpCode !== 200 || empty($data) || !isset($data[0]['document'])) {
    exit("ACCESO DENEGADO: No existe ningún usuario con ese token.");
}

// SI LLEGA AQUÍ, EL TOKEN EXISTE EN LA BASE DE DATOS
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { FONT-FAMILY: VERDANA; PADDING: 50PX; BACKGROUND-COLOR: #FFF; COLOR: #333; TEXT-ALIGN: CENTER; }
        .BOX { BORDER: 2PX SOLID #333; PADDING: 30PX; DISPLAY: INLINE-BLOCK; BORDER-RADIUS: 5PX; }
        .BTN { 
            DISPLAY: BLOCK; 
            MARGIN: 15PX AUTO; 
            PADDING: 12PX 25PX; 
            BACKGROUND-COLOR: #333; 
            COLOR: #FFF; 
            TEXT-DECORATION: NONE; 
            FONT-WEIGHT: BOLD;
            WIDTH: 220PX;
            BORDER-RADIUS: 3PX;
        }
        .BTN:HOVER { BACKGROUND-COLOR: #000; }
        H1 { MARGIN-TOP: 0; }
    </STYLE>
</HEAD>
<BODY>

    <DIV CLASS="BOX">
        <H1>CONTENIDO VALIDADO</H1>
        <P>Token encontrado en la base de datos de usuarios.</P>
        <HR>
        
        <A HREF="conseguirapi.php" CLASS="BTN">Conseguir API</A>
        <A HREF="subidaManual.php" CLASS="BTN">Subir manualmente</A>
    </DIV>

</BODY>
</HTML>
