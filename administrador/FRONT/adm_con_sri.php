<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/config.php/register_globals.php'); 
require_once('../../Librerias/procedimientos/almacenados_standar.php');

function getAgent(){
    $user_agent    = array(
        "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; FDM)",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; Avant Browser [avantbrowser.com]; Hotbar 4.4.5.0)",
        "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en; rv:1.8.1.14) Gecko/20080409 Camino/1.6 (like Firefox/2.0.0.14)",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Version/3.1 Safari/525.13",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; NeosBrowser; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; es-ES; rv:1.8.1) Gecko/20061010 Firefox/2.0",
        "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36"	
    );  
    return $user_agent[rand(0, count($user_agent)-1)];
}
function isRuc($ruc_cons, $proxy=null){
    $link1="https://srienlinea.sri.gob.ec/sri-catastro-sujeto-servicio-internet/rest/ConsolidadoContribuyente/existePorNumeroRuc?numeroRuc=";
    try{ 
	$agent=getAgent();
        $request = curl_init(); 
        $timeOut = 0; 
        curl_setopt ($request, CURLOPT_URL, $link1.$ruc_cons); 
        curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt ($request, CURLOPT_USERAGENT, $agent); 
        curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);         
		if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
			curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
			if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
		}
        $response = curl_exec($request); 
        curl_close($request);
        return ($response===true || (is_string($response) &&  strtoupper($response)=="TRUE"))?true:false;
    } catch (Exception $ex) {
        return false;
    }     
}
if(isset($formPersona)){
    if($filtro=='d'){
        function consultarCedula($cedula, $timeOut = 0, $proxy=null){
            $data=array('success'=>true, 'message'=>null, 'data'=>null);	
            try{ 
                $request = curl_init(); 
                curl_setopt ($request, CURLOPT_URL, "https://srienlinea.sri.gob.ec/sri-registro-civil-servicio-internet/rest/DatosRegistroCivil/obtenerListaPorNombreCompleto?nombreCompleto=".urlencode(strtoupper($cedula))); 
                curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt ($request, CURLOPT_USERAGENT, getAgent()); 
                curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);         
                if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
                    curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
                    curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
                    if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
                }
                $response = curl_exec($request); 
                curl_close($request);
                $data['data'] = json_decode($response,true);
                if($data['data']==null) throw new Exception("La $cedula no existe o no se logro obtener los datos!");
                $max=20;
                if(count($data['data'])>1)
                    $data['data']=array_slice($data['data'], 0, $max);
                foreach ($data['data'] as &$v) {
                    $v=array('identificacion'=>$v['identificacion'],'nombreCompleto'=>$v['nombreCompleto']);
                } unset($v);
                if(count($data['data'])==1) $data['data']=$data['data'][0]; else if(count($data['data'])==$max) $data['data']['NOTA']="Puede existir mas informacion, reduzca la busqueda.";
            } catch (Exception $ex) {
                $data['success']=false;
                $data['message']=$ex->getMessage();
            }     
            utf8_encode_deep($data);
            return $data;
        }
    }else
    if($servidor=='s'){
        function consultarCedula($cedula, $timeOut = 0, $proxy=null){
            $data=array('success'=>true, 'message'=>null, 'data'=>null);	
            try{ 
                if(strlen($cedula)!=10) throw new Exception('La cedula debe tener 10 digitos!');
                $request = curl_init(); 
                curl_setopt ($request, CURLOPT_URL, "https://srienlinea.sri.gob.ec/sri-registro-civil-servicio-internet/rest/DatosRegistroCivil/obtenerPorNumeroIdentificacion?numeroIdentificacion=".$cedula); 
                curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt ($request, CURLOPT_USERAGENT, getAgent()); 
                curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);         
                if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
                    curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
                    curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
                    if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
                }
                $response = curl_exec($request); 
                curl_close($request);
                $data['data'] = json_decode($response,true);
                if($data['data']==null) throw new Exception("La $cedula no existe o no se logro obtener los datos!");
                //array_merge($data['data'],array('residencia'=>null,'domicilio'=>null,'numeroDomicilio'=>null));
            } catch (Exception $ex) {
                $data['success']=false;
                $data['message']=$ex->getMessage();
            }     
            utf8_encode_deep($data);
            return $data;
        }
    }else{
        function consultarCedula($cedula, $timeOut = 0, $proxy=null){
            $data=array('success'=>true, 'message'=>null, 'data'=>null);	
            try{ 
                if(strlen($cedula)!=10) throw new Exception('La cedula debe tener 10 digitos!');
                $post = array('tipo'=>'getDataWsRc','ci'=>$cedula, 'tp'=>'C', 'ise'=>'SI');
                $request = curl_init(); 
                curl_setopt ($request, CURLOPT_URL, "http://certificados.ministeriodegobierno.gob.ec/gestorcertificados/antecedentes/data.php"); 
                curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt ($request, CURLOPT_USERAGENT, getAgent()); 
                curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);
                curl_setopt ($request, CURLOPT_REFERER, 'http://certificados.ministeriodegobierno.gob.ec/gestorcertificados/antecedentes/');
                curl_setopt ($request, CURLOPT_POSTFIELDS, $post);
                if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
                    curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
                    curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
                    if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
                }
                $response = curl_exec($request); 
                curl_close($request);		
                $ced = json_decode($response,true);
                if($ced==null) throw new Exception('La cedula no existe o no se logro obtener los datos!');
                $ced=$ced[0];
                if(isset($ced['error']) && !empty($ced['error'])) throw new Exception("La $cedula no existe o no se logro obtener los datos!");
                $data['data']=array('identificacion' => $ced['identity'],'nombreCompleto' => $ced['name'],'genero' => $ced['genre'],'fechaNacimiento' => $ced['dob'],'estadoCivil' => $ced['civilstate'],'nacionalidad' => $ced['nationality'],'nombreCompletoConyuge' => null,'fechaDefuncion' => null,'numeroCedulaPadre' => null,'numeroCedulaMadre' => null,'residencia'=>$ced['residence'],'domicilio'=>$ced['streets'],'numeroDomicilio'=>$ced['homenumber']);
            } catch (Exception $ex) {
                $data['success']=false;
                $data['message']=$ex->getMessage();
            }   
            utf8_encode_deep($data);
            return $data;
        }
    }
    $data=(consultarCedula($search));
    if($data['success']){
        if($filtro=='c')
            $data['data']['posseeRuc']=(isRuc($search."001")?"SI":"NO");
        $data['html'] = "";
    }
    echo json_encode($data);
    exit();
}
if(isset($formRuc)){
    function captchaSri($proxy=null){
            $timeOut = 0; 
            $agent=getAgent();
            $captcha1="https://srienlinea.sri.gob.ec/sri-captcha-servicio-internet/captcha/start/1?r=f5tmg7sds4s";
            $captcha2="https://srienlinea.sri.gob.ec/sri-captcha-servicio-internet/rest/ValidacionCaptcha/validarCaptcha/";
            try{
                    $request = curl_init();
                    curl_setopt ($request, CURLOPT_URL,$captcha1); 
                    curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt ($request, CURLOPT_USERAGENT, $agent); 
                    curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);   
                    curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt ($request, CURLOPT_VERBOSE, 1);
                    curl_setopt ($request, CURLOPT_HEADER, 1);
                    //curl_setopt($request, CURLOPT_HEADERFUNCTION, 'callbackCookie');
                    if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
                            curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
                            curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
                            if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
                    }
                    $response = curl_exec($request); 
                    $header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
                    $header = substr($response, 0, $header_size);
                    $body = substr($response, $header_size);		
                    curl_close($request);
                    $capchaVal = json_decode($body,true);
                    if($capchaVal==null) throw new Exception('Error getCaptcha();!');
                    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
                    $cookies=(implode("; HttpOnly; secure,", $matches[1]));		

                    $request = curl_init();
                    curl_setopt ($request, CURLOPT_HTTPHEADER, array("Cookie: $cookies"));
                    curl_setopt ($request, CURLOPT_URL,"{$captcha2}{$capchaVal['values'][0]}?emitirToken=true"); 
                    curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
                    curl_setopt ($request, CURLOPT_USERAGENT, $agent); 
                    curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);  

                    if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
                            curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
                            curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
                            if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
                    }
                    $response = curl_exec($request); 
                    curl_close($request);

                    $token = json_decode($response,true);
                    if($token==null || isset($token['titulo'])) throw new Exception('Error getToken();!');
                    return $token['mensaje'];
            } catch (Exception $ex) {
            return null;
        } 
    }
    // $proxy=array('ip'=>'192.168.0.1', 'port'=>'3128', 'user'=>'usuario', 'password'=>'clave');
    function consultarRucSri($ruc_cons, $proxy=null){ 
        $timeOut = 0; 
        $agent=getAgent();
        $link2="https://srienlinea.sri.gob.ec/sri-catastro-sujeto-servicio-internet/rest/ConsolidadoContribuyente/obtenerPorNumerosRuc?&ruc=";
        $link3="https://srienlinea.sri.gob.ec/sri-catastro-sujeto-servicio-internet/rest/Establecimiento/consultarPorNumeroRuc?numeroRuc=";
        
        $ruc=array('success'=>true, 'message'=>null, 'isRuc'=>false, 'hasData'=>false);
        if(strlen($ruc_cons)==10) $ruc_cons.="001";
        if(strlen($ruc_cons)!=13){
            $ruc['success']=false;
            $ruc['message']="El RUC $ruc_cons debe constar de 13 Digitos! ";
        }else
        try{ 
            $ruc['isRuc']= isRuc($ruc_cons, $proxy);
            if($ruc['isRuc']){ 
                for($i=0;$i<3;$i++){
                $autorizacion=captchaSri($timeOut,$proxy);
                    if($autorizacion!=null) break;
                }
                if($autorizacion==null) throw new Exception('Error Captcha!');
                $request = curl_init();             
                curl_setopt ($request, CURLOPT_HTTPHEADER, array("Authorization: $autorizacion"));
                curl_setopt ($request, CURLOPT_URL, $link2.$ruc_cons); 
                curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt ($request, CURLOPT_USERAGENT, $agent); 
                curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut); 
                if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
                    curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
                    curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
                    if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
                }
                $response = curl_exec($request); 
                curl_close($request); 
                $aux = json_decode($response,true);
                if(is_array($aux)){
                    $ruc['hasData']=true;                
                    $ruc['obligadoContabilidad']=$aux[0]['obligado']=='S'?true:false;
                    $ruc['contribuyenteEspecial']=($aux[0]['claseContribuyente']!=null && strtoupper($aux[0]['claseContribuyente'])=='ESPECIAL')?true:false;
                    $ruc['tipo']=($aux[0]['estadoPersonaNatural']=='ACT'?'N':($aux[0]['estadoSociedad']=='ACT'?'J':null));
                    $ruc['tipoLong']=($aux[0]['estadoPersonaNatural']=='ACT'?'NATURAL':($aux[0]['estadoSociedad']=='ACT'?'JURIDICA':null));
                    $ruc['activo']=$aux[0]['estadoPersonaNatural']=='ACT'||$aux[0]['estadoSociedad']=='ACT'?true:false;
                    $ruc['rucData']=$aux[0];                    
                   
                    $request = curl_init();             
                    curl_setopt ($request, CURLOPT_HTTPHEADER, array("Authorization: $autorizacion"));
                    curl_setopt ($request, CURLOPT_URL, $link3.$ruc_cons); 
                    curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
                    curl_setopt ($request, CURLOPT_USERAGENT, $agent); 
                    curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut); 
                    if($proxy!=null){ //Si tiene salida a Internet por Proxy, debe poner ip y puerto
                        curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
                        curl_setopt ($request, CURLOPT_PROXY, "$proxy[ip]:$proxy[port]");
                        if(isset($proxy['user'])) curl_setopt ($request, CURLOPT_PROXYUSERPWD, "$proxy[user]:$proxy[password]"); 
                    }
                    $response = curl_exec($request); 
                    curl_close($request); 
                    $ruc['rucData']['Establecimientos'] = json_decode($response,true);                
                }
            }else{
                $ruc['message']="No es un ruc";
            }
        } catch (Exception $ex) {
            $ruc['success']=false;
            $ruc['message']=$ex->getMessage();
        } 
        utf8_encode_deep($ruc);
        return $ruc;
    }
    $data=(consultarRucSri($search));
    $data['html1']="ver";
    if($data['success'] && $data['isRuc'] && $data['hasData']){
        $data['html'] = "";
    }
    echo json_encode($data);
    exit();
}

