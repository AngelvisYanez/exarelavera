<?php	
/**
* @abstract Permite realizar movimientos de inventario
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/inv_log_inventario.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Inv($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Inv;


$hoy = date("Y-m-d");
$mes = date("m");


if(isset($CatSelect)){
    $rs_tpaj= $obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$CatSelect, $obBD_conexion);
    $Cat_Cod=$CatSelect;
    echo "<option value=''>Todas</option>";
    foreach ($rs_tpaj as $row) 
        echo mb_convert_encoding("<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>", 'UTF-8', 'ISO-8859-1');        
    exit();
}

if(isset($productos)){
	try{
		if($Suc_Cod!="t"){
			$param="sucursal.Suc_Cod=".$Suc_Cod;
		}else{
			$param="empresas.Emp_Cod=".$Ses_Emp_Cod;
		}
		 if ($Cate_Cod != '' and $Sub_Cod == '') {
        	$cat = " AND categorias.Cat_Rec=$Cate_Cod ";
	    	}

	    	if ($Cate_Cod!='' and $Sub_Cod!=''){
	    	$cat = " AND item.Cat_Cod=$Sub_Cod ";
	    	}
		if ($Ubi_Cod != '') {
        	$ubi = " AND producto.Ubi_Cod=$Ubi_Cod ";
    		}
		$array=$obBD_con1->getArrayConsulta(34, $param.'*'.$cat.'*'.$ubi, $obBD_conexion);
		foreach ($array as $key => &$row) {				
			$Ite_Cod=$row['Pro_Cod'];
			$kardex1 = $obBD_con1->getArrayConsulta(32,$Ite_Cod, $obBD_conexion);
			if(count($kardex1)==1 && $kardex1[0]['Saldo']!==0 && $kardex1[0]['Stock']!=0){         
				$kardex1[0]['Promedio']=round(($kardex1[0]['Saldo']/$kardex1[0]['Stock']),6);
			}else{
				$kardex1[0]['Promedio']=0;$kardex1[0]['Saldo']=0;$kardex1[0]['Stock']=0;
			}
			list($ann, $mes, $dia) = preg_split('![/.-]!',$ini);
			$kardex1[0]['Kar_Det']='<b>Saldo al '.$dia.', de '.mes($mes, 1).', '.$ann.'</b>';
			$kardex2 = $obBD_con1->getArrayConsulta(33,$Ite_Cod, $obBD_conexion);
			if(count($kardex2)>0) $kardex=array_merge($kardex1,$kardex2);
			else $kardex=$kardex1;
			
			$row['Ent_Stk']=0;
			$row['Ent_Sal']=0;
			$row['Sal_Stk']=0;
			$row['Sal_Sal']=0;
			
			$x=COUNT($kardex);
			for($i=1;$i<$x;$i++){
				if($kardex[$i]['Kar_Sal']*1!=0){
					$kardex[$i]['Kar_Pre']=$kardex[$i-1]['Promedio'];
					$kardex[$i]['Kar_Ime']=$kardex[$i]['Kar_Pre']*$kardex[$i]['Kar_Sal'];
				}
				$kardex[$i]['Stock']=$kardex[($i-1)]['Stock']*1+$kardex[$i]['Kar_Can']*1-$kardex[$i]['Kar_Sal']*1;
				$kardex[$i]['Saldo']=$kardex[$i-1]['Saldo']*1+$kardex[$i]['Kar_Ims']*1-$kardex[$i]['Kar_Ime']*1;
				$kardex[$i]['Promedio']=($kardex[$i]['Stock']!=0?$kardex[$i]['Saldo']/$kardex[$i]['Stock']:$kardex[$i-1]['Promedio']);
				
				$row['Ent_Stk']+=$kardex[$i]['Kar_Can']*1;
				$row['Ent_Sal']+=$kardex[$i]['Kar_Ims']*1;
				$row['Sal_Stk']+=$kardex[$i]['Kar_Sal']*1;
				$row['Sal_Sal']+=$kardex[$i]['Kar_Ime']*1;
			}
			$row['Ini_Stk']=(string)(empty($kardex[0]['Stock'])?0.00:$kardex[0]['Stock']);
			$row['Ini_Prp']=(string)round((empty($kardex[0]['Promedio'])?0.00:$kardex[0]['Promedio']),8);
			$row['Ini_Sal']=(string)(empty($kardex[0]['Saldo'])?0.00:$kardex[0]['Saldo']); 
			
			$row['Ent_Prp']+=($row['Ent_Stk']!=0?$row['Ent_Sal']/$row['Ent_Stk']:null);
			$row['Sal_Prp']+=($row['Sal_Stk']!=0?$row['Sal_Sal']/$row['Sal_Stk']:null);
				
			$row['Kar_Stk']=(string)(empty($kardex[$x-1]['Stock'])?0.00:$kardex[$x-1]['Stock']);
			$row['Kar_Prp']=(string)round((empty($kardex[$x-1]['Promedio'])?0.00:$kardex[$x-1]['Promedio']),8);
			$row['Kar_Sal']=(string)(empty($kardex[$x-1]['Saldo'])?0.00:$kardex[$x-1]['Saldo']); 
		}
    }catch(Exception $e){ $responce=array(success=>false,message=>'No se logro obtener informaciÃ³n del Kardex!',error=>$e); }      
    $responce['rows'] = array_values($array);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}

if(isset($productos)){
    if($Suc_Cod!="t"){
			$param="Suc_Cod=".$Suc_Cod;
		}else{
			$param="Emp_Cod=".$Ses_Emp_Cod;
		}
	$array=$obBD_con1->getArrayConsulta(22, $param, $obBD_conexion);
    //var_dum
    foreach ($array as $key => &$row) {
        $row['Pro_Sal']=(string)round($row['Pro_Stk']*$row['Pro_Prp'],2);
        $kardexHist = $obBD_con1->getArrayConsulta(13,$ini.'*'.$fin.'*'.$row['Pro_Cod'], $obBD_conexion,true);
        $kardex=array_merge(array(0=>array()),$kardexHist); $x=COUNT($kardex);
        for($i=1;$i<$x;$i++){
            if($kardex[$i]['Kar_Sal']*1!=0){
                $kardex[$i]['Kar_Pre']=  empty($kardex[$i-1]['Promedio'])?0:$kardex[$i-1]['Promedio'];
                $kardex[$i]['Kar_Ime']= round($kardex[$i]['Kar_Pre']*$kardex[$i]['Kar_Sal'],2);
            }
            $kardex[$i]['Stock']=$kardex[($i-1)]['Stock']*1+$kardex[$i]['Kar_Can']*1-$kardex[$i]['Kar_Sal'];
            $kardex[$i]['Saldo']=round($kardex[$i-1]['Saldo']*1+$kardex[$i]['Kar_Ims']*1-$kardex[$i]['Kar_Ime'],2);            
            $kardex[$i]['Promedio']=($kardex[$i]['Stock']!=0?round($kardex[$i]['Saldo']/$kardex[$i]['Stock'],2):$kardex[$i-1]['Promedio']);
        }        
        $row['Kar_Stk']=(string)(empty($kardex[$x-1]['Stock'])?0.00:$kardex[$x-1]['Stock']);
        $row['Kar_Prp']=(string)round((empty($kardex[$x-1]['Promedio'])?0.00:$kardex[$x-1]['Promedio']),8);
        $row['Kar_Sal']=(string)(empty($kardex[$x-1]['Saldo'])?0.00:$kardex[$x-1]['Saldo']); 
        // var_dump($row['Kar_Stk'],$row['Pro_Stk']);
        // var_dump($row['Pro_Cod'],round($row['Kar_Prp'],8)==round($row['Pro_Prp'],8)); 
        // $row['Dif']=round($row['Pro_Stk'],2)-round($row['Stk_Can'],2);
		// $row['Dif2']=round($row['Kar_Stk'],2)-round($row['Stk_Can'],2);
        //if(round($row['Kar_Stk'],2)==round($row['Stk_Can'],2))
        //   unset($array[$key]);
    }    
    $responce['rows'] = array_values($array);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
				<TITLE><?Php echo "Toma Fisica de Producto [EXA]"; ?></TITLE>
                <meta charset="UTF-8">
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Listado para toma Fisica de Inventario</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">                   
                    <div class="col-xs-12">
						<form id="formFiltros" class="form-horizontal normal">
							<fieldset class="exa-fieldset">                           
								<legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
								<div class="row">
									<div class="col-xs-6">
										<div class="form-group">
											
											<?php
												$sucursales = $obBD_con1->getArrayConsulta(25,$Ses_Emp_Cod, $obBD_conexion);
											?>
											<label class="col-sm-3 control-label label-xs ">Sucursal:</label>
											<div class="col-sm-6"> 
											<select name="Suc_Cod" class="form-control input-xs" id="Suc_Cod">
												<option value="t"><?php echo "<< TODAS >>";?></option>
												<?php foreach($sucursales as $datos){?>
														<option value="<?php echo $datos['Suc_Cod'];?>"><?php echo $datos['Suc_Des'];?></option>
												<?php }?>
											</select>
											</div>
										</div>
									</div>	
									<div class="col-xs-6">	
									

										  <div class="form-group">
											<label class="col-sm-2 control-label label-xs " for="Cate_Cod">CategorÃ­a:</label>
											<div class="col-sm-7">
												<?php $row_rs_categ = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod, $obBD_conexion); ?>
												<select name="Cate_Cod" id="Cate_Cod" class="form-control input-xs" data-placeholder="Todas">
													<option value="">Todas</option>
													<?Php foreach ($row_rs_categ as $row) { ?><option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo /* strtoupper($row['Par_Cat_Des']).' ï¿½ '. */$row['Cat_Des']; ?></option><?Php } ?>
												</select>
											</div>


											<div class="col-xs-2">
											  <div class=""><button type="button"  onclick="kardexGrid.setGridParam({postData:$('#formFiltros').getData('productos')}); kardexGrid.trigger('reloadGrid', [{page:1}]) " class="btn btn-sm btn-success" title="Ejecutar BÃºsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
											</div>
										 </div>

										 <div class="form-group">
											<label class="col-sm-2 control-label label-xs " for="Sub_Cod">SubcategorÃ­a:</label>
											<div class="col-sm-7">
												<select name="Sub_Cod" id="Sub_Cod" class="form-control input-xs" data-placeholder="Todas">
													<option value=''>Todas</option>
												</select>
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-2 control-label label-xs " for="Ubi_Cod">UbicaciÃ³n:</label>
											<div class="col-sm-7">
												<?php $rs_ubicacion = $obBD_con1->getArrayConsulta(50, $Ses_Emp_Cod, $obBD_conexion); ?>
												<select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-xs">
													<option value="">Todas</option>
													<?Php foreach ($rs_ubicacion as $row) {?><option value="<?Php echo $row['Ubi_Cod']; ?>"><?Php echo $row['Ubi_Des']; ?></option><?Php }?>
												</select>
											</div>
										</div>
										  
									</div>
									
								</div>
							</fieldset> 
						</form>
					 </div>       
                    <div class="col-xs-12" style="min-height: 450px;">                       
                        <table id="prods"></table>
                        <div id="prodsPager"></div>
                      
                        <script>

                            var kardexGrid=$("#prods");
                            $(document).ready(function () {
                                $.createDialog('#successDialog',150,550);
								$.createDateRange('#ini','#fin');
								$('#ini').val('2000-01-01'); //$('#ini').datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
								$('#fin').datepicker("setDate", new Date()); 
                                kardexGrid.createGrid({
                                    url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                                    mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
                                    postData: $('#formFiltros').getData('productos'),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,footerRow:true,
                                    caption:'Listado de Productos',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'CÃ³d.Int.', name: 'Pro_Cod', key: true, hidden:false,viewable:true, width: 25,align:'center' },                                        
                                        { label: 'Nombre del Producto',name: 'Ite_Lar', width: 80, formatter:function(c,o,r){ return r.Ite_Lar+(r.Ite_Lar!==r.Pro_Obs&&$.vv(r.Pro_Obs)?' '+r.Pro_Obs:''); } },
					{ label: 'Categoria',name: 'Cat_Des', width: 40,classes:'columnHighlight1',align:'center', hidedlg:'false'},       
					{ label: 'Marca',name: 'Mar_Des', width: 40,classes:'columnHighlight1',align:'center', hidedlg:'false'},                                   
                                        /*{ label: 'Stock',name: 'Pro_Stk', width: 30,classes:'columnHighlight3',align:'center',formatter:'number'},
                                        { label: 'P. Promedio',name: 'Pro_Prp', width: 40,classes:'columnHighlight3',align:'right'},
										{ label: 'Saldo',name: 'Pro_Sal', width: 40,classes:'columnHighlight3',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},
										{ label: 'Stock',name: 'Stk_Can', width: 30,classes:'columnHighlight3',align:'center',formatter:'number'},
                                        { label: 'P. Promedio',name: 'Cat_Des', width: 40,classes:'columnHighlight3',align:'right'},
                                        { label: 'Saldo',name: 'Stk_Sal', width: 40,classes:'columnHighlight3',align:'right',formatter:'currency', formatoptions: {prefix:'$', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum"},*/
										
										
                                        { label: 'Stock',name: 'Kar_Stk', width: 30,classes:'columnHighlight1',align:'center',formatter:'number'},
                                        { label: 'C. Fisica',name: '', width: 40,classes:'columnHighlight1',align:'right', hidedlg:'false'},
                                        { label: 'Diferencia',name: '', width: 40,classes:'columnHighlight1',align:'right', hidedlg:'false'}, 
                                        { label: 'Observacion',name: '', width: 40,classes:'columnHighlight1',align:'right', hidedlg:'false'},   
                                    ],     
                                    footerrow: true, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#prodsPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null,
									loadComplete:function(){
										//kardexGrid.startGridEdit();
										kardexGrid.setGridSummary(['Ini_Sal','Ent_Sal','Sal_Sal','Kar_Sal'],{Ite_Lar:'<div style="text-align:right;">TOTALES:</div>'});
									}
                                },true,"#prodsPager").gridButtonsAdd([
									{buttonicon:'print',caption:'Imprimir',onClickButton:function(){ printR('#prods'); }},
									{buttonicon:'download-alt',caption:'Descargar',onClickButton:function(){ exportR('#prods'); }}
								]); ; 
                          

                                
                      
                            });

                            $('#Cate_Cod').change(function(){
							 var cod=$('#Cate_Cod').val();
					        $('#Sub_Cod').html('');
					        $.get("",{CatSelect:cod}, function( response ) {
					            $('#Sub_Cod').html(response);
					            //$('#proGrid').trigger('reloadGrid', [{ page: 1 }]);
					           // Grid.clearGrid();
					        })  
					   		});
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
    <script>

							
        function printR(grid) {
			$('#tablaReporte').html($(grid).jqGrid('exportGridInnerHTML',{generated:false, caption:false, footer:true, bodyBorder:false}));
			$('#titleReporte').html($(grid).getCaption());
			$('#formatoReporte').printElement({pageTitle:"<?Php echo $Ses_Sys_Nom; ?>",printMode:'popup',overrideElementCSS:[{ href:'../../mascaras/model1/estilos/print.css',media:'print'}]});                
		}
		function exportR(grid) {
			var temp=$('<div>'+$('#formatoExportar').html()+'</div>');
			temp.append($(grid).jqGrid('exportGridHTML',{generated:false,caption:true,bodyBorder:false,footer:true,sepEnd:true}));                
			$.downloadFile($.exportarExcelBlob(temp.html(),'Digitacion'),'digitacion_'+$.getDate()+'.xls');    
		}
    </script>
	<div id="formatoReporte" style="display: none;">
	  <div style="width: 1030px;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'TOMA FISICA DE INVENTARIO', '<span id="titleReporte"></span>',$obBD_conexion); ?>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style=""></table>            
            <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
	  </div>
        </div>  
        <div id="formatoExportar" style="width: 700px;display: none;">
            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'TOMA FISICA DE INVENTARIO', '<span class="title_grid"></span>',$obBD_conexion,false,6); ?>
        </div>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
</BODY>
</HTML>