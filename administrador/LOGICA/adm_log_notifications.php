<?Php
/**
* Descripcion: Cargar el menu del sistema informatico
* Fecha de actualizacion:	2019-02-14
* Desarrollador:	 Erik Niebla
*/
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');

class Class_Sys_Notifications extends MysqlDatos{
    public $lista=array();
    public $today;
    function __construct($obBD_conexion) {
        $this->today=date("Y-m-d");
        $this->setSentencias('sentencias_noti');
        //$this->VentanasNuevas($obBD_conexion);
        $this->FirmaElectronica($obBD_conexion);
        $this->ProductosCaducos($obBD_conexion);
        //$this->BlocksCaducos($obBD_conexion);
	
	if($_SESSION['Ses_Emp_Cod']!=1){
        $this->Notificaciones($obBD_conexion);
        $this->General($obBD_conexion);
        }
		
    }

    function VentanasNuevas($obBD_conexion){
        function formatLabel($tipo,$ventana,$notif){
            $label='';
            switch($tipo){
                case 'N': 
                    $notif->setIcon('glyphicon glyphicon-fire','btn-yellow');
                    $label="Se agrego la funcionalidad de <span class=\"green\">$ventana</span>"; 
                    break; //mejora
                case 'U': 
                    $notif->setIcon('glyphicon glyphicon-tags','btn-purple');
                    $label="Se mejoro la funcionalidad de <span class=\"green\">$ventana</span>"; 
                    break; //mejora
                default: $label=$ventana;
            } 
            $notif->setLabel($label.'&nbsp;');
        }        
        $ventanas=array(
            array('type'=>'N','Link'=>'./adm_pas_usuarios_2.0.php','Label'=>'Ventana','Desc'=>'Probando <a>Probando</a>'),
            array('type'=>'U','Link'=>'./adm_pas_usuarios_2.0.php','Label'=>'Ventana','Desc'=>'Probando <a>Probando</a>'),
            array('type'=>'A','Link'=>'./adm_pas_usuarios_2.0.php','Label'=>'Ventana','Desc'=>'Probando <a>Probando</a>')
        );
        foreach($ventanas as $v){
            $notif=new NotificationExa();            
            formatLabel($v['type'],$v['Label'],$notif);
            $notif->setDescripcion($v['Desc']);
            //$notif->setLink($v['Link']);
            array_push($this->lista, $notif);
        }
    }
    // funcional pero si Not_Msj contiene espaciado entre lineas deja de funcionar
    // function Notificaciones($obBD_conexion){
    //     $hoy = date("Y-m-d");
    //     $mensaje=$this->getArrayConsulta('notificacion.selectWhere',array('where'=>array('Not_Est'=>'A')),$obBD_conexion);
    //     foreach ($mensaje as $key => $value) {
    //         $notif=new NotificationExa();
    //         if(($hoy>=$value['Not_Fei'] AND $hoy<=$value['Not_Fec']) AND $value['Emp_Cod']==$_SESSION['Ses_Emp_Cod']) {
    //         $notif->setLabel($value['Not_Enc']);
    //         $notif->danger();
    //         $notif->setDescripcion($value['Not_Msj']);
    //         array_push($this->lista, $notif);
    //         }
    //     }
    // }

    function Notificaciones($obBD_conexion){
        $hoy = date("Y-m-d");
        $mensaje = $this->getArrayConsulta('notificacion.selectWhere', array('where' => array('Not_Est' => 'A')) ,$obBD_conexion);
        foreach ($mensaje as $key => $value) {
            $notif = new NotificationExa();
            
            if (($hoy >= $value['Not_Fei'] AND $hoy <= $value['Not_Fec']) AND $value['Emp_Cod'] == $_SESSION['Ses_Emp_Cod']) {
                $notif->setLabel($value['Not_Enc']);
                $notif->danger();
    
                // Asegurarse de que el salto de línea sea el correcto (en caso de que sea '\r\n' o '\n')
                $mensaje_con_saltos = str_replace(array("\r\n", "\r", "\n"), '<br>', $value['Not_Msj']);
                $notif->setDescripcion($mensaje_con_saltos);
                
                array_push($this->lista, $notif);
            }
        }
    }


    function General($obBD_conexion){
        $hoy = date("Y-m-d");
        $general=$this->getArrayConsulta('notificacion.selectWhere',array('where'=>array('Not_Est'=>'A','Emp_Cod'=>1)),$obBD_conexion);
        if(count($general)!=0){
            if($hoy>=$general[0]['Not_Fei'] AND $hoy<=$general[0]['Not_Fec']) {
                $notif=new NotificationExa();
                $notif->setLabel($general[0]['Not_Enc']);
                $notif->danger();
                $notif->setDescripcion($general[0]['Not_Msj']);
                array_push($this->lista, $notif);
            }
        }
    }
    