$hoy = date("Y-m-d");
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo "Consulta de Rucs [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Consultas REGISTRO CIVIL / SRI</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-6">
                    <fieldset class="exa-fieldset ">                           
                        <legend class="Titulos2">Consultar Persona</legend>
                        <form id="formPersona" class="form-horizontal normal" action="javascript:getPersona();" method="post" >
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs" style="text-align: right;">Servidor:</label>  
                                <div class="col-sm-9 radioset" >
                                    <input id="op_opcS1" name="servidor" type="radio" value="r" onClick="" required>
                                    <label for="op_opcS1">&nbsp;&nbsp;Registro Civil&nbsp;&nbsp;&nbsp;</label>
                                    <input id="op_opcS2" name="servidor" type="radio" value="s" onClick="" required  checked>
                                    <label for="op_opcS2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;SRI&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs" style="text-align: right;">Filtro:</label>  
                                <div class="col-sm-9 radioset" >
                                    <input id="op_opc1" name="filtro" type="radio" value="d" onClick="" required>
                                    <label for="op_opc1">&nbsp;&nbsp;&nbsp;&nbsp;Apellidos&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                    <input id="op_opc2" name="filtro" type="radio" value="c" onClick="" required  checked>
                                    <label for="op_opc2">&nbsp;&nbsp;&nbsp;&nbsp;Cedula&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-sm required">Buscar:</label>  
                                
                                <div class="col-xs-9"> 
                                    <div class="input-group">                        
                                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm clearable submit"/>
                                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Persona"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                    </div>
                                </div>                                  
                            </div>
                        </form>
                    </fielset>    
                    <div id="resultadoPersona">

                    </div>
                </div>
                
                <div class="col-sm-6">
                    <fieldset class="exa-fieldset ">                           
                        <legend class="Titulos2">Consultar RUC en el SRI</legend>
                        <form id="formRuc" class="form-horizontal normal" action="javascript:getRuc();" method="post" >                            
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-sm required">Buscar:</label>  
                                
                                <div class="col-xs-5"> 
                                    <div class="input-group">                        
                                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="13" minlength="13" maxlength="13" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm clearable submit"/>
                                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Persona"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                    </div>
                                </div>                                  
                            </div>
                        </form>
                    </fielset>    
                    <div id="resultadoRuc">

                    </div>
                </div>
            </div> 
        </div>
    </div>
        
        
    <script type="text/javascript">
    $(function() {
        
    });
    function getPersona(){    
        $('#resultadoPersona').html("");
        $('#loader').show();
        $.getDataJson("",$('#formPersona').getData('formPersona'),function (r){
            $('#resultadoPersona').html(r.html);
        });
    }
    function getRuc(){    
        $('#resultadoRuc').html("");
        $('#loader').show();
        $.getDataJson("",$('#formRuc').getData('formRuc'),function (r){
            if(r.isRuc===false) return $.alert("No es un RUC valido!");
            if(r.hasData===false) return $.alert("No se logro optener los datos del RUC!");
            $('#resultadoRuc').html(r.html);
        });
    }   
    </script>
</BODY>
</HTML>



