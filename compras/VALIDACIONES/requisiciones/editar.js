var container;
var today = new Date();
var gestionarItem
let observacion = ''

$(function() {
    gestionarItem = $("#TablaRequisicionDetalle");
    gestionarItem.createGrid({
        caption:'',
        data: [],
        rowNum: 10000000,
        height: 'auto',
        footerrow: true,
        headertitles: true,
        selectGridRows: false,
        colModel: [
            { name: 'index', label: 'Index', width: 10, sorttype: 'int', align: 'center', key: true, hidden: true },
            { name: 'Rqd_Cant', label: 'Cant.', labelLong: 'Cantidad', width: 10, align: 'right', title: false, editable: (true), editoptions: { dataInit: styleCant } },
            { name: 'Rqd_Uni', label: 'Uni.', labelLong: 'Unidad', width: 25, resizable: false },
            { name: 'Ite_Lar', label: 'Descripci&oacute;n', width: 100 },
            { name: 'Mar_Des', label: 'Marca', width: 25, resizable: false },
            { name: 'Cat_Des', label: 'Categoria', width: 25, resizable: false },
        ]
    }, true, 'itemsPager', { view: false }).gridButtonsAdd([
        
    ]);
    gestionarItem.getFootRow(true);
    gestionarItem.jqGrid('footerData', 'set', {
        Ite_Lar: '<div class="footerFact formDatos" class="formDatos"><label style="position:relative;text-align: left;">Observaci&oacute;n:</label><textarea id="Req_Obs_Txt" name="Req_Obs_Txt" tabindex="12" class="text" disabled onchange="">'+observacion  +'</textarea></div><div>&nbsp;</div><div>&nbsp;</div>',
       
    }, false);



    $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    container = $("#container");
    container.createGrid({
        postData: $("#searchContainer").getData("profAjax"),
        height: 250,
        caption: 'Requisiciones Registradas',
        // stateCol: 'Req_Est',
        stateConfig: { Inactiva: 'cellRed2' },
        colModel: [
            { label: 'Num', name: 'Req_Num', align: "center", hidden: false, key: true, width: 10 },
            { label: 'Fecha Cre.', name: 'Req_Fec_Cre',index: 'Req_Fec_Cre',align: "center", width: 15 },
            { label: 'Requisitor', name: 'Requisitor',  hidden: false, width: 55 },
            { label: 'Tipo', name: 'Rtp_Des', width: 20, classes: 'nowrap' },
            { label: 'Fecha Ent.', name: 'Req_Fec_Ent', width: 25},
            { label: $.createIcon('home'), name: 'actReg', align: "center", width: 7, formatter: 'gridButton', formatoptions: { action: verProforma, conditional: function(o) { return o.Req_Est !== 'Inactiva'; }, icon: 'arrow-right', type: 'success', title: 'Ver requisicion' } },
        ],
        sortorder: "desc",
        sortname: 'Req_Fec_Ent',
        grouping: true,
        groupingView: {
            groupField: ['Req_Fec_Ent'],
            groupColumnShow: [true],
            groupDataSorted: true,
            groupOrder : 'desc'
        },
        /* sortname: "Req_Fec_Cre",
        sortorder: "desc",
        sortable: true, */
        //selectGridRows: false
    }, true, "#containerPager", {}).gridButtonsAdd([{ 
        }]);

    let desde = $('#desdeT');
    let hasta = $('#hastaT');
    $.createDateRange(desde, hasta);
    $("#tabsProformas").createTabs();
    enableDateOne();
    enableDateTwo();
});

function styleCant(e, obj, opt) {

    e.style.textAlign = 'right';
    e.placeholder = '0';
    $(e).on('keyup', function() {
        if (isNaN(this.value)) { $(this).val('1').focus(); } else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 2) this.value = $.toFixed(this.value); }
        updateRowItem(obj);
    });
}

function enableDateOne() {
    $('#radsct3').on("click", function() {
        $('#divFecha').show();
        $('#desdeT').removeAttr('disabled');
        $('#hastaT').removeAttr('disabled');
        $('#search').attr('disabled', 'disabled');

    });
}

