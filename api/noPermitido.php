<?php
// Función para obtener la IP real del usuario
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Vercel y Cloudflare envían la IP real aquí
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$userIP = getRealIP();

// Si sigue saliendo 127.0.0.1 en local, es porque tu servidor local 
// no tiene salida a internet, pero en Vercel esto mostrará la IP pública.

// Generar un Ray ID aleatorio (estilo Cloudflare)
$rayID = bin2hex(random_bytes(8)); 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>ACCESO RESTRINGIDO</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        BODY { 
            MARGIN: 0; 
            PADDING: 0; 
            FONT-FAMILY: VERDANA, GENEVA, SANS-SERIF; 
            BACKGROUND-COLOR: #F3F4F6; 
            DISPLAY: FLEX; 
            JUSTIFY-CONTENT: CENTER; 
            ALIGN-ITEMS: CENTER; 
            HEIGHT: 100VH; 
            COLOR: #1F2937;
        }
        .CAJA { 
            WIDTH: 450PX; 
            BACKGROUND: #FFFFFF; 
            PADDING: 40PX; 
            BORDER-RADIUS: 8PX; 
            BOX-SHADOW: 0 10PX 15PX -3PX RGBA(0, 0, 0, 0.1);
            BORDER-TOP: 5PX SOLID #EF4444; /* Barra roja de advertencia */
            TEXT-ALIGN: LEFT;
        }
        H1 { 
            MARGIN-TOP: 0;
            FONT-SIZE: 18PX; 
            COLOR: #B91C1C; 
        }
        .ALERTA {
            FONT-STYLE: ITALIC;
            COLOR: #6B7280;
            MARGIN-BOTTOM: 20PX;
            DISPLAY: BLOCK;
        }
        .DATA-BOX {
            BACKGROUND-COLOR: #F9FAFB;
            BORDER: 1PX SOLID #E5E7EB;
            PADDING: 15PX;
            BORDER-RADIUS: 4PX;
            FONT-FAMILY: MONOSPACE;
            FONT-SIZE: 13PX;
            COLOR: #374151;
            LINE-HEIGHT: 1.8;
        }
        .LABEL { COLOR: #9CA3AF; FONT-WEIGHT: BOLD; TEXT-TRANSFORM: UPPERCASE; FONT-SIZE: 10PX; }
        .BTN {
            MARGIN-TOP: 25PX;
            DISPLAY: BLOCK;
            TEXT-ALIGN: CENTER;
            TEXT-DECORATION: NONE;
            COLOR: #2563EB;
            FONT-SIZE: 12PX;
            FONT-WEIGHT: BOLD;
        }
        .BTN:HOVER { TEXT-DECORATION: UNDERLINE; }
    </STYLE>
</HEAD>
<BODY>

<DIV CLASS="CAJA">
    <H1>No puedes ver estas configuraciones</H1>
    <SPAN CLASS="ALERTA">*No tienes permiso*</SPAN>

    <DIV CLASS="DATA-BOX">
        <SPAN CLASS="LABEL">Tu IP:</SPAN> <?php echo $userIP; ?><BR>
        <SPAN CLASS="LABEL">Ray ID:</SPAN> <?php echo $rayID; ?><BR>
        <SPAN CLASS="LABEL">Estado:</SPAN> Denied
    </DIV>

    <A HREF="/index.php" CLASS="BTN">Volver al inicio</A>
</DIV>

</BODY>
</HTML>
