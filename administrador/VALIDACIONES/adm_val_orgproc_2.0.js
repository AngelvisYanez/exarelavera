var $treeview, selected;
$(function(){
    $treeview=$('#directorios');
    $treeview.jstree({        
        core : {
            data: { 'url': urlTree,"dataType": "json" },
            multiple: false,
            check_callback: function(operation, node, node_parent, node_position, more) { 
                if (operation === 'move_node') return  ($.vv(node_parent) &&  $.vv(node_parent.original) && node_parent.original.type === 'Org') || node_parent.id==='#' ;
            }
        }, dnd: {           
            is_draggable: function(node){ return $.vv(node) && $.vv(node[0]) && !node[0].state.disabled && $.vv(node[0].original) && node[0].original.Org_Niv !== '0' ; }
        }, types : {
            Org: {"icon" : "glyphicon glyphicon-folder-open yellow"},        
            Pcs: {"icon" : "glyphicon glyphicon-chevron-right green"}
        }, node_customize:{
            types:{
                Org:function(el,node){                    
                    var o=node.original;
                    node.text='<i class="grey">Dir.:</i> '+o.Org_Des+($.isEmpty(o.Org_Det)?'':' <i class="glyphicon glyphicon-info-sign blue" title="'+o.Org_Det+'"></i>');
                    node.icon=$.isEmpty(o.Org_Ico)?node.icon:o.Org_Ico+" orange";
                },
                Pcs:function(el,node){ 
                    var o=node.original;
                    node.text='<b> '+o.Pcs_Lin+'</b> ('+o.Pcs_Nom+')'+($.isEmpty(o.Pcs_Det)?'':' <i class="glyphicon glyphicon-info-sign purple" title="'+o.Pcs_Det+'"></i>');
                    node.icon=o.Pcs_Tip!=='P'?"glyphicon glyphicon-print brown":($.isEmpty(o.Pcs_Ico)?node.icon:o.Pcs_Ico+" green");
                }
            }
        }, plugins: ["types","dnd",'conditionalselect',"node_customize","search"]
    }).on('select_node.jstree', function (e, data) {          
        var html='&nbsp;';
        selected={};
        $('.none').hide();        
        if(data.node.text!==''){ 
            html="<i class='"+(data.node.original.icon===null?'fa fa-angle-double-right':data.node.icon)+" purple'></i>&nbsp<b class='blue'><u>"+$('<div>'+data.node.text+'</div>').text().trim().replace(/\((.*)\)/g, "")+":</u></b>&nbsp;&nbsp;&nbsp;";
            selected=data.node.original;
            if(data.node.original.type==='Org'){           
                $('.grupo').show();
            }else if(data.node.original.type==='Pcs'){            
                $('.proceso').show();
            }
        }
        $('#plan-footer').html(html);          
    }).on('move_node.jstree', function (e, data) {
        var parent=$treeview.getNodeItemParent(data.node.id);
        var data={moveData:true, id:data.node.id, type:data.node.original.type, parent:parent.id, brothers:parent.children};
        $.saveDataJson('',data,function(r){ return false; },function(){ $treeview.jstree(true).refresh(); }, function(){ $treeview.jstree(true).refresh(); });
    }).on('refresh.jstree', function(e, data) { 
        var sn=$treeview.jstree("get_selected");       
        if($.vv(sn[0])) $treeview.scrollFocusNode(sn[0]); 
    });;
    $('#addRutas').createDialog({icon:'plus',height:125,width:400});
    setIcons();
});
function updateTree(){
    $treeview.jstree(true).refresh(); 
    $('.formNone').hide();
}
function searchNode(val){
    this.val('').removeClass('clearable');
    $('.formNone').hide();
    var node=$treeview.searchString(val);    
    setTimeout(function(){ $treeview.scrollFocusNode(node); },100);
}
function setFolder(data,isNew){
    $('.formNone').hide();
    $('#basesDatosGroup').hide(); // Ocultar selector de procesos
    var frm=$('#formDirectorio').show();  
    data=!isNew?data:{Org_Niv:data.Org_Cod,Parent:data.Org_Des,Org_Ord:$treeview.getNodeItem(data.Org_Cod*1===0?'#':'G_'+data.Org_Cod).children.length+1};
    if(data.Org_Niv*1===0) data['Parent']='ROOT'; else data['Parent']=$treeview.getNodeItem('G_'+data.Org_Niv).original.Org_Des; 
    
    // Mostrar selector de bases de datos
    if(!isNew){
        // Si es edición, cargar bases donde existe el directorio
        $('#basesDatosGroupDirectorio').show();
        loadBasesDatosDirectorio(data['Org_Des'], data['Org_Niv']);
    } else {
        // Si es nuevo, cargar todas las bases
        $('#basesDatosGroupDirectorio').show();
        loadBasesDatosDirectorio();
    }
    
    frm.setData(data);
    $('#Org_Ico').trigger('chosen:updated');
}
function setProcess(data,isNew){
    $('.formNone').hide();
    $('#basesDatosGroupDirectorio').hide(); // Ocultar selector de directorio
    var frm=$('#formProceso').show();     
    if(!isNew){ 
        data['Parent']=$treeview.getNodeItem('G_'+data.Org_Cod).original.Org_Des;
        data['Pcs_Nom_Original']=data['Pcs_Nom'];
        data['Pcs_Tip_Original']=data['Pcs_Tip'];
        $('#basesDatosGroup').show();
        loadBasesDatos(data['Pcs_Nom'], data['Pcs_Tip']);
    } else {
        $('#basesDatosGroup').show();
        loadBasesDatos();
    }
    frm.setData(!isNew?data:{Org_Cod:data.Org_Cod,Parent:data.Org_Des,Pcs_Ord:$treeview.getNodeItem('G_'+data.Org_Cod).children.length+1, Tpr_Cod:1, Pcs_Tip:'P'});
    $('#Pcs_Ico').trigger('chosen:updated');
}

