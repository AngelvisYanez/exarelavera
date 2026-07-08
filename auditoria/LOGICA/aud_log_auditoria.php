<?php 
require_once(__DIR__."/../../DATA/MysqlConexion.php");
require_once(__DIR__."/../../DATA/MysqlDatos.php");
// require_once("../../DATA/MysqlConexion.php");
// require_once("../../DATA/MysqlDatos.php");
/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 * @package auditoria.LOGICA
 */
class Class_Log_Datos_Aud extends MysqlDatos{
	
	/**
	 * Arreglo de palabras reservadas
	 * @var unknown
	 */
	public $reservadas;
	
	/**
	 * Obtener una fila resultante de la base de datos
	 * @param string $query consulta a ejecutarse
	 * @param Class_Log_Conexion $obBD_conexion objeto de conexcion con la base de datos
	 * @return array arreglo de datos
	 */
	function getRowConsulta($query, $obBD_conexion, $fixWarning1=null, $fixWarning2=0)
	{
		/**
		 * Ejecutar Consulta
		 */
		$result = $this->consulta($query,$obBD_conexion->conexion);
		/**
		 * Obtener la fila
		 */
		$row = $this->fetch_assoc($result);
		/**
		 * liberar memoria
		 */
		$this->free_result($result);
	
		return $row;
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos
	 * @param string $query consulta a ejecutarse
	 * @param Class_Log_Conexion $obBD_conexion para realizar la conexcion correspondiente
	 * @return array $array arreglo de datos asociados
	 */
	function getArrayConsulta($query, $obBD_conexion, $fixWarning1=null, $fixWarning2=0)
	{
		$result = $this->consulta($query,$obBD_conexion->conexion);
	
		$array = array();
	
		while($row_rs = $this->fetch_assoc($result))
		{
			$array[] = $row_rs;
		}
	
		$this->free_result($result);
	
		return $array;
	}
	
	/**
	 * Segun la sentencia obtener el nombre de la tabla
	 * @param string $str sentencia
	 * @param string $char_ini caracter antes del nombre de la tabla; primera coincidencia
	 * @param string $char_fin caracter despues del nombre de la tabla; primera coincidencia
	 * @return string nombre de la tabla resultante
	 */
	function getNombreTabla($str, $char_ini, $char_fin = ''){
		
		$nombre = '';
		
		if($char_fin == ''){
			/**
			 * Posicion de inicio
			 */
			$pos_ini = strpos($str, $char_ini);
			
			$nombre = strtolower(trim(substr($str,$pos_ini + 1, abs(strlen($str) - $pos_ini))));
		}else{
			/**
			 * Posicion de inicio
			 */
			$pos_ini = strpos($str, $char_ini) + 1;
			/**
			 * Posicion de fin
			 */
			$pos_fin = strpos($str, $char_fin);
			
			$nombre = strtolower(trim(substr(trim($str),$pos_ini,(abs($pos_ini - $pos_fin)))));
		}
		return $nombre;
	}
	
	/**
	 * Obtener la fila de datos de la tabla tables de auditoria
	 * @param string $str
	 * @param string $char_ini
	 * @param Class_Log_Conexion $obBD_conexion
	 * @param string $char_fin
	 * @return Ambigous <multitype:NULL , multitype:>
	 */
	function getRowNombreTabla($str, $char_ini, $obBD_conexion, $obBD_con1, $char_fin = ''){
		
		$Nombre = $this->getNombreTabla($str, $char_ini, $char_fin);
		
		/**
		 * Obtener el nombre de la tabla
		 */
		$row_tab = $this->getRowConsulta($this->sentencias(1, $this->parametros($Nombre)), $obBD_conexion);
		
		if(!($row_tab['Tab_Cod'] > 0)){
			$row_tab = array();
		
			$Arr_Tables = $this->getArrayConsulta($this->sentencias(4, ''), $obBD_conexion);
			foreach($Arr_Tables as $row){
				if($Nombre == $row['Name']){
					if($obBD_con1->Error == 0){
						$Tab_Des = substr($row['Comment'], 0, strpos($row['Comment'],';'));
						$obBD_con1->grabarv_registros($this->sentencias(5, $this->parametros($row['Name'].'*'.$Tab_Des)),$obBD_conexion->conexion);
						$row_tab['Tab_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
						$this->guardarNombreColumnas($row_tab['Tab_Cod'], $row['Name'], $obBD_con1, $obBD_conexion);
					}else{
						break;
					}
				}else{
					$aux = $this->getRowConsulta($this->sentencias(1, $this->parametros($row['Name'])), $obBD_conexion);
					if(!($aux['Tab_Cod'] > 0)){
						if($obBD_con1->Error == 0){
							$Tab_Des = substr($row['Comment'], 0, strpos($row['Comment'],';'));
							$obBD_con1->grabarv_registros($this->sentencias(5, $this->parametros($row['Name'].'*'.$Tab_Des)),$obBD_conexion->conexion);
							$row_aux = $obBD_con1->insercionid($obBD_conexion->conexion);
							$this->guardarNombreColumnas($row_aux, $row['Name'], $obBD_con1, $obBD_conexion);
						}else{
							break;
						}
					}
				}
			}
			unset($Arr_Tables);
			unset($aux);
		}
		unset($Nombre);
		return $row_tab;
	}
	
	/**
	 * Guardado de los campos de la tablas existentes
	 * @param number $Tab_Cod codigo de la tabla
	 * @param string $Tab_Nom nombre de la tabla 
	 * @param Class_Log_Datos $obBD_con1
	 * @param Class_Log_Conexion $obBD_conexion
	 */
	function guardarNombreColumnas($Tab_Cod, $Tab_Nom, $obBD_con1, $obBD_conexion){
		$Arr_Columns = $this->getArrayConsulta($this->sentencias(10, $this->parametros($Tab_Nom)), $obBD_conexion);
		
		foreach($Arr_Columns as $columns){
			if($obBD_con1->Error == 0){
				$obBD_con1->grabarv_registros($this->sentencias(11, $this->parametros($Tab_Cod.'*'.$columns['COLUMN_NAME'].'*'.str_replace("'", "", $columns['COLUMN_COMMENT']))),$obBD_conexion->conexion);
			}else{
				break;
			}
		}
	}
	/**
	 * Quitar palabras reservadas o nombre de funciones en los campos
	 * @param string $string
	 * @return string
	 */
	function quitarReservadas($string){
		$str = $string;
		foreach($this->reservadas as $row){
			$str = str_replace(strtoupper($row['Res_Nom']) , '', $str);
			$str = str_replace(strtolower($row['Res_Nom']) , '', $str);
		}
		
		$str = str_replace('(', '', str_replace(')', '', $str));
		
		return $str;
	}
	
	/**
	 * Obtener los insert de auditoria
	 * @param string $Request_Uri
	 * @param number $Ses_Usu_Cod
	 * @param Class_Log_Datos $obBD_con1
	 * @param Class_Log_Conexion $obBD_conexion
	 * @return string sentencias de insert concatenadas
	 */
	function grabarAuditoria($Ses_Dat_Dis,$Request_Uri, $Ses_Usu_Cod, $obBD_con1, $obBD_conexion){
		
		//$this->reservadas = $this->getArrayConsulta($this->sentencias(14, $this->parametros('')), $obBD_conexion);
	
		/**
		 * obtener el nombre de la pagina
		 */
		$URL = explode("/", $Request_Uri);
		$Pcs_Nom = trim($URL[count($URL) - 1]);
		
		/**
		 * Codigo del proceso
		 */
		$row_pcs = $this->getRowConsulta($this->sentencias(2,$this->parametros($Ses_Dat_Dis.'*'.$Pcs_Nom)), $obBD_conexion);
	
		/**
		 * Arreglo de sentencias
		 */
		$Arr_Querys = explode('*', $obBD_con1->sentencias);
		
		/**
		 * Codigo a ejecutarse
		 */
		$Arr_Codigos = explode('*',$obBD_con1->codigos);
		
		$sentencias = '';
	
		for($i = 0; $i < count($Arr_Querys) - 1; $i++){
			
			$hoy = date('Y-m-d H:i:s');
			
			/**
			 * sql ejecutada
			 */
			$query =  strtoupper(trim($Arr_Querys[$i]));
			
			$query = str_replace("`", "", $query);
	
			/**
			 * evento de la sql ejecutada
			 */
			$evento = substr($query,0,1);
			
			$Row_Evn = $this->getRowConsulta($this->sentencias(9,$this->parametros($evento)), $obBD_conexion);
			
			$Log_Cam = '';
			$Log_Val = '';
	
			switch($evento){
				case "I":
					/**
					 * [0] insert int... hasta values
					 * [1] despues de values... (val1,...
					 */
					$cadenas = explode('VALUES', $query);
						
					/**
					 * Obtener el nombre de la tabla
					 */
					$row_tab = $this->getRowNombreTabla($cadenas[0], 'O', $obBD_conexion, $obBD_con1, '(');
					
					$Log_Val = str_replace("'", "~", trim($cadenas[1]));
					
					//$Log_Val = $this->quitarReservadas($Log_Val);
	
					$sentencias .= $this->sentencias(3, $this->parametros($Ses_Usu_Cod.'*'.$row_pcs['Pcs_Cod'].'*'.$row_tab['Tab_Cod'].'*'.$hoy.'*'.$Row_Evn['Eve_Cod'].'*'.trim(strpbrk($cadenas[0], '(')).'*'.$Log_Val.'*'.$Arr_Codigos[$i])).'*';
					break;
				case "U":
					/**
					 * [0] update... hasta set
					 * [1] despues de set... val1, = 1...
					 */
					$sql_part1 = explode('SET', $query);
						
					/**
					 * Obtener el nombre de la tabla
					 */
					$row_tab = $this->getRowNombreTabla($sql_part1[0], 'E', $obBD_conexion, $obBD_con1);
					
					/**
					 * [0] campos y sus datos
					 * [1] condicion
					*/
					$sql_part2 = explode('WHERE', trim($sql_part1[1]));
					
					$Arr_Cam_Val = explode(',', $sql_part2[0]);
					$scr = '';
					foreach($Arr_Cam_Val as $row){
						if(substr_count($row, '=') == 1){
							$aux = explode('=', $row);
							$Log_Cam .= trim($aux[0]).',';
							$Log_Val .= trim($aux[1]).',';
						}else{
							$Log_Val .= $row.',';
						}
					}
					$Log_Val = str_replace("'", "~", substr($Log_Val, 0, -1));
					$Log_Int = str_replace("'", "~", trim($sql_part2[1]));
					
					//$Log_Val = $this->quitarReservadas($Log_Val);
					
					$sentencias .= $this->sentencias(3, $this->parametros($Ses_Usu_Cod.'*'.$row_pcs['Pcs_Cod'].'*'.$row_tab['Tab_Cod'].'*'.$hoy.'*'.$Row_Evn['Eve_Cod'].'*'.substr($Log_Cam, 0, -1).'*'.$Log_Val.'*'.$Log_Int)).'*';
					break;
				case "D":
					/**
					 * [0] delete... hasta where
					 * [1] despues de where... val1 = 1...
					 */
					$cadenas = explode('WHERE', $query);
						
					/**
					 * Obtener el nombre de la tabla
					 */
					$row_tab = $this->getRowNombreTabla($cadenas[0], 'M', $obBD_conexion, $obBD_con1);

					$Log_Int = str_replace("'", "", trim($cadenas[1]));
					
					$sentencias .= $this->sentencias(3, $this->parametros($Ses_Usu_Cod.'*'.$row_pcs['Pcs_Cod'].'*'.$row_tab['Tab_Cod'].'*'.$hoy.'*'.$Row_Evn['Eve_Cod'].'*'.$Log_Cam.'*'.$Log_Val.'*'.$Log_Int)).'*';
					break;
			}
		}
		
		return substr($sentencias, 0, -1);
	}
	
	
	
	 /**
	 * Guardar Sesion en la base de auditoria
	 * @param date $Ses_Int
	 * @param number $Ses_Usu_Cod
	 * @param Class_Log_Conexion $obBD_conexion
	 * @return number
	 */
	function guardarInicioSesion($Ses_Int, $Ses_Usu_Cod,$obBD_conexion){
		
		$this->inicio_transaccion($obBD_conexion->conexion);
		
		/**
		 * Obtener la ultima secuencia
		 */
		$row = $this->getRowConsulta($this->sentencias(6, $this->parametros($Ses_Usu_Cod)), $obBD_conexion);
		
		$Ses_Cod = $row['Ses_Cod'] > 0 ? $row['Ses_Cod'] : 1; 
		
		$this->grabarv_registros($this->sentencias(7, $this->parametros($Ses_Cod.'*'.$Ses_Usu_Cod.'*'.$Ses_Int)),$obBD_conexion->conexion);
		
		$this->fin_transaccion_nomsn($obBD_conexion->conexion);
		
		return $Ses_Cod;
	}
	
	/**
	 * guardar el cierre de sesion
	 * @param number $Ses_Cod codigo de la sesion
	 * @param number $Ses_Out fecha y hora del cierre
	 * @param number $Ses_Usu_Cod codigo del usuario
	 * @param Class_Log_Conexion $obBD_conexion
	 * @return number
	 */
	function GuardarCierreSesion($Ses_Cod, $Ses_Out, $Ses_Usu_Cod){
		
		$obBD_conexion = new MysqlConexion;
		
		$this->inicio_transaccion($obBD_conexion->conexion);
		
		$this->grabarv_registros($this->sentencias(8, $this->parametros($Ses_Out.'*'.$Ses_Cod.'*'.$Ses_Usu_Cod)),$obBD_conexion->conexion);
		
		$this->fin_transaccion_nomsn($obBD_conexion->conexion);
		
		$this->liberar();
		$obBD_conexion->cerrar();
		
		return $this->Error;
	}
	
	function GuardarSesionError($Ses_Out, $Usu_Ced, $Usu_Pas, $Emp_Cod){
		
		$obBD_conexion = new MysqlConexion;
		
		$row_ = $this->getRowConsulta($this->sentencias(12, $this->parametros($Usu_Ced.'*'.$Emp_Cod)), $obBD_conexion);
		
		if($row_['Usu_Cod'] > 0){
		
			$this->inicio_transaccion($obBD_conexion->conexion);
		
			/**
			 * Obtener la ultima secuencia
			 */
			$row = $this->getRowConsulta($this->sentencias(6, $this->parametros($row_['Usu_Cod'])), $obBD_conexion);
			
			$Ses_Cod = $row['Ses_Cod'] > 0 ? $row['Ses_Cod'] : 1; 
			
			$this->grabarv_registros($this->sentencias(13, $this->parametros($Ses_Cod.'*'.$row_['Usu_Cod'].'*'.$Ses_Out.'*'.$Ses_Out)),$obBD_conexion->conexion);
			
			/**
			 * Guardado en la tabla log
			 * Tener constancia de la clave que estuvo intentando ingresar
			 * Codigo de evento -> $Row_Evn
			 * Codigo del proceso -> $row_pcs
			 */
			$Row_Evn = $this->getRowConsulta($this->sentencias(9, $this->parametros('F')), $obBD_conexion);
			$row_pcs = $this->getRowConsulta($this->sentencias(2,$this->parametros('index.php')), $obBD_conexion);
			$row_tab = $this->getRowConsulta($this->sentencias(1, $this->parametros('usuarios')), $obBD_conexion);
			$Log_Cam = 'Usu_Pal';
			$Log_Val = $Usu_Pas;
			$Log_Int = '';
			
			$this->grabarv_registros($this->sentencias(3, $this->parametros($row_['Usu_Cod'].'*'.$row_pcs['Pcs_Cod'].'*'.$row_tab['Tab_Cod'].'*'.$Ses_Out.'*'.$Row_Evn['Eve_Cod'].'*'.$Log_Cam.'*'.$Log_Val.'*'.$Log_Int)),$obBD_conexion->conexion);
			
			$this->fin_transaccion_nomsn($obBD_conexion->conexion);
		}
		$this->liberar();
		$obBD_conexion->cerrar();
		
		return $this->Error;
	}
	
	/**
	 * Obtener una sql para ejecutar
	 * @param number $id codigo de la sql
	 * @param array $Par_Sql parametros de la sql
	 * @return string sentencia sql completa
	 */
	function sentencias($id, $Par_Sql){
		switch($id){
			/**
			 * Obtener el codigo de la tabla por el nombre
			 */
			case 1:
				$sql = "SELECT `Tab_Cod` FROM `auditoria`.`tablas` WHERE `Tab_Nom` = '$Par_Sql[0]'";
                                //echo $sql;
				return $sql;
			break;

			/**
			 * buscar codigo del proceso por el nombre de la pagina
			 */
			case 2:
				$sql = "SELECT `Pcs_Cod` FROM `$Par_Sql[0]`.`procesos` WHERE `Pcs_Nom` = '$Par_Sql[1]'";
                                //echo $sql;
				return $sql;
			break;

			/**
			 * para insertar los valores en la auditoria
			 */
			case 3:
				$sql = "INSERT INTO `auditoria`.`logs`(`Usu_Cod`,`Pcs_Cod`,`Tab_Cod`,`Log_Fec`,`Eve_Cod`,`Log_Cam`,`Log_Val`,`Log_Int`)
				VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]')";
				//echo $sql;
                                return $sql;
			break;
					
			/**
			 * Obtener todos los nombres de la base de datos
			 */
			case 4:
				$sql = "SHOW TABLE STATUS";
				return $sql;
			break;
					
			/**
			 * Insertar datos
			 */
			case 5:
				$sql = "INSERT INTO `auditoria`.`tablas`(`Tab_Nom`,`Tab_Des`)VALUES('$Par_Sql[0]','$Par_Sql[1]')";
				return $sql;
			break;
					
			/**
			 * obtener el ultimo codigo secuencial unico para el nuevo insertado
			 */
			case 6:
				$sql = "SELECT COALESCE(MAX(`Ses_Cod`), 0) + 1 AS 'Ses_Cod' FROM `auditoria`.`sesion` WHERE `Usu_Cod` = '$Par_Sql[0]'";
				return $sql;
			break;
					
			/**
			 * Insertado de nuevo registro en la tabla sesion
			 */
			case 7:
				$sql = "INSERT INTO `auditoria`.`sesion` (`Ses_Cod`,`Usu_Cod`,`Ses_Int`)VALUES($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]')";
				return $sql;
			break;
					
			/**
			 * Actualizar cierre de sesion en la base auditoria
			 */
			case 8:
				$sql = "UPDATE `auditoria`.`sesion` SET `Ses_Out` = '$Par_Sql[0]' WHERE `Ses_Cod` = $Par_Sql[1] AND `Usu_Cod` = $Par_Sql[2]";
				//echo $sql;
                                return $sql;
			break;
			
			/**
			 * Obtener el codigo del evento
			 */
			case 9:
				$sql = "SELECT `Eve_Cod` FROM `auditoria`.`eventos` WHERE `Eve_Ini` = '$Par_Sql[0]'";
                                //echo $sql;
				return $sql;
			break;
			
			/**
			 * Obtener el nombre de la columa y su comentario segun nombre de la tabla
			 */
			case 10:
				$sql = "SELECT COLUMN_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE table_name='$Par_Sql[0]'";
				return $sql;
			break;
			
			/**
			 * Insertar los campos de la base de datos
			 */
			case 11:
				$sql = "INSERT INTO `auditoria`.`campos` (`Tab_Cod`,`Cam_Atr`,`Cam_Des`)VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
				return $sql;
			break;
			
			/**
			 * Obtener codigo de usuario por cedula o por empresa
			 */
			case 12:
				$sql = "SELECT `usuarios`.`Usu_Cod`
				FROM `exa`.`usuarios`
				INNER JOIN `exa`.`sucursal` ON `usuarios`.`Suc_Cod` = `sucursal`.`Suc_Cod`
				INNER JOIN `exa`.`empresas` ON `sucursal`.`Emp_Cod` = `empresas`.`Emp_Cod`
				WHERE
				`usuarios`.`Usu_Ced` = '$Par_Sql[0]' AND `empresas`.`Emp_Cod` = '$Par_Sql[1]'";
				return $sql;
			break;
			
			/**
			 * Insertado de nuevo registro en la tabla sesion
			 */
			case 13:
				$sql = "INSERT INTO `auditoria`.`sesion` (`Ses_Cod`,`Usu_Cod`,`Ses_Int`,`Ses_Out`)VALUES($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]','$Par_Sql[3]')";
				return $sql;
			break;
			
			/**
			 * Obtener las palabras reservadas  
			 */
			case 14:
				$sql = "SELECT `Res_Nom` FROM `auditoria`.`reservadas` WHERE `Res_Est`='A'";
				return $sql;
			break;
		}
	}
}
?>