<?php
/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_producto_1.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
 * Creaci�n del Objeto para consultas
 */
$obBD_con1 = new Class_Log_Datos_Pro;
/**
 * Evita el reenvio
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($CatSelect)){
    $rs_tpaj= $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod.'*'.$CatSelect, $obBD_conexion);
    $Cat_Cod=$CatSelect;
    echo "<option value=''>Todas</option>";
    foreach ($rs_tpaj as $row) 
        echo utf8_encode("<option value='$row[Cat_Cod]'>$row[Cat_Des]</option>");        
    exit();
}

if (isset($prodAjax)) {
    $data = $_GET;
    //$obBD_con1->echoLog($data);
    $data['Suc_Cod'] = $Ses_Suc_Cod;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    if(!isset($data['Filtros'])) $data['Filtros']='';
//    $contar = $obBD_con1->getRowConsulta(18, $data, $obBD_conexion);
//    $data["limits"]=$pagination['limits'];
    //if($contar['total']>0)
    $data['Order'] = " ORDER BY " . ($grupo == 'clear' ? 'Ite_Lar' : $grupo);
    if ($letra == 'TODOS') {
        //$b = strtoupper($txt_busqueda);
        //$data['Filtros'] = $data['Filtros'] . " (Ite_Lar LIKE '%$b%' OR Ite_Cor LIKE '%$b%' OR Cat_Des LIKE '%$b%')";
        $search="";
        $array=explode(" ",strtoupper($txt_busqueda));
        foreach($array as $ar){
            if(!empty($ar) && $ar!='') $search.=(($search!=''?" AND ":"")."CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE '%$ar%'");
        }
        if($search=='') $search="1=1";

        $data['Filtros'] = $data['Filtros'] . $search;
    } else {
        $data['Filtros'] = "Ite_Lar LIKE '$letra%' ";
    }
    if ($Ubi_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND producto.Ubi_Cod=$Ubi_Cod ";
    }
    if ($Lin_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND producto.Lin_Cod=$Lin_Cod ";
    } //else{
    //$data['Filtros']=$data['Filtros']." AND producto.Lin_Cod IS NULL ";
    // }
    if ($Cate_Cod != '' and $Sub_Cod == '') {
        $data['Filtros'] = $data['Filtros'] . " AND categorias.Cat_Cod=$Cate_Cod ";
    }

    if ($Cate_Cod!='' and $Sub_Cod!=''){
    	$data['Filtros'] = $data['Filtros'] . " AND item.Cat_Cod=$Sub_Cod ";
    }

    $iva = $obBD_con1->getRowConsulta(29, $hoy, $obBD_conexion/*, true*/);
    $data['Iva_Por']=$iva['Iva_Por'];
    //$obBD_con1->echoLog($iva);
    $datos = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion/*, true*/);
    //$obBD_con1->echoLog($datos);
    /* foreach($datos as $dat){
        //$obBD_con1->echoLog($dat);
        //$valAct = $dat['Pro_Uni'];
        $dat['Pro_Uni2'] = round($dat['Pro_Uni'], 3, PHP_ROUND_HALF_EVEN);
        $obBD_con1->echoLog($dat);
    } */
    //$datos['Pro_Uni'] = round($datos['Pro_Uni'], 2, PHP_ROUND_HALF_EVEN);
    //$obBD_con1->echoLog($datos);
    $pagination = pages(count($datos), $page, $rows);
    $responce= $pagination['data'];
    $responce['rows'] = $datos;
    $responce['success'] = true;
    utf8_encode_deep($responce['rows']); echo json_encode($responce);exit();
}