function loadBasesDatos(pcsNom, pcsTip){
    var d = { loadBasesDatos: true };
    if(pcsNom && pcsTip){ d.Pcs_Nom = pcsNom; d.Pcs_Tip = pcsTip; }
    $.ajax({
        url: '',
        type: 'POST',
        data: d,
        dataType: 'json',
        success: function(response){
            if(response.success && response.bases){
                var html = '';
                $.each(response.bases, function(i, base){
                    html += '<div style="margin-bottom: 5px;">';
                    html += '<label style="font-weight: normal; cursor: pointer; margin: 0;">';
                    html += '<input type="checkbox" name="bases_datos[]" value="' + base.Dat_Dis + '"' + (base.existe ? ' checked' : '') + ' style="margin-right: 5px;">';
                    html += '<span>' + (base.Emp_Nom || base.Dat_Dis.toUpperCase()) + '</span>';
                    html += '</label>';
                    html += '</div>';
                });
                $('#basesDatosCheckboxes').html(html);
            }
        },
        error: function(){
            $.alert('Error al cargar las bases de datos disponibles.');
        }
    });
}
function loadBasesDatosDirectorio(orgDes, orgNiv){
    var d = { loadBasesDatosDirectorio: true };
    if(orgDes){ d.Org_Des = orgDes; d.Org_Niv = orgNiv; }
    $.ajax({
        url: '',
        type: 'POST',
        data: d,
        dataType: 'json',
        success: function(response){
            if(response.success && response.bases){
                var html = '';
                $.each(response.bases, function(i, base){
                    html += '<div style="margin-bottom: 5px;">';
                    html += '<label style="font-weight: normal; cursor: pointer; margin: 0;">';
                    html += '<input type="checkbox" name="bases_datos_directorio[]" value="' + base.Dat_Dis + '"' + (base.existe ? ' checked' : '') + ' style="margin-right: 5px;">';
                    html += '<span>' + (base.Emp_Nom || base.Dat_Dis.toUpperCase()) + '</span>';
                    html += '</label>';
                    html += '</div>';
                });
                $('#basesDatosCheckboxesDirectorio').html(html);
            }
        },
        error: function(){
            $.alert('Error al cargar las bases de datos disponibles.');
        }
    });
}
function saveForm(data) {
    // Recoger bases seleccionadas tanto para insertar como para actualizar
    if($('#basesDatosGroup').is(':visible')){
        var basesSeleccionadas = [];
        $('#basesDatosCheckboxes input[type="checkbox"]:checked').each(function(){
            basesSeleccionadas.push($(this).val());
        });
        data.bases_datos = basesSeleccionadas;
    }
    // Recoger bases seleccionadas para directorio
    if($('#basesDatosGroupDirectorio').is(':visible')){
        var basesSeleccionadasDirectorio = [];
        $('#basesDatosCheckboxesDirectorio input[type="checkbox"]:checked').each(function(){
            basesSeleccionadasDirectorio.push($(this).val());
        });
        data.bases_datos_directorio = basesSeleccionadasDirectorio;
    }
    
    $.saveDataJson("", data, function (r){
        var mensaje = '';
        
        // Si hay un mensaje del servidor, usarlo directamente (ya viene formateado)
        if(r.message){
            // Convertir saltos de l�nea a <br> para HTML
            mensaje = r.message.replace(/\n/g, '<br>');
        } else {
            // Construir mensaje manualmente si no viene del servidor
            if(r.success){
                if(r.insertadas && r.insertadas.length > 0){
                    mensaje += '<b class="green">Proceso insertado exitosamente en:</b> ' + r.insertadas.join(', ') + '.<br>';
                }
                if(r.existentes && r.existentes.length > 0){
                    mensaje += '<b class="orange">El proceso ya existe en:</b> ' + r.existentes.join(', ') + '.<br>';
                }
                if(r.errores && r.errores.length > 0){
                    mensaje += '<b class="red">Errores al insertar en:</b> ';
                    var errores = [];
                    $.each(r.errores, function(i, err){
                        errores.push(err.base + ' (' + err.error + ')');
                    });
                    mensaje += errores.join(', ') + '.<br>';
                }
            } else {
                mensaje = r.error || 'Error al guardar el proceso.';
            }
        }
        
        if(mensaje){
            $.alert(mensaje);
        }
        
        $treeview.jstree(true).refresh();
        $('.formNone').hide();
        return false;
    });
}
function anulaProcess(data){
    $.createDialogConfirm('¿Est&aacute; seguro que desea eliminar el registro, no se podra reversar?',{anulaData:true, id:data.id, type:data.type},function(data){
        $.saveDataJson("", data, function (r){
            $treeview.nodeItemEnabled(selected.id,false);
            $('.formNone').hide();
        });
    });
}
function movFila(type){  
    var parent=$treeview.getNodeItemParent(selected.id);
    var data={moveData:true, movi:type, id:selected.id, type:selected.type, parent:parent.id, brothers:parent.children};
    if(type==='up' && data.id===data.brothers[0]) return $.alert("<b class='red'>ERROR: </b>No se Puede <u class='green'>Subir</u> mas!");
    if(type==='down' && data.id===data.brothers[data.brothers.length-1]) return $.alert("<b class='red'>ERROR: </b>No se Puede <u class='green'>Bajar</u> mas!");
    var pos=$.inArray(data.id, data.brothers);
    $treeview.jstree("move_node", "#"+data.id, "#"+parent.id, pos+(type==='up'?-1:2));
    /*$.saveDataJson('',data,function(r){        
        var pos=$.inArray(data.id, data.brothers);
        //if(parent.id==='#') $treeview.jstree(true).refresh(); 
        //else 
            $treeview.jstree("move_node", "#"+data.id, "#"+parent.id, pos+(type==='up'?-1:2));
        return false;
    });*/
}
function saveRuta(data) {
    $.saveDataJson("", data, function (r){  
        $('#Rut_Cod').append('<option value="'+r.Rut_Cod+'">'+r.Rut_Des.substring(1,r.Rut_Des.length-1)+'</option>');
        $('#addRutas').dialog('close');
    });
}
function setIcons(){
    var html='<option data-icon="fa-home" value=""></option>', icons=[
    'glyphicon glyphicon-adjust',
    'glyphicon glyphicon-align-center',
    'glyphicon glyphicon-align-justify',
    'glyphicon glyphicon-align-left',
    'glyphicon glyphicon-align-right',
    'glyphicon glyphicon-arrow-down',
    'glyphicon glyphicon-arrow-left',
    'glyphicon glyphicon-arrow-right',
    'glyphicon glyphicon-arrow-up',
    'glyphicon glyphicon-asterisk',
    'glyphicon glyphicon-backward',
    'glyphicon glyphicon-ban-circle',
    'glyphicon glyphicon-barcode',
    'glyphicon glyphicon-bell',
    'glyphicon glyphicon-bold',
    'glyphicon glyphicon-book',
    'glyphicon glyphicon-bookmark',
    'glyphicon glyphicon-briefcase',
    'glyphicon glyphicon-bullhorn',
    'glyphicon glyphicon-calendar',
    'glyphicon glyphicon-camera',
    'glyphicon glyphicon-certificate',
    'glyphicon glyphicon-check',
    'glyphicon glyphicon-chevron-down',
    'glyphicon glyphicon-chevron-left',
    'glyphicon glyphicon-chevron-right',
    'glyphicon glyphicon-chevron-up',
    'glyphicon glyphicon-circle-arrow-down',
    'glyphicon glyphicon-circle-arrow-left',
    'glyphicon glyphicon-circle-arrow-right',
    'glyphicon glyphicon-circle-arrow-up',
    'glyphicon glyphicon-cloud',
    'glyphicon glyphicon-cloud-download',
    'glyphicon glyphicon-cloud-upload',
    'glyphicon glyphicon-cog',
    'glyphicon glyphicon-collapse-down',
    'glyphicon glyphicon-collapse-up',
    'glyphicon glyphicon-comment',
    'glyphicon glyphicon-compressed',
    'glyphicon glyphicon-copyright-mark',
    'glyphicon glyphicon-credit-card',
    'glyphicon glyphicon-cutlery',
    'glyphicon glyphicon-dashboard',
    'glyphicon glyphicon-download',
    'glyphicon glyphicon-download-alt',
    'glyphicon glyphicon-earphone',
    'glyphicon glyphicon-edit',
    'glyphicon glyphicon-eject',
    'glyphicon glyphicon-envelope',
    'glyphicon glyphicon-euro',
    'glyphicon glyphicon-exclamation-sign',
    'glyphicon glyphicon-expand',
    'glyphicon glyphicon-export',
    'glyphicon glyphicon-eye-close',
    'glyphicon glyphicon-eye-open',
    'glyphicon glyphicon-facetime-video',
    'glyphicon glyphicon-fast-backward',
    'glyphicon glyphicon-fast-forward',
    'glyphicon glyphicon-file',
    'glyphicon glyphicon-film',
    'glyphicon glyphicon-filter',
    'glyphicon glyphicon-fire',
    'glyphicon glyphicon-flag',
    'glyphicon glyphicon-flash',
    'glyphicon glyphicon-floppy-disk',
    'glyphicon glyphicon-floppy-open',
    'glyphicon glyphicon-floppy-remove',
    'glyphicon glyphicon-floppy-save',
    'glyphicon glyphicon-floppy-saved',
    'glyphicon glyphicon-folder-close',
    'glyphicon glyphicon-folder-open',
    'glyphicon glyphicon-font',
    'glyphicon glyphicon-forward',
    'glyphicon glyphicon-fullscreen',
    'glyphicon glyphicon-gbp',
    'glyphicon glyphicon-gift',
    'glyphicon glyphicon-glass',
    'glyphicon glyphicon-globe',
    'glyphicon glyphicon-hand-down',
    'glyphicon glyphicon-hand-left',
    'glyphicon glyphicon-hand-right',
    'glyphicon glyphicon-hand-up',
    'glyphicon glyphicon-hd-video',
    'glyphicon glyphicon-hdd',
    'glyphicon glyphicon-header',
    'glyphicon glyphicon-headphones',
    'glyphicon glyphicon-heart',
    'glyphicon glyphicon-heart-empty',
    'glyphicon glyphicon-home',
    'glyphicon glyphicon-import',
    'glyphicon glyphicon-inbox',
    'glyphicon glyphicon-indent-left',
    'glyphicon glyphicon-indent-right',
    'glyphicon glyphicon-info-sign',
    'glyphicon glyphicon-italic',
    'glyphicon glyphicon-leaf',
    'glyphicon glyphicon-link',
    'glyphicon glyphicon-list',
    'glyphicon glyphicon-list-alt',
    'glyphicon glyphicon-lock',
    'glyphicon glyphicon-log-in',
    'glyphicon glyphicon-log-out',
    'glyphicon glyphicon-magnet',
    'glyphicon glyphicon-map-marker',
    'glyphicon glyphicon-minus',
    'glyphicon glyphicon-minus-sign',
    'glyphicon glyphicon-move',
    'glyphicon glyphicon-music',
    'glyphicon glyphicon-new-window',
    'glyphicon glyphicon-off',
    'glyphicon glyphicon-ok',
    'glyphicon glyphicon-ok-circle',
    'glyphicon glyphicon-ok-sign',
    'glyphicon glyphicon-open',
    'glyphicon glyphicon-paperclip',
    'glyphicon glyphicon-pause',
    'glyphicon glyphicon-pencil',
    'glyphicon glyphicon-phone',
    'glyphicon glyphicon-phone-alt',
    'glyphicon glyphicon-picture',
    'glyphicon glyphicon-plane',
    'glyphicon glyphicon-play',
    'glyphicon glyphicon-play-circle',
    'glyphicon glyphicon-plus',
    'glyphicon glyphicon-plus-sign',
    'glyphicon glyphicon-print',
    'glyphicon glyphicon-pushpin',
    'glyphicon glyphicon-qrcode',
    'glyphicon glyphicon-question-sign',
    'glyphicon glyphicon-random',
    'glyphicon glyphicon-record',
    'glyphicon glyphicon-refresh',
    'glyphicon glyphicon-registration-mark',
    'glyphicon glyphicon-remove',
    'glyphicon glyphicon-remove-circle',
    'glyphicon glyphicon-remove-sign',
    'glyphicon glyphicon-repeat',
    'glyphicon glyphicon-resize-full',
    'glyphicon glyphicon-resize-horizontal',
    'glyphicon glyphicon-resize-small',
    'glyphicon glyphicon-resize-vertical',
    'glyphicon glyphicon-retweet',
    'glyphicon glyphicon-road',
    'glyphicon glyphicon-save',
    'glyphicon glyphicon-saved',
    'glyphicon glyphicon-screenshot',
    'glyphicon glyphicon-sd-video',
    'glyphicon glyphicon-search',
    'glyphicon glyphicon-send',
    'glyphicon glyphicon-share',
    'glyphicon glyphicon-share-alt',
    'glyphicon glyphicon-shopping-cart',
    'glyphicon glyphicon-signal',
    'glyphicon glyphicon-sort',
    'glyphicon glyphicon-sort-by-alphabet',
    'glyphicon glyphicon-sort-by-alphabet-alt',
    'glyphicon glyphicon-sort-by-attributes',
    'glyphicon glyphicon-sort-by-attributes-alt',
    'glyphicon glyphicon-sort-by-order',
    'glyphicon glyphicon-sort-by-order-alt',
    'glyphicon glyphicon-sound-5-1',
    'glyphicon glyphicon-sound-6-1',
    'glyphicon glyphicon-sound-7-1',
    'glyphicon glyphicon-sound-dolby',
    'glyphicon glyphicon-sound-stereo',
    'glyphicon glyphicon-star',
    'glyphicon glyphicon-star-empty',
    'glyphicon glyphicon-stats',
    'glyphicon glyphicon-step-backward',
    'glyphicon glyphicon-step-forward',
    'glyphicon glyphicon-stop',
    'glyphicon glyphicon-subtitles',
    'glyphicon glyphicon-tag',
    'glyphicon glyphicon-tags',
    'glyphicon glyphicon-tasks',
    'glyphicon glyphicon-text-height',
    'glyphicon glyphicon-text-width',
    'glyphicon glyphicon-th',
    'glyphicon glyphicon-th-large',
    'glyphicon glyphicon-th-list',
    'glyphicon glyphicon-thumbs-down',
    'glyphicon glyphicon-thumbs-up',
    'glyphicon glyphicon-time',
    'glyphicon glyphicon-tint',
    'glyphicon glyphicon-sort',
    'glyphicon glyphicon-transfer',
    'glyphicon glyphicon-trash',
    'glyphicon glyphicon-tree-conifer',
    'glyphicon glyphicon-tree-deciduous',
    'glyphicon glyphicon-unchecked',
    'glyphicon glyphicon-upload',
    'glyphicon glyphicon-usd',
    'glyphicon glyphicon-user',
    'glyphicon glyphicon-volume-down',
    'glyphicon glyphicon-volume-off',
    'glyphicon glyphicon-volume-up',
    'glyphicon glyphicon-warning-sign',
    'glyphicon glyphicon-wrench',
    'glyphicon glyphicon-zoom-in',
    'glyphicon glyphicon-zoom-out',
      'fa fa-500px',
      'fa fa-amazon',
      'fa fa-black-tie',
      'fa fa-cc-diners-club',
      'fa fa-cc-jcb',
      'fa fa-chrome',
      'fa fa-clone',
      'fa fa-commenting',
      'fa fa-commenting-o',
      'fa fa-contao',
      'fa fa-creative-commons',
      'fa fa-expeditedssl',
      'fa fa-firefox',
      'fa fa-fonticons',
      'fa fa-get-pocket',
      'fa fa-gg',
      'fa fa-gg-circle',
      'fa fa-houzz',
      'fa fa-internet-explorer',
      'fa fa-odnoklassniki',
      'fa fa-odnoklassniki-square',
      'fa fa-opencart',
      'fa fa-opera',
      'fa fa-optin-monster',
      'fa fa-registered',
      'fa fa-safari',
      'fa fa-sticky-note',
      'fa fa-sticky-note-o',
      'fa fa-television',
      'fa fa-trademark',
      'fa fa-tripadvisor',
      'fa fa-tv',
      'fa fa-vimeo',
      'fa fa-wikipedia-w',
      'fa fa-y-combinator',
      'fa fa-yc',
      'fa fa-adjust',
      'fa fa-anchor',
      'fa fa-archive',
      'fa fa-area-chart',
      'fa fa-arrows',
      'fa fa-arrows-h',
      'fa fa-arrows-v',
      'fa fa-asterisk',
      'fa fa-at',
      'fa fa-automobile',
      'fa fa-balance-scale',
      'fa fa-ban',
      'fa fa-bank',
      'fa fa-bar-chart',
      'fa fa-bar-chart-o',
      'fa fa-barcode',
      'fa fa-bars',
      'fa fa-battery-0',
      'fa fa-battery-1',
      'fa fa-battery-2',
      'fa fa-battery-3',
      'fa fa-battery-4',
      'fa fa-battery-empty',
      'fa fa-battery-full',
      'fa fa-battery-half',
      'fa fa-battery-quarter',
      'fa fa-battery-three-quarters',
      'fa fa-bed',
      'fa fa-beer',
      'fa fa-bell',
      'fa fa-bell-o',
      'fa fa-bell-slash',
      'fa fa-bell-slash-o',
      'fa fa-binoculars',
      'fa fa-birthday-cake',
      'fa fa-bolt',
      'fa fa-bomb',
      'fa fa-book',
      'fa fa-bookmark',
      'fa fa-bookmark-o',
      'fa fa-briefcase',
      'fa fa-bug',
      'fa fa-building',
      'fa fa-building-o',
      'fa fa-bullhorn',
      'fa fa-bullseye',
      'fa fa-calculator',
      'fa fa-calendar',
      'fa fa-calendar-check-o',
      'fa fa-calendar-minus-o',
      'fa fa-calendar-o',
      'fa fa-calendar-plus-o',
      'fa fa-calendar-times-o',
      'fa fa-camera',
      'fa fa-camera-retro',
      'fa fa-caret-square-o-down',
      'fa fa-caret-square-o-left',
      'fa fa-caret-square-o-right',
      'fa fa-caret-square-o-up',
      'fa fa-cart-arrow-down',
      'fa fa-cart-plus',
      'fa fa-cc',
      'fa fa-certificate',
      'fa fa-check',
      'fa fa-check-circle',
      'fa fa-check-circle-o',
      'fa fa-check-square',
      'fa fa-check-square-o',
      'fa fa-child',
      'fa fa-circle',
      'fa fa-circle-o',
      'fa fa-circle-o-notch',
      'fa fa-circle-thin',
      'fa fa-clock-o',
      'fa fa-close',
      'fa fa-cloud',
      'fa fa-cloud-download',
      'fa fa-cloud-upload',
      'fa fa-code',
      'fa fa-code-fork',
      'fa fa-coffee',
      'fa fa-cog',
      'fa fa-cogs',
      'fa fa-comment',
      'fa fa-comment-o',
      'fa fa-compass',
      'fa fa-copyright',
      'fa fa-credit-card',
      'fa fa-crop',
      'fa fa-crosshairs',
      'fa fa-cube',
      'fa fa-cubes',
      'fa fa-cutlery',
      'fa fa-dashboard',
      'fa fa-database',
      'fa fa-desktop',
      'fa fa-diamond',
      'fa fa-dot-circle-o',
      'fa fa-download',
      'fa fa-edit',
      'fa fa-ellipsis-h',
      'fa fa-ellipsis-v',
      'fa fa-envelope',
      'fa fa-envelope-o',
      'fa fa-envelope-square',
      'fa fa-eraser',
      'fa fa-exchange',
      'fa fa-exclamation',
      'fa fa-exclamation-circle',
      'fa fa-exclamation-triangle',
      'fa fa-external-link',
      'fa fa-external-link-square',
      'fa fa-eye',
      'fa fa-eye-slash',
      'fa fa-eyedropper',
      'fa fa-fax',
      'fa fa-feed',
      'fa fa-female',
      'fa fa-film',
      'fa fa-filter',
      'fa fa-fire',
      'fa fa-fire-extinguisher',
      'fa fa-flag',
      'fa fa-flag-checkered',
      'fa fa-flag-o',
      'fa fa-flash',
      'fa fa-flask',
      'fa fa-folder',
      'fa fa-folder-o',
      'fa fa-folder-open',
      'fa fa-folder-open-o',
      'fa fa-frown-o',
      'fa fa-futbol-o',
      'fa fa-gamepad',
      'fa fa-gavel',
      'fa fa-gear',
      'fa fa-gears',
      'fa fa-gift',
      'fa fa-glass',
      'fa fa-globe',
      'fa fa-graduation-cap',
      'fa fa-group',
      'fa fa-hand-grab-o',
      'fa fa-hand-lizard-o',
      'fa fa-hand-paper-o',
      'fa fa-hand-peace-o',
      'fa fa-hand-pointer-o',
      'fa fa-hand-rock-o',
      'fa fa-hand-scissors-o',
      'fa fa-hand-spock-o',
      'fa fa-hand-stop-o',
      'fa fa-hdd-o',
      'fa fa-headphones',
      'fa fa-heart',
      'fa fa-heart-o',
      'fa fa-heartbeat',
      'fa fa-history',
      'fa fa-home',
      'fa fa-hotel',
      'fa fa-hourglass',
      'fa fa-hourglass-1',
      'fa fa-hourglass-2',
      'fa fa-hourglass-3',
      'fa fa-hourglass-end',
      'fa fa-hourglass-half',
      'fa fa-hourglass-o',
      'fa fa-hourglass-start',
      'fa fa-i-cursor',
      'fa fa-image',
      'fa fa-inbox',
      'fa fa-industry',
      'fa fa-info',
      'fa fa-info-circle',
      'fa fa-institution',
      'fa fa-key',
      'fa fa-keyboard-o',
      'fa fa-language',
      'fa fa-laptop',
      'fa fa-leaf',
      'fa fa-legal',
      'fa fa-lemon-o',
      'fa fa-level-down',
      'fa fa-level-up',
      'fa fa-life-bouy',
      'fa fa-life-buoy',
      'fa fa-life-ring',
      'fa fa-life-saver',
      'fa fa-lightbulb-o',
      'fa fa-line-chart',
      'fa fa-location-arrow',
      'fa fa-lock',
      'fa fa-magic',
      'fa fa-magnet',
      'fa fa-mail-forward',
      'fa fa-mail-reply',
      'fa fa-mail-reply-all',
      'fa fa-male',
      'fa fa-map',
      'fa fa-map-marker',
      'fa fa-map-o',
      'fa fa-map-pin',
      'fa fa-map-signs',
      'fa fa-meh-o',
      'fa fa-microphone',
      'fa fa-microphone-slash',
      'fa fa-minus',
      'fa fa-minus-circle',
      'fa fa-minus-square',
      'fa fa-minus-square-o',
      'fa fa-mobile',
      'fa fa-mobile-phone',
      'fa fa-money',
      'fa fa-moon-o',
      'fa fa-mortar-board',
      'fa fa-motorcycle',
      'fa fa-mouse-pointer',
      'fa fa-music',
      'fa fa-navicon',
      'fa fa-newspaper-o',
      'fa fa-object-group',
      'fa fa-object-ungroup',
      'fa fa-paint-brush',
      'fa fa-paper-plane',
      'fa fa-paper-plane-o',
      'fa fa-paw',
      'fa fa-pencil',
      'fa fa-pencil-square',
      'fa fa-pencil-square-o',
      'fa fa-phone',
      'fa fa-phone-square',
      'fa fa-photo',
      'fa fa-picture-o',
      'fa fa-pie-chart',
      'fa fa-plug',
      'fa fa-plus',
      'fa fa-plus-circle',
      'fa fa-plus-square',
      'fa fa-plus-square-o',
      'fa fa-power-off',
      'fa fa-print',
      'fa fa-puzzle-piece',
      'fa fa-qrcode',
      'fa fa-question',
      'fa fa-question-circle',
      'fa fa-quote-left',
      'fa fa-quote-right',
      'fa fa-random',
      'fa fa-recycle',
      'fa fa-refresh',
      'fa fa-remove',
      'fa fa-reorder',
      'fa fa-reply',
      'fa fa-reply-all',
      'fa fa-retweet',
      'fa fa-road',
      'fa fa-rss',
      'fa fa-rss-square',
      'fa fa-search',
      'fa fa-search-minus',
      'fa fa-search-plus',
      'fa fa-send',
      'fa fa-send-o',
      'fa fa-server',
      'fa fa-share',
      'fa fa-share-alt',
      'fa fa-share-alt-square',
      'fa fa-share-square',
      'fa fa-share-square-o',
      'fa fa-shield',
      'fa fa-shopping-cart',
      'fa fa-sign-in',
      'fa fa-sign-out',
      'fa fa-signal',
      'fa fa-sitemap',
      'fa fa-sliders',
      'fa fa-smile-o',
      'fa fa-soccer-ball-o',
      'fa fa-sort',
      'fa fa-sort-alpha-asc',
      'fa fa-sort-alpha-desc',
      'fa fa-sort-amount-asc',
      'fa fa-sort-amount-desc',
      'fa fa-sort-asc',
      'fa fa-sort-desc',
      'fa fa-sort-down',
      'fa fa-sort-numeric-asc',
      'fa fa-sort-numeric-desc',
      'fa fa-sort-up',
      'fa fa-spinner',
      'fa fa-spoon',
      'fa fa-square',
      'fa fa-square-o',
      'fa fa-star',
      'fa fa-star-half',
      'fa fa-star-half-empty',
      'fa fa-star-half-full',
      'fa fa-star-half-o',
      'fa fa-star-o',
      'fa fa-street-view',
      'fa fa-suitcase',
      'fa fa-sun-o',
      'fa fa-support',
      'fa fa-tablet',
      'fa fa-tachometer',
      'fa fa-tag',
      'fa fa-tags',
      'fa fa-tasks',
      'fa fa-terminal',
      'fa fa-thumb-tack',
      'fa fa-ticket',
      'fa fa-times',
      'fa fa-times-circle',
      'fa fa-times-circle-o',
      'fa fa-tint',
      'fa fa-toggle-down',
      'fa fa-toggle-left',
      'fa fa-toggle-off',
      'fa fa-toggle-on',
      'fa fa-toggle-right',
      'fa fa-toggle-up',
      'fa fa-trash',
      'fa fa-trash-o',
      'fa fa-tree',
      'fa fa-trophy',
      'fa fa-tty',
      'fa fa-umbrella',
      'fa fa-university',
      'fa fa-unlock',
      'fa fa-unlock-alt',
      'fa fa-unsorted',
      'fa fa-upload',
      'fa fa-user',
      'fa fa-user-plus',
      'fa fa-user-secret',
      'fa fa-user-times',
      'fa fa-users',
      'fa fa-video-camera',
      'fa fa-volume-down',
      'fa fa-volume-off',
      'fa fa-volume-up',
      'fa fa-warning',
      'fa fa-wifi',
      'fa fa-wrench',     
      'fa fa-thumbs-down',
      'fa fa-thumbs-o-down',
      'fa fa-thumbs-o-up',
      'fa fa-thumbs-up',
      'fa fa-ambulance',
      'fa fa-bicycle',
      'fa fa-bus',
      'fa fa-cab',
      'fa fa-car',
      'fa fa-fighter-jet',
      'fa fa-plane',
      'fa fa-rocket',
      'fa fa-ship',
      'fa fa-space-shuttle',
      'fa fa-subway',
      'fa fa-taxi',
      'fa fa-train',
      'fa fa-truck',
      'fa fa-wheelchair',
      'fa fa-genderless',
      'fa fa-intersex',
      'fa fa-mars',
      'fa fa-mars-double',
      'fa fa-mars-stroke',
      'fa fa-mars-stroke-h',
      'fa fa-mars-stroke-v',
      'fa fa-mercury',
      'fa fa-neuter',
      'fa fa-transgender',
      'fa fa-transgender-alt',
      'fa fa-venus',
      'fa fa-venus-double',
      'fa fa-venus-mars',
      'fa fa-file',
      'fa fa-file-archive-o',
      'fa fa-file-audio-o',
      'fa fa-file-code-o',
      'fa fa-file-excel-o',
      'fa fa-file-image-o',
      'fa fa-file-movie-o',
      'fa fa-file-o',
      'fa fa-file-pdf-o',
      'fa fa-file-photo-o',
      'fa fa-file-picture-o',
      'fa fa-file-powerpoint-o',
      'fa fa-file-sound-o',
      'fa fa-file-text',
      'fa fa-file-text-o',
      'fa fa-file-video-o',
      'fa fa-file-word-o',
      'fa fa-file-zip-o',
      'icon-admin',
      'icon-bancos',
      'icon-bancos1',
      'icon-caja',
      'icon-contabi',
      'icon-factura',
      'icon-rrhh',
      'icon-tesoreria',
      'icon-tributa',
      'icon-anticipo',
      'icon-ccxcc',
      'icon-ccxpp',
      'icon-cheque',
      'icon-conciliacion',
      'icon-ctadeudora',
      'icon-deuda',
      'icon-nomina',
      'icon-tipopago',
      'icon-comprobante',
      'icon-costosydistribucion',
      'icon-herramientas',
      'icon-libros',
      'icon-informes',
      'icon-liqidacioncompra',
      'icon-parametros',
      'icon-plancuentas',
      'icon-periodoscontables',
      'icon-rolpagos',
      'icon-services',
      'icon-tipoasiento',
      'icon-tipocomprobante',
      'icon-tipopolisa'];
    $.each(icons,function(i,v){
        var val=v.split(' '); if(val.length===1) val[1]=val[0];
        html+="<option data-icon='"+v+"' value='"+v+"'>"+val[1].replace('-',' ').upperFirstWords()+"</option>";
    });
    $('.chosen-select').append(html).createChosenIcon('input-sm',{allow_single_deselect:true});     
}
