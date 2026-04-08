function calcular2()
{
	var Pre_Com = document.getElementById('Pre_Com').value != "" ? document.getElementById('Pre_Com').value : 0;
	var Pre_Por = document.getElementById('Pre_Por').value != "" ? document.getElementById('Pre_Por').value : 0;
	
	var obj = Math.round(((Pre_Com * Pre_Por)/100)*100)/100;
	document.getElementById('Pre_Uti').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Uti = document.getElementById('Pre_Uti').value != "" ? document.getElementById('Pre_Uti').value : 0;
	//obj = Math.round((parseFloat(Pre_Com)+parseFloat(Pre_Uti))*100)/100;
	//document.getElementById('Pre_Pvp').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Pvp = document.getElementById('Pre_Pvp').value != "" ? document.getElementById('Pre_Pvp').value : 0;
	var Pre_Dcs = document.getElementById('Pre_Dcs').value != "" ? document.getElementById('Pre_Dcs').value : 0;
	obj = Math.round(((Pre_Pvp*Pre_Dcs)/100)*100)/100;
	document.getElementById('Pre_Dct').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Dct = document.getElementById('Pre_Dct').value != "" ? document.getElementById('Pre_Dct').value : 0;
	obj = Math.round((parseFloat(Pre_Pvp)-parseFloat(Pre_Dct))*100)/100;
	document.getElementById('Pre_Tot').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Tot = document.getElementById('Pre_Tot').value != "" ? document.getElementById('Pre_Tot').value: 0;
	obj = Math.round((parseFloat(Pre_Tot)-parseFloat(Pre_Com))*100)/100;
	document.getElementById('Pre_Gan').value = isNaN(obj) ? 0 : obj;
	ColorGanancia();
}

function calcular()
{
	var Pre_Com = document.getElementById('Pre_Com').value != "" ? document.getElementById('Pre_Com').value : 0;
	var Pre_Por = document.getElementById('Pre_Por').value != "" ? document.getElementById('Pre_Por').value : 0;
	
	var obj = Math.round(((Pre_Com * Pre_Por)/100)*100)/100;
	document.getElementById('Pre_Uti').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Uti = document.getElementById('Pre_Uti').value != "" ? document.getElementById('Pre_Uti').value : 0;
	obj = Math.round((parseFloat(Pre_Com)+parseFloat(Pre_Uti))*100)/100;
	document.getElementById('Pre_Pvp').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Pvp = document.getElementById('Pre_Pvp').value != "" ? document.getElementById('Pre_Pvp').value : 0;
	var Pre_Dcs = document.getElementById('Pre_Dcs').value != "" ? document.getElementById('Pre_Dcs').value : 0;
	obj = Math.round(((Pre_Pvp*Pre_Dcs)/100)*100)/100;
	document.getElementById('Pre_Dct').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Dct = document.getElementById('Pre_Dct').value != "" ? document.getElementById('Pre_Dct').value : 0;
	obj = Math.round((parseFloat(Pre_Pvp)-parseFloat(Pre_Dct))*100)/100;
	document.getElementById('Pre_Tot').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Tot = document.getElementById('Pre_Tot').value != "" ? document.getElementById('Pre_Tot').value: 0;
	obj = Math.round((parseFloat(Pre_Tot)-parseFloat(Pre_Com))*100)/100;
	document.getElementById('Pre_Gan').value = isNaN(obj) ? 0 : obj;
	ColorGanancia();
}


function ColorGanancia()
{
	if(document.getElementById('Pre_Gan').value<0)
	{
		document.getElementById('Pre_Gan').style.color="red";
	}
	else
	{
		document.getElementById('Pre_Gan').style.color="";
	}
}