function enableDateTwo() {
    $('#radsct1').on("click", function() {
        $('#divFecha').hide();
        $('#desdeT').attr('disabled', 'disabled');
        $('#hastaT').attr('disabled', 'disabled');
        $('#search').removeAttr('disabled');
    });
    $('#radsct2').on("click", function() {
        $('#divFecha').hide();
        $('#desdeT').attr('disabled', 'disabled');
        $('#hastaT').attr('disabled', 'disabled');
        $('#search').removeAttr('disabled');
    });
}

Object.size = function(obj) {
    var size = 0,
        key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

$("#radsc3").on("click", function() {
    $('#searchProf').find('#desde').removeAttr('disabled');
    $('#searchProf').find('#desde').show();
    $('#searchProf').find('#hasta').removeAttr('disabled');
    $('#searchProf').find('#hasta').show();
    $('#searchProf').find('#h').removeAttr("style");
    $('#searchProf').find('#d').removeAttr("style");
    $('#searchProf').find('#dl').show();
    $('#searchProf').find('#dlh').show();
    $('#searchProf').find('#search').attr('disabled', 'disabled');
});

$('#searchProf').find("#desde").on("change", function() {
    if ($('#searchProf').find('#desde').val() > $('#searchProf').find('#hasta').val()) {
        $.alert('El valor de Desde es superior a Hasta');
        $('#desde').val('');
    }
});

$('#searchProf').find("#hasta").on("change", function() {
    if ($('#searchProf').find('#hasta').val() < $('#searchProf').find('#desde').val()) {
        $.alert('El valor de Hasta debe ser superior o igual al Desde');
        $('#hasta').val('');
    }


});

$("#radsc1").on("click", function() {
    $('#searchProf').find('#desde').attr('disabled', 'disabled');
    $('#searchProf').find('#hasta').attr('disabled', 'disabled');
    $('#searchProf').find('#search').removeAttr('disabled');
    $('#searchProf').find('#desde').hide();
    $('#searchProf').find('#dl').hide();
    $('#searchProf').find('#hasta').hide();
    $('#searchProf').find('#dlh').hide();
    $('#searchProf').find('#search').val('');
});
$("#radsc2").on("click", function() {
    $('#searchProf').find('#desde').attr('disabled', 'disabled');
    $('#searchProf').find('#hasta').attr('disabled', 'disabled');
    $('#searchProf').find('#search').removeAttr('disabled');
    $('#searchProf').find('#desde').hide();
    $('#searchProf').find('#dl').hide();
    $('#searchProf').find('#hasta').hide();
    $('#searchProf').find('#dlh').hide();
    $('#searchProf').find('#search').val('');

});

function verProforma(row) {
    console.log('ROW',row);
    $('#prubaTabla').clearGrid();
    $('#Req_Num').val(row['Req_Num']);
    var next = $("#prubaTabla").jqGrid('getCol', 'index', false, 'max');
    $.post(
        '', 
        { getRequisicionPorId: true, Req_Cod: row['Req_Cod'] }, 
        function(response) {
            console.log("RESPONSE",response)
            gestionarItem.jqGrid('clearGridData')
            $('#Prs_Ced').val(response['requisicion']['Prs_Ced']);
            $('#Requisitor').text(response['requisicion']['Requisitor']);
            $('#Prs_Dir').text(response['requisicion']['Prs_Dir']);
            $('#Prs_Cor').text(response['requisicion']['Prs_Cor']);
            $('#Req_Fec_Cre').text(response['requisicion']['Req_Fec_Cre']);
            $('#Req_Fec_Ent').text(response['requisicion']['Req_Fec_Ent']);
            $('#Req_Tip').text(response['requisicion']['Rtp_Des']);
            $('#Usuario').val(response['requisicion']['Usuario']);
            $('#Req_Per_Sol').text(response['requisicion']['Req_Per_Sol']);
            $('#Req_Ent_Par').text(response['requisicion']['Req_Ent_Par']);
            $('#Req_Ent_Com').text(response['requisicion']['Req_Ent_Com']);
            $('#Req_Obs_Txt').text(response['requisicion']['Req_Obs']);
            gestionarItem.jqGrid('addRowData', 1, response['requisicion_det'], 'last')
    }, 'json').fail(function() {  })

    $('#allProformas').moveComp('#documentoVistaD').updateGridsSizes();

}