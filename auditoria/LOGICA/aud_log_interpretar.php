<?php
/**
 * Interpreta logs de auditoria a lenguaje de usuario.
 *
 * @package auditoria.LOGICA
 */

if (!function_exists('aud_to_utf8')) {
	/**
	 * Normaliza texto de BD (latin1/windows-1252) a UTF-8 valido.
	 * Usar en JSON/APIs. Para HTML del ERP preferir aud_to_latin1 / aud_h.
	 */
	function aud_to_utf8($val)
	{
		if ($val === null) {
			return '';
		}
		if (!is_string($val)) {
			if (is_numeric($val)) {
				return (string)$val;
			}
			return '';
		}
		if ($val === '') {
			return '';
		}
		$isUtf8 = false;
		if (function_exists('mb_check_encoding')) {
			$isUtf8 = @mb_check_encoding($val, 'UTF-8');
		} elseif (!preg_match('/[\x80-\xFF]/', $val)) {
			$isUtf8 = true; /* ASCII */
		}
		if ($isUtf8) {
			/* Mojibake tipico: secuencias UTF-8 que representan latin1 mal leido */
			if (function_exists('utf8_decode') && function_exists('utf8_encode')
				&& preg_match('/[\xC2\xC3][\x80-\xBF]/', $val)
				&& preg_match('/Ã.|Â./', $val)) {
				$asLatin = @utf8_decode($val);
				if (is_string($asLatin) && $asLatin !== '' && preg_match('/[\x80-\xFF]/', $asLatin)) {
					$re = @utf8_encode($asLatin);
					if (is_string($re) && $re !== '' && function_exists('mb_check_encoding') && @mb_check_encoding($re, 'UTF-8')) {
						return $re;
					}
				}
			}
			return $val;
		}
		if (function_exists('mb_convert_encoding')) {
			$try = @mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
			if (is_string($try) && $try !== '' && @mb_check_encoding($try, 'UTF-8')) {
				return $try;
			}
			$try = @mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1');
			if (is_string($try) && $try !== '' && @mb_check_encoding($try, 'UTF-8')) {
				return $try;
			}
		}
		if (function_exists('utf8_encode')) {
			$try = @utf8_encode($val);
			if (is_string($try) && $try !== '') {
				return $try;
			}
		}
		return preg_replace('/[\x80-\xFF]/', '?', $val);
	}
}
if (!function_exists('aud_to_latin1')) {
	/**
	 * Texto listo para HTML del ERP (charset iso-8859-1 / conexion latin1).
	 */
	function aud_to_latin1($val)
	{
		$s = aud_to_utf8($val);
		if ($s === '') {
			return '';
		}
		if (function_exists('mb_convert_encoding')) {
			$try = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
			if (is_string($try)) {
				return $try;
			}
		}
		if (function_exists('utf8_decode')) {
			return @utf8_decode($s);
		}
		return $s;
	}
}
if (!function_exists('aud_h')) {
	function aud_h($val) {
		/* Pantallas UTF-8 + conexion mysqli utf8: escapar como UTF-8. */
		$s = function_exists('aud_to_utf8') ? aud_to_utf8($val) : (string)$val;
		if ($s === '') {
			return '';
		}
		$flags = ENT_QUOTES;
		if (defined('ENT_SUBSTITUTE')) {
			$flags |= ENT_SUBSTITUTE;
		}
		$out = htmlspecialchars($s, $flags, 'UTF-8');
		if ($out === '' && $s !== '') {
			$out = str_replace(
				array('&', '"', "'", '<', '>'),
				array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;'),
				$s
			);
		}
		return $out;
	}
}

/**
 * Etiqueta segura para combos de filtro (UTF-8).
 *
 * @param string $label
 * @param string $fallback
 * @return string texto listo para aud_h / option HTML
 */
function aud_filtro_label($label, $fallback = '')
{
	$s = trim(aud_to_utf8($label));
	if ($s === '') {
		$s = trim(aud_to_utf8($fallback));
	}
	return $s;
}

function aud_etiquetas_campo()
{
	return array(
		'Pec_Cod' => 'Periodo',
		'Com_Cod' => 'Comprobante',
		'Com_Num' => 'Numero de comprobante',
		'Com_Fec' => 'Fecha del comprobante',
		'Com_Con' => 'Concepto',
		'Com_Val' => 'Valor',
		'Com_Est' => 'Estado',
		'Tia_Cod' => 'Tipo de asiento',
		'Prv_Cod' => 'Proveedor',
		'Cli_Cod' => 'Cliente',
		'Asi_Cod' => 'Asiento',
		'Asi_Deh' => 'Debe / Haber',
		'Asi_Val' => 'Valor del asiento',
		'Asi_Con' => 'Concepto del asiento',
		'Asi_Fec' => 'Fecha del asiento',
		'Cta_Cod' => 'Cuenta contable',
		'Emp_Cod' => 'Empresa',
		'Suc_Cod' => 'Sucursal',
		'Usu_Cod' => 'Usuario',
		'Pcs_Cod' => 'Proceso',
		'Log_Int' => 'Referencia',
		'Man_Cod' => 'Manifiesto',
		'Man_Num' => 'Numero de manifiesto',
		'Man_Fec' => 'Fecha del manifiesto',
		'Man_Pes' => 'Peso reportado (Kg)',
		'Man_Pun' => 'Tarifa',
		'Man_Est' => 'Estado del manifiesto',
		'Man_Tip' => 'Accion',
		'Man_Con' => 'Concepto del manifiesto',
		'Man_Obs' => 'Observacion',
		'Man_Obe' => 'Observacion',
		'Man_Gui' => 'Guia de remision',
		'Pla_Cod' => 'Planta',
		'Veh_Cod' => 'Vehiculo',
		'Cho_Cod' => 'Chofer / operario',
		'Tud_Cod' => 'Turno',
		'Tur_Cod' => 'Configuracion de turnos',
		'Tud_Fec' => 'Fecha del turno',
		'Tud_Hin' => 'Hora de inicio',
		'Tud_Hfi' => 'Hora de fin',
		'Tud_Cup' => 'Cupos',
		'Tud_Est' => 'Estado del turno',
		'Tur_Fei' => 'Inicio de vigencia',
		'Tur_Fef' => 'Fin de vigencia',
		'Tur_Est' => 'Estado de turnos',
		'MVis_Cod' => 'Visitante',
		'MVis_Est' => 'Estado del visitante',
		'MVis_Nac' => 'Nacionalidad',
		'MVis_Eci' => 'Estado civil',
		'MVis_Obs' => 'Observacion del visitante',
		'Man_Eve' => 'Evento',
		'Man_ENom' => 'Nombre del evento',
		'Man_EFei' => 'Inicio del evento',
		'Man_EFef' => 'Fin del evento',
		'Man_EEst' => 'Estado del evento',
		'Man_Vig' => 'Vigente',
		'Man_Ehor' => 'Horas del evento',
		'Ama_Val' => 'Valor del anticipo',
		'Ama_Est' => 'Estado del anticipo',
		'Vet_Cod' => 'Venta',
		'Vet_Num' => 'Numero de factura',
		'Vet_Obs' => 'Observacion',
		'Vet_Des' => 'Fecha de la venta',
		'Vet_Hor' => 'Hora de la venta',
		'Vet_Xml' => 'Clave de acceso',
		'Vet_Aut' => 'Autorizacion',
		'Vet_Sri' => 'Autorizacion SRI',
		'Vet_Prop' => 'Propina',
		'Vet_Ide' => 'Identificacion',
		'Vet_Can' => 'Cantidad',
		'Vet_Pru' => 'Precio unitario',
		'Vet_Imp' => 'Importe',
		'Vet_Ite' => 'Item',
		'Tic_Cod' => 'Tipo de comprobante',
		'Caj_Cod' => 'Caja',
		'Caj_Fec' => 'Fecha de apertura',
		'Caj_Fef' => 'Fecha de cierre',
		'Caj_Hoi' => 'Hora de inicio',
		'Caj_Hof' => 'Hora de cierre',
		'Caj_Obs' => 'Observacion',
		'Caj_Est' => 'Estado de caja',
		'Caj_Gen' => 'Generada',
		'Caj_Exi' => 'Efectivo',
		'Vnd_Cod' => 'Vendedor',
		'Pro_Cod' => 'Producto',
		'InvDis_Cod' => 'Dispositivo',
		'DisUsr_Cod' => 'Asignacion de dispositivo',
		'Cfg_Cod' => 'Regla de monitoreo',
		'Cfg_Est' => 'Estado de la regla',
		'Org_Cod' => 'Modulo / directorio',
		'Not_Cod' => 'Notificacion',
		'Not_Est' => 'Estado de notificacion',
		'Correo' => 'Correo electronico',
		'Ses_Cod' => 'Sesion',
		'Ses_Est' => 'Estado de sesion',
		'Ses_Ip' => 'Direccion IP',
		'Ses_Min_Uso' => 'Minutos de uso',
		'Pun_Cod' => 'Punto de emision',
		'Aut_Cod' => 'Punto de emision',
		/* Tecnico / manifiesto_tecnico */
		'Mat_Cod' => 'Codigo de asignacion tecnica',
		'Hum_Cod' => 'Nivel de humedad',
		'Mat_Dna' => 'Tipo de desecho no aprobado',
		'Mat_Fde' => 'Fecha de asignacion',
		'Mat_Eae' => 'Estado ambiental',
		'Mat_Ear' => 'Estado de accion',
		'Mat_Oce' => 'Observacion',
		'Mat_Tra' => 'Tratamiento',
		'Mat_Est' => 'Estado de la asignacion',
		'Mat_Sys' => 'Fecha/hora del sistema',
		'Mat_Nce' => 'Numero de celda',
		'Mat_Cce' => 'Celda',
		'Mat_Dce' => 'Grupo de celda',
		/* Nucleo manifiesto / Relavera */
		'Man_Fec' => 'Fecha del manifiesto',
		'Man_Hor' => 'Hora del manifiesto',
		'Man_Fes' => 'Fecha/hora de salida',
		'Man_Fea' => 'Fecha/hora de llegada',
		'Man_Lac' => 'Licencia ambiental',
		'Man_Rgd' => 'Registro generador de desecho',
		'Man_Dsa' => 'Origen del desecho',
		'Man_Dde' => 'Destino del desecho',
		'Man_Rut' => 'Ruta de llegada',
		'Man_Tes' => 'Estado operativo',
		'Man_Usu' => 'Bitacora de estados',
		'Man_Sys' => 'Fecha/hora del sistema',
		'Tde_Cod' => 'Tipo de desecho',
		'Tde_Des' => 'Descripcion del desecho',
		'Tde_Cde' => 'Codigo del desecho',
		'Tde_Cas' => 'Cas / categoria del desecho',
		'Cel_Cod' => 'Celda',
		'Cel_Nom' => 'Nombre de celda',
		'Cel_Num' => 'Numero de celda',
		'Cel_Est' => 'Estado de celda',
		'Cel_Rec' => 'Grupo de celda',
		'Pla_Nom' => 'Nombre de planta',
		'Pla_Lic' => 'Licencia de planta',
		'Pla_Dir' => 'Direccion de planta',
		'Pla_Rut' => 'Ruta de planta',
		'Pla_Est' => 'Estado de planta',
		'Pla_Crd' => 'Codigo de desecho (planta)',
		'Pla_Pfa' => 'Periodicidad de facturacion',
		/* Empresa transporte (manifiesto_transporte) */
		'Mat_Des' => 'Empresa de transporte',
		'Mat_Mae' => 'Licencia ambiental MAE',
		'Mat_Tel' => 'Telefono transporte',
		'Mat_Pco' => 'Plan de contingencia',
		'Mat_Dir' => 'Direccion transporte',
		/* Mensajes / QR */
		'Msj_Id' => 'Mensaje',
		'Msj_Cod' => 'Codigo de mensaje',
		'Msj_Tip' => 'Tipo de mensaje',
		'Msj_Tex' => 'Texto del mensaje',
		'Msj_Img' => 'Imagen del mensaje',
		'Msj_Fec' => 'Fecha del mensaje',
		'Msj_Est' => 'Estado del mensaje',
		/* Contratos planta */
		'Mco_Cod' => 'Contrato',
		'Mco_Num' => 'Numero de contrato',
		'Mco_Not' => 'Nota del contrato',
		'Mco_Fca' => 'Fecha del contrato',
		'Mco_Est' => 'Estado del contrato',
		'Mcd_Cod' => 'Documento de contrato',
		'Mcd_Tip' => 'Tipo de documento',
		'Mcd_Nom' => 'Nombre del documento',
		'Mcd_Url' => 'Archivo del documento',
		'Mcd_Est' => 'Estado del documento',
		/* Turnos */
		'Mtc_Cod' => 'Cabecera de turno',
		'Mtd_Cod' => 'Detalle de turno',
		'Tud_Obs' => 'Observacion del turno',
		/* Visitantes */
		'MVis_Nom' => 'Nombre del visitante',
		'MVis_Ape' => 'Apellido del visitante',
		'MVis_Ced' => 'Cedula del visitante',
		'MVis_Tel' => 'Telefono del visitante',
		'MVis_Cor' => 'Correo del visitante',
		'Mvi_Cod' => 'Visitante',
		'Vis_Cod' => 'Visitante',
		/* Maquinaria / horometro */
		'Hor_Cod' => 'Registro de horometro',
		'Hor_Fec' => 'Fecha de jornada',
		'Hor_Ini' => 'Horometro inicial',
		'Hor_Fin' => 'Horometro final',
		'Hor_Hrs' => 'Horas trabajadas',
		'Hor_Set' => 'Ubicacion / sector',
		'Hor_Est' => 'Estado del horometro',
		'Hor_Hini' => 'Hora de inicio',
		'Hor_Hfin' => 'Hora de fin',
		'Mal_Cod' => 'Alimentacion / liquidacion',
		'Did_Cod' => 'Detalle de dispensador',
		/* Vehiculo / chofer extras */
		'Veh_Pla' => 'Placa',
		'Veh_Mar' => 'Marca',
		'Veh_Col' => 'Color',
		'Veh_Cap' => 'Capacidad / peso',
		'Veh_Tit' => 'Tipo de vehiculo',
		'Veh_Est' => 'Estado del vehiculo',
		'Cho_Mae' => 'Licencia MAE chofer',
		'Cho_Tli' => 'Tipo de licencia',
		'Cho_Est' => 'Estado del chofer',
		'Cho_Obs' => 'Observacion del chofer',
		/* Inventario / dispositivos */
		'UsInv_Cod' => 'Asignacion de inventario',
		'UsInv_Usu' => 'Usuario asignado',
		'UsInv_Fec' => 'Fecha de asignacion',
		'UsInv_Est' => 'Estado de asignacion',
		'InvDis_Nom' => 'Nombre del dispositivo',
		'InvDis_Des' => 'Descripcion del dispositivo',
		'InvDis_Tipo' => 'Tipo de dispositivo',
		'InvDis_Cupos' => 'Cupos del dispositivo',
		'InvDis_Est' => 'Estado del dispositivo',
		'InvDis_Fec' => 'Fecha del dispositivo',
		'mac_address' => 'Direccion MAC',
		'DisUsr_IP' => 'IP del dispositivo',
		'DisUsr_Est' => 'Estado del vinculo',
		'DisUsr_FecR' => 'Fecha de vinculo',
		'DisUsr_FecUA' => 'Ultima actividad del dispositivo',
		/* Anticipos */
		'Ama_Cod' => 'Anticipo',
		'Ama_Doc' => 'Documento del anticipo',
		'Ama_Fec' => 'Fecha del anticipo',
		'Ama_Fha' => 'Fecha/hora del anticipo',
		'Ama_IgV' => 'Incluye IVA',
		'Ama_Img' => 'Comprobante / imagen',
		'Ama_Obs' => 'Observacion del anticipo',
		'Ama_Tde' => 'Tipo de documento',
		'Ama_Tip' => 'Tipo de anticipo',
		/* Cobranzas CxC */
		'Cpc_Cod' => 'Cuenta por cobrar',
		'Cpc_Cxc' => 'Documento CxC',
		'Cpc_Est' => 'Estado de cobranza',
		'Cpc_Fec' => 'Fecha de cobranza',
		'Cpc_Obs' => 'Observacion de cobranza',
		'Cpc_Val' => 'Valor de cobranza',
		'Cpc_Ven' => 'Vencimiento',
		/* Persona */
		'Prs_Cod' => 'Persona',
		'Prs_Nom' => 'Nombres',
		'Prs_Ape' => 'Apellidos',
		'Prs_Ced' => 'Cedula / RUC',
		'Prs_Tel' => 'Telefono',
		'Prs_Te2' => 'Telefono alterno',
		'Prs_Cel' => 'Celular',
		'Prs_Cor' => 'Correo',
		'Prs_Dir' => 'Direccion',
		'Prs_Gen' => 'Genero',
		'Prs_Sex' => 'Sexo',
		'Prs_Est' => 'Estado de persona',
		'Prs_Fec' => 'Fecha de nacimiento',
		'Prs_Ciu' => 'Ciudad',
		'Prs_San' => 'Tipo de sangre',
		'Prs_Esc' => 'Estado civil',
		/* Cliente extras */
		'Cli_Nom' => 'Nombre del cliente',
		'Cli_Ced' => 'Identificacion del cliente',
		'Cli_Cor' => 'Correo del cliente',
		'Cli_Est' => 'Estado del cliente',
		'Cli_Tic' => 'Tipo de identificacion',
		/* Chofer extras */
		'Cho_Nom' => 'Nombre del chofer',
		'Cho_Ced' => 'Cedula del chofer',
		'Cho_Cel' => 'Celular del chofer',
		'Cho_Tel' => 'Telefono del chofer',
		'Cho_Cor' => 'Correo del chofer',
		'Cho_Dir' => 'Direccion del chofer',
		'Cho_Nac' => 'Nacionalidad',
		'Cho_Eci' => 'Estado civil',
		'Cho_Edad' => 'Edad',
		'Cho_Nli' => 'Numero de licencia',
		'Cho_Fei' => 'Fecha de ingreso',
		'Cho_Car' => 'Cargo',
		'Cho_Cli' => 'Cliente asociado',
		'Cho_Tip' => 'Tipo de chofer',
		'Cho_Tsa' => 'Tipo de sangre',
		'Cho_Tem' => 'Temperatura',
		'Cho_Nem' => 'Nivel de emergencia',
		'Cho_Tco' => 'Tipo de contrato',
		/* Vehiculo extras */
		'Veh_Mod' => 'Modelo',
		'Veh_Pes' => 'Peso del vehiculo',
		'Veh_Val' => 'Valor del vehiculo',
		'Veh_Adi' => 'Adicionales',
		'Veh_Tip' => 'Clase / tipo',
		'Veh_Amo' => 'Anio modelo',
		'Veh_Mde' => 'Motor / detalle',
		'Veh_Col2' => 'Color secundario',
		'Mav_Con' => 'Contrato del vehiculo',
		'Mac_Con' => 'Contrato del chofer',
		/* Planta extras */
		'Pla_Act' => 'Actividad de planta',
		'Pla_Cap' => 'Capacidad de planta',
		'Pla_Car' => 'Caracteristica',
		'Pla_Cau' => 'Causa / observacion',
		'Pla_Dis' => 'Distancia',
		'Pla_Fem' => 'Fecha de emision licencia',
		'Pla_Fve' => 'Fecha de vencimiento licencia',
		'Pla_Geo' => 'Geolocalizacion',
		'Pla_RUC' => 'RUC de planta',
		'Pla_Smi' => 'Saldo minimo',
		'Pla_Wat' => 'WhatsApp planta',
		'Pla_Contribuyente' => 'Contribuyente',
		/* Contrato extras */
		'Mco_Obs' => 'Observacion del contrato',
		'Mco_Vig' => 'Vigencia del contrato',
		'Mco_Ren' => 'Renovacion',
		'Mco_Fap' => 'Fecha de aprobacion',
		'Mco_Sys' => 'Fecha/hora del sistema',
		'Mco_DirN' => 'Directorio / carpeta',
		'Mcd_Sys' => 'Fecha/hora del documento',
		'Mcd_File' => 'Archivo',
		'Mcd_Del' => 'Eliminado',
		/* Evento / certificado asistencia */
		'Man_Teve' => 'Tipo de certificado',
		'Man_Afir' => 'Area de firma',
		'Man_Tcrf' => 'Texto del certificado',
		'Man_Wms' => 'Mensaje WhatsApp',
		'Man_Mmsg' => 'Mensaje del evento',
		'Man_Mdel' => 'Minutos de tolerancia',
		'Man_Nme' => 'Nombre corto',
		'Man_Reg' => 'Registro',
		'Man_Lic' => 'Licencia',
		/* Celda extras */
		'Cel_Tip' => 'Tipo de celda',
		'Cel_Ubi' => 'Ubicacion de celda',
		/* Humedad */
		'Hum_Des' => 'Descripcion de humedad',
		'Hum_Rie' => 'Riesgo de humedad',
		'Hum_Est' => 'Estado de humedad',
		/* Dispensador / alimentacion */
		'Dis_Cod' => 'Dispensador',
		'Dis_Nom' => 'Nombre del dispensador',
		'Dis_Des' => 'Descripcion del dispensador',
		'Dis_Tip' => 'Combustible',
		'Dis_Are' => 'Area del dispensador',
		'Dis_Cap' => 'Capacidad',
		'Dis_Com' => 'Combustible / producto',
		'Dis_Uni' => 'Unidad',
		'Dis_Est' => 'Estado del dispensador',
		'Dis_Sys' => 'Fecha/hora del sistema',
		'Did_Can' => 'Cantidad',
		'Did_Est' => 'Estado del movimiento',
		'Did_Fec' => 'Fecha del movimiento',
		'Did_Obs' => 'Observacion',
		'Did_Otr' => 'Otro detalle',
		'Did_Pun' => 'Punto / valor',
		'Did_Rel' => 'Relacionado',
		'Did_Sys' => 'Fecha/hora del sistema',
		'Did_Tip' => 'Tipo de movimiento',
		'Mal_Est' => 'Estado de alimentacion',
		'Mal_Fec' => 'Fecha de alimentacion',
		'Mal_Num' => 'Numero de alimentacion',
		'Mal_Obs' => 'Observacion',
		'Mal_Sys' => 'Fecha/hora del sistema',
		'Mal_Tip' => 'Tipo de alimentacion',
		/* Horometro extras */
		'Hor_Cal' => 'Calibracion',
		'Hor_Obs' => 'Observacion del horometro',
		'Hor_Tur' => 'Turno de jornada',
		/* Visitante extras */
		'MVis_Nem' => 'Nivel de emergencia',
		'MVis_Tem' => 'Temperatura',
		'MVis_Tsa' => 'Tipo de sangre',
		/* Mensaje / turno sys */
		'Msj_Cnt' => 'Contador de mensaje',
		'Msj_Sys' => 'Fecha/hora del sistema',
		'Tur_Sys' => 'Fecha/hora del sistema',
		/* Matricula / datos vehiculo extendidos (manifiesto_matricula) */
		'Mat_Pla' => 'Placa',
		'Mat_Mar' => 'Marca',
		'Mat_Ano' => 'Anio',
		'Mat_Obs' => 'Observacion',
		'Mat_Tip' => 'Tipo',
		'Mat_Ton' => 'Tonelaje',
		'Mat_Num' => 'Numero',
		'Mat_Fca' => 'Fecha de caducidad',
		'Mat_Fem' => 'Fecha de emision',
		'Mat_Fve' => 'Fecha de vencimiento',
		'Mat_Cha' => 'Chasis',
		'Mat_Nmo' => 'Motor',
		'Mat_Ram' => 'RAMV',
		'Mat_Cil' => 'Cilindraje',
		'Mat_Cve' => 'Clase de vehiculo',
		'Mat_Ori' => 'Pais de origen',
		'Mat_Car' => 'Carroceria',
		'Mat_Tpe' => 'Tipo de peso',
		'Mat_Tco' => 'Tipo de combustible',
		'Mat_Deg' => 'Digitador',
		'Mat_Pan' => 'Placa anterior',
		'Mat_Npa' => 'Numero de pasajeros',
		'Mat_Adj' => 'Adjunto',
		/* Anticipos contables / bancos */
		'Ant_Cod' => 'Anticipo contable',
		'Ant_Doc' => 'Numero de anticipo',
		'Ant_Val' => 'Valor del anticipo',
		'Ant_Fec' => 'Fecha del anticipo',
		'Ant_Est' => 'Estado del anticipo',
		'Ant_Obs' => 'Observacion del anticipo',
		'Ant_Tip' => 'Tipo de anticipo',
		'Ban_Cod' => 'Cuenta bancaria',
		'Ban_Cue' => 'Numero de cuenta',
		'Ban_Obs' => 'Observacion bancaria',
		'Ban_Tip' => 'Tipo de cuenta',
		'Ban_Est' => 'Estado de cuenta bancaria',
		'Bak_Cod' => 'Banco',
		'Bak_Des' => 'Nombre del banco',
		'Bak_Est' => 'Estado del banco',
		'Pld_Cod' => 'Plan de cuentas',
		'Pld_Des' => 'Descripcion del plan',
		'Pld_Cdc' => 'Codigo contable',
		'Dis_Uni' => 'Unidad de medida',
		'Ddc_Val' => 'Valor aplicado',
		'Ddc_Cod' => 'Detalle de anticipo',
		'Ciu_Cod' => 'Ciudad',
		'Ide_Cod' => 'Tipo de identificacion',
		'Aut_Cod' => 'Autorizacion SRI',
		'Tic_Cod' => 'Tipo de comprobante',
		'Tpc_Cod' => 'Forma de pago',
		'Pag_Cod' => 'Tipo de pago',
		'Pun_Cod' => 'Punto de impresion',
		'Per_Cod' => 'Personal',
		'Prs_Cod' => 'Persona',
		'Maq_Cod' => 'Equipo de maquinaria',
		'Com_Cod' => 'Comprobante',
		'Vet_Cod' => 'Venta / factura',
		'Cta_Cod' => 'Cuenta contable',
		'Pld_Cod' => 'Cuenta del plan'
	);
}

