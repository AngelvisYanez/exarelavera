<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creacion  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require('../LOGICA/con_log_costos.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Cos($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Cos;
//$obBD_con1->debug(true);

$hoy = date("Y-m-d");
$mes = date("m");

/* buscar cuentas contables */
if(isset($cuenAjax)){ 
    $data=$_GET ; $data['Emp_Cod']=$Ses_Emp_Cod;  
    if($Index=='g_'&&$es_grupo=='S')
        $responce=$obBD_con1->getPageGridJson(6, $data, $obBD_conexion);
    else
        $responce=$obBD_con1->getPageGridJson(2, $data, $obBD_conexion);
}
if(isset($getCtaAction)){ 
    $txt_fec_fin=$anio.'-'.$month.'-'.ultimoDia($mes,$anio);
    $obBD_con1->echoLog($txt_fec_fin);
    if($cta['Pld_Tip']!='Grupo')
        $pld=sql_conjunction($cta['Pld_Cod'], 'asientos.Pld_Cod=');
    else{
        $lista=array();
        $rs_cuentas = $obBD_con1->getArrayConsulta(7, $cta['Pld_Cod'],$obBD_conexion);
        foreach ($rs_cuentas as $row){ 
            array_push($lista,$row['Pld_Cod']);
        }
        $pld=sql_conjunction($lista, 'asientos.Pld_Cod=');
    }    
    $rs_mayor_banco = $obBD_con1->getArrayConsulta(3, $pld.'*'.$txt_fec_fin,$obBD_conexion);
    $total_mayor=0;
    if (count($rs_mayor_banco) > 0)
        foreach ($rs_mayor_banco as $row){ // Calculo el Total del Libro Mayor
            if($row['Asi_Deh']=='D') $total_mayor+=$row['Total']; else $total_mayor-=$row['Total'];
        }
    $cta['Tot']=abs($total_mayor);    
    switch($cta['start']){       
        case 'v_':
            $cantidad = $obBD_con1->getRowConsulta(5, $cta['Pld_Cod'].'*'.'V'.'*'.$txt_fec_fin,$obBD_conexion);
            $cta['Uni']=$cantidad['Can'];            
            break;
        case 'c_':
            $cantidad = $obBD_con1->getRowConsulta(4, $cta['Pld_Cod'].'*'.'C'.'*'.$txt_fec_fin,$obBD_conexion);
            $cta['Uni']=$cantidad['Can'];            
            break;        
    }
    $r=array('success'=>true, 'cta'=>$cta);
    $obBD_con1->echoJson($r);
}
//$obBD_con1->echoLog('ver'); 
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
    <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.js"></script>
    <style>
        #costos tr[jqfootlevel="1"].jqfoot{font-size:11px;}
        #costos tr[jqfootlevel="0"].jqfoot{background: antiquewhite;} 
        #costos tr.jqfoot td.borderTop{ border-top-color:#789; border-top-style: solid; border-top-width: 1px; }
        #costos tr[jqfootlevel="0"].jqfoot td.borderTop{ border-top-color:#111; border-bottom-color:#111 !important; border-bottom-style: double; border-bottom-width: 4px; }
    </style>
</HEAD>
<BODY> 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Costos de Producción</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="">
                <div class="row">
                    <div class="col-sm-12">  
                        
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Búsqueda de Centros de Consumo</legend> <!-- Form Name -->
                           <form id="searchForm" class="form-horizontal normal" role="form" action="javascript:gridComp.Search('#searchForm','consAjax');">
                               <div class="form-group">
                                    <label class="col-xs-2 control-label label-sm required">Seleccione Periodo:</label> 
                                    <div class="col-xs-2">
                                        <?php $row_rs_periodos = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion); ?>
                                        <select name="perio_cont" id="perio_cont" onchange="setPeriodo()"  class="form-control input-sm">
                                            <!--<option value="">Seleccione...</option>-->
    <?php
      if (count($row_rs_periodos) > 0){ $periodo = current($row_rs_periodos);
          foreach ($row_rs_periodos as $row){ 
              echo "<option value='$row[Pec_Cod]' data--pec_-cod='$row[Pec_Cod]' data--pla_-cod='$row[Pla_Cod]' data--pec_-fei='$row[Pec_Fei]' data--pec_-fef='$row[Pec_Fef]'  data-periodo='$row[Periodo]'>$row[Periodo]</option>";
          }    
      } ?>
                                        </select>   
                                    </div>                                
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs required">Desde:</label>  
                                    <div class="col-xs-2"><span class="form-control input-xs">Enero</span></div>
                                    <label class="col-xs-1 control-label label-xs required">Hasta:</label>  
                                    <div class="col-xs-2">
                                      <div class="input-group input-group-xs">
                                        <input id="Month" name="Month" type="hidden">
                                        <span id="Mes" class="form-control"></span>
                                        <span class="input-group-btn">
                                            <button id="MonthButton" onclick="$('#Month').monthpicker('show','#Mes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
                                        </span>
                                      </div>
                                    </div>
                                    <div class="col-xs-2">
                                        <button type="button" onclick="data_temp=$.cloneData(data); setRowGrid();" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-refresh"></i> Actualizar</button>
                                    </div>    
                                </div> 
                           </form>                           
                        </fieldset>
                        <div>
                            <table id="costos"></table>
                            <div id="costosPager"></div>
                        </div>
                        <div style="padding-top: 8px;">
                            <button type="button" onclick="imprimir()" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-print"></i> Imprimir</button>
                            <button type="button" onclick="exportar()" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-download"></i> Exportar</button>
                        </div>
                    </div>
                </div>    
              
            </div>   
        </div>
    </div>
    <script type="text/javascript">
      var cta_id,grid=$("#costos"),data=[          
          {root:'COSTOS DE PRODUCCIÓN',type:'1.COMPRAS',start:'c_',id:'c_1',int:1,Pld_Cod:1,Pld_Des:'INVENTARIO INICIAL DE MATERIA PRIMA',Uni:0,Tot:0,edit_uni:'S',change:'S'},          
          {root:'COSTOS DE PRODUCCIÓN',type:'2.PRODUCCIÓN',start:'p_',id:'p_1',int:1,Pld_Cod:1,Pld_Des:'(+)INVENTARIO INICIAL DE PRODUCCION EN PROCESO',Uni:0,Tot:0,edit_uni:'S',change:'S'},
          {root:'COSTOS DE PRODUCCIÓN',type:'2.PRODUCCIÓN',start:'p_',id:'p_99',int:0,Pld_Cod:1,Pld_Des:'(-)INVENTARIO FINAL DE PRODUCCION EN PROCESO',Uni:0,Tot:0,edit_uni:'S',edit_tot:'S'}          
      ],data_temp=$.cloneData(data);
      function setPeriodo(){
          var pec=$('#perio_cont').find('option:selected').data();
          $('input[name=Pec_Cod]').val(pec['Pec_Cod']);
          $('input[name=Periodo]').val(pec['periodo']);
          $('#cuenDialog').getDialogGrid().clearGrid();
      }
      function updateTotales(){
          var totales={'v_':{Pld_Des:'<div/>',Uni:0,Tot:0},'c_':{Pld_Des:'<div style="text-align:right;">TOTAL COMPRAS:</div>',Uni:0,Tot:0},'p_':{Pld_Des:'<div style="text-align:right;">TOTAL PRODUCCION EN PROCESO:</div>',Uni:0,Tot:0},'g_':{Pld_Des:'<div style="text-align:right;">TOTAL COSTOS DE PRODUCCION:</div>',Uni:0,Tot:0}};
          $.each(data_temp,function(i,v){              
              if(!(v['Pld_Des'].trim()).indexOf('(-)')){
                totales[v['start']]['Uni']-=(v['Uni']*1);
                totales[v['start']]['Tot']-=(v['Tot']*1);
              }else{
                totales[v['start']]['Uni']+=(v['Uni']*1);
                totales[v['start']]['Tot']+=(v['Tot']*1);
              }
          });
          totales['p_']['Uni']+=totales['c_']['Uni'];
          totales['p_']['Tot']+=totales['c_']['Tot'];
          totales['g_']['Uni']=totales['p_']['Uni'];
          totales['g_']['Tot']+=totales['p_']['Tot'];
          $.each(data_temp,function(i,v){
              if(v['start']==='g_') data_temp[i]['Uni']=totales['p_']['Uni'];
              if(v['Uni']*1>0&&v['Tot']*1>0) data_temp[i]['Prom']=v['Tot']/v['Uni'];
          });
          var ven_tot=grid.find('tr[id^=v_]').next('tr[jqfootlevel=1]').next('tr[jqfootlevel=0]');
          var gas_tot=grid.find('tr[id^=g_]').next('tr[jqfootlevel=1]').next('tr[jqfootlevel=0]');
          if(gas_tot.length===0) gas_tot=grid.find('tr[id^=p_]').next('tr[jqfootlevel=1]').next('tr[jqfootlevel=0]');
          $.each(totales,function(k,v){
              var tr=grid.find('tr[id^='+k+']').next('tr[jqfootlevel=1]');
              $.each(v,function(k2,d){
                  tr.find('td[aria-describedby$=_'+k2+']').html(isNaN(d)?d:$.numFormat(d,k2==='Uni'?'number':'currency'));
                  if(k==='v_') ven_tot.find('td[aria-describedby$=_'+k2+']').html(isNaN(d)?'<div style="text-align:right;">TOTAL VENTAS:</div>':$.numFormat(d,k2==='Uni'?'number':'currency'));
                  if(k==='g_'&&k2!=='Uni') gas_tot.find('td[aria-describedby$=_'+k2+']').html(isNaN(d)?'<div style="text-align:right;">TOTAL COSTOS + GASTOS:</div>':$.numFormat(d));
              });
          });
          var ganancia=totales['v_']['Tot']>totales['g_']['Tot'], estado=ganancia?'UTILIDAD DEL EJERCICIO':'PERDIDA DEL EJERCICIO';          
          grid.setGridSummary(['remove'],{ Pld_Des:'<div style="text-align:right;'+(ganancia?'color:green;':'color:red;')+'">'+estado+'==></div>', Tot:(Math.abs(totales['v_']['Tot']-totales['g_']['Tot'])) });
          console.log(totales);
      }
      function startEdit(){
          var aux=$.jgrid.inlineEdit;
          $.jgrid.inlineEdit={focusField:false};
          grid.jqGrid('editRow','c_1');
          grid.jqGrid('editRow','p_1');
          grid.jqGrid('editRow','p_99');
          $.jgrid.inlineEdit=aux;
      }
      function setRowGrid(){
          grid.jqGrid('setCaption', 'VENTA Y COSTO DE PRODUCCIÓN - Periódo '+$('#perio_cont option:selected').data('periodo')+' Desde ENERO Hasta '+$('#Mes').text().toUpperCase());          
          grid.setRows(data_temp);                 
          startEdit();
          updateTotales();
          grid.find('tr[id^=v_]').next('tr[jqfootlevel=1]').css({border:'none',display:'none',height:'0px'}).find('td').html('');
      }
      function setCta(id){
          cta_id=id;
          var fila=$.arrayGetItem(data_temp,'id',cta_id);
          $('#Index').val(fila['start']);
          $('.grupos').hide(); 
          $('#cuenDialog').dialog('open');
      }
      function SelectCta(cta){          
          var require={'v_':{root:'PRODUCTOS TERMINADOS',type:'1.VENTAS'}, 'c_':{root:'COSTOS DE PRODUCCIÓN',type:'1.COMPRAS'}, 'g_':{root:'COSTOS DE PRODUCCIÓN',type:'3.COSTOS/GASTOS'} };
          var index=$('#Index').val(), datos=$.arrayGetWhere(data_temp,function(obj){ return !obj.id.indexOf(index); }), next=$.arrayMaxVal(datos,'int')+1;
          $.extend(cta,require[index],{start:index,id:index+next,int:next,remove:true});
          $.getDataJson('',{cta:cta, anio:$('#perio_cont option:selected').data('periodo'), month:$('#Month').monthpicker('getMonth'), getCtaAction:true},function(r){
                r['cta']['Pld_Des']=r['cta']['Pld_Des'].toUpperCase();
                if(!$.varValid(cta_id)){
                    data_temp.push(r['cta']);                    
                }else{
                    var fila=$.arrayGetItem(data_temp,'id',cta_id);
                    $.extend(fila,{Tot:r['cta']['Tot'],Pld_Des:r['cta']['Pld_Des'],Pld_Cdc:r['cta']['Pld_Cdc'],Pld_Cod:r['cta']['Pld_Cod']});                    
                    cta_id=undefined;
                }
                setRowGrid();
          });
          $('#cuenDialog').dialog('close');
      }
      function deleteCta(id){
          $.arraySpliceWhere(data_temp,'id',id,false);
          setRowGrid();
      }
      

        $(function() { 
            grid.createGrid({
              caption:"VENTA Y COSTO DE PRODUCCIÓN", selectGridRows:false, height: 'auto', footerrow:true, sortname: "id", sortorder: "asc",
              colModel:[
                  { label: 'Cód.Int.', name: 'id', key: true, width: 15,align:"center", hidden:true },  
                  { label: 'Cód.Int.', name: 'root', width: 15, align:"center", hidden:true },  
                  { label: 'Cód.Int.', name: 'type', width: 15, align:"center", hidden:true }, 
                  { label: 'Edition.', name: 'edit_uni', width: 15, hidden:true },  
                  { label: 'Edition.', name: 'edit_tot', width: 15, hidden:true },
                  { label: 'Cód.Int.', name: 'Pld_Cod', hidden:true },                  
                  { label: 'Codigo', name: 'Pld_Cdc', width: 20 },
                  { label: '&nbsp;', name: 'change', width: 10, align:"center", formatter:'gridButton', formatoptions:{action:setCta, icon:'check', data:function(o){ return o.id; }, /*caseFalse:function(o){ return $.varValid(o.Pld_Cdc)?o.Pld_Cdc:''; },*/ conditional:function(o){ return $.toBool(o.change)===true; } }  } ,
                  { label: 'Cuenta', name: 'Pld_Des', width: 90  },
                  { label: 'Unidades', name: 'Uni', width: 30, align:"right", editable:true, classes:'borderTop', formatter:'number', editoptions: { dataInit: function(el) { grid.createInputDiario(el,"edit_uni"); } } },
                  { label: 'V. Total', name: 'Tot', width: 30, align:"right", editable:true, classes:'borderTop', formatter:'currency', editoptions: { dataInit: function(el) { grid.createInputDiario(el,"edit_tot"); } } },
                  { label: 'V. Unitario Prom.', name: 'Prom', align:"right", width: 30, classes:'borderTop', formatter:'currency', formatoptions:{decimalPlaces: 6} },
                  { label: '&nbsp;', name: 'remove', width: 10, align:"center", formatter:'gridButton', formatoptions:{action:deleteCta, title:'Quitar', type:'danger', icon:'remove', data:function(o){ return o.id; }, conditional:function(o){ return $.toBool(o.remove)===true; } }  }  
              ],             
              grouping: true,
              groupingView : {
                  groupCollapse : false,
                  groupText : ['<b>{0}</b>'],
                  groupField : ['root', 'type'],
                  groupColumnShow : [false, false],
                  groupOrder: ['desc', 'asc'],
                  groupSummary : [true, true]
              }
          },true,'#costosPager',{view:false}).gridButtonsAdd([
              {buttonicon:'plus', caption:'Agregar Ventas', onClickButton:function(){ $('#Index').val('v_'); $('.grupos').hide(); $('#cuenDialog').dialog('open'); }},
              {buttonicon:'plus', caption:'Agregar Compras', onClickButton:function(){ $('#Index').val('c_'); $('.grupos').hide(); $('#cuenDialog').dialog('open'); }},
              {buttonicon:'plus', caption:'Agregar Costos/Gastos', onClickButton:function(){ $('#Index').val('g_'); $('.grupos').show(); $('#cuenDialog').dialog('open'); }}
          ]);     
          $('#Month').attr('data-monthplacer','#Mes').createMonthPicker({showYear:false, prepend:'Seleccione Mes',openOnFocus:false},function(){}).monthpicker('setMonthActive',3);;
          setPeriodo();
          //setRowGrid();
      });  
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <script>
        $.createSearchDialog('cuenDialog',[                   
            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
            { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
            { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:SelectCta} }
        ],null,null,null,null,{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
            .find('.form-group-options').append('<div class="col-xs-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" /><input name="Pec_Cod" type="hidden" /><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>')
            .next('.form-group-search').append('<div class="col-xs-3 grupos"><div class="checkbox check-big input-sm"><label><input id="es_grupo" name="es_grupo" type="checkbox" value="S" offval="N" />Por Grupo</label></div></div>');; 
        $.fn.createInputDiario = function(element,editar){
            var jgrid=this, rowId=$(element).closest('tr.jqgrow').attr('id'), tip=jgrid.jqGrid('getCell',rowId,editar); var e=editar.split("_");
            $(element).parent().removeAttr("title"); 
            if(tip==='S'){ 
                $(element).on('change', function(){ var fila=$.arrayGetItem(data_temp,'id',rowId); fila[e[1][0].toUpperCase()+e[1].slice(1)]=$(this).val()*1; if(fila['Uni']>0&&fila['Tot']>0) fila['Prom']=fila['Tot']/fila['Uni']; else fila['Prom']=0; grid.changeRow(rowId,fila); updateTotales();  }); 
                $(element).attr('onkeypress','return  validar_decimal(event)');
                if(parseFloat($(element).val())===0) $(element).val("");
                $(element).css('text-align', 'right');
            }else{ $(element).parent().html($(element).val()); };     
        };  
        function imprimir(){ grid.stopGridEdit(); $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML',{footer:true,generated:false,removeHiddens:true,removeCols:[1,6]})); $('#imprimir').printElement();  startEdit(); }
        function exportar(){ grid.stopGridEdit(); $('#tablaExporta').html(grid.jqGrid('exportGridInnerHTML',{footer:true,bodyBorder:false,removeHiddens:true,removeCols:[1,6]})); $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'Costos'), 'costos_' + $.getDate() + '.xls'); startEdit(); }
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <div id="imprimir" style="display: none;">
        <div style="width: 1030px;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE COSTOS', '<span class="subtitle"></span>', $obBD_conexion) ?>
        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>
        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
        </div>
    </div>
    <div id="exportar" style="display: none;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE COSTOS', '<span class="subtitle"></span>', $obBD_conexion, false, 5) ?>
        <table id="tablaExporta" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
    </div>
</BODY>
</HTML>