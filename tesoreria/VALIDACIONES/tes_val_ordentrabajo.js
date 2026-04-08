/**
* @fileoverview Libreria con funciones de validaciones
*
* @author Alejandro Camacho
* @version 0.1
*/

var arrayPersona = [];
var valorBusqueda = 'CONSUMIDOR FINAL';
var valorBusqueda2 = 'VARIOS INGRESOS';
var bCf = false;
var bVi = false;
var contador = 0;
var stadoC= false,stadoV = false;


function inicio(){
  $("#txt_fec_entrega").createDatePickers();
  $("#txt_fec_recepcion").createDatePickers();
}

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;


//MODIFICAR ORDEN DE TRABAJO 
//Inicio Grid para presentar la busqueda 
$(function(){ 

$("#tableResult").createGrid({
        autowidth : false, shrinkToFit: true, height: 270,responsive:false,footerRow:true,
        autoheight: true,
        colModel: [
            {label: 'C&oacute;d.', name: 'Ord_Cod', width: 20, align: "center",key:true},
            {label: 'Numero', name: 'Ord_Num', width: 30, align: "center"},
            {label: 'Fecha Recepcion', name: 'Ord_Fecha_Rec', width: 50, align: "center"},
            {label: 'Fecha Entrega', name: 'Ord_Fecha_Ent', width: 50, align: "center"},
            {label: 'Cliente', name: 'Cliente', width: 150, align: "center"},
            {label: 'Descripcion', name: 'Ord_Descripcion', width: 150, align: "center"},
            {label: 'Total', name: 'Ord_Total', width: 50, align: "center"},
            {label: 'Abono', name: 'Ord_Abono', width: 50, align: "center"},
            {label: 'Saldo', name: 'Ord_Saldo', width: 50, align: "center"},
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatter:'gridButton', formatoptions:{action:imprimirOrden, icon:'print', type:'info',title:'Imprimir'}},
            { label: '&nbsp;', name: 'act4', width: 20, align: 'center',viewable: false, formatter:function(cellvalue, options, rowObject){                  
                   return $.getGridButton(cargarDoc, rowObject, 'Modificar', 'glyphicon glyphicon-arrow-right');
                 }
            } 
        ],
          loadComplete: function(data){
                if($.varValid(data.rows))
                for(var i=0,z=data.rows.length;i<z;i++){
                    if(data.rows[i]['Tar_Est'] ==='Inactivo') $("#"+data.rows[i].Tar_Cod+' td:not(.jqgrid-rownum)').addClass('cellRed2');
                    if(data.rows[i]['Tar_Est'] ==='Validado') $("#"+data.rows[i].Tar_Cod+' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                    if(data.rows[i]['Tar_Est'] ==='Entregado') $("#"+data.rows[i].Tar_Cod+' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                }
            }

    }, true, "#tableResultPager",{}); 

    $.createSearchDialog('clieDialog',[
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Cliente', name: 'cliente', width: 100},
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: 'Ciudad', name: 'Ciu_Des', hidden:false, width: 50 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectCliente} }
    ],null,null,null,{headertitles:true},{ title:'Cliente', text:'Prs_Ced' });                    

});
  
function cargarDoc(row){
       $('#documentoSearch').hide();
       $('#documentoMain').show();
       $.post("",{cargarEditar:true,Ord_Cod:row['Ord_Cod']}, function(response){
            $('#fichaForm').setData(response);
            const $select = document.querySelector('#Ser_Cod');
            $select.value = response['Ord_Servicio'];
      },'json').fail(function (){$.alert();});
}

function searchCliente(ced,tipo){
    (tipo==='ec')?ced=ced.substring(0,10):ced;
    $.post("",{searchCliente:true,Prs_Ced:ced}, function(response){
        if(response['existe']===true){
            $.alert('El cliente '+ced+' ya se encuentra registrado..!!');
            clear();
        }
    },'json').fail(function (){$.alert();});
}

 function modificar(){
  var cod = $('#Cli_Cod').val();
  var fec = $('#Ord_Fecha_Rec').val();
  var num = $('#Ord_Num').val();
  var part = $('#Ord_Partes').val();
  var desc= $('#Ord_Descripcion').val();
  var repu = $('#Ord_Repuestos').val();
  var obs = $('#Ord_Observaciones').val();

  if(cod!='' && fec!='' && num!='' && part!='' && desc!='' && repu!='' && obs!=''){
      $.saveDataJson('', $('#fichaForm').getData('saveDocument'), 
      function( resp ){
      if(resp['success']==true){
         $.alert("Orden de trabajo guardada con exito");
         $('#documentoMain').hide();
         $('#documentoSearch').show();
         limpiar();
         $('#tableResult').trigger('reloadGrid');
      }else{
          $.alert("No se pudo realizar la transaccion");
      }
      return false;
      });
  }else{
      $('#cliente').focus();
      $.alert('Ingrese todos los campos');

  }
}

function selectCliente(cliente){
  $('#clieFormTemp').setData($.extend(cliente,{op_opciones:'c'}));
  $('#clieDialog').dialog('close');
}


function checkEntrega(){
  if($('#f_entrega').is(':checked')){
    $("#txt_fec_entrega").prop('disabled', false);
  }
  else{
    $("#txt_fec_entrega").prop('disabled', true);
  }
  
}

function checkRecepcion(){
 if($('#f_recepcion').is(':checked')){
    $("#txt_fec_recepcion").prop('disabled', false);
  }
  else{
    $("#txt_fec_recepcion").prop('disabled', true);
  }
}

function atras(){
       $('#documentoMain').hide();
       $('#documentoSearch').show();
       limpiar();
    }

function limpiar(){
  $("#fichaForm")[0].reset();
  $("#Prs_Cor").text("");
  $("#cliente").text("");
  $("#Prs_Dir").text("");
  $("#Prs_Tel").text("");

  $("#Ord_Partes").text("");
  $("#Ord_Descripcion").text("");
  $("#Ord_Repuestos").text("");
}

function calcularSaldo(){
  var saldo = 0;
  var total = 0;
  var abono = 0;
  if($('#Ord_Total').val() != ""){
    total = parseFloat($('#Ord_Total').val());
  }
  if($('#Ord_Abono').val() != ""){
     abono = parseFloat($('#Ord_Abono').val());
  }
  saldo = total - abono;
  $('#Ord_Saldo').val(saldo);
}


function imprimirOrden(row){
    $.post( "",{imprimir:true, Ord_Cod:row['Ord_Cod']}, function( response ) {
        if(response['success']===true){
            $(response['html']).printElement({pageTitle:'Exa Software Contable'});
        }else{$.alert(response['message']);}                                   
     },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); console.log(error); });
}

 function agregarPartes(){
    var texto = $( "#Par_Cod option:selected" ).text();
    document.getElementById('Ord_Partes').value += texto + ', ';
}

function agregarDescripcion(){
    var texto = $( "#Des_Cod option:selected" ).text();
    document.getElementById('Ord_Descripcion').value += texto + ', ';
}

function agregarRepuestos(){
    var texto = $( "#Rep_Cod option:selected" ).text();
    document.getElementById('Ord_Repuestos').value += texto + ', ';
}