function aud_modulos_tabla()
{
	return array(
		'comprobantes' => 'Contabilidad',
		'asientos' => 'Contabilidad',
		'usuarios' => 'Administracion',
		'compras' => 'Compras',
		'proveedore' => 'Compras',
		'facturas' => 'Facturacion',
		'ventas' => 'Facturacion',
		'ventas_det' => 'Facturacion',
		'caja_aper' => 'Facturacion',
		'clientes' => 'Facturacion',
		'manifiesto' => 'Relavera / Manifiestos',
		'manifiesto_turnos_cab' => 'Relavera / Turnos',
		'manifiesto_turnos_det' => 'Relavera / Turnos',
		'manifiesto_visitante' => 'Relavera / Visitantes',
		'manifiesto_evento' => 'Relavera / Visitantes',
		'anticipos_clientes' => 'Relavera / Anticipos',
		'det_ant_cccc' => 'Relavera / Anticipos',
		'manifiesto_anticipo' => 'Relavera / Anticipos',
		'pag_anticipo_cli' => 'Relavera / Anticipos',
		'manifiesto_contratos' => 'Relavera / Contratos',
		'manifiesto_contratos_docu' => 'Relavera / Contratos',
		'param_manifiesto' => 'Relavera / Contratos',
		'maquinaria_alimentacion' => 'Relavera / Maquinaria',
		'maquinaria_dispensador' => 'Relavera / Maquinaria',
		'maquinaria_dispensador_det' => 'Relavera / Maquinaria',
		'maquinaria_dispensador_cierre' => 'Relavera / Maquinaria',
		'maquinaria_equipo' => 'Relavera / Maquinaria',
		'maquinaria_horometro' => 'Relavera / Maquinaria',
		'manifiesto_liquidacion_maq' => 'Relavera / Maquinaria',
		'manifiesto_tecnico' => 'Relavera / Tecnicos',
		'manifiesto_mensajes' => 'Relavera / Tecnicos',
		'chofer' => 'Relavera / Operario y Vehiculos',
		'vehiculo' => 'Relavera / Operario y Vehiculos',
		'personal' => 'Relavera / Operario y Vehiculos',
		'inventario_dispositivos' => 'Relavera / Inventario',
		'usuario_inventario' => 'Relavera / Inventario',
		'ccpp_cobrar' => 'Relavera / Cobranzas',
		'det_ccpp_c' => 'Relavera / Cobranzas',
		'pago_venta' => 'Relavera / Cobranzas',
		'ventas_compr' => 'Relavera / Cobranzas'
	);
}

/**
 * Casos de simulacion: cada tabla se ata a un proceso cuyo modulo raiz coincide.
 * mod_like se usa en SQL; mod_re / mod_not_re validan que no haya cruce.
 */
