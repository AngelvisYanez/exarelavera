<?php
/**
* Descripción: Objeto Paginacion
* Fecha de actualización:	2016-12-25
* Desarrollador:	 Erik Niebla
*/
class TreeMenu{
    protected $_index = array();
    protected $_dirtyIndex = false;
    protected $_pages=array();
    protected $_class;
    
    function TreeMenu(){}
    public function setClass($class = null){
        if (null !== $class && !is_string($class)) throw new Exception('Invalid argument: $class must be a string or null');
        $this->_class = $class;
        return $this;
    }
    public function getClass(){ return $this->_class; }
    public function notifyOrderUpdated(){ $this->_dirtyIndex = true; }
    protected function _sort(){ 
        if ($this->_dirtyIndex) {
            $newIndex = array();
            $index = 0;

            foreach ($this->_pages as $hash => $page) {
                $order = $page->getOrder();
                if ($order === null) {
                    $newIndex[$hash] = $index;
                    $index++;
                } else {
                    $newIndex[$hash] = $order;
                }
            }

            asort($newIndex);
            $this->_index = $newIndex;
            $this->_dirtyIndex = false;
        }
    }    
    public function addPage($page){
        if ($page === $this) throw new Exception('A page cannot have itself as a parent');
        if (!is_array($page)&& !($page instanceof TreeMenuItem)){
            throw new Exception('Invalid argument:  an array');
        } 
        $hash = $page->hashCode();
        if (array_key_exists($hash, $this->_index)) return $this;
        // adds page to container and sets dirty flag
        $this->_pages[$hash] = $page;
        $this->_index[$hash] = $page->getOrder();
        $this->_dirtyIndex = true;
        // inject self as page parent
        $page->setParent($this);
        return $this;
    }
    public function addPages($pages){
        if (!is_array($pages)) 
            throw new Exception('Invalid argument: $pages must be an array.');
        foreach ($pages as $page) $this->addPage($page);
        return $this;
    }
    public function setPages(array $pages){ $this->removePages(); return $this->addPages($pages); }
    public function getPages(){ return $this->_pages; }
    public function removePage($page, $recursive = false){
        if ($page instanceof TreeMenuItem) {
            $hash = $page->hashCode();
        } elseif (is_int($page)) {
            $this->_sort();
            if (!$hash = array_search($page, $this->_index)) { return false;}
        } else { return false; }

        if (isset($this->_pages[$hash])) {
            unset($this->_pages[$hash]);
            unset($this->_index[$hash]);
            $this->_dirtyIndex = true;
            return true;
        }
        if ($recursive) {         
            foreach ($this->_pages as $childPage) {
                if ($childPage->hasPage($page, true)) {
                    $childPage->removePage($page, true);
                    return true;
                }
            }
        }
        return false;
    }
    public function removePages(){ $this->_pages = array(); $this->_index = array(); return $this; }
    public function hasPage($page, $recursive = false){ 
        if (array_key_exists($page->hashCode(), $this->_index)) {
            return true;
        } elseif ($recursive) {
            foreach ($this->_pages as $childPage) {
                if ($childPage->hasPage($page, true)) {
                    return true;
                }
            }
        }
        return false;
    }
    public function hasPages(){ return count($this->_index) > 0; }
    public function hasProccess(){
        if ($this->get('itemType') == 'D') return true;
        foreach ($this->_pages as $childPage) {
            if ($childPage->get('itemType') == 'D') return true;
            if ($childPage->hasProccess()) return true;
        } return false;
    }
//    public function __call($method, $arguments){
//        if (@preg_match('/(find(?:One|All)?By)(.+)/', $method, $match)) {
//            return $this->{$match[1]}($match[2], $arguments[0], !empty($arguments[1]));
//        }
//
//        require_once 'Zend/Navigation/Exception.php';
//        throw new Exception(
//            sprintf(
//                'Bad method call: Unknown method %s::%s',
//                get_class($this),
//                $method
//            )
//        );
//    }
    public function toArray(){
        $pages = array();

        $this->_dirtyIndex = true;
        $this->_sort();
        $indexes = array_keys($this->_index);
        foreach ($indexes as $hash) {
            $pages[] = $this->_pages[$hash]->toArray();
        }
        return $pages;
    }
    public function current(){
        $this->_sort();
        current($this->_index);
        $hash = key($this->_index);

        if (isset($this->_pages[$hash])) {
            return $this->_pages[$hash];
        } else {
            throw new Exception('Corruption detected in container;');
        }
    }
    public function key(){
        $this->_sort();
        return key($this->_index);
    }
    public function next(){
        $this->_sort();
        next($this->_index);
    }
    public function rewind(){
        $this->_sort();
        reset($this->_index);
    }
    public function valid(){
        $this->_sort();
        return current($this->_index) !== false;
    }
    public function hasChildren(){
        return $this->hasPages();
    }
    public function getChildren(){
        $hash = key($this->_index);

        if (isset($this->_pages[$hash])) {
            return $this->_pages[$hash];
        }

        return null;
    }
    public function count(){
        return count($this->_index);
    }
}
class TreeMenuItem extends TreeMenu {
    
