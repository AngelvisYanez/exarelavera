/*
 * author: Asael Tello 23-08-2017
 */
$(function () {
  /*===========================
         * CREA TABLA DE BUSQUEDA
         ============================*/
  $("#tableResult").createGrid(
    {
      height: 295,
      colModel: [
        { label: "C&oacute;d. Int.", name: "Usu_Cod", width: 15, align: "center", key: true, },
        { label: "C&eacute;dula", name: "Prs_Ced", width: 50, align: "center" },
        { label: "Usuario", name: "persona", width: 100, align: "left" },
        { label: "Planta", name: "Pla_Nom", width: 75, align: "left" },
        { label: "Sucursales", name: "Sucursales", width: 75, formatter: "tags",
          formatoptions: { type: "purple" }
        },
        { label: "Perfiles", name: "Perfiles", width: 75, formatter: "tags" },
        { label: "Pto. Impresi&oacute;n", name: "Pun_Des_m", width: 50, align: "center", },
        { label: "Modificar", name: "act3", width: 30, align: "center", viewable: false, formatter: "gridButton",
          formatoptions: { action: "modificarUser", icon: "pencil", title: "Modificar", }
        },
        { label: "LogOut", name: "act4", width: 30, align: "center", viewable: false, formatter: "gridButton",
          formatoptions: { action: "closeSessions", data: "Usu_Cod", icon: "log-out", type: "purple", title: "Cerrar Sesiones", }
        },
        /* formatter:function(cellvalue, options, rowObject){
                        return $.getGridButton(modificarUser, rowObject, 'Modificar', 'pencil');
                }
            },*/
      ],
    },
    true,
    "#tableResultPager",
    {}
  ); //false -> paginacion | true -> una sola page
  //window.onload = inicio;

  /* grid de busqueda de clientes */
  // Grid del modal oculto de Cliente  - nuevo 03-12-25
  if ($('#clientesDialog').length === 1)
    $.createSearchDialog('clientesDialog', [
            { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Cliente', name: 'nombre', width: 100 },
            { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente } }
        ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

        toggleBotonesCliente();

    // inicializa el dialog de registrar pagos de anticipo
    if ($('#pagosDialog').length === 1)
        $('#pagosDialog').createDialog({ height: 350, width: 600, icon: 'usd' });

    if ($('#CliDialog').length === 1){
      $.createSearchDialog('CliDialog', [
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Cliente', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCli } }
      ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

      // Modificar el action del formulario para usar clientesAjax
      $("#CliForm").attr('action', "javascript:searchCliDialog()");

      // Crear función personalizada de búsqueda que usa clientesAjax
      window.searchCliDialog = function() {
        var g = $("#CliGrid"), f = $("#CliForm");
        var formData = f.getData(); // Obtener todos los datos del formulario
        delete formData.CliAjax; // Eliminar el parámetro incorrecto si existe
        formData.clientesAjax = true; // Agregar el parámetro correcto
        var e = g.getGridParam('postExtra') || {};
        g.setGridParam({postData: null});
        g.jqGrid('setGridParam', {
          datatype: 'json',
          postData: $.extend(true, e, formData)
        }).gridUpdate(1);
        f.effect("highlight", {}, 500);
      };
    }

  /*===========
  * Beginning *
  ===========*/
  //function inicio()
  //{
  $("#tabsUser").createTabs();
  $("#editDialog").createDialog({ width: 550, height: 480, icon: "pencil" });

  /* Set Ciudades y Provincias */
  $("#Ciu_Cod").createChosen("input-xs", {
    tabIndex: 6,
    width: "100%",
    template: function (t, d) {
      return (
        '<div class="over"><b>' +
        t +
        '</b></div><div class="over desc" style="font-size:11px;"><b>Provincia:</b> ' +
        d["prov"] +
        " <b>Pa&iacute;s:</b> " +
        d["pais"] +
        "</div>"
      );
    },
  });

  $("#Suc_Cod").createChosen();
  $("#Per_Cod").createChosen();
  $("#Suc_Cod_m").createChosen();
  $("#Per_Cod_m").createChosen();
  fetchAllData();

  /*=============================================
            * Fetch All Perfiles de la Empresa en Modificar
            ===============================================*/
  $.getDataJson( "", { getPerfil: true },
    function (res) {
      var data = "";
      $.each(res.rows, function (i, item) {
        data +=
          "<tr id=" +
          item.Per_Cod +
          ">" +
          "<td> <input id =check" +
          item.Per_Cod +
          " type='checkbox' data-percod =" +
          item.Per_Cod +
          " /></td>" +
          "<td>" +
          item.Per_Des +
          "</td>" +
          "</tr>";
      });
      $("#tablePerfil").html(data);
    },
    function (err) { }
  );
  //}

  /*
    * Change en CheckBox Vendedor
    */
  $("#chkVen").on("change", function () {
    if ($(this).is(":checked")) {
      $("#Pun_Des").prop("disabled", false);
      $("#Pun_Des").focus();
    } else {
      $("#Pun_Des").prop("disabled", true);
    }
  });

  /* Change en CheckBox Manifiesto (dentro del fieldsetManifiesto) */
  $("#fieldsetManifiesto #chkVen").on("change", function () {
    if ($(this).is(":checked")) {
      // Mostrar el botón de búsqueda de cliente
      $("#btnBusCLi").show();
    } else {
      // Ocultar el botón de búsqueda de cliente
      $("#btnBusCLi").hide();
      // Limpiar los campos del manifiesto cuando se desmarca el checkbox
      $("#fieldsetManifiesto #CedCliMan input[name='Prs_Ced_Aux']").val('');
      $("#fieldsetManifiesto input#Prs_Ced").val('');
      $("#fieldsetManifiesto #nombre").val('');
      // También limpiar los campos ocultos
      $("#fieldsetManifiesto #Cli_Cod").val('');
      $("#fieldsetManifiesto #Prs_Cod").val('');
      $("#fieldsetManifiesto #bandera_prov").val('nosel');
    }
  });

  /* LLENA TABLA DE DATOS */
  function fetchAllData() {
    /* Fetch the Table with all data  */
    $.getDataJson( "", { searchAll: true },
      function (res) {
        $("#tableResult").setRows(res.rows);
      },
      function (err) { }
    );
  }

  /*====================
    * GUARDAR USUARIO *
  =================== */
  $("#btnGuardar").on("click", function () {
    if (flag_guardar !== 1) {
      if (
        $("#Prs_Ced").val() !== "" &&
        $("#Prs_Nom").val() !== "" &&
        $("#Prs_Ape").val() !== "" &&
        $("#Usu_Pal").val() !== "" &&
        $("#Usu_Pal_C").val() !== "" &&
        $("#Ciu_Cod").val() !== "" &&
        $("#Per_Cod").chosen().val() !== null
      ) {
        var data = $("#frmUser").getData();

        $.createDialogConfirm("¿Est&aacute; seguro de guardar el usuario?", data,
          function () {$.getDataJson("", { guardar: true, data: data, flag: flag_guardar },
              function (res) {
                if (res.success === true) {
                  $.alert("Usario creado con &eacute;xito!!");
                  fetchAllData();
                }
                clearForm();
              },
              function () {
                clearForm();
              }
            );
          }
        );
      } else {
        $.alert("Los campos marcados con * son requeridos");
      }
    } else {
      $.alert("Asegurese que el usuario no tenga asiganada una sucursal");
    }
  });

  /* Clear Form data */
  function clearForm() {
    $("#frmUser")[0].reset();
    $("#Per_Cod option:selected").removeAttr("selected"); // remove from root
    $("#Per_Cod").val("").trigger("chosen:updated"); // update chosen
    $("#Ciu_Cod option:selected").removeAttr("selected");
    $("#Ciu_Cod").val("").trigger("chosen:updated"); // update chosen
    $("#chkVen").prop("checked", false);
    $("#Usu_Ced").prop("disabled", false);
    $("#Pun_Des").prop("disabled", true);
    $("#Prs_Ced").fieldValid("");
    $("#Usu_Pal").fieldValid("");
    $("#Usu_Pal_C").fieldValid("");
    $("#Pun_Des").fieldValid("");
    $("#Suc_Cod").val("").trigger("chosen:updated"); // update chosen
  }

  /* BUSQUEDA POR FILTROS */
  $("#btnSearch").on("click", function () {
    const v = $('#search').val();
    if (v !== null && v.trim() !== '') {
      if ($("#rad_ba1").is(":checked")) {
        //cedula
        fetchData("Prs_Ced = ", $("#search").val());
      } else {// apellido
        var d = "%" + $("#search").val() + "%";
        fetchData("Prs_Ape like ", d);
      }
    } else {
      $.getDataJson("", { searchAll: true },
        function (res) {
          $("#tableResult").setRows(res.rows);
        },
        function (err) { }
      );
    }
  });

  /*
   * Fetch the Data
   */
  function fetchData(fil, dat) {
    $.getDataJson( "", { searchFiltro: true, filtro: fil, dato: dat },
      function (res) {
        $("#tableResult").setRows(res.rows);
      },
      function (err) { }
    );
  }

  /*============================
         * EVENTO AL CERRAR EL DIALOG
         *  - limpia todos los checks
         *  - bandera para los checks
         =============================*/
  $("#editDialog").on("dialogclose", function (event, ui) {
    json_asig = [];
    json_save = [];
    $("#Usu_Pal_m").fieldValid("");
    $("#Usu_Pal_Cm").fieldValid("");
    var table = $("#tablePerfil tbody");
    table.find("tr").each(function (y, item) {
      $("#check" + item.id).prop("checked", false);
    });
  });

  /*=================================
         * MODIFICAR USUARIO
         ===================================*/
  $("#btnModificar").on("click", function () {
    if (flag_pass === 0) {
      var data = $("#formDialog").getData();

      $.createDialogConfirm( "Est&aacute; seguro de modificar el Usuario ?", data, function () {
          json_save = $("#Per_Cod_m").chosen().val();
          var flag_mod_pun = "0";
          var punto = $("#Pun_Des_m").val();

          if (pun_des === punto) {
            // verifica si cambio Pun_Des
            flag_mod_pun = "1";
          }

          $.getDataJson( "", { modificar: true, sav: json_save, del: json_asig, data: data, flag_pun: flag_mod_pun, },
            function (res) {
              data["act3"] = "";
              $("#tableResult").changeRow(data.Usu_Cod, data); //actualiza el row

              // Limpiar campos del cliente después de guardar exitosamente
              $("#fieldsetModCli #Cli_Cod_Aux").val('');
              $("#fieldsetModCli #Prs_Cod_Aux").val('');
              $("#fieldsetModCli #Usu_Cod_Aux").val('');
              $("#fieldsetModCli #bandera_prov1").val("nosel");
              $("#fieldsetModCli #Prs_Ced_Aux").val('');
              $("#fieldsetModCli #CliNom").val('');
              $("#fieldsetModCli #Pla_Cod_m").val('').empty().append($('<option>', { value: '', text: 'Seleccione...' }));

              $("#editDialog").dialog("close");
              $.alert(res.message);
            },
            function () { }
          );
        }
      );
    } else {
      $.alert("Las contrase&ntilde;as no coinciden");
    }
  });

  $("#Usu_Pal_C").on("blur", function () {
    validatePass("Usu_Pal", "Usu_Pal_C");
  });

  $("#Usu_Pal_Cm").on("blur", function () {
    validatePass("Usu_Pal_m", "Usu_Pal_Cm");
  });
});

// nuevo 03-12-25
function toggleBotonesCliente() {
  // Verificar que la variable global esté definida
  if (typeof tieneClienteManifiesto === 'undefined') {
      tieneClienteManifiesto = false;
  }

  // Verificar si el checkbox chkVen del manifiesto está marcado
  var chkVenMarcado = $("#fieldsetManifiesto #chkVen").is(":checked");

  if (!tieneClienteManifiesto) {
      // Si NO hay cliente manifiesto (la consulta no trae datos), mostrar los botones de búsqueda
      $('#radsf1, #radsf2').show();
      $('label[for="radsf1"], label[for="radsf2"]').show();
      // Mostrar el botón de búsqueda de cliente solo si chkVen está marcado
      if (chkVenMarcado) {
          $('#btnBusCLi').removeAttr('style').show();
      } else {
          $('#btnBusCLi').css('display', 'none');
      }
  } else {
      // Si SÍ hay cliente manifiesto (la consulta trae datos), ocultar los botones de búsqueda
      $('#radsf1, #radsf2').hide();
      $('label[for="radsf1"], label[for="radsf2"]').hide();
      // Mostrar el botón de búsqueda de cliente solo si chkVen está marcado
      if (chkVenMarcado) {
          $('#btnBusCLi').removeAttr('style').show();
      } else {
          $('#btnBusCLi').css('display', 'none');
      }
      // Si radsf1 estaba seleccionado, cambiar a radsf3 (Manifiesto)
      if ($('#radsf1').is(':checked')) {
          $('#radsf3').prop('checked', true);
      }
  }
}

function selectCliente(cliente) {
  // Si el parámetro viene como array, obtener el primer elemento (objeto de la fila)
  var rowData = Array.isArray(cliente) ? cliente[0] : cliente;

  // Campos ocultos dentro del fieldset del manifiesto
  $("#fieldsetManifiesto #Cli_Cod").val(rowData.Cli_Cod || '');
  $("#fieldsetManifiesto #Prs_Cod").val(rowData.Prs_Cod || '');
  $("#fieldsetManifiesto #bandera_prov").val("sel");

  // Campo Prs_Ced dentro del div CedCliMan (dentro del fieldset del manifiesto)
  $("#fieldsetManifiesto #CedCliMan input[name='Prs_Ced_Aux']").val(rowData.Prs_Ced || '');
  // También intentar con el selector directo por ID dentro del fieldset
  $("#fieldsetManifiesto input#Prs_Ced").val(rowData.Prs_Ced || '');

  // Campo nombre dentro del fieldset del manifiesto
  $("#fieldsetManifiesto #nombre").val(rowData.nombre || '');

  // Forzar actualización visual si es necesario
  $("#fieldsetManifiesto #CedCliMan input[name='Prs_Ced']").trigger('change');

  $.getJSON( "", { AjaxPlanta: true, Cli_Cod: rowData.Cli_Cod },
    function (response) {
      // Asegurar que la respuesta sea un objeto y tenga la propiedad rows
      if (response && $.isArray(response.rows)) {
        var selector = $("#Pla_Cod");
        selector.empty();
        selector.append($('<option>', { value: '', text: 'Seleccione...' }));

        $.each(response.rows, function (i, val) {
          selector.append(
            $('<option>', {
              value: val.Pla_Cod,
              text: (val.Pla_Nom || '') + ' - ' + (val.Pla_Lic || '')
            })
          );
        });
      } else {
        console.warn("Respuesta de AjaxPlanta sin filas válidas:", response);
      }
    }
  ),

  // Cerrar el diálogo de búsqueda
  $('#clientesDialog').dialog('close');
}

function selectCli(cliente) {
  // Si el parámetro viene como array, obtener el primer elemento (objeto de la fila)
  var rowData = Array.isArray(cliente) ? cliente[0] : cliente;

  // Campos ocultos dentro del fieldsetModCli
  $("#fieldsetModCli #Cli_Cod_Aux").val(rowData.Cli_Cod || '');
  $("#fieldsetModCli #Prs_Cod_Aux").val(rowData.Prs_Cod || '');
  $("#fieldsetModCli #bandera_prov1").val("sel");

  // Campo Prs_Ced_Aux (Cédula/RUC) - selector directo por ID
  $("#fieldsetModCli #Prs_Ced_Aux").val(rowData.Prs_Ced || '');

  // Campo nombre del cliente (el alias en SQL es 'nombre')
  $("#fieldsetModCli #CliNom").val(rowData.nombre || '');

  // Forzar actualización visual si es necesario
  $("#fieldsetModCli #Prs_Ced_Aux").trigger('change');

  // Cargar plantas del cliente seleccionado
  $.getJSON( "", { AjaxPlanta: true, Cli_Cod: rowData.Cli_Cod },
    function (response) {
      // Asegurar que la respuesta sea un objeto y tenga la propiedad rows
      if (response && $.isArray(response.rows)) {
        var selector = $("#fieldsetModCli #Pla_Cod_m");
        selector.empty();
        selector.append($('<option>', { value: '', text: 'Seleccione...' }));

        $.each(response.rows, function (i, val) {
          selector.append(
            $('<option>', {
              value: val.Pla_Cod,
              text: (val.Pla_Nom || '') + ' - ' + (val.Pla_Lic || '')
            })
          );
        });
      } else {
        console.warn("Respuesta de AjaxPlanta sin filas válidas:", response);
      }
    }
  );

  // Cerrar el diálogo de búsqueda
  $('#CliDialog').dialog('close');
}

function limpiarCamposCliente() {
  // Limpiar todos los campos del fieldsetModCli
  $("#fieldsetModCli #Cli_Cod_Aux").val('');
  $("#fieldsetModCli #Prs_Cod_Aux").val('');
  $("#fieldsetModCli #bandera_prov1").val("nosel");
  $("#fieldsetModCli #Prs_Ced_Aux").val('');
  $("#fieldsetModCli #CliNom").val('');
  $("#fieldsetModCli #Pla_Cod_m").val('').empty().append($('<option>', { value: '', text: 'Seleccione...' }));
}

var err = 0;
var flag_guardar = 0;
var flag_pass = 0;
var json_asig = [];
var json_save = [];
var pun_des = "";

function validar(op) {
  var cedula = $("#Prs_Ced").val();
  switch (op) {
    case 1:
      if (validaNoIdentif(cedula)["success"]) {
        $("#Prs_Ced").fieldValid(true);
        searchPersona(cedula, "ec");
      } else {
        err = 1;
        $("#Prs_Ced").fieldValid(false, validaNoIdentif(cedula)["message"]);
      }
      break;
    case 2:
      if (cedula.length === 13 && validaNoIdentif(cedula)["success"]) {
        $("#Prs_Ced").fieldValid(true);
        searchCliente(cedula, "ec");
      } else {
        err = 1;
        $("#Ide_Cod").val(1);
        $("#Prs_Ced").fieldValid(false, validaNoIdentif(cedula)["message"]);
      }
      break;
    case 3:
      $("#Prs_Ced").fieldValid(true);
      searchCliente(cedula, "ex");
      break;
  }
}

/*
 * Busqueda de Persona para la creacion de Usuarios
 * @param {type} ced
 * @param {type} tipo
 * @returns {undefined}
 */
function searchPersona(ced, tipo) {
  tipo === "ec" ? (ced = ced.substring(0, 10)) : ced;
  $.post( "", { searchPersona: true, Prs_Ced: ced },
    function (response) {
      if (response["existe"] === 1) {
        // as user
        $("#frmUser")[0].reset();
        $("#Prs_Ced").fieldValid("");
        $("#Prs_Ced").focus();
        flag_guardar = 1;

        $.alert(
          "La persona " + ced + " tiene un usuario asignado en esta sucursal"
        );
      }
      if (response["existe"] === 2) {
        // as person
        $("#Usu_Ced").val($("#Prs_Ced").val());
        $("#Ciu_Cod").val(response["Ciu_Cod"]).trigger("chosen:updated");
        $.extend(response, { Prs_Ced: $("#Prs_Ced").val() });
        $("#frmUser").setData(response, false);
        $("#Usu_Ced").prop("disabled", true);
        $("#Usu_Pal").focus();
        flag_guardar = 2;
      }
      if (response["existe"] === 0) {
        // not it bd
        $("#Prs_Nom").val("");
        $("#Prs_Ape").val("");
        $("#Prs_Nom").val("");
        $("#Usu_Ced").val($("#Prs_Ced").val());
        $("#Usu_Ced").prop("disabled", true);
        flag_guardar = 0;
      }
    },
    "json"
  ).fail(function () {
    $.alert();
  });
}

/*
 * Valida Cedula
 */
function validaNoIdentif(number) {
  var digitos = number.split(""),
    dto = digitos.length,
    acu = 0,
    resp = { success: false, message: "" },
    coef = {
      NA: [2, 1, 2, 1, 2, 1, 2, 1, 2],
      PU: [3, 2, 7, 6, 5, 4, 3, 2, 0],
      PR: [4, 3, 2, 7, 6, 5, 4, 3, 2],
    },
    modulo,
    acum = 0;
  if (dto === 0) resp["message"] = "No has ingresado ning\u00fan dato!";
  else {
    for (var i = 0; i < dto; i++)
      if (!isNaN(digitos[i])) {
        digitos[i] = digitos[i] * 1;
        acu = acu + 1;
      }
    if (acu === dto) {
      var tipo = digitos[2];
      if (tipo === 7 || tipo === 8)
        resp["message"] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"';
      else {
        tipo = tipo < 6 ? "NA" : tipo === 6 ? "PU" : tipo === 9 ? "PR" : "";
        modulo = tipo === "NA" ? 10 : 11;
        resp["tipo_abrev"] = tipo;
        resp["tipo"] =
          tipo === "NA"
            ? "Natural"
            : tipo === "PR"
              ? "Privada"
              : tipo === "PU"
                ? "P\u00fablica"
                : "";
      }
      if (dto !== 10 && dto !== 13) {
        resp["message"] = "La cantidad de d\u00EDgitos deben ser 10 o 13";
        return resp;
      } else {
        resp["doc_abr"] = dto === 10 ? "C" : dto === 13 ? "R" : "";
        resp["doc"] = dto === 10 ? "C\u00E9dula" : dto === 13 ? "R.U.C." : "";
      }
      if (number.substring(0, 2) * 1 > 24)
        resp["message"] =
          "Los dos primeros d\u00EDgitos no pueden ser mayores a 24.";
      if (dto === 13) {
        if (number.substring(10, 13) !== "001")
          resp["message"] =
            "Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.";
        // if (tipo === "PU" && number.substring(9, 13) !== "0001"     )
        if (tipo === "PU" && number.substring(10, 13) !== "001")
          resp["message"] =
            "El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001";
      } else if (tipo === "PU" || tipo === "PR")
        resp["message"] =
          "El R.U.C. de las empresas " +
          resp["tipo"] +
          "s deben tener 13 digitos!";
      if (resp["message"].length > 0) return resp;


      for (var a = 0; a < 9; a++) {
        var resul = digitos[a] * coef[tipo][a];
        acum += resul - (tipo === "NA" && resul >= 10 ? 9 : 0);
      }

      var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;

      if (digitos[tipo === "PU" ? 8 : 9] !== digitoVerificador) {
        resp["message"] = "El n\u00famero de " + resp["doc"] + " de la " + (tipo === "NA" ? "Persona Natural" : "Empresa " + resp["tipo"]) +
          " ingresado es inv\u00E1lido!";
      }

      if (resp["message"].length === 0) resp["success"] = true;

    } else resp["message"] = "ERROR: Solo debe contener d\u00EDgitos!";
  }
  return resp;
}

/* Valida existencia de Punto */
function validatePunto() {
  $.getDataJson( "", { validatePunto: true, Pun_Des: $("#Pun_Des").val() },
    function (res) {
      $("#Pun_Des").fieldValid(true);
    },
    function (
      err // on success false
    ) {
      $("#Pun_Des").fieldValid(false, err.message);
      $("#Pun_Des").focus();
    }
  );
}

/* Valida Passwords */
function validatePass(Usu_Pal, Usu_Pal_C) {
  if ($("#" + Usu_Pal).val() !== $("#" + Usu_Pal_C).val()) {
    $("#" + Usu_Pal).fieldValid(false, "No coinciden los caracteres");
    $("#" + Usu_Pal_C).fieldValid(false, "No coinciden los caracteres");
    $("#" + Usu_Pal_C).val("");
    //$('#'+Usu_Pal_C).focus();
    flag_pass = 1;
  } else {
    $("#" + Usu_Pal).fieldValid(true);
    $("#" + Usu_Pal_C).fieldValid(true);
    flag_pass = 0;
  }
}

/* Modificar Usuario */
function modificarUser(row) {
  $("#editDialog").dialog("open");
  $("#formDialog").setData(row);
  $("#Usu_Pal_m").val("");
  $("#Usu_Pal_Cm").val("");
  $("#Prs_Ced_m").val(row.Prs_Ced);
  $("#persona").val(row.persona);
  pun_des = row.Pun_Des_m;

  // Limpiar campos del cliente antes de cargar nuevos datos
  $("#fieldsetModCli #Cli_Cod_Aux").val('');
  $("#fieldsetModCli #Prs_Cod_Aux").val('');
  $("#fieldsetModCli #bandera_prov1").val("nosel");
  $("#fieldsetModCli #Prs_Ced_Aux").val('');
  $("#fieldsetModCli #CliNom").val('');
  $("#fieldsetModCli #Pla_Cod_m").val('').empty().append($('<option>', { value: '', text: 'Seleccione...' }));

  // Llenar Usu_Cod_Aux con el código del usuario que se está modificando
  $("#fieldsetModCli #Usu_Cod_Aux").val(row.Usu_Cod || '');

  $.getDataJson( "", { getPerfilByUser: true, Prs_Cod: row.Prs_Cod, Usu_Cod: row.Usu_Cod },
    function (res) {
      json_asig = [];
      json_asig = res.perfiles; // perfiles asignados

      $.each(res.perfiles, function (i, val) {
        $("#Per_Cod_m option[value=" + val.Per_Cod + "]").prop( "selected", true );
      });
      
      $.each(res.sucursales, function (i, val) {
        $("#Suc_Cod_m option[value=" + val.Suc_Cod + "]").prop( "selected", true );
      });

      $("#Per_Cod_m").trigger("chosen:updated");
      $("#Suc_Cod_m").trigger("chosen:updated");
    },
    function (err) {
      $.alert(err);
    }
  );

  // Consultar si el usuario tiene un cliente asignado en manifiesto_usuario
  $.getDataJson(
    "",
    { getClienteByUser: true, Usu_Cod: row.Usu_Cod },
    function (res) {
      if (res.success && res.cliente && Object.keys(res.cliente).length > 0) {
        var cliente = res.cliente;
        console.log("Datos del cliente recibidos:", cliente);
        // Llenar los campos del cliente en el formulario de modificación
        $("#fieldsetModCli #Cli_Cod_Aux").val(cliente.Cli_Cod || '');
        $("#fieldsetModCli #Prs_Cod_Aux").val(cliente.Prs_Cod || '');
        $("#fieldsetModCli #bandera_prov1").val("sel");
        $("#fieldsetModCli #Prs_Ced_Aux").val(cliente.Prs_Ced || '');
        $("#fieldsetModCli #CliNom").val(cliente.nombre || '');
        
        // Cargar plantas del cliente y seleccionar la planta existente
        if (cliente.Cli_Cod) {
          // Guardar el Pla_Cod del cliente antes de hacer la llamada AJAX
          var plantaExistente = cliente.Pla_Cod ? String(cliente.Pla_Cod) : null;
          console.log("Planta existente a seleccionar:", plantaExistente, "Tipo:", typeof cliente.Pla_Cod);
          
          $.getJSON( "", { AjaxPlanta: true, Cli_Cod: cliente.Cli_Cod },
            function (response) {
              // Asegurar que la respuesta sea un objeto y tenga la propiedad rows
              if (response && $.isArray(response.rows)) {
                var selector = $("#fieldsetModCli #Pla_Cod_m");
                selector.empty();
                selector.append($('<option>', { value: '', text: 'Seleccione...' }));

                $.each(response.rows, function (i, val) {
                  var optionValue = String(val.Pla_Cod);
                  selector.append(
                    $('<option>', {
                      value: optionValue,
                      text: (val.Pla_Nom || '') + ' - ' + (val.Pla_Lic || '')
                    })
                  );
                });
                
                // Seleccionar automáticamente la planta si ya tiene una asignada
                if (plantaExistente) {
                  console.log("Intentando seleccionar planta:", plantaExistente, "Opciones disponibles:", selector.find('option').map(function() { return $(this).val(); }).get());
                  selector.val(plantaExistente);
                  // Verificar si se seleccionó correctamente
                  if (selector.val() !== plantaExistente) {
                    console.warn("No se pudo seleccionar la planta. Valor intentado:", plantaExistente, "Valor actual:", selector.val());
                  } else {
                    console.log("Planta seleccionada correctamente");
                  }
                  // Forzar la actualización visual si el select no se actualiza
                  selector.trigger('change');
                }
              } else {
                console.warn("Respuesta de AjaxPlanta sin filas válidas:", response);
              }
            }
          );
        }
      }
    },
    function (err) {
      // Si no hay cliente asignado o hay error, no hacer nada (los campos ya están limpios)
      console.log("No se encontró cliente asignado o hubo un error:", err);
    }
  );
}
function closeSessions(Usu_Cod) {
  $.createDialogConfirm( "Est&aacute; seguro de modificar el Usuario ?", { action: "closeSessions", Usu_Cod: Usu_Cod },
    function (data) {
      socketVentanas.send("json:" + $.jsonParser(data));
    }
  );
}