function aud_sim_casos()
{
	return array(
		array(
			'id' => 'comprobantes',
			'pcs_noms' => array('con_alt_compr', 'con_pri_compr'),
			'mod_like' => 'ontabilid',
			'mod_re' => '/contabilid/i',
			'mod_not_re' => '/auditoria|relavera|facturaci|tesorer/i',
			'tabs' => array('comprobantes', 'asientos')
		),
		array(
			'id' => 'manifiesto',
			'pcs_noms' => array('man_alt_manifiesto'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|manifiesto/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto')
		),
		array(
			'id' => 'turnos',
			'pcs_noms' => array('man_adm_turnos'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|turno/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto_turnos_cab', 'manifiesto_turnos_det')
		),
		array(
			'id' => 'visitantes',
			'pcs_noms' => array('man_alt_visitantes', 'man_alt_visitante'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|visitante/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto_visitante')
		),
		array(
			'id' => 'eventos',
			'pcs_noms' => array('man_adm_configuracion'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|visitante|evento/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto_evento')
		),
		array(
			'id' => 'ventas',
			'pcs_noms' => array('fac_alt_fac_ven_3.2', 'fac_alt_fac_ven'),
			'mod_like' => 'acturaci',
			'mod_re' => '/facturaci/i',
			'mod_not_re' => '/auditoria|contabilid|relavera/i',
			'tabs' => array('ventas', 'ventas_det')
		),
		array(
			'id' => 'caja',
			'pcs_noms' => array('fac_alt_caja_2.0', 'fac_alt_caja', 'fac_alt_aper_caja'),
			'mod_like' => 'acturaci',
			'mod_re' => '/facturaci/i',
			'mod_not_re' => '/auditoria|contabilid|relavera/i',
			'tabs' => array('caja_aper')
		),
		array(
			'id' => 'anticipos',
			'pcs_noms' => array('man_ant_1.0', 'man_est_cuenta_1.0'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|anticipo/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('anticipos_clientes', 'det_ant_cccc', 'manifiesto_anticipo', 'pag_anticipo_cli')
		),
		array(
			'id' => 'contratos',
			'pcs_noms' => array('man_alt_contratos', 'man_con_planta', 'man_alt_param'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|contrato|planta/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('manifiesto_contratos', 'manifiesto_contratos_docu', 'param_manifiesto')
		),
		array(
			'id' => 'maquinaria',
			'pcs_noms' => array('man_alt_maquinaria_dispensador', 'man_alt_maquinaria_horometro', 'man_alt_maquinaria_preliquidacion', 'man_alt_alimentacion'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|maquinaria|dispensador|horometro|alimentacion/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('maquinaria_alimentacion', 'maquinaria_dispensador', 'maquinaria_dispensador_det', 'maquinaria_dispensador_cierre', 'maquinaria_equipo', 'maquinaria_horometro', 'manifiesto_liquidacion_maq')
		),
		array(
			'id' => 'tecnicos',
			'pcs_noms' => array('man_tec_1.0', 'man_tec_camp_1.0'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|tecnico/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('manifiesto_tecnico', 'manifiesto_mensajes')
		),
		array(
			'id' => 'operario_vehiculos',
			'pcs_noms' => array('man_alt_vehiculos_choferes', 'man_alt_datos_choferes_vehiculos'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|vehiculo|chofer|operario/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('chofer', 'vehiculo', 'personal')
		),
		array(
			'id' => 'inventario',
			'pcs_noms' => array('inventario_dispositivos', 'dispositivos_usuario', 'man_adm_usuarios', 'man_adm_notificacion'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|inventario|dispositivo/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('inventario_dispositivos', 'usuario_inventario', 'dispositivos_usuario')
		),
		array(
			'id' => 'cobranzas',
			'pcs_noms' => array('man_est_cuenta_1.0', 'man_fac_man', 'man_alt_fac'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|cobranza|cuenta|pago/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('ccpp_cobrar', 'det_ccpp_c', 'pago_venta', 'ventas_compr')
		)
	);
}

function aud_sim_caso_por_id($id)
{
	foreach (aud_sim_casos() as $c) {
		if ($c['id'] === $id) {
			return $c;
		}
	}
	return null;
}

/** El modulo resuelto del proceso debe coincidir con el caso de simulacion. */
function aud_sim_modulo_valido($row, $caso)
{
	if (!is_array($row) || !is_array($caso) || empty($row['Pcs_Cod'])) {
		return false;
	}
	$mod = '';
	if (!empty($row['Mod_Des'])) {
		$mod = trim($row['Mod_Des']);
	} elseif (!empty($row['Org_Des'])) {
		$mod = trim($row['Org_Des']);
	}
	if ($mod === '') {
		return false;
	}
	if (!preg_match($caso['mod_re'], $mod)) {
		return false;
	}
	if (!empty($caso['mod_not_re']) && preg_match($caso['mod_not_re'], $mod)) {
		return false;
	}
	return true;
}

/**
 * Sube directorio -> padre -> abuelo y solo acepta el nodo raiz (Org_Niv=0).
 * Un directorio intermedio nunca es el modulo.
 */
function aud_resolver_modulo_arbol($nodo, $padre = null, $abuelo = null, $bis = null)
{
	$cands = array($nodo, $padre, $abuelo, $bis);
	foreach ($cands as $n) {
		if (!is_array($n) || empty($n['Org_Cod'])) {
			continue;
		}
		$niv = isset($n['Org_Niv']) ? (int)$n['Org_Niv'] : -1;
		if ($niv === 0 && !empty($n['Org_Des'])) {
			return array(
				'Org_Cod' => (int)$n['Org_Cod'],
				'Org_Des' => trim($n['Org_Des'])
			);
		}
	}
	return null;
}

function aud_nombre_registro($tabNom, $tabAli, $tabDes)
{
	$map = array(
		'comprobantes' => 'comprobante contable',
		'asientos' => 'asiento contable',
		'usuarios' => 'usuario',
		'compras' => 'compra',
		'facturas' => 'factura',
		'ventas' => 'factura de venta',
		'ventas_det' => 'detalle de venta',
		'caja_aper' => 'caja',
		'manifiesto' => 'manifiesto',
		'manifiesto_turnos_cab' => 'turno',
		'manifiesto_turnos_det' => 'detalle de turno',
		'manifiesto_visitante' => 'visitante',
		'manifiesto_evento' => 'evento',
		'anticipos_clientes' => 'anticipo de cliente',
		'det_ant_cccc' => 'detalle de anticipo',
		'manifiesto_anticipo' => 'anticipo',
		'pag_anticipo_cli' => 'pago de anticipo',
		'manifiesto_contratos' => 'contrato con planta',
		'manifiesto_contratos_docu' => 'documento de contrato',
		'param_manifiesto' => 'parametro de manifiesto',
		'maquinaria_alimentacion' => 'alimentacion de maquinaria',
		'maquinaria_dispensador' => 'dispensador de gasolina',
		'maquinaria_dispensador_det' => 'detalle de dispensador',
		'maquinaria_dispensador_cierre' => 'cierre de dispensador',
		'maquinaria_equipo' => 'equipo de maquinaria',
		'maquinaria_horometro' => 'horometro de maquinaria',
		'manifiesto_liquidacion_maq' => 'liquidacion de maquinaria',
		'manifiesto_tecnico' => 'asignacion de tecnico',
		'manifiesto_mensajes' => 'mensaje',
		'chofer' => 'chofer',
		'vehiculo' => 'vehiculo',
		'personal' => 'personal',
		'inventario_dispositivos' => 'dispositivo de inventario',
		'usuario_inventario' => 'usuario de inventario',
		'ccpp_cobrar' => 'cuenta por cobrar',
		'det_ccpp_c' => 'detalle de cuenta por cobrar',
		'pago_venta' => 'pago de venta',
		'ventas_compr' => 'comprobante de venta'
	);
	$k = strtolower(trim((string)$tabNom));
	if (isset($map[$k])) {
		return $map[$k];
	}
	if (strlen(trim((string)$tabDes)) > 0) {
		return strtolower(trim($tabDes));
	}
	if (strlen(trim((string)$tabAli)) > 0) {
		return strtolower(trim($tabAli));
	}
	return $k !== '' ? strtolower(str_replace('_', ' ', $k)) : 'registro';
}

function aud_verbo_evento($eveIni, $eveDes)
{
	$ini = strtoupper(trim((string)$eveIni));
	if ($ini === 'I') {
		return 'Registro';
	}
	if ($ini === 'U') {
		return 'Modifico';
	}
	if ($ini === 'D') {
		return 'Elimino';
	}
	if ($ini === 'F') {
		return 'Intento fallido';
	}
	$des = trim((string)$eveDes);
	$mapDes = array(
		'Insertar' => 'Nuevo registro',
		'Actualizar' => 'Modificacion',
		'Eliminar' => 'Eliminacion',
		'Fallido' => 'Intento fallido'
	);
	if ($des !== '' && isset($mapDes[$des])) {
		return $mapDes[$des];
	}
	return $des !== '' ? $des : 'Actividad';
}

function aud_etiqueta_evento($eveIni, $eveDes)
{
	$ini = strtoupper(trim((string)$eveIni));
	if ($ini === 'I') {
		return 'Nuevo registro';
	}
	if ($ini === 'U') {
		return 'Modificacion';
	}
	if ($ini === 'D') {
		return 'Eliminacion';
	}
	if ($ini === 'F') {
		return 'Intento fallido';
	}
	return aud_verbo_evento($eveIni, $eveDes);
}

function aud_nombre_modulo($row)
{
	if (!empty($row['Mod_Des'])) {
		return aud_to_utf8(trim($row['Mod_Des']));
	}
	$arbol = aud_resolver_modulo_arbol(
		array(
			'Org_Cod' => isset($row['Dir_Cod']) ? $row['Dir_Cod'] : (isset($row['Org_Cod']) ? $row['Org_Cod'] : 0),
			'Org_Des' => isset($row['Dir_Des']) ? $row['Dir_Des'] : (isset($row['Org_Des']) ? $row['Org_Des'] : ''),
			'Org_Niv' => isset($row['Org_Niv']) ? $row['Org_Niv'] : (isset($row['Dir_Niv']) ? $row['Dir_Niv'] : -1)
		),
		array(
			'Org_Cod' => isset($row['Padre_Cod']) ? $row['Padre_Cod'] : 0,
			'Org_Des' => isset($row['Padre_Des']) ? $row['Padre_Des'] : '',
			'Org_Niv' => isset($row['Padre_Niv']) ? $row['Padre_Niv'] : -1
		),
		array(
			'Org_Cod' => isset($row['Abuelo_Cod']) ? $row['Abuelo_Cod'] : 0,
			'Org_Des' => isset($row['Abuelo_Des']) ? $row['Abuelo_Des'] : '',
			'Org_Niv' => isset($row['Abuelo_Niv']) ? $row['Abuelo_Niv'] : -1
		)
	);
	if ($arbol !== null) {
		return aud_to_utf8($arbol['Org_Des']);
	}
	$tieneProceso = !empty($row['Pcs_Cod']) || !empty($row['Pcs_Lin']) || !empty($row['Pcs_Nom']);
	if ($tieneProceso) {
		return 'Sin modulo';
	}
	$tab = isset($row['Tab_Nom']) ? strtolower(trim($row['Tab_Nom'])) : '';
	$map = aud_modulos_tabla();
	if ($tab !== '' && isset($map[$tab])) {
		return $map[$tab];
	}
	if (!empty($row['Tab_Des'])) {
		return aud_to_utf8(trim($row['Tab_Des']));
	}
	if (!empty($row['Tab_Ali'])) {
		return aud_to_utf8(trim($row['Tab_Ali']));
	}
	return $tab !== '' ? $tab : 'Sistema';
}

function aud_nombre_directorio($row)
{
	if (!empty($row['Dir_Des'])) {
		return aud_to_utf8(trim($row['Dir_Des']));
	}
	if (!empty($row['Org_Des'])) {
		return aud_to_utf8(trim($row['Org_Des']));
	}
	return '';
}

function aud_nombre_empresa($row)
{
	if (!empty($row['Emp_Nom'])) {
		$nom = aud_to_utf8(trim($row['Emp_Nom']));
		if ($nom !== '') {
			return $nom;
		}
	}
	if (!empty($row['Emp_Cod'])) {
		return 'Empresa '.$row['Emp_Cod'];
	}
	return 'Sin empresa';
}

function aud_nombre_sucursal($row)
{
	if (!empty($row['Suc_Des'])) {
		$nom = aud_to_utf8(trim($row['Suc_Des']));
		if ($nom !== '') {
			return $nom;
		}
	}
	if (!empty($row['Suc_Cod'])) {
		return 'Sucursal '.$row['Suc_Cod'];
	}
	return '';
}

function aud_nombre_usuario($row)
{
	$nom = isset($row['Usu_Nom']) ? aud_to_utf8(trim($row['Usu_Nom'])) : '';
	if ($nom !== '') {
		return $nom;
	}
	if (!empty($row['Usu_Cod'])) {
		return 'Usuario '.$row['Usu_Cod'];
	}
	return 'Usuario no identificado';
}

function aud_nombre_proceso($row)
{
	if (!empty($row['Pcs_Lin'])) {
		return aud_to_utf8(trim($row['Pcs_Lin']));
	}
	if (!empty($row['Pcs_Det'])) {
		return aud_to_utf8(trim($row['Pcs_Det']));
	}
	if (!empty($row['Pcs_Nom'])) {
		return aud_to_utf8(trim($row['Pcs_Nom']));
	}
	$fromInt = aud_pcs_nom_desde_int(isset($row['Log_Int']) ? $row['Log_Int'] : '');
	if ($fromInt !== '') {
		return aud_to_utf8($fromInt).' (no registrado)';
	}
	return 'Proceso no registrado';
}

function aud_pcs_nom_desde_int($int)
{
	$int = (string)$int;
	if (preg_match('/Pcs_Nom=([^|;]+)/', $int, $m)) {
		return trim($m[1]);
	}
	return '';
}

function aud_valores_anteriores($row)
{
	$int = isset($row['Log_Int']) ? (string)$row['Log_Int'] : '';
	$pos = strpos($int, 'OLD:');
	if ($pos === false) {
		return array();
	}
	$chunk = substr($int, $pos + 4);
	$chunk = preg_replace('/\|\|.*$/', '', $chunk);
	$out = array();
	foreach (explode(',', $chunk) as $part) {
		$eq = explode('=', $part, 2);
		if (count($eq) === 2) {
			$atr = trim($eq[0]);
			if ($atr !== '') {
				$out[$atr] = trim($eq[1]);
			}
		}
	}
	return $out;
}

function aud_identificador($row, $obBD_conexion = null)
{
	$int = isset($row['Log_Int']) ? trim(str_replace('~', '', (string)$row['Log_Int'])) : '';
	if ($int === '') {
		return '';
	}
	$int = preg_replace('/Pcs_Nom=[^|;]+\s*\|\|\s*/', '', $int);
	$int = preg_replace('/\s*\|\|\s*OLD:.*$/', '', $int);
	$int = trim($int);
	if ($int === '') {
		return '';
	}
	$bits = array();
	$chunks = preg_split('/\s*\|\|\s*|\s*;\s*/', $int);
	foreach ($chunks as $chunk) {
		$chunk = trim($chunk);
		if ($chunk === '' || stripos($chunk, 'Pcs_Nom=') === 0) {
			continue;
		}
		if (strpos($chunk, '=') !== false) {
			$partes = explode('=', $chunk, 2);
			$col = trim(str_replace('`', '', $partes[0]));
			$eti = aud_humanizar_campo($col);
			$val = isset($partes[1]) ? trim($partes[1], " \t\n\r'\"~") : '';
			if ($val !== '') {
				$valNat = aud_valor_natural($col, $val, $row, $obBD_conexion);
				if ($valNat !== '' && $valNat !== $val && $valNat !== '(sin valor)') {
					$bits[] = $eti.': '.$valNat;
				} else {
					$bits[] = $eti.': '.$val;
				}
			} else {
				$bits[] = $eti;
			}
		} else {
			// Solo un numero: si es manifiesto, resolver etiqueta
			if (preg_match('/^\d+$/', $chunk)) {
				$tab = isset($row['Tab_Nom']) ? strtolower(trim((string)$row['Tab_Nom'])) : '';
				if ($tab === 'manifiesto' || $tab === 'manifiesto_tecnico') {
					$col = ($tab === 'manifiesto') ? 'Man_Cod' : 'Mat_Cod';
					$valNat = aud_valor_natural($col, $chunk, $row, $obBD_conexion);
					$bits[] = ($valNat !== '' && $valNat !== $chunk) ? $valNat : (aud_humanizar_campo($col).': '.$chunk);
				} else {
					$bits[] = $chunk;
				}
			} else {
				$bits[] = $chunk;
			}
		}
		if (count($bits) >= 4) {
			break;
		}
	}
	return implode(' · ', $bits);
}

function aud_etiqueta_campo($atr, $rowCampo)
{
	$atr = trim(str_replace('`', '', (string)$atr));
	if (is_array($rowCampo)) {
		$ali = !empty($rowCampo['Cam_Ali']) ? trim($rowCampo['Cam_Ali']) : '';
		$des = !empty($rowCampo['Cam_Des']) ? trim($rowCampo['Cam_Des']) : '';
		// Si el alias sigue siendo el codigo tecnico (Cam_Nom), humanizar.
		if ($ali !== '' && strcasecmp($ali, $atr) !== 0 && strpos($ali, '_') === false) {
			return $ali;
		}
		if ($des !== '' && strcasecmp($des, $atr) !== 0 && strpos($des, '_') === false) {
			return $des;
		}
		if ($ali !== '' && strcasecmp($ali, $atr) !== 0) {
			return $ali;
		}
	}
	return aud_humanizar_campo($atr);
}

/**
 * Explica que significa el VALOR concreto (no el nombre del campo).
 * Ej.: A => "Activo: el registro esta vigente";
 *      42 => "El valor 42 corresponde al usuario Juan Perez".
 */
function aud_descripcion_meta($atr, $valNatural, $valRaw, $rowCampo = null)
{
	$atr = trim(str_replace('`', '', (string)$atr));
	$eti = aud_etiqueta_campo($atr, $rowCampo);
	$valNatural = trim((string)$valNatural);
	$valRaw = trim((string)$valRaw);
	$rawUp = strtoupper($valRaw);

	if ($valNatural === '' || $valNatural === '(sin valor)') {
		return 'Sin valor: no se indico informacion en este dato.';
	}

	$suf = '';
	$partes = explode('_', $atr);
	if (count($partes) >= 2) {
		$suf = strtolower($partes[count($partes) - 1]);
	}
	$esCodigo = ($suf === 'cod' || preg_match('/_Cod$/', $atr));
	$codigoResuelto = ($esCodigo && $valRaw !== '' && preg_match('/^\d+$/', $valRaw) && $valNatural !== $valRaw);

	// Estados: significado del valor A/I/C/S/F
	$estados = array(
		'Man_Est', 'Tud_Est', 'Tur_Est', 'MVis_Est', 'Man_EEst', 'Ama_Est',
		'Com_Est', 'Cfg_Est', 'Not_Est', 'Caj_Est', 'Ses_Est', 'Mat_Est',
		'UsInv_Est', 'InvDis_Est', 'DisUsr_Est'
	);
	if (in_array($atr, $estados) || $suf === 'est') {
		$mapEst = array(
			'A' => 'Activo: el registro esta vigente y disponible para usarse.',
			'I' => 'Inactivo: el registro fue anulado o deshabilitado.',
			'C' => 'Cerrado: el registro quedo finalizado y ya no admite cambios normales.',
			'S' => 'Suspendido: el registro esta temporalmente detenido.',
			'F' => 'Forzado: el cierre se hizo de manera administrativa (no por el usuario).'
		);
		if ($atr === 'Caj_Est') {
			$mapEst['A'] = 'Abierta: la caja esta en operacion.';
			$mapEst['C'] = 'Cerrada: la caja ya fue cuadrada o cerrada.';
		}
		if ($atr === 'Ses_Est') {
			$mapEst['A'] = 'Activa: la sesion del usuario sigue abierta.';
			$mapEst['C'] = 'Cerrada: el usuario cerro sesion normalmente.';
			$mapEst['I'] = 'Inactividad: la sesion se cerro automaticamente por falta de uso.';
			$mapEst['F'] = 'Forzada: un administrador cerro la sesion del usuario.';
		}
		if (isset($mapEst[$rawUp])) {
			return $mapEst[$rawUp];
		}
		return 'El valor "'.$valNatural.'" es el estado actual de '.$eti.'.';
	}

	// Si / No
	if (in_array($atr, array('Caj_Gen', 'Man_Vig', 'Cfg_Activo', 'Ama_IgV', 'Mco_Vig', 'Mco_Ren')) || $suf === 'vig' || $suf === 'gen') {
		if ($rawUp === 'S' || $rawUp === 'A' || strcasecmp($valNatural, 'Si') === 0) {
			return 'Si: esta opcion esta marcada / habilitada.';
		}
		if ($rawUp === 'N' || $rawUp === 'I' || strcasecmp($valNatural, 'No') === 0) {
			return 'No: esta opcion no esta marcada / esta deshabilitada.';
		}
	}

	if ($atr === 'Asi_Deh') {
		if ($rawUp === 'D' || stripos($valNatural, 'Debe') !== false) {
			return 'Debe: el monto se cargo al lado Debe del asiento.';
		}
		if ($rawUp === 'H' || stripos($valNatural, 'Haber') !== false) {
			return 'Haber: el monto se cargo al lado Haber del asiento.';
		}
	}

	if ($atr === 'Man_Tip') {
		$mapTip = array(
			'P' => 'Pendiente: el manifiesto esta pendiente de avance operativo.',
			'C' => 'Comercial: tipo de manifiesto comercial.',
			'T' => 'Transporte: tipo de manifiesto de transporte.',
			'V' => 'Visitante: tipo asociado a visitante.',
			'GE' => 'Entrada de Volqueta: el guardia registro el ingreso de la volqueta.',
			'GI' => 'Entrada de Volqueta: el guardia registro el ingreso de la volqueta.',
			'GS' => 'Salida de Volqueta: el guardia registro la salida de la volqueta.',
			'A' => 'Aprobado: el tecnico aprobo el manifiesto.',
			'F' => 'Facturado: el manifiesto ya fue facturado.',
			'R' => 'Rechazado: el manifiesto fue rechazado.'
		);
		if (isset($mapTip[$rawUp])) {
			return $mapTip[$rawUp];
		}
	}
	if ($atr === 'Man_Tes') {
		return 'Estado operativo del manifiesto: "'.$valNatural.'".';
	}
	if ($atr === 'Man_Fes' || $atr === 'Man_Fea') {
		$que = ($atr === 'Man_Fes') ? 'salida desde planta' : 'llegada a relavera';
		return 'El valor "'.$valNatural.'" es la fecha/hora de '.$que.'.';
	}
	if ($atr === 'Man_Gui') {
		return 'El valor "'.$valNatural.'" es el numero de guia de remision.';
	}
	if ($atr === 'Man_Lac') {
		return 'El valor "'.$valNatural.'" es el numero de licencia ambiental.';
	}
	if ($atr === 'Man_Dsa' || $atr === 'Man_Dde' || $atr === 'Man_Rut') {
		return 'El valor es el texto: "'.$valNatural.'".';
	}

	if ($atr === 'Mat_Eae') {
		$map = array(
			'A' => 'Aceptado: el desecho fue aceptado ambientalmente.',
			'R' => 'Rechazado: el desecho no fue aceptado.',
			'AC' => 'Aceptado con condicion: se acepto con observaciones o condiciones.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Mat_Ear') {
		$map = array(
			'TR' => 'Transporte: la accion registrada es de transporte.',
			'AT' => 'Almacenamiento temporal: la accion es almacenamiento temporal.',
			'EL' => 'Eliminacion: la accion registrada es eliminacion.',
			'DF' => 'Disposicion final: la accion es disposicion final.',
			'CT' => 'Cierre tecnico: la accion es un cierre tecnico.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Mat_Tra') {
		$map = array(
			'AT' => 'Almacenamiento temporal: tratamiento aplicado al desecho.',
			'DF' => 'Disposicion final: tratamiento de disposicion final.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Did_Tip') {
		$map = array(
			'IN' => 'Compra a proveedor: ingreso de combustible/producto al dispensador.',
			'IC' => 'Carga interna o ajuste positivo del inventario del dispensador.',
			'ET' => 'Transferencia de entrada desde otro dispensador.',
			'SA' => 'Abastecimiento a maquinaria: despacho de combustible.',
			'SC' => 'Ajuste negativo o consumo interno del dispensador.',
			'ST' => 'Transferencia de salida hacia otro dispensador.',
			'SI' => 'Saldo inicial registrado en el dispensador.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Mal_Tip') {
		$map = array(
			'D' => 'Desayuno: registro de alimentacion del personal.',
			'A' => 'Almuerzo: registro de alimentacion del personal.',
			'M' => 'Merienda: registro de alimentacion del personal.',
			'C' => 'Cena: registro de alimentacion del personal.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Veh_Tip') {
		$map = array(
			'V' => 'Clasificacion: maquinaria tipo vehiculo.',
			'O' => 'Clasificacion: otros equipos.',
			'VM' => 'Vehiculo vinculado a manifiestos.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Veh_Tit') {
		$map = array(
			'V' => 'Titulo/tipo: volqueta.',
			'B' => 'Titulo/tipo: bus(eta).',
			'C' => 'Titulo/tipo: camioneta.',
			'M' => 'Titulo/tipo: maquinaria.',
			'T' => 'Titulo/tipo: trailer.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Dis_Tip') {
		$map = array(
			'DI' => 'Combustible DIESEL del dispensador.',
			'SU' => 'Combustible SUPER del dispensador.',
			'EC' => 'Combustible ECO del dispensador.',
			'EX' => 'Combustible EXTRA del dispensador.',
			'DIESEL' => 'Combustible DIESEL del dispensador.',
			'SUPER' => 'Combustible SUPER del dispensador.',
			'ECO' => 'Combustible ECO del dispensador.',
			'EXTRA' => 'Combustible EXTRA del dispensador.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Dis_Uni') {
		$map = array(
			'GA' => 'Unidad en galones.',
			'LI' => 'Unidad en litros.',
			'GALONES' => 'Unidad en galones.',
			'LITROS' => 'Unidad en litros.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Cho_Tip') {
		$map = array(
			'OP' => 'Operario de maquinaria.',
			'CM' => 'Chofer asociado a manifiestos.',
			'CH' => 'Chofer general.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Cel_Tip') {
		$map = array(
			'G' => 'Grupo de celdas / contenedores.',
			'D' => 'Detalle / celda individual.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Pla_Pfa') {
		$map = array(
			'D' => 'Facturacion diaria de la planta.',
			'S' => 'Facturacion semanal de la planta.',
			'M' => 'Facturacion mensual de la planta.',
			'Q' => 'Facturacion quincenal de la planta.'
		);
		if (isset($map[$rawUp])) {
			return $map[$rawUp];
		}
	}
	if ($atr === 'Hum_Cod' && $codigoResuelto) {
		return 'El valor '.$valRaw.' corresponde al nivel de humedad "'.$valNatural.'".';
	}

	// Codigos resueltos a nombre (usuarios anadidos, plantas, clientes, etc.)
	if ($codigoResuelto) {
		$quien = array(
			'Usu_Cod' => 'usuario',
			'Cli_Cod' => 'cliente',
			'Prv_Cod' => 'proveedor',
			'Pla_Cod' => 'planta',
			'Veh_Cod' => 'vehiculo',
			'Cho_Cod' => 'chofer',
			'Pro_Cod' => 'producto',
			'Vnd_Cod' => 'vendedor',
			'Emp_Cod' => 'empresa',
			'Suc_Cod' => 'sucursal',
			'Pcs_Cod' => 'proceso',
			'Org_Cod' => 'modulo o area',
			'Man_Cod' => 'manifiesto',
			'Vet_Cod' => 'venta',
			'Com_Cod' => 'comprobante',
			'Asi_Cod' => 'asiento',
			'Caj_Cod' => 'caja',
			'InvDis_Cod' => 'dispositivo',
			'DisUsr_Cod' => 'asignacion de dispositivo',
			'Hum_Cod' => 'nivel de humedad',
			'Mat_Cod' => 'asignacion tecnica',
			'UsInv_Cod' => 'asignacion de inventario',
			'UsInv_Usu' => 'usuario',
			'Tde_Cod' => 'tipo de desecho',
			'Cel_Cod' => 'celda',
			'Mco_Cod' => 'contrato',
			'Hor_Cod' => 'registro de horometro',
			'Dis_Cod' => 'dispensador',
			'Ama_Cod' => 'anticipo',
			'Cpc_Cod' => 'cuenta por cobrar',
			'Did_Cod' => 'movimiento de dispensador',
			'Mal_Cod' => 'alimentacion / liquidacion',
			'Tud_Cod' => 'turno',
			'Tur_Cod' => 'configuracion de turnos',
			'Prs_Cod' => 'persona',
			'Per_Cod' => 'personal',
			'MVis_Cod' => 'visitante',
			'Msj_Cod' => 'mensaje',
			'Ant_Cod' => 'anticipo contable',
			'Ban_Cod' => 'cuenta bancaria',
			'Bak_Cod' => 'banco',
			'Mcd_Cod' => 'documento de contrato',
			'Maq_Cod' => 'equipo de maquinaria',
			'Com_Cod' => 'comprobante',
			'Vet_Cod' => 'venta',
			'Cta_Cod' => 'cuenta contable',
			'Pld_Cod' => 'detalle de plan de cuentas',
			'Ciu_Cod' => 'ciudad',
			'Ide_Cod' => 'tipo de identificacion',
			'Aut_Cod' => 'autorizacion SRI',
			'Tic_Cod' => 'tipo de comprobante',
			'Tpc_Cod' => 'forma de pago',
			'Pag_Cod' => 'tipo de pago',
			'Pun_Cod' => 'punto de impresion'
		);
		$tipo = isset($quien[$atr]) ? $quien[$atr] : strtolower($eti);
		return 'El valor '.$valRaw.' corresponde al '.$tipo.' "'.$valNatural.'".';
	}

	if ($esCodigo && preg_match('/^\d+$/', $valRaw)) {
		return 'El valor '.$valRaw.' es el codigo interno de "'.$eti.'". No se pudo obtener el nombre asociado.';
	}

	if ($suf === 'num') {
		return 'El valor "'.$valNatural.'" es el numero que identifica este documento.';
	}
	if ($suf === 'fec' || $suf === 'fei' || $suf === 'fef' || ($suf === 'des' && preg_match('/^\d{4}-\d{2}-\d{2}/', $valNatural))) {
		return 'El valor "'.$valNatural.'" es la fecha registrada en este movimiento.';
	}
	if ($suf === 'hor' || $suf === 'hin' || $suf === 'hfi' || $suf === 'hoi' || $suf === 'hof') {
		return 'El valor "'.$valNatural.'" es la hora registrada en este movimiento.';
	}
	if ($suf === 'val' || $suf === 'imp' || $suf === 'pru' || $suf === 'prop' || $suf === 'exi' || $suf === 'pun') {
		return 'El valor "'.$valNatural.'" es el monto monetario registrado.';
	}
	if ($suf === 'pes') {
		return 'El valor "'.$valNatural.'" es el peso registrado.';
	}
	if ($suf === 'can' || $suf === 'cup') {
		return 'El valor "'.$valNatural.'" es la cantidad de unidades registrada.';
	}
	if ($suf === 'obs' || $suf === 'obe' || $suf === 'con' || $atr === 'Mat_Oce' || $atr === 'Mat_Dna') {
		return 'El valor es el texto: "'.$valNatural.'".';
	}
	if ($atr === 'Mat_Sys' || $suf === 'sys') {
		return 'El valor "'.$valNatural.'" es la fecha/hora automatica del sistema al guardar.';
	}
	if ($atr === 'Ses_Ip') {
		return 'El valor "'.$valNatural.'" es la direccion IP de origen de la accion.';
	}

	if (is_array($rowCampo) && !empty($rowCampo['Cam_Des'])) {
		$desCat = trim($rowCampo['Cam_Des']);
		if ($desCat !== '' && strcasecmp($desCat, $atr) !== 0 && strpos($desCat, '_') === false) {
			return 'El valor "'.$valNatural.'" significa: '.$desCat.'.';
		}
	}

	return 'El valor registrado es "'.$valNatural.'".';
}
function aud_parse_cam_val($campos, $valores)
{
	$campos = str_replace('`', '', (string)$campos);
	$campos = str_replace('(', '', $campos);
	$campos = str_replace(')', '', $campos);
	$Arr = explode(',', $campos);
	$valores = trim((string)$valores);
	// INSERT legacy a veces deja parentesis externos: (val1,val2)
	if ($valores !== '' && $valores[0] === '(' && substr($valores, -1) === ')') {
		$valores = substr($valores, 1, -1);
	}
	// Quitar ; residual
	$valores = rtrim($valores, " \t\r\n;");

	// ~...~ delimita textos (puede incluir comas). Si hay ~, parsear quitando
	// delimitadores; las comas internas se protegen con un marcador temporal.
	// Nunca convertir ~ de apertura/cierre en comas (evita ",D," / ",texto,").
	if (strpos($valores, '~') !== false || substr_count($campos, ',') != substr_count($valores, ',')) {
		$count = strlen($valores);
		$str_ = '';
		$contador = 0;
		for ($i = 0; $i < $count; $i++) {
			$char = $valores[$i];
			if ($char === '~') {
				$contador = ($contador == 0) ? 1 : 0;
				continue;
			}
			if ($char === ',' && $contador == 1) {
				$str_ .= "\x01";
			} else {
				$str_ .= $char;
			}
		}
		$Arr_val = explode(',', $str_);
		foreach ($Arr_val as $k => $v) {
			$Arr_val[$k] = str_replace("\x01", ',', $v);
		}
	} else {
		$Arr_val = explode(',', $valores);
	}

	$pares = array();
	$max = count($Arr);
	for ($i = 0; $i < $max; $i++) {
		$atr = trim($Arr[$i]);
		if ($atr === '') {
			continue;
		}
		$val = isset($Arr_val[$i]) ? $Arr_val[$i] : '';
		$val = trim($val, " \t\n\r\0\x0B'\"");
		$val = trim($val, '~');
		$pares[] = array('atr' => $atr, 'val' => $val);
	}
	return $pares;
}

function aud_formato_valor($atr, $val)
{
	$atr = trim($atr);
	$val = trim((string)$val);
	if ($val === '' || strtoupper($val) === 'NULL') {
		return '(sin valor)';
	}
	if ($atr === 'Asi_Deh') {
		$u = strtoupper(trim($val, " \t,"));
		if ($u === 'D') {
			return 'Debe';
		}
		if ($u === 'H') {
			return 'Haber';
		}
	}
	if ($atr === 'Com_Val' || $atr === 'Asi_Val' || $atr === 'Man_Pun' || $atr === 'Ama_Val' || $atr === 'Vet_Pru' || $atr === 'Vet_Imp' || $atr === 'Vet_Prop' || $atr === 'Caj_Exi') {
		if (is_numeric($val)) {
			return number_format((float)$val, 2, '.', ',');
		}
	}
	if ($atr === 'Man_Pes') {
		if (is_numeric($val)) {
			return number_format((float)$val, 2, '.', ',').' kg';
		}
	}
	if ($atr === 'Caj_Est') {
		$u = strtoupper($val);
		if ($u === 'A') {
			return 'Abierta';
		}
		if ($u === 'C') {
			return 'Cerrada';
		}
	}
	if ($atr === 'Ses_Est') {
		$mapSes = array('A' => 'Activa', 'C' => 'Cerrada', 'I' => 'Cerrada por inactividad', 'F' => 'Cierre forzado');
		$u = strtoupper($val);
		if (isset($mapSes[$u])) {
			return $mapSes[$u];
		}
	}
	if ($atr === 'Caj_Gen' || $atr === 'Man_Vig' || $atr === 'Cfg_Activo') {
		$u = strtoupper($val);
		if ($u === 'S' || $u === 'A') {
			return 'Si';
		}
		if ($u === 'N' || $u === 'I') {
			return 'No';
		}
	}
	$estados = array('Man_Est', 'Tud_Est', 'Tur_Est', 'MVis_Est', 'Man_EEst', 'Ama_Est', 'Com_Est', 'Cfg_Est', 'Not_Est');
	$estados = array_merge($estados, array(
		'Mco_Est', 'Mcd_Est', 'Msj_Est', 'Cel_Est', 'Mal_Est', 'Did_Est', 'Dis_Est',
		'Cpc_Est', 'Hum_Est', 'Cli_Est', 'Veh_Est', 'Cho_Est', 'Hor_Est', 'Pla_Est', 'Prs_Est'
	));
	if (in_array($atr, $estados)) {
		$u = strtoupper($val);
		if ($u === 'A') {
			return 'Activo';
		}
		if ($u === 'I') {
			return 'Inactivo';
		}
		if ($u === 'S') {
			return 'Suspendido';
		}
		if ($u === 'C') {
			return 'Cerrado';
		}
	}
	if ($atr === 'Man_Tip') {
		$u = strtoupper($val);
		$mapTip = array(
			'P' => 'Pendiente',
			'C' => 'Comercial',
			'T' => 'Transporte',
			'V' => 'Visitante',
			'GE' => 'Entrada de Volqueta',
			'GI' => 'Entrada de Volqueta',
			'GS' => 'Salida de Volqueta',
			'A' => 'Aprobado por tecnico',
			'F' => 'Facturado',
			'R' => 'Rechazado'
		);
		if (isset($mapTip[$u])) {
			return $mapTip[$u];
		}
	}
	if ($atr === 'Man_Tes') {
		$u = strtoupper($val);
		$mapTes = array(
			'P' => 'Pendiente',
			'GE' => 'Entrada de Volqueta',
			'GI' => 'Entrada de Volqueta',
			'GS' => 'Salida de Volqueta',
			'A' => 'Aprobado',
			'F' => 'Facturado',
			'R' => 'Rechazado'
		);
		if (isset($mapTes[$u])) {
			return $mapTes[$u];
		}
		// Man_Tes a veces concatena estados: "P-GE-A"
		if (strpos($val, '-') !== false) {
			$parts = explode('-', $val);
			$out = array();
			foreach ($parts as $p) {
				$p = strtoupper(trim($p));
				$out[] = isset($mapTes[$p]) ? $mapTes[$p] : $p;
			}
			return implode(' > ', $out);
		}
	}
	if ($atr === 'Veh_Tit') {
		$u = strtoupper(trim($val));
		$mapVeh = array(
			'V' => 'Volqueta',
			'B' => 'Bus(eta)',
			'C' => 'Camioneta',
			'M' => 'Maquinaria',
			'T' => 'Trailer'
		);
		if (isset($mapVeh[$u])) {
			return $mapVeh[$u];
		}
	}
	if ($atr === 'Msj_Tip') {
		$u = strtoupper($val);
		$mapMsj = array('T' => 'Texto', 'I' => 'Imagen', 'A' => 'Alerta', 'Q' => 'QR');
		if (isset($mapMsj[$u])) {
			return $mapMsj[$u];
		}
	}
	if ($atr === 'Hor_Est') {
		$u = strtoupper($val);
		$mapHor = array('P' => 'En proceso', 'C' => 'Completado', 'A' => 'Activo', 'I' => 'Inactivo');
		if (isset($mapHor[$u])) {
			return $mapHor[$u];
		}
	}
	if ($atr === 'Did_Tip') {
		$u = strtoupper($val);
		$mapDid = array(
			'IN' => 'Compra a proveedor',
			'IC' => 'Carga interna / ajuste positivo',
			'ET' => 'Transferencia entrada',
			'SA' => 'Abastecimiento a maquinaria',
			'SC' => 'Ajuste negativo / consumo',
			'ST' => 'Transferencia salida',
			'SI' => 'Saldo inicial'
		);
		if (isset($mapDid[$u])) {
			return $mapDid[$u];
		}
	}
	if ($atr === 'Mal_Tip') {
		$u = strtoupper($val);
		$mapMal = array('D' => 'Desayuno', 'A' => 'Almuerzo', 'M' => 'Merienda', 'C' => 'Cena');
		if (isset($mapMal[$u])) {
			return $mapMal[$u];
		}
	}
	if ($atr === 'Veh_Tip') {
		$u = strtoupper($val);
		$mapVt = array(
			'V' => 'Maquinaria (vehiculos)',
			'O' => 'Otros (equipos)',
			'VM' => 'Vehiculo de manifiesto'
		);
		if (isset($mapVt[$u])) {
			return $mapVt[$u];
		}
	}
	if ($atr === 'Ama_IgV' || $atr === 'Mco_Vig' || $atr === 'Mco_Ren') {
		$u = strtoupper($val);
		if ($u === 'S' || $u === 'A' || $u === '1') {
			return 'Si';
		}
		if ($u === 'N' || $u === 'I' || $u === '0') {
			return 'No';
		}
	}
	if ($atr === 'Prs_Gen' || $atr === 'Prs_Sex') {
		$u = strtoupper($val);
		if ($u === 'M') {
			return 'Masculino';
		}
		if ($u === 'F') {
			return 'Femenino';
		}
	}
	if ($atr === 'Pla_Pfa') {
		$u = strtoupper($val);
		$mapPfa = array('D' => 'Diaria', 'S' => 'Semanal', 'M' => 'Mensual', 'Q' => 'Quincenal');
		if (isset($mapPfa[$u])) {
			return $mapPfa[$u];
		}
	}
	if ($atr === 'Dis_Tip') {
		$u = strtoupper($val);
		$mapDis = array(
			'DI' => 'DIESEL', 'DIESEL' => 'DIESEL',
			'SU' => 'SUPER', 'SUPER' => 'SUPER',
			'EC' => 'ECO', 'ECO' => 'ECO',
			'EX' => 'EXTRA', 'EXTRA' => 'EXTRA'
		);
		if (isset($mapDis[$u])) {
			return $mapDis[$u];
		}
	}
	if ($atr === 'Dis_Uni') {
		$u = strtoupper($val);
		$mapUni = array('GA' => 'Galones', 'GALONES' => 'Galones', 'LI' => 'Litros', 'LITROS' => 'Litros');
		if (isset($mapUni[$u])) {
			return $mapUni[$u];
		}
	}
	if ($atr === 'Cho_Tip') {
		$u = strtoupper($val);
		$mapCho = array('OP' => 'Operario', 'CM' => 'Chofer de manifiesto', 'CH' => 'Chofer');
		if (isset($mapCho[$u])) {
			return $mapCho[$u];
		}
	}
	if ($atr === 'Cel_Tip') {
		$u = strtoupper($val);
		$mapCel = array('G' => 'Grupo', 'D' => 'Detalle');
		if (isset($mapCel[$u])) {
			return $mapCel[$u];
		}
	}
	if ($atr === 'Prs_Esc') {
		$u = strtoupper($val);
		$mapEsc = array(
			'S' => 'Soltero/a',
			'C' => 'Casado/a',
			'D' => 'Divorciado/a',
			'V' => 'Viudo/a',
			'U' => 'Union libre'
		);
		if (isset($mapEsc[$u])) {
			return $mapEsc[$u];
		}
	}
	if ($atr === 'Per_Tit') {
		$u = trim($val);
		$mapTit = array(
			'Np' => 'No posee', 'NP' => 'No posee',
			'Abg' => 'Abogado/a', 'ABG' => 'Abogado/a',
			'Bac' => 'Bachiller', 'BAC' => 'Bachiller',
			'Dr' => 'Doctor/a', 'DR' => 'Doctor/a',
			'Eco' => 'Economista', 'ECO' => 'Economista',
			'Ing' => 'Ingeniero/a', 'ING' => 'Ingeniero/a',
			'Lcd' => 'Licenciado/a', 'LCD' => 'Licenciado/a'
		);
		if (isset($mapTit[$u])) {
			return $mapTit[$u];
		}
	}
	/* Codigos de asignacion tecnica (manifiesto_tecnico) */
	if ($atr === 'Mat_Eae') {
		$map = array('A' => 'Aceptado', 'R' => 'Rechazado', 'AC' => 'Aceptado con condicion');
		$u = strtoupper($val);
		if (isset($map[$u])) {
			return $map[$u];
		}
	}
	if ($atr === 'Mat_Ear') {
		$map = array(
			'TR' => 'Transporte',
			'AT' => 'Almacenamiento temporal',
			'EL' => 'Eliminacion',
			'DF' => 'Disposicion final',
			'CT' => 'Cierre tecnico'
		);
		$u = strtoupper($val);
		if (isset($map[$u])) {
			return $map[$u];
		}
	}
	if ($atr === 'Mat_Tra') {
		$map = array('AT' => 'Almacenamiento temporal', 'DF' => 'Disposicion final');
		$u = strtoupper($val);
		if (isset($map[$u])) {
			return $map[$u];
		}
	}
	$estadosInv = array('Mat_Est', 'UsInv_Est', 'InvDis_Est', 'DisUsr_Est');
	if (in_array($atr, $estadosInv)) {
		$u = strtoupper($val);
		if ($u === 'A') {
			return 'Activo';
		}
		if ($u === 'I') {
			return 'Inactivo';
		}
	}
	return $val;
}

function aud_det_conexion_mysqli($obBD_conexion)
{
	if (is_object($obBD_conexion) && !empty($obBD_conexion->conexion)) {
		return $obBD_conexion->conexion;
	}
	if (is_object($obBD_conexion)) {
		return $obBD_conexion;
	}
	return null;
}

function aud_valor_codigo_desde_row($atr, $val, $row)
{
	$atr = trim((string)$atr);
	$val = trim((string)$val);
	if ($val === '') {
		return '';
	}
	if ($atr === 'Emp_Cod' && !empty($row['Emp_Nom'])) {
		return trim($row['Emp_Nom']);
	}
	if ($atr === 'Suc_Cod' && !empty($row['Suc_Des'])) {
		return trim($row['Suc_Des']);
	}
	if ($atr === 'Usu_Cod' && !empty($row['Usu_Nom'])) {
		return trim($row['Usu_Nom']);
	}
	if ($atr === 'Pcs_Cod') {
		$pcs = aud_nombre_proceso($row);
		if ($pcs !== '' && $pcs !== 'Proceso no registrado') {
			return $pcs;
		}
	}
	return '';
}

function aud_db_dis_para_lookup($row)
{
	$datDis = '';
	if (!empty($row['Dat_Dis'])) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', $row['Dat_Dis']);
	}
	if ($datDis === '' && !empty($_SESSION['Ses_Dat_Dis'])) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']);
	}
	if ($datDis === '' && !empty($GLOBALS['Ses_Dat_Dis'])) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', $GLOBALS['Ses_Dat_Dis']);
	}
	if ($datDis === '' && function_exists('aud_sql_db_dis')) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace('`', '', aud_sql_db_dis()));
	}
	if ($datDis === '' || $datDis === 'servicios' || $datDis === 'exa') {
		$datDis = 'ecoparkmining';
	}
	return $datDis;
}

function aud_db_master_para_lookup()
{
	$master = 'exa_master';
	if (class_exists('Env')) {
		$cand = preg_replace('/[^a-zA-Z0-9_]/', '', (string)\Env::get('DB_DATABASE', 'exa_master'));
		if ($cand !== '') {
			$master = $cand;
		}
	}
	return $master;
}

function aud_valor_codigo_lookup($atr, $val, $row, $obBD_conexion)
{
	$atr = trim((string)$atr);
	$val = trim((string)$val);
	if ($val === '' || !preg_match('/^\d+$/', $val)) {
		return '';
	}
	$con = aud_det_conexion_mysqli($obBD_conexion);
	if (!$con) {
		return '';
	}
	$datDis = aud_db_dis_para_lookup($row);
	$master = aud_db_master_para_lookup();
	$id = (int)$val;
	$sql = '';
	if ($atr === 'Emp_Cod') {
		$sql = "SELECT `Emp_Nom` AS `Nom` FROM `{$master}`.`empresas` WHERE `Emp_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Suc_Cod') {
		$sql = "SELECT `Suc_Des` AS `Nom` FROM `{$master}`.`sucursal` WHERE `Suc_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Usu_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`usuarios` u
			LEFT JOIN `{$datDis}`.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
			WHERE u.`Usu_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pcs_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Pcs_Lin`),''), IFNULL(NULLIF(TRIM(`Pcs_Det`),''), `Pcs_Nom`)) AS `Nom`
			FROM `{$datDis}`.`procesos` WHERE `Pcs_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Cli_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(c.`Cli_Nom`),''), TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,'')))) AS `Nom`
			FROM `{$datDis}`.`cliente` c
			LEFT JOIN `{$datDis}`.`persona` p ON c.`Prs_Cod` = p.`Prs_Cod`
			WHERE c.`Cli_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Prv_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(pr.`Prv_Com`),''), TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,'')))) AS `Nom`
			FROM `{$datDis}`.`proveedore` pr
			LEFT JOIN `{$datDis}`.`persona` p ON pr.`Prs_Cod` = p.`Prs_Cod`
			WHERE pr.`Prv_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pla_Cod') {
		$sql = "SELECT `Pla_Nom` AS `Nom` FROM `{$datDis}`.`manifiesto_plantas` WHERE `Pla_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Tde_Cod') {
		$sql = "SELECT CONCAT(IFNULL(NULLIF(TRIM(`Tde_Cde`),''), ''), IF(IFNULL(TRIM(`Tde_Cde`),'')<>'' AND IFNULL(TRIM(`Tde_Des`),'')<>'',' - ',''), IFNULL(NULLIF(TRIM(`Tde_Des`),''), CONCAT('Desecho ',`Tde_Cod`))) AS `Nom`
			FROM `{$datDis}`.`manifiesto_desechos` WHERE `Tde_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Cel_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Cel_Nom`),''), IFNULL(NULLIF(TRIM(`Cel_Num`),''), CONCAT('Celda ',`Cel_Cod`))) AS `Nom`
			FROM `{$datDis}`.`manifiesto_celdas` WHERE `Cel_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Veh_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Veh_Pla`),''), IFNULL(NULLIF(TRIM(`Veh_Des`),''), CONCAT('Vehiculo ',`Veh_Cod`))) AS `Nom`
			FROM `{$datDis}`.`vehiculo` WHERE `Veh_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Cho_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`chofer` c
			LEFT JOIN `{$datDis}`.`persona` p ON c.`Prs_Cod` = p.`Prs_Cod`
			WHERE c.`Cho_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pro_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Pro_Nom`),''), IFNULL(NULLIF(TRIM(`Pro_Des`),''), CONCAT('Producto ',`Pro_Cod`))) AS `Nom`
			FROM `{$datDis}`.`productos` WHERE `Pro_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Vnd_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`vendedores` v
			LEFT JOIN `{$datDis}`.`persona` p ON v.`Prs_Cod` = p.`Prs_Cod`
			WHERE v.`Vnd_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Org_Cod') {
		$sql = "SELECT `Org_Des` AS `Nom` FROM `{$datDis}`.`organizado` WHERE `Org_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Hum_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Hum_Des`),''), CONCAT('Humedad ',`Hum_Cod`)) AS `Nom`
			FROM `{$datDis}`.`manifiesto_nivel_humedad` WHERE `Hum_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Man_Cod') {
		$sql = "SELECT CONCAT('M', IFNULL(`Pla_Cod`,''), '-', LPAD(IFNULL(`Man_Num`,0), 4, '0')) AS `Nom`
			FROM `{$datDis}`.`manifiesto` WHERE `Man_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'InvDis_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`InvDis_Nom`),''), IFNULL(NULLIF(TRIM(`mac_address`),''), CONCAT('Dispositivo ',`InvDis_Cod`))) AS `Nom`
			FROM `{$datDis}`.`inventario_dispositivos` WHERE `InvDis_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'UsInv_Usu') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`usuarios` u
			LEFT JOIN `{$datDis}`.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
			WHERE u.`Usu_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Mat_Cod') {
		// Puede ser asignacion tecnica o empresa de transporte.
		$sql = "SELECT CONCAT('Asignacion tecnica #', `Mat_Cod`) AS `Nom`
			FROM `{$datDis}`.`manifiesto_tecnico` WHERE `Mat_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Dis_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Dis_Nom`),''), CONCAT('Dispensador ',`Dis_Cod`)) AS `Nom`
			FROM `{$datDis}`.`maquinaria_dispensador` WHERE `Dis_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Ama_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Ama_Doc`),''), CONCAT('Anticipo ',`Ama_Cod`)) AS `Nom`
			FROM `{$datDis}`.`manifiesto_anticipo` WHERE `Ama_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Cpc_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Cpc_Cxc`),''), CONCAT('CxC ',`Cpc_Cod`)) AS `Nom`
			FROM `{$datDis}`.`ccpp_cobrar` WHERE `Cpc_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Mco_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Mco_Num`),''), CONCAT('Contrato ',`Mco_Cod`)) AS `Nom`
			FROM `{$datDis}`.`manifiesto_contratos` WHERE `Mco_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Mcd_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Mcd_Nom`),''), CONCAT('Documento ',`Mcd_Cod`)) AS `Nom`
			FROM `{$datDis}`.`manifiesto_contratos_docu` WHERE `Mcd_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Ant_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Ant_Doc`),''), CONCAT('Anticipo ',`Ant_Cod`)) AS `Nom`
			FROM `{$datDis}`.`anticipos_clientes` WHERE `Ant_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Bak_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Bak_Des`),''), CONCAT('Banco ',`Bak_Cod`)) AS `Nom`
			FROM `{$datDis}`.`bancos` WHERE `Bak_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Ban_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(bk.`Bak_Des`,''), IF(IFNULL(bk.`Bak_Des`,'')<>'' AND IFNULL(b.`Ban_Cue`,'')<>'',' - ',''), IFNULL(b.`Ban_Cue`, CONCAT('Cuenta ',b.`Ban_Cod`)))) AS `Nom`
			FROM `{$datDis}`.`banco` b
			LEFT JOIN `{$datDis}`.`bancos` bk ON bk.`Bak_Cod` = b.`Bak_Cod`
			WHERE b.`Ban_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Tud_Cod') {
		$sql = "SELECT CONCAT(IFNULL(DATE_FORMAT(d.`Tud_Fec`,'%d/%m/%Y'),''), ' ', IFNULL(TIME_FORMAT(d.`Tud_Hin`,'%H:%i'),''), '-', IFNULL(TIME_FORMAT(d.`Tud_Hfi`,'%H:%i'),''), ' (#', d.`Tud_Cod`, ')') AS `Nom`
			FROM `{$datDis}`.`manifiesto_turnos_det` d WHERE d.`Tud_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Tur_Cod') {
		$sql = "SELECT CONCAT('Turno #', c.`Tur_Cod`, IFNULL(CONCAT(' ', DATE_FORMAT(c.`Tur_Fei`,'%d/%m/%Y'), '-', DATE_FORMAT(c.`Tur_Fef`,'%d/%m/%Y')), '')) AS `Nom`
			FROM `{$datDis}`.`manifiesto_turnos_cab` c WHERE c.`Tur_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'MVis_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''), IF(IFNULL(p.`Prs_Ced`,'')<>'', CONCAT(' (',p.`Prs_Ced`,')'), ''))) AS `Nom`
			FROM `{$datDis}`.`manifiesto_visitante` mv
			LEFT JOIN `{$datDis}`.`persona` p ON p.`Prs_Cod` = mv.`Prs_Cod`
			WHERE mv.`MVis_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Msj_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(LEFT(`Msj_Tex`,80)),''), CONCAT('Mensaje ',`Msj_Cod`)) AS `Nom`
			FROM `{$datDis}`.`manifiesto_mensajes` WHERE `Msj_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Hor_Cod') {
		$sql = "SELECT CONCAT(IFNULL(DATE_FORMAT(h.`Hor_Fec`,'%d/%m/%Y'),''), ' ', IFNULL(v.`Veh_Pla`,''), ' (#', h.`Hor_Cod`, ')') AS `Nom`
			FROM `{$datDis}`.`maquinaria_horometro` h
			LEFT JOIN `{$datDis}`.`vehiculo` v ON v.`Veh_Cod` = h.`Veh_Cod`
			WHERE h.`Hor_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Mal_Cod') {
		$sql = "SELECT CONCAT(IFNULL(DATE_FORMAT(`Mal_Fec`,'%d/%m/%Y'),''), IF(IFNULL(`Mal_Tip`,'')<>'', CONCAT(' ',`Mal_Tip`),''), ' (#',`Mal_Cod`,')') AS `Nom`
			FROM `{$datDis}`.`maquinaria_alimentacion` WHERE `Mal_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Did_Cod') {
		$sql = "SELECT CONCAT(IFNULL(d.`Dis_Nom`,''), ' ', IFNULL(md.`Did_Tip`,''), ' ', IFNULL(md.`Did_Can`,''), ' (#', md.`Did_Cod`, ')') AS `Nom`
			FROM `{$datDis}`.`maquinaria_dispensador_det` md
			LEFT JOIN `{$datDis}`.`maquinaria_dispensador` d ON d.`Dis_Cod` = md.`Dis_Cod`
			WHERE md.`Did_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Prs_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(`Prs_Ape`,''),' ',IFNULL(`Prs_Nom`,''), IF(IFNULL(`Prs_Ced`,'')<>'', CONCAT(' (',`Prs_Ced`,')'), ''))) AS `Nom`
			FROM `{$datDis}`.`persona` WHERE `Prs_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Per_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`personal` pe
			LEFT JOIN `{$datDis}`.`persona` p ON p.`Prs_Cod` = pe.`Prs_Cod`
			WHERE pe.`Per_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Maq_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Maq_Ser`),''), CONCAT('Equipo ',`Maq_Cod`)) AS `Nom`
			FROM `{$datDis}`.`maquinaria_equipo` WHERE `Maq_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Com_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Com_Num`),''), CONCAT('Comprobante ',`Com_Cod`)) AS `Nom`
			FROM `{$datDis}`.`comprobantes` WHERE `Com_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Vet_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Vet_Num`),''), CONCAT('Venta ',`Vet_Cod`)) AS `Nom`
			FROM `{$datDis}`.`ventas` WHERE `Vet_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Cta_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Cta_Nom`),''), IFNULL(NULLIF(TRIM(`Cta_CodC`),''), CONCAT('Cuenta ',`Cta_Cod`))) AS `Nom`
			FROM `{$datDis}`.`cuentas` WHERE `Cta_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pld_Cod') {
		$sql = "SELECT CONCAT(IFNULL(NULLIF(TRIM(`Pld_Cdc`),''), ''), IF(IFNULL(TRIM(`Pld_Cdc`),'')<>'' AND IFNULL(TRIM(`Pld_Des`),'')<>'',' - ',''), IFNULL(NULLIF(TRIM(`Pld_Des`),''), CONCAT('Cuenta ',`Pld_Cod`))) AS `Nom`
			FROM `{$datDis}`.`det_plan` WHERE `Pld_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Ciu_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Ciu_Des`),''), CONCAT('Ciudad ',`Ciu_Cod`)) AS `Nom`
			FROM `{$datDis}`.`ciudad` WHERE `Ciu_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Ide_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Ide_Des`),''), CONCAT('Identificacion ',`Ide_Cod`)) AS `Nom`
			FROM `{$datDis}`.`identifica` WHERE `Ide_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Aut_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Aut_Sri`),''), CONCAT('Autorizacion ',`Aut_Cod`)) AS `Nom`
			FROM `{$datDis}`.`autorizaci` WHERE `Aut_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Tic_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Tic_Des`),''), CONCAT('Tipo comprobante ',`Tic_Cod`)) AS `Nom`
			FROM `{$datDis}`.`tipo_compr` WHERE `Tic_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Tpc_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Tpc_Des`),''), CONCAT('Forma pago ',`Tpc_Cod`)) AS `Nom`
			FROM `{$datDis}`.`tipopagocom` WHERE `Tpc_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pag_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Pag_Des`),''), CONCAT('Tipo pago ',`Pag_Cod`)) AS `Nom`
			FROM `{$datDis}`.`tipos_pago` WHERE `Pag_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pun_Cod') {
		$sql = "SELECT CONCAT('Punto de impresion ',`Pun_Cod`) AS `Nom`
			FROM `{$datDis}`.`puntos_imp` WHERE `Pun_Cod`={$id} LIMIT 1";
	}
	if ($sql === '') {
		if ($atr === 'Hum_Cod') {
			$mapHum = array(1 => 'Mayor de 14 - ALTO', 2 => 'Menor a 14 - BAJO');
			return isset($mapHum[$id]) ? $mapHum[$id] : '';
		}
		return '';
	}
	$rs = @mysqli_query($con, $sql);
	$nom = '';
	if ($rs) {
		$reg = mysqli_fetch_assoc($rs);
		mysqli_free_result($rs);
		$nom = !empty($reg['Nom']) ? trim($reg['Nom']) : '';
	}
	// Fallback conocido del formulario de tecnicos si la tabla de humedad no responde.
	if ($nom === '' && $atr === 'Hum_Cod') {
		$mapHum = array(
			1 => 'Mayor de 14 - ALTO',
			2 => 'Menor a 14 - BAJO'
		);
		if (isset($mapHum[$id])) {
			return $mapHum[$id];
		}
	}
	// Mat_Cod: si no es tecnico, intentar empresa de transporte.
	if ($nom === '' && $atr === 'Mat_Cod') {
		$rs2 = @mysqli_query($con, "SELECT IFNULL(NULLIF(TRIM(`Mat_Des`),''), CONCAT('Transporte ',`Mat_Cod`)) AS `Nom`
			FROM `{$datDis}`.`manifiesto_transporte` WHERE `Mat_Cod`={$id} LIMIT 1");
		if ($rs2 && ($reg2 = mysqli_fetch_assoc($rs2))) {
			$nom = !empty($reg2['Nom']) ? trim($reg2['Nom']) : '';
		}
		if ($rs2) {
			mysqli_free_result($rs2);
		}
	}
	// Mal_Cod: puede ser preliquidacion (manifiesto_liquidacion_maq).
	if ($nom === '' && $atr === 'Mal_Cod') {
		$rs2 = @mysqli_query($con, "SELECT IFNULL(NULLIF(TRIM(`Mal_Num`),''), CONCAT('Preliquidacion ',`Mal_Cod`)) AS `Nom`
			FROM `{$datDis}`.`manifiesto_liquidacion_maq` WHERE `Mal_Cod`={$id} LIMIT 1");
		if ($rs2 && ($reg2 = mysqli_fetch_assoc($rs2))) {
			$nom = !empty($reg2['Nom']) ? trim($reg2['Nom']) : '';
		}
		if ($rs2) {
			mysqli_free_result($rs2);
		}
	}
	// Cli_Cod: algunas BD usan clientes (plural).
	if ($nom === '' && $atr === 'Cli_Cod') {
		$rs2 = @mysqli_query($con, "SELECT IFNULL(NULLIF(TRIM(c.`Cli_Nom`),''), TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,'')))) AS `Nom`
			FROM `{$datDis}`.`clientes` c
			LEFT JOIN `{$datDis}`.`persona` p ON c.`Prs_Cod` = p.`Prs_Cod`
			WHERE c.`Cli_Cod`={$id} LIMIT 1");
		if ($rs2 && ($reg2 = mysqli_fetch_assoc($rs2))) {
			$nom = !empty($reg2['Nom']) ? trim($reg2['Nom']) : '';
		}
		if ($rs2) {
			mysqli_free_result($rs2);
		}
	}
	return $nom;
}

function aud_valor_natural($atr, $val, $row, $obBD_conexion = null)
{
	$base = aud_formato_valor($atr, $val);
	$nom = aud_valor_codigo_desde_row($atr, $val, $row);
	if ($nom === '') {
		$nom = aud_valor_codigo_lookup($atr, $val, $row, $obBD_conexion);
	}
	if ($nom !== '') {
		return $nom;
	}
	return $base;
}

function aud_humanizar_campo($atr)
{
	$atr = trim(str_replace('`', '', (string)$atr));
	$map = aud_etiquetas_campo();
	if (isset($map[$atr])) {
		return $map[$atr];
	}
	$partes = explode('_', $atr);
	if (count($partes) >= 2) {
		$pref = strtoupper($partes[0]);
		$suf = strtolower($partes[count($partes) - 1]);
		$prefNom = array(
			'COM' => 'comprobante',
			'ASI' => 'asiento',
			'CTA' => 'cuenta',
			'PRV' => 'proveedor',
			'CLI' => 'cliente',
			'USU' => 'usuario',
			'EMP' => 'empresa',
			'SUC' => 'sucursal',
			'PEC' => 'periodo',
			'MAN' => 'manifiesto',
			'TUD' => 'turno',
			'TUR' => 'turno',
			'VEH' => 'vehiculo',
			'CHO' => 'chofer',
			'PLA' => 'planta',
			'AMA' => 'anticipo',
			'VET' => 'venta',
			'TIC' => 'comprobante',
			'VND' => 'vendedor',
			'CAJ' => 'caja',
			'PRO' => 'producto',
			'ORG' => 'modulo',
			'CFG' => 'configuracion',
			'NOT' => 'notificacion',
			'SES' => 'sesion',
			'INV' => 'inventario',
			'DIS' => 'dispositivo',
			'MAT' => 'tecnico',
			'HUM' => 'humedad',
			'USINV' => 'asignacion',
			'TDE' => 'desecho',
			'CEL' => 'celda',
			'MCO' => 'contrato',
			'MCD' => 'documento',
			'MSJ' => 'mensaje',
			'HOR' => 'horometro',
			'MVIS' => 'visitante',
			'ANT' => 'anticipo',
			'BAN' => 'cuenta bancaria',
			'BAK' => 'banco',
			'PER' => 'personal',
			'PRS' => 'persona',
			'MAQ' => 'equipo',
			'PLD' => 'cuenta contable',
			'DID' => 'movimiento',
			'MAL' => 'alimentacion'
		);
		$sufNom = array(
			'cod' => 'codigo',
			'num' => 'numero',
			'fec' => 'fecha',
			'con' => 'concepto',
			'val' => 'valor',
			'est' => 'estado',
			'des' => 'descripcion',
			'nom' => 'nombre',
			'obs' => 'observacion',
			'hor' => 'hora',
			'ip' => 'direccion IP',
			'dna' => 'desecho no aprobado',
			'eae' => 'estado ambiental',
			'ear' => 'estado de accion',
			'oce' => 'observacion',
			'tra' => 'tratamiento',
			'fde' => 'fecha',
			'sys' => 'fecha/hora del sistema',
			'fes' => 'fecha de salida',
			'fea' => 'fecha de llegada',
			'gui' => 'guia de remision',
			'lac' => 'licencia ambiental',
			'dsa' => 'origen del desecho',
			'dde' => 'destino del desecho',
			'rut' => 'ruta',
			'tes' => 'estado operativo',
			'pes' => 'peso',
			'pun' => 'tarifa'
		);
		$izq = isset($prefNom[$pref]) ? $prefNom[$pref] : strtolower($partes[0]);
		$der = isset($sufNom[$suf]) ? $sufNom[$suf] : strtolower($partes[count($partes) - 1]);
		return ucfirst($der).' de '.$izq;
	}
	return $atr;
}

