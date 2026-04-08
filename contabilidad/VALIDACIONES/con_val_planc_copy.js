/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var $treeview;
$(function(){
    $treeview=$('#cuentas');
    $treeview.jstree({        
        core:{ multiple: false}, 
        types : {
            R:{icon:"fa fa-hand-o-right red"},
            G:{icon:"glyphicon glyphicon-folder-open blue"},
            D:{icon:"fa fa-file-text green"}
        }, 
        node_customize:{ types:{R:format, G:format, D:format} }, 
        plugins: ["types","node_customize","search"]
    });
    $('#Emp_Cod').createChosen('input-sm',{template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc">'+d['Emp_Nom']+'</div>';}});
    document.getElementById('upload').addEventListener('change', handleFileSelect, false);
});
function format(el,node){ var o=node.original; node.text="<b>"+o.Pld_Cdc+"</b> - "+o.Pld_Des; }
function setLength(){ setTimeout(function(){ var total=$treeview.getJsTreeData('',true).length; $('#plan-footer').html('Se Cargaron '+total+' Cuenta(s).&nbsp;&nbsp;&nbsp;'); $('#guardarBtn')[total>0?'show':'hide'](); },100); };
function searchNode(val){
    this.val('').removeClass('clearable');   
    var node=$treeview.searchString(val);    
    setTimeout(function(){ $treeview.scrollFocusNode(node); },100);
}
function getCuentas(){
    var ctas=$treeview.getJsTreeData(null,true);
    for(var i=0,z=ctas.length;i<z;i++){
        ['Pld_Cod','id', 'type'].forEach(e => delete ctas[i].data[e]);
    } return ctas;
}
/* copiar un plan de cuentas */
function getPlanes(Emp_Cod){
    if(Emp_Cod===''){ $('#Pla_Cod').html('<option value="" selected="">Selecione ...</option>'); return; }
    $.getDataJson('',{getPlanes:true,Emp_Cod:Emp_Cod},function(r){
        $('#Pla_Cod').fillSelect(r.Planes,'Pla_Obs','Pla_Cod');
        return false;
    });
}
function loadCuentas(){
    var form=$('#formCuentas').getData('treeAjax');
    $.getDataJson('',form,function(r){
        $treeview.setJsTreeData(r.Cuentas);
        $('#plan-tittle').html('Listado Cuentas '+(new Date().getYear()+1900));
        setLength();
    });
}
/* crear desde excel */
function getChilds(ar,Pld_Cdc){
    var nivel=Pld_Cdc.split('.').length, childs=[];
    $.each(ar,function(i,v){ var nChilds=v.Pld_Cdc.split('.'); if(v.Pld_Cdc!=='R'&&nivel+1===nChilds.length){ var ini=v.Pld_Cdc.substring(0, Pld_Cdc.length+1); if(Pld_Cdc+"."===ini){ v['children']=getChilds(ar,v.Pld_Cdc); childs.push(v); } } });
    return childs;
}    
function formatTree(ar){
    var far=[],z=ar.length,i=0,i;
    var duplicated=[], sorted_arr=ar.slice().sort(function(a,b){ return (a.Pld_Cdc > b.Pld_Cdc)?1:((a.Pld_Cdc < b.Pld_Cdc)?-1:0); });
    for (var i = 0; i < sorted_arr.length - 1; i++){ if (sorted_arr[i+1]['Pld_Cdc']===sorted_arr[i]['Pld_Cdc']) duplicated.push(sorted_arr[i]['Pld_Cdc']); }
    if(duplicated.length>0)
        $.alert((duplicated.length===1?"El <u class='green'>Codigo</u>":"Los <u class='green'>Codigos</u>")+" <b class='blue'>"+duplicated.join("</b>, <b class='blue'>")+"</b> se encuentra"+(duplicated.length===1?" <b class='red'>DUPLICADO</b>!":"n <b class='red'>DUPLICADOS</b>!"));        
    else
        $.each(ar,function(i,v){ if(v['type']==='R'){ v['children']=getChilds(ar,v.Pld_Cdc); far.push(v); } });
    $treeview.setJsTreeData(far);
    setLength();
    return far;
}
function fieldFormat(v,k){
    v = v.replace(/(\r\n|\n|\r)/gm," ").trim();
    if(k==='Codigo'){
        var t="",a=v.split(".");
        $.each(a,function(i,c){ if(c.length>0) t+=((t===''?'':'.')+(c*1)); });
        return t;
    } return v;
}
function parseExcel(file){
    var ar=[];
    var reader = new FileReader();
    reader.onload = function(e) {
        var data = e.target.result;
        var workbook = XLSX.read(data,{type: 'binary'});
        workbook.SheetNames.forEach(function(sheetName){ // Here is your object
            var libro=XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheetName]),
                cuenta={Codigo:'Pld_Cdc',Nombre:'Pld_Des',Tipo:'Pld_Tip'};
            if(libro.length>1){
                for(var i=1,z=libro.length;i<z;i++){
                    var aux={id:"C_"+i};
                    $.each(libro[i],function(k,v){ 
                        var kn=libro[0][k]; 
                        if(!$.isUnd(cuenta[kn])){
                            aux[cuenta[kn]]=fieldFormat(""+v,kn); if(kn==='Tipo'&&$.isUnd(aux['type']))aux['type']=v; if(kn==='Codigo'&&(""+v).length<3) aux['type']='R';  
                        }else{
                            var val=fieldFormat(""+v,kn);
                            aux['parametros']=aux['parametros']||{};
                            aux['parametros'][kn]=val;
                        }
                    });                    
                    if(Object.keys(aux).length>=3)
                        ar.push(aux);
                } 
            }else $.alert('No se encontraron suficientes filas!');              
            formatTree(ar);
            $("#loader").fadeOut("slow");
            return;
        });
        $("#loader").fadeOut("slow");
    };
    reader.onerror = function(ex){ console.log(ex); $("#loader").fadeOut("slow"); };
    reader.readAsBinaryString(file);
}
function handleFileSelect(evt) {
    $("#loader").show();
    var files = evt.target.files; // FileList object        
    if(files.length>0){            
        parseExcel(files[0]); 
        var name=files[0].name.split('.');
        $('#plan-tittle').html('Cuentas - '+name[0]);
        $('#formExcel').setData({});
    }else $.alert("Debe Seleccionar un Archivo!");
}
/* guardar plan de cuentas */
function savePlan(){
    var data={savePlan:$('#plan-tittle').html(), c:$.jsonParser(getCuentas())};
    if(data.c.length===0) return $.alert("El </b>Plan de Cuentas<b> no tiene ningun registro!");
    //console.log(data);
    $.createDialogConfirm('¿Esta seguro que desear guardar el <b class="green">Plan de Cuentas</b>?',data,function(){
        $.saveDataJson('',data,function(r){
            $treeview.setJsTreeData([]);
            $('#formCuentas').setData({});
            $('#plan-tittle').html('Listado Cuentas');
            setLength();
        });
    });
}