    function FirmaElectronica($obBD_conexion){
        $firma=$this->getArrayConsulta('llave_elect.selectWhere', array('where'=>array('Lla_Est'=>'A','Emp_Cod'=>$_SESSION['Ses_Emp_Cod'])), $obBD_conexion);
        if(count($firma)>0){
            $dias = date_diff(date_create($this->today),date_create($firma[0]['Lla_Cad']))->format('%R%a')*1;
            if($dias<=10){
                $notif=new NotificationExa();
                $notif->setLabel('Caducidad Firma Electronica');
                $notif->setIcon('fa fa-key',$dias<=0?'btn-danger':'btn-warning');
                
                $notif->danger();
                
                if($dias<=0){
                    // $notif->setDescripcion('La <u class=&quot;green&quot; style="color: #69aa46 !important;">Firma Electronica</u> ha <u class=&quot;red&quot;  style="color: #dd5a43 !important;">CADUCADO</u>, debe realizar el tramite para adquirir una nueva!');
                    // $mensaje = 'La <u class=&quot;green&quot; style="color: #69aa46 !important;">Firma Electronica</u> ha <u class=&quot;red&quot;  style="color: #dd5a43 !important;">CADUCADO</u>, debe realizar el tramite para adquirir una nueva!';
                    $mensaje = '<span style="display:block; text-align:center;">La <u style="color:#69aa46;">Firma Electronica</u> ha <u style="color:#dd5a43;">CADUCADO</u>.</span><span style="display:block; text-align:center;">Debe realizar el tramite para adquirir una nueva!</span>';
                } else {
                    // $notif->setDescripcion('La <u class=&quot;green&quot; style="color: #69aa46 !important;">Firma Electronica</u> ha caducara en <u class=&quot;orange&quot; style="color: #ff892a !important">'.$dias.' dia(s)</u>, debe realizar el tramite para adquirir una nueva!');
                    // $mensaje = 'La <u class=&quot;green&quot; style="color: #69aa46 !important;">Firma Electronica</u> caducará en <u class=&quot;orange&quot; style="color: #ff892a !important">'.$dias.' dia(s)</u>, debe realizar el tramite para adquirir una nueva!';
                    $mensaje = '<span style="display:block; text-align:center;">La <u style="color:#69aa46;">Firma Electronica</u> caducara en <u style="color:#ff892a;">'.$dias.' dia(s)</u>.</span><span style="display:block; text-align:center;">Debe realizar el tramite para adquirir una nueva!</span>';
                }
                // Normalizar saltos de línea por si acaso
                $mensaje = str_replace(array("\r\n", "\r", "\n"), '<br>', $mensaje);
                $notif->setDescripcion($mensaje);
                array_push($this->lista, $notif);
            }
        }
    }
    function ProductosCaducos($obBD_conexion){
        $lotes = $this->getArrayConsulta('loteprod.selectWhere', array('where'=>array('Lte_Est'=>'A'), 'setWhere' =>array('setEmpCod')), $obBD_conexion);
        if(count($lotes)>0)
            for($i = 0; $i < count($lotes); ++$i) {
                $dias = date_diff(date_create($this->today),date_create($lotes[$i]['Lte_Cad']))->format('%R%a')*1;
                if($dias <= (int)$lotes[$i]['Lte_Nti']){
                    $notif=new NotificationExa();
                    $notif->setLabel('Lote  '.$lotes[$i]['Lte_Ser']);
                    $notif->setIcon('fa fa-cube',$dias<0?'btn-danger':'btn-warning');
                    $notif->setLink('../../facturacion/FRONT/fac_con_lotes_prod.php');
                    if($dias<0){
                        $notif->danger();
                        $notif->setDescripcion('Posee <u class=&quot;green&quot; style="color: #69aa46 !important;">lotes caducados</u> debe dar de baja.<u class=&quot;red&quot; style="color: #dd5a43 !important;"></u>');
                    }else{
                         $notif->setDescripcion('En <u class=&quot;green&quot; style="color: #69aa46 !important;">'.$dias.'</u> dias ha de caducarse su Lote.<u class=&quot;red&quot; style="color: #dd5a43 !important;"></u>');
                    }
                    array_push($this->lista, $notif);
                    //$notif->setConteo(1, 'badge-danger');
                }
            }        
    }
    function BlocksCaducos($obBD_conexion){
        $notif=new NotificationExa();
        $notif->setIcon('glyphicon glyphicon-tags','btn-pink');
        $notif->setLabel('Caducidad Blocks Documentos');
        $notif->setConteo(1, 'badge-warning');
        $notif->setLink('./adm_pas_usuarios_2.0.php');
        array_push($this->lista, $notif);
    }
    function renderNotifications() {
        $total=count($this->lista);
        $html = array();
        $html[] = '<li class="purple notificaciones" '.($total>0?'':'style="display:none"').'>';
        $html[] = '<a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="ace-icon fa fa-bell icon-animated-bell" style="font-size: 18px; margin-top: 15px;"></i><span class="badge badge-important">'.$total.'</span></a>';
        $html[] = '<ul class="dropdown-menu-right dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close animated flipInX" >';
        $html[] = '<li class="dropdown-header"><i class="ace-icon fa fa-exclamation-triangle"></i>'.$total.($total>1?' Notificaciones':' Notificacion').'</li>';
        $html[] = '<li class="dropdown-content">';
        $html[] = '<ul class="dropdown-menu dropdown-navbar navbar-pink">';
        if($total>0) foreach ($this->lista as $notif) { $html[] = $notif->render(); }
        $html[] = '</ul>';
        $html[] = '</li>';
        $html[] = '<!--<li class="dropdown-footer"><a href="#">See all notifications<i class="ace-icon fa fa-arrow-right"></i></a></li>-->';
        $html[] = '</ul>';
        $html[] = '</li>';
        return join(PHP_EOL, $html);
    }
    function sentencias_noti($id,$Par_Sql){
        $sql="";
        switch($id){
            case 0:
                $sql = "";
                //echo $sql;
                break;
        }
        return $sql;
    }

}
class NotificationExa{
    protected $_icon = 'fa fa-info-circle';
    protected $_color = 'btn-info';
    protected $_bag_color = 'badge-info';
    protected $_bag_title = null;
    protected $_desc_corta='';
    protected $_desc_larga='';
    protected $_conteo=0;
    protected $_link=null;
    protected $_danger=false;

