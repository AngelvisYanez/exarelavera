<?php	
/**
* @abstract Permite registrar los cheques 
* @author Erik Niebla
* Fecha de creaciï¿½n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($gridAjax)){ 	
        $responce['success']=false;
        $rs_mayor_banco = $obBD_con1->getArrayConsulta(381, $bancos.'*'.$txt_fec_fin,$obBD_conexion);     
        
        if (count($rs_mayor_banco) > 0){ 
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
            $cheques = $obBD_con1->getArrayConsulta(382, $bancos.'*'.$txt_fec_ini.'*'.$txt_fec_fin,$obBD_conexion);            
            $rs_cheques=array_merge($cheques_otros,$cheques);
            $inicio= strtotime("$txt_fec_ini 00:00:00");$fin=strtotime("$txt_fec_fin 00:00:00");
            //$formato="mso-number-format:\"_([$$-300A] * #,##0.00_);[Red]_([$$-300A] * -(#,##0.00);_([$$-300A] * ----------;_(@_)\";";
            for($i=0;$i<count($rs_cheques);$i++){//Separar en Grupos
                 if(strtotime($rs_cheques[$i]['Com_Fec']." 00:00:00")<$inicio){
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

            $responce=array(
                success=>true,header=>$header,report=>reporteHtml($header,'tes_pri_con_ban.html'),grid=>array(rows=>$rs_cheques,page=>1,total=>1,records=>count($rs_cheques))
            );
        }  
        if(!$responce['success']) $responce['message']="No se ha encontrado ningun registro!";
        utf8_encode_deep($responce); echo json_encode($responce); exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>                	
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
                <style>                    
                    .saldos{width: 120px;font-weight: bold;padding:5px;text-align: right;}
                    hr{border-style: inset;border-width: 1px;margin-top: 5px; margin-bottom: 5px;}
                </style>
	</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Generaci&oacute;n de Conciliaciones Bancarias<?Php echo $periodo; ?></h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <form action="<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>" method="post" name= "form1" id= "form1" class="form-horizontal normal">
                        <div class="col-sm-6">
                            <fieldset class="exa-fieldset">
                                <legend><label class="Titulos2">Ingrese Rangos</label></legend>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Desde:</label>  
                                    <div class="col-xs-3" ><input name="txt_fec_ini" type="text" id="txt_fec_ini" class="form-control input-xs" style="text-align: center;" /></div>
                                    <label class="col-xs-1 control-label label-xs">Hasta:</label>  
                                    <div class="col-xs-3" ><input name="txt_fec_fin" type="text" id="txt_fec_fin" class="form-control input-xs" style="text-align: center;" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Saldo al Corte:</label>  
                                    <div class="col-xs-3" ><input name="saldo_ban" type="number" id="saldo_ban" value="0" class="form-control input-xs nospin" class="text ui-corner-all" style="text-align: right;" /></div>
                                </div>    
                             </fieldset>    
                        </div>
                        <div class="col-sm-6">
                             <fieldset class="exa-fieldset">
                                <legend><label class="Titulos2">Seleccione Banco</label></legend>
                                <div class="form-group">
                                     <label class="col-xs-2 control-label label-xs">Banco:</label>
                                     <div class="col-xs-7" >
                                         <select name="bancos" id="bancos"  class="form-control input-xs"   >
<?php $rs_bancos = $obBD_con1->getArrayConsulta(377,$Ses_Emp_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0){ 
        foreach ($rs_bancos as $row){  
             ?><option value="<?php echo $row['Ban_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option><?php
        }
    } ?>
                                        </select>
                                     </div>
                                     <div class="col-xs-3" >
                                        <button type="button" class="btn btn-success btn-sm" title="Generar Concilición Bancaria" onclick="Search()"> <i class="glyphicon glyphicon-eye-open"></i> <span> Visualizar</span> </button>
                                     </div>   
                                </div>
                            </fieldset>   
                        </div>
                    </form>
                    <div class="col-sm-12">  
                                <div class="ui-widget ui-widget-content ui-corner-all">
                                    <div class="ui-widget-header ui-corner-top  " style="font-size: 16px;"><center>Conciliaci&oacute;n Bancaria</center></div>
                                    <div id="Conciliacion" style="padding:10px;" >
                                        <p data-name="range_fec" class="form-control-static input-xs" style="font-size: 13px;text-align: center;">&nbsp;</p><hr/>
                                        <div class="form-horizontal normal">
                                            <div class="form-group">
                                                <label class="col-xs-2 control-label label-xs">Cuenta Contable:</label>  
                                                <div class="col-xs-2" ><span class="form-control input-xs" data-name="Pld_Cdc"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Banco:</label>  
                                                <div class="col-xs-4" ><span class="form-control input-xs" data-name="Pld_Des"></span></div>
                                                <label class="col-xs-1 control-label label-xs">Cuenta:</label>  
                                                <div class="col-xs-2" ><span class="form-control input-xs" data-name="Ban_Cue"></span></div>
                                            </div>                                     
                                        </div><hr/>
                                        <div align="right">SALDO EN LIBRO DE LA EMPRESA: <input data-name="total_mayor" type="text" class="ui-widget-content ui-corner-all saldos" readonly /> </div><hr/>
                                        <div style="min-height: 270px"> 
                                            <table id="list"></table><div id="listPager"></div>
                                        </div><hr/>
                                        <div align="right">EL SALDO EN EL ESTADO DE CUENTA BANCARIO DEBE SER: <input data-name="total_conci" type="text" class="ui-widget-content ui-corner-all saldos" readonly /> </div><hr/>
                                    </div>
                                </div>
                    </div>
                    <div class="col-sm-12" style="padding-top: 10px;" >
                        <button onclick="$('#Exportar').printElement({pageTitle:'<?Php echo $Ses_Sys_Nom; ?>',leaveOpen: true})" title="Imprimir Reporte" type="button" class="btn btn-primary start" > <i class="glyphicon glyphicon-print"></i> <span> Imprimir</span></button>
                        <button onclick="$.downloadFile($.exportarExcelBlob($('#Exportar').html(),'Reporte Conciliacion'),'Reporte Conciliacion-'+$.getDate()+'.xls');/*downloadFile(exportarExcelBlob('Exportar','Reporte Conciliacion'),'Reporte Conciliacion-'+getDate()+'.xls')*/" title="Descargar archivo de Excel" class="btn btn-primary start" > <i class="glyphicon glyphicon-download" ></i> <span> Excel</span></button>
                      <!--<button type="button" class="btn btn-primary start" onclick="exportarExcel('Exportar')"> <i class="icon-share icon-white"></i> <span>Excel</span></button>-->              
                    </div>
                </div>
            </div>
        </div>  
    </div>    
    <script type="text/javascript">     //                                   
       function Search(){
           $.saveDataJson("<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",$("#form1").getData('gridAjax'),
                function(response){                                          
                       $('#Conciliacion').setData(response['header'],null,'name');
                       $("#list").setRows(response['grid']['rows']);
                       $("#Exportar").html(response['report']); return false;
                }
            ); $("#form1").effect("highlight",{},500);
       } 
       $(document).ready(function () {                        
           $.createDateRange('#txt_fec_ini','#txt_fec_fin');
           var jgrid=$("#list");
           jgrid.createGrid({               
               colModel: [
                   { label: 'Cód.Int.', name: 'id', key: true, width: 15,align:"center", hidden:true },
                   { label: 'Grupo', width: 1,name: 'Grupo', hidden:true},
                   { label: 'Fecha Comp.', name: 'Com_Fec', width: 40,align:"center" },   
                   { label: 'No. Che.', name: 'Che_Num', width: 40},  
                   { label: 'Concepto', name: 'Asi_Glo', width: 90},                                                                  
                   { label: 'Beneficiario', name: 'proveedor', width: 150 },                      
				   //{ label: 'Fec.Emis.', name: 'Com_Fec', width: 40,align:"center"},
                   { label: 'Fec.Cheque', name: 'Che_Fec', width: 40,align:"center"},
                   { label: 'Importe', name: 'Che_Val', width: 70, align: 'right', formatter:'currency',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round' },
                   { label: 'Asiento', name: 'asiento', width: 40},
                      { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                           formatter:function (cellvalue, options, rowObject) {return  '<span class="btn btn-info btn-xs" title="Ver" type="button" onclick="$(\'#list\').viewGridRow(\''+rowObject.id+'\');"><i class="glyphicon glyphicon-info-sign"></i></span>';}
                      }
               ],                   
               groupingView: {
                   groupField: ["Grupo"],groupColumnShow: [false],
                   groupText: ["<div><span style='float:left;'> {1} Cheque(s)</span> <b>{0}</b>  <b style='position: absolute;right: 25px;'>Total: $ {Che_Val} <b></div>"],
                   groupOrder: ["asc"],groupSummary: [true],groupCollapse: false
               },grouping:true, height:220, pginput:false, pgbuttons:false, pgtext:null
           },true,'#listPager',{refresh: false}); 
       });  
    </script>
    <div id="Exportar" style="display: none;"></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>