if(isset($bajaProd)|| isset($altaProd)){
    $obBD_con1->debug(true);
    $data=$_POST;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    //$Pro_Est = 'I';
    try{
        if($bajaProd){
            $data['Pro_Est']='I';
            $obBD_con1->operacionobBD(27,$data,$obBD_conexion);
        }
        if($altaProd){
            $data['Pro_Est']='A';
            $obBD_con1->operacionobBD(27,$data,$obBD_conexion);
        }

      $response['success']=true;
      $response['message']='Producto actualizado con &Eacutexito ';
   }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp);  }
   $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
   if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
   $obBD_con1->echoJson($resp);
}

if(isset($prdSearch)){
    //$obBD_con1->echoLog('ESTAMOS EN LA BUSQUEDA DE PRODUCTO');
    $respta=$_POST;
    //$obBD_con1->echoLog($respta);
    $resp=array(
        'success'=>true,
        'producto'=>$obBD_con1->getRowConsulta(28,$Pro_Cod ,$obBD_conexion),
    );
    $obBD_con1->echoJson($resp);
    $obBD_con1->echoLog($resp);
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Producto Consultar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <style>
            .pagination>li>a, .pagination>li>span {padding: 4px 2px;}
            .pagination {/*display: block;*/margin:0;padding: 0;}
            .chosen-default span,.chosen-single span{color:#555;}
            .chosen-single span{padding-left: 5px;}
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consulta y Baja de Productos</h3></div>

            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <form id="formProduct" class="form-horizontal normal"  action="javascript:"  >
                    <div class="row">
                        <div class="col-sm-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
	<!-- Text input-->
	<div class="form-group">
		<label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
		<div class="col-xs-10 radioset opt_search">
			<input id="radsc1" name="op_opciones" type="radio" value="A" checked="" onclick="setfocus(this.form.search)" alt="" />
			<label for="radsc1">&nbsp;&nbsp;&nbsp;Activo&nbsp;&nbsp;&nbsp;</label>
			<input id="radsc2" name="op_opciones" type="radio" value="I" onclick="setfocus(this.form.search)" alt="" />
			<label for="radsc2">&nbsp;&nbsp;&nbsp;Inactivo&nbsp;&nbsp;&nbsp;</label>

		</div>
	</div>

	<div class="form-group">
		<label class="col-sm-2 control-label label-sm " for="Cop_Fec">Buscar:</label>
		<div class="col-sm-5">
			<input type="text" name="txt_busqueda" id="txt_busqueda" class="form-control input-sm text clearable">
		</div>
		<div class="col-sm-3">
			<button class="btn btn-sm btn-success" onclick="loadData();">
				<i class="glyphicon glyphicon-search"></i>&nbsp;&nbsp;&nbsp;Buscar
			</button>
		</div>
	</div>
	</fieldset>
	<!-- Text input-->
	<div class="form-group">
		<div class="col-sm-12 center">
			<input type="hidden" id="letra" name="letra" value="TODOS" />
			<nav>
				<?php $Letras = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "Ñ", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "TODOS"); ?>
				<ul class="pagination pagination-centered">
					<?php foreach ($Letras as $letra) { ?><li <?php if ($letra=='TODOS' ) echo 'class="active"'; ?>><a><?php echo $letra; ?></a></li><?php } ?>
				</ul>
			</nav>
		</div>
	</div>
	</div>
	<div class="col-sm-6">
		<fieldset class="exa-fieldset">
			<legend class="Titulos2">Filtros:</legend>
			<!-- Form Name -->
			<!-- Text input-->
			<div class="form-group">
				<label class="col-sm-3 control-label label-xs " for="Cate_Cod">Categoría:</label>
				<div class="col-sm-9">
					<?php $row_rs_categ = $obBD_con1->getArrayConsulta(32, $Ses_Emp_Cod, $obBD_conexion); ?>
					<select name="Cate_Cod" id="Cate_Cod" class="form-control input-xs" data-placeholder="Todas">
						<option value=""></option>
						<?Php foreach ($row_rs_categ as $row) { ?><option value="<?Php echo $row['Cat_Cod']; ?>"><?Php echo $row['Cat_Des']; ?></option><?Php } ?>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label label-xs " for="Sub_Cod">Subcategoría:</label>
				<div class="col-sm-9">
					<select name="Sub_Cod" id="Sub_Cod" class="form-control input-xs" data-placeholder="Todas">
						<option value=''>Todas</option>
					</select>
				</div>
			</div>


			<!-- Text input-->
			<div class="form-group">
				<label class="col-sm-3 control-label label-xs " for="Ubi_Cod">Ubicación:</label>
				<div class="col-sm-3">
					<?php $rs_ubicacion = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion); ?>
					<select name="Ubi_Cod" id="Ubi_Cod" class="form-control input-xs">
						<option value="">Todas</option>
						<?Php foreach ($rs_ubicacion as $row) {?><option value="<?Php echo $row['Ubi_Cod']; ?>"><?Php echo $row['Ubi_Des']; ?></option><?Php }?>
					</select>
				</div>
			</div>
			<!-- Text input-->
			<div class="form-group">
				<label class="col-sm-3 control-label label-xs " for="Lin_Cod">Línea:</label>
				<div class="col-sm-3">
					<?php $rs_linea = $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod, $obBD_conexion); ?>
					<select name="Lin_Cod" id="Lin_Cod" class="form-control input-xs">
						<option value="">Todas</option>
						<?Php foreach ($rs_linea as $row) { ?><option value="<?Php echo $row['Lin_Cod']; ?>"><?Php echo $row['Lin_Des']; ?></option><?Php }?>
					</select>
				</div>
				<label class="col-sm-3 control-label label-xs " for="Lin_Cod">Agrupar por:</label>
				<div class="col-sm-3">
					<select name="grupo" id="grupo" class="form-control input-xs">
						<option value="clear">No Agrupar</option>
						<option value="Cat_Des">Categoria</option>
						<option value="Ubi_Des">Bodega</option>
						<option value="Lin_Des">Linea</option>
					</select>
				</div>
			</div>
		</fieldset>
	</div>
	<div class="col-sm-12" style="min-height: 270px">
		<table id="grid"></table>
		<div id="gridPager"></div>
	</div>
	</div>

	</form>
	</div>
	</div>

	<script>
		var downProd = new Boolean(false);
		var upProd = new Boolean(false);
		var Grid = $('#grid');
		var list_printers = $.getLocalStore('printers') || {},
			printers = list_printers['has_printers'] === true;
		var list_codigos = [];
		$(document).ready(function() {
			//var Grid = $('#grid');
			$("#Cate_Cod").createChosen('input-xs', {
				allow_single_deselect: true
			});
			$('.pagination').find('li a').click(function() {
				$('.pagination').find('li').removeClass('active');
				$(this).parent().addClass('active');
				$('#letra').val($(this).text().trim());
				if ($(this).text().trim() === 'TODOS') $('#txt_busqueda').removeAttr('disabled');
				else $('#txt_busqueda').attr('disabled', 'disabled').val('');
				loadData();
			});
			$("#grupo").change(function() {
				var vl = $(this).val();
				if (vl) {
					if (vl === "clear") Grid.jqGrid('groupingRemove', true);
					else Grid.jqGrid('groupingGroupBy', vl);
				} //loadData();
			});
			Grid.createGrid({
                            colModel: [
                                {label:"Cod.Int.",name:"Pro_Cod",width:25,key:!0,viewable:!0,align:"center"},
                                {label: 'Cod.Emp', name: 'Pro_Cod_Emp', width: 25,align: 'center', sorttype: "string"},
                                {label:"Categoria",name:"Cat_Des",width:40},
                                {label:"Desc. Larga",name:"Ite_Lar",width:100,classes:"highlightSearch"},
                                {label:"Desc. Corta",name:"Ite_Cor",width:50},
                                {label:"Detalle",name:"Pro_Obs",width:50,classes:"highlightSearch"},
                                {label:"Marca",name:"Mar_Des",width:30},
                                {label:"Ubic.",name:"Ubi_Des",width:30},
                                {label:"Linea",name:"Lin_Des",width:30},
                                {label:"Pres.",name:"Pre_Des",width:30},
                                {label:"Unid.",name:"Uni_Des",width:30},
								{label: 'Aqd.', name: 'Adq_Des', width: 30},
                                {label:"M.",name:"Pro_Uni",hidden:!0,width:20},
                                {label:"Stock",name:"Stk_Can",width:20},
                                {label:"Prom.",name:"Stk_Prp",width:30,align:"right"},
                                {label:"Preci.",name:"RoundedPrice",width:30,align:"right"},
                                {label:"Iva",name:"hasIva",width:15,align:"center",formatter:"truefalse"},
                                {label:"P.V.P.",name:"Pvp",width:30,align:"right"},
                                {label:$.createIcon("trash"),name:"actReg",align:"center",width:20,formatter:"gridButton",formatoptions:{action:bajaProducto,conditional:function(a){return"I"!==a.Pro_Est;},caseFalse:function(a){return $('<button type="button" class="btn btn-xs btn-success"  onclick="activaProducto('+a.Pro_Cod+')"><i class="glyphicon glyphicon-off"></i></button>').attr("title",'<u class="orange">Activar</u> producto').prop("outerHTML");},icon:"glyphicon glyphicon-trash",type:"danger",title:"Dar de Baja producto"}}
                            ].concat(
                                            printers ? [ {label: $.createIcon('barcode'), name: 'actReg', align: "center", width: 20, formatter: 'gridButton',formatoptions: { action: "imprimirLabel", conditional: function(o) { return o.Pro_Est !== 'I'; }, icon: 'barcode', type: 'info', title: 'Imprimir Etiqueta' } }] : []
					),
					height: 270, caption: ' ', loadonce: true, rowNum: 100000000, pginput: false, pgbuttons: false, pgtext: "Mostrando {0} Documentos.", sortname: 'Cha_Cod', sortorder: "asc",
					groupingView: {
						groupField: ["Ubi_Des"], groupColumnShow: [true],groupOrder: ["asc"], groupSummary: [false], groupCollapse: false,
						groupText: ["<div><span style='float:right;'> {1} Item(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  </div>"]
						
					},
					grouping: false
				}, true, "#gridPager", { refresh: false, view: true })
				.gridButtonsAdd([
                                    { caption: "Exportar Excel", buttonicon: "download", onClickButton: function() { Grid.jqGrid('exportGridExcel', { nombre: "Productos", hoja: "HOJA 1" }); } },
                                    { caption: "Imprimir", buttonicon: "print", onClickButton: function() { Grid.jqGrid('printGrid', { nombre: "Productos", hoja: "HOJA 1" }); } }
				].concat(
                                    printers ? [null, { caption: "Lista Codigos", buttonicon: "eye-open", id: 'verCodigos', classes: 'btn-info', onClickButton: function() { showLabels(); $('#imprimirGrupo').dialog('open'); } }] : []
				));
			var inp = $('#txt_busqueda');
			Grid.on('jqGridAfterLoadComplete', function(ev, glc) {
				Grid.highlightSearch(inp.val().trim());
			});
			//loadData();
			if (printers) {
				$('#imprimirLabel').createDialog({
					height: 260,
					width: 500,
					icon: 'print'
				});
				$.each(list_printers['printers'], function(i, v) {
					$('#printers').append('<option value="' + v + '">' + v + '</option>');
				});
				$('#imprimirGrupo').createDialogDetail({
                                    height:260,width:500,caption:"Imprimir Etiquetas/Codigo de Barras",pager:"imprimirGrupoPager",
                                    colModel:[
                                        {label:"Cod.Int.",name:"Pro_Cod",width:25,key:!1,viewable:!0,align:"center",hidden:!0},
                                        {label:"Impresora",name:"printer",width:15,hidden:!0},
                                        {label:"Codigo",name:"codigo",width:25,align:"center"},
                                        {label:"Producto",name:"producto",width:40},
                                        {label:"Corta",name:"corta",width:25},
                                        {label:"#",name:"cantidad",width:10,align:"right"},
                                        {label:$.createIcon("trash"),name:"actReg",align:"center",width:10,formatter:"gridButton",formatoptions:{action:"bajaLabel",icon:"glyphicon glyphicon-trash",type:"danger",title:"Eliminar"}}
                                    ],
                                    grouping:!0,
                                    groupingView:{groupField:["printer"],groupColumnShow:[!1],groupText:['<div class="txtLeft">{0}</div>']}
                                }, {
					icon: 'print'
				}).getDialogGrid().gridButtonsAdd([{
					caption: "Imprimir",
					buttonicon: "print",
					onClickButton: function() {
						if ($('#imprimirGrupoGrid').getGridParam("reccount") === 0) return $.alert(
							'No hay etiquetas para imprimir!');
						$.postDataJson("http://" + list_printers['ip_printers'] + ":" + list_printers['port_printers'] +"/exa/printers/printBarrasGroup.php", {imprimir: list_codigos},function(r) {
								$.each(r.done, function(j, d) {
									$.each(list_codigos, function(i, im) {
										if (d === im.printer) {
											list_codigos.splice(i, 1); return false;
										}
									});
								});
								$('#imprimirGrupo').dialog('close');
								return false;
							});
					}
				}]);
			}

		});

		function bajaLabel(data) {
			$.each(list_codigos, function(i, im) {
				if (im['printer'] === data['printer']) {
					list_codigos[i]['listado'].splice(data['i'], 1);
					return false;
				}
			});
			showLabels();
		}

		function showLabels() {
			var lista = [];
			$.each(list_codigos, function(i, im) {
				$.each(im['listado'], function(j, d) { lista.push($.extend(true, {i: j}, d)); });
			});
			$('#imprimirGrupoGrid').setRows(lista);
		}

		function imprimirLabel(data) {
			$('#printerForm').setData({
				Pro_Cod: data['Pro_Cod'],
				codigo: data['Pro_Bar'],
				producto: data['Ite_Lar'] + " " + data['Pro_Obs'],
				corta: data['Ite_Cor'],
				cantidad: 1
			}, false);
			$('#imprimirLabel').dialog('open');
		}

		function sendToPrinter() {
			var data = $('#printerForm').getData();
			var link = "http://" + list_printers['ip_printers'] + ":" + list_printers['port_printers'] + "/exa/printers/";
			if (data['individual'] === 'S') {
				$.postDataJson(link + "printBarras.php", data, function(r) {
					$('#imprimirLabel').dialog('close');
					return false;
				});
			} else {
				var add = true;
				$.each(list_codigos, function(i, v) {
					if (v['printer'] === data['printer']) {
						list_codigos[i]['listado'].push(data);
						add = false;
					}
				});
				if (add) list_codigos.push({printer: data['printer'],listado: [data]});
				$('#imprimirLabel').dialog('close');
			}
		}

		function loadData() {
			$('#grid').Search('#formProduct', 'prodAjax');
		}

		function bajaProducto(producto) {
			//console.log(producto);
			downProd = true;
			upProd = false;

			$.createDialogConfirm(`¿Est&aacute; seguro que desea dar de <strong> baja a <u>${producto['Ite_Lar']} </strong></u> ?`, producto,cntrProducto);
		}

		function activaProducto(producto) {
			//console.log(producto);
			downProd = false;
			upProd = true;
			//$.createDialogConfirm(`�Est&aacute; seguro que desea dar de <strong> baja a <u>${producto['Cat_Des']} </strong></u> ?`, producto, cntrProducto);
			busquedaProducto(producto);
		}

		function busquedaProducto(codProd) {
			$.getDataJson("", {prdSearch: true, Pro_Cod: codProd}, function(resp) {
				//console.log(resp.producto);
				$.createDialogConfirm(`¿Est&aacute; seguro que desea <strong> dar de alta a <u>${resp.producto['Ite_Lar']} </strong></u>?`, codProd,cntrProducto);
			});
		}

		function cntrProducto(producto) {
			if (downProd) {
				//console.log('Esta dando de baja',producto);
				//console.log('Estado',producto['Pro_Est']);
				//bajaProd
				$.saveDataJson("", {bajaProd: true,Pro_Cod: producto['Pro_Cod']}, function(responce) {
					Grid.changeRow(producto['Pro_Cod'], {Pro_Est: 'I',actDel: ''});
					Grid.trigger("reloadGrid");
					return false;
				});
			}
			if (upProd) {
				console.log('Estamos en el Control de producto', producto);
				$.saveDataJson("", {altaProd: true, Pro_Cod: producto}, function(responce) {
					Grid.changeRow(producto['Pro_Cod'], {Pro_Est: 'A',actDel: ''});
					Grid.trigger("reloadGrid");
					loadData();
					return false;
				});
			}
		}

		$('#Cate_Cod').change(function(){
		 var cod=$('#Cate_Cod').val();
        $('#Sub_Cod').html('');
        $.get("",{CatSelect:cod}, function( response ) {
            $('#Sub_Cod').html(response);
        })  
   		});
	</script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
	<div id="imprimirGrupo" style="display: none;"></div>
	<div id="imprimirLabel" title="Imprimir Etiquetas/Codigo de Barras" style="display: none;">
		<form id="printerForm" class="form-horizontal normal" action="javascript:sendToPrinter();">
			<div class="form-group">
				<label class="col-sm-3 control-label label-sm required">Impresora:</label>
				<div class="col-sm-5">
					<select id="printers" name="printer" class="form-control input-sm"></select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-3 control-label label-sm required">Codigo:</label>
				<div class="col-sm-9">
					<input type="text" name="codigo" class="form-control input-sm" readonly="" />
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-3 control-label label-sm required">Corta:</label>
				<div class="col-sm-9">
					<div class="input-group">
						<input type="text" name="corta" class="form-control input-sm" readonly="" />
						<span class="input-group-addon">
							<input type="radio" name="label" value="corta" aria-label="Etiqueta">
						</span>
					</div>
					<!-- /input-group -->
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-3 control-label label-sm required">Producto:</label>
				<div class="col-sm-9">
					<div class="input-group">
						<input type="text" name="producto" class="form-control input-sm" readonly="" />
						<span class="input-group-addon">
							<input type="radio" name="label" value="larga" aria-label="Etiqueta" checked="">
						</span>
					</div>
					<!-- /input-group -->
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-3 control-label label-sm required">Cantidad:</label>
				<div class="col-sm-4">
					<input type="number" name="cantidad" class="form-control input-sm" step="1" required="" />
				</div>
			</div>
			<input type="text" id="individual" name="individual" class="form-control input-sm hidden" value="S" />
			<input type="text" name="Pro_Cod" class="form-control input-sm hidden" value="" />
			<div class="form-group">
				<div class="col-sm-12 center">
					<br/>
					<button type="button" onclick="$('#individual').val('N'); $('#printerForm').formSubmit();" class="btn btn-sm btn-primary">
						<i class="glyphicon glyphicon-plus"></i> AGREGAR A LISTADO</button>
					<button type="button" onclick="$('#individual').val('S'); $('#printerForm').formSubmit();" class="btn btn-sm btn-success">
						<i class="glyphicon glyphicon-print"></i> IMPRIMIR ETIQUETA</button>
				</div>
			</div>
		</form>
	</div>
	</BODY>

</HTML>