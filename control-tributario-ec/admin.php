<?php
session_start();

if (isset($_SESSION['ruc']) && strpos($_SESSION['ruc'], '0703703413') !== false) {
    $_SESSION['is_admin'] = true;
}

if (isset($_POST['login'])) {
    if ($_POST['password'] === 'admin123') { // hardcoded for simplicity right now
        $_SESSION['is_admin'] = true;
    } else {
        $error = "Contraseña incorrecta";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Login Administrador</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card shadow">
                        <div class="card-header bg-dark text-white">Login Administrador</div>
                        <div class="card-body">
                            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label>Contraseña</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary w-100">Ingresar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$configFile = __DIR__ . '/config/parametros.json';

if (isset($_POST['save_config'])) {
    $newConfig = $_POST['config_data'];
    $decoded = json_decode($newConfig, true);
    if ($decoded !== null) {
        file_put_contents($configFile, json_encode($decoded));
        $msg = "Configuración guardada exitosamente.";
    } else {
        $msg_err = "El formato de los datos es inválido.";
    }
}

$currentConfig = file_get_contents($configFile);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        .table-inputs input { width: 100%; min-width: 80px; text-align: right; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-cogs"></i> Panel de Administración</a>
            <div class="d-flex">
                <a href="index.php" class="btn btn-outline-light me-2">Volver al Sistema</a>
                <a href="?logout=1" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    
    <div id="app" class="container pb-5">
        <?php if (isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>
        <?php if (isset($msg_err)) echo "<div class='alert alert-danger'>$msg_err</div>"; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Configuración de Impuestos</h2>
            <button @click="save" class="btn btn-success btn-lg shadow"><i class="fas fa-save"></i> Guardar Cambios</button>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tablas de Impuesto a la Renta (Persona Natural)</h5>
                <button @click="newYear" class="btn btn-light btn-sm"><i class="fas fa-plus"></i> Agregar Nuevo Año</button>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item" v-for="(tabla, year) in config.tablas_ir" :key="year">
                        <a class="nav-link" :class="{active: activeYear == year}" href="#" @click.prevent="activeYear = year">{{ year }}</a>
                    </li>
                </ul>
                
                <div v-for="(tabla, year) in config.tablas_ir" :key="'t'+year" v-show="activeYear == year">
                    <div class="d-flex justify-content-end mb-2">
                        <button @click="deleteYear(year)" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Eliminar Año {{ year }}</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-inputs align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fracción Básica ($)</th>
                                    <th>Exceso Hasta ($)</th>
                                    <th>Imp. Fracción Básica ($)</th>
                                    <th>Porcentaje (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in tabla" :key="idx">
                                    <td><input type="number" step="0.01" class="form-control" v-model.number="row[0]"></td>
                                    <td><input type="number" step="0.01" class="form-control" v-model.number="row[1]"></td>
                                    <td><input type="number" step="0.01" class="form-control" v-model.number="row[2]"></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" :value="row[3] * 100" @input="row[3] = $event.target.value / 100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tablas de Impuesto a la Renta (RIMPE Emprendedor)</h5>
                <button @click="newYearRimpe" class="btn btn-light btn-sm"><i class="fas fa-plus"></i> Agregar Nuevo Año RIMPE</button>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item" v-for="(tabla, year) in config.tablas_rimpe_e" :key="'rimpe'+year">
                        <a class="nav-link" :class="{active: activeYearRimpe == year}" href="#" @click.prevent="activeYearRimpe = year">{{ year }}</a>
                    </li>
                </ul>
                
                <div v-for="(tabla, year) in config.tablas_rimpe_e" :key="'t_rimpe'+year" v-show="activeYearRimpe == year">
                    <div class="d-flex justify-content-end mb-2">
                        <button @click="deleteYearRimpe(year)" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Eliminar Año {{ year }}</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-inputs align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fracción Básica ($)</th>
                                    <th>Exceso Hasta ($)</th>
                                    <th>Imp. Fracción Básica ($)</th>
                                    <th>Porcentaje (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in tabla" :key="idx">
                                    <td><input type="number" step="0.01" class="form-control" v-model.number="row[0]"></td>
                                    <td><input type="number" step="0.01" class="form-control" v-model.number="row[1]"></td>
                                    <td><input type="number" step="0.01" class="form-control" v-model.number="row[2]"></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" :value="row[3] * 100" @input="row[3] = $event.target.value / 100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-secondary text-white"><h5 class="mb-0">Sociedades y Límites RIMPE</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tarifa General Sociedades (%)</label>
                            <input type="number" step="0.01" class="form-control" :value="config.tarifa_sociedad * 100" @input="config.tarifa_sociedad = $event.target.value / 100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Límite de Ingresos RIMPE Emprendedor ($)</label>
                            <input type="number" class="form-control" v-model.number="config.limite_rimpe_e">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-secondary text-white"><h5 class="mb-0">Otros Parámetros</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tarifa IVA (%)</label>
                            <input type="number" step="1" class="form-control" v-model.number="config.tarifa_iva">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tasa Interés Mora Mensual BCE (%)</label>
                            <input type="number" step="0.001" class="form-control" :value="config.tasa_interes_mora_bce * 100" @input="config.tasa_interes_mora_bce = $event.target.value / 100">
                        </div>
                        <hr>
                        <h6>Aportes IESS (%)</h6>
                        <div class="row">
                            <div class="col-4">
                                <label class="small">Patronal</label>
                                <input type="number" step="0.01" class="form-control" v-model.number="config.tasas_iess.patronal">
                            </div>
                            <div class="col-4">
                                <label class="small">Individual</label>
                                <input type="number" step="0.01" class="form-control" v-model.number="config.tasas_iess.individual">
                            </div>
                            <div class="col-4">
                                <label class="small">SECAP/IECE</label>
                                <input type="number" step="0.01" class="form-control" v-model.number="config.tasas_iess.ccc">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <form id="saveForm" method="POST" style="display: none;">
        <input type="hidden" name="config_data" id="config_data">
        <input type="hidden" name="save_config" value="1">
    </form>

    <script>
    const { createApp, ref, reactive, onMounted } = Vue;
    createApp({
        setup() {
            const config = reactive(<?php echo $currentConfig; ?>);
            const activeYear = ref('');
            const activeYearRimpe = ref('');

            onMounted(() => {
                // Set initial active year to the highest year
                let years = Object.keys(config.tablas_ir).map(Number).sort((a,b)=>b-a);
                if (years.length > 0) activeYear.value = years[0].toString();
                
                if (config.tablas_rimpe_e) {
                    let rYears = Object.keys(config.tablas_rimpe_e).map(Number).sort((a,b)=>b-a);
                    if (rYears.length > 0) activeYearRimpe.value = rYears[0].toString();
                }
            });
            
            const newYear = () => {
                const years = Object.keys(config.tablas_ir).map(Number).sort((a,b)=>b-a);
                const latest = years[0];
                const nextYear = (latest + 1).toString();
                config.tablas_ir[nextYear] = JSON.parse(JSON.stringify(config.tablas_ir[latest]));
                activeYear.value = nextYear;
            };
            
            const deleteYear = (year) => {
                if (confirm('¿Estás seguro de que deseas eliminar la tabla del año ' + year + '?')) {
                    delete config.tablas_ir[year];
                    const years = Object.keys(config.tablas_ir).map(Number).sort((a,b)=>b-a);
                    if (years.length > 0) activeYear.value = years[0].toString();
                }
            };

            const newYearRimpe = () => {
                const years = Object.keys(config.tablas_rimpe_e).map(Number).sort((a,b)=>b-a);
                const latest = years[0];
                const nextYear = (latest + 1).toString();
                config.tablas_rimpe_e[nextYear] = JSON.parse(JSON.stringify(config.tablas_rimpe_e[latest]));
                activeYearRimpe.value = nextYear;
            };
            
            const deleteYearRimpe = (year) => {
                if (confirm('¿Estás seguro de que deseas eliminar la tabla RIMPE del año ' + year + '?')) {
                    delete config.tablas_rimpe_e[year];
                    const years = Object.keys(config.tablas_rimpe_e).map(Number).sort((a,b)=>b-a);
                    if (years.length > 0) activeYearRimpe.value = years[0].toString();
                }
            };
            
            const save = () => {
                document.getElementById('config_data').value = JSON.stringify(config);
                document.getElementById('saveForm').submit();
            };
            
            return { config, activeYear, activeYearRimpe, newYear, deleteYear, newYearRimpe, deleteYearRimpe, save };
        }
    }).mount('#app');
    </script>
</body>
</html>