    function setIcon($icon,$color='btn-info'){
        $this->_icon=$icon;
        $this->_color=$color;
    }
    function setLink($link){
        $this->_link=$link;
    }
    function setConteo($conteo,$color='badge-info',$title=null){
        $this->_conteo=$conteo;
        $this->_bag_color=$color;
        $this->_bag_title=$title;
    }
    function setLabel($label){
        $this->_desc_corta=$label;
    }
    function setDescripcion($descripcion){
        $this->_desc_larga=$descripcion;
    }
    function danger(){
        $this->_danger=true;
    }
    function render(){       
        $html = array();
        $html[] = '<li>';
        if(!is_null($this->_link))
            $html[] = '    <a href="'.$this->_link.'" target="contenido">';
        else
            $html[] = '    <a onclick="openAlert(\''.($this->fixHtmlAttr($this->_desc_corta)).'\',\''.($this->fixHtmlAttr($this->_desc_larga)).'\');">';
        $html[] = '        <div class="clearfix">';
        $html[] = '            <span class="">';
        $html[] = '                <i class="btn btn-xs no-hover '.$this->_color.' '.$this->_icon.'"></i> ';
        if($this->_conteo*1>0)           
            $html[] = '            <span class="pull-right badge '.$this->_bag_color.'"'.(!is_null($this->_bag_title)?' title="'.$this->_bag_title."'":'').'>'.$this->_conteo.'</span>';        
        $html[] = '<span>'.$this->_desc_corta.'</span>';
        $html[] = '            </span>';

        $html[] = '        </div>';
        $html[] = '    </a>';
        $html[] = '</li>';
        if($this->_desc_larga!==''&&$this->_danger){
            $html[] = '<script>$(function () { openAlert(\''.($this->fixHtmlAttr($this->_desc_corta)).'\',\''.($this->fixHtmlAttr($this->_desc_larga)).'\'); });</script>';
        }
        return join(PHP_EOL, $html);
    }
    function fixHtmlAttr($str){        
        // return str_replace('"', "\'",str_replace("'", "\'",$str));
        
        // Primero escapar las barras invertidas (debe ser primero)
        $str = str_replace('\\', '\\\\', $str);
        // Luego escapar las comillas simples (que son las que usamos en el JavaScript)
        $str = str_replace("'", "\\'", $str);
        // Convertir saltos de línea a espacios para evitar problemas
        $str = str_replace(array("\r\n", "\r", "\n"), ' ', $str);
        // Las comillas dobles no necesitan escape si usamos comillas simples en JS
        return $str;
    }
}