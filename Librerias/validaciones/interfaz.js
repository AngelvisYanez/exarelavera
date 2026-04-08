// JavaScript Document
// <!-- INICIO J Q U E R Y    T E X T    B O X -->
// <!-- Este modal es utilizado para el TOOLTIP y TEXTBOX -->
document.write('<script type="text/javascript" src="../../Librerias/textbox/jquery.js" type="text/javascript"><\/script>');
// <!-- FIN J Q U E R Y    T E X T    B O X -->

// <!-- INICIO J Q U E R Y    FIXED-HEADER-TABLE -->
document.write('<script type="text/javascript" src="../../Librerias/fixed-header-table/lib/jquery.min.js"><\/script>');
document.write('<script type="text/javascript" src="../../Librerias/fixed-header-table/lib/jquery.fixedheadertable.js"><\/script>');    
document.write('<script type="text/javascript" src="../../Librerias/fixed-header-table/lib/fixed.header.inc.js?x=2"><\/script>');  
// <!-- FIN J Q U E R Y    FIXED-HEADER-TABLE -->

// <!-- INICIO J Q U E R Y    T O O L T I P -->
document.write('<script type="text/javascript" src="../../Librerias/tooltip/jquery.tooltip.js" type="text/javascript"><\/script>');
// <!-- FIN J Q U E R Y    T O O L T I P -->

// <!-- INICIO J Q U E R Y    L O A D E R -->
document.write('<script type="text/javascript" src="../../Librerias/loader/jquery.loader.js" type="text/javascript"><\/script>');
document.write('<div id="loader"></div>');
// <!-- FIN J Q U E R Y    T O O L T I P -->
document.write('<script type="text/javascript">');
document.write('$.getCookie=function(cname){ var na=cname+"=",dc=decodeURIComponent(document.cookie),ca=dc.split(\';\');for(var i=0;i<ca.length;i++){ var c=ca[i]; while(c.charAt(0)===\' \'){c=c.substring(1);} if(c.indexOf(na)===0){return c.substring(na.length, c.length);} } return ""; };');
document.write('$.removeCookie=function(key,value){ var t = new Date();	t.setMilliseconds(t.getMilliseconds() + -1 * 864e+5); document.cookie=[encodeURIComponent(key),\'=\',String(value),\'; expires=\' + t.toUTCString()].join(\'\'); };');
document.write('$.isUnd=function(v){ return v===undefined; };');
document.write('$.varValid=$.vv=function(v){return (v!==null && !$.isUnd(v));};');
document.write('$.isBoolean=$.isBool=function(v){return typeof v===\'boolean\';};');
document.write('$.isEmpty=function(v){ if(!arguments.length) throw \'No Argument!\'; return (!$.vv(v))||($.isBool(v)&&v===false)||($.isText(v)&&v.trim()===\'\')||($.isArray(v)&&v.length===0)||($.isObj(v)&&Object.keys(v).length==0)||(!$.isNaN(v)&&v===0); };');
document.write('$.isString=$.isText=function(v){return typeof v===\'string\';};');
document.write('$.isObject=$.isObj=function(v){return $.vv(v)&&!$.isArray(v)&&typeof v===\'object\';};');
document.write('$.jsonParser=function(v){if($.isArray(v)||$.isObj(v)){return JSON.stringify(v);}else{try{return JSON.parse(v);}catch(e){return v;}}};');
// document.write('$(document).ready(function(){ socketVentanas=new SocketVentanas(); if(!$.isEmpty(Ses_Emp_Cod)) socketVentanas.connectDefault(); });');
document.write('</script>');
// document.write('<script src="../../framework/php/ventanasSocket/socketExaVentanas.js"></script>');