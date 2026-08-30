// JavaScript Document
function asignar_fechas(valor) {
	arreglo = valor.split("*");
	document.getElementById('Pec_Fei').value = arreglo[1];
	document.getElementById('Pec_Fef').value = arreglo[2];
}

/* Balance General */
function validar_balance(form, campo) {
	/* Variables del periodo contable */
	var Pec_Fei = document.getElementById('Pec_Fei').value;
	var Pec_Fef = document.getElementById('Pec_Fef').value;
	//var Periodo = document.getElementById('Pec_Cod').text;	
	/* Variables de las fechas seleccionadas */
	var ini = document.getElementById('txt_fec_ini').value;
	var fin = document.getElementById('txt_fec_fin').value;

	if (requerido(campo) != false) {
		if (ini >= Pec_Fei && fin <= Pec_Fef) {
			form.submit();
		} else {
			$.alert("Las fechas deben estar en el rango del periodo contable seleccionado <br> Inicio: " + Pec_Fei + " <br> Fin    : " + Pec_Fef,null,'warning');
		}
	}
}//Fin del function validar_balance(form)
