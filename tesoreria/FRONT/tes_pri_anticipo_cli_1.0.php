<?php
/**
* @abstract Reporte de comprobante contable (ingreso, egreso, diario)
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización: 2010-09-06
* Fecha de actualización  2012-04-29
* Fecha de actualización  2015-05-07
* @author Lewis Chimarro
*/
require_once('../../Librerias/config.php/register_globals.php'); 
require_once($APP_REAL_PATH.'/administrador/LOGICA/logica.php');
require_once('../LOGICA/tes_log_anticipo_cli_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion_get = new Class_Log_Conexion_Ant_cli($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Ant_cli;

$hoy = date("d-m-Y");
$fecha = explode('-', $hoy);

?>
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
<style type="text/css">
.TablaRepComprLeft {
    font-weight: normal;
}
.img-imp-ant{
  width: 95%;
}

.titulo-print{

}
.txt-center{
   text-align:center;
}

.table-pg {
  border-collapse: collapse;
}
.table-pg td{
   border: black 1px solid;
   padding-left:1%;
   padding-right:1%;
}
.table-pg th{
   border: black 1px solid;
   padding-left:1%;
   padding-right:1%;
}


</style>
</head>
<body>
<br>
<?php $anticipo=$obBD_con_get->getRowConsulta(36, $anticip,$obBD_conexion_get);?>
<?php $cliente=$obBD_con_get->getRowConsulta(35, $client,$obBD_conexion_get);?>
<?php $sucur=$obBD_con_get->getRowConsulta(39, $Ses_Suc_Cod,$obBD_conexion_get);?>
   <div class="center header-ant" align="center">
      <table width="100%">
         <tr>
            <td rowspan="3" width="150"><img class="img-imp-ant" src="<?php echo $Ses_Emp_Log?>" alt=""></td>
            <td colspan="2" align="center"><b><?php echo $Ses_Emp_Nom?></b></td>
            <td>
            <td style="text-align:right;" colspan="2"><?/*echo $sucur['Ciu_Des'].", ";*/?>
               <?php 
               $dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
               $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
               $AntFec=explode("-",$anticipo['Ant_Fec']);
               echo $anticipo['Ant_Fec'];
               ?>
            </td>
            </td>
         </tr>
         <tr>           
            <td colspan="2" align="center"><b>Direc.:</b> <?echo $sucur['Suc_Dir']?></td>
         </tr>
         <tr>
            <td colspan="2" align="center"><b>Tel.:</b> <?echo $sucur['Suc_Te1']?></td>
            <td><td><td width="150" style="border:1px solid;text-align:right;">$ <?php echo number_format($anticipo['Ant_Val'], 2, '.', ',')?></td></td></td>
         </tr>
      </table>
   </div>
   <hr>
   
   <div align="center"> <b class="titulo-print"><u>ANTICIPO DE CLIENTE</u> &nbsp; #ANT-<span id="ant_doc_ver"><?echo $anticipo['Ant_Doc']?></span></b> </div><br>
   <table>
      <tr>
         <td width="20%"></td>
         <td></td>
      </tr>
      <tr>
         <td style="vertical-align:top;"><b>Recibimos de:</b></td>
         <td><?echo $cliente['nombre']?> CON C&Eacute;DULA <?echo $cliente['Prs_Ced']?></td>
      </tr>
      <tr>
         <td><b>La cantidad de:</b></td>
         <td> <?php echo num2letras($anticipo['Ant_Val'],false).' DOLARES AMERICANOS'; ?></td>
      </tr>
   </table>
   <br>
   <div align="center">
      <?php $tipos_pag = $obBD_con_get->getArrayConsulta(37, array('Ant_Cod'=>$anticip), $obBD_conexion_get);
      $table_htm="<table class='table-pg'>"
      ."<thead>"
      ."<th>Tipo</th>"
      ."<th>N&uacute;mero</th>"
      ."<th>Cuenta</th>"
      ."<th>Documento</th>"
      ."<th>Valor</th>"
      ."</thead>";      
      foreach ($tipos_pag as $tpg) {
         
         $pago = $obBD_con_get->getArrayConsulta(38, array('Ant_Cod'=>$anticip,'Pag_Cod'=>$tpg['Pag_Cod']), $obBD_conexion_get);
         foreach ($pago as $pg) {
            if($tpg['Pag_Abr']=='EFE'){
               $table_htm.="<tr><td width='85'>".$tpg['Pag_Des']."</td><td width='70'> ".$pg['Che_Num']."</td><td width='300'>".$pg['Pac_Cto']."</td><td width='150'>".$pg['Pac_Num']."</td><td width='120' style='text-align:right;'> $ ".number_format($pg['Pac_Val'], 2, '.', ',')."</td></tr>";
            }
            if($tpg['Pag_Abr']=='DEP'){
               $table_htm.="<tr><td width='85'>".$tpg['Pag_Des']."</td><td width='70'> ".$pg['Che_Num']."</td><td width='300'>".$pg['Pac_Cto']."</td><td width='150'>".$pg['Pac_Num']."</td><td width='120' style='text-align:right;'> $ ".number_format($pg['Pac_Val'], 2, '.', ',')."</td></tr>";
            }
            if($tpg['Pag_Abr']=='TRF'){
               $table_htm.="<tr><td width='85'>".$tpg['Pag_Des']."</td><td width='70'> ".$pg['Che_Num']."</td><td width='300'>".$pg['Pac_Cto']."</td><td width='150'>".$pg['Pac_Num']."</td><td width='120' style='text-align:right;'> $ ".number_format($pg['Pac_Val'], 2, '.', ',')."</td></tr>";
            }
            if($tpg['Pag_Abr']=='OTR'){
               $table_htm.="<tr><td width='85'>".$tpg['Pag_Des']."</td><td width='70'> ".$pg['Che_Num']."</td><td width='300'> ".$pg['Pac_Cto']." </td><td width='150'>".$pg['Pac_Num']."</td><td width='120' style='text-align:right;'> $ ".number_format($pg['Pac_Val'], 2, '.', ',')."</td></tr>";
            }
            if($tpg['Pag_Abr']=='CHE'){
               $table_htm.="<tr><td width='85'>".$tpg['Pag_Des']."</td><td width='70'> ".$pg['Che_Num']."</td>".
               "<td width='300'>".$pg['Pac_Cto']."</td>".
               "<td width='150'>".$pg['Pac_Num']."</td>".
               "<td width='120' style='text-align:right;'> $ ".number_format($pg['Pac_Val'], 2, '.', ',')."</td></tr>";
            }
         }
      }
      $table_htm.="</table><br>";
      echo $table_htm;
      ?>
   </div>
   <hr>
   <br>
   <div align="center">
      <table width="100%">
      <tr align="center">
         <td><u>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;</u></td>
      </tr>
      <tr align="center">
         <td><b><?echo $cliente['nombre']?></b></td>
      </tr>
      </table>
      <br>
      <table>
      <tr>
      <td><?echo $sucur['Ciu_Des']?>, 
         <?php 
         $dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
         $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
          
         echo $dias[date('w')]." ".date('d')." de ".$meses[date('n')-1]. " del ".date('Y') ;
         ?>
         </td>
      </tr>
      </table>
   </div>
   <script>
   $(document).ready(function () {
      var val_ant_doc=$("#ant_doc_ver").text();
      $("#ant_doc_ver").text((val_ant_doc*1).padLeft(4));
   });
   </script>
</body>
</html>