/* NitValidation Windows*/
function NitVal() {
   window.open("NitVal.html","NIT","width=300,height=150,scrollbars=NO,status=no,location=no,resizable=no")
}

// Usuarios
function AltaU() {
   window.open("ausers.html","Altas de Usuarios","width=500,height=475,scrollbars=NO,status=no,location=no,resizable=no")
}

function ModU(id) {
   window.open("aUsers.jsp?id="+id,"Modificacion de Usuarios","width=775,height=475,scrollbars=NO,status=no,location=no,resizable=no")
}

// Grupos
function AltaG() {
   window.open("aGroups.jsp","Altas de Groupos","width=775,height=475,scrollbars=NO,status=no,location=no,resizable=no")
}

function ModG(id) {
   window.open("aGroups.jsp?id="+id,"Modificacion de Grupos","width=775,height=475,scrollbars=NO,status=no,location=no,resizable=no")
}

// Opciones
function AltaO() {
   window.open("aOptions.jsp","Altas de Opciones","width=775,height=475,scrollbars=NO,status=no,location=no,resizable=no")
}

function ModO(id) {
   window.open("aOptions.jsp?op="+id,"Modificacion de Opciones","width=775,height=475,scrollbars=NO,status=no,location=no,resizable=no")
}

// Validacion del NIT GT
function valNIT() 
{
	var Res = false;
	var nt = document.getElementById("nit");
	t = nt.value;
	var pos = t.indexOf("-");
	var correlativo = t.substring(0, pos);
	var verificador = t.substring(pos + 1, t.length); 
	var valor = 0;
	var valid = 0;

	//fill up to 9 digits.
	for ( i=correlativo.length; i<9; i++)
		correlativo = "0"+correlativo;
	
	//get the complete sum.
	var q=0;
	for ( j=10; j>=2; j--)
		valor = valor+correlativo[q++]*j;

	valid = 11 - (valor % 11)

	if ( valid == 10 && verificador.toUpperCase()=='K')
		Res = true;
	
	if ( valid == 11 && verificador==0)
		Res = true;
	
	if ( valid <10 && verificador==valid )
		Res = true;

	if (!Res)
	{
		document.getElementById("info").className = "alert alert-error";
		document.getElementById("info").innerHTML = "Nit Invalido!";
	}else
	{
		document.getElementById("info").className = "alert alert-success";
		document.getElementById("info").innerHTML = "Nit Valido!";
	}
} 

function Textarea_Sin_Enter($char, $mozChar, $id)
{
   //alert ($char+" "+$mozChar);
   $textarea = document.getElementById($id);
   niveles = -1;
    
   if($mozChar != null) { // Navegadores compatibles con Mozilla
       if($mozChar == 13){
           if(navigator.appName == "Opera") niveles = -2;
           $textarea.value = $textarea.value.slice(0, niveles);
       }
   // navegadores compatibles con IE
   } else if($char == 13) $textarea.value = $textarea.value.slice(0,-2);
}
