<?php
/**
 * plantillas_front.php
 * Interfaz de usuario para la administraciÃ³n de Plantillas Presupuestarias (EXA PPTO).
 * Permite listar, crear, editar, eliminar y duplicar plantillas usando jQuery & Bootstrap 3.
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Emp_Cod) && isset($_SESSION['Ses_Emp_Cod'])) {
    $Ses_Emp_Cod = $_SESSION['Ses_Emp_Cod'];
}

$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli_conn = $obBD_conexion->conexion;

$emp_nom_display = isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'Empresa Actual';
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Plantillas de Presupuestos - EXA</title>
    <?php require_once(__DIR__ . '/../../contabilidad/FRONT/con_model3_assets.php'); ?>
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <!-- CSS adicional del mÃ³dulo -->
    <style>
        .exa-pre-form-panel {
            background-color: var(--v2-bg-subtle, #f7fafc);
            border: var(--v2-elev-border);
            border-radius: var(--v2-radius);
            padding: 20px;
            margin-bottom: 20px;
        }
        .text-center-valign {
            vertical-align: middle !important;
        }
        .modal-body-custom {
            padding: 24px;
        }
        .badge-proyectos,
        .badge-partidas {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 700;
            display: inline-block;
            min-width: 28px;
        }
        .badge-partidas {
            background-color: #edf2f7;
            color: #2d3748;
        }
        .badge-partidas.has-items {
            background-color: #e6fffa;
            color: #234e52;
            border: 1px solid #81e6d9;
        }
        .ppto-plt-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
            flex-wrap: nowrap;
        }
        .ppto-plt-btn {
            width: 30px;
            height: 30px;
            padding: 0;
            line-height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .ppto-plt-btn i {
            font-size: 14px;
            line-height: 1;
        }
        .ppto-toast {
            display: none;
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 100001;
            min-width: 280px;
            max-width: 420px;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(0,0,0,0.15);
            color: #fff;
        }
        .ppto-toast-success { background: #38a169; }
        .ppto-toast-error { background: #e53e3e; }
        .ppto-toast-info { background: #3182ce; }
    </style>
</head>
<body class="exa-ui-fill-root">

<div class="panel panel-main exa-ui-panel exa-ui-fill-page">
    <div class="panel-heading exa-header exa-header-flex">
        <h3 class="panel-title"><i class="bi bi-file-earmark-spreadsheet-fill"></i> Plantillas Presupuestarias</h3>
        <div class="exa-header-actions">
            <span class="text-muted" style="font-size:12px;">Empresa: <?php echo htmlspecialchars($emp_nom_display); ?></span>
        </div>
    </div>
    
    <div class="panel-body exa-body">
        <div class="exa-ui-page-view">
            
            <div class="exa-pre-section-head" style="margin-bottom: 18px;">
                <h5 class="exa-adq-section-title" style="margin:0; border:0; padding:0;">
                    <i class="bi bi-folder-symlink text-primary"></i> CatÃ¡logo de plantillas
                </h5>
                <button class="btn btn-success btn-sm" onclick="modalCrearPlantilla()" title="Nueva plantilla">
                    <i class="bi bi-plus-lg"></i> Nueva
                </button>
            </div>

            <!-- TABLA DE LISTADO DE PLANTILLAS -->
            <div class="exa-adq-table-wrap">
                <table class="table table-bordered exa-adq-table" id="tabla_plantillas">
                    <thead>
                        <tr>
                            <th style="width: 80px;" class="text-center">ID</th>
                            <th>Nombre</th>
                            <th>DescripciÃ³n</th>
                            <th style="width: 72px;" class="text-center" title="Partidas en la plantilla">Partidas</th>
                            <th style="width: 90px;" class="text-center" title="Proyectos activos">Proyectos</th>
                            <th style="width: 80px;" class="text-center">Estado</th>
                            <th style="width: 120px;" class="text-center">Registro</th>
                            <th style="width: 110px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_plantillas">
                        <tr>
                            <td colspan="8" class="text-center text-muted" style="padding: 30px;">
                                <i class="bi bi-hourglass-split"></i> Cargando plantillas del sistema...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div><!-- exa-ui-page-view -->
    </div><!-- panel-body -->
</div><!-- panel -->


<!-- MODAL: REGISTRO / EDICIÃ“N DE PLANTILLA -->
<div id="modal_plantilla" class="exa-pre-modal-overlay">
    <div class="exa-pre-modal-box" style="width: 50%; max-width: 650px;">
        <span class="exa-pre-modal-close" onclick="cerrarModalPlantilla()">&times;</span>
        <h3 id="modal_plantilla_titulo" class="exa-adq-section-title">Formulario de Plantilla</h3>
        
        <form id="form_plantilla" onsubmit="event.preventDefault(); guardarPlantilla();">
            <input type="hidden" name="plt_id" id="form_plt_id" value="" />
            
            <div class="modal-body-custom">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; color: #2d3748; margin-bottom: 6px;">Nombre de la Plantilla <span style="color:#e53e3e;">*</span></label>
                    <input type="text" name="plt_nombre" id="form_plt_nombre" class="form-control input-sm" placeholder="Ej: Plantilla Base de Obras Civiles" required />
                </div>
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; color: #2d3748; margin-bottom: 6px;">Descripcion <span style="color:#718096; font-weight: normal;">(opcional)</span></label>
                    <textarea name="plt_descripcion" id="form_plt_descripcion" rows="3" class="form-control input-sm" style="resize: vertical;" placeholder="Proporciona detalles adicionales sobre el alcance de esta plantilla..."></textarea>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; color: #2d3748; margin-bottom: 6px;">Estado Administrativo</label>
                    <select name="plt_estado" id="form_plt_estado" class="form-control input-sm">
                        <option value="A">Activa (Apto para asignar a proyectos)</option>
                        <option value="I">Inactiva (Archivada para nuevos proyectos)</option>
                    </select>
                </div>
            </div>

            <div class="exa-pre-form-actions" style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <button type="button" class="btn btn-default btn-sm" onclick="cerrarModalPlantilla()">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>


<!-- MODAL: DUPLICAR/CLONAR PLANTILLA -->
<div id="modal_duplicar" class="exa-pre-modal-overlay">
    <div class="exa-pre-modal-box" style="width: 45%; max-width: 550px;">
        <span class="exa-pre-modal-close" onclick="cerrarModalDuplicar()">&times;</span>
        <h3 class="exa-adq-section-title">Clonar plantilla</h3>
        
        <form id="form_duplicar" onsubmit="event.preventDefault(); guardarDuplicacion();">
            <input type="hidden" name="dup_plt_id" id="dup_plt_id" value="" />
            
            <div class="modal-body-custom">
                <p class="text-muted" style="font-size: 13px; line-height: 1.5; margin-bottom: 18px;">
                    Se copiarÃ¡ la plantilla y sus partidas. Indique el nombre del duplicado:
                </p>
                <div class="form-group">
                    <label style="font-weight: 600; color: #2d3748; margin-bottom: 6px;">Nombre del Duplicado <span style="color:#e53e3e;">*</span></label>
                    <input type="text" name="dup_plt_nombre_nuevo" id="dup_plt_nombre_nuevo" class="form-control input-sm" placeholder="Ej: Plantilla de Obras Civiles - Copia" required />
                </div>
            </div>

            <div class="exa-pre-form-actions" style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <button type="button" class="btn btn-default btn-sm" onclick="cerrarModalDuplicar()">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-files"></i> Clonar</button>
            </div>
        </form>
    </div>
</div>


</div>

<div id="ppto_toast" class="ppto-toast" role="status" aria-live="polite"></div>

<script>
    const LOGICA_URL = '../LOGICA/plantillas_logica.php';

    function pptoToast(message, type) {
        type = type || 'success';
        const $t = $('#ppto_toast');
        $t.removeClass('ppto-toast-success ppto-toast-error ppto-toast-info')
          .addClass('ppto-toast-' + type)
          .text(message)
          .stop(true, true)
          .fadeIn(200);
        setTimeout(function() { $t.fadeOut(400); }, 3800);
    }

    function listarPlantillas() {
        const tbody = $('#tbody_plantillas');

        $.ajax({
            url: LOGICA_URL,
            type: 'GET',
            data: { action: 'listar' },
            dataType: 'json',
            success: function(res) {
                tbody.empty();
                if (res.status && res.data.length > 0) {
                    res.data.forEach(function(item) {
                        const tr = $('<tr>');
                        const partidas = parseInt(item.partidas_total, 10) || 0;

                        const badgePart = partidas > 0
                            ? '<span class="badge badge-partidas has-items" title="' + partidas + ' partida(s)">' + partidas + '</span>'
                            : '<span class="badge badge-partidas" title="Sin partidas">0</span>';

                        const badgeProy = item.proyectos_activos > 0
                            ? '<span class="badge badge-proyectos btn-primary" style="background-color:#3182ce;" title="' + item.proyectos_activos + ' proyecto(s)">' + item.proyectos_activos + '</span>'
                            : '<span class="badge badge-proyectos text-muted" style="background-color:#edf2f7;color:#718096;" title="Sin proyectos">0</span>';

                        const badgeEst = item.plt_estado === 'A'
                            ? '<span class="label label-success" style="font-weight:700;">Activa</span>'
                            : '<span class="label label-danger" style="font-weight:700;">Inactiva</span>';

                        const desc = item.plt_descripcion ? escapeHtml(item.plt_descripcion) : '<span class="text-muted">—</span>';
                        const acciones = $('<td class="text-center text-center-valign"><div class="ppto-plt-actions"></div></td>');
                        const $wrap = acciones.find('.ppto-plt-actions');

                        const btnEdit = $('<button type="button" class="btn btn-default btn-xs ppto-plt-btn btn-editar-plt" title="Editar"><i class="bi bi-pencil-square"></i></button>');
                        const btnClone = $('<button type="button" class="btn btn-primary btn-xs ppto-plt-btn btn-clonar-plt" title="Clonar"><i class="bi bi-files"></i></button>');
                        const btnDel = $('<button type="button" class="btn btn-danger btn-xs ppto-plt-btn btn-eliminar-plt" title="Eliminar"><i class="bi bi-trash"></i></button>');

                        btnEdit.data('item', item);
                        btnClone.data('id', item.plt_id).data('nombre', item.plt_nombre);
                        btnDel.data('id', item.plt_id).data('proyectos', item.proyectos_activos);

                        $wrap.append(btnEdit, btnClone, btnDel);

                        tr.append(
                            $('<td class="text-center text-center-valign"><strong>' + item.plt_id + '</strong></td>'),
                            $('<td class="text-center-valign"><strong>' + escapeHtml(item.plt_nombre) + '</strong></td>'),
                            $('<td class="text-center-valign text-muted" style="font-size:11px;">' + desc + '</td>'),
                            $('<td class="text-center text-center-valign">').html(badgePart),
                            $('<td class="text-center text-center-valign">').html(badgeProy),
                            $('<td class="text-center text-center-valign">').html(badgeEst),
                            $('<td class="text-center text-center-valign" style="font-size:11px;">' + escapeHtml(item.plt_fecha_registro) + '</td>'),
                            acciones
                        );

                        tbody.append(tr);
                    });
                } else {
                    tbody.html('<tr><td colspan="8" class="text-center text-muted" style="padding:24px;">No hay plantillas registradas para esta empresa.</td></tr>');
                }
            },
            error: function() {
                tbody.html('<tr><td colspan="8" class="text-center text-danger" style="padding:24px;"><i class="bi bi-exclamation-triangle-fill"></i> Error al cargar el listado.</td></tr>');
                pptoToast('Error al cargar el listado de plantillas.', 'error');
            }
        });
    }

    function modalCrearPlantilla() {
        $('#modal_plantilla_titulo').text('Nueva plantilla');
        $('#form_plt_id').val('');
        $('#form_plt_nombre').val('');
        $('#form_plt_descripcion').val('');
        $('#form_plt_estado').val('A');
        $('#modal_plantilla').show();
    }

    function modalEditarPlantilla(item) {
        $('#modal_plantilla_titulo').text('Editar plantilla');
        $('#form_plt_id').val(item.plt_id);
        $('#form_plt_nombre').val(item.plt_nombre);
        $('#form_plt_descripcion').val(item.plt_descripcion || '');
        $('#form_plt_estado').val(item.plt_estado);
        $('#modal_plantilla').show();
    }

    function guardarPlantilla() {
        const id = $('#form_plt_id').val();
        const action = id === '' ? 'crear' : 'editar';
        const data = {
            plt_id: id,
            plt_nombre: $('#form_plt_nombre').val(),
            plt_descripcion: $('#form_plt_descripcion').val(),
            plt_estado: $('#form_plt_estado').val()
        };

        $.ajax({
            url: LOGICA_URL + '?action=' + action,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    cerrarModalPlantilla();
                    listarPlantillas();
                    pptoToast(res.message, 'success');
                } else {
                    pptoToast(res.message, 'error');
                }
            },
            error: function() {
                pptoToast('Error de red al guardar la plantilla.', 'error');
            }
        });
    }

    function eliminarPlantilla(id, proyectos_activos) {
        if (proyectos_activos > 0) {
            pptoToast('La plantilla tiene proyectos activos asociados y no puede eliminarse.', 'error');
            return;
        }

        if (!confirm('Esta seguro de eliminar permanentemente esta plantilla?')) {
            return;
        }

        $.ajax({
            url: LOGICA_URL + '?action=eliminar',
            type: 'POST',
            data: { plt_id: id },
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    listarPlantillas();
                    pptoToast(res.message, 'success');
                } else {
                    pptoToast(res.message, 'error');
                }
            },
            error: function() {
                pptoToast('Error de red al eliminar la plantilla.', 'error');
            }
        });
    }

    function modalDuplicarPlantilla(id, nombre) {
        $('#dup_plt_id').val(id);
        $('#dup_plt_nombre_nuevo').val(nombre + ' - Copia');
        $('#modal_duplicar').show();
    }

    function guardarDuplicacion() {
        const data = {
            plt_id: $('#dup_plt_id').val(),
            plt_nombre_nuevo: $('#dup_plt_nombre_nuevo').val()
        };

        $.ajax({
            url: LOGICA_URL + '?action=duplicar',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    cerrarModalDuplicar();
                    listarPlantillas();
                    pptoToast(res.message, 'success');
                } else {
                    pptoToast(res.message, 'error');
                }
            },
            error: function() {
                pptoToast('Error de red al clonar la plantilla.', 'error');
            }
        });
    }

    function cerrarModalPlantilla() { $('#modal_plantilla').hide(); }
    function cerrarModalDuplicar() { $('#modal_duplicar').hide(); }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    $(document).ready(function() {
        listarPlantillas();

        $('#tbody_plantillas').on('click', '.btn-editar-plt', function() {
            modalEditarPlantilla($(this).data('item'));
        });

        $('#tbody_plantillas').on('click', '.btn-clonar-plt', function() {
            modalDuplicarPlantilla($(this).data('id'), $(this).data('nombre'));
        });

        $('#tbody_plantillas').on('click', '.btn-eliminar-plt', function() {
            eliminarPlantilla($(this).data('id'), $(this).data('proyectos'));
        });
    });
</script>

</body>
</html>
<?php
$obBD_conexion->cerrar();
?>
