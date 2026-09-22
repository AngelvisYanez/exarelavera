<?php
/**
 * Script de Creación / Aprovisionamiento Integral de Empresa Local de Pruebas
 * 
 * Configura:
 * - exa_master: empresas, sucursal, data, access (para 22600781)
 * - exa: empresas, sucursal, usuarios, perfiles, perfiorgan (todos los 786 procesos), usuarperfi
 * - Directorios físicos (sri, facturación, imágenes)
 * - Datos Mock / Fallback (plan_cuenta, periodos, config_facturacion, marcas, bodegas, clientes, proveedores, rrhh)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';

$db = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS);
if (!$db) {
    die("Error conectando a MySQL: " . mysqli_connect_error() . "\n");
}
mysqli_set_charset($db, 'utf8');

$EMP_COD = 999;
$SUC_COD = 999;
$DAT_COD = 999;
$PER_COD = 999;
$USU_CED = '22600781';
$USU_PAL_HASH = 'e10adc3949ba59abbe56e057f20f883e'; // md5('123456')
$PRS_COD = 53102; // ANGELVIS YANEZ

// ----------------------------------------------------
// 1. REGISTRO EN EXA_MASTER
// ----------------------------------------------------
echo "\n[1/4] Configurando exa_master...\n";
mysqli_select_db($db, 'exa_master');

// 1.1 exa_master.empresas
$q = mysqli_query($db, "SELECT Emp_Cod FROM empresas WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO empresas (Emp_Cod, Emp_Nom, Emp_Ruc, Emp_Est, Emp_Cor) 
            VALUES ($EMP_COD, 'EMPRESA DE PRUEBAS LOCAL S.A.', '9999999999001', 'A', '! EMPRESA DE PRUEBAS LOCAL')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa_master.empresas: " . mysqli_error($db) . "\n");
    echo "  + Insertado exa_master.empresas (Emp_Cod: $EMP_COD)\n";
} else {
    mysqli_query($db, "UPDATE empresas SET Emp_Nom='EMPRESA DE PRUEBAS LOCAL S.A.', Emp_Est='A', Emp_Cor='! EMPRESA DE PRUEBAS LOCAL' WHERE Emp_Cod=$EMP_COD");
    echo "  = Ya existía exa_master.empresas, actualizado.\n";
}

// 1.2 exa_master.sucursal
$q = mysqli_query($db, "SELECT Suc_Cod FROM sucursal WHERE Suc_Cod = $SUC_COD");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO sucursal (Suc_Cod, Emp_Cod, Suc_Est, Suc_Des) 
            VALUES ($SUC_COD, $EMP_COD, 'A', 'SUCURSAL MATRIZ PRUEBAS')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa_master.sucursal: " . mysqli_error($db) . "\n");
    echo "  + Insertado exa_master.sucursal (Suc_Cod: $SUC_COD)\n";
} else {
    mysqli_query($db, "UPDATE sucursal SET Suc_Est='A', Suc_Des='SUCURSAL MATRIZ PRUEBAS' WHERE Suc_Cod=$SUC_COD");
    echo "  = Ya existía exa_master.sucursal, actualizado.\n";
}

// 1.3 exa_master.data
$q = mysqli_query($db, "SELECT Dat_Cod FROM data WHERE Dat_Cod = $DAT_COD");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO data (Dat_Cod, Emp_Cod, Dat_Dis, Dat_Est) 
            VALUES ($DAT_COD, $EMP_COD, 'exa', 'A')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa_master.data: " . mysqli_error($db) . "\n");
    echo "  + Insertado exa_master.data (Dat_Cod: $DAT_COD -> exa)\n";
} else {
    mysqli_query($db, "UPDATE data SET Dat_Dis='exa', Dat_Est='A' WHERE Dat_Cod=$DAT_COD");
    echo "  = Ya existía exa_master.data, actualizado.\n";
}

// 1.4 exa_master.access
$q = mysqli_query($db, "SELECT * FROM access WHERE Suc_Cod = $SUC_COD AND Dat_Cod = $DAT_COD AND Acc_Usr = '$USU_CED'");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr, Acc_Est) 
            VALUES ($SUC_COD, $DAT_COD, '$USU_CED', 'A')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa_master.access: " . mysqli_error($db) . "\n");
    echo "  + Insertado exa_master.access para usuario $USU_CED\n";
} else {
    mysqli_query($db, "UPDATE access SET Acc_Est='A' WHERE Suc_Cod=$SUC_COD AND Dat_Cod=$DAT_COD AND Acc_Usr='$USU_CED'");
    echo "  = Ya existía exa_master.access, actualizado a Activo.\n";
}

// ----------------------------------------------------
// 2. REGISTRO EN EXA (BASE DISTRIBUIDA)
// ----------------------------------------------------
echo "\n[2/4] Configurando base distribuida exa...\n";
mysqli_select_db($db, 'exa');

// 2.1 exa.empresas
$q = mysqli_query($db, "SELECT Emp_Cod FROM empresas WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO empresas (Emp_Cod, Emp_Ruc, Emp_Nom, Emp_Rep, Emp_Rce, Emp_Con, Emp_Rco, Emp_Est, Emp_Log, Emp_Cor, Emp_Act, Emp_Ren, Emp_Rre, Emp_Cnt) 
            VALUES ($EMP_COD, '9999999999001', 'EMPRESA DE PRUEBAS LOCAL S.A.', 'ANGELVIS YANEZ', '1', 'ANGELVIS YANEZ', '$USU_CED', 'A', '../../imagenes/$EMP_COD/logo.png', '! EMPRESA DE PRUEBAS LOCAL', 'EMPRESA DE PRUEBAS LOCAL Y DESARROLLO', 'ANGELVIS YANEZ', '$USU_CED', 'S')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa.empresas: " . mysqli_error($db) . "\n");
    echo "  + Insertado exa.empresas (Emp_Cod: $EMP_COD)\n";
} else {
    mysqli_query($db, "UPDATE empresas SET Emp_Nom='EMPRESA DE PRUEBAS LOCAL S.A.', Emp_Est='A', Emp_Cor='! EMPRESA DE PRUEBAS LOCAL', Emp_Cnt='S' WHERE Emp_Cod=$EMP_COD");
    echo "  = Ya existía exa.empresas, actualizado.\n";
}

// 2.2 exa.sucursal
$q = mysqli_query($db, "SELECT Suc_Cod FROM sucursal WHERE Suc_Cod = $SUC_COD");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO sucursal (Suc_Cod, Ciu_Cod, Emp_Cod, Suc_Sri, Suc_Des, Suc_Dir, Suc_Te1, Suc_Cor, Suc_Est, Suc_Com) 
            VALUES ($SUC_COD, 1, $EMP_COD, '001', 'SUCURSAL MATRIZ PRUEBAS', 'MATRIZ LOCAL DE PRUEBAS', '0999999999', 'test@exacontable.local', 'A', '! EMPRESA DE PRUEBAS LOCAL')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa.sucursal: " . mysqli_error($db) . "\n");
    echo "  + Insertado exa.sucursal (Suc_Cod: $SUC_COD)\n";
} else {
    mysqli_query($db, "UPDATE sucursal SET Suc_Est='A', Suc_Des='SUCURSAL MATRIZ PRUEBAS' WHERE Suc_Cod=$SUC_COD");
    echo "  = Ya existía exa.sucursal, actualizado.\n";
}

// 2.3 exa.usuarios
$q = mysqli_query($db, "SELECT Usu_Cod FROM usuarios WHERE Suc_Cod = $SUC_COD AND Usu_Ced = '$USU_CED'");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO usuarios (Prs_Cod, Suc_Cod, Usu_Ced, Usu_Pal, Usu_Tip, Usu_Est, Usu_Cad, Usu_Men) 
            VALUES ($PRS_COD, $SUC_COD, '$USU_CED', '$USU_PAL_HASH', 'A', 'A', 'N', 'T')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa.usuarios: " . mysqli_error($db) . "\n");
    $USU_COD = mysqli_insert_id($db);
    echo "  + Insertado exa.usuarios (Usu_Cod: $USU_COD para cédula $USU_CED)\n";
} else {
    $r = mysqli_fetch_assoc($q);
    $USU_COD = $r['Usu_Cod'];
    mysqli_query($db, "UPDATE usuarios SET Usu_Est='A', Usu_Pal='$USU_PAL_HASH', Usu_Tip='A', Usu_Cad='N', Usu_Men='T' WHERE Usu_Cod=$USU_COD");
    echo "  = Ya existía exa.usuarios (Usu_Cod: $USU_COD), actualizado.\n";
}

// 2.4 exa.perfiles (Administrador con todos los permisos)
$q = mysqli_query($db, "SELECT Per_Cod FROM perfiles WHERE Per_Cod = $PER_COD");
if (mysqli_num_rows($q) == 0) {
    $sql = "INSERT INTO perfiles (Per_Cod, Emp_Cod, Per_Des, Per_Est) 
            VALUES ($PER_COD, $EMP_COD, 'Administrador de Sistemas', 'A')";
    if (!mysqli_query($db, $sql)) die("Error insertando en exa.perfiles: " . mysqli_error($db) . "\n");
    echo "  + Insertado exa.perfiles (Per_Cod: $PER_COD)\n";
} else {
    mysqli_query($db, "UPDATE perfiles SET Per_Est='A', Per_Des='Administrador de Sistemas' WHERE Per_Cod=$PER_COD");
    echo "  = Ya existía exa.perfiles (Per_Cod: $PER_COD), actualizado.\n";
}

// 2.5 exa.perfiorgan (ASIGNAR TODOS LOS PROCESOS ACTIVOS AL PERFIL)
echo "  * Asignando procesos a perfil $PER_COD...\n";
$q_proc = mysqli_query($db, "SELECT Pro_Cod FROM procesos WHERE Pro_Est = 'A'");
$inserted_p = 0;
while ($row_p = mysqli_fetch_assoc($q_proc)) {
    $p_cod = $row_p['Pro_Cod'];
    $chk = mysqli_query($db, "SELECT * FROM perfiorgan WHERE Per_Cod = $PER_COD AND Pro_Cod = $p_cod");
    if (mysqli_num_rows($chk) == 0) {
        mysqli_query($db, "INSERT INTO perfiorgan (Per_Cod, Pro_Cod) VALUES ($PER_COD, $p_cod)");
        $inserted_p++;
    }
}
echo "  + Procesos asignados en perfiorgan: $inserted_p (de un total de " . mysqli_num_rows($q_proc) . " procesos activos)\n";

// 2.6 exa.usuarperfi (Vincular usuario con perfil)
$chk_up = mysqli_query($db, "SELECT * FROM usuarperfi WHERE Usu_Cod = $USU_COD AND Per_Cod = $PER_COD");
if (mysqli_num_rows($chk_up) == 0) {
    mysqli_query($db, "INSERT INTO usuarperfi (Usu_Cod, Per_Cod) VALUES ($USU_COD, $PER_COD)");
    echo "  + Vinculado usuario $USU_COD con perfil $PER_COD en usuarperfi\n";
} else {
    echo "  = Usuario $USU_COD ya estaba vinculado con perfil $PER_COD\n";
}

// ----------------------------------------------------
// 3. CREACIÓN DE DIRECTORIOS FÍSICOS
// ----------------------------------------------------
echo "\n[3/4] Creando directorios físicos del sistema...\n";
$baseDir = __DIR__;
$dirs = [
    $baseDir . "/tesoreria/FRONT/SRI/$EMP_COD",
    $baseDir . "/tesoreria/FRONT/SRI/$EMP_COD/firmas",
    $baseDir . "/tesoreria/FRONT/SRI/$EMP_COD/comprobantes",
    $baseDir . "/facturacion/FRONT/$EMP_COD",
    $baseDir . "/facturacion/FRONT/$EMP_COD/xml",
    $baseDir . "/imagenes/$EMP_COD"
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "  + Creado directorio: $dir\n";
        } else {
            echo "  ! Error creando directorio: $dir\n";
        }
    } else {
        echo "  = Directorio existente: $dir\n";
    }
}

// Crear logo dummy si no existe
$logoFile = $baseDir . "/imagenes/$EMP_COD/logo.png";
if (!file_exists($logoFile)) {
    // 1x1 png transparente
    file_put_contents($logoFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
    echo "  + Creado logo dummy en $logoFile\n";
}

// ----------------------------------------------------
// 4. DATOS MOCK / FALLBACK EN PROCESOS CLAVE
// ----------------------------------------------------
echo "\n[4/4] Sembrando datos mock y fallback para pruebas...\n";

// 4.1 Plan de Cuentas (Copiar de plan estándar si no tiene)
$q_pc = mysqli_query($db, "SELECT Pla_Cod FROM plan_cuenta WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($q_pc) == 0) {
    $sql = "INSERT INTO plan_cuenta (Emp_Cod, Pla_Des, Pla_Tip, Pla_Est) 
            VALUES ($EMP_COD, 'PLAN GENERAL DE CUENTAS PRUEBAS', 'C', 'A')";
    mysqli_query($db, $sql);
    $PLA_COD = mysqli_insert_id($db);
    echo "  + Creado Plan de Cuentas (Pla_Cod: $PLA_COD)\n";

    // Clonar cuentas del plan general (Pla_Cod = 1)
    $q_det = mysqli_query($db, "SELECT * FROM det_plan WHERE Pla_Cod = 1");
    $clonadas = 0;
    while ($row_d = mysqli_fetch_assoc($q_det)) {
        $cue_cod = mysqli_real_escape_string($db, $row_d['Cue_Cod']);
        $cue_nom = mysqli_real_escape_string($db, $row_d['Cue_Nom']);
        $cue_des = mysqli_real_escape_string($db, $row_d['Cue_Des']);
        $cue_tip = $row_d['Cue_Tip'];
        $cue_mov = $row_d['Cue_Mov'];
        $cue_bal = $row_d['Cue_Bal'];
        $cue_est = $row_d['Cue_Est'];
        $cue_pos = $row_d['Cue_Pos'];
        $cue_sri = mysqli_real_escape_string($db, $row_d['Cue_Sri']);

        $ins_cue = "INSERT INTO det_plan (Pla_Cod, Cue_Cod, Cue_Nom, Cue_Des, Cue_Tip, Cue_Mov, Cue_Bal, Cue_Est, Cue_Pos, Cue_Sri)
                    VALUES ($PLA_COD, '$cue_cod', '$cue_nom', '$cue_des', '$cue_tip', '$cue_mov', '$cue_bal', '$cue_est', '$cue_pos', '$cue_sri')";
        if (mysqli_query($db, $ins_cue)) $clonadas++;
    }
    echo "  + Cuentas contables clonadas al nuevo plan: $clonadas\n";
} else {
    echo "  = Plan de cuentas ya configurado.\n";
}

// 4.2 Períodos Contables 2025 y 2026
$periodos = [
    ['anio' => '2025', 'fec_des' => '2025-01-01', 'fec_has' => '2025-12-31'],
    ['anio' => '2026', 'fec_des' => '2026-01-01', 'fec_has' => '2026-12-31'],
];
foreach ($periodos as $p) {
    $chk_p = mysqli_query($db, "SELECT Per_Ano FROM perio_cont WHERE Emp_Cod = $EMP_COD AND Per_Ano = '{$p['anio']}'");
    if (mysqli_num_rows($chk_p) == 0) {
        $ins_p = "INSERT INTO perio_cont (Emp_Cod, Per_Ano, Per_Des, Per_Has, Per_Est)
                  VALUES ($EMP_COD, '{$p['anio']}', '{$p['fec_des']}', '{$p['fec_has']}', 'A')";
        mysqli_query($db, $ins_p);
        echo "  + Creado periodo contable {$p['anio']}\n";
    }

    // Activar mes de apertura / cierre en listado_apertura_det
    $chk_ap = mysqli_query($db, "SELECT * FROM listado_apertura_det WHERE Emp_Cod = $EMP_COD AND Per_Ano = '{$p['anio']}'");
    if (mysqli_num_rows($chk_ap) == 0) {
        for ($m = 1; $m <= 12; $m++) {
            $mes_pad = str_pad($m, 2, '0', STR_PAD_LEFT);
            mysqli_query($db, "INSERT INTO listado_apertura_det (Emp_Cod, Per_Ano, Mes_Cod, Lis_Est) VALUES ($EMP_COD, '{$p['anio']}', '$mes_pad', 'A')");
        }
        echo "  + Meses contables habilitados para periodo {$p['anio']}\n";
    }
}

// 4.3 Configuración de Facturación Electrónica (confi_fact)
$chk_cf = mysqli_query($db, "SELECT * FROM confi_fact WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_cf) == 0) {
    $ins_cf = "INSERT INTO confi_fact (Emp_Cod, Amb_Sri, Tip_Emi, Con_Esp, Obl_Lle, Con_Dir, Con_Fec_Ini)
               VALUES ($EMP_COD, '1', '1', 'NO', 'SI', 'MATRIZ LOCAL DE PRUEBAS', '2025-01-01')";
    mysqli_query($db, $ins_cf);
    echo "  + Configuración de facturación electrónica (confi_fact) inicializada en modo Pruebas (Amb: 1)\n";
}

// 4.4 Configuración de Parámetros de Ventas e IVA (confi_phva)
$chk_phva = mysqli_query($db, "SELECT * FROM confi_phva WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_phva) == 0) {
    $ins_phva = "INSERT INTO confi_phva (Emp_Cod, Por_Iva, Por_Ice, Por_Irb, Por_Ser) 
                 VALUES ($EMP_COD, 15.00, 0.00, 0.00, 0.00)";
    mysqli_query($db, $ins_phva);
    echo "  + Configuración de IVA (15%) registrada en confi_phva\n";
}

// 4.5 Marca Mock
$chk_mar = mysqli_query($db, "SELECT * FROM marca WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_mar) == 0) {
    mysqli_query($db, "INSERT INTO marca (Emp_Cod, Mar_Nom, Mar_Est) VALUES ($EMP_COD, 'GENERICA', 'A')");
    echo "  + Marca inicial mock creada\n";
}

// 4.6 Tipo de Precio Mock
$chk_tp = mysqli_query($db, "SELECT * FROM tipo_preci WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_tp) == 0) {
    mysqli_query($db, "INSERT INTO tipo_preci (Emp_Cod, Tip_Des, Tip_Est) VALUES ($EMP_COD, 'PVP NORMAL', 'A')");
    echo "  + Tipo de precio mock creado\n";
}

// 4.7 Ubicación Mock
$chk_ubi = mysqli_query($db, "SELECT * FROM ubicacion WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_ubi) == 0) {
    mysqli_query($db, "INSERT INTO ubicacion (Emp_Cod, Ubi_Des, Ubi_Est) VALUES ($EMP_COD, 'LOCAL PRINCIPAL', 'A')");
    echo "  + Ubicación mock creada\n";
}

// 4.8 Punto de Emisión Mock (puntos_imp)
$chk_pi = mysqli_query($db, "SELECT * FROM puntos_imp WHERE Suc_Cod = $SUC_COD");
if (mysqli_num_rows($chk_pi) == 0) {
    mysqli_query($db, "INSERT INTO puntos_imp (Suc_Cod, Pun_Sri, Pun_Des, Pun_Est) VALUES ($SUC_COD, '001', 'PUNTO DE EMISION 001', 'A')");
    echo "  + Punto de emisión mock 001 creado\n";
}

// 4.9 Bodega Mock
$chk_bod = mysqli_query($db, "SELECT * FROM bodega WHERE Suc_Cod = $SUC_COD");
if (mysqli_num_rows($chk_bod) == 0) {
    mysqli_query($db, "INSERT INTO bodega (Suc_Cod, Bod_Nom, Bod_Est) VALUES ($SUC_COD, 'BODEGA CENTRAL MATRIZ', 'A')");
    echo "  + Bodega central mock creada\n";
}

// 4.10 Cliente Consumidor Final y Cliente Prueba
$chk_cli = mysqli_query($db, "SELECT * FROM cliente WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_cli) == 0) {
    mysqli_query($db, "INSERT INTO cliente (Emp_Cod, Cli_Nom, Cli_Cir, Cli_Tip, Cli_Dir, Cli_Tel, Cli_Est) 
                       VALUES ($EMP_COD, 'CONSUMIDOR FINAL', '9999999999999', 'F', 'LOCAL', '9999999', 'A')");
    mysqli_query($db, "INSERT INTO cliente (Emp_Cod, Cli_Nom, Cli_Cir, Cli_Tip, Cli_Dir, Cli_Tel, Cli_Est) 
                       VALUES ($EMP_COD, 'CLIENTE DE PRUEBAS LOCAL', '0701234567', 'C', 'MACHALA', '0991234567', 'A')");
    echo "  + Clientes mock (Consumidor Final y Cliente Pruebas) creados\n";
}

// 4.11 Proveedor Mock
$chk_prv = mysqli_query($db, "SELECT * FROM proveedore WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_prv) == 0) {
    mysqli_query($db, "INSERT INTO proveedore (Emp_Cod, Pro_Nom, Pro_Ruc, Pro_Tip, Pro_Dir, Pro_Tel, Pro_Est)
                       VALUES ($EMP_COD, 'PROVEEDOR GENERAL DE PRUEBAS', '1790012345001', 'J', 'QUITO', '022345678', 'A')");
    echo "  + Proveedor mock creado\n";
}

// 4.12 RRHH Base (tipo_personal, areas, departamentos, personal mock)
$chk_tper = mysqli_query($db, "SELECT * FROM tipo_personal WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_tper) == 0) {
    mysqli_query($db, "INSERT INTO tipo_personal (Emp_Cod, Tip_Per_Nom, Tip_Per_Est) VALUES ($EMP_COD, 'ADMINISTRATIVO', 'A')");
    echo "  + Tipo de personal creado\n";
}
$chk_area = mysqli_query($db, "SELECT * FROM areas_rrhh WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_area) == 0) {
    mysqli_query($db, "INSERT INTO areas_rrhh (Emp_Cod, Are_Nom, Are_Est) VALUES ($EMP_COD, 'ADMINISTRACION', 'A')");
    echo "  + Área RRHH creada\n";
}
$chk_dep = mysqli_query($db, "SELECT * FROM departamen WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_dep) == 0) {
    mysqli_query($db, "INSERT INTO departamen (Emp_Cod, Dep_Nom, Dep_Est) VALUES ($EMP_COD, 'GERENCIA GENERAL', 'A')");
    echo "  + Departamento creado\n";
}
$chk_pers = mysqli_query($db, "SELECT * FROM personal WHERE Emp_Cod = $EMP_COD AND Per_Cir = '$USU_CED'");
if (mysqli_num_rows($chk_pers) == 0) {
    mysqli_query($db, "INSERT INTO personal (Emp_Cod, Suc_Cod, Per_Cir, Per_Nom, Per_Ape, Per_Dir, Per_Tel, Per_Est)
                       VALUES ($EMP_COD, $SUC_COD, '$USU_CED', 'ANGELVIS', 'YANEZ', 'MACHALA', '0999999999', 'A')");
    echo "  + Ficha de personal para usuario $USU_CED creada\n";
}

// 4.13 Parámetros de inventario (tipo_ajus)
$chk_taj = mysqli_query($db, "SELECT * FROM tipo_ajus WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_taj) == 0) {
    mysqli_query($db, "INSERT INTO tipo_ajus (Emp_Cod, Tip_Aju_Des, Tip_Aju_Tip, Tip_Aju_Est) VALUES ($EMP_COD, 'INGRESO POR INVENTARIO INICIAL', 'I', 'A')");
    mysqli_query($db, "INSERT INTO tipo_ajus (Emp_Cod, Tip_Aju_Des, Tip_Aju_Tip, Tip_Aju_Est) VALUES ($EMP_COD, 'EGRESO POR MERMA / DESECHO', 'E', 'A')");
    echo "  + Tipos de ajuste de inventario creados\n";
}

// 4.14 Horarios RRHH (tipo_horario)
$chk_hor = mysqli_query($db, "SELECT * FROM tipo_horario WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_hor) == 0) {
    mysqli_query($db, "INSERT INTO tipo_horario (Emp_Cod, Tip_Hor_Des, Tip_Hor_Est) VALUES ($EMP_COD, 'JORNADA REGULAR 8 HORAS', 'A')");
    echo "  + Tipo horario mock creado\n";
}

// 4.15 Activos fijos (tipo_activo)
$chk_act = mysqli_query($db, "SELECT * FROM tipo_activo WHERE Emp_Cod = $EMP_COD");
if (mysqli_num_rows($chk_act) == 0) {
    mysqli_query($db, "INSERT INTO tipo_activo (Emp_Cod, Tip_Act_Des, Tip_Act_Est) VALUES ($EMP_COD, 'EQUIPOS DE COMPUTACION', 'A')");
    echo "  + Tipo de activo mock creado\n";
}

// 4.16 Caja chica (caja_chica)
$chk_caj = mysqli_query($db, "SELECT * FROM caja_chica WHERE Suc_Cod = $SUC_COD");
if (mysqli_num_rows($chk_caj) == 0) {
    mysqli_query($db, "INSERT INTO caja_chica (Suc_Cod, Caj_Nom, Caj_Mon, Caj_Est) VALUES ($SUC_COD, 'CAJA CHICA GENERAL MATRIZ', 200.00, 'A')");
    echo "  + Caja chica mock creada\n";
}

echo "\n=======================================================\n";
echo " EMPRESA DE PRUEBAS LOCAL CREADA Y LISTA PARA USAR:\n";
echo " Empresa: [Emp_Cod: $EMP_COD] EMPRESA DE PRUEBAS LOCAL S.A. (! EMPRESA DE PRUEBAS LOCAL)\n";
echo " Sucursal: [Suc_Cod: $SUC_COD] SUCURSAL MATRIZ PRUEBAS\n";
echo " Usuario: $USU_CED\n";
echo " Clave: 123456\n";
echo " Perfil: Administrador de Sistemas (786 de 786 procesos)\n";
echo " Base de datos: exa\n";
echo "=======================================================\n";
