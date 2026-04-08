// Declaracion de variables globales
var plantasGrid = $('#plantasGrid');

$(function(){
    // Inicializar grid de plantas con columnas agrupadas
    plantasGrid.createGrid({
        caption: 'Plantas',
        rowNum: 50,
        height: 332,
        footerrow: true,
        autowidth: false,
        shrinkToFit: false,
        width: null,
        scroll: 1,
        scrollrows: true,
        viewrecords: true,
        datatype: 'local',
        colModel: [
            { label: 'Cod.', name: 'Pla_Cod', width: 50, align: "center", key: true, frozen: true },
            // Grupo: Planta (15 columnas)
            { label: 'Cédula/RUC', name: 'Cli_Ced', width: 100, align: "left" },
            { label: 'Cliente', name: 'Cliente', width: 150, align: "left" },
            { label: 'Nombre Planta', name: 'Pla_Nom', width: 150, align: "left" },
            { label: 'Cod.Arcon', name: 'Pla_Car', width: 100, align: "left" },
            { label: 'Ciudad', name: 'Ciu_Des', width: 120, align: "left" },
            { label: 'Nro. Licencia', name: 'Pla_Lic', width: 120, align: "left" },
            { label: 'Dirección', name: 'Pla_Dir', width: 150, align: "left" },
            { label: 'Ubicacion Geo.', name: 'Pla_Geo', width: 150, align: "left" },
            { label: 'Tiempo Relavera', name: 'Pla_Dis', width: 100, align: "center" },
            { label: 'Capacidad', name: 'Pla_Cap', width: 100, align: "left" },
            { label: 'Nro.Gen.Desechos', name: 'Pla_Crd', width: 120, align: "left" },
            { label: 'Nro.Aut.Operacion', name: 'Pla_Cau', width: 120, align: "left" },
            { label: 'Ruta Relavera', name: 'Pla_Rut', width: 150, align: "left" },
            { label: 'Fec.Emision', name: 'Pla_Fem', width: 100, align: "center" },
            { label: 'Fec.Vencimiento', name: 'Pla_Fve', width: 100, align: "center" },
            // Grupo: Admin Planta (11 columnas)
            { label: 'Identificacion', name: 'Admin_Ced', width: 100, align: "left" },
            { label: 'Nombres', name: 'Admin_Nom', width: 120, align: "left" },
            { label: 'Apellidos', name: 'Admin_Ape', width: 120, align: "left" },
            { label: 'Estado Civil', name: 'Admin_Esc', width: 100, align: "center" },
            { label: 'Genero', name: 'Admin_Sex', width: 80, align: "center" },
            { label: 'Fecha Nac.', name: 'Admin_Fec', width: 100, align: "center" },
            { label: 'Lugar Nac.', name: 'Admin_Ciu_Nac', width: 120, align: "left" },
            { label: 'Teléfono 1', name: 'Admin_Tel', width: 100, align: "left" },
            { label: 'Teléfono 2', name: 'Admin_Tel2', width: 100, align: "left" },
            { label: 'Lugar Trabajo', name: 'Admin_Ciu_Tra', width: 120, align: "left" },
            { label: 'Email', name: 'Admin_Cor', width: 150, align: "left" },
            // Grupo: Tributario (11 columnas)
            { label: 'Identificacion', name: 'Cont_Ced', width: 100, align: "left" },
            { label: 'Nombres', name: 'Cont_Nom', width: 120, align: "left" },
            { label: 'Apellidos', name: 'Cont_Ape', width: 120, align: "left" },
            { label: 'Estado Civil', name: 'Cont_Esc', width: 100, align: "center" },
            { label: 'Genero', name: 'Cont_Sex', width: 80, align: "center" },
            { label: 'Fecha Nac.', name: 'Cont_Fec', width: 100, align: "center" },
            { label: 'Lugar Nac.', name: 'Cont_Ciu_Nac', width: 120, align: "left" },
            { label: 'Teléfono 1', name: 'Cont_Tel', width: 100, align: "left" },
            { label: 'Teléfono 2', name: 'Cont_Tel2', width: 100, align: "left" },
            { label: 'Lugar Trabajo', name: 'Cont_Ciu_Tra', width: 120, align: "left" },
            { label: 'Email', name: 'Cont_Cor', width: 150, align: "left" },
            // Grupo: Ambiental (11 columnas)
            { label: 'Identificacion', name: 'Amb_Ced', width: 100, align: "left" },
            { label: 'Nombres', name: 'Amb_Nom', width: 120, align: "left" },
            { label: 'Apellidos', name: 'Amb_Ape', width: 120, align: "left" },
            { label: 'Estado Civil', name: 'Amb_Esc', width: 100, align: "center" },
            { label: 'Genero', name: 'Amb_Sex', width: 80, align: "center" },
            { label: 'Fecha Nac.', name: 'Amb_Fec', width: 100, align: "center" },
            { label: 'Lugar Nac.', name: 'Amb_Ciu_Nac', width: 120, align: "left" },
            { label: 'Teléfono 1', name: 'Amb_Tel', width: 100, align: "left" },
            { label: 'Teléfono 2', name: 'Amb_Tel2', width: 100, align: "left" },
            { label: 'Lugar Trabajo', name: 'Amb_Ciu_Tra', width: 120, align: "left" },
            { label: 'Email', name: 'Amb_Cor', width: 150, align: "left" }
        ]
    }, false, 'plantasGridPager', { view: false, refresh: false });
    
    // Configurar encabezados agrupados antes de agregar botones
    plantasGrid.jqGrid('setGroupHeaders', {
        useColSpanStyle: true,
        groupHeaders: [
            { startColumnName: 'Cli_Ced', numberOfColumns: 15, titleText: 'Datos Planta' },
            { startColumnName: 'Admin_Ced', numberOfColumns: 11, titleText: 'Admin. Planta' },
            { startColumnName: 'Cont_Ced', numberOfColumns: 11, titleText: 'Tributario' },
            { startColumnName: 'Amb_Ced', numberOfColumns: 11, titleText: 'Ambiental' }
        ]
    });
    
    // Agregar evento al botón de exportar
    $('#exportExcelBtn').on('click', function(e) {
        e.preventDefault();
        plantasGrid.jqGrid('exportGridExcel', {
            nombre: 'Plantas',
            hoja: 'HOJA 1',
            footer: true
        });
    });
    
    // No cargar grid al inicio
    
    // Eventos para el input de búsqueda
    $('input[name="search"]').on('clearable', function() {
        buscarPlantas();
    }).on('keypress', function(e) {
        if(e.which == 13) {
            buscarPlantas();
            return false;
        }
    });
});

function buscarPlantas() {
    var search = $('input[name="search"]').val();
    var op = $('input[name="op_opciones"]:checked').val();
    
    plantasGrid.jqGrid('setGridParam', {
        url: 'man_con_planta.php',
        datatype: 'json',
        postData: {
            LoadPlantasGridAjax: true,
            search: search,
            op_opciones: op
        },
        page: 1
    }).trigger('reloadGrid');
}

