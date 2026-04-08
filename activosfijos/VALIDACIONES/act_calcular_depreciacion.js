/* 
 Por: José Ambuludí
 Sección para calcuular la depreciación anual y mensual de un activo
 Se debe considerar lo siguiente:
    * Existen dos formas de depreciar:
        * Con días completos de cada mes, a excepción de febrero que siempre tendrá 28 días y 365 días del año.
        * Con 30 días de cada mes incluido Febrero y 360 días del año.   
 */

//La función calculo permite determinar el valor de depreciación diario y las fechas de inicio y fin de dpreciacion
//fecha: fecha de inicio de depreciación
//costo: es el costo del producto menos el valor residual
//porcentaje: en caso de que exista son los estipulados por el SRI
//vida_util: en caso de que existiese es la vida útil en años del activo
//valor_compra: valor neto por la compra del producto
//tipo: puede ser de dos tipos: 
        //DT=días tributarios 30 días para todos los meses con 360 días anuales
        //DM=días completos de cada uno de los meses con 365 días anuales
function calculo(fecha,costo,porcentaje,vida_util,valor_compra,tipo){
    var dep_anu=0,dep_dia=0,date='',newdate='',fecha_inicio='',fecha_fin='',dias_anio=0;
    var descomponer=fecha.split('-');var anio=descomponer[0];var mes=descomponer[1];var dia=descomponer[2];
    date = new Date(fecha);
    if(porcentaje>0){
        dep_anu=(costo*porcentaje)/100;
        vida_util=costo/dep_anu;
    }else{
        dep_anu=costo/vida_util;
        porcentaje=((dep_anu*100)/costo).toFixed(2);
    }
    if(tipo==='DT'){
        dias_anio=360;
        if(dia==='31'){
            newdate = new Date(date);
            newdate.setDate(newdate.getDate() + 2);
            fecha_inicio=newdate.getFullYear()+'-'+('0'+(newdate.getMonth() + 1)).slice(-2)+'-'+('0'+newdate.getDate()).slice(-2);
        }else{
            fecha_inicio=fecha;
        }
        if((dia==='01')&&(mes==='03')){
            anio=parseInt(anio)+parseInt(vida_util);
            fecha_fin=anio+'-02-30';
        }else{
            date.setFullYear(date.getFullYear() + parseInt(vida_util));
            fecha_fin=date.getFullYear()+'-'+('0'+(date.getMonth() + 1)).slice(-2)+'-'+('0'+(date.getDate())).slice(-2);
        }
        var descomponer=fecha_fin.split('-');
        if(descomponer[2]>30){fecha_fin=descomponer[0]+'-'+descomponer[1]+'-30';}
    }else{
        dias_anio=365;
        fecha_inicio=fecha;
        date.setFullYear(date.getFullYear() + parseInt(vida_util));
        fecha_fin=date.getFullYear()+'-'+('0'+(date.getMonth() + 1)).slice(-2)+'-'+('0'+(date.getDate())).slice(-2);
    }
    dep_dia=dep_anu/dias_anio;
    return depreciacion(fecha_inicio,fecha_fin,dep_dia,Math.floor(vida_util),valor_compra,tipo,porcentaje);
}
function depreciacion(fch_ini,fch_fin,dep_dia,vida_util,valor_compra,tipo,porcentaje){
    var mes_com=12,dep_anual=0,dias=0,dfi_mes=0,datos_anual=[],datos_mensual=[],dep_acum=0,val_libros=valor_compra,dep_men=0,fecha_fin='',fecha_inicio=fch_ini;
    var descomponer_fi=fch_ini.split('-');
    var descomponer_ff=fch_fin.split('-');
    var ani_ini=descomponer_fi[0];var mes_ini=descomponer_fi[1];var dia_ini=descomponer_fi[2];
    var ani_fin=descomponer_ff[0];var mes_fin=descomponer_ff[1];var dia_fin=descomponer_ff[2];
    for(var a=ani_ini;a<=ani_fin;a++){
        if(parseInt(a)===parseInt(ani_fin)){ mes_com=mes_fin; }
        for(var m=mes_ini;m<=mes_com;m++){
            m=('0'+m).slice(-2);
            fch_ini=a+'-'+m+'-'+dia_ini;
            if((parseInt(a)===parseInt(ani_fin))&&(parseInt(m)===parseInt(mes_fin))){ var num_dia=dia_fin; dfi_mes=dia_fin;}
            else{ dias=num_dias(fch_ini,tipo);num_dia=dias[0];dfi_mes=dias[1];}
            dep_men=num_dia*dep_dia;
            dep_acum=parseFloat(dep_acum)+parseFloat(dep_men);
            val_libros=val_libros-dep_men;
            fecha_fin=a+'-'+m+'-'+dfi_mes;
            datos_mensual.push({'anio':a,'fec_ini':fch_ini,'fec_fin':fecha_fin,'Val_Dep':dep_men.toFixed(2),'Dep_Acu':dep_acum.toFixed(2),'Val_Res':Math.abs(val_libros.toFixed(2))});
            dep_anual=parseFloat(dep_anual)+parseFloat(dep_men);
            dia_ini='01';
        }
        datos_anual.push({'periodo':a,'Val_Dep':dep_anual.toFixed(2),'Dep_Acu':dep_acum.toFixed(2),'Val_Res':Math.abs(val_libros.toFixed(2))});
        mes_ini=1;dep_anual=0;
    }
    return [datos_anual,datos_mensual,fecha_inicio,fecha_fin,vida_util,Math.abs(val_libros.toFixed(2)),porcentaje];
}
/*Función que devuelve el número de días que contiene un mes específico*/
function num_dias(fch_ini,tipo){
    var num_dia=0,dfi_mes=0;
    var descomponer_fi=fch_ini.split('-');
    var ani_ini=descomponer_fi[0];var mes_ini=descomponer_fi[1];var dia_ini=descomponer_fi[2];
    if(tipo==='DM'){
        dfi_mes = new Date(ani_ini || new Date().getFullYear(), mes_ini, 0).getDate();
        if (parseInt(mes_ini) === 02) {
            dfi_mes = 28;
        }
        num_dia=(dfi_mes-dia_ini)+1;
    }else{
        if(dia_ini==='01'){num_dia=30;}else{num_dia=(30-dia_ini)+1;}
        dfi_mes=30;
    }
    return [num_dia,dfi_mes];
}

