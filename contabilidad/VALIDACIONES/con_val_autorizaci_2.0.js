$(function () {

    /*************************************
    * ONLOAD PAGINA
    **************************************/
    function inicio() {
        $("#tabsSearch").createTabs();
        $("#editDialog").createDialog({ width: 500, height: 450, icon: 'pencil' });
        $("#Aut_Fci").createDatePickers();
        $("#Aut_Cad").createDatePickers();
        /*
        * 0 -> Nuevo
        * 1 -> Modificar
        */
        var flag_accion = 0;
    }

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;

    /*
     * On Change SELECT Sucursal get Punto
     */
    $('#Suc_Cod').on("change", function (e) {
        e.preventDefault();
        data = "";
        var cod = $('#Suc_Cod').val();
        if (cod !== "0") {
            $.getDataJson("", { getPunto: true, Suc_Cod: cod }, function (res) {
                fetchPunto("Pun_Cod", res);
            });
        }
        else { $('#Pun_Cod').html(data); }
    });

    /*
     * On Change SELECT Punto get Tipo de Documento
     */
    $('#Pun_Cod').on("change", function (e) {
        e.preventDefault();
        var cod = $('#Pun_Cod').val();
        var succod = $('#Suc_Cod').val();
        var data = "";

        if (cod !== "0") {
            $.getDataJson("", { getTipDoc: true, Pun_Cod: cod, Suc_Cod: succod }, function (res) {
                $.each(res.tipDoc, function (i, item) {
                    if (i === 0) {
                        data = "<option value = 0> Seleccionar ... </option>";
                    }
                    data += "<option value=" + item.Tic_Cod + ">" + item.Tic_Des + "</option>";
                });
                $('#Tic_Cod').html(data);
            }, function (nr) { });
        }
        else { $('#Tic_Cod').html(data); }
    });
    $('#Tic_Cod_n').on("change", function (e) { setNumAutElectronica(); });
    $('#Aut_Tem').on("change", function (e) { setNumAutElectronica(); });
    /*
     * Fetch select for Punto
     * @param {type} control
     * @param {type} res
     * @returns {undefined}
     */
    function fetchPunto(control, res) {
        $.each(res.punto, function (i, item) {
            if (i === 0) {
                data = "<option value ='0' selected> Seleccionar ... </option>";
            }
            data += "<option value=" + item.Pun_Cod + " data--pun_-cod = " + item.Pun_Cod + ">" + item.Pun_Des + "</option>";
        });
        $('#' + control).html(data);
    }

    /*
     * CHANGE FECHA INICIO
     */
    $('#Aut_Fci').on("change", function () {
        var d = $("#Aut_Fci").datepicker("getDate");

        var e = document.getElementById("Aut_Tem");
        var seleccion = e.options[e.selectedIndex].value;

        if (seleccion != 'E') {
            $("#Aut_Cad").datepicker("setDate", new Date(d.getFullYear() + 1, d.getMonth(), d.getDate()));
        }
        $('#Aut_Ini').focus();
    });

    /*
    * On Click BUTTON search data
    */
    $('#btnSearch').on("click", function (e) {
        e.preventDefault();
        var pun_cod = $('#Pun_Cod').val();
        var tic_cod = $('#Tic_Cod').val();
        var suc_cod = $('#Suc_Cod').val();

        if (suc_cod !== "0" && pun_cod !== "0" && tic_cod !== "0") {
            $.getDataJson('', { searchFiltro: true, Pun_Cod: pun_cod, Tic_Cod: tic_cod }, function (res) {
                $("#tableResult").setRows(res['rows']);
            }, function (f) {
            });
        }
        else {
            $.alert("Seleccionar Sucursal, Punto y Tipo de Documento");
        }
    });

    /*
     * On Click Boton Accion autorizacion
     * Crea o Modifica una Autorizacion
     */
    $('#btnAccion').on("click", function (e) {
        e.preventDefault();

        if ($('#Aut_Sri').val() !== "" && $('#Aut_Ini').val() !== "" && $('#Aut_Fin').val() !== "" && $('#Pun_Sri').val() !== "" && $('#Aut_Ima').val() !== "") {
            if (flag_accion === 1) // modificar
            {
                $.saveDataJson('', $('#formDialog').getData('modifyAut'), function (re) {
                    $('#editDialog').dialog('close');
                    var data = $('#formDialog').getData();
                    data['act3'] = '';
                    $("#tableResult").changeRow(data.Aut_Cod, data);//actualiza el row                
                });
            }
            else // nueva
            {
                $.saveDataJson('', $.extend($('#formDialog').getData('saveAut'), $('#Pun_Cod_n').val()), function (re) {
                    tipoDoc = $('#Tic_Cod_n').val(); // select the code of tipo doc. from modal                    
                    $('#editDialog').dialog('close');

                    $.getDataJson("", { getTipDoc: true, Pun_Cod: $('#Pun_Cod').val() }, function (res) {
                        $.each(res.tipDoc, function (i, item) {
                            if (i === 0) {
                                data = "<option value = 0> Seleccionar ... </option>";
                            }
                            data += "<option value=" + item.Tic_Cod + ">" + item.Tic_Des + "</option>";
                        });
                        $('#Tic_Cod').html(data);
                        $('#Tic_Cod').val(tipoDoc);// asigna a tipo de documento
                        $('#btnSearch').trigger('click');
                    }, function (nr) { });


                });
            }
        }
        else {
            $.alert("Los campos con * son requeridos");
        }
    });

    /*
     * On Click nueva autorizacion
     */
    $('#btnNueva').on("click", function (e) {
        flag_accion = 0;
        var cod = $('#Suc_Cod').val();
        var pun = $('#Pun_Cod').val();
        if (cod !== "0" && pun !== "0") // selecciona sucursal and punto
        {
            $('#Pun_Cod_n').html("<option value=" + pun + ">" + $('#Pun_Cod option:selected').text() + "</option>");
            $('#editDialog').dialog("option", "title", "Nueva Autorizaci�n");
            $('#Pun_Cod_d').show(); // muestra select punto
            $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Guardar");
            $('#editDialog').dialog('open');
            $("#formDialog")[0].reset(); // limpia modal
            setNumAutElectronica();
            var d = $("#Aut_Fci").datepicker("getDate");
            $("#Aut_Cad").datepicker("setDate", new Date(d.getFullYear() + 1, d.getMonth(), d.getDate()));
        }
        else {
            $.alert("Seleccionar Sucursal y Punto de Venta");
        }
    });

    //Inicio Grid para presentar la busqueda   
    $("#tableResult").createGrid({
        height: 295,
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Aut_Cod', width: 30, align: "center", key: true },
            { label: 'Tipo', name: 'AutTem', width: 50, align: "center" },
            { label: 'Autorizaci&oacute;n SRI', name: 'Aut_Sri', width: 50, align: "center" },
            { label: 'Punto SRI', name: 'Pun_Sri', width: 50, align: "center" },
            { label: 'Fecha Inicio', name: 'Aut_Fci', width: 50, align: "center" },
            { label: 'Fecha Caducidad', name: 'Aut_Cad', width: 50, align: "center" },
            { label: 'Inicio Blok', name: 'Aut_Ini', width: 50, align: "center" },
            { label: 'Fin Blok', name: 'Aut_Fin', width: 50, align: "center" },
            { label: 'Estado', name: 'Aut_Est', width: 50, align: "center" },
            {
                label: 'Modificar', name: 'act3', width: 30, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return $.getGridButton(modificarAut, rowObject, 'Modificar', 'pencil');
                }
            },
            {
                label: '&nbsp;', name: 'act2', width: 30, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Aut_Est === 'Activo')
                        return $.getGridButton(setEstado, rowObject, 'Desactivar', 'fa fa-unlock');
                    else
                        return $.getGridButton(setEstado, rowObject, 'Activar', 'fa fa-lock', '', 'danger');
                }
            }
        ]
    }, true, "#tableResultPager", {}); //false -> paginacion | true -> una sola page    
});

