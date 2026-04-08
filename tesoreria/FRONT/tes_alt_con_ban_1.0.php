<?php	
/**
* @abstract Permite registrar los cheques 
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($gridAjax)){ 	
        $responce['success']=false;
        $rs_mayor_banco = $obBD_con1->getArrayConsulta(342, $bancos.'*'.$txt_fec_fin,$obBD_conexion);     
        
        if (count($rs_mayor_banco) > 0) 
        { 
            $header['ses_emp_nom']=$Ses_Emp_Nom;$header['total_real']=number_format($saldo_ban,2);$header['total_mayor']=0;
            $header['range_fec']="RANGO DE FECHAS:&nbsp; <b>$txt_fec_ini</b> &nbsp;AL&nbsp; <b>$txt_fec_fin</b>";
            foreach ($rs_mayor_banco as $row){ // Calculo el Total del Libro Mayor
                if($row['Asi_Deh']=='D') {$header['total_mayor']=$header['total_mayor']+$row['Total'];}
                else   {$header['total_mayor']=$header['total_mayor']-$row['Total'];}
                $header['Pld_Cdc']=$row['Pld_Cdc'];$header['Pld_Des']=$row['Pld_Des'];$header['Ban_Cue']=$row['Ban_Cue'];
            } //Fin Lbro Mayor
            $ban=true;
            $header['total_fuera']=0; $header['total_curso']=0;$header['data_fuera']="";$header['data_curso']="";//$header['total_cheques']=0; 
            $cheques_otros = $obBD_con1->getArrayConsulta(372, $Ses_Emp_Cod.'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*'.$bancos,$obBD_conexion);
            $cheques = $obBD_con1->getArrayConsulta(344, $bancos.'*'.$txt_fec_ini.'*'.$txt_fec_fin,$obBD_conexion);            
            $rs_cheques=array_merge($cheques_otros,$cheques);
            $inicio= strtotime("$txt_fec_ini 00:00:00");$fin=strtotime("$txt_fec_fin 00:00:00");
            //$formato="mso-number-format:\"_([$$-300A] * #,##0.00_);[Red]_([$$-300A] * -(#,##0.00);_([$$-300A] * ----------;_(@_)\";";
            for($i=0;$i<count($rs_cheques);$i++){//Separar en Grupos
                 if(strtotime($rs_cheques[$i]['Com_Fec']." 00:00:00")<=$inicio){
                     $rs_cheques[$i]['Grupo']="MOVIMIENTOS EN TRANSITO FUERA DEL PERIODO";
                     $header['total_fuera']=$header['total_fuera']+$rs_cheques[$i]['Che_Val'];
                     $header['data_fuera']=$header['data_fuera']."<tr style='font-size:12px;'><td align='center'>".$rs_cheques[$i]['Com_Fec']."</td><td>".$rs_cheques[$i]['Che_Num']."</td><td style='white-space: nowrap; overflow: hidden;;padding-right:3px;'>".$rs_cheques[$i]['Asi_Glo']."</td><td style='white-space: nowrap; overflow: hidden;padding-left:3px;'>".$rs_cheques[$i]['proveedor']."</td><td align='center'>".$rs_cheques[$i]['Che_Fec']."</td><td align='right'>$ ".number_format($rs_cheques[$i]['Che_Val'],2)."</td><td>&nbsp;".$rs_cheques[$i]['asiento']."</td></tr>";
                 }else{
                     $rs_cheques[$i]['Grupo']="MOVIMIENTOS EN TRANSITO PERIODO EN CURSO";
                     $header['total_curso']=$header['total_curso']+$rs_cheques[$i]['Che_Val'];
                     $header['data_curso']=$header['data_curso']."<tr style='font-size:12px;'><td align='center'>".$rs_cheques[$i]['Com_Fec']."</td><td>".$rs_cheques[$i]['Che_Num']."</td><td style='white-space: nowrap; overflow: hidden;;padding-right:3px;'>".$rs_cheques[$i]['Asi_Glo']."</td><td style='white-space: nowrap; overflow: hidden;padding-left:3px;'>".$rs_cheques[$i]['proveedor']."</td><td align='center'>".$rs_cheques[$i]['Che_Fec']."</td><td align='right'>$ ".number_format($rs_cheques[$i]['Che_Val'],2)."</td><td>&nbsp;".$rs_cheques[$i]['asiento']."</td></tr>";
                 }                 
            }//Fin Separar en Grupos
            $header['total_conci']=number_format($header['total_mayor']+ $header['total_fuera']+ $header['total_curso'],2);
            $header['total_fuera']=number_format($header['total_fuera'],2);$header['total_curso']=number_format($header['total_curso'],2);
            $header['total_mayor']=number_format($header['total_mayor'],2);
                    
            $responce['success']=true;
            $responce['header']=$header;
            $responce['grid']['rows']=$rs_cheques;  
            $responce['grid']['page'] = 1;
            $responce['grid']['total'] = 1;
            $responce['grid']['records'] = count($rs_cheques);
            $responce['report']=reporteHtml($header,'tes_pri_con_ban.html');
        }
         
        utf8_encode_deep($responce);        
	echo json_encode($responce);
	exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?>
                <style>                    
                    .saldos{width: 120px;font-weight: bold;padding:5px;text-align: right;}
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
            <td height="10">&raquo; Generaci&oacute;n de Conciliaciones Bancarias<?Php echo $periodo; ?></td>
        </tr>
      <tr>
      <td height="389" align="left" valign="top">
        <form action="<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>" method="post" name= "form1" id= "form1">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>                                   
                    <td width="50%">
            <FIELDSET style="height: 50px;">
                <LEGEND>
                    <label class="Titulos2">Ingrese Rangos</label>
		</LEGEND>
                <table width="100%">
                    <tr>
                        <td width="30%" align="right">Desde:</td>
                        <td><input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="text ui-corner-all" style="text-align: center;" /></td>
                        <td>&nbsp;&nbsp;&nbsp;Hasta:</td>
                        <td><input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="text ui-corner-all" style="text-align: center;" /></td>
                    </tr>
                    <tr>
                        <td align="right">Saldo Bancario al Corte:</td>
                        <td colspan="3"><input name="saldo_ban" type="text" id="saldo_ban" value="0" size="10" class="text ui-corner-all" style="text-align: right;" /></td>                    
                    </tr>
                </table>
            </FIELDSET>
                    </td>
                     <td width="50%">
            <FIELDSET style="height: 50px;">
		<LEGEND>
                    <label class="Titulos2">Seleccionar Bancos</label>
		</LEGEND>
		<table width="100%" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="77" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;"><div align="right" >Banco:</div></td>
			  <td class="BarraBusqueda" style="border-left: 0px;">
                              <select name="bancos" id="bancos" class=""  >
<?php
    $rs_bancos = $obBD_con1->getArrayConsulta(339,$Ses_Emp_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
                                  <option value="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
<?php
        }
    }
?>
                              </select>
                          </td>
			  <td width="200"><div align="center">	                                
                                <button type="button" class="btn btn-success" title="Generar Concilición Bancaria" onclick="Search()"> <i class="icon-play icon-white"></i> <span>Generar Conciliaci&oacute;n</span> </button>
                            </div>
                          </td>
			</tr>
                 </table>
            </FIELDSET>
                    </td>
                </tr>
            </table>
        </form>
            <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la Concilicaci&oacute;n </label>
		</LEGEND>
                  
                    <div class="ui-widget ui-widget-content ui-corner-all">
                        <div class="ui-widget-header ui-corner-all" style="font-size: 16px;"><center>Conciliaci&oacute;n Bancaria</center></div>
                        <div style="padding:10px;" >
                            <center id="Rango" style="font-size: 13px;">&nbsp;</center>
                            <hr/>
                            <div style="width: 60%;">
                            <div class="segmento">Cuenta Contable:</div><div class="datasegmento"><input id="Cod_Cue" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                            <div class="segmento">Nombre Banco:</div><div  class="datasegmento"><input id="Nom_Ban" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                            <div class="segmento">Cuenta Banco:</div><div  class="datasegmento"><input id="Num_Cue" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
                            </div>
                            <hr/>
                            <div align="right">SALDO EN LIBRO DE LA EMPRESA: <input id="Sal_May" type="text" class="ui-widget-content ui-corner-all saldos" readonly /> </div>
                            <hr/>
                            <div> 
                                <table id="list"></table>
                                <div id="listPager"></div>
                            </div>
                            <hr/>
                            <div align="right">EL SALDO EN EL ESTADO DE CUENTA BANCARIO DEBE SER: <input id="Sal_Conc" type="text" class="ui-widget-content ui-corner-all saldos" readonly /> </div>
                            <hr/>
                        </div>
                    </div>
                
                
                 <script type="text/javascript">     
//                    function validarFecha(ini,fin){
////                        if($("#"+ini).datepicker( "getDate" )>$("#"+fin).datepicker( "getDate" )){
////                            $("#"+ini).datepicker( "setDate", $("#"+fin).datepicker( "getDate" ) );
////                            $("#"+ini).blur();  
////                        }
//                    }                   
                    function Search(){	
                         $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",$("#form1").getData('gridAjax'), function( response ) {
                                if(response['success']===true){
                                    var jgrid=$("#list");
                                    jgrid.clearGrid();
                                    $("#Cod_Cue").val(response['header']['Pld_Cdc']);
                                    $("#Nom_Ban").val(response['header']['Pld_Des']);
                                    $("#Num_Cue").val(response['header']['Ban_Cue']);
                                    $("#Sal_May").val(response['header']['total_mayor']);                                    
                                    $("#Sal_Conc").val(response['header']['total_conci']);
                                    $("#Rango").html(response['header']['range_fec']);
                                    $("#Exportar").html(response['report']);
                                    jgrid.jqGrid('setGridParam',{rowNum:response['grid']['records']});
                                    jgrid.jqGrid('setGridParam', {data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');
                                }else{alert("No se ha encontrado ningun registro!");}
                             },'json').fail(function(error) { alert("El Servidor ha fallado en responder! "); });  
                             $("#form1").effect("highlight",{},500);	
                    }            
                   
                    $(document).ready(function () {                        
                        $.createDateRange('#txt_fec_ini','#txt_fec_fin');
                        var jgrid=$("#list");
                        jgrid.jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            autowidth : true, shrinkToFit: true, height: 220,
                            colModel: [
                                { label: 'Cód.Int.', name: 'id', key: true, width: 15,align:"center", hidden:true },
                                { label: 'Grupo', width: 1,name: 'Grupo', hidden:true },
                                { label: 'Fecha Comp.', name: 'Com_Fec', width: 40,align:"center" },   
                                { label: 'No. Che.', name: 'Che_Num', width: 40},  
                                { label: 'Concepto', name: 'Asi_Glo', width: 90},                                                                  
                                { label: 'Beneficiario', name: 'proveedor', width: 150 },                      
                                { label: 'Fecha Che.', name: 'Che_Fec', width: 40,align:"center"},
                                { label: 'Importe', name: 'Che_Val', width: 70, align: 'right', formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round' 
                                },
                                { label: 'Asiento', name: 'asiento', width: 40},
                                   { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) {return  '<span class="btn btn-info btn-mini" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\''+rowObject.id+'\');"><i class="icon-info-sign icon-white"></i></span>';}
                                   }
                            ],                                                     
                            rowNum: 10,pager: "#listPager", gridview: true, rownumbers: true, viewrecords: false, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,pgtext: null,                            
                            groupingView: {
                                groupField: ["Grupo"],groupColumnShow: [false],
                                groupText: ["<div><span style='float:left;'> {1} Cheque(s)</span> <b>{0}</b>  <b style='position: absolute;right: 25px;'>Total: $ {Che_Val} <b></div>"],
                                groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
                            },grouping: true
                            
                        });                        
                        jgrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        jgrid.jqGrid('bindKeys'); 
                    });  
               </script>
	</FIELDSET>
        </td>
      </tr>
      <tr>
          <td>
              <div style="padding:15px;">
                  <button onclick="$('#Exportar').printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>',leaveOpen: true})" title="Imprimir Reporte" type="button" class="btn btn-primary start" > <i class="icon-print icon-white"></i> <span>Imprimir</span></button>
                  <button onclick="$.downloadFile($.exportarExcelBlob($('#Exportar').html(),'Reporte Conciliacion'),'Reporte Conciliacion-'+$.getDate()+'.xls');/*downloadFile(exportarExcelBlob('Exportar','Reporte Conciliacion'),'Reporte Conciliacion-'+getDate()+'.xls')*/" title="Descargar archivo de Excel" class="btn btn-primary start" > <i class="icon-share icon-white" ></i> <span>Excel</span></button>               
                <!--<button type="button" class="btn btn-primary start" onclick="exportarExcel('Exportar')"> <i class="icon-share icon-white"></i> <span>Excel</span></button>-->              
              </div>
          </td>
      </tr>
    </table>
   	
</div>	
    <div id="Exportar" style="display: none;"> 
    </div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>