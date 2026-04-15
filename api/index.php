<?php
// Configuración de GitHub
$githubToken = getenv('CREA_IMAGEN_HTML');
$repoOwner = "jfornielestiburcio02-web"; 
$repoName = "hostingimage";      

$mensaje = "";
$urlImagen = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombreOriginal = basename($archivo['name']);
    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
    
    $nombreAleatorio = bin2hex(random_bytes(8)) . "." . $extension;
    $rutaGitHub = "imagenes/publicas/transicional/" . $nombreAleatorio;
    $contenidoBase64 = base64_encode(file_get_contents($archivo['tmp_name']));

    $urlApi = "https://api.github.com/repos/$repoOwner/$repoName/contents/$rutaGitHub";
    
    $payload = json_encode([
        "message" => "Upload public image: $nombreAleatorio",
        "content" => $contenidoBase64
    ]);

    $ch = curl_init($urlApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $githubToken",
        "User-Agent: Vercel-PHP-Hosting",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 201) {
        // Usamos raw.githubusercontent para que la imagen sea visible de inmediato
        $urlImagen = "https://raw.githubusercontent.com/$repoOwner/$repoName/main/$rutaGitHub";
        $mensaje = "¡Copia el enlace para tener la imagen lista!";
    } else {
        // DEPURACIÓN DETALLADA
        $datosError = json_decode($response, true);
        $detalle = isset($datosError['message']) ? $datosError['message'] : "Error desconocido";
        
        // Si el token llega vacío desde getenv
        if (empty($githubToken)) {
            $detalle = "LA VARIABLE 'CREA_IMAGEN_HTML' ESTÁ VACÍA EN VERCEL";
        }

        $mensaje = "<strong>ERROR HTTP " . $httpCode . "</strong>: " . $detalle;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Hostimage - Debug Mode</title>
    <style type="text/css">
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; }
        #header { background: #fff; padding: 15px 30px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .lang-switcher a { text-decoration: none; margin-right: 15px; color: #333; font-size: 14px; }
        .lang-switcher img { width: 20px; vertical-align: middle; border: 1px solid #ccc; }
        .empresa-link a { background: #000; color: #fff; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        #main-content { text-align: center; margin-top: 80px; }
        .drop-zone { border: 2px dashed #bbb; padding: 50px; width: 50%; margin: 0 auto; background: #fff; cursor: pointer; border-radius: 10px; }
        .result-box { margin-top: 30px; padding: 20px; background: #e7f3ff; display: inline-block; border-radius: 5px; border: 1px solid #b6d4fe; }
        .error-box { margin-top: 30px; padding: 20px; background: #ffe7e7; display: inline-block; border-radius: 5px; border: 1px solid #feb6b6; color: #d00; }
        input[type="text"] { width: 350px; padding: 8px; border: 1px solid #ccc; }
    </style>
</head>
<body>

    <div id="header">
        <div class="lang-switcher">
            <span>Cambiar lenguaje: </span>
            <a href="/es/"><img src="https://flagcdn.com/es.svg" alt="ES" /> ES</a>
            <a href="/en/"><img src="https://flagcdn.com/gb.svg" alt="EN" /> EN</a>
        </div>
        <div class="empresa-link">
            <a href="/sempresa/">Solución para empresas</a>
        </div>
    </div>

    <div id="main-content">
        <h1>Sube tu imagen rápidamente</h1>
        <p>Arrastra o selecciona un archivo para comenzar</p>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
                <input type="file" name="archivo" id="fileInput" style="display:none;" onchange="this.form.submit()" />
                <strong>Haz clic aquí o suelta tu imagen</strong>
            </div>
        </form>

        <?php if ($urlImagen != ""): ?>
            <div class="result-box">
                <strong><?php echo $mensaje; ?></strong><br /><br />
                <input type="text" value="<?php echo $urlImagen; ?>" id="copyInput" readonly="readonly" />
                <button onclick="copyLink()">Copiar Enlace</button>
            </div>
        <?php elseif ($mensaje != ""): ?>
            <div class="error-box">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
        function copyLink() {
            var copyText = document.getElementById("copyInput");
            copyText.select();
            document.execCommand("copy");
            alert("Enlace copiado con éxito");
        }
    </script>
</body>
</html>
