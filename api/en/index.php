<?php
$githubToken = getenv('CREA_IMAGEN_HTML');
$repoOwner = "jfornielestiburcio02-web"; 
$repoName = "hostingimage";      

$mensaje = "";
$urlImagen = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombreAleatorio = bin2hex(random_bytes(8)) . "." . pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $rutaInterna = "publicas/transicional/" . $nombreAleatorio;
    $rutaGitHub = "imagenes/" . $rutaInterna;
    
    $contenidoBase64 = base64_encode(file_get_contents($archivo['tmp_name']));
    $urlApi = "https://api.github.com/repos/$repoOwner/$repoName/contents/$rutaGitHub";
    
    $payload = json_encode([
        "message" => "Upload: $nombreAleatorio",
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
        // URL FINAL CON TU DOMINIO
        $protocolo = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $dominio = $_SERVER['HTTP_HOST'];
        $urlImagen = $protocolo . $dominio . "/imagenes/" . $rutaInterna;
        $mensaje = "¡Imagen lista en tu hosting!";
    } else {
        $error = json_decode($response, true);
        $mensaje = "Error " . $httpCode . ": " . ($error['message'] ?? 'Fallo de red');
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Hostimage</title>
<style type="text/css">
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: #f4f7f6; text-align: center; }
        #header { background: #fff; padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .btn-empresa { background: #000; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        #main { margin-top: 50px; }
        .drop-zone { border: 3px dashed #3498db; background: #fff; width: 400px; margin: 0 auto; padding: 60px; border-radius: 20px; cursor: pointer; transition: 0.3s; }
        .drop-zone:hover { background: #e8f4fd; border-color: #2980b9; }
        
        /* Loading Spinner */
        #loading { display: none; margin-top: 20px; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .result-box { margin-top: 30px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: inline-block; }
        input[type="text"] { width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</HEAD>
<BODY>
    <div id="header">
    <div>
 <a href="/es/"><img src="https://flagcdn.com/es.svg" width="50" /></a>
        </div>
        <a href="/sempresa/" class="btn-empresa">Solución para empresas</a>
    </div>
<div id="main">
<h1>Upload your file</h1>
        <form id="uploadForm" action="" method="post" enctype="multipart/form-data">
     <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
      <input type="file" name="archivo" id="fileInput" style="display:none;" onchange="showLoading()" />
     <strong>Click to upload your image</strong>
  </div>
        </form>
 <div id="loading">
  <div class="spinner"></div>
  <p>This will take a few seconds, please wait...</p>
        </div>
        <?php if ($urlImagen != ""): ?>
            <div class="result-box">
                <p style="color: green; font-weight: bold;"><?php echo $mensaje; ?></p>
                <input type="text" value="<?php echo $urlImagen; ?>" id="copyInput" readonly />
                <button onclick="copyLink()" style="padding: 10px; cursor:pointer;">Copy URL</button>
            </div>
        <?php elseif (strpos($mensaje, 'Error') !== false): ?>
            <p style="color:red;"><?php echo $mensaje; ?></p>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
        function showLoading() {
            document.getElementById('uploadForm').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
            document.getElementById('uploadForm').submit();
        }
        function copyLink() {
            var copyText = document.getElementById("copyInput");
            copyText.select();
            document.execCommand("copy");
            alert("Enlace copiado: " + copyText.value);
        }
    </script>

</BODY>
</HTML>

