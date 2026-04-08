// JavaScript Document
/**
 * Validaciones y configuración del grid para consulta de empresas activas por periodo
 * 
 * @author Sistema
 * @version 2.0
 * Fecha de actualización:	2025-01-XX
 */

// Declaracion de variables globales para asignacion de grids
var empresasGrid = $('#empresasGrid');

$(function(){
    // Grid de empresas activas
    empresasGrid.createGrid({
        caption: 'Empresas Activas por Periodo',
        rowNum: 50,
        rowList: [250, 500, 750, 1000],
        height: 400,
        footerrow: true,
        url: window.location.pathname,
        mtype: 'GET',
        datatype: 'json',
        viewrecords: true,
        colModel: [
            { label: 'Cod. Int.', name: 'Emp_Cod', key: true, width: 30, align: "center" },
            { label: 'Nombre Empresa', name: 'Emp_Nom', width: 250, align: "left" },
            { label: 'RUC', name: 'Emp_Ruc', width: 120, align: "left" },
            { label: 'Nombre Corto', name: 'Emp_Cor', width: 150, align: "left" },
            { label: 'Estado', name: 'Emp_Est', width: 30, align: "center", hidden: true },
            { label: 'Compras', name: 'total_compras', width: 80, align: "right", 
                formatter: 'number',
                formatoptions: { thousandsSeparator: ',', decimalPlaces: 0 }
            },
            { label: 'Ventas', name: 'total_ventas', width: 80, align: "right",
                formatter: 'number',
                formatoptions: { thousandsSeparator: ',', decimalPlaces: 0 }
            }
        ],
        loadComplete: function(data) {
            var $grid = $(this);
            var ids = $grid.jqGrid('getDataIDs');
            ids.forEach(function(id) {
                var rowData = $grid.jqGrid('getRowData', id);
                if ($.trim(rowData.Emp_Est).toUpperCase() === 'I') {
                    $grid.jqGrid('setRowData', id, false, {
                        background: '#FADDDD'
                    });
                }
            });
        }
    }, false, 'empresasGridPager', { view: false, refresh: true }).gridButtonsAdd([
        { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                empresasGrid.jqGrid('exportGridExcel', {
                    nombre: 'Empresas_Activas',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true
                });
            }
        }
    ]);
    
    // Función para desbloquear campos cuando se selecciona un periodo
    window.desbloquear = function() {
        // Esta función se puede usar para habilitar/deshabilitar campos según el periodo seleccionado
    };
    
    // Función para establecer foco en el campo de búsqueda
    window.setfocus = function(form) {
        if(form && form.search) {
            form.search.focus();
        }
    };
});
