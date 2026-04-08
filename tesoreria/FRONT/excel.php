<?php
require_once("vendor/php-excel-reader/excel_reader2.php");
require_once("vendor/SpreadsheetReader.php");
require_once("../../administrador/LOGICA/seguridad.php");
require_once('../../DATA/MysqlConexion.php');

if (isset($_POST["import"])) {
    /* Creacion del Objeto de conexion */
    $conne = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $conn = $conne->conectar();

    $target_dir = "uploads/";
    $targetPath = $target_dir . basename($_FILES["file"]["name"]);

    move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);
    $Reader = new SpreadsheetReader($targetPath);

    $sheetCount = count($Reader->sheets());
    for ($i = 0; $i < 1; $i++) {

        $Reader->ChangeSheet($i);

        $contador = 0;
        $noIngresadas = "No se ingresaron las filas: ";
        $Ingresadas = "Estas filas ya fueron ingresadas: ";
        $filas = 0;

        foreach ($Reader as $Row) {
            $contador++;

            if ($contador > 1) {

                $cedula = "";
                if (isset($Row[0])) {
                    $cedula = mysqli_real_escape_string($conn, $Row[0]);
                }

                $nombre = "";
                if (isset($Row[1])) {
                    $nombre = mysqli_real_escape_string($conn, $Row[1]);
                }

                $apellido = "";
                if (isset($Row[2])) {
                    $apellido = mysqli_real_escape_string($conn, $Row[2]);
                }

                $genero = "";
                if (isset($Row[3])) {
                    $genero = mysqli_real_escape_string($conn, $Row[3]);
                }

                $direccion = "";
                if (isset($Row[4])) {
                    $direccion = mysqli_real_escape_string($conn, $Row[4]);
                }

                $telefono = "";
                if (isset($Row[5])) {
                    $telefono = mysqli_real_escape_string($conn, $Row[5]);
                }

                $celular = "";
                if (isset($Row[6])) {
                    $celular = mysqli_real_escape_string($conn, $Row[6]);
                }

                $correo = "";
                if (isset($Row[7])) {
                    $correo = mysqli_real_escape_string($conn, $Row[7]);
                }

                $ciudad = "";
                if (isset($Row[8])) {
                    $ciudad = mysqli_real_escape_string($conn, $Row[8]);
                }

                $identificacion = "";
                if (isset($Row[9])) {
                    $identificacion = mysqli_real_escape_string($conn, $Row[9]);
                }

                $tip_contribuyente = "";
                if (isset($Row[10])) {
                    $tip_contribuyente = mysqli_real_escape_string($conn, $Row[10]);
                }

                $contabilidad = "";
                if (isset($Row[11])) {
                    $contabilidad = mysqli_real_escape_string($conn, $Row[11]);
                }

                $tip_empresa = "";
                if (isset($Row[12])) {
                    $tip_empresa = mysqli_real_escape_string($conn, $Row[12]);
                }


                //validar campos obligatorios 

                if (!empty($cedula) && !empty($apellido)) {
                    //Se ingresa la persona
                    //Se obtiene el id de la persona ingresada
                    $sqlExistePersona = "SELECT Prs_Cod as prs_cod FROM persona where Prs_Ced = " . "'" . "$cedula" . "'";
                    $resultExiste = mysqli_query($conn, $sqlExistePersona);
                    $codigoPersona = null;
                    while ($row = mysqli_fetch_array($resultExiste)) {
                        $codigoPersona = $row['prs_cod'];
                    }

                    if ($codigoPersona == null) {
                        $queryPersona = "insert into persona(Prs_Ced,Prs_Nom,Prs_Ape,Prs_Sex,Prs_Dir,Prs_Tel,
                                                Prs_Cel,Prs_Cor,Ciu_Cod,Ide_Cod)
                                                values(
                                                '" . $cedula . "','" . $nombre . "','" . $apellido . "',
                                                '" . $genero . "','" . $direccion . "','" . $telefono . "',
                                                '" . $celular . "','" . $correo . "'," . $ciudad . ",
                                                " . $identificacion . ")";

                        $resultPersona = mysqli_query($conn, $queryPersona);

                        $sqlSelect = "SELECT MAX(Prs_Cod) as id FROM persona";
                        $result = mysqli_query($conn, $sqlSelect);
                        while ($row = mysqli_fetch_array($result)) {
                            $id = $row['id'];
                        }
                    } else {
                        $id = $codigoPersona;
                    }

                    $persona = NULL;
                    //$sqlExisteCliente = "SELECT Prs_Cod FROM cliente where Prs_Cod = " . $id;
                    $sqlExisteCliente = " SELECT Prs_Cod FROM cliente where Prs_Cod = " . $id . " AND Emp_Cod=" . $_SESSION['Ses_Emp_Cod'];
                    $resultExisteCliente = mysqli_query($conn, $sqlExisteCliente);
                    while ($row = mysqli_fetch_array($resultExisteCliente)) {
                        $persona = $row['Prs_Cod'];
                    }

                    $ingresoCliente = false;
                    if ($persona == null) {
                        //Se ingresa el cliente relacionado con la persona
                        $queryCliente = "insert into cliente(Prs_Cod,Cli_Tic,Cli_Con,Cli_Tip,Emp_Cod) values(" . $id . ",'" . $tip_contribuyente . "','" . $contabilidad . "', '" . $tip_empresa . "'," . $_SESSION['Ses_Emp_Cod'] . ")";
                        $resultCliente = mysqli_query($conn, $queryCliente);
                        $ingresoCliente = true;
                    } else {
                        $filas2 = 2;
                        $Ingresadas = $Ingresadas . $contador . ",";
                    }

                    if ($ingresoCliente == false) {
                        $filas = 1;
                        $noIngresadas = $noIngresadas . $contador . ",";
                    }
                } else {
                    $filas = 1;
                    $noIngresadas = $noIngresadas . $contador . ",";
                }
            }
        } //If del foreach 

        if ($filas == 0) {
            $type = "success";
            $message = "Se importaron los datos correctamente!";
        } else if ($filas == 1) {
            $type = "error";
            $message =  $noIngresadas;
        }
        if ($filas2 == 2) {
            $type = "success";
            $message = $Ingresadas;
        }

        /* if ($filas == 0) {
            $type = "success";
            $message = "Se importaron los datos correctamente!";
        } else {
            $type = "error";
            $message =  $noIngresadas;
        }*/
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script>

    <style>
        .success {
            background: #c7efd9;
            border: #bbe2cd 1px solid;
        }

        .error {
            background: #fbcfcf;
            border: #f3c6c7 1px solid;
        }

        div#response.display-block {
            display: block;
        }

        .custom-file-input~.custom-file-label::after {
            content: "Seleccionar";
        }
    </style>

    <title><?Php echo $Ses_Sys_Nom; ?></title>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.min.js"></script>

