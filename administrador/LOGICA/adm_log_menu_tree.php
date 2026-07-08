<?Php
/**
* Descripci�n: Cargar el menu del sistema inform�tico
* Fecha de actualizaci�n:	2016-12-25
* Desarrollador:	 Erik Niebla
*/
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once ('../../skins/php/TreeMenu.php');

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
        if (isset($Organiza[$Org_Cod]))
        foreach ($Organiza[$Org_Cod] AS $Org){
            $item=new TreeMenuItem(array('id'=>$Org['Org_Cod'],'label'=>$Org['Org_Des'],'icon'=>$Org['Org_Ico'],'order'=>$Org['Org_Ord'],'itemType'=>'G'));
            $this->setMenuPages($item,$Org['Org_Cod'],$Organiza,$Procesos);
            $menu->addPage($item);
        }
        if (isset($Procesos[$Org_Cod]))
        foreach ($Procesos[$Org_Cod] AS $Pcs){
            $item=new TreeMenuItem(array('id'=>$Pcs['Pcs_Cod'],'label'=>$Pcs['Pcs_Lin'],'title'=>$Pcs['Pcs_Det'],'icon'=>$Pcs['Pcs_Ico'],'order'=>$Pcs['Pcs_Ord'],'itemType'=>'D','href'=>$Pcs["Rut_Des"].$Pcs["Pcs_Nom"]));
            $menu->addPage($item);
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
    function menuToHtml1($menu,$itemClass=''){
        $html = array();
        $html[] ='<ul class="'.$menu->getClass().'">';
        foreach ($menu->getPages() as $page) {
            //if($this->navigation()->accept($page)) {  
                $dropdown = $page->hasPages();
                if(!$page->isVisible() || (!$page->getHref()&&!$dropdown) || !$page->hasProccess()) { continue; } // visibility of the page
                $icon = $page->getIcon();
                $active=$page->isActive()||($dropdown?$page->isChildActive():false);
                
                $html[] = '<li class="'.$itemClass.' '. ($active ? 'active' : ''). ($dropdown&&$active ? ' open' :'').'" >';
                $html[] = '<a ' . ($dropdown ? 'href="#" class="dropdown-toggle"' : 'href="'.$page->getHref().'"').' >';
                $html[] = (!empty($icon) ? '<i class="menu-icon '.$icon.'"></i>' : '<i class="menu-icon fa fa-angle-double-right"></i>');
                $html[] = '<span class="menu-text">'.$page->getLabel().'</span>';

                if ($dropdown) { $html[] = '<b class="arrow fa fa-angle-down"></b>'; }
                $html[] = '</a>';
                if ($dropdown) {$html[] = '<b class="arrow"></b>';}	 
                if (!$dropdown) { $html[] = '</li>'; continue; }
                $html[] = $this->createSubmenu1($page->getPages(),$itemClass /*,$this->navigation()*/);		
                $html[] = "</li>";
            //}
        }
        $html[] = '</ul><!-- /.nav-list -->'; 
        return join('', $html);
    } 
    function createSubmenu1($pages,$itemClass=''/*,$navigation*/){	
        $html[] = '<ul class="submenu">';
        $SubDropdown = false;
        foreach ($pages as $subpage) {                
                $SubDropdown = $subpage->hasPages();
                $SubIcon = $subpage->getIcon();
                $SubActive=$subpage->isActive()||($SubDropdown?$subpage->isChildActive():false);
                //if($navigation->accept($subpage)) {                        
                        if (!$subpage->isVisible() || (!$subpage->getHref()&&!$SubDropdown) || ($SubDropdown&&!$subpage->hasItemType('D')) ) { continue; }// visibility of the sub-page
                        if ($subpage->getLabel() == 'divider') { $html[] = '<li class="divider"></li>'; continue; }
                        $html[] = '<li class="'.$itemClass.' '. ($SubActive ? 'active' : ''). ($SubDropdown&&$SubActive ? ' open' :'').'" >';
                        $html[] = '<a ' . ($SubDropdown ? 'href="#" class="dropdown-toggle"' : ' class="menu-link" target="contenido" href="'.$subpage->getHref().'"').' >';
                        $html[] = (!empty($SubIcon) ? '<i class="menu-icon '.$SubIcon.'"></i>' : '<i class="menu-icon fa fa-angle-double-right"></i>');
                        $html[] = '<span class="menu-text">'.$subpage->getLabel().'</span>';
                        if ($SubDropdown) {$html[] = '<b class="arrow fa fa-angle-down"></b>';}
                        $html[] = "</a>";
                        if ($SubDropdown) { $html[] = '<b class="arrow"></b>'; }	 
                        if (!$SubDropdown) { $html[] = '</li>'; continue; }
                        $html[] = $this->createSubmenu1($subpage->getPages(),$itemClass /*,$navigation*/);
                        $html[] = "</li>";
               // }
        }	 
        $html[] = "</ul>";
        return join(PHP_EOL, $html);
    }
    
    
    function sentencias_men($id,$Par_Sql){
        switch($id){
            case 1:
                $sql = "(SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, Org_Ico,
                    organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN (SELECT organizado.Org_Niv FROM organizado WHERE organizado.Org_Cod IN 
                    (SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
                     (procesos.Org_Cod = organizado.Org_Cod) WHERE (".$Par_Sql[1]."))) ORDER BY organizado.Org_Ord) 
                     UNION DISTINCT
                     (SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, Org_Ico,
                    organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN  
                    (SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
                     (procesos.Org_Cod = organizado.Org_Cod) WHERE (".$Par_Sql[1].")) ".(empty($Par_Sql[0])?'':"AND organizado.Org_Niv = $Par_Sql[0]")." ORDER BY organizado.Org_Ord)";
                //echo $sql;                
                return $sql;
            case 2:    
                $sql="SELECT DISTINCT
                      organizado.Org_Det,
                      organizado.Org_Ord,
                      organizado.Org_Des,
                      organizado.Org_Niv,
                      organizado.Org_Cod,
                      organizado.Org_Img,
                      organizado.Org_Ime,
                      organizado.Org_Ico
                    FROM
                      organizado
                    WHERE
                      ".(empty($Par_Sql[0])?'':"organizado.Org_Niv=$Par_Sql[0] AND")." Org_Mod='A' ORDER BY organizado.Org_Niv,IF(organizado.Org_Niv=0,organizado.Org_Ord,organizado.Org_Cod)";
                //echo $sql;   
                return $sql;
            case 3:    
                $sql = "SELECT DISTINCT procesos.Pcs_Cod, procesos.Org_Cod, procesos.Pcs_Ord, procesos.Pcs_Lin, rutas.Rut_Des,
                        procesos.Pcs_Nom, procesos.Pcs_Img, procesos.Pcs_Det,Pcs_Ico
                        FROM
                          rutas
                          INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
                          INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
                        WHERE
                        procesos.Pcs_Est='A' AND procesos.Pcs_Tip = '$Par_Sql[2]'
                        ".(empty($Par_Sql[0])?'':"AND procesos.Org_Cod=$Par_Sql[0]")." AND (".$Par_Sql[1].")
                        ORDER BY procesos.Pcs_Ord";			
                //echo $sql;   
                return $sql;    
                
        }
    }
}