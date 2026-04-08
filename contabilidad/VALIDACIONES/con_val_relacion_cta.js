// JavaScript Document

function asignar_fechas(valor)
{
	arreglo = valor.split("*");
	document.getElementById('Pec_Fei').value = arreglo[1];
	document.getElementById('Pec_Fef').value = arreglo[2];
}