function aud_codigo_tip_operativo($raw)
{
	$raw = strtoupper(trim((string)$raw));
	if ($raw === '') {
		return '';
	}
	// Man_Tes a veces concatena: "P-GE-A"
	if (strpos($raw, '-') !== false) {
		$parts = explode('-', $raw);
		$raw = strtoupper(trim(end($parts)));
	}
	if ($raw === 'GE' || $raw === 'GI') {
		return 'E';
	}
	if ($raw === 'GS') {
		return 'S';
	}
	return $raw;
}

/**
 * Accion operativa de guardia/garita sobre volqueta (no "Modificar").
 * Lee Man_Tip / Man_Tes / bitacora Man_Usu del log.
 *
 * @param array $row
 * @param array|null $pares
 * @return string Entrada de Volqueta | Salida de Volqueta | ''
 */
function aud_accion_volqueta($row, $pares = null)
{
	$tab = isset($row['Tab_Nom']) ? strtolower(trim($row['Tab_Nom'])) : '';
	$pcs = strtolower(
		trim(
			(isset($row['Pcs_Lin']) ? $row['Pcs_Lin'] : '').' '.
			(isset($row['Pcs_Nom']) ? $row['Pcs_Nom'] : '').' '.
			(isset($row['Pcs_Det']) ? $row['Pcs_Det'] : '').' '.
			(isset($row['Dir_Des']) ? $row['Dir_Des'] : '').' '.
			(isset($row['Org_Des']) ? $row['Org_Des'] : '')
		)
	);
	$esManifiesto = ($tab === 'manifiesto' || strpos($tab, 'manifiesto') !== false);
	$esGuardiaTec = (strpos($pcs, 'guardia') !== false || strpos($pcs, 'garita') !== false
		|| strpos($pcs, 'tecnic') !== false || strpos($pcs, 'volqueta') !== false);
	if (!$esManifiesto && !$esGuardiaTec) {
		return '';
	}
	if (!is_array($pares)) {
		$pares = aud_parse_cam_val(
			isset($row['Log_Cam']) ? $row['Log_Cam'] : '',
			isset($row['Log_Val']) ? $row['Log_Val'] : ''
		);
	}
	$codigo = '';
	if (is_array($pares)) {
		foreach ($pares as $p) {
			$atr = isset($p['atr']) ? $p['atr'] : '';
			$val = isset($p['val']) ? $p['val'] : (isset($p['val_raw']) ? $p['val_raw'] : '');
			if ($atr === 'Man_Tip' || $atr === 'Man_Tes') {
				$codigo = aud_codigo_tip_operativo($val);
				if ($codigo === 'E' || $codigo === 'S') {
					break;
				}
			}
		}
		if ($codigo !== 'E' && $codigo !== 'S') {
			foreach ($pares as $p) {
				$atr = isset($p['atr']) ? $p['atr'] : '';
				$val = isset($p['val']) ? $p['val'] : (isset($p['val_raw']) ? $p['val_raw'] : '');
				if ($atr !== 'Man_Usu' || $val === '') {
					continue;
				}
				if (preg_match_all('/["\']Man_Tip["\']\s*:\s*["\'](GE|GI|GS)["\']/i', $val, $mm)) {
					$last = strtoupper(end($mm[1]));
					$codigo = aud_codigo_tip_operativo($last);
					if ($codigo === 'E' || $codigo === 'S') {
						break;
					}
				}
			}
		}
	}
	if ($codigo === 'E') {
		return 'Entrada de Volqueta';
	}
	if ($codigo === 'S') {
		return 'Salida de Volqueta';
	}
	return '';
}