/*Cálculo de depreciación mensual dentro del archivo act_alt_depreciacion_1.0.php
Parámetros que recibe:
fch_ini: fecha de inicio del mes a depreciar (2016-01-01)
dep_dia: valor de la depreciación diaria del activo
tipo: especifíca si es DT(Días tributarios) o DM(Días Mensuales)
fin_dep: valor que indica que se ha llegado a la fecha final de depreciación, contiene el día y no la fecha completa(01,02,13,25,30,etc)
*/
function dep_mensual(fch_ini,dep_dia,tipo,fin_dep){
    var descomponer_fi=fch_ini.split('-');
    var ani_fin=descomponer_fi[0];var mes_fin=descomponer_fi[1];
    var dias=num_dias(fch_ini,tipo);
    if(fin_dep>0){dias[0]=fin_dep;dias[1]=fin_dep;}
    var dep_men=dias[0]*dep_dia;
    var fch_fin=ani_fin+'-'+('0'+mes_fin).slice(-2)+'-'+('0'+dias[1]).slice(-2);
    return [dep_men,fch_fin];//Se retorna la depreciación mensual y la fecha final del mes
}
/*Cálculo de depreciación acumulada dentro del archivo act_alt_depreciacion_1.0.php
Parámetros que recibe:
fechas: array que contiene las fechas de los activos registradas dentro de la tabla activo_deprecia
dep_dia: valor de la depreciación diaria del activo
tipo: especifíca si es DT(Días tributarios) o DM(Días Mensuales)
 */
function dep_acumulada(fechas,dep_dia,tipo){
    var dep_acum=0,dias=0;
    for(var i=0;i<fechas.length;i++){
        dias=num_dias(fechas[i]['Acd_Fpd'],tipo);
        dep_acum=parseFloat(dias[0]*dep_dia)+parseFloat(dep_acum);
    }
    return dep_acum;//Se retorna la depreciación acumulada 
}


