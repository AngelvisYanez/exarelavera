/* COMPAT JQUERY 3: reemplazos nativos de APIs deprecated para no pasar por jQuery Migrate.
   Cargar DESPUÉS de jquery-3.7.1 + jquery-migrate y ANTES de bootstrap/jquery-ui/jqGrid. */
(function($){
    var trimRegex=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, class2type={}, ts=Object.prototype.toString;
    $.each('Boolean Number String Function Array Date RegExp Object Error Symbol'.split(' '),function(i,n){ class2type['[object '+n+']']=n.toLowerCase(); });
    function toType(v){ return v==null?String(v):(typeof v==='object'||typeof v==='function'?(class2type[ts.call(v)]||'object'):typeof v); }
    $.type=toType;
    $.isFunction=function(v){ return typeof v==='function'; };
    $.isArray=Array.isArray;
    $.isNumeric=function(v){ var t=toType(v); return (t==='number'||t==='string')&&!isNaN(v-parseFloat(v)); };
    $.trim=function(v){ return v==null?'':(''+v).replace(trimRegex,''); };
    $.fn.bind=function(types,data,fn){ return this.on(types,null,data,fn); };
    $.fn.unbind=function(types,fn){ return this.off(types,null,fn); };
    $.fn.delegate=function(selector,types,data,fn){ return this.on(types,selector,data,fn); };
    $.fn.undelegate=function(selector,types,fn){ return this.off(types,selector,fn); };
    $.fn.hover=function(fnOver,fnOut){ return this.on('mouseenter',fnOver).on('mouseleave',fnOut||fnOver); };
    $.each(('blur focus focusin focusout resize scroll click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup contextmenu').split(' '),function(i,name){
        $.fn[name]=function(data,fn){ return arguments.length>0?this.on(name,null,data,fn):this.trigger(name); };
    });
    if($.expr&&$.expr.pseudos) $.expr[':']=$.expr.pseudos;
}(jQuery));
