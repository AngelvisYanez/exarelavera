<?php
function consultarCedula($cedula){
    ini_set('max_execution_time', 300); //300 seconds = 5 minutes
    $request = curl_init();
    $timeOut = 0;        
    $data=array('success'=>true, 'message'=>null, 'hasData'=>false, 'data'=>null);
    curl_setopt ($request, CURLOPT_URL, "http://www.ecuadorlegalonline.com/modulo/datos/consultar-cidataimage.php");
    //curl_setopt ($request, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest",'Origin: http://www.ecuadorlegalonline.com', 'Host: www.ecuadorlegalonline.com', 'Content-Type: application/x-www-form-urlencoded', 'Accept: */*', 'Accept-Encoding: gzip, deflate', 'Accept-Language: es-ES,es;q=0.9', 'Connection: keep-alive', 'Content-Length: 16'));
    //curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt ($request, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36"); 
    curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);
    curl_setopt ($request, CURLOPT_POST, 1);
    curl_setopt ($request, CURLOPT_POSTFIELDS, array( 'token'=>$cedula ));
    curl_setopt ($request, CURLOPT_REFERER, 'http://www.ecuadorlegalonline.com/consultas/registro-civil/consultar-cedulas/');


    $server_output = curl_exec ($request);

    curl_close ($request);
    if(!empty($server_output) && trim($server_output)!=''){
        $data['hasData']=true;
        function getDataRegExp($html, $input, $RegExpession=null){
            try{
                $finalData=null;
                $Reg_Exp = '#\<input class="'.$input.'" type="text" value=(.+?) readonly#s';                 
                preg_match($RegExpession==null?$Reg_Exp:$RegExpession, $html, $finalData);
                return ($finalData!=null && isset($finalData[1]))? str_replace('"','',trim($finalData[1])):"";
            } catch (Exception $e){
                return "";    
            }
        }
//        $request = curl_init();
//        curl_setopt ($request, CURLOPT_URL, "http://www.ecuadorlegalonline.com/modulo/datos/consultar-direccion.php");
//        curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
//        curl_setopt ($request, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36"); 
//        curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut);
//        curl_setopt ($request, CURLOPT_POST, 1);
//        curl_setopt ($request, CURLOPT_POSTFIELDS, array( 'ci'=>$cedula ));
//        curl_setopt ($request, CURLOPT_REFERER, 'http://www.ecuadorlegalonline.com/consultas/registro-civil/consultar-cedulas/');
//        $direccion = curl_exec ($request);
//        curl_close ($request);
        
        $data['data']=array(
            'tipo'=>getDataRegExp($server_output,'style1'),
            'cedula'=>getDataRegExp($server_output,'style2'),
            'ciudadano'=>getDataRegExp($server_output,'style3'),           
            'lugar_nacimiento'=>getDataRegExp($server_output,'style5'),
            'fecha_nacimiento'=>getDataRegExp($server_output,'style6'),
            'nacionalidad'=>getDataRegExp($server_output,'style7'),
            'sexo'=>getDataRegExp($server_output,'style8'),
            'estado_civil'=>getDataRegExp($server_output,'','#\<input id="cedestado" class="style9" type="text" value=(.+?) readonly#s'),
//            'direccion'=>trim($direccion),
            'conyugue'=>getDataRegExp($server_output,'','#\<input id="cedconyuge" class="style4" type="text" value=(.+?) readonly#s'),
            'conyugue_extra'=>getDataRegExp($server_output,'','#\<span id="lblconyuge" class="style17"\>(.+?)\<\/span\>#s'),
        );       
        
    }else{
        $ruc['success']=false;
        $ruc['message']='No se logro obtener los datos o la cedula es incorrecta!';
    }
    //var_dump($data);
    //echo $server_output;
    return $data;
}
function consultarRuc($ruc_cons){
	ini_set('max_execution_time', 300); //300 seconds = 5 minutes
    //ini_set("allow_url_fopen", 1);  
    $link1="https://declaraciones.sri.gob.ec/sri-catastro-sujeto-servicio-internet/rest/ConsolidadoContribuyente/existePorNumeroRuc?numeroRuc=";
    $link2="https://declaraciones.sri.gob.ec/sri-catastro-sujeto-servicio-internet/rest/ConsolidadoContribuyente/obtenerPorNumerosRuc?&ruc=";
    $ruc=array('success'=>true, 'message'=>null, 'isRuc'=>false, 'hasData'=>false);
    if(strlen($ruc_cons)==10) $ruc_cons.="001";
    if(strlen($ruc_cons)!=13){
        $ruc['success']=false;
        $ruc['message']="El RUC $ruc_cons debe constar de 13 Digitos! ";
    }else
    try{   
        $user_agent    = array(
            "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)",
            "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; FDM)",
            "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; Avant Browser [avantbrowser.com]; Hotbar 4.4.5.0)",
            "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en; rv:1.8.1.14) Gecko/20080409 Camino/1.6 (like Firefox/2.0.0.14)",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Version/3.1 Safari/525.13",
            "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; NeosBrowser; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; es-ES; rv:1.8.1) Gecko/20061010 Firefox/2.0"
        );  
        $agent = $user_agent[rand(0, count($user_agent)-1)];
        
        $request = curl_init(); 
        $timeOut = 0; 
        curl_setopt ($request, CURLOPT_URL, $link1.$ruc_cons); 
        curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt ($request, CURLOPT_USERAGENT, $agent); 
        curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut); 
        //Si tiene salida a Internet por Proxy, debe poner ip y puerto
        //curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
        //curl_setopt ($request, CURLOPT_PROXY, '192.168.0.1:3128');
        //curl_setopt ($request, CURLOPT_PROXYUSERPWD, 'user:password'); 
        $response = curl_exec($request); 
        curl_close($request);      
        //var_dump(((is_string($response) &&  strtoupper($response))=="TRUE"));
        $ruc['isRuc']=($response===true || (is_string($response) &&  strtoupper($response)=="TRUE"))?true:false;
        if(trim($response)=='') throw new Exception ("No se pudo obtener confirmacion del sri!");
        if($ruc['isRuc']){            
            $request = curl_init(); 
            $timeOut = 0; 
            curl_setopt ($request, CURLOPT_URL, $link2.$ruc_cons); 
            curl_setopt ($request, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt ($request, CURLOPT_USERAGENT, $agent); 
            curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut); 
            //Si tiene salida a Internet por Proxy, debe poner ip y puerto
            //curl_setopt ($request, CURLOPT_HTTPPROXYTUNNEL, 1);
            //curl_setopt ($request, CURLOPT_PROXY, '192.168.0.1:3128');
            //curl_setopt ($request, CURLOPT_PROXYUSERPWD, 'user:password'); 
            $response = curl_exec($request); 
            curl_close($request);             
            $aux = json_decode($response);
            if(is_array($aux)){
                $ruc['hasData']=true;                
                $ruc['obligadoContabilidad']=$aux[0]->obligado=='S'?true:false;
                $ruc['contribuyenteEspecial']=($aux[0]->claseContribuyente!=null && strtoupper($aux[0]->claseContribuyente)=='ESPECIAL')?true:false;
                $ruc['tipo']=($aux[0]->estadoPersonaNatural=='ACT'?'N':($aux[0]->estadoSociedad=='ACT'?'J':null));
                $ruc['tipoLong']=($aux[0]->estadoPersonaNatural=='ACT'?'NATURAL':($aux[0]->estadoSociedad=='ACT'?'JURIDICA':null));
                $ruc['activo']=$aux[0]->estadoPersonaNatural=='ACT'||$aux[0]->estadoSociedad=='ACT'?true:false;
                $ruc['rucData']=$aux[0];
            }
        }else{            
            $ruc['hasData']=true;   
            $ruc['message']="No se encontro el ruc!";
            $cedula= consultarCedula(substr($ruc_cons, 0, 10));
            if($cedula['success']&&$cedula['hasData']){
                $ruc['message']="No posee ruc!";
                $ruc['cedData']=$cedula['data'];
            }                
        }
    } catch (Exception $ex) {
        $ruc['success']=false;
        $ruc['message']=$ex->getMessage();
    } 
    //change_string_deep('utf8_decode',$ruc);
    return $ruc;
}