function aud_resumen_actividad($row, $obBD_conexion = null)
{
	$accionVolq = aud_accion_volqueta($row);
	if ($accionVolq !== '') {
		$ident = aud_identificador($row, $obBD_conexion);
		if ($ident !== '') {
			return $accionVolq.' - '.$ident;
		}
		return $accionVolq;
	}
	$verbo = aud_verbo_evento(isset($row['Eve_Ini']) ? $row['Eve_Ini'] : '', isset($row['Eve_Des']) ? $row['Eve_Des'] : '');
	$obj = aud_nombre_registro(
		isset($row['Tab_Nom']) ? $row['Tab_Nom'] : '',
		isset($row['Tab_Ali']) ? $row['Tab_Ali'] : '',
		isset($row['Tab_Des']) ? $row['Tab_Des'] : ''
	);
	$ident = aud_identificador($row, $obBD_conexion);
	if ($ident !== '') {
		return $verbo.' un '.$obj.' - '.$ident;
	}
	return $verbo.' un '.$obj;
}

function aud_pares_interpretados($row, $obBD_con1, $obBD_conexion)
{
	$pares = aud_parse_cam_val(
		isset($row['Log_Cam']) ? $row['Log_Cam'] : '',
		isset($row['Log_Val']) ? $row['Log_Val'] : ''
	);
	$reconstruido = false;
	// Logs antiguos con Log_Cam/Log_Val vacios (bug NOW();): reconstruir
	// desde el registro vivo usando la referencia (Log_Int / insert_id).
	if (count($pares) === 0 && $obBD_conexion) {
		$paresFallback = aud_pares_desde_referencia($row, $obBD_conexion);
		if (count($paresFallback) > 0) {
			$pares = $paresFallback;
			$reconstruido = true;
		}
	}
	$out = array();
	foreach ($pares as $p) {
		$rowCampo = null;
		$atr = isset($p['atr']) ? $p['atr'] : '';
		$valRaw = isset($p['val']) ? $p['val'] : '';
		if ($obBD_con1 && $obBD_conexion && !empty($row['Tab_Cod']) && $atr !== '') {
			$rowCampo = $obBD_con1->getRowConsulta(8, $row['Tab_Cod'].'*'.$atr, $obBD_conexion);
		}
		$valNat = aud_valor_natural($atr, $valRaw, $row, $obBD_conexion);
		$item = array(
			'atr' => $atr,
			'eti' => aud_to_utf8(aud_etiqueta_campo($atr, $rowCampo)),
			'val' => aud_to_utf8($valNat),
			'val_raw' => aud_to_utf8($valRaw),
			'des' => aud_to_utf8(aud_descripcion_meta($atr, $valNat, $valRaw, $rowCampo))
		);
		if ($reconstruido || !empty($p['reconstruido'])) {
			$item['reconstruido'] = true;
			$item['des'] = 'Valor actual del registro (el log original no guardo el detalle). '.$item['des'];
		}
		$out[] = $item;
	}
	return $out;
}

