
$(function(){

	$('#Suc_Cod').on('change',function(event){
        var sucursal = $("#Suc_Cod").val();
        $('#Tic_Cod').find('option').remove().end().append('<option value="">Seleccione...</option>').val('');
        $('#Aut_Cod').find('option').remove().end().append('<option value="">Seleccione...</option>').val('');
        $('#Pun_Sri').find('option').remove().end().append('<option value="">Seleccione...</option>').val('');
        $("#valNumeros").text("");
      	cargarDatos(sucursal);
    });

    $('#Tic_Cod').on('change',function(event){
        var sucursal = $("#Suc_Cod").val();
        var tipo = $("#Tic_Cod").val();
        $('#Aut_Cod').find('option').remove().end().append('<option value="">Seleccione...</option>').val('');
        $('#Pun_Sri').find('option').remove().end().append('<option value="">Seleccione...</option>').val('');
        $("#valNumeros").text("");
        cargarDatosAutorizacion(sucursal, tipo);
    });

    $('#Aut_Cod').on('change',function(event){
    	$('#Pun_Sri').find('option').remove().end().append('<option value="">Seleccione...</option>').val('');
    	$("#valNumeros").text("");
        var sucursal = $("#Suc_Cod").val();
        var tipo = $("#Tic_Cod").val();
        var Aut_Sri = $("#Aut_Cod option:selected").text();
        cargarDatosPuntos(sucursal, tipo, Aut_Sri);
    });

    $('#Pun_Sri').on('change',function(event){
        var sucursal = $("#Suc_Cod").val();
        var tipo = $("#Tic_Cod").val();
        var Aut_Sri = $("#Aut_Cod option:selected").text();
        var Pun_Sri = $("#Pun_Sri option:selected").text();
        buscarAuts(sucursal, tipo, Aut_Sri, Pun_Sri);
    });

});

var datosAutorizaciones;

function cargarDatos(sucu){
	$.getDataJson('',{'datos':true, 'sucursal':sucu},
		function(resp){
				var autoriza = resp['autorizaciones'];
				datosAutorizaciones = autoriza;
				const filteredArr = autoriza.reduce((acc, current) => {
				  const x = acc.find(item => item.Tic_Cod === current.Tic_Cod);
				  if (!x) {
				    return acc.concat([current]);
				  } else {
				    return acc;
				  }
				}, []);

				filteredArr.forEach(function(tipoDocumento) {
				  $("#Tic_Cod").append(new Option(tipoDocumento['Tic_Des'], tipoDocumento['Tic_Cod']));
				});

		},function(err){
			console.error(err);
		});
}

function cargarDatosAutorizacion(sucu, tipo){
	$.getDataJson('',{'autorizaci':true, 'sucursal':sucu, 'tipo': tipo},
		function(resp){
				var autoriza = resp['autorizaciones'];
				autoriza.forEach(function(tipoDocumento) {
				  $("#Aut_Cod").append(new Option(tipoDocumento['Aut_Sri'], tipoDocumento['Aut_Cod']));
				});

		},function(err){
			console.error(err);
		});
}

function cargarDatosPuntos(sucu, tipo, aut_sri){
	$.getDataJson('',{'puntoEmision':true, 'sucursal':sucu, 'tipo': tipo, 'autSri': aut_sri},
		function(resp){
				var autoriza = resp['puntosSRI'];
				autoriza.forEach(function(tipoDocumento) {
				  $("#Pun_Sri").append(new Option(tipoDocumento['Pun_Sri'], tipoDocumento['Aut_Cod']));
				});

		},function(err){
			console.error(err);
		});
}

function buscarAuts(sucu, tipo, Aut_Sri, Pun_Sri){
	$.getDataJson('',{'buscar':true, 'sucursal':sucu, 'tipo': tipo, 'autSri':Aut_Sri, 'punSri':Pun_Sri},
		function(resp){
				var autoriza = resp['autorizaciones'];
				buscarVacios(autoriza);
		},function(err){
			console.error(err);
		});
}


function buscarVacios(autCodigos){
	var codigos = new Array();
	var numeros = new Array();

	autCodigos.forEach(function(autorizacion){
		codigos.push(autorizacion.Aut_Cod)
	});

	codigoTexto = codigos.join();
	var tipo = $("#Tic_Cod option:selected").text();
	var valor = 'F';
	if(tipo == "COMPROBANTE DE RETENCION"){
		valor = "R";
	}
	if(tipo == "GU�A DE REMISI�N"){
		valor = "G";
	}
	codigoTexto = "(" + codigoTexto + ")";

	$.getDataJson('',{'consultarVetNums':true, 'autorizaciones':codigoTexto, 'tipo':valor},
		function(resp){
				resp['numeros'].forEach(function(num){
					numeros.push(num.Vet_Num);
				});
				var perdidos = missing(numeros.map(x=>+x));
				var rangos = getRanges(perdidos);
				var perdidos2 = rangos.join();
				if(perdidos != ""){
					$("#valNumeros").text(perdidos2);
				}
				else{
					$("#valNumeros").text("No se pierde la secuencia de numeros!");
				}

		},function(err){
			console.error(err);
		});
}

function missing(arr) {
    const result = [];
    if (arr.length <= 1) { return result }
    var i = 1, val = arr[0] + 1;
    const count = ((arr[arr.length - 1]) - val) - (arr.length - 2);
    while (result.length < count) {
        while (arr[i] !== val) { result.push(val++) }
        i++;
        val++;
    }
    return result;
}

function getRanges(array) {
  var ranges = [], rstart, rend;
  for (var i = 0; i < array.length; i++) {
    rstart = array[i];
    rend = rstart;
    while (array[i + 1] - array[i] == 1) {
      rend = array[i + 1]; // increment the index if the numbers sequential
      i++;
    }
    ranges.push(rstart == rend ? rstart+'' : rstart + '-' + rend);
  }
  return ranges;
}


