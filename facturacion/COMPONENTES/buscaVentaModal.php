<?php

/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
?>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="ventaDialog" title="B&uacute;squeda de Ventas Exterior"></div>   
    <script>
    $(function() { 
        $('#ventaDialog').createSearchDialog({
            datatype: 'local',
            colModel:[                   
                { label: 'Cód. Int.', name: 'Vet_Cod', width: 30 ,align:"center", key:true, hidden: false},                  
                { label: 'Tipo Documento', name: 'Tic_Des', width: 100 },
                { label: 'No. Documento', name: 'Secuencia', width: 90, align:"center" }, 
                { label: 'Export.', name: 'Exportacion', width: 20, align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Tiene Exportacion',noMsg:' '}, title:false},
                { label: 'Fecha', name: 'Caj_Fec', width: 45, align:"center"},
                { label: 'RUC/Cedula', name: 'Ruc', width: 100, align:"center" },
                { label: 'Cliente', name: 'Cliente', width: 75},             
                { label: 'Estado', name: 'Vet_Est', width: 20,align:"center", formatter:'estado', title:false },   
              { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false, formatter:function (cv, opts, rObj) { return  $.getGridButton(SelectVta,rObj,'Seleccione Venta'); } }
          ]},{ title:'Cuenta', options:[{label:' Cliente ',value:'p'},{label:' Cédula/Ruc ',value:'c'},{label:' No. Documento ',value:'d'}] });
    });
    </script>
    
