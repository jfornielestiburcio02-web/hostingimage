<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 TRANSITIONAL//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
    <TITLE>HOSTING IMAGE - ACCESO</TITLE>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="TEXT/HTML; CHARSET=UTF-8">
    <STYLE TYPE="TEXT/CSS">
        /* Fondo neutro y profesional */
        BODY { 
            MARGIN: 0; 
            PADDING: 0; 
            FONT-FAMILY: VERDANA, GENEVA, SANS-SERIF; 
            BACKGROUND-COLOR: #E5E7EB; 
            DISPLAY: FLEX; 
            JUSTIFY-CONTENT: CENTER; 
            ALIGN-ITEMS: CENTER; 
            HEIGHT: 100VH; 
            COLOR: #374151;
        }

        /* Caja de login limpia */
        .CAJA { 
            WIDTH: 360PX; 
            BACKGROUND: #FFFFFF; 
            PADDING: 40PX; 
            BORDER-RADIUS: 8PX; 
            BOX-SHADOW: 0 4PX 6PX -1PX RGBA(0, 0, 0, 0.1), 0 2PX 4PX -1PX RGBA(0, 0, 0, 0.06);
            BORDER: 1PX SOLID #D1D5DB;
        }

        H2 { 
            MARGIN-TOP: 0;
            FONT-SIZE: 20PX; 
            COLOR: #111827; 
            LETTER-SPACING: -0.5PX;
            TEXT-ALIGN: CENTER;
        }

        .SUBTITLE { 
            FONT-SIZE: 12PX; 
            COLOR: #6B7280; 
            MARGIN-BOTTOM: 25PX; 
            TEXT-ALIGN: CENTER;
        }

        /* Inputs rectos y serios */
        .INPUT-GROUP { TEXT-ALIGN: LEFT; MARGIN-BOTTOM: 15PX; }
        
        LABEL { 
            DISPLAY: BLOCK; 
            FONT-SIZE: 11PX; 
            FONT-WEIGHT: BOLD;
            COLOR: #4B5563; 
            MARGIN-BOTTOM: 6PX; 
        }
        
        INPUT { 
            WIDTH: 100%; 
            PADDING: 10PX; 
            BOX-SIZING: BORDER-BOX; 
            BORDER: 1PX SOLID #D1D5DB; 
            BORDER-RADIUS: 4PX; 
            FONT-FAMILY: VERDANA;
            FONT-SIZE: 13PX;
            BACKGROUND-COLOR: #F9FAFB;
        }

        INPUT:FOCUS { 
            OUTLINE: NONE; 
            BORDER-COLOR: #2563EB; 
            BACKGROUND-COLOR: #FFFFFF;
            BOX-SHADOW: 0 0 0 3PX RGBA(37, 99, 235, 0.1);
        }

        /* Botón sólido */
        .BOTON { 
            BACKGROUND-COLOR: #1F2937;
            COLOR: #FFFFFF; 
            PADDING: 12PX; 
            WIDTH: 100%; 
            CURSOR: POINTER; 
            BORDER: NONE; 
            BORDER-RADIUS: 4PX;
            FONT-WEIGHT: BOLD; 
            FONT-SIZE: 14PX;
            MARGIN-TOP: 10PX;
            TRANSITION: BACKGROUND 0.2S;
        }

        .BOTON:HOVER { 
            BACKGROUND-COLOR: #000000;
        }

        /* Links de alternancia */
        .TOGGLE-LINK { 
            MARGIN-TOP: 20PX; 
            FONT-SIZE: 11PX; 
            COLOR: #6B7280; 
            TEXT-ALIGN: CENTER;
        }

        .TOGGLE-LINK A { 
            COLOR: #2563EB; 
            TEXT-DECORATION: NONE; 
            FONT-WEIGHT: BOLD; 
            CURSOR: POINTER;
        }

        .TOGGLE-LINK A:HOVER { TEXT-DECORATION: UNDERLINE; }

        /* Corrección de visibilidad: Ocultar registro al inicio */
        #SECCION-REGISTRO { DISPLAY: NONE; }
    </STYLE>
</HEAD>
<BODY>

<DIV CLASS="CAJA">
    
    <DIV ID="SECCION-LOGIN">
        <H2>Login</H2>
        <DIV CLASS="SUBTITLE"></DIV>
        
        <FORM ACTION="procesar.php" METHOD="POST">
            <DIV CLASS="INPUT-GROUP">
                <LABEL>User</LABEL>
                <INPUT TYPE="TEXT" NAME="USUARIO" REQUIRED>
            </DIV>
            
            <DIV CLASS="INPUT-GROUP">
                <LABEL>Password</LABEL>
                <INPUT TYPE="PASSWORD" NAME="CONTRASENA" REQUIRED>
            </DIV>
            
            <BUTTON TYPE="SUBMIT" CLASS="BOTON">Login</BUTTON>
        </FORM>
        
        <DIV CLASS="TOGGLE-LINK">
            ¿No tienes cuenta? <A HREF="JAVASCRIPT:VOID(0)" ONCLICK="toggleForms()">Regístrate aquí</A>
        </DIV>
    </DIV>

    <DIV ID="SECCION-REGISTRO">
        <H2> Create Account </H2>
        <DIV CLASS="SUBTITLE"></DIV>
        
        <FORM ACTION="registrar.php" METHOD="POST">
            <DIV CLASS="INPUT-GROUP">
                <LABEL>User</LABEL>
                <INPUT TYPE="TEXT" NAME="USUARIO" REQUIRED>
            </DIV>
            
            <DIV CLASS="INPUT-GROUP">
                <LABEL>Password</LABEL>
                <INPUT TYPE="PASSWORD" NAME="CONTRASENA" REQUIRED>
            </DIV>

            <DIV CLASS="INPUT-GROUP">
                <LABEL>Repeat Password</LABEL>
                <INPUT TYPE="PASSWORD" NAME="REPETIR_CONTRASENA" REQUIRED>
            </DIV>
            
            <BUTTON TYPE="SUBMIT" CLASS="BOTON">Create</BUTTON>
        </FORM>
        
        <DIV CLASS="TOGGLE-LINK">
            ¿Did you already have a account? <A HREF="JAVASCRIPT:VOID(0)" ONCLICK="toggleForms()">Login</A>
        </DIV>
    </DIV>

</DIV>

<SCRIPT TYPE="TEXT/JAVASCRIPT">
    function toggleForms() {
        var login = document.getElementById('SECCION-LOGIN');
        var registro = document.getElementById('SECCION-REGISTRO');
        
        if (login.style.display === 'none') {
            login.style.display = 'block';
            registro.style.display = 'none';
        } else {
            login.style.display = 'none';
            registro.style.display = 'block';
        }
    }
</SCRIPT>

</BODY>
</HTML>