/**
 * Claves de referencia a partir de Log_Int.
 * Soporta "Mat_Cod=62617", "Mat_Cod = '62617'", "WHERE Mat_Cod=62617" o solo "62617".
 *
 * @param string $logInt
 * @return array lista de {col, val}
 */
function aud_ref_keys_desde_int($logInt)
{
	$int = trim(str_replace('~', '', (string)$logInt));
	if ($int === '') {
		return array();
	}
	$int = preg_replace('/Pcs_Nom=[^|;]+\s*\|\|\s*/', '', $int);
	$int = preg_replace('/\s*\|\|\s*OLD:.*$/s', '', $int);
	$int = preg_replace('/^\s*WHERE\s+/i', '', trim($int));
	$out = array();
	if (preg_match_all('/([A-Za-z_][A-Za-z0-9_]*)\s*=\s*[\'"]?([^\'"\s|,;]+)[\'"]?/', $int, $m, PREG_SET_ORDER)) {
		foreach ($m as $hit) {
			$col = $hit[1];
			$val = $hit[2];
			if (strcasecmp($col, 'Pcs_Nom') === 0) {
				continue;
			}
			$out[] = array('col' => $col, 'val' => $val);
		}
	}
	if (count($out) === 0 && preg_match('/^\d+$/', $int)) {
		$out[] = array('col' => '', 'val' => $int);
	}
	return $out;
}

/**
 * Candidatos de PK segun nombre de tabla cuando Log_Int es solo un numero.
 *
 * @param string $tabNom
 * @return array
 */
function aud_pk_candidatos_tabla($tabNom)
{
	$t = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabNom));
	$map = array(
		'manifiesto_tecnico' => array('Mat_Cod'),
		'manifiesto' => array('Man_Cod'),
		'manifiesto_contratos' => array('Mco_Cod'),
		'manifiesto_contratos_docu' => array('Mcd_Cod'),
		'manifiesto_visitante' => array('Mvi_Cod', 'Vis_Cod'),
		'manifiesto_evento' => array('Mev_Cod', 'Eve_Cod'),
		'manifiesto_turnos_cab' => array('Mtc_Cod', 'Tur_Cod'),
		'manifiesto_turnos_det' => array('Mtd_Cod'),
		'manifiesto_mensajes' => array('Msj_Cod', 'Msj_Id'),
		'inventario_dispositivos' => array('InvDis_Cod'),
		'usuario_inventario' => array('UsInv_Cod'),
		'dispositivos_usuario' => array('DisUsr_Cod'),
		'maquinaria_horometro' => array('Hor_Cod'),
		'maquinaria_alimentacion' => array('Mal_Cod'),
		'maquinaria_dispensador_det' => array('Did_Cod'),
		'manifiesto_liquidacion_maq' => array('Mal_Cod', 'Mlq_Cod'),
		'caja_aper' => array('Caj_Cod'),
		'ventas' => array('Vet_Cod'),
		'ventas_det' => array('Ved_Cod', 'Vet_Cod'),
		'ccpp_cobrar' => array('Cpc_Cod'),
		'comprobantes' => array('Com_Cod'),
		'asientos' => array('Asi_Cod')
	);
	if (isset($map[$t])) {
		return $map[$t];
	}
	// Heuristica: prefijo de 3 letras + _Cod (Mat_Cod, Man_Cod, ...)
	$parts = explode('_', $t);
	$pref = '';
	foreach (str_split($parts[0]) as $i => $ch) {
		if ($i >= 3) {
			break;
		}
		$pref .= $ch;
	}
	$pref = ucfirst($pref);
	$cands = array();
	if ($pref !== '') {
		$cands[] = $pref.'_Cod';
	}
	$cands[] = 'id';
	$cands[] = 'Cod';
	return $cands;
}

/**
 * Reconstruye pares campo/valor leyendo el registro actual en la BD distribuida.
 *
 * @param array $row log
 * @param object $obBD_conexion
 * @return array
 */
function aud_pares_desde_referencia($row, $obBD_conexion)
{
	$tab = isset($row['Tab_Nom']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string)$row['Tab_Nom']) : '';
	if ($tab === '') {
		return array();
	}
	$con = aud_det_conexion_mysqli($obBD_conexion);
	if (!$con) {
		return array();
	}
	$dbDis = aud_db_dis_para_lookup($row);
	if ($dbDis === '') {
		return array();
	}
	$keys = aud_ref_keys_desde_int(isset($row['Log_Int']) ? $row['Log_Int'] : '');
	if (count($keys) === 0) {
		return array();
	}

	$whereParts = array();
	foreach ($keys as $k) {
		$col = isset($k['col']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $k['col']) : '';
		$val = isset($k['val']) ? $k['val'] : '';
		if ($val === '') {
			continue;
		}
		$valEsc = mysqli_real_escape_string($con, $val);
		if ($col !== '') {
			$whereParts[] = "`{$col}`='{$valEsc}'";
		} else {
			// Solo numero: probar candidatos de PK
			foreach (aud_pk_candidatos_tabla($tab) as $pk) {
				$pk = preg_replace('/[^a-zA-Z0-9_]/', '', $pk);
				if ($pk === '') {
					continue;
				}
				$sqlTry = "SELECT * FROM `{$dbDis}`.`{$tab}` WHERE `{$pk}`='{$valEsc}' LIMIT 1";
				$rTry = @mysqli_query($con, $sqlTry);
				if ($rTry && ($live = mysqli_fetch_assoc($rTry))) {
					mysqli_free_result($rTry);
					return aud_fila_a_pares($live);
				}
				if ($rTry) {
					mysqli_free_result($rTry);
				}
			}
			return array();
		}
	}
	if (count($whereParts) === 0) {
		return array();
	}
	$sql = "SELECT * FROM `{$dbDis}`.`{$tab}` WHERE ".implode(' AND ', $whereParts)." LIMIT 1";
	$r = @mysqli_query($con, $sql);
	if (!$r) {
		return array();
	}
	$live = mysqli_fetch_assoc($r);
	mysqli_free_result($r);
	if (!is_array($live) || count($live) === 0) {
		return array();
	}
	return aud_fila_a_pares($live);
}

/**
 * Convierte una fila de BD en pares para el detalle.
 *
 * @param array $live
 * @return array
 */
function aud_fila_a_pares($live)
{
	$skip = array(
		'password', 'passwd', 'usu_pas', 'usu_pal', 'token', 'secret',
		'refresh', 'hash', 'salt', 'blob', 'img', 'foto', 'pdf'
	);
	$pares = array();
	$n = 0;
	foreach ($live as $col => $val) {
		$colL = strtolower((string)$col);
		$omit = false;
		foreach ($skip as $s) {
			if (strpos($colL, $s) !== false) {
				$omit = true;
				break;
			}
		}
		if ($omit) {
			continue;
		}
		if (is_array($val) || is_object($val)) {
			continue;
		}
		$val = (string)$val;
		if (strlen($val) > 500) {
			$val = substr($val, 0, 500).'…';
		}
		$pares[] = array(
			'atr' => (string)$col,
			'val' => $val,
			'reconstruido' => true
		);
		$n++;
		if ($n >= 40) {
			break;
		}
	}
	return $pares;
}

function aud_resumen_detalle($row, $pares, $obBD_conexion = null)
{
	$pcs = aud_nombre_proceso($row);
	$bits = array();
	if (is_array($pares)) {
		foreach ($pares as $p) {
			if (!isset($p['val']) || $p['val'] === '') {
				continue;
			}
			$eti = isset($p['eti']) ? $p['eti'] : $p['atr'];
			if ($eti === $p['atr']) {
				$eti = aud_humanizar_campo($p['atr']);
			}
			$bits[] = $eti.': '.$p['val'];
			if (count($bits) >= 4) {
				break;
			}
		}
	}
	if (count($bits) === 0) {
		$ident = aud_identificador($row, $obBD_conexion);
		if ($ident !== '') {
			$bits[] = $ident;
		}
	}
	if (count($bits) === 0) {
		return $pcs;
	}
	return $pcs.' - '.implode('; ', $bits);
}

/**
 * Tablas del nucleo Relavera/manifiesto que pueden enriquecer contexto.
 *
 * @return array mapa tab => true
 */
