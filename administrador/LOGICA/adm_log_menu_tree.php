<?Php
/**
* Descripción: Cargar el menu del sistema informático
* Fecha de actualización:	2016-12-25
* Desarrollador:	 Erik Niebla
*/
require_once (__DIR__ . '/../../auditoria/LOGICA/aud_log_auditoria.php');
require_once (__DIR__ . '/../../skins/php/TreeMenu.php');
require_once (__DIR__ . '/adm_sql_menu.php');
require_once (__DIR__ . '/../../Librerias/procedimientos/almacenados_standar.php');

if (!function_exists('groupBy')) {
    function groupBy($array, $key) {
        $result = array();
        if (is_array($array)) {
            foreach ($array as $element) {
                if (is_object($element)) {
                    $groupKey = isset($element->$key) ? $element->$key : null;
                } elseif (is_array($element)) {
                    $groupKey = isset($element[$key]) ? $element[$key] : null;
                } else {
                    continue;
                }
                if ($groupKey !== null) {
                    $result[$groupKey][] = $element;
                }
            }
        }
        return $result;
    }
}

class Class_Sys_Menu extends MysqlDatos{  
    function __construct() {
        $this->setSentencias('sentencias_men');
    }
    function getMenuContainer($Perfiles,$obBD){ 
        $mperf1=$this->buildProfileFilter($Perfiles);
        if (empty($mperf1)) { return new TreeMenu(); }
        $menu=new TreeMenu();
        $menu->setPages($this->getMenuPages(0,$mperf1,$obBD));
        return $menu;
    }
    function getMenuPages($Org_Cod,$mperf,$obBD){  
        $pages=array();
        $result1= $this->getArrayConsulta(($Org_Cod==0?1:2),$Org_Cod.'*'.$mperf, $obBD);
        $result2= ($Org_Cod==0?array():$this->getArrayConsulta(3,$Org_Cod.'*'.$mperf.'*P', $obBD));
        foreach ($result1 AS $Org){
            $item=new TreeMenuItem(array('id'=>$Org['Org_Cod'],'label'=>$Org['Org_Des'],'icon'=>$Org['Org_Ico'],'order'=>$Org['Org_Ord'],'itemType'=>'G'));
            $item->setPages($this->getMenuPages($Org['Org_Cod'],$mperf,$obBD));
            array_push($pages,$item);
        }
        foreach ($result2 AS $Pcs){
            $item=new TreeMenuItem(array('id'=>$Pcs['Pcs_Cod'],'label'=>$Pcs['Pcs_Lin'],'title'=>$Pcs['Pcs_Det'],'icon'=>$Pcs['Pcs_Ico'],'order'=>$Pcs['Pcs_Ord'],'itemType'=>'D','href'=>$Pcs["Rut_Des"].$Pcs["Pcs_Nom"]));
            array_push($pages,$item);
        }
        return $pages;
    }
    function buildProfileFilter($Perfiles){
        if (!is_array($Perfiles) || empty($Perfiles)) return '';
        $mperf='';
        foreach($Perfiles as $item) $mperf=$mperf." "."perfiorgan.Per_Cod=".$item." OR";
        return trim(substr($mperf,1,-2));
    }
    function getMenuContainer2($Perfiles,$obBD){
        $mperf1=$this->buildProfileFilter($Perfiles);
        $Organiza=groupBy($this->getArrayConsulta(2,'*'.$mperf1, $obBD),'Org_Niv');
        $Procesos=groupBy($this->getArrayConsulta(3,'*'.$mperf1.'*P', $obBD),'Org_Cod');
        $menu=new TreeMenu();
        $this->setMenuPages($menu,0,$Organiza,$Procesos);
        return $menu;
    }
    function setMenuPages(&$menu,$Org_Cod,$Organiza,$Procesos){
        if (isset($Organiza[$Org_Cod])) {
            foreach ($Organiza[$Org_Cod] AS $Org){
                $item=new TreeMenuItem(array('id'=>$Org['Org_Cod'],'label'=>$Org['Org_Des'],'icon'=>$Org['Org_Ico'],'order'=>$Org['Org_Ord'],'itemType'=>'G'));
                $this->setMenuPages($item,$Org['Org_Cod'],$Organiza,$Procesos);
                $menu->addPage($item);
            }
        }
        if (isset($Procesos[$Org_Cod])) {
            foreach ($Procesos[$Org_Cod] AS $Pcs){
                $item=new TreeMenuItem(array('id'=>$Pcs['Pcs_Cod'],'label'=>$Pcs['Pcs_Lin'],'title'=>$Pcs['Pcs_Det'],'icon'=>$Pcs['Pcs_Ico'],'order'=>$Pcs['Pcs_Ord'],'itemType'=>'D','href'=>$Pcs["Rut_Des"].$Pcs["Pcs_Nom"]));
                $menu->addPage($item);
            }
        }
    }
    function menuToHtml($case,$menu,$class,$itemClass){
        switch($case){
            case 1: 
                $menu->setClass($class);
                $menu->itemClass=$itemClass;
                return $this->menuToHtml1($menu,$menu->itemClass);
            default: return '';
        }
    }
    function menuToHtml1($menu,$itemClass){
        $res="";
        $pages = $menu->getPages();
        if (is_array($pages)) {
            foreach($pages AS $items){
                $icon = $items->getIcon();
                if (empty($icon)) {
                    $icon = "menu-icon fa fa-caret-right";
                }
                $label = $items->getLabel();
                $itemType = $items->getItemType();
                $href = $items->getHref();
                $res .= "<li class='".$itemClass."'>";
                if ($itemType == "G") {
                    $res .= "<a href='#' class='dropdown-toggle'><i class='".$icon."'></i><span class='menu-text'>".$label."</span><b class='arrow fa fa-angle-down'></b></a>";
                    $res .= "<b class='arrow'></b>";
                    $res .= "<ul class='submenu'>";
                    $res .= $this->menuToHtml1($items, $itemClass);
                    $res .= "</ul>";
                } else {
                    $res .= "<a href='#' data-url='".$href."'><i class='".$icon."'></i><span class='menu-text'>".$label."</span></a>";
                    $res .= "<b class='arrow'></b>";
                }
                $res .= "</li>";
            }
        }
        return $res;
    }
}
?>
