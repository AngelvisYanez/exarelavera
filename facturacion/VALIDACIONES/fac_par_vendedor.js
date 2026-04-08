/**
 * Validaciones de modulo vendedores
 * 
 * @author Wilson Belduma
 *
 * @package facturacion.Validaciones
*/

function validarFormulario_vendedor() {
    var cedulaRUC = document.getElementById("Prs_Ced").value;
    var nombre = document.getElementById("empleado").value;
    var puntoImpresion = document.getElementById("Pun_Descripcion").value;
    if (cedulaRUC == "" || nombre == "" || puntoImpresion == "") {
        $.alert('Por favor, complete todos los campos obligatorios.');
        return false;
    }
    return true;
}

function campos_hide() {
    document.getElementById("Prs_Ced").value = "";
    document.getElementById("empleado").value = "";
    document.getElementById("Pun_Descripcion").value = "Caja-Vendedores";
    document.getElementById("Pun_Ubi").value = "";
    var messageDiv = document.querySelector(".message");
    if (messageDiv) {
        messageDiv.innerHTML = "";
    }
}
function closeMessage() {
    var messageDiv = document.querySelector(".message");
    if (messageDiv) {
        messageDiv.innerHTML = "";
    }
}

function cargarEmpleado(empleado) {
    $('#form2').setData(empleado, false);
}

//Funcion para cargar Ajax
$(function () {
    $.createDateRange('#ini', '#fin');
    $('#ini').val('2020-01-01');
    $('#fin').datepicker("setDate", new Date());
    $("#list").jqGrid({
        url: '#',
        mtype: "GET",
        datatype: "json",
        regional: 'es',
        responsive: true,
        postData: $("#formBuscar").getData("vendedorAjax"),
        autowidth: true,
        shrinkToFit: true,
        height: 200,
        cmTemplate: {
            sortable: false
        },
        colModel: [{
            label: 'Código',
            name: 'Prs_Cod',
            width: 30,
            align: "center"
        },

        {
            label: 'Fecha',
            name: 'Vet_Sys',
            width: 35,
            align: "center"
        },
        {
            label: 'Cédula',
            name: 'Prs_Ced',
            width: 30,
            align: "center"
        },
        {
            label: 'Empleado',
            name: 'empleado',
            width: 80,
            align: "center"
        },
        {
            label: 'Cod. Venta',
            name: 'Vet_Cod',
            width: 20,
            align: "center"
        },
        {
            label: 'Estado',
            name: 'Vnd_Est',
            width: 40,
            align: "center"
        },
        {
            label: 'Pun Cod',
            name: 'Pun_Cod',
            width: 40,
            align: "center",
            hidden: true
        },
        {
            label: 'Vnd_Cod',
            name: 'Vnd_Cod',
            width: 40,
            align: "center",
            hidden: true
        },
        {
            label: 'Cant. Vntas',
            name: 'cantidad_ventas',
            width: 20,
            align: "center",
            hidden: false
        },
        {
            label: 'Total',
            name: 'total_ventas',
            width: 30,
            align: "center",
            hidden: false
        },


       


        {
            label: '% Venta',
            name: 'total_ventas',
            width: 30,
            align: "center",
            hidden: false
        },





            /*,
                                {
                                    label: '&nbsp;',
                                    name: 'act1',
                                    width: 30,
                                    align: 'center',
                                    viewable: false,
                                    formatter: function(cellvalue, options, rowObject) {
                                        return $.getGridButton(cargarEmpleado, rowObject);
                                    }
                                }*/
        ],
        rowNum: 10000,
        pager: "#listPager",
        gridview: false,
        rownumbers: false,
        viewrecords: true,
        pgbuttons: false,
        pgtext: null,
        altRows: true,
        altclass: "myAltRowClass"
    });
});