    protected $_id;
    protected $_label;
    protected $_order;
    protected $_target;
    protected $_title;   
    protected $_icon;
    protected $_href;
    protected $_itemType;
    
    protected $_resource;
    protected $_privilege;
    protected $_active = false;
    protected $_visible = true;
    protected $_properties = array();
    
    protected $_accesskey;
    protected static $_defaultPageType;
    protected $_fragment;   
    protected $_parent;
    protected $_rel = array();
    protected $_rev = array();
    protected $_customHtmlAttribs = array();
        
    function TreeMenuItem(array $options=null){ if($options!==null) $this->setOptions($options); }
    public function setHref($href){
        if (null !== $href && !is_string($href)) throw new Exception('Invalid argument: $label must be a string or null');
        $this->_href = $href; return $this;
    }
    function getHref(){ return $this->_href;}
    public function setOptions(array $options){
        foreach ($options as $key => $value) $this->set($key, $value);
        return $this;
    }
    public function setLabel($label){
        if (null !== $label && !is_string($label)) throw new Exception('Invalid argument: $label must be a string or null');
        $this->_label = $label; return $this;
    }
    public function getLabel(){ return $this->_label; }
    public function setItemType($itemType){
        if (null !== $itemType && !is_string($itemType)) throw new Exception('Invalid argument: $itemType must be a string or null');
        $this->_itemType = $itemType; return $this;
    }
    public function getItemType(){ return $this->_itemType; }
    public function setFragment($fragment){
        if (null !== $fragment && !is_string($fragment)) throw new Exception('Invalid argument: $fragment must be a string or null');
        $this->_fragment = $fragment; return $this;
    }
    public function getFragment(){ return $this->_fragment; }
    public function setId($id = null){
        if (null !== $id && !is_string($id) && !is_numeric($id)) throw new Exception('Invalid argument: $id must be a string, number or null');
        $this->_id = null === $id ? $id : (string) $id; return $this;
    }
    public function getId(){ return $this->_id; }
    public function setTitle($title = null){
        if (null !== $title && !is_string($title)) throw new Exception('Invalid argument: $title must be a non-empty string');
        $this->_title = $title; return $this;
    }
    public function getTitle(){ return $this->_title; }
    public function setIcon($icon = null){
        if (null !== $icon && !is_string($icon)) throw new Exception('Invalid argument: $icon must be a non-empty string');
        $this->_icon = $icon; return $this;
    }
    public function getIcon(){ return $this->_icon; }
    public function setTarget($target = null) {
        if (null !== $target && !is_string($target)) throw new Exception('Invalid argument: $target must be a string or null');
        $this->_target = $target; return $this;
    }
    public function getTarget(){ return $this->_target; }
    public function setAccesskey($character = null){
        if (null !== $character && (!is_string($character) || 1 != strlen($character))) throw new Exception('Invalid argument: $character must be a single character or null');
        $this->_accesskey = $character; return $this;
    }
    public function getAccesskey() { return $this->_accesskey; }
    public function setRel($relations = null){
        $this->_rel = array();
        if (null !== $relations) {
            if (!is_array($relations)) throw new Exception('Invalid argument: $relations must be an array. ');
            foreach ($relations as $name => $relation)  if (is_string($name)) $this->_rel[$name] = $relation;
        } return $this;
    }
    public function getRel($relation = null){
        if (null !== $relation) return isset($this->_rel[$relation]) ? $this->_rel[$relation] : null;
        return $this->_rel;
    }
    public function setRev($relations = null){
        $this->_rev = array();
        if (null !== $relations) {            
            if (!is_array($relations)) throw new Exception('Invalid argument: $relations must be an array.');
            foreach ($relations as $name => $relation) if (is_string($name)) $this->_rev[$name] = $relation;
        } return $this;
    }
    public function getRev($relation = null){
        if (null !== $relation) return isset($this->_rev[$relation]) ? $this->_rev[$relation] : null;
        return $this->_rev;
    }
    public function setCustomHtmlAttrib($name, $value){
        if (!is_string($name))  throw new Exception('Invalid argument: $name must be a string');        
        if (null !== $value && !is_string($value)) throw new Exception('Invalid argument: $value must be a string or null');
        if (null === $value && isset($this->_customHtmlAttribs[$name])) unset($this->_customHtmlAttribs[$name]);
        else $this->_customHtmlAttribs[$name] = $value; return $this;
    }
    public function setCustomHtmlAttribs(array $attribs){
        foreach ($attribs as $key => $value) $this->setCustomHtmlAttrib($key, $value); return $this;
    }
    public function getCustomHtmlAttrib($name){
        if (!is_string($name)) throw new Exception('Invalid argument: $name must be a string');
        if (isset($this->_customHtmlAttribs[$name])) return $this->_customHtmlAttribs[$name];
        return null;
    }
    public function getCustomHtmlAttribs(){ return $this->_customHtmlAttribs; }
    public function removeCustomHtmlAttrib($name){
        if (!is_string($name)) throw new Exception('Invalid argument: $name must be a string');
        if (isset($this->_customHtmlAttribs[$name])) unset($this->_customHtmlAttribs[$name]);
    }
    public function clearCustomHtmlAttribs(){ $this->_customHtmlAttribs = array(); return $this; }
    public function setOrder($order = null){
        if (is_string($order)) {
            $temp = (int) $order;
            if ($temp < 0 || $temp > 0 || $order == '0') $order = $temp; 
        }
        if (null !== $order && !is_int($order))  throw new Exception('Invalid argument: $order must be an integer or null, or a string that casts to an integer');
        $this->_order = $order;
        // notify parent, if any
        if (isset($this->_parent)) $this->_parent->notifyOrderUpdated();
        return $this;
    }
    public function getOrder(){ return $this->_order; }
    public function setResource($resource = null){
        if (null === $resource || is_string($resource)) $this->_resource = $resource;
        else  throw new Exception('Invalid argument: $resource must be null, a string.');
        return $this;
    }
    public function getResource(){ return $this->_resource; }
    public function setPrivilege($privilege = null){ $this->_privilege = is_string($privilege) ? $privilege : null; return $this; }
    public function getPrivilege(){ return $this->_privilege; }
    public function isActive($recursive = false){
        if (!$this->_active && $recursive) {
            foreach ($this->_pages as $page) if ($page->isActive(true)) return true;
            return false;
        } return $this->_active;
    }
    public function setActive($active = false){  if(is_string($active)&&(strtolower($active)=='s'||strtolower($active)=='true')) $active=true; $this->_active = (bool) $active; return $this; }
    public function hasItemType($itemType){
        if($this->_itemType==$itemType) return true;
        foreach ($this->_pages as $subpage) {            
            if($subpage->getItemType()==$itemType)
                return true;
            else if($subpage->hasPages()){
                        if($subpage->hasItemType($itemType))
                            return true;
                }else return false;
        } return false;
    }
    public function getActive($recursive = false){ return $this->isActive($recursive); }
    public function isChildActive(){   
        foreach ($this->_pages as $subpage) {            
            if($subpage->isActive()) return true;
            else if($subpage->hasPages()){
                    if($subpage->isChildActive()) return true;
                }else return false;
        }  return false;
    }
    public function setVisible($visible = true){ if (is_string($visible) &&( 'false' == strtolower($visible) || 'n' == strtolower($visible)  )) $visible = false; $this->_visible = (bool) $visible; return $this; }
    public function isVisible($recursive = false){
        if ($recursive && isset($this->_parent)) 
            if (!$this->_parent->isVisible(true)) return false;
        return $this->_visible;
    }
    public function getVisible($recursive = false){ return $this->isVisible($recursive); }
    public function setParent($parent = null){
        if ($parent === $this)
            throw new Exception('A page cannot have itself as a parent');        
        if ($parent === $this->_parent) return $this; // return if the given parent already is parent        
        if (null !== $this->_parent) $this->_parent->removePage($this);  // remove from old parent       
        $this->_parent = $parent;  // set new parent        
        if (null !== $this->_parent && !$this->_parent->hasPage($this, false))
            $this->_parent->addPage($this); // add to parent if page and not already a child
        return $this;
    }
    public function getParent(){ return $this->_parent; }
    public function set($property, $value){
        if (!is_string($property) || empty($property)) throw new Exception('Invalid argument: $property must be a non-empty string');
        $method = 'set' . self::_normalizePropertyName($property);
        if ($method != 'setOptions' && $method != 'setConfig' &&method_exists($this, $method)) $this->$method($value);
        else $this->_properties[$property] = $value;
        return $this;
    }
    public function get($property){
        if (!is_string($property) || empty($property)) throw new Exception('Invalid argument: $property must be a non-empty string');
        $method = 'get' . self::_normalizePropertyName($property);
        if (method_exists($this, $method)) return $this->$method();
        elseif (isset($this->_properties[$property])) return $this->_properties[$property];        
        return null;
    }
    public function __set($name, $value){ $this->set($name, $value); }
    public function __get($name){ return $this->get($name); }
    public function __isset($name){
        $method = 'get' . self::_normalizePropertyName($name);
        if (method_exists($this, $method))  return true;
        return isset($this->_properties[$name]);
    }
    public function __unset($name){
        $method = 'set' . self::_normalizePropertyName($name);
        if (method_exists($this, $method)) throw new Exception(sprintf('Unsetting native property "%s" is not allowed',$name));
        if (isset($this->_properties[$name])) unset($this->_properties[$name]);
    }
    public function __toString(){ return $this->_label; }
    public function addRel($relation, $value){
        if (is_string($relation)) $this->_rel[$relation] = $value; return $this;
    }
    public function addRev($relation, $value){
        if (is_string($relation)) $this->_rev[$relation] = $value; return $this;
    }
    public function removeRel($relation){
        if (isset($this->_rel[$relation])) unset($this->_rel[$relation]); return $this;
    }
    public function removeRev($relation){
        if (isset($this->_rev[$relation])) unset($this->_rev[$relation]); return $this;
    }
    public function getDefinedRel(){ return array_keys($this->_rel); }
    public function getDefinedRev(){  return array_keys($this->_rev); }
    public function getCustomProperties(){ return $this->_properties; }
    public final function hashCode(){ return spl_object_hash($this); }
    public function toArray(){
        return array_merge(
            $this->getCustomProperties(),
            array(
                'label'             => $this->getlabel(),
                'fragment'          => $this->getFragment(),
                'id'                => $this->getId(),
                'class'             => $this->getClass(),
                'title'             => $this->getTitle(),
                'icon'              => $this->getIcon(),
                'target'            => $this->getTarget(),
                'accesskey'         => $this->getAccesskey(),
                'rel'               => $this->getRel(),
                'rev'               => $this->getRev(),
                'customHtmlAttribs' => $this->getCustomHtmlAttribs(),
                'order'             => $this->getOrder(),
                'resource'          => $this->getResource(),
                'privilege'         => $this->getPrivilege(),
                'active'            => $this->isActive(),
                'visible'           => $this->isVisible(),
                'type'              => get_class($this),
                'pages'             => parent::toArray()
            )
        );
    }
    protected static function _normalizePropertyName($property){ return str_replace(' ', '', ucwords(str_replace('_', ' ', $property))); }
    public static function setDefaultPageType($type = null) {
        if($type !== null && !is_string($type)) throw new Exception('Cannot set default page type: type is no string but should be');
        self::$_defaultPageType = $type;
    }
    public static function getDefaultPageType() { return self::$_defaultPageType; }
    //public function dump() { var_dump($this); }
}