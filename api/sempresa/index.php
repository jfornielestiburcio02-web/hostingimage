<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>HOSTING IMAGE - ACCESO</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        /* Estilos Base y Fondo Animado */
        BODY { 
            MARGIN: 0; 
            PADDING: 0; 
            FONT-FAMILY: 'Segoe UI', VERDANA, SANS-SERIF; 
            BACKGROUND: #F4F7F6; 
            BACKGROUND-IMAGE: LINEAR-GRADIENT(135DEG, #667EEA 0%, #764BA2 100%);
            DISPLAY: FLEX; 
            JUSTIFY-CONTENT: CENTER; 
            ALIGN-ITEMS: CENTER; 
            HEIGHT: 100VH; 
        }

        /* Contenedor Principal Estilo Card */
        .CAJA { 
            WIDTH: 400PX; 
            BACKGROUND: #FFFFFF; 
            PADDING: 45PX; 
            BORDER-RADIUS: 15PX; 
            BOX-SHADOW: 0 15PX 35PX RGBA(0,0,0,0.2); 
            TEXT-ALIGN: CENTER;
            TRANSITION: ALL 0.3S EASE;
        }

        H2 { COLOR: #333; FONT-SIZE: 24PX; MARGIN-BOTTOM: 10PX; LETTER-SPACING: -1PX; }
        .SUBTITLE { COLOR: #777; FONT-SIZE: 14PX; MARGIN-BOTTOM: 30PX; }

        /* Estilo de los Inputs Modernos */
        .INPUT-GROUP { TEXT-ALIGN: LEFT; MARGIN-BOTTOM: 20PX; }
        LABEL { DISPLAY: BLOCK; FONT-SIZE: 12PX; COLOR: #555; MARGIN-BOTTOM: 5PX; FONT-WEIGHT: BOLD; }
        
        INPUT { 
            WIDTH: 100%; 
            PADDING: 12PX; 
            BOX-SIZING: BORDER-BOX; 
            BORDER: 2PX SOLID #EEE; 
            BORDER-RADIUS: 8PX; 
            FONT-SIZE: 14PX;
            TRANSITION: BORDER-COLOR 0.3S;
        }
        INPUT:FOCUS { 
            OUTLINE: NONE; 
            BORDER-COLOR: #764BA2; 
            BACKGROUND: #F9F9FF; 
        }

        /* Botón con Degradado */
        .BOTON { 
            BACKGROUND: LINEAR-GRADIENT(TO RIGHT, #667EEA, #764BA2);
            COLOR: #FFFFFF; 
            PADDING: 14PX; 
            WIDTH: 100%; 
            CURSOR: POINTER; 
            BORDER: NONE; 
            BORDER-RADIUS: 8PX;
            FONT-WEIGHT: BOLD; 
            FONT-SIZE: 16PX;
            LETTER-SPACING: 1PX;
            BOX-SHADOW: 0 4PX 15PX RGBA(118, 75, 162, 0.3);
            TRANSITION: TRANSFORM 0.2S, BOX-SHADOW 0.2S;
        }
        .BOTON:HOVER { 
            TRANSFORM: SCALE(1.02); 
            BOX-SHADOW: 0 6PX 20PX RGBA(118, 75, 162, 0.4);
        }

        /* Switch para cambiar entre Login y Registro */
        .TOGGLE-LINK { 
            MARGIN-TOP: 20PX; 
            FONT-SIZE: 13PX; 
            COLOR: #777; 
        }
        .TOGGLE-LINK A { 
            COLOR: #667EEA; 
            TEXT-DECORATION: NONE; 
            FONT-WEIGHT: BOLD; 
            CURSOR: POINTER;
        }

        /* Esconder el registro por defecto */
        #FORM-REGISTRO { DISPLAY: NONE; }
    </STYLE>
</HEAD>
<BODY>

<DIV CLASS="CAJA">
    
    <DIV ID="SECCION-LOGIN">
        <H2>Bienvenido</H2>
        <P CLASS="SUBTITLE">Introduce tus credenciales para entrar</P>
        
        <FORM ACTION="procesar_login.php" METHOD="POST">
            <DIV CLASS="INPUT-GROUP">
                <LABEL>USUARIO</LABEL>
                <INPUT TYPE="TEXT" NAME="USUARIO" PLACEHOLDER="Ej: jmatamoros" REQUIRED>
            </DIV>
            
            <DIV CLASS="INPUT-GROUP">
                <LABEL>CONTRASEÑA</LABEL>
                <INPUT TYPE="PASSWORD" NAME="CONTRASENA" PLACEHOLDER="••••••••" REQUIRED>
            </DIV>
            
            <BUTTON TYPE="SUBMIT" CLASS="BOTON">ENTRAR AL PANEL</BUTTON>
        </FORM>
        
        <DIV CLASS="TOGGLE-LINK">
            ¿No tienes cuenta? <A ONCLICK="toggleForms()">Regístrate ahora</A>
        </DIV>
    </DIV>

    <DIV ID="SECCION-REGISTRO">
        <H2>Crear Cuenta</H2>
        <P CLASS="SUBTITLE">Introduce estos datos para registrarte</P>
        
        <FORM ACTION="registrar.php" METHOD="POST">
            <DIV CLASS="INPUT-GROUP">
                <LABEL>USUARIO</LABEL>
                <INPUT TYPE="TEXT" NAME="USUARIO" PLACEHOLDER="Nuevo usuario" REQUIRED>
            </DIV>
            
            <DIV CLASS="INPUT-GROUP">
                <LABEL>CONTRASEÑA</LABEL>
                <INPUT TYPE="PASSWORD" NAME="CONTRASENA" PLACEHOLDER="Mínimo 6 caracteres" REQUIRED>
            </DIV>

            <DIV CLASS="INPUT-GROUP">
                <LABEL>REPETIR CONTRASEÑA</LABEL>
                <INPUT TYPE="PASSWORD" NAME="REPETIR_CONTRASENA" PLACEHOLDER="Confirma tu contraseña" REQUIRED>
            </DIV>
            
            <BUTTON TYPE="SUBMIT" CLASS="BOTON">REGISTRARME</BUTTON>
        </FORM>
        
        <DIV CLASS="TOGGLE-LINK">
            ¿Ya eres miembro? <A ONCLICK="toggleForms()">Inicia sesión</A>
        </DIV>
    </DIV>

</DIV>

<SCRIPT TYPE="TEXT/JAVASCRIPT">
    // Función simple para cambiar de formulario con estilo
    FUNCTION toggleForms() {
        VAR login = DOCUMENT.GETELEMENTBYID('SECCION-LOGIN');
        VAR registro = DOCUMENT.GETELEMENTBYID('SECCION-REGISTRO');
        
        IF (login.STYLE.DISPLAY === 'NONE') {
            login.STYLE.DISPLAY = 'BLOCK';
            registro.STYLE.DISPLAY = 'NONE';
        } ELSE {
            login.STYLE.DISPLAY = 'NONE';
            registro.STYLE.DISPLAY = 'BLOCK';
        }
    }
</SCRIPT>

</BODY>
</HTML>