/*
 * Estado 
 */
function setEstado(row) {
    if (row.Aut_Est === "Activo") {
        row.Aut_Est = 'I';
    } else {
        row.Aut_Est = 'A';
    }
    $.createDialogConfirm("Esta seguro de realizar esta <b>acci&oacute;n</b> ?", row, function () {
        $.getDataJson('', { setEstado: true, Aut_Cod: row.Aut_Cod, Aut_Est: row.Aut_Est, Tic_Cod: row.Tic_Cod, Pun_Cod: row.Pun_Cod }, function (response) {
            $('#btnSearch').trigger('click');
        })
    }, function (f) {
    });
}

function modificarAut(row) {
    flag_accion = 1;
    $('#formDialog').setData(row);
    $('#Aut_Sri').prop('readonly', row['Aut_Tem'] === 'E');
    $('#Tic_Cod_n').val(row.Tic_Cod);
    $('#Aut_Tpt').prop('checked', row.Aut_Tpt === "S");//Marcar si es socio de una empresa de transporte
    ver_socios();
    $('#editDialog').dialog("option", "title", "Editar");
    $('#Pun_Cod_d').hide();
    $('#btnAccion').html("<i class='glyphicon glyphicon-floppy-disk'></i>   Modificar");
    $('#editDialog').dialog('open');
}

function setNumAutElectronica() {
    if ($('#Aut_Tem').val() === 'E') {
        var Tic_Sri = '' + $('#Tic_Cod_n option:selected').data('Tic_Sri');
        var Suc_Cod = '' + $('#Suc_Cod').val();
        $('#Aut_Sri').val(Suc_Cod + (0).padLeft(10 - Suc_Cod.length - Tic_Sri.length) + Tic_Sri).prop('readonly', true)
    } else {
        $('#Aut_Sri').val('').prop('readonly', false)
    }
}
function validaRango() {
    var ini = $('#Aut_Ini').val();
    var fin = $('#Aut_Fin').val();
    var validador = fin - ini;
    if (validador > 999) {
        $.alert("La diferencia entre secuencia final e inicial no puede ser superior a 1000");
        $('#Aut_Fin').val('');
    }
}


//Socios transportista
function ver_socios() {
    const check = document.getElementById("Aut_Tpt");
    const labelSocio = document.getElementById("label_socio");
    labelSocio.hidden = !check.checked; // una sola línea
}