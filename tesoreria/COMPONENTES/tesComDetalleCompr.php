<?php
/**
 * Componente que muestra el detalle contable de un comprobante de egreso.
 *
 * Propiedades requeridas:
 *   $com_codigo    Codigo del comprobante
 *   $obBD_con1     Objeto de datos (logica de cheques)
 *   $obBD_conexion Objeto de conexion
 *
 * @package tesoreria.COMPONENTES
 */
if (!isset($com_codigo) || trim($com_codigo) == '') {
	echo error_alerta("No se ha definido la propiedad <strong>com_codigo</strong> para mostrar el detalle.", 2, true);
	return;
}

$row_rs_detalle = $obBD_con1->getArrayConsulta(338, $com_codigo . '*' . 'D' . '*' . 'ORDER BY Pld_Cdc', $obBD_conexion);

if (count($row_rs_detalle) == 0) {
	echo error_alerta("El comprobante seleccionado no registra asientos contables.", 1, true);
	return;
}

$detalle = current($row_rs_detalle);
$v_absoluto = explode(".", $detalle['Com_Val']);
$v_decimal = isset($v_absoluto[1]) ? str_pad(substr($v_absoluto[1], 0, 2), 2, '0') : '00';
?>
<div class="row">
	<div class="col-xs-12">
		<fieldset class="exa-fieldset">
			<legend class="Titulos2">Datos Generales</legend>
			<dl class="dl-horizontal detalle-compr">
				<dt>La cantidad de:</dt>
				<dd><?php echo num2letras($v_absoluto[0], false, true) . ', ' . $v_decimal . ' /100 DOLARES AMERICANOS'; ?></dd>
				<dt>Por concepto:</dt>
				<dd><?php echo $detalle['Com_Con']; ?></dd>
				<dt>Observaci&oacute;n:</dt>
				<dd><?php echo trim($detalle['Com_Obs']) != '' ? $detalle['Com_Obs'] : '&mdash;'; ?></dd>
			</dl>
		</fieldset>
	</div>
	<div class="col-xs-12">
		<fieldset class="exa-fieldset">
			<legend class="Titulos2">Asientos Contables</legend>
			<div class="table-responsive">
				<table class="table table-condensed table-hover table-exa">
					<thead>
						<tr>
							<th width="15%">C&oacute;digo</th>
							<th>Descripci&oacute;n</th>
							<th width="15%" class="text-right">Debe</th>
							<th width="15%" class="text-right">Haber</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_d = 0;
						$total_h = 0;
						foreach ($row_rs_detalle as $row) {
							$es_debe = ($row['Asi_Deh'] == 'D');
							if ($es_debe) $total_d += round($row['Asi_Val2'], 2);
							else $total_h += round($row['Asi_Val2'], 2);
						?>
							<tr>
								<td><?php echo $row['Pld_Cdc']; ?></td>
								<td<?php echo $es_debe ? '' : ' class="text-muted" style="padding-left:25px;"'; ?>><?php echo $row['Pld_Des']; ?></td>
								<td class="text-right"><?php echo $es_debe ? formato_numero($row['Asi_Val'], 2, 4) : '&nbsp;'; ?></td>
								<td class="text-right"><?php echo $es_debe ? '&nbsp;' : formato_numero($row['Asi_Val'], 2, 4); ?></td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr class="active">
							<th colspan="2" class="text-right">SUMAN:</th>
							<th class="text-right"><?php echo formato_numero($total_d, 2, 4); ?></th>
							<th class="text-right"><?php echo formato_numero($total_h, 2, 4); ?></th>
						</tr>
					</tfoot>
				</table>
			</div>
		</fieldset>
	</div>
</div>