function aud_tablas_nucleo_relavera()
{
	return array(
		'manifiesto' => true,
		'manifiesto_tecnico' => true,
		'manifiesto_mensajes' => true,
		'manifiesto_vehiculo' => true,
		'manifiesto_chofer' => true,
		'manifiesto_transporte' => true,
		'manifiesto_plantas' => true,
		'manifiesto_desechos' => true,
		'manifiesto_celdas' => true,
		'manifiesto_contratos' => true,
		'manifiesto_contratos_docu' => true,
		'manifiesto_anticipo' => true,
		'manifiesto_turnos_cab' => true,
		'manifiesto_turnos_det' => true,
		'manifiesto_visitante' => true,
		'manifiesto_evento' => true,
		'manifiesto_liquidacion_maq' => true,
		'manifiesto_nivel_humedad' => true,
		'maquinaria_horometro' => true,
		'maquinaria_alimentacion' => true,
		'maquinaria_dispensador' => true,
		'maquinaria_dispensador_det' => true,
		'maquinaria_equipo' => true,
		'param_manifiesto' => true,
		'vehiculo' => true,
		'chofer' => true,
		'cliente' => true,
		'anticipos_clientes' => true,
		'banco' => true,
		'bancos' => true
	);
}

/**
 * Extrae codigos de interes (Man/Cli/Pla/Mat/...) desde pares + Log_Int.
 *
 * @param array $row
 * @param array $pares
 * @return array mapa atr => val
 */
function aud_codigos_desde_log($row, $pares)
{
	$out = array();
	if (is_array($pares)) {
		foreach ($pares as $p) {
			$atr = isset($p['atr']) ? trim(str_replace('`', '', (string)$p['atr'])) : '';
			$val = '';
			if (isset($p['val_raw']) && trim((string)$p['val_raw']) !== '') {
				$val = trim((string)$p['val_raw']);
			} elseif (isset($p['val'])) {
				$val = trim((string)$p['val']);
			}
			if ($atr === '' || $val === '' || strtoupper($val) === 'NULL') {
				continue;
			}
			// Quitar comillas/tildes de valor crudo
			$val = trim($val, " \t\n\r'\"~");
			if ($val !== '' && preg_match('/^[\w.\-]+$/u', $val)) {
				$out[$atr] = $val;
			}
		}
	}
	$keys = aud_ref_keys_desde_int(isset($row['Log_Int']) ? $row['Log_Int'] : '');
	foreach ($keys as $k) {
		$col = isset($k['col']) ? $k['col'] : '';
		$val = isset($k['val']) ? $k['val'] : '';
		if ($col !== '' && $val !== '' && !isset($out[$col])) {
			$out[$col] = $val;
		} elseif ($col === '' && $val !== '' && preg_match('/^\d+$/', $val)) {
			$tab = isset($row['Tab_Nom']) ? strtolower(trim((string)$row['Tab_Nom'])) : '';
			if ($tab === 'manifiesto' && !isset($out['Man_Cod'])) {
				$out['Man_Cod'] = $val;
			} elseif ($tab === 'manifiesto_tecnico' && !isset($out['Mat_Cod'])) {
				$out['Mat_Cod'] = $val;
			} elseif ($tab === 'manifiesto_mensajes' && !isset($out['Msj_Id']) && !isset($out['Msj_Cod'])) {
				$out['Msj_Id'] = $val;
			} elseif ($tab === 'manifiesto_contratos' && !isset($out['Mco_Cod'])) {
				$out['Mco_Cod'] = $val;
			} elseif ($tab === 'manifiesto_contratos_docu' && !isset($out['Mcd_Cod'])) {
				$out['Mcd_Cod'] = $val;
			} elseif ($tab === 'manifiesto_plantas' && !isset($out['Pla_Cod'])) {
				$out['Pla_Cod'] = $val;
			} elseif ($tab === 'cliente' && !isset($out['Cli_Cod'])) {
				$out['Cli_Cod'] = $val;
			}
		}
	}
	return $out;
}

/**
 * Resuelve Man_Cod / Pla_Cod / Cli_Cod a partir de tablas satelite.
 *
 * @param array $codes
 * @param string $tab
 * @param object $obBD_conexion
 * @param array $row
 * @return array {Man_Cod, Pla_Cod, Cli_Cod}
 */
