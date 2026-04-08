<?php
/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaciï¿½n  2015-07-22
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
 * Creaciï¿½n del Objeto para consultas
 */
$obBD_con1 = new Class_Log_Datos_Pro;
/**
 * Evita el reenvio
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($prodAjax)) {
    $data = $_GET;
    $obBD_con1->echoLog($data);
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
    if ($Cat_Cod != '') {
        $data['Filtros'] = $data['Filtros'] . " AND item.Cat_Cod=$Cat_Cod ";
    }
    $datos = $obBD_con1->getArrayConsulta(18, $data, $obBD_conexion);
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
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
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
                                        <input id="radsc1" name="op_opciones" type="radio" value="A" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Activo&nbsp;&nbsp;&nbsp;</label>
                                        <input id="radsc2" name="op_opciones" type="radio" value="I" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;Inactivo&nbsp;&nbsp;&nbsp;</label>

                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-sm " for="Cop_Fec">Buscar:</label>
                                    <div class="col-sm-5">
                                        <input type="text" name="txt_busqueda" id="txt_busqueda" class="form-control input-sm text clearable" >
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
                                    <input type="hidden" id="letra" name="letra" value="TODOS"  />
                                    <nav>
                                        <?php $Letras = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "Ñ", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "TODOS"); ?>
                                        <ul class="pagination pagination-centered">
                                            <?php foreach ($Letras as $letra) { ?>
                                                <li <?php if ($letra == 'TODOS') echo 'class="active"'; ?>><a><?php echo $letra; ?></a></li>
                                            <?php } ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                                <!-- Text input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs " for="Cat_Cod">Categoría:</label>
                                    <div class="col-sm-9">
<?php $row_rs_categ = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion); ?>
                                        <select name="Cat_Cod" id="Cat_Cod" class="form-control input-xs" data-placeholder="Todas">
                                            <option value=""></option>
                                            <?Php
                                            foreach ($row_rs_categ as $row) {
                                                ?>
                                                <option value="<?Php echo $row['Cat_Cod']; ?>" ><?Php echo /* strtoupper($row['Par_Cat_Des']).' » '. */$row['Cat_Des']; ?></option>
                                                <?Php }
                                            ?>
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
                                            <?Php
                                            foreach ($rs_ubicacion as $row) {
                                                ?>
                                                <option value="<?Php echo $row['Ubi_Cod']; ?>" ><?Php echo $row['Ubi_Des']; ?></option>
                                                <?Php }
                                            ?>
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
                                            <?Php
                                            foreach ($rs_linea as $row) {
                                                ?>
                                                <option value="<?Php echo $row['Lin_Cod']; ?>" ><?Php echo $row['Lin_Des']; ?></option>
                                                <?Php }
                                            ?>
                                        </select>
                                    </div>
                                    <label class="col-sm-3 control-label label-xs " for="Lin_Cod">Agrupar por::</label>
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
            $(document).ready(function () {
                //var Grid = $('#grid');
                $("#Cat_Cod").createChosen('input-xs',{allow_single_deselect: true});
                $('.pagination').find('li a').click(function () {
                    $('.pagination').find('li').removeClass('active');
                    $(this).parent().addClass('active');
                    $('#letra').val($(this).text());
                    if ($(this).text() === 'TODOS')  $('#txt_busqueda').removeAttr('disabled');
                    else $('#txt_busqueda').attr('disabled','disabled').val('');
                    loadData();
                });
                $("#grupo").change(function () {
                    var vl = $(this).val();
                    if (vl) { if (vl === "clear") Grid.jqGrid('groupingRemove', true); else Grid.jqGrid('groupingGroupBy', vl); } //loadData();
                });
                Grid.createGrid({
                    colModel: [
                        {label: 'Cod.Int.', name: 'Pro_Cod', width: 25,key: true, viewable: true,align: 'center'},
                       // {label: 'AN', name: 'Cha_Cod', width: 40, hidden: true,align: 'center', sorttype: "string"},
                        {label: 'Categoria', name: 'Cat_Des', width: 40},
                        {label: 'Desc. Larga', name: 'Ite_Lar', width: 100, classes:"highlightSearch" },
                        {label: 'Desc. Corta', name: 'Ite_Cor', width: 60},
                        {label: 'Detalle', name: 'Pro_Obs', width: 50, classes:"highlightSearch" },
                        {label: 'Marca', name: 'Mar_Des', width: 40},
                        {label: 'Ubic.', name: 'Ubi_Des', width: 40},
                        {label: 'Linea', name: 'Lin_Des', width: 40},
                        {label: 'Pres.', name: 'Pre_Des', width: 40},
                        {label: 'Unid.', name: 'Uni_Des', width: 30},
                        {label: 'M.', name: 'Pro_Uni',hidden: true, width: 20},
                        {label: 'Stock', name: 'Stk_Can', width: 20},
                        {label: 'Prom.', name: 'Stk_Prp', width: 30, align: 'right'},
						{label: 'P.V.P.', name: 'RoundedPrice', width: 30, align: 'right'},
                        //{label: 'Precio', name: 'Stk_Prp', width: 30, align: 'right'},
                        //{ label: 'P.V.P', name: 'Provee', width: 30  },
                        { label: $.createIcon('cog'), name: 'actReg', align: "center", width: 10, formatter: 'gridButton', formatoptions:{action:bajaProducto, conditional: function(o){return o.Pro_Est !== 'I'}, caseFalse: function(o){return $('<button type="button" class="btn btn-xs btn-success"  onclick="activaProducto('+o['Pro_Cod']+')"><i class="glyphicon glyphicon-off"></i></button>').attr('title','<u class="orange">Activar</u> producto').prop('outerHTML');}, icon: 'glyphicon glyphicon-trash', type: 'danger', title: 'Dar de Baja producto' } },
                    ],
                    height: 270, caption: ' ', loadonce:true, rowNum: 100000000, pginput: false, pgbuttons: false, pgtext: "Mostrando {0} Documentos.", sortname: 'Cha_Cod', sortorder: "asc",
                    groupingView: {
                        groupField: ["Ubi_Des"], groupColumnShow: [true],
                        groupText: ["<div><span style='float:right;'> {1} Item(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  </div>"],
                        groupOrder: ["asc"], groupSummary: [false], groupCollapse: false
                    }, grouping: false
                },true,"#gridPager",{refresh: false, view: true})
                    .gridButtonsAdd([
                        {caption: "Exportar Excel", buttonicon: "glyphicon glyphicon-download", onClickButton: function () { Grid.jqGrid('exportGridExcel', {nombre: "Productos", hoja: "HOJA 1"}); } },
                        {caption: "Imprimir", buttonicon: "glyphicon glyphicon-print", onClickButton: function () { Grid.jqGrid('printGrid', {nombre: "Productos", hoja: "HOJA 1"}); } }
                    ]);
				var inp	=$('#txt_busqueda');
				Grid.on('jqGridAfterLoadComplete',function (ev,glc){ Grid.highlightSearch(inp.val().trim()); });
                //loadData();
            });
            function loadData() {$('#grid').Search('#formProduct','prodAjax');}
            function bajaProducto(producto){
                //console.log(producto);
                downProd = true;
                upProd = false;

                $.createDialogConfirm(`¿Est&aacute; seguro que desea dar de <strong> baja a <u>${producto['Ite_Lar']} </strong></u> ?`, producto, cntrProducto);
            }
            function activaProducto(producto){
                //console.log(producto);
                downProd = false;
                upProd = true;
                //$.createDialogConfirm(`¿Est&aacute; seguro que desea dar de <strong> baja a <u>${producto['Cat_Des']} </strong></u> ?`, producto, cntrProducto);
                busquedaProducto(producto);
            }
            function busquedaProducto(codProd){
                $.getDataJson("", {prdSearch:true, Pro_Cod: codProd}, function(resp){
                    //console.log(resp.producto);
                    $.createDialogConfirm(`¿Est&aacute; seguro que desea <strong> dar de alta a <u>${resp.producto['Ite_Lar']} </strong></u>?`, codProd, cntrProducto);
                });
            }

            function cntrProducto(producto){
                if(downProd){
                    //console.log('Esta dando de baja',producto);
                    //console.log('Estado',producto['Pro_Est']);
                    //bajaProd
                    $.saveDataJson("", { bajaProd:true, Pro_Cod: producto['Pro_Cod']}, function(responce){
                        Grid.changeRow(producto['Pro_Cod'], { Pro_Est: 'I', actDel: '' });
                        Grid.trigger("reloadGrid");
                        return false;
                    });
                }
                if(upProd){
                    console.log('Estamos en el Control de producto',producto);
                    $.saveDataJson("", { altaProd:true, Pro_Cod: producto }, function(responce){
                        Grid.changeRow(producto['Pro_Cod'], { Pro_Est: 'A', actDel: '' });
                        Grid.trigger("reloadGrid");
                        loadData();
                        return false;
                    });
                }
            }
        </script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    </BODY>
</HTML>