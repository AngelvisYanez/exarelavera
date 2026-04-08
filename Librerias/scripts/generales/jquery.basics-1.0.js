/*
	Autor : Erik Niebla A.
	Mail: 	ep_niebla@hotmail.com,ep.niebla@gmail.com
	Fecha: 	08/08/2015
*/
$(document).ready(function(){   		
    $(window).on('load',function(){
        $(".radioset").buttonset();
        $('.hasDatepicker:not(.hasTimepicker):not(.isTimepicker)').attr('placeholder','yyyy-mm-dd').attr('pattern',$.getDateRegExp());
        $('.isTimepicker').attr('placeholder','hh:mm').attr('pattern',$.getHoraRegExp());
        $('.hasTimepicker').attr('placeholder','hh:mm').attr('placeholder','yyyy-mm-dd hh:mm').attr('pattern',$.getDateRegExp()+' '+$.getHoraRegExp());
        if(!$.isUnd($.mask)){
            $('.hasDatepicker:not(.hasTimepicker):not(.isTimepicker)').mask("9999-99-99",{placeholder:"_"});
            $('.hasTimepicker').mask("9999-99-99 99:99",{placeholder:"_"});
            $('.isTimepicker').mask("99:99",{placeholder:"_"});
        }
        $(document).tooltip({content:function(){ return $(this).attr('title'); }, track: true});	
    });	//$(".isSelectMenu").selectmenu();//$(".isMoney").MoneyFormat("33334");             
});
// FUNCIONES JQGRID 5.0 JQUERY
$.funcOrVal=function(d,arg,def,inside){ if(!$.isBool(inside))inside=false; return $.vv(d)?($.isFunc(d)?($.vv(arg)?d(arg):d()):(inside&&$.isObj(arg)?arg[d]:d)):def; };
$.getFunction=function(f){ var u; return $.isFunc(f)?f:($.isText(f)?($.isset(f)&&$.isFunc(eval(f))?eval(f):u):u);};
$.unformatCellData=function(cv,opts,el){ return $(el).find('div').data('originaldata'); };
$.unformatCellHtml=function(cv,opts,el){ var e=$(el),c=$(e.prop('firstChild')).not('.btn'); return c.is(':input')?c.val():e.text(); };
$.numFormat=function(val,type){ if(!$.vv(type))type='currency'; return $.fn.fmatter(type, val*1,$.jgrid.regional["es"].formatter); };
$.numUnformat=function(val,type){ if(!$.vv(type))type='currency'; return $.unformat($("<span>"+val+"</span>"),{colModel:{formatter:type,formatoptions:$.jgrid.regional["es"].formatter[type]}})*1; };
$.fn.numFormat=function(type){ var i=this.is(':input'); this[i?'val':'html']($.numFormat(this[i?'val':'text'](),type)); return this; };
$.fn.numUnformat=function(type){ var i=this.is(':input'); this[i?'val':'html']($.numUnformat(this[i?'val':'text'](),type)); return this; };
$.groupHeader=function (cellval,opts,rowObject,action){ 
    var dato=cellval, groupIdPrefix=opts.gid+"ghead_", groupIdPrefixLength=groupIdPrefix.length,name=opts.colModel.name,span=opts.colModel.groupSpan, gspan=(($.vv(span)&&span!=='')?span=span.replace(/{0\}/gmi,cellval):cellval),fields=span.match(/([^{]*?)\w(?=\})/gmi);
	if(opts.rowId.substr(0,groupIdPrefixLength)===groupIdPrefix&&$.isUnd(action)){ var data=$('#'+opts.gid).jqGrid("getGridParam", "data")/*getSeletedByComlumn(name,cellval)*/,item,count=0; $.each(data, function(i,v){ if(v[name]===cellval){ if(!$.vv(item))item=v; count++;  } }); gspan=gspan.replace(/{1\}/g,count); if($.vv(fields)) $.each(fields, function(i,v){ if($.vv(item[v])&&isNaN(v)){gspan=gspan.replace(new RegExp('{'+v+'\}','g'), item[v]); } });gspan=gspan.replace(/{\w\}/g,''); return gspan; } return dato;
};
$.originalRow=function(name){ return $.extend({},$.originalDataRow,{name:(name||'OriginalData')}); };
$.originalDataRow={label:'',name:'OriginalData',hidden:true,viewable:false,editable:false,width:0,title:false,formatter:function(cv,opts,rObj){ return $("<div/>").attr('data-originaldata',$.jsonParser($.cloneCO(opts,rObj))).hide().prop('outerHTML'); },unformat:$.unformatCellData };
$.fn.pk=function(){ return this[0].p.keyName; };
$.fn.getOriginalData=function(id,name){if($.vv(id)) return this.jqGrid('getCell',id,(name||'OriginalData')); else return this.getGridColumn((name||'OriginalData'));};
$.fn.getColModel=function(name){ var c; $.each(this[0].p.colModel,function(i,v){ if(v.name===name){ c=v; return false; } }); return c; };
$.fn.gridColUpdate=function(type,cols,resize){ if(this.length>0&&$.inArray(type,['show','hide'])<0||!$.vv(cols)) return this; var b=$.isBool(resize)?resize:true,t=this,w=t[0].p.width; if($.isText(cols))cols=[cols]; $.each(cols,function(i,v){ t[type+'Col'](v); }); if(b){t.setGridWidth(w); t[type+'Col'](cols[0]);} return t; };
$.fn.gridUpdate=function(page){ this.trigger('reloadGrid', [{page:$.vv(page)?page:1}]); return this; };
$.fn.loadUpdate=function(){ var g=!!this.length?this[0].p:null,dt={total:g.lastpage,page:g.page,records:g.records,rows:g.data,userdata:g.userData};  if($.vv(g)){ this.triggerHandler("jqGridLoadComplete", [dt]); if($.isFunc(g.loadComplete)) g.loadComplete.call(this,dt); this.triggerHandler("jqGridAfterLoadComplete", [dt]); } return this; };
$.fn.nextIndex=function(col){ col=$.vv(col)?col:'index'; var next=this.jqGrid('getCol',col,false,'max'); return (isNaN(next)?1:next+1); };
$.fn.getCaption=function(){var caption=''; caption=$($(this)[0].grid.cDiv).find('.ui-jqgrid-title').html(); return caption;};//Return boolean
$.fn.getIndexById=function(id){var ids=this.jqGrid('getDataIDs');for (var i=0; i<ids.length; i++){if(ids[i]===id) return i;}return -1;};//Return int
$.fn.clearGrid=function(footer){this.jqGrid('setGridParam',{datatype:'local'}); this.jqGrid("clearGridData",($.vv(footer)&&footer===true)); return this.jqGrid('setGridParam', {data:[]}).gridUpdate(); };
$.fn.setRow=function(r){ var t=this,i=this.pk(); if(!$.isObj(r)||!$.vv(r[i])) return t; t.jqGrid('addRowData',r[i],r,'last'); t.highlightRow(r[i]); return t; };
$.fn.setRows=function(rows){ this.clearGrid(); if(!$.vv(rows)||!rows.length) return this; return this.jqGrid('setGridParam', {datatype:'local',data:rows,page:1,records:rows.length,total:1}).gridUpdate(); };
$.fn.setRowsByIndex=function(rows,index){ if($.isArray(rows)) for(var i=0;i<rows.length;i++) rows[i][$.vv(index)?index:'index']=i; return this.setRows(rows); };
$.fn.getColumnIndexByName=function (col){var cm=this.jqGrid('getGridParam', 'colModel'),i,l; for(i=0,l=cm.length;i<l;i+=1){if(cm[i].name===col) return i;};return -1;};
$.fn.existsId=function(id){var ids=this.jqGrid('getDataIDs');for (var i=0; i<ids.length; i++){if(String(ids[i])===String(id)) return true;}return false;};//Return boolean
$.fn.existsValByCol=function(col,value){ if(this.getColumnIndexByName(col)===-1) return false; var ids=this.jqGrid('getDataIDs'); for (var i=0; i<ids.length; i++){if(String((this.jqGrid('getRowData', ids[i]))[col])===String(value)) return true;}return false;};//Return boolean
$.fn.selectAllByCol=$.fn.selectAllByComlumn=function (col,marca,formatter){ formatter=($.vv(formatter)?formatter:false); marca=($.vv(marca)?marca:true);var ids=this.jqGrid('getDataIDs'),ban=""; if(marca){ban="Yes";}else{ban="No";}; for(var i=(ids.length-1);i>=0;i--){ if(formatter){ var sett={[col]:marca}; this.changeRow(ids[i],sett); }else this.jqGrid('setCell',ids[i],col,ban);}; return this; };
$.fn.getSelectedByCol=$.fn.getSeletedByComlumn=function (col,marca,allFields){
	var idName=this.pk() ,batch=new Array(),ids=this.jqGrid('getDataIDs');marca=($.vv(marca)?marca:'Yes');allFields=($.vv(allFields)?allFields:true);	
	for (var i=0; i<ids.length; i++){ this.jqGrid('saveRow',ids[i],false,'clientArray'); var data=this.jqGrid('getRowData',ids[i]); if(String(data[col])===String(marca)){ if(allFields){ if(!$.vv(data[idName])) data[idName]=ids[i]; batch.push(data);}else batch.push(ids[i]); } } return batch;
};
$.fn.getGridColumn=function(col){ var batch=new Array(),cols=new Array(); if(!$.vv(col))return batch; var ids=this.jqGrid('getDataIDs');	
	if($.isArray(col)){ for(var j=0;j<col.length;j++){ var i=this.getColumnIndexByName(col[j]); if(i>=0) cols.push({i:i,k:col[j]}); } if(!cols.length) return batch; for(var i=0,z=ids.length;i<z;i++){ var item={}; for(var j=0;j<cols.length;j++){item[cols[j]['k']]=this.jqGrid('getCell',ids[i],cols[j]['i']);} batch.push(item); } }
	else{ if(this.getColumnIndexByName(col)===-1) return batch; for(var i=0,z=ids.length;i<z;i++){ batch.push(this.jqGrid('getCell',ids[i],col)); } } return batch;
}; //return array
$.ocf=function(o){return o.colModel.formatoptions||{};};
$.fn.validCols=function(c){ if($.isArray(c)) for(var i=(c.length-1);i>=0;i--){ if(this.getColumnIndexByName(c[i])<0) c.splice(i,1); } else c=$.isText(c)&&this.getColumnIndexByName(c)>-1?[c]:[];  return c; };
$.fn.getFootRow=function(noBorder){ var id=this.attr('id'), fRow=$("#gbox_"+id+" #gview_"+id+" .ui-jqgrid-sdiv .footrow"); if(noBorder===true) fRow.find('>td:not(:last-child)').css("border-right-color", "transparent").removeClass('ui-state-default'); return fRow; }; 
$.fn.getGridSummary=function(cols){ var o={},t=this, cols=t.validCols(cols); $.each(cols,function(i,v){ var aux=t.jqGrid('getCol',v,false,'sum'); o[v]=$.isNum(aux)?aux:undefined; }); return o; };
$.fn.setGridSummary=function(cols,item,format,ffunc){ var t=this, c=t.validCols(cols), f=format; if(c.length===0) return this; if(!$.isObj(item))item={}; $.extend(item,t.getGridSummary(c)); if(!$.vv(f)||f===true) t.jqGrid('footerData','set',item); else{  $.each(item,function(k,v){ var ft=$.isBool(f)?f:$.inArray(k,f)===-1; t.jqGrid('footerData','set',{[k]:!ft&&$.isFunc(ffunc)?ffunc(v):v},ft); }); }return this; };
$.fn.startGridEdit=function(wh){var aux=$.jgrid.inlineEdit; $.jgrid.inlineEdit={focusField:false}; var ids=this.jqGrid('getDataIDs'); for(var i=(ids.length-1); i>=0 ; i--){if($.isUnd(wh)||($.isFunc(wh)&&wh(this.jqGrid('getRowData',ids[i]))))this.jqGrid('editRow',ids[i]);} $.jgrid.inlineEdit=aux;  return this; };
$.fn.stopGridEdit=function(){var ids=this.jqGrid('getDataIDs'); for (var i=0; i<ids.length; i++){this.jqGrid('saveRow',ids[i],false,'clientArray');} return this; };
$.fn.changeRow=function(rowId,data,defaults,replaceEdit){ if(!$.vv(rowId)||!$.isObj(data)) return; if(!$.isObj(defaults)) defaults={}; var inputs; if($.isObj(replaceEdit)){ inputs=replaceEdit; replaceEdit=true; } if(!$.isBool(replaceEdit))replaceEdit=false;  var t=this,edit=t.find('tr#'+rowId).attr('editable')*1?1:0,edit_fields=(edit?t.find('tr#'+rowId).getDataForced():{}),editing=($.extend(defaults,edit_fields)),new_data={};if(t[0].p.datatype==='local') new_data=$.extend(t.jqGrid('getLocalRow',rowId),data,editing,inputs||{}); else new_data=$.extend(new_data,t.jqGrid('getRowData',rowId),data,editing,inputs||{}); var aux=$.extend(true,{},new_data); if(!replaceEdit){ $.each(edit_fields,function(k,v){delete aux[k];}); t.jqGrid('setRowData',rowId,aux); }else{ if(edit){ if($.isObj(inputs)) t.find('tr#'+rowId).setData(new_data,false);  t.jqGrid('saveRow',rowId, false, 'clientArray'); } t.jqGrid('setRowData',rowId,new_data); if(edit)t.jqGrid('editRow',rowId); } t.trigger('jqGridAfterLoadComplete'); return new_data; };
$.fn.setGridNoPager=function(){$(this.setGridParam({rowNum:10000000,pgbuttons : false,pgtext : "",pginput : false}).getGridParam('pager')).find('td[id$=Pager_center]').hide(); /*this.gridUpdate();*/ return this;};
$.fn.getGridBatch=function(where){ var idName=this.pk(), batch=new Array(),cond=($.isFunc(where)),ids=this.jqGrid('getDataIDs');for (var i=0; i<ids.length; i++){ if(this.find('tr#'+ids[i]).attr('editable')*1) this.jqGrid('saveRow', ids[i], false, 'clientArray'); var rda=this.jqGrid('getRowData', ids[i]); if(!$.vv(rda[idName])) rda[idName]=ids[i]; if(!cond) batch.push(rda); else if(where(rda)) batch.push(rda); } return batch; }; //return array
$.fn.updateGridsSizes=function(){ this.find('[id^="gbox_"]').each(function(index){  var $this=$(this); if($this.actual('width')<=150){ var id=($this.attr('id')).split('_'); id.splice(0,1); id=id.join('_'); $('#'+id).jqGrid("resizeGrid"); } }); return this;};
$.getGridButton=function(action,data,title,icon,text,type,size,attr){ var b=$('<button type="button" onclick="" class="btn btn-'+($.vv(type)?type:'success')+' btn-'+($.vv(size)?size:'xs')+'" title="'+($.vv(title)?title:($.vv(text)?text:'Seleccionar'))+'">'+(icon===''?'':$.createIcon($.vv(icon)?icon:'arrow-right'))+($.vv(text)?' '+text:'')+'</button>'),click; if($.vv(action)&&$.vv(data)){ click=$.isFunc(action)?action.name:action; if(click.indexOf('(')===-1&&click.indexOf(')')===-1) click+="($(this).data('originaldata'))";  if($.isText(click)){ b.attr('onclick',click).attr('data-originaldata',$.jsonParser(data));}} if($.isObj(attr)) b.attr(attr); return($('<div/>').append(b).html()); }; //return html string
$.fn.gridButtonsAdd=function(array){ var $t=this; if(!$.isArray(array)) return $t; $.each(array,function(i,v){ $t.gridButtonAdd(v);}); return this; }; /*individual*/ $.fn.gridButtonAdd=function(opts){ if(!$.vv(opts))opts={}; if(!Object.keys(opts).length) opts={sepclass:'ui-separator'}; if($.vv(opts['sepclass'])) this.jqGrid('navSeparatorAdd',this[0].p.pager,$.extend({sepclass:'ui-separator',sepcontent:''},opts)); else{ if($.vv(opts['buttonicon']))opts['buttonicon']=$.createIcon(opts['buttonicon'],true); opts['caption']=$.vv(opts['caption'])?'&nbsp;'+opts['caption']:''; this.jqGrid('navButtonAdd',this[0].p.pager,$.extend(true,{position:"last"},opts)); } return this; };
$.fn.setCellEv=function(ev){ var t=this; if($.isObj(ev)) $.each(ev,function(k,v){ var fun=$.isFunc(v)?"($('#'+$(this).data('gid')).getColModel($(this).data('name')))['formatoptions']['dataEvents']['"+k+"']":$.isText(v)?v:null; if($.vv(fun)) t.attr('on'+k,fun+(fun.indexOf('(')===-1&&fun.indexOf(')')===-1?".call(this,$(this).data(),$('#'+$(this).data('gid')).jqGrid('getRowData',$(this).data('rowId')));":'')); });  return t; };
$.fn.fmatter.textboxExa=function(cv,opts,cObjt){ var id=opts.rowId, c=opts.colModel,  f=$.ocf(opts), con=$.getFunction(f['conditional']), i=$.getFunction(f['dataInit']), el=$('<input />').attr({type:"text",class:'editable inline-edit-cell '+$.jgrid.styleUI.jQueryUI.inlinedit.inputClass,style:"width: 96%;",'data-gid':opts.gid,'data-name':c.name,'data-row-id':id,value:$.vv(cv)?cv:''}); if($.isFunc(con)&&!con(cObjt)) return cv; if($.isFunc(i)) i(el[0],opts,cObjt); el.setCellEv(f['dataEvents']);  return el.prop('outerHTML'); }; $.fn.fmatter.textboxExa.unformat=$.unformatCellHtml;
$.fn.fmatter.truefalse=function(cv,opts,cObjt){ var f=$.ocf(opts),yv=f['yesValue'],v=$.vv(yv)?($.isFunc(yv)?yv(cv):yv===cv):$.toBool(cv),e=v?'yes':'no',msgVar=e+'Msg',micon=e+'Icon',mcolor=e+'Color',msg=$.funcOrVal(f[msgVar],cObjt,v?'Si':'No'), icon=$.funcOrVal(f[micon],cObjt,v?'ok':'remove'), color=$.funcOrVal(f[mcolor],cObjt,v?'green':'blue'); if(!$.isBool(f['msg'])) f['msg']=true; return (!$.vv(cv)||cv===''?'':$('<div><i/>'+(!f['noText']?'<u class="hidden">'+cv+'</u>':'')+'</div>').attr({'data-originaldata':cv,title:f['msg']?msg.trim():''}).find('i').attr('class',$.createIcon(icon,true)+' '+color).end().prop('outerHTML')); }; $.fn.fmatter.truefalse.unformat=$.unformatCellData;
$.fn.fmatter.checkboxExa=function(cv,opts,cObjt){ var f=$.extend({yes:'S',no:'N'},$.ocf(opts)), i=$.getFunction(f['dataInit']); if(($.vv(f['nullifField'])&&cObjt[f['nullifField']]!==(f['nullifValue']||''))||(f['nullif']===true&&(!$.vv(cv)||cv===(f['nullifValue']||'')))) return ''; var el=$('<input />').attr({type:"checkbox",'data-gid':opts.gid,'data-name':opts.colModel.name,'data-row-id':opts.rowId,value:f['yes'],offval:f['no'],checked:(cv===f['yes']||(!(!$.vv(cv)||cv==='')&&f['defaultChecked']===true)),onchange:f['onchange']}); if($.isFunc(i)) i(el[0],opts,cObjt);  el.setCellEv(f['dataEvents']); return el.prop('outerHTML'); }; $.fn.fmatter.checkboxExa.unformat=function(cv,opts,el){ var f=$.ocf(opts); var ckb=$(el).find('input[type=checkbox]'); if(!ckb.length) return (f['no']||'N'); return ckb.attr(ckb.is(":checked")?'value':'offval'); };
$.fn.fmatter.estado=function(cv,opts){ if(!$.vv(cv)||cv==='') return ''; var f=$.ocf(opts),full=$.vv(f['full'])?f['full']:false; var est='',def={A:'Activo',I:'Inactivo',E:'Eliminado',X:'No Visible'}; if($.vv(f['types'])) est=f['types'][cv]; else{ if($.vv(def[cv])) est=def[cv]; else est='Indefinido'; } return $('<div class="other-title vcenter">'+(full?est:cv)+'</div>').attr('title',est).attr('data-originaldata',cv).prop('outerHTML'); }; $.fn.fmatter.estado.unformat=$.unformatCellData;
$.fn.fmatter.title=function(cv,opts,cObjt){ var f=$.ocf(opts); if(!$.vv(cv)||cv===''||(!$.isFunc(f['title'])&&!$.isText(f['title']))) return ''; var s=$('<div class="other-title vcenter"></div>'); s.attr('title',$.funcOrVal(f['title'],cObjt,'',true)).html(cv); return s.prop('outerHTML'); }; $.fn.fmatter.title.unformat=$.unformatCellHtml;
$.fn.fmatter.gridButton=function(cv,opts,cObjt){ var o=$.cloneCO(opts,cObjt); var f=$.ocf(opts); if(!$.vv(f['action'])) return '';  var con=$.getFunction(f['conditional']); return (!$.isFunc(con)||con(o))?$.getGridButton(f['action'],$.funcOrVal(f['data'],o,o,$.isText(f['data'])),f['title'],f['icon'],f['text'],f['type'],f['size'],f['attr']):$.funcOrVal(f['caseFalse'],o,''); }; $.fn.fmatter.gridButton.unformat=$.unformatCellHtml;
$.cloneCO=function(op,co){ if(!$.vv(op.idName)) return co; var o=$.cloneData(co); if(!$.vv(o[op.idName]))o[op.idName]=op.rowId; return o; };
$.fieldHeader=function (v, f, rc){ return v||(rc[f]||"Undefined"); };
// FUNCIONES GENERALES JQUERY
$.fn.getDataForm=function(tipo,all){ if(!this.length) return {}; if(this[0].tagName.toUpperCase()!=='FORM') return this.getDataForced(tipo,all); if($.isBool(tipo)) all=tipo; all=($.isBool(all)?all:true); var data=this.serializeObject(); var aux={},del=[]; this.find('input[type=checkbox]:not(:checked),input:disabled,textarea:disabled,select:disabled,span.databind').each(function(){ var t=$(this), n=t.attr('name'), p=t.attr('type'); if(t.prop('tagName')==='SPAN'){ if(!$.isUnd(n)&&$.isUnd(data[n])) data[n]=(t.hasClass('isMoney')||t.hasClass('isNumber'))?''+$.numUnformat(t.text().trim(),'number'):t.text().trim(); }else if(!$.isUnd(n)&&$.isUnd(data[n])){ if($.isUnd(aux[n])){ if(!$.isUnd(p)&&p.toUpperCase()==='CHECKBOX'){ if(t.is(':checked')){ if(all) aux[n]=this.value; }else{ if(!$.isUnd(t.attr('offval'))){ aux[this.name]=t.attr('offval'); }else { if(all) aux[n]=''; } } }else{ if(all) aux[n]=t.val(); } } }else{ del.push(this.name); } } ); $.each(del,function(i,v){ delete aux[v]; }); $.extend(true,data,aux); this.find('select.getData').each(function(){ var sNa=$(this).attr('name'); if($.vv(sNa)&&$.vv(data[sNa])){ var tos=$(this).find('option:selected'), obD=$.extend({[sNa+'_Txt']:tos.text()},tos.data()); if($(this).hasClass('ins')) $.extend(data,obD); else data[sNa+'_Data']=obD; } }); if($.isText(tipo)) data[tipo]=true; return data; };
$.fn.getDataForced=function(tipo,all){ if(!this.length) return {}; if(this[0].tagName.toUpperCase()==='FORM') return this.getDataForm(tipo,all); var f=$('<form>'); f.append(this.clone(true)); var fo=f.find('form'); if(fo.length>0) fo.each(function(){ $(this).replaceWith($("<div/>").append($(this).children())); }); var d=f.getDataForm(tipo,all); f.empty().remove(); return d; };
$.fn.getData=function(tipo,all){ if(!this.length) return {}; var data={}; this.each(function(){ $.extend(data,$(this).getDataForm(tipo,all)); }); return data; };
$.fn.setData=function(data,reset,meta){ if(!$.vv(data)) data={}; if(!$.isObj(data)) return this; var fields=this.find(":input,span.form-control,span.input-group-addon,p.form-control-static,span.databind");  if($.isText(reset)){meta=reset;}else{meta=($.vv(meta)?meta:'');} reset=($.isBool(reset)?reset:true); 
	$.each(fields,function(){ var $this=$(this),name=(meta===''?$this.attr('name'):$this.data(meta)),type=$this.attr('type'),trigger=($.vv($this.data('trigger'))||$this.hasClass("datatrigger")),keyup=($.vv($this.data('keyup'))||$this.hasClass("datakeyup"));
		if($.vv(name)) 
			if(type!=='radio'&&type!=='checkbox'){ if($this.is('select')&&$.vv(data[name+'_html'])) $this.html(data[name+'_html']);  if($.vv(data[name])){ if($this.is(':input')){ var aux=$this.val(); $this.val(data[name]); if($this.is('input[type=text]')&&keyup) $this.trigger('keyup'); if(trigger&&aux!==data[name]) $this.trigger('change'); if($this.hasClass('clearable')) $this[data[name]!==''?'addClass':'removeClass']('x'); }else $this.html($.isNum(data[name])&&($this.hasClass('isMoney')||$this.hasClass('isNumber'))?$.numFormat(data[name],'number'):data[name]); if($this.hasClass("datatitle")) $this.attr('title',data[name].trim()); }else{ if(reset){if($this.is(':input')){ var aux=$this.val(); $this.val(''); if($this.is('input[type=text]')&&keyup) $this.trigger('keyup'); if(trigger&&aux!=='') $this.trigger('change'); if($this.hasClass('clearable')) $this.removeClass('x');  }else $this.html('');} } }
			else{ var aux=$this.prop("checked"); if(type==='radio'&&$.vv(data[name])){ if($this.val()===data[name]){ $this.prop("checked",true); }else{ if(reset) $this.prop("checked",false);} }		
			if(type==='checkbox'&&$.vv(data[name])){ if($.isArray(data[name])){ if(!data[name].length){ if(reset)$this.prop("checked",false);} $.each(data[name],function(i,v){ if($this.val()===v){$this.prop("checked",true);return false;}else $this.prop("checked",false); }); }else{if($this.val()===data[name])$this.prop("checked",true); else $this.prop("checked",false);}} if(trigger&&aux!==$this.prop("checked")) $this.trigger('change'); }	 		
	}); return this;
}; 
$.getUrlSaveJson=function(str){ str=str||''; return ($.vv(UrlSaveJson)?UrlSaveJson:'/')+str; };
$.getDataJson=function(url,data,success,error,fail,allways,method){ var s=function(r){ var a; if($.vv(success)) a=success(r); return $.vv(a)?a:false; }; $.saveDataJson(url,data,s,error,fail,allways,method||'get'); };
$.postDataJson=function(url,data,success,error,fail,allways,method){ var s=function(r){ var a; if($.vv(success)) a=success(r); return $.vv(a)?a:false; }; $.saveDataJson(url,data,s,error,fail,allways,method||'post'); };
$.saveDataJson=function(url,data,success,error,fail,allways,method){ var jsonCall=(method==='GET'||method==='get'?$.get:$.post),msg,grid=($.isObj(url)?url:null),l=!$.vv(grid);  if(l) $('#loader').show(); else grid.jqGrid("progressBar",{method:"show",loadtype:'enable',htmlcontent:'Cargando...'}); var msg; url=(grid===null?(url||$.getUrlSaveJson()):grid[0].p.url);	
	jsonCall(url,data, function(response){ 
		if(response['message']===''||!$.vv(response['message'])) if($.toBool(response['success'])===true) response['message']='La acci&oacute;n se ha realizado con &Eacute;xito!'; else response['message']='No se ha podido realizar la acci&oacute;n!'; 	
		if($.toBool(response['success'])===true){ if($.vv(success)) msg=success(response); }else{ if($.vv(error)) msg=error(response); } if(msg!==false) $.alert(response['message'],null,($.toBool(response['success'])===true?'ok':'remove'));
	},'json').fail(function(e){ if(!$.vv(fail)) $.alert(); else{ msg=fail(e); if($.isText(msg)) $alert(msg); else if(msg!==false) $.alert();} }).always(function(){ if(l)$("#loader").fadeOut("slow"); else grid.jqGrid("progressBar",{method:"hide",loadtype:'enable'}); if($.vv(allways)) allways();});  
}; 
$.fn.getValidationJson=function(url,data,action,fail,allways){ var msg, $this=this, url=(url||$.getUrlSaveJson()); $this.fieldValid().addClass('fa fa-spin fa-spinner blue'); $.get(url,data, function(response){ if(response['success']==='') $this.fieldValid(); else $this.fieldValid(!$.toBool(response['success'])?response['state']||false:true, $.toBool(response['success'])?null:response['message']); if($.vv(action)) action(response);  },'json').fail(function(e){ if($.vv(fail)) msg=fail(e); $this.fieldValid(false,'Fallo la conexion.'); }).always(function(){ if($.vv(allways)) allways(); }); };
$.fn.fieldValid=function(state,message){ var next=this.next(),i=next.show().find('i'); if(!$.vv(next.data('title'))){ next.data('title',$.vv(next.attr('title'))?next.attr('title'):''); next.data('class',i.attr('class')); } next.removeClass('alert-danger alert-warning'); i.removeAttr('class'); if(state==='return'){ next.attr('title',next.data('title')); return i.addClass(next.data('class')); } if(state==='hide'){ next.hide(); state=''; }  i.addClass((!$.isBool(state)?(state==='warning'?$.createIcon('warning-sign orange',true):''):(state?$.createIcon('ok green',true):$.createIcon('remove red',true)))); next.removeAttr('title'); if($.isText(message)) next.attr('title',message); if(state==='warning')next.addClass('alert-warning'); if(state===false)next.addClass('alert-danger');  return i; };
$.fn.Search=function(form,tipo){ this.setGridParam({postData:null}); var a=$.isObj(form),jform=a?form:$(form); if(a){ if($.vv(tipo))jform[tipo]=true; }else jform.effect("highlight",{},500);  this.jqGrid('setGridParam',{datatype:'json',postData:a?jform:jform.getData(tipo)}).gridUpdate(); };
$.fn.SearchOrDialog=function(form,tipo,dialog,callback){
	var postdata=$(form).getData(tipo),jgrid=this;postdata['page']=1;postdata['rows']=2;
	$(form).effect("highlight",{},500);
	$.get(this[0].p.url,postdata, function(response){		
		if(parseInt(response['records'])===1){callback(response['rows'][0]);}
		else{jgrid.Search(form,tipo);$(dialog).dialog("open");}
	},'json');	
};
$.fn.SearchOrDialogArray=function(array,tipo,dialog,callback){
	var postdata=array,jgrid=this;postdata['page']=1;postdata['rows']=2;postdata[tipo]=true;
	$(form).effect("highlight",{},500);
	$.get(this[0].p.url,postdata, function(response){		
		if(parseInt(response['records'])===1){callback(response['rows'][0]);}
		else{jgrid.Search(form,tipo);$(dialog).dialog("open");}
	},'json');	
};
$.fn.moveComp=function(comp){$('#loader').show();this.hide();$(comp).show();$('#loader').fadeOut('slow');return $(comp);};
$.fn.dateLimits=function(minDate,maxDate){ if($.vv(minDate))this.datepicker( "option","minDate",minDate ); if($.vv(maxDate))this.datepicker("option","maxDate",maxDate ); return this;};
$.fn.formSubmit=function(){ this.removeAttr('novalidate'); $('<input type=\'submit\' />').hide().appendTo(this).click().remove();return this;};
$.fn.createChosen=function (clase,options){ // Requiere jquery.chosen
    clase=clase||'input-sm';options=$.extend({width:"100%"},(options||{}));
	this.each(function(i,obj){ var tab=$(obj).attr('tabindex')||''; /*if(tab!=='') $(obj).removeAttr('tabindex');*/ if(!$.vv(options['template'])) $(this).chosen(options); else $(this).chosenDesc(options); var id=$(obj).attr('id')||''; if(id!==''){$("#"+id+"_chosen").addClass('bs-chosen').find('.chosen-single').addClass('form-control '+clase);$("#"+id+"_chosen").find(".chosen-search").find('input').addClass('text');  if(tab!==''){ $("#"+id+"_chosen").attr('tabindex',tab).on('focus',function(){  $(obj).trigger('chosen:open.chosen'); });  } } }); return this;
};
$.fn.tableRemoveColumn=function(columnIndex,remove){ if(!$.vv(columnIndex))return this; remove=$.vv(remove)?remove:true; var index=[];
	if($.isArray(columnIndex)){index=columnIndex;index.sort(function(a, b){return b-a;});}else{index=[columnIndex];}	
	for(var i=0;i<index.length;i++){		
		if(remove){this.find("tbody").find("tr").find("td:eq("+index[i]+")").remove();this.find("thead").find("tr").find("th:eq("+index[i]+")").remove();}else{this.find("tbody").find("tr").find("td:eq("+index[i]+")").hide();this.find("thead").find("tr").find("th:eq("+index[i]+")").hide();} 
	}return this;
};
$.fn.tableRemoveRow=function(rowIndex,remove){ if(!$.vv(rowIndex))return this; remove=$.vv(remove)?remove:true; var index=[]; if($.isArray(rowIndex)){index=rowIndex;index.sort(function(a, b){return b-a;});}else{index=[rowIndex];}	for(var i=0;i<index.length;i++){if(remove){this.find("tbody").find("tr:eq("+index[i]+")").remove();}else{this.find("tbody").find("tr:eq("+index[i]+")").hide();} }return this; };
$.imprimirUrl=function(url,css){ $('#loader').show(); $.get(url, {}, function(r){ var p1=/<body[^>]*>((.|[\n\r])*)<\/body>/im, p2=/<head[^>]*>((.|[\n\r])*)<\/head>/im, p3=/<style[^>]*>((.|[\n\r])[^"]*)<\/style>/gmi, b=r, body=p1.exec(b), s='', styles; if($.vv(body)){ var head=p2.exec(b); b=body[1]; if($.vv(head)) styles=head[1].match(p3); if($.isArray(styles)) $.each(styles,function(i,v){ s=s+v; }); } $('<div>'+s+b+'</div>').printElement({ pageTitle:'Reporte Exa', overrideElementCSS:[{ href:(css||'../../mascaras/model1/estilos/print.css'), media:'print'}]}); }).fail(function (){ $.alert(); }).always(function(){ $("#loader").fadeOut("slow"); }); }; // requiere printElement.js
// Requiere jquery.maskmoney.js
/*$.MoneySet=function(id){$(id).maskMoney({prefix:'$ ',thousands:',',decimal:'.',precision:4,affixesStay:true,allowNegative:true,allowZero:true});$(id).maskMoney('mask');}; $.MoneyUnSet=function(id){$(id).maskMoney('destroy');}*/
$.objColEqual=function(obj,col,val){ if(!$.isObj(obj)) return false; if($.isBool(val)) return($.toBool(obj[col])===val); else if(isNaN(val)) return (String(obj[col])===String(val)); else if(isNaN(obj[col])) return (String(obj[col])===String(val));  else return (obj[col]*1===val*1); };
$.arrayIsValidKey=function(array,columnName){ if((!$.isArray(array)|| !$.vv(columnName))||(array.length>0 && $.isUnd(array[0][columnName]))) return false; return true;}; 
$.arraySpliceWhere=function(array,columnName,value,all){ if($.isFunc(columnName)){ where=columnName; all=($.isBool(value)?value:true); for(var i=array.length-1;i>=0;i--) if(where(array[i])){ array.splice(i,1); if(!all) break;} return array; } if($.isUnd(all))all=true; if(!$.arrayIsValidKey(array,columnName)||$.isUnd(value)) return array;  for(var i=array.length-1;i>=0;i--) if($.objColEqual(array[i],columnName,value)){array.splice(i,1); if(!all) break;} return array; };
$.arraySpliceFields=function(array,columns){ if(!$.isArray(array)||!$.vv(columns)) return array; if($.isText(columns)) columns=[columns]; for(var j=0;j<array.length;j++){ $.each(columns,function(i,v){ delete array[j][v]; }); } return array; };
$.arrayExistsVal=function(array,columnName,value){ if(!$.arrayIsValidKey(array,columnName)||$.isUnd(value)) return false; for(var i=0;i<array.length;i++) if($.objColEqual(array[i],columnName,value)){return true;} return false; };
$.arrayCountVal=function(array,columnName,value){ if(!$.isFunc(columnName)) if(!$.arrayIsValidKey(array,columnName)||$.isUnd(value)) return 0; var cont=0; for(var i=0;i<array.length;i++) if($.isFunc(columnName)){ if(columnName(array[i])) cont++; }else if($.objColEqual(array[i],columnName,value)) cont++; return cont; };
$.arrayMaxVal=function(array,columnName,columnName2,value){ if(!$.arrayIsValidKey(array,columnName)) return; if((!$.isUnd(columnName2)&&!$.arrayIsValidKey(array,columnName2))||!$.isUnd(columnName2)&&$.isUnd(value)) return; var max=null,wh=(!$.isUnd(value)); for(var i=0;i<array.length;i++) if(wh){ if($.objColEqual(array[i],columnName2,value)&&!isNaN(array[i][columnName])) max=(max===null||(array[i][columnName]*1)>max?(array[i][columnName]*1):max); }else{ if(!isNaN(array[i][columnName])) max=(max===null||(array[i][columnName]*1)>max?(array[i][columnName]*1):max); } return max; };
$.arrayMinVal=function(array,columnName,columnName2,value){ if(!$.arrayIsValidKey(array,columnName)) return; if((!$.isUnd(columnName2)&&!$.arrayIsValidKey(array,columnName2))||!$.isUnd(columnName2)&&$.isUnd(value)) return; var min=null,wh=(!$.isUnd(value)); for(var i=0;i<array.length;i++) if(wh){ if($.objColEqual(array[i],columnName2,value)&&!isNaN(array[i][columnName])) min=(min===null||(array[i][columnName]*1)<min?(array[i][columnName]*1):min); }else{ if(!isNaN(array[i][columnName])) min=(min===null||(array[i][columnName]*1)<min?(array[i][columnName]*1):min); } return min; };
$.arraySumVal=function(array,columnName,columnName2,value){ if(!$.arrayIsValidKey(array,columnName)) return; if((!$.isUnd(columnName2)&&!$.arrayIsValidKey(array,columnName2))||!$.isUnd(columnName2)&&$.isUnd(value)) return; var sum=0,a=array,wh=(!$.isUnd(value)); for(var i=0;i<a.length;i++) if(!isNaN(a[i][columnName])) if(wh){ if($.objColEqual(a[i],columnName2,value)) sum+=(a[i][columnName]*1); }else{ sum+=(a[i][columnName]*1); } return sum; };
$.arrayGetWhere=function(array,where,index){  var iaux=($.isText(index)?true:($.isBool(index)?index:false)); var resp=new Array(); if(!$.isFunc(where)) return resp; if(!$.isArray(array)) return resp; if(iaux) index=($.isText(index)?index:'index');  for(var i=0;i<array.length;i++) if(where(array[i])){ if(iaux) array[i][index]=i; resp.push(array[i]);} return resp; };
$.arrayGetItems=function(array,columnName,value,index){  if(!$.arrayIsValidKey(array,columnName)||$.isUnd(value)) return new Array(); return $.arrayGetWhere(array,function(d){ return $.objColEqual(d,columnName,value); },($.isText(index)||$.isBool(index)?index:true)); };
$.arrayGetItem=function(array,columnName,value,index){ var items=$.arrayGetItems(array,columnName,value,index); if(items.length===1) return items[0]; else return null; };
$.arrayGetColumns=function(array,columns){ 
    var resp=new Array(); if(!$.isArray(array)) return resp; if($.isArray(columns)){for(var i=(columns.length-1);i>=0;i--){ if(!$.arrayIsValidKey(array,columns[i])) columns.splice(i,1); }}else{ if(!$.arrayIsValidKey(array,columns))return resp;} if(!columns.length) return resp;
	for(var j=0;j<array.length;j++){ var item={}; if($.isArray(columns)) $.each(columns,function(i,v){ item[v]=$.cloneData(array[j][v]); }); else item[columns]=$.cloneData(array[j][columns]); resp.push(item); } return resp;
};
$.createDatePickers= function(id){ if(!$.vv(id)) return; return $(id).createDatePickers(); };
$.fn.createDatePickers= function(opts){ opts=($.isObj(opts)?opts:{}); this.datepicker($.extend({changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1, hideMsg:true},opts)); if(opts['clean']!==true) this.datepicker("setDate", new Date()); if(opts['checkAvailability']===true) this.on('change',function(){ var $t=$(this),d=$t.val(), set=$t.data('datepicker').settings, min=set['minDate'], max=set['maxDate'], errmsg=''; $t.fieldValid(set['hideMsg']?'hide':'return'); if(d.length===10){ if(!$.dateValid(d)) errmsg='Ingrese una fecha v\u00E1lida!'; else{ if($.vv(min)&&min>d) errmsg='Esta Fecha no puede ser menor a '+min+'!';  if($.vv(max)&&max<d) errmsg='Esta Fecha no puede ser mayor a '+max+'!';  } if(errmsg!==''){ $t.val('').fieldValid('warning',errmsg); setTimeout(function(){ $t.focus(); },0);  } } }); return this; };
$.createDateRange= function(fromDate,toDate,sep){ if(!$.vv(fromDate)||!$.vv(toDate)) return; var today=new Date(); $( fromDate ).createDatePickers({ onClose:function(selectedDate){ $(toDate).datepicker("option","minDate",selectedDate); } }).datepicker("setDate", new Date(today.getTime() - ( ($.isNum(sep)?sep:30) * 24 * 3600 * 1000))); $( toDate ).createDatePickers({ onClose:function(selectedDate){ $(fromDate).datepicker("option","maxDate",selectedDate ); } }).datepicker("setDate", today);/*$(fromDate).datepicker( "option", "showAnim", "blind" );$(toDate).datepicker( "option", "showAnim", "blind" );*/};
$.createDialog=function(comp,height,width,noTitleStuff,action,icon){ return $(comp).createDialog({height:(height||300),width:(width||400),noTitleStuff:noTitleStuff,icon:icon},action); };
$.createIcon=function(str,justClass,attr,css,tag){ if(!$.vv(str)||str==='') return ''; tag=tag||'i'; var c=(!str.indexOf("ui-icon-")?'':(!str.indexOf("glyphicon")?'glyphicon ':(!str.indexOf("fa-")||!str.indexOf("fa fa-")?'fa ':'glyphicon glyphicon-')))+str; if(justClass===true) return c; var $t=$('<'+tag+($.isText(attr)?' '+attr:'')+($.isText(css)?' style="'+css+'"':'')+' class="'+c+'"/>'); if($.isObj(css)) $t.css(attr); return $t.prop('outerHTML'); };
$.fn.createDialog=function(options,action){ var $t=(!this.length)?$('<div id="'+this.selector.replace("#","")+'" style="display:none"><div>').appendTo('body'):this;  options=($.isObj(options)?options:{});
	var icon=options['icon'],noTitleStuff=($.vv(options['noTitleStuff'])?options['noTitleStuff']:true ),dClass=(noTitleStuff?"TitleStuff":"noTitleStuff"); ($.vv(options['dialogClass'])?options['dialogClass']+=' '+dClass:options['dialogClass']=dClass);
	var opts={            
		height: 300, width: 400, closeText:"Cerrar Ventana", position:{my: "center",at: "center",of: $('body')},
		show: {effect: "fade",duration: 500},hide: {effect: "fade",duration: 200},modal:true,autoOpen:false,resizable:false,
		close: function(){$($(this).parent()[0].nextSibling).unbind('click');if($.vv(action))action();},
		open: function(){ var dg=$(this); $('.ui-widget-overlay').bind('click', function (){ dg.dialog('close'); });}		
	};  $.extend(opts,options); $t.each(function(){ $(this).dialog(opts).parent().children(".ui-dialog-titlebar").prepend($.createIcon($.vv(icon)?icon:'paperclip')); }); 
	if(options['noBorder']===true) $t.addClass('ui-dialog-noborder'); if(options['noOverflow']===true) $t.addClass('ui-dialog-nooverflow'); if($.vv(options['extraClass'])) $t.addClass(options['extraClass']); return $t; 
};
$.createDialogConfirm=function(message,data,accion,cancel){	
	message=message||'Esta seguro que desea realizar esta <b>acci&oacute;n</b>.'; var dialog=$('<div title="CONFIRMAR ACCI&Oacute;N">'+'<div style="font-size:14px;"><center>'+message+'</center></div></div>');//$("body").append(dialog);		
	dialog.dialog({
	  dialogClass: 'dialog-confirm-test',closeText:"Cerrar y Cancelar",modal:true,autoOpen:true,resizable:false, position:{my: "center",at: "center",of: $('body')},
          buttons:[{text:"Aceptar", click:function(){$(this).dialog( "close" );if(data!==null){accion(data);}else{accion();}}, icons:{ primary: "ui-icon-check" }},{text: "Cancelar", click:function(){$(this).dialog( "close" );if($.vv(cancel))cancel();}, icons: { primary: "ui-icon-closethick" }}],
	  close: function(){$($(this).parent()[0].nextSibling).unbind('click'); $(this).remove();},
	  open: function(){ var dg=$(this); $(dg.parent()[0].nextSibling).bind('click', function (){ dg.dialog('close'); if($.vv(cancel))cancel(); });}	  	  
    }).parent().children(".ui-dialog-titlebar").prepend($.createIcon('question-sign'));	
};
$.fn.createWYSIWYG= function(opts){ /* Requiere summernote.js */
	var o=$.extend({lang:'es-ES',height:100,placeholder:'Ingrese Texto..',toolbar:[['style',['bold','italic','underline','clear']],['fontsize',['fontsize']],['color',['color']],['para',['ul', 'ol','paragraph']]],popover:{image:[],link:[],air:[]},disableResizeEditor:true},($.isObj(opts)?opts:{}));
	this.each(function(){ $(this).summernote(o);$(this.nextSibling).find('.note-toolbar').find('.btn.btn-default').removeClass('btn-sm').addClass('btn-xs');$(this.nextSibling).find('.note-statusbar').hide(); }); return this;
};
$.fn.createTabs=function(options,loader,grids){ loader=($.vv(loader)?loader:true);grids=($.vv(grids)?grids:true);var opts={cache:false,beforeActivate: function( event, ui ){ if(loader) $("#loader").show(); },activate: function( event, ui ){ if(grids) ui.newPanel.updateGridsSizes(); if(loader) $("#loader").fadeOut("slow"); }};if($.isObj(options)) $.extend(true,opts,options); this.tabs(opts); return this; };
$.fn.createFormSearch=function(form){ if(!$.vv(form)) form={}; if(!$.isObj(form)||!this.length) return this; var $this, name=this.attr('id'),id=name.replace("Form",""),label=($.vv(form['label'])?form['label']:'B&uacute;squeda'),txtF=($.vv(form['text'])?form['text']:'search'),optR=($.vv(form['radio'])?form['radio']:'op_opciones'),title=($.vv(form['title'])?form['title']:'Persona'),opts=(($.vv(form['options']) && $.isArray(form['options']))?form['options']:[{label:'&nbsp;&nbsp;Apellido/Nombre&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&eacute;dula/R.U.C&nbsp;&nbsp;',value:'c'}]),optts='';
	$.each(opts,function(i,v){ optts=optts+'<input id="'+id+'Rdio'+i+'" name="'+optR+'" type="radio" value="'+v['value']+'" '+(i===0?'checked=""':'')+' onclick="setfocus(this.form.'+txtF+')" alt="" /><label for="'+id+'Rdio'+i+'">'+v['label']+'</label>'; });
	if(!this.find('fieldset').length) this.append('<fieldset class="exa-fieldset"><legend class="Titulos2">Filtros</legend></fieldset>'); $this=$((this.find('fieldset'))[this.find('fieldset').length-1]); 
	$this.append((optts.length>0?'<div class="form-group form-group-options"><label class="col-xs-2 control-label label-xs">Filtrar Por:</label><div class="col-xs-5 radioset">'+optts+'</div></div>':'')+
		'<div class="form-group form-group-search"><label class="col-xs-2 control-label">'+label+':</label><div class="col-xs-7"><div class="input-group"><input name="'+txtF+'" onkeydown="if(event.keyCode===13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese '+label+'..." autofocus  class="form-control input-sm clearable submit"/><span class="input-group-btn form-group-search-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar '+title+'" tabindex="-1" >'+$.createIcon('search')+'</span> <span>Buscar</span></button></span></div></div></div>'+
		'<input type="text" style="display:none" tabindex="-1" />'); return this.addClass('form-horizontal normal'); 
};
$.fn.createGrid=function(options,local,pager,pgoptions){ 
	var t=this; local=($.vv(local)?local:false);if($.vv(pager)) pager='#'+pager.replace("#",""); var select=($.isBool(options['selectGridRows'])?options['selectGridRows']:true), pglocal=(local?{pgbuttons:false,pgtext:null}:{}),width=t.parent().actual('width'); 
	var opts={mtype: "GET", datatype: (local?'local':'json'), regional:'es', autowidth:false, width:width, shrinkToFit:true, hidegrid:false,responsive:true,cmTemplate: {sortable:false},gridview:true, rownumbers:true, viewrecords:true, altRows:true, altclass:"myAltRowClass",rowNum:(local?10000000:250),height:(local?150:200),pager:($.vv(pager)?pager:''),tabIndex:'-1', bindKeys:!select?false:true, onSelectRow:!select?function(id){ $(this).resetSelection(); $(this).find('tr#'+id).attr('tabindex','-1'); }:null/*,ajaxRowOptions: { async: true },*/};	$.extend(opts,pglocal); if($.isObj(options)) $.extend(opts,options); 
	if(!$.isUnd(opts['totalCols'])&&opts['footerrow']){ t.on('jqGridAfterLoadComplete',function(){ if(opts['totalPage']&&opts['userDataOnFooter']){ var dF=t[0].grid.sDiv, nF=$(dF).find("tr.myfootrow").remove(), oF=$(dF).find("tr.footrow:not(.myfootrow)"); nF=oF.clone(); nF.addClass("myfootrow").children("td").each(function(){ this.style.width=""; }).end().insertAfter(oF); oF.find('td').html(''); } t.setGridSummary(opts['totalCols'],opts['totalDefault'],opts['totalNoFormat'],opts['totalFormatFunc']); }); }
	if(!$.isUnd(opts['stateCol'])&&!$.isUnd(opts['stateConfig'])){ t.on('jqGridAfterLoadComplete',function (ev,glc){ var r=($.isUnd(glc)||$.isUnd(glc['rows']))?t.getGridBatch():glc.rows; var cs=[]; $.each(opts['stateConfig'], function(i,c){ cs.push(c); }); t.find('tr td').removeClass(cs.join(' ')); $.each(r,function(i,v){ if(!$.isUnd(v[opts['stateCol']])&&!$.isUnd(opts['stateConfig'][v[opts['stateCol']]])){ var k=t.pk(); t.find('tr#'+v[k]+' td').addClass(opts['stateConfig'][v[opts['stateCol']]]); } }); }); }
	this.jqGrid(opts); if(opts['bindKeys']) t.jqGrid('bindKeys'); if($.vv(pager)){ var pgopts={ edit: false, add: false, del: false, search: false, refresh: (local?false:true), view: true, position: "left", cloneToTop: false }; if($.isObj(pgoptions)) $.extend(pgopts,pgoptions); t.navGrid(pager,pgopts); } 	
	return t;
};
$.closeDialogGrid=function(dialog){ return '<button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#'+dialog+'\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>'; };
$.fn.createDialogDetail=function(grid,dialog){ var $t,di=this.selector.replace("#",""),id=di.replace("Dialog",""); if(!$.vv(dialog)) dialog={}; if(!$.vv(grid)) grid={caption:''};
    var g=$.extend(grid,{responsive:false,height:!isNaN(grid['height'])?grid['height']:219, width:!isNaN(grid['width'])?grid['width']:593,caption:($.createIcon(dialog['icon'])+"&nbsp;")+(grid['caption']||'')+'&nbsp;'+$.closeDialogGrid(di)}); 
    $t=this.createDialog($.extend(dialog,{height:grid['height']+54+(grid['footerrow']===true?20:0),width:grid['width']+7,noTitleStuff:false,noBorder:true,noOverflow:true,extraClass:'noMargin'}));
    if(!$t.find('#'+id+'Grid').length) $t.append('<div class="condensed-header"><table id="'+id+'Grid"></table></div>'); $t.find('#'+id+'Grid').createGrid(g,true); return $t;
};
$.fn.createSearchDialog=function(gridOp,form,dialOp){
    var name=this.selector.replace("#",""), opD=$.extend({height:400,width:700,noTitleStuff:true,icon:'search',noOverflow:true},$.isObj(dialOp)?dialOp:{}), $t=this.createDialog(opD), fl=$t.find("form").length, id=name.replace("Dialog",""), container=$('<div class="condensed"><table id="'+id+'Grid"></table><div id="'+id+'GridPager"></div></div>'); if(fl===0) $t.append('<form></form>'); 
    var jform=$t.append(container).find("form").attr('id',id+"Form"); jform.find('fieldset').addClass('exa-fieldset').find('legend').addClass('Titulos2'); if($.vv(form)) jform.createFormSearch(form); jform.attr("action","javascript:$.Search('"+id+"')").append('<input type="text" style="display:none" tabindex="-1" />').find("input[name='search']").addClass("clearable").addClass("submit"); 
	var jgrid=$("#"+id+"Grid"), formHeight=jform.actual('outerHeight', { includeMargin:true }),gridHeight=opD['height']-formHeight-(opD['noTitleStuff']?95:65), op=$.extend({ url:'',width :(opD['width']-32),height:gridHeight,postData: jform.getData(id+"Ajax"),colModel: [], rowNum: 50, rowList:[10,20,50,100], autowidth:false, responsive:false },$.isObj(gridOp)?gridOp:{});  jgrid.createGrid(op,false,id+'GridPager',{view:false}); return $t;
};
$.createSearchDialog=function(name,colModel,height,width,title,grid,form){ var u; grid=$.isObj(grid)?grid:{}; grid['colModel']=colModel; return $('#'+name.replace("#","")).createSearchDialog(grid,form,{width:(width===null?u:width),height:height===null?u:height,noTitleStuff:title===null?u:title}); };
$.Search=function(id){ var jgrid=$("#"+id+"Grid"),jform=$("#"+id+"Form");	jgrid.setGridParam({postData: null}); jgrid.jqGrid('setGridParam',{datatype:'json',postData: jform.getData(id+"Ajax")}).gridUpdate(); jform.effect("highlight",{},500); };
$.SearchOrDialog=function(name,callback,otherForm){ if($.isObj(otherForm)){$.SearchOrDialogArray(name,callback,otherForm); return;}; name=name.replace("#","");var id=name.replace("Dialog",""); var jformtemp=$(($.vv(otherForm)?otherForm:"#"+id+"FormTemp")),postdata=$.extend(jformtemp.getData(id+"Ajax"),{page:1,rows:2}); jformtemp.effect("highlight",{},500); var field=jformtemp.find('.dialogSearch');  $.SearchOrDialogArray(name,callback,postdata,(field.length>0?field.attr('name'):null)); };
$.SearchOrDialogArray=function(name,callback,array,field){ field=field||'search'; name=name.replace("#","");var id=name.replace("Dialog",""); var jgrid=$("#"+id+"Grid"),postdata=$.extend($("#"+id+"Form").getData(id+"Ajax"),array,{page:1,rows:2}); $.get(jgrid[0].p.url,postdata, function(response){ if(parseInt(response['records'])===1){callback(response['rows'][0]);}else{$("#"+id+"Form").find("input[name='"+field+"']").addClass('x').val(postdata[field]);$.Search(id);$("#"+name).dialog("open");} },'json'); };
$.getDialogGrid=function(name){var id=name.replace("Dialog","");return $(id+"Grid");};
$.fn.getDialogGrid=function(){if(this.length===1){var id=this.attr('id').replace("Dialog","");return $('#'+id+"Grid");} return null;};	
$.alert=function(message,action,icon){
	icon=icon||($.vv(message)?'info-sign':'alert');message=message||'El Servidor ha fallado en responder!'; var dialog=$('<div title="MENSAJE DEL SISTEMA">'+'<div style="font-size:14px;"><center><b>'+message+'</b></center></div></div>');	//$("body").append(dialog);		
	dialog.dialog({
	  dialogClass: 'dialog-alert-test', closeText:"Cerrar Mensaje", modal: true,autoOpen: true,resizable: false,position:{my: "center",at: "center",of: $('body')}, buttons: [{text: "Aceptar",click:function(){$(this).dialog( "close" );}, icons:{ primary: "ui-icon-check" }}],
	  close: function(){$($(this).parent()[0].nextSibling).unbind('click');$(this).remove();if($.vv(action))action();},show: {effect: "fade",duration: 500},
	  open: function(){ var dg=$(this); $(dg.parent()[0].nextSibling).bind('click', function (){ dg.dialog('close'); });}	  
    }).parent().children(".ui-dialog-titlebar").prepend($.createIcon(icon));
};
$.fn.alertDiv= function(msg,animate,time){
	msg=msg||{header:'SISTEMA:',message:'No se pudo completar la acci&oacute;n.',type:2,max:1,unique:true}; msg.type=msg.type||2;var type='danger';msg.max=msg.max||1;msg.unique=($.vv(msg.unique)?msg.unique:true);time=time||5000;var id='alert_id_'+(Math.round(Math.random() * 99999)).toString(),timeExtra=550;animate=animate||false;
	mensaje=$('<div id="'+id+'" class="msg alert fade in '+(animate?"animated bounceIn":'')+'"><a href="#" class="close" data-dismiss="alert">'+$.createIcon('remove')+'</a>'+(!$.vv(msg.header)?'':'<strong>'+msg.header+'</strong>&nbsp;')+(!$.vv(msg.message)?'':msg.message)+'</div>');
	if(!isNaN(msg.type)){switch(msg.type){ case 0:type='success';break; case 1:type='info';break; }}else{type=msg.type;}
	mensaje.addClass( "alert-"+type );//mensaje.show( 'fade', {}, 500, null );	
	if(msg.unique){this.find('.msg').remove();this.append(mensaje);}
	else{		
		this.prepend(mensaje);var others=this.find('.msg');
		if(others.length>=msg.max)
			for(var i=(msg.max);i<others.length;i++)
				if(animate){$(others[i]).addClass( "fadeOutDown" ).css('position','absolute').removeClass('msg');var otherId=$(others[i]).attr('id');setTimeout(function(){if($('#'+otherId).length) $('#'+otherId).remove();},timeExtra);}
				else{$(others[i]).hide().remove();}
	}//mensaje.hide(); mensaje.show( 'fade', {}, 500, null );
	if(animate) setTimeout(function(){if($('#'+id).length){$('#'+id).addClass( "fadeOutDown" ).css('position','absolute').removeClass('msg');setTimeout(function(){$('#'+id).remove();},timeExtra);}},time);
	else {setTimeout(function(){if($('#'+id).length){$('#'+id).hide().remove();}},time);}
};
$.fn.alertMsg= function(message){
	var $div=this.parent().parent().find('.msgDiv'); if(!$div.length){$div=this.parent().parent().parent().find('.msgDiv');}	
	if(!$.vv(message)){ $div.find('.lblMsg').html(''); $div.find('.imgMsg').attr('src','../../mascaras/model1/imagenes/ok-s.gif'); }else{ $div.find('.lblMsg').html(message); $div.find('.imgMsg').attr('src','../../mascaras/model1/imagenes/32x32/cancel.gif'); }return this;
};
$.fn.clearMsg= function(){
	var $div=this.parent().parent().find('.msgDiv'); if(!$div.length){$div=this.parent().parent().parent().find('.msgDiv');}		
	$div.find('.lblMsg').html('');	$div.find('.imgMsg').removeAttr('src');	return this;
};
//FUNCIONES GENERALES
function setfocus(campo){if(campo){campo.focus();}}
function validar_injections(e){ 
    tecla=(document.all) ? e.keyCode : e.which; 
    if(tecla===13 || tecla===8) return true; 
    patron=/[A-Za-zï¿½ï¿½0-9\_\-\.\:\$\%\ \[\]\(\)]/;
    te=String.fromCharCode(tecla); 	
    return patron.test(te); 
}
function validar_numeric(e){ 
    tecla=(document.all) ? e.keyCode : e.which; 
    if(tecla===13 || tecla===8) return true; 
    patron =/^[0-9]*$/; // 4
    te=String.fromCharCode(tecla); 	
    return patron.test(te); 
}
function validar_decimal(e){ 
    tecla=(document.all) ? e.keyCode : e.which;  
    if(tecla===13 || tecla===8) return true; 
    patron =/^[0-9\.]*$/; // 4
    te=String.fromCharCode(tecla); 	
    return patron.test(te); 
}
var eko=function (k){if(this.hasOwnProperty(k)) return this[k]; else return 0;},eks=function (k){if(this.hasOwnProperty(k)) return this[k]; else return '';};function ek(a){a.eko=eko;a.eks=eks;};
Number.prototype.padLeft=function(n,str){ return (this<0?'-':'')+Array(n-String(Math.abs(this)).length+1).join(str||'0')+Math.abs(this); };
Number.prototype.padRight=function(n,str){ return (this<0?'-':'')+Math.abs(this)+Array(n-String(Math.abs(this)).length+1).join(str||'0'); };
String.prototype.upperFirstChar = function(){ return this.length>1?(this.substring(0,1).toUpperCase())+this.slice(1):(this.length===0?this:this.toUpperCase()); };
String.prototype.upperFirstWords = function(){ return this.length>1?this.toLowerCase().replace(/(^| )(\w)/g, function(s){ return s.toUpperCase() }):(this.length===0?this:this.toUpperCase()); };

// CREDITO DEBITO DIARIO
$.fn.updateGridDiario=function(){ 
    var total_haber=0,total_debe=0;
    var ids= this.jqGrid('getDataIDs');
    for(var i=0;i<ids.length;i++){
        var tip=this.jqGrid('getCell',ids[i],'Det_Tip'), monto;
        if(tip==='D'){monto=$('#'+ids[i]+'_Debe');total_debe +=Number(monto.val());}
        else {monto=$('#'+ids[i]+'_Haber');total_haber +=Number(monto.val());}
        if(!parseFloat(monto.val())){monto.val("");}
    };
    this.jqGrid("footerData", "set", {Glosa: "<div style='text-align:right;'>TOTALES:</div>", Debe:total_debe, Haber:total_haber});
    if(this.data('Diff')===true) $('#'+this.attr('id')+'_diferencia').val($.toFixed(Math.abs(total_debe-total_haber)));
    if($.isText(this.data('Com_Val'))) $(this.data('Com_Val')).val($.toFixed(total_debe));
};
$.fn.createInputDiario=function(element,tipo){
    var jgrid=this, rowId=$(element).closest('tr.jqgrow').attr('id'), tip=jgrid.jqGrid('getCell',rowId,'Det_Tip');  
    $(element).parent().removeAttr("title"); 
    if(tip===tipo){ 
        $(element).on('change', function(){ $(this).val($.toFixed($(this).val())); jgrid.updateGridDiario();}); 
        $(element).attr('onkeypress','return  validar_decimal(event)');
        if(parseFloat($(element).val())===0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    }else{ $(element).parent().html(''); };     
}; 
$.clearFooterDiario=function(grid,diff,com_val){ $(grid).data('Com_Val',com_val); $(grid).data('Diff',diff); grid=grid.replace("#","");
    if(diff===true) $("#gbox_"+grid+" #gview_"+grid+" div.ui-jqgrid-titlebar").append("<div class='pull-right'>Diferencia:&nbsp;<input id='"+grid+"_diferencia' value='0.00' style='width: 100px; color: black; margin-top: -2px; text-align:right;height: 18px;' tabindex='-1' readonly='' />&nbsp;<div>");
	var $footRow=$("#gbox_"+grid+" #gview_"+grid+" .ui-jqgrid-sdiv .footrow"); 
    $footRow.find('>td[aria-describedby]:not(:first-child)').css({"border-right-color":"transparent","background-color":"white"});    
	$footRow.find('>td[aria-describedby="'+grid+'_Debe"],>td[aria-describedby="'+grid+'_Haber"]').css({"border-right-color":"","background-color":''});
    $footRow.find('>td[aria-describedby="'+grid+'_Glosa"]').css("border-right-color", "");
};

/* Copyright 2012, Ben Lin
 * http://dreamerslab.com/blog/en/get-hidden-elements-width-and-height-with-jquery/
 * Version: 1.0.16
 */
(function(a){a.fn.addBack=a.fn.addBack||a.fn.andSelf;a.fn.extend({actual:function(b,l){if(!this[b]){throw'$.actual => The jQuery method "'+b+'" you called does not exist';}var f={absolute:false,clone:false,includeMargin:false};
var i=a.extend(f,l);var e=this.eq(0);var h,j;if(i.clone===true){h=function(){var m="position: absolute !important; top: -1000 !important; ";e=e.clone().attr("style",m).appendTo("body");};j=function(){e.remove();};}else{var g=[];var d="";var c;h=function(){c=e.parents().addBack().filter(":hidden");d+="visibility: hidden !important; display: block !important; ";
if(i.absolute===true){d+="position: absolute !important; ";}c.each(function(){var m=a(this);var n=m.attr("style");g.push(n);m.attr("style",n?n+";"+d:d);});};j=function(){c.each(function(m){var o=a(this);var n=g[m];if($.isUnd(n)){o.removeAttr("style");}else{o.attr("style",n);}});};}h();var k=/(outer)/.test(b)?e[b](i.includeMargin):e[b]();j();return k;}});})(jQuery);
// CLONE ADDON
!function(e){jQuery.fn.clone=function(){for(var t=e.apply(this,arguments),n="textarea",d="select",i=this.find(n).add(this.filter(n)),f=t.find(n).add(t.filter(n)),l=this.find(d).add(this.filter(d)),r=t.find(d).add(t.filter(d)),a=0,s=i.length;s>a;++a)$(f[a]).val($(i[a]).val());for(var a=0,s=l.length;s>a;++a)r[a].selectedIndex=l[a].selectedIndex;return t}}(jQuery.fn.clone);
// CLEARABLE INPUT - Pone un boton X para limpiar un campo de busqueda
jQuery(function($){ function tog(v){return v?'addClass':'removeClass';} 
  $(document).on('input', '.clearable', function(){$(this)[tog(this.value)]('x');})
  .on('mousemove', '.x', function(e){$(this)[tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)]('onX');})
  .on('touchstart click', '.onX', function(ev){ev.preventDefault();$(this).removeClass('x onX').val('').trigger('keyup').trigger('change').trigger('clearable'); if($(this).hasClass("submit")){this.form.submit();}});
});
/*
$.topZIndex=function (selector){return Math.max(0, Math.max.apply(null, $.map(((selector || "*")==="*")? $.makeArray(document.getElementsByTagName("*")) : $(selector),function (v){return parseFloat($(v).css("z-index")) || null;})));};//Returns the highest (top-most) zIndex in the document(minimum value returned: 0)	
$.fn.topZIndex=function(opt){ if(this.length===0) return this; opt=$.extend({increment: 1}, opt); var zmax=$.topZIndex(opt.selector), inc=opt.increment;return this.each(function (){ this.style.zIndex=(zmax += inc); });}; //Increments the CSS z-index of each element in the matched set to a value larger than the highest current zIndex in the document.
$.fn.topZItem=function(){ var itemMax; this.each(function(){ var index_current=parseInt($(this).css("zIndex"), 10),index_highest=($.vv(itemMax)?parseInt($(itemMax).css("zIndex"), 10):0); if(index_current >index_highest){itemMax=this;} }); return $(itemMax);}; // devuelve el item con mas alto zindex	
*/
 // Convierte un form en un objeto array
$.fn.serializeObject=function(){ var o={},a=this.serializeArray(); $.each(a,function(){ if(!$.isUnd(o[this.name])){ if(!o[this.name].push){o[this.name]=[o[this.name]];} o[this.name].push(this.value||'');}else{o[this.name]=this.value||'';} }); return o; };
// TOGGLE ATTRIBUTE CSS
$.fn.toggleAttr=function(attr){ var $this=$(this); if(!$.vv(attr)) return $this; $this.each(function(){ var $t=$(this); $t.attr(attr)?$t.removeAttr(attr):$t.attr(attr, attr); }); return $this;};
$.fn.toggleStyles=function(css,style1,style2){if(!$.vv(css)||!$.vv(style1)||!$.vv(style2)) return this; var $this=$(this), cssStyle={}; ($this.css(css)===style1)?cssStyle[css]=style2:cssStyle[css]=style1; $this.css(cssStyle); return $this;};
$.fn.toggleCss=function(css,style){return $(this).toggleStyles(css,style,'');};

$.isset=function(name){ if(!$.isText(name)) return false; try{eval(name);}catch(e){ if(e instanceof ReferenceError) return false; } return true; };
$.isEmpty=function(v){ if(!arguments.length) throw 'No Argument!'; return (!$.vv(v))||($.isBool(v)&&v===false)||($.isText(v)&&v.trim()==='')||($.isArray(v)&&v.length===0)||($.isObj(v)&&Object.keys(v).length==0)||($.isNum(v)&&v===0); };
$.ifEmpty=function(v,d){ return $.isEmpty(v)?d||null:v; };
$.ifvv=function(v,d){ return $.vv(v)?v:d||null; };
$.isUnd=function(v){ return v===undefined; };
$.varValid=$.vv=function(v){return (v!==null && !$.isUnd(v));};
$.isBoolean=$.isBool=function(v){return typeof v==='boolean';};
$.isString=$.isText=function(v){return typeof v==='string';};
$.isNum=$.isNumeric; $.isFunc=$.isFunction;
$.isObject=$.isObj=function(v){return $.vv(v)&&!$.isArray(v)&&typeof v==='object';};
$.dateValid=function(fecha){ if(!$.isText(fecha)||fecha.length!==10) return false; var f=/^(\d{4})[-\/](\d{2})[-\/](\d{2})$/.exec(fecha); if(f===null) return false; var d=f[3], m=f[2], y=f[1]; return m>0 && m<13 && y>1899 && y<32768 && d>0 && d<=(new Date(y,m,0)).getDate(); };
$.round=function(val,dec){ dec=$.vv(dec)?dec:2; if(!$.vv(val)||isNaN(val)||isNaN(dec)) return null; return (Math.round(val*Math.pow(10,dec))/Math.pow(10,dec)); };
$.toFixed=function(val,dec){ dec=$.vv(dec)?dec:2; var n=$.round(val,dec); return ($.vv(n)?n.toFixed(dec):null); };
$.strHtmlToStr=function(v,all){ if(!$.vv(v)) return ''; all=($.isBool(all)?all:false); var $o=$('<span>'); if($.isText(v)){ if(all) return $o.html(v).text(); else  return $o.html(v).children().remove().end().text(); }else{ if(all) return $o.append(v.clone()).text(); else return $o.append(v.clone()).children().remove().end().text(); } };
$.jsonParser=function(v){if($.isArray(v)||$.isObj(v)){return JSON.stringify(v);}else{try{return JSON.parse(v);}catch(e){return v;}}};
$.cloneData =function(v){if(!$.vv(v)) return v; if($.isArray(v))return $.extend(true,[],v); if($.isObj(v))return $.extend(true,{},v); return v;};
$.toBool=function(val){ if(!$.vv(val))return false; if($.isBool(val))return val; var num=+val,b=false;if(!isNaN(num)) b=!!num; else switch(String(val).toLowerCase()){	/*case 'y': case 'yes': case 's': case 'si': b=true; break;*/ case 'n': case 'no': case 'f': case 'off': case 'apagado': b=false; break; default: b=!!String(val).toLowerCase().replace(!!0,'');}return b;};
$.getDateRegExp=function(regex){ var re='(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))'; return(regex===true?new RegExp(re):re); };
$.getHoraRegExp=function(){ return '([0-1]{1}[0-9]{1}|20|21|22|23):[0-5]{1}[0-9]{1}'; };
/* require jquer.validate */
$.clearValidate=function(){$.validator.prototype.showErrors=function(){}; $.validator.setDefaults({debug:false,onsubmit:false});};