function aud_resolver_claves_manifiesto($codes, $tab, $obBD_conexion, $row)
{
	$man = isset($codes['Man_Cod']) && preg_match('/^\d+$/', (string)$codes['Man_Cod']) ? (int)$codes['Man_Cod'] : 0;
	$pla = isset($codes['Pla_Cod']) && preg_match('/^\d+$/', (string)$codes['Pla_Cod']) ? (int)$codes['Pla_Cod'] : 0;
	$cli = isset($codes['Cli_Cod']) && preg_match('/^\d+$/', (string)$codes['Cli_Cod']) ? (int)$codes['Cli_Cod'] : 0;
	$con = aud_det_conexion_mysqli($obBD_conexion);
	$datDis = aud_db_dis_para_lookup($row);
	$tab = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tab));

	if ($con && $datDis !== '') {
		// Tecnico -> Man_Cod
		if ($man <= 0 && !empty($codes['Mat_Cod']) && preg_match('/^\d+$/', (string)$codes['Mat_Cod'])) {
			$mid = (int)$codes['Mat_Cod'];
			$rs = @mysqli_query($con, "SELECT `Man_Cod` FROM `{$datDis}`.`manifiesto_tecnico` WHERE `Mat_Cod`={$mid} LIMIT 1");
			if ($rs && ($r = mysqli_fetch_assoc($rs)) && !empty($r['Man_Cod'])) {
				$man = (int)$r['Man_Cod'];
			}
			if ($rs) {
				mysqli_free_result($rs);
			}
		}
		// Mensaje -> Man_Cod / Pla_Cod
		if ($man <= 0 || $pla <= 0) {
			$msjId = 0;
			if (!empty($codes['Msj_Id']) && preg_match('/^\d+$/', (string)$codes['Msj_Id'])) {
				$msjId = (int)$codes['Msj_Id'];
			} elseif (!empty($codes['Msj_Cod']) && preg_match('/^\d+$/', (string)$codes['Msj_Cod'])) {
				$msjId = (int)$codes['Msj_Cod'];
			}
			if ($msjId > 0) {
				$rs = @mysqli_query($con, "SELECT `Man_Cod`, `Pla_Cod` FROM `{$datDis}`.`manifiesto_mensajes`
					WHERE `Msj_Id`={$msjId} OR `Msj_Cod`={$msjId} LIMIT 1");
				if ($rs && ($r = mysqli_fetch_assoc($rs))) {
					if ($man <= 0 && !empty($r['Man_Cod'])) {
						$man = (int)$r['Man_Cod'];
					}
					if ($pla <= 0 && !empty($r['Pla_Cod'])) {
						$pla = (int)$r['Pla_Cod'];
					}
				}
				if ($rs) {
					mysqli_free_result($rs);
				}
			}
		}
		// Contrato -> Pla_Cod (+ cliente via planta)
		if ($pla <= 0 && !empty($codes['Mco_Cod']) && preg_match('/^\d+$/', (string)$codes['Mco_Cod'])) {
			$mco = (int)$codes['Mco_Cod'];
			$rs = @mysqli_query($con, "SELECT `Pla_Cod` FROM `{$datDis}`.`manifiesto_contratos` WHERE `Mco_Cod`={$mco} LIMIT 1");
			if ($rs && ($r = mysqli_fetch_assoc($rs)) && !empty($r['Pla_Cod'])) {
				$pla = (int)$r['Pla_Cod'];
			}
			if ($rs) {
				mysqli_free_result($rs);
			}
		}
		if ($pla <= 0 && !empty($codes['Mcd_Cod']) && preg_match('/^\d+$/', (string)$codes['Mcd_Cod'])) {
			$mcd = (int)$codes['Mcd_Cod'];
			$rs = @mysqli_query($con, "SELECT c.`Pla_Cod` FROM `{$datDis}`.`manifiesto_contratos_docu` d
				INNER JOIN `{$datDis}`.`manifiesto_contratos` c ON c.`Mco_Cod` = d.`Mco_Cod`
				WHERE d.`Mcd_Cod`={$mcd} LIMIT 1");
			if ($rs && ($r = mysqli_fetch_assoc($rs)) && !empty($r['Pla_Cod'])) {
				$pla = (int)$r['Pla_Cod'];
			}
			if ($rs) {
				mysqli_free_result($rs);
			}
		}
		// Planta -> Cli_Cod tipico
		if ($cli <= 0 && $pla > 0) {
			$rs = @mysqli_query($con, "SELECT `Cli_Cod` FROM `{$datDis}`.`manifiesto_plantas` WHERE `Pla_Cod`={$pla} LIMIT 1");
			if ($rs && ($r = mysqli_fetch_assoc($rs)) && !empty($r['Cli_Cod'])) {
				$cli = (int)$r['Cli_Cod'];
			}
			if ($rs) {
				mysqli_free_result($rs);
			}
		}
	}

	return array('Man_Cod' => $man, 'Pla_Cod' => $pla, 'Cli_Cod' => $cli, 'tab' => $tab);
}

/**
 * Contexto de negocio Relavera: manifiesto, cliente y planta.
 *
 * @param array $row
 * @param array $pares
 * @param object|null $obBD_conexion
 * @return array lista de {eti, val}
 */
function aud_contexto_relavera($row, $pares, $obBD_conexion = null)
{
	$tab = isset($row['Tab_Nom']) ? strtolower(trim((string)$row['Tab_Nom'])) : '';
	$nucleo = aud_tablas_nucleo_relavera();
	$mod = aud_nombre_modulo($row);
	$esRelavera = isset($nucleo[$tab]) || stripos($mod, 'Relavera') !== false || strpos($tab, 'manifiesto') === 0;
	if (!$esRelavera) {
		return array();
	}

	$codes = aud_codigos_desde_log($row, $pares);
	$keys = aud_resolver_claves_manifiesto($codes, $tab, $obBD_conexion, $row);
	$man = (int)$keys['Man_Cod'];
	$pla = (int)$keys['Pla_Cod'];
	$cli = (int)$keys['Cli_Cod'];

	$con = aud_det_conexion_mysqli($obBD_conexion);
	$datDis = aud_db_dis_para_lookup($row);
	$ctx = array(
		'manifiesto' => '',
		'man_num' => '',
		'cliente' => '',
		'planta' => '',
		'vehiculo' => '',
		'chofer' => ''
	);

	if ($con && $datDis !== '' && $man > 0) {
		$sql = "SELECT m.`Man_Cod`, m.`Man_Num`, m.`Pla_Cod`, m.`Cli_Cod`,
			CONCAT('M', IFNULL(m.`Pla_Cod`,''), '-', LPAD(IFNULL(m.`Man_Num`,0), 4, '0')) AS `Man_Lbl`,
			IFNULL(NULLIF(TRIM(mp.`Pla_Nom`),''), CONCAT('Planta ', m.`Pla_Cod`)) AS `Pla_Nom`,
			IFNULL(NULLIF(TRIM(c.`Cli_Nom`),''), TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,'')))) AS `Cli_Nom`,
			IFNULL(NULLIF(TRIM(v.`Veh_Pla`),''), '') AS `Veh_Pla`,
			TRIM(CONCAT(IFNULL(pch.`Prs_Ape`,''),' ',IFNULL(pch.`Prs_Nom`,''))) AS `Cho_Nom`
			FROM `{$datDis}`.`manifiesto` m
			LEFT JOIN `{$datDis}`.`manifiesto_plantas` mp ON mp.`Pla_Cod` = m.`Pla_Cod`
			LEFT JOIN `{$datDis}`.`cliente` c ON c.`Cli_Cod` = m.`Cli_Cod`
			LEFT JOIN `{$datDis}`.`persona` p ON p.`Prs_Cod` = c.`Prs_Cod`
			LEFT JOIN `{$datDis}`.`vehiculo` v ON v.`Veh_Cod` = m.`Veh_Cod`
			LEFT JOIN `{$datDis}`.`chofer` ch ON ch.`Cho_Cod` = m.`Cho_Cod`
			LEFT JOIN `{$datDis}`.`persona` pch ON pch.`Prs_Cod` = ch.`Prs_Cod`
			WHERE m.`Man_Cod`={$man} LIMIT 1";
		$rs = @mysqli_query($con, $sql);
		if ($rs && ($r = mysqli_fetch_assoc($rs))) {
			$ctx['manifiesto'] = aud_to_utf8(isset($r['Man_Lbl']) ? $r['Man_Lbl'] : '');
			$ctx['man_num'] = isset($r['Man_Num']) ? (string)$r['Man_Num'] : '';
			$ctx['planta'] = aud_to_utf8(isset($r['Pla_Nom']) ? $r['Pla_Nom'] : '');
			$ctx['cliente'] = aud_to_utf8(isset($r['Cli_Nom']) ? trim($r['Cli_Nom']) : '');
			$ctx['vehiculo'] = aud_to_utf8(isset($r['Veh_Pla']) ? $r['Veh_Pla'] : '');
			$ctx['chofer'] = aud_to_utf8(isset($r['Cho_Nom']) ? trim($r['Cho_Nom']) : '');
			if ($pla <= 0 && !empty($r['Pla_Cod'])) {
				$pla = (int)$r['Pla_Cod'];
			}
			if ($cli <= 0 && !empty($r['Cli_Cod'])) {
				$cli = (int)$r['Cli_Cod'];
			}
		}
		if ($rs) {
			mysqli_free_result($rs);
		}
	}

	// Sin Man_Cod: resolver planta y/o cliente sueltos (contratos, turnos, visitantes, etc.)
	if ($con && $datDis !== '' && $ctx['planta'] === '' && $pla > 0) {
		$rs = @mysqli_query($con, "SELECT IFNULL(NULLIF(TRIM(`Pla_Nom`),''), CONCAT('Planta ',`Pla_Cod`)) AS `Pla_Nom`, `Cli_Cod`
			FROM `{$datDis}`.`manifiesto_plantas` WHERE `Pla_Cod`={$pla} LIMIT 1");
		if ($rs && ($r = mysqli_fetch_assoc($rs))) {
			$ctx['planta'] = aud_to_utf8($r['Pla_Nom']);
			if ($cli <= 0 && !empty($r['Cli_Cod'])) {
				$cli = (int)$r['Cli_Cod'];
			}
		}
		if ($rs) {
			mysqli_free_result($rs);
		}
	}
	if ($con && $datDis !== '' && $ctx['cliente'] === '' && $cli > 0) {
		$rs = @mysqli_query($con, "SELECT IFNULL(NULLIF(TRIM(c.`Cli_Nom`),''), TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,'')))) AS `Cli_Nom`
			FROM `{$datDis}`.`cliente` c
			LEFT JOIN `{$datDis}`.`persona` p ON p.`Prs_Cod` = c.`Prs_Cod`
			WHERE c.`Cli_Cod`={$cli} LIMIT 1");
		if ($rs && ($r = mysqli_fetch_assoc($rs)) && !empty($r['Cli_Nom'])) {
			$ctx['cliente'] = aud_to_utf8(trim($r['Cli_Nom']));
		}
		if ($rs) {
			mysqli_free_result($rs);
		}
	}

	$items = array();
	if ($ctx['manifiesto'] !== '') {
		$items[] = array('eti' => 'Manifiesto', 'val' => $ctx['manifiesto']);
	} elseif ($man > 0) {
		$items[] = array('eti' => 'Manifiesto', 'val' => 'Man_Cod '.$man);
	}
	if ($ctx['cliente'] !== '') {
		$items[] = array('eti' => 'Cliente', 'val' => $ctx['cliente']);
	}
	if ($ctx['planta'] !== '') {
		$items[] = array('eti' => 'Planta', 'val' => $ctx['planta']);
	}
	if ($ctx['vehiculo'] !== '') {
		$items[] = array('eti' => 'Vehiculo', 'val' => $ctx['vehiculo']);
	}
	if ($ctx['chofer'] !== '') {
		$items[] = array('eti' => 'Chofer', 'val' => $ctx['chofer']);
	}
	return $items;
}

function aud_frase_movimiento($row, $pares, $obBD_conexion = null)
{
	$modulo = aud_nombre_modulo($row);
	$proceso = aud_nombre_proceso($row);
	$obj = aud_nombre_registro(
		isset($row['Tab_Nom']) ? $row['Tab_Nom'] : '',
		isset($row['Tab_Ali']) ? $row['Tab_Ali'] : '',
		isset($row['Tab_Des']) ? $row['Tab_Des'] : ''
	);
	$accionVolq = aud_accion_volqueta($row, $pares);
	$ini = strtoupper(isset($row['Eve_Ini']) ? $row['Eve_Ini'] : '');
	if ($accionVolq !== '') {
		$frase = 'Accion registrada: '.$accionVolq.' sobre un '.$obj.' en el modulo '.$modulo.'.';
	} elseif ($ini === 'I') {
		$frase = 'Se registro un nuevo '.$obj.' en el modulo '.$modulo.'.';
	} elseif ($ini === 'U') {
		$frase = 'Se modificaron datos de un '.$obj.' en el modulo '.$modulo.'.';
	} elseif ($ini === 'D') {
		$frase = 'Se elimino o anulo un '.$obj.' en el modulo '.$modulo.'.';
	} else {
		$frase = 'Se registro una actividad sobre un '.$obj.' en el modulo '.$modulo.'.';
	}
	if ($proceso !== '' && $proceso !== 'Proceso no registrado') {
		$frase .= ' El proceso utilizado fue "'.$proceso.'".';
	}
	$usuario = aud_nombre_usuario($row);
	if ($usuario !== '' && $usuario !== 'Usuario no identificado') {
		$frase .= ' El usuario fue "'.$usuario.'".';
	}
	$empresa = aud_nombre_empresa($row);
	if ($empresa !== '' && $empresa !== 'Sin empresa') {
		$frase .= ' Empresa: '.$empresa.'.';
	}
	$ctxRel = aud_contexto_relavera($row, $pares, $obBD_conexion);
	foreach ($ctxRel as $c) {
		if (!empty($c['eti']) && !empty($c['val'])) {
			$frase .= ' '.$c['eti'].': '.$c['val'].'.';
		}
	}
	$ident = aud_identificador($row, $obBD_conexion);
	if ($ident !== '') {
		$frase .= ' Referencia: '.$ident.'.';
	}
	return $frase;
}

function aud_html_detalle($row, $pares, $obBD_conexion = null)
{
	$usuario = aud_h(aud_nombre_usuario($row));
	$empresa = aud_h(aud_nombre_empresa($row));
	$sucursal = aud_h(aud_nombre_sucursal($row));
	$moduloTxt = aud_filtro_label(aud_nombre_modulo($row), 'Sin modulo');
	$directorioTxt = aud_filtro_label(aud_nombre_directorio($row), '');
	$procesoTxt = aud_filtro_label(aud_nombre_proceso($row), 'Proceso no registrado');
	$modulo = aud_h($moduloTxt !== '' ? $moduloTxt : 'Sin modulo');
	$directorio = aud_h($directorioTxt);
	$proceso = aud_h($procesoTxt !== '' ? $procesoTxt : 'Proceso no registrado');
	$actividad = aud_h(aud_to_utf8(aud_resumen_actividad($row, $obBD_conexion)));
	$registro = aud_h(aud_to_utf8(aud_nombre_registro(
		isset($row['Tab_Nom']) ? $row['Tab_Nom'] : '',
		isset($row['Tab_Ali']) ? $row['Tab_Ali'] : '',
		isset($row['Tab_Des']) ? $row['Tab_Des'] : ''
	)));
	$ident = aud_h(aud_to_utf8(aud_identificador($row, $obBD_conexion)));
	$eveIni = strtoupper(trim(isset($row['Eve_Ini']) ? $row['Eve_Ini'] : ''));
	$accionVolq = aud_accion_volqueta($row, $pares);
	if ($accionVolq !== '') {
		$eveDes = aud_h(aud_to_utf8($accionVolq));
	} else {
		$eveDes = aud_h(aud_to_utf8(aud_etiqueta_evento($eveIni, isset($row['Eve_Des']) ? $row['Eve_Des'] : '')));
	}
	$badgeClass = 'aud-det-badge';
	if ($eveIni === 'I') {
		$badgeClass .= ' aud-det-badge-i';
	} elseif ($eveIni === 'U') {
		$badgeClass .= ' aud-det-badge-u';
	} elseif ($eveIni === 'D') {
		$badgeClass .= ' aud-det-badge-d';
	} elseif ($eveIni === 'F') {
		$badgeClass .= ' aud-det-badge-f';
	}

	$fecha = '';
	$hora = '';
	if (!empty($row['Log_Fec'])) {
		$parts = explode(' ', trim($row['Log_Fec']));
		$fecha = aud_h(isset($parts[0]) ? $parts[0] : '');
		$hora = aud_h(isset($parts[1]) ? $parts[1] : '');
	}

	$html = '<div class="aud-detalle m4-detalle">';

	/* 1) Cabecera: evento + fecha */
	$html .= '<div class="aud-det-head">';
	$html .= '<div class="aud-det-head-left">';
	$html .= '<span class="'.$badgeClass.'">'.($eveDes !== '' ? $eveDes : 'Actividad').'</span>';
	if (!empty($row['Log_Cod'])) {
		$html .= '<span class="aud-det-id">#'.(int)$row['Log_Cod'].'</span>';
	}
	$html .= '</div>';
	$html .= '<div class="aud-det-when">';
	if ($fecha !== '') {
		$html .= '<span class="aud-det-when-main"><i class="fa fa-calendar"></i> '.$fecha;
		if ($hora !== '') {
			$html .= ' <span class="aud-det-hora"><i class="fa fa-clock-o"></i> '.$hora.'</span>';
		}
		$html .= '</span>';
	}
	$html .= '</div></div>';

	/* 2) Resumen de la accion */
	$usuPlain = trim(aud_nombre_usuario($row));
	if (function_exists('mb_substr')) {
		$inicial = $usuPlain !== '' ? mb_strtoupper(mb_substr($usuPlain, 0, 1, 'UTF-8'), 'UTF-8') : 'U';
	} else {
		$inicial = $usuPlain !== '' ? strtoupper(substr($usuPlain, 0, 1)) : 'U';
	}
	$html .= '<div class="aud-det-summary">';
	$html .= '<div class="aud-det-avatar">'.aud_h($inicial).'</div>';
	$html .= '<div class="aud-det-summary-body">';
	$html .= '<p class="aud-det-title"><strong>'.$usuario.'</strong> '.$actividad.'.</p>';
	$html .= '<p class="aud-det-lead">'.aud_h(aud_to_utf8(aud_frase_movimiento($row, $pares, $obBD_conexion))).'</p>';
	$html .= '</div></div>';

	/* 3) Contexto en rejilla */
	$html .= '<fieldset class="exa-fieldset aud-det-context"><legend class="Titulos2">Contexto</legend>';
	$html .= '<div class="aud-det-meta">';
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Empresa</span><span class="aud-det-value">'.$empresa.'</span></div>';
	if ($sucursal !== '') {
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Sucursal</span><span class="aud-det-value">'.$sucursal.'</span></div>';
	}
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Modulo</span><span class="aud-det-value">'.$modulo.'</span></div>';
	if ($directorio !== '' && $directorio !== $modulo) {
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Area</span><span class="aud-det-value">'.$directorio.'</span></div>';
	}
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Proceso</span><span class="aud-det-value">'.$proceso.'</span></div>';
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Tipo de documento</span><span class="aud-det-value">'.ucfirst($registro).'</span></div>';
	$ctxRel = aud_contexto_relavera($row, $pares, $obBD_conexion);
	foreach ($ctxRel as $c) {
		$etiC = isset($c['eti']) ? aud_h($c['eti']) : '';
		$valC = isset($c['val']) ? aud_h($c['val']) : '';
		if ($etiC === '' || $valC === '') {
			continue;
		}
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">'.$etiC.'</span><span class="aud-det-value">'.$valC.'</span></div>';
	}
	if ($ident !== '') {
		$html .= '<div class="aud-det-meta-item aud-det-meta-wide"><span class="aud-det-label">Referencia</span><span class="aud-det-value">'.$ident.'</span></div>';
	}
	$html .= '</div></fieldset>';

	/* 4) Datos del movimiento */
	$html .= '<fieldset class="exa-fieldset aud-det-datos"><legend class="Titulos2">Datos del movimiento</legend>';
	$hayReconstruido = false;
	foreach ($pares as $pChk) {
		if (!empty($pChk['reconstruido'])) {
			$hayReconstruido = true;
			break;
		}
	}
	if ($hayReconstruido) {
		$html .= '<p class="aud-det-hint text-muted" style="margin-bottom:8px;"><i class="fa fa-history"></i> '
			.'Detalle reconstruido desde el registro actual (el log original no guardo los campos). '
			.'Puede diferir del estado exacto al momento del evento.</p>';
	}
	if (count($pares) === 0) {
		$html .= '<p class="aud-det-empty"><i class="fa fa-info-circle"></i> No hay datos adicionales para mostrar en este movimiento.</p>';
	} else {
		$html .= '<div class="table-responsive aud-det-table-wrap"><table class="table table-bordered table-condensed aud-det-table">';
		$html .= '<thead><tr><th class="aud-det-col-dato">Dato</th>';
		$viejos = aud_valores_anteriores($row);
		if (count($viejos) > 0) {
			$html .= '<th class="aud-det-col-antes">Antes</th><th class="aud-det-col-despues">Despues</th>';
		} else {
			$html .= '<th class="aud-det-col-valor">Valor</th>';
		}
		$html .= '<th class="aud-det-col-des">Que significa este valor</th></tr></thead><tbody>';
		foreach ($pares as $p) {
			$eti = isset($p['eti']) ? $p['eti'] : aud_humanizar_campo($p['atr']);
			if ($eti === $p['atr']) {
				$eti = aud_humanizar_campo($p['atr']);
			}
			$des = isset($p['des']) ? $p['des'] : aud_descripcion_meta(
				isset($p['atr']) ? $p['atr'] : '',
				isset($p['val']) ? $p['val'] : '',
				isset($p['val_raw']) ? $p['val_raw'] : (isset($p['val']) ? $p['val'] : ''),
				null
			);
			$html .= '<tr><td class="aud-det-col-dato">'.aud_h($eti).'</td>';
			if (count($viejos) > 0) {
				$oldVal = '';
				$oldRaw = '';
				if (isset($p['atr']) && isset($viejos[$p['atr']])) {
					$oldRaw = $viejos[$p['atr']];
					$oldVal = aud_valor_natural($p['atr'], $oldRaw, $row, null);
				}
				$html .= '<td class="aud-det-col-antes"><span class="aud-det-val-old">'.aud_h($oldVal !== '' ? $oldVal : '—').'</span></td>';
				if ($oldVal !== '' && $oldVal !== $p['val']) {
					$des = 'Antes: "'.$oldVal.'". Ahora: '.$des;
				}
			}
			$html .= '<td class="aud-det-col-despues"><span class="aud-det-val-new">'.aud_h($p['val']).'</span></td>';
			$html .= '<td class="aud-det-col-des">'.aud_h($des).'</td></tr>';
		}
		$html .= '</tbody></table></div>';
	}
	$html .= '</fieldset></div>';
	return $html;
}

/**
 * Estado de captura para avisos en monitor y configuracion.
 * $cfgCount: reglas activas de la empresa (-1 si no se pudo leer).
 */
function aud_estado_captura($empCod, $cfgCount = -1)
{
	if (!class_exists('AuditQueue')) {
		$queueFile = dirname(__FILE__) . '/aud_log_queue.php';
		if (file_exists($queueFile)) {
			require_once($queueFile);
		}
	}
	$enabled = class_exists('AuditQueue') && AuditQueue::enabled();
	$emp = (int)$empCod;
	$cfg = (int)$cfgCount;
	$msg = '';
	$ok = true;
	if (!$enabled) {
		$ok = false;
		$msg = 'El registro de actividades esta desactivado. No se guardaran movimientos nuevos hasta reactivarlo.';
	} elseif ($emp <= 0) {
		$ok = false;
		$msg = 'No hay empresa activa. No se puede asociar la actividad al monitoreo.';
	} elseif ($cfg > 0) {
		$msg = 'Registro activo: se guardan los movimientos de los '.$cfg.' modulo(s)/area(s)/proceso(s) marcados en la configuracion.';
	} else {
		$ok = false;
		$msg = 'El registro esta activo, pero aun no hay reglas. Marque modulos en Configuracion de monitoreo para comenzar a auditar.';
	}
	return array('ok' => $ok, 'enabled' => $enabled, 'cfg' => $cfg, 'message' => $msg);
}

function aud_html_banner_captura($estado)
{
	if (!is_array($estado) || empty($estado['message'])) {
		return '';
	}
	$cls = !empty($estado['ok']) ? 'aud-captura-ok' : 'aud-captura-off';
	return '<p class="aud-captura-banner '.$cls.'">'.aud_h($estado['message']).'</p>';
}

/**
 * Fecha mas antigua con datos registrados en auditoria (aviso "datos desde").
 * Se consulta una sola vez por empresa y se cachea en sesion.
 * $obBD_con1 / $obBD_conexion son opcionales: si no llegan se usa la fecha
 * de inicio del monitoreo (10-sep-2026).
 */
function aud_fecha_registro_inicio($empCod, $obBD_con1 = null, $obBD_conexion = null)
{
	try {
		$sessOk = (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE);
	} catch (\Exception $eSess) {
		$sessOk = false;
	} catch (\Throwable $eSess2) {
		$sessOk = false;
	}
	if (!isset($sessOk)) {
		$sessOk = false;
	}
	$emp = (int)$empCod;
	$key = 'aud_min_fec_'.(int)$emp;
	if ($sessOk && isset($_SESSION[$key]) && $_SESSION[$key] !== '') {
		return (string)$_SESSION[$key];
	}
	$fecha = '2026-09-10';
	if ($obBD_con1 && $obBD_conexion && method_exists($obBD_con1, 'getRowConsulta')) {
		try {
			$row = $obBD_con1->getRowConsulta(37, array($emp), $obBD_conexion);
			if (is_array($row) && !empty($row['min_fec'])) {
				$d = substr(trim((string)$row['min_fec']), 0, 10);
				if ($d !== '' && $d !== '0000-00-00' && strtotime($d)) {
					$fecha = $d;
				}
			}
		} catch (\Exception $eSQL) {
			// conservar la fecha por defecto
		} catch (\Throwable $eSQL2) {
			// conservar la fecha por defecto
		}
	}
	if ($sessOk) {
		$_SESSION[$key] = $fecha;
	}
	return $fecha;
}

/** Banner informativo: desde que fecha hay datos en auditoria. */
function aud_html_banner_desde($fecha)
{
	$fecha = trim((string)$fecha);
	if ($fecha === '' || $fecha === '0000-00-00') {
		return '';
	}
	$ts = strtotime($fecha);
	if (!$ts) {
		return '';
	}
	$meses = array('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre');
	$legible = (int)date('j', $ts).' de '.$meses[(int)date('n', $ts) - 1].' de '.date('Y', $ts);
	$dia = date('d/m/Y', $ts);
	return '<p class="aud-aviso-desde"><span class="glyphicon glyphicon-info-sign"></span> La auditoria registra datos desde el <strong>'.$legible.'</strong> ('.$dia.'). No hay movimientos registrados con anterioridad a esa fecha.</p>';
}

/**
 * Historial (timeline) de un registro: todos los movimientos del mismo
 * tipo de registro e identificador, ordenados cronologicamente.
 */
function aud_html_historial($row, $obBD_con1 = null, $obBD_conexion = null)
{
	if (!$obBD_con1 || !$obBD_conexion || !method_exists($obBD_con1, 'getArrayConsulta')) {
		return '<p class="aud-det-empty">Historial no disponible.</p>';
	}
	$tabCod = isset($row['Tab_Cod']) ? (int)$row['Tab_Cod'] : 0;
	$emp = isset($row['Emp_Cod']) ? (int)$row['Emp_Cod'] : 0;
	$actualCod = isset($row['Log_Cod']) ? (int)$row['Log_Cod'] : 0;
	$int = trim((string)(isset($row['Log_Int']) ? $row['Log_Int'] : ''));
	// El identificador base es lo que precede a " || OLD:..."
	$base = trim(preg_replace('/\s*\|\|.*$/s', '', $int));
	if ($base === '') {
		return '<p class="aud-det-empty">Este movimiento no tiene referencia para armar su historial de cambios.</p>';
	}
	$hist = $obBD_con1->getArrayConsulta(36, array($tabCod, $emp, $base, 100), $obBD_conexion);
	if (!is_array($hist) || count($hist) === 0) {
		return '<p class="aud-det-empty">No hay otros cambios registrados sobre este documento.</p>';
	}
	$html = '<ul class="aud-hist-list">';
	foreach ($hist as $r) {
		$eveIni = strtoupper(trim(isset($r['Eve_Ini']) ? $r['Eve_Ini'] : ''));
		$eveDes = aud_h(aud_etiqueta_evento($eveIni, isset($r['Eve_Des']) ? $r['Eve_Des'] : ''));
		$badgeClass = 'aud-det-badge';
		if ($eveIni === 'I') {
			$badgeClass .= ' aud-det-badge-i';
		} elseif ($eveIni === 'U') {
			$badgeClass .= ' aud-det-badge-u';
		} elseif ($eveIni === 'D') {
			$badgeClass .= ' aud-det-badge-d';
		} elseif ($eveIni === 'F') {
			$badgeClass .= ' aud-det-badge-f';
		}
		$fec = trim(isset($r['Log_Fec']) ? $r['Log_Fec'] : '');
		$usuario = aud_nombre_usuario($r);
		$usuario = ($usuario !== '' && $usuario !== 'Usuario no identificado') ? $usuario : '';
		$paresDet = aud_pares_interpretados($r, null, null);
		$resumen = aud_h(aud_resumen_detalle($r, $paresDet));
		$esActual = ((int)$r['Log_Cod'] === $actualCod);
		$html .= '<li class="aud-hist-item'.($esActual ? ' aud-hist-item-actual' : '').'">';
		$html .= '<div class="aud-hist-top">';
		$html .= '<span class="'.$badgeClass.'">'.($eveDes !== '' ? $eveDes : 'Actividad').'</span>';
		$html .= '<span class="aud-hist-when">'.aud_h($fec).'</span>';
		if ($esActual) {
			$html .= '<span class="aud-hist-actual">Este movimiento</span>';
		}
		$html .= '</div>';
		if ($usuario !== '') {
			$html .= '<div class="aud-hist-usuario">Por '.aud_h($usuario).'</div>';
		}
		if ($resumen !== '') {
			$html .= '<div class="aud-hist-resumen">'.$resumen.'</div>';
		}
		$logCodRow = (int)$r['Log_Cod'];
		if ($logCodRow > 0) {
			$html .= '<div class="aud-hist-ver"><a href="javascript:void(0);" data-logcod="'.$logCodRow.'" class="aud-hist-verlink">Ver este movimiento</a></div>';
		}
		$html .= '</li>';
	}
	$html .= '</ul>';
	return $html;
}

/**
 * Detalle con pestañas: "Movimiento" (contenido clasico + contenido extra)
 * y "Historial de cambios" (linea de tiempo del registro).
 */
function aud_html_detalle_tabs($row, $pares, $obBD_con1 = null, $obBD_conexion = null, $extraMovHtml = '')
{
	$html = '<div class="aud-det-tabs">';
	$html .= '<ul class="aud-det-tabnav">';
	$html .= '<li class="aud-det-tabli active" data-tab="mov"><a href="javascript:void(0);">Movimiento</a></li>';
	$html .= '<li class="aud-det-tabli" data-tab="hist"><a href="javascript:void(0);">Historial de cambios</a></li>';
	$html .= '</ul>';
	$html .= '<div class="aud-det-tabpane active" id="audDetTabMov">';
	$html .= aud_html_detalle($row, $pares, $obBD_conexion);
	$html .= (string)$extraMovHtml;
	$html .= '</div>';
	$html .= '<div class="aud-det-tabpane" id="audDetTabHist" style="display:none;">';
	$html .= aud_html_historial($row, $obBD_con1, $obBD_conexion);
	$html .= '</div></div>';
	$html .= '<script type="text/javascript">(function(){var $w=window.jQuery;if(!$w){return;}$w("#detalleContenido").off("click.audDet").on("click.audDet",".aud-det-tabnav .aud-det-tabli a",function(e){e.preventDefault();var $li=$w(this).closest(".aud-det-tabli");var t=$li.attr("data-tab")||"mov";var $dlg=$w("#detalleContenido");$dlg.find(".aud-det-tabli").removeClass("active");$li.addClass("active");$dlg.find(".aud-det-tabpane").hide();$dlg.find(".aud-det-tabpane").removeClass("active");var $pane=$dlg.find("#audDetTab"+((t==="hist")?"Hist":"Mov"));$pane.show().addClass("active");try{$w("#detalleDialog").dialog("option","position",{my:"center",at:"center",of:window});}catch(e2){}});$w("#detalleContenido").off("click.audHist").on("click.audHist",".aud-hist-verlink",function(e){e.preventDefault();var c=parseInt($w(this).attr("data-logcod"),10);if(c>0&&typeof window.audVerDetalle==="function"){window.audVerDetalle(c);}});})();</script>';
	return $html;
}