</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Importar clientes</h3>
        </div>

        <div class="row">
            <div class=" col-xs-6" style="margin-left: 10px; margin-top: 10px;">

                <form action="" method="post"
                    name="frmExcelImport" id="frmExcelImport" enctype="multipart/form-data">
                    <div class="text-center custom-file">
                        <input class="custom-file-input" type="file" name="file" id="file" accept=".xlsm" lang="es">
                        <label class="custom-file-label" for="file">Elegir archivo (usar el formato) .xlsm</label>
                    </div>

                    <div class="text-center mt-2">
                        <button type="submit" id="submit" name="import"
                            class="btn btn-md btn-success btn-submit"> <i class="glyphicon glyphicon-import"></i> Importar</button>
                    </div>
                </form>

                <div class="text-center mt-2" style="margin-bottom: 10px;">
                    <a href="formato.xlsm" download="importarclientes.xlsm">
                        <button class="btn btn-sm btn-info" type="button" title="Descargar formato"><i class="glyphicon glyphicon-download-alt"></i> Formato </button>
                    </a>
                </div>

                <script>
                    $('#file').on('change', function() {
                        //get the file name
                        fileName = $(this).val().replace('C:\\fakepath\\', " ");
                        //replace the "Choose a file" label
                        $(this).next('.custom-file-label').html(fileName);
                    })
                </script>

            </div>
        </div>
    </div>

    <div class="container">
        <div id="response" class="mx-auto col-md-6 mt-2 <?php if (!empty($type)) {
                                                            echo $type . " display-block";
                                                        } ?>"><?php if (!empty($message)) {
                                                                    echo $message;
                                                                } ?>
        </div>
    </div>

</body>

</html>