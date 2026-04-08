<?php

/**
 * Reporte de Registro de Anticipo
 * Genera un reporte en formato HTML para imprimir el registro de anticipo
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_ant_1.0.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Manifiesto($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Manifiesto;

// Obtener el código del anticipo
$Ama_Cod = isset($_GET['Ama_Cod']) ? $_GET['Ama_Cod'] : '';

if (empty($Ama_Cod)) {
  die('No se proporcionó el código del anticipo');
}

// Obtener los datos del anticipo
$valores = array('Ama_Cod' => $Ama_Cod);
$anticipo = $obBD_con1->getRowConsulta(13, $valores, $obBD_conexion);

if ($obBD_con1->Error != 0 || !$anticipo) {
  die('No se encontraron datos del anticipo');
}

// Preparar los datos para la plantilla
$cedula_ruc = isset($anticipo['Prs_Ced']) ? $anticipo['Prs_Ced'] : '---';
$cliente = isset($anticipo['cliente']) ? $anticipo['cliente'] : '---';
$planta = isset($anticipo['Pla_Nom']) ? $anticipo['Pla_Nom'] : '---';
$licencia = isset($anticipo['Pla_Lic']) ? $anticipo['Pla_Lic'] : '---';
$fecha = isset($anticipo['Ama_Fec']) ? $anticipo['Ama_Fec'] : '---';
$tipo_pago = isset($anticipo['Pag_Des']) ? $anticipo['Pag_Des'] : '---';
$banco_acreditar = isset($anticipo['Ban_Cue']) ? $anticipo['Ban_Cue'] : '---';
if (isset($anticipo['Ban_Obs']) && !empty($anticipo['Ban_Obs'])) {
  $banco_acreditar .= ' - ' . $anticipo['Ban_Obs'];
}
$banco_origen = isset($anticipo['Bak_Des']) ? $anticipo['Bak_Des'] : 'Ninguno';
$num_documento = isset($anticipo['Ama_Doc']) && !empty($anticipo['Ama_Doc']) ? $anticipo['Ama_Doc'] : '---';
$valor = isset($anticipo['Ama_Val']) ? number_format($anticipo['Ama_Val'], 2, '.', ',') : '0.00';
$observaciones = isset($anticipo['Ama_Obs']) ? $anticipo['Ama_Obs'] : '';

// Determinar el estado
$estado = 'PENDIENTE';
$estado_class = 'pendiente';
if (isset($anticipo['Ama_Tip'])) {
  if ($anticipo['Ama_Tip'] == 'P') {
    $estado = 'En Revisión...';
    $estado_class = 'pendiente';
  } elseif ($anticipo['Ama_Tip'] == 'A') {
    $estado = 'ACREDITADO';
    $estado_class = 'aprobado';
  } elseif ($anticipo['Ama_Tip'] == 'R') {
    $estado = 'RECHAZADO';
    $estado_class = 'rechazado';
  }
}

// Cerrar conexiones
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Registro de Anticipo</title>

  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    @page {
      size: A4;
      margin: 10mm;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      background: #f5f7fa;
      color: #000;
    }

    .page {
      width: 210mm;
      margin: 8mm auto;
      background: #fff;
      padding: 8mm;
    }

    .header {
      text-align: center;
      border-bottom: 1px solid #aaa;
      padding-bottom: 3mm;
      margin-bottom: 3mm;
    }

    .header h1 {
      font-size: 14px;
      letter-spacing: 1px;
    }

    .status {
      margin-top: 4px;
      font-weight: bold;
    }

    .status.pendiente {
      color: #b36b00
    }

    .status.aprobado {
      color: #28a745
    }

    .status.rechazado {
      color: #dc3545
    }

    .section {
      margin-bottom: 4px;
    }

    .section-title {
      font-weight: bold;
      margin-bottom: 3px;
      font-size: 11px;
    }

    .box {
      border: 1px solid #aaa;
      padding: 4px;
    }

    .row {
      display: flex;
      gap: 10px;
      margin-bottom: 3px;
    }

    .col {
      flex: 1
    }

    .col-full {
      width: 100%;
      margin-bottom: 3px
    }

    .label {
      font-weight: bold
    }

    /* OBSERVACIONES */
    .observaciones-section {
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .observaciones-box {
      border: 1px solid #aaa;
      padding: 4px;
      min-height: 24px;
      max-height: 28px;
      overflow: hidden;
      white-space: pre-wrap;
    }

    @media print {
      body {
        margin: 0
      }

      .page {
        padding: 8mm
      }
    }
  </style>
</head>

<body>

  <div class="page">

    <!-- ENCABEZADO -->
    <div class="header">
      <h1>REGISTRO DE ANTICIPO</h1>
      <div class="status <?php echo $estado_class; ?>">Estado: <?php echo htmlspecialchars($estado); ?></div>
    </div>

    <!-- DATOS DEL CLIENTE -->
    <div class="section">
      <div class="section-title">Datos del Cliente</div>
      <div class="box">
        <div class="col-full">
          <span class="label">Cliente:</span> <?php echo htmlspecialchars($cliente); ?>
        </div>
        <div class="col-full">
          <span class="label">Cédula / RUC:</span> <?php echo htmlspecialchars($cedula_ruc); ?>
        </div>
        <div class="row">
          <div class="col"><span class="label">Planta:</span> <?php echo htmlspecialchars($planta); ?></div>
          <div class="col"><span class="label">Licencia:</span> <?php echo htmlspecialchars($licencia); ?></div>
        </div>
      </div>
    </div>

    <!-- DATOS DEL ANTICIPO -->
    <div class="section">
      <div class="section-title">Datos del Anticipo</div>
      <div class="box">
        <div class="row">
          <div class="col"><span class="label">Tipo:</span> <?php echo htmlspecialchars($tipo_pago); ?></div>
          <div class="col"><span class="label">Fecha:</span> <?php echo htmlspecialchars($fecha); ?></div>
        </div>
        <div class="row">
          <div class="col">
            <span class="label">Acreditar a:</span>
            <?php echo htmlspecialchars($banco_acreditar); ?>
          </div>
        </div>
        <div class="row">
          <div class="col"><span class="label">Banco Origen:</span> <?php echo htmlspecialchars($banco_origen); ?></div>
          <div class="col"><span class="label">No. Documento:</span> <?php echo htmlspecialchars($num_documento); ?></div>
        </div>
        <div class="row">
          <div class="col"><span class="label">Valor:</span> $ <?php echo htmlspecialchars($valor); ?></div>
        </div>
      </div>
    </div>

    <!-- OBSERVACIONES -->
    <div class="observaciones-section">
      <div class="section-title">Observaciones</div>
      <div class="observaciones-box"><?php echo htmlspecialchars($observaciones); ?></div>
    </div>
    <br><br>

    <!-- IMAGEN DEL VOUCHER -->
    <div class="section">
      <div class="section-title">Imagen del Voucher</div>
      <div class="box">
        <img src="<?php echo htmlspecialchars($anticipo['Ama_Img']); ?>" alt="Imagen del Voucher" style="max-width:100%; max-height:100%;" />
      </div>
    </div>
  </div>

</body>

</html>