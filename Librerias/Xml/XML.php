<?php
class XmlDoc extends SimpleXMLElement {     
    public static function Ns($namespace){
        $ns=null;
        switch(strtoupper($namespace)){
            case 'XMLNS':$ns='http://www.w3.org/2000/xmlns/'; break;
            case 'WS':   $ns='http://microsoft.com/wsdl/types/'; break;
            case 'DS':   $ns='http://www.w3.org/2000/09/xmldsig#'; break;
            case 'ETSI': $ns='http://uri.etsi.org/01903/v1.3.2#'; break;            
        } return $ns;
    }
     public static function Cd($C14=null){
        $C14=strtoupper($C14);
        $cd=array(
            'C14N'=>             'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
            'C14N_COMMENTS'=>    'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments',
            'EXC_C14N'=>         'http://www.w3.org/2001/10/xml-exc-c14n#',
            'EXC_C14N_COMMENTS'=>'http://www.w3.org/2001/10/xml-exc-c14n#WithComments'
        );         
        return empty($C14)?$cd:(isset($cd[$C14])?$cd[$C14]:null);        
    }
    public function text($value=null){ if(empty($value)) return $this.''; else{ $this[0]=$value; return $this; } }
    public function childNs($prefix){ return $this->children($prefix,true); }
    public function addChildNs($prefix,$name,$value=null){ return $this->addChild("$prefix:$name", $value, $this->Ns($prefix)); }
    public function addAttributeNs($prefix){ $ns='xmlns'; $this->toNode()->setAttributeNS($this->Ns($ns), "$ns:".$prefix, $this->Ns($prefix)); }
    public function removeAttributeNs($prefix){ $this->toNode()->removeAttribute("xmlns:$prefix"); }
    public function tot(){ return count($this); }
    public function addBefore($insert, $duplicate=true){ return $this->insertNodeBefore($insert,$this,$duplicate); }
    public function insertNodeBefore($insert, SimpleXMLElement $target, $duplicate=true){
        $target_dom = dom_import_simplexml($target);
        $insert_dom = $this->convertToNode($insert);
        if(!$duplicate)
            if($target_dom->previousSibling && $target_dom->previousSibling->nodeName==$insert_dom->nodeName) return $target_dom->previousSibling;
        return (!$insert_dom)?false:simplexml_import_dom($target_dom->parentNode->insertBefore($insert_dom, $target_dom));
    }
    public function addAfter($insert, $duplicate=true){ return $this->insertNodeAfter($insert,$this,$duplicate); }
    public function insertNodeAfter($insert, SimpleXMLElement $target, $duplicate=true){
        $target_dom = dom_import_simplexml($target);
        $insert_dom = $this->convertToNode($insert); 
        if(!$duplicate)
            if($target_dom->nextSibling && $target_dom->nextSibling->nodeName==$insert_dom->nodeName) return $target_dom->nextSibling;        
        return (!$insert_dom)?false:simplexml_import_dom(($target_dom->nextSibling)?$target_dom->parentNode->insertBefore($insert_dom, $target_dom->nextSibling):$target_dom->parentNode->appendChild($insert_dom));
    }
    public function removeNode($node=null){        
        $dom=dom_import_simplexml(empty($node)?$this:$node);
        $dom->parentNode->removeChild($dom);       
    }
    public function convertToNode($data, $formatOutput=false, $whitespaces=false){
        $data_dom=null;
        if(is_a($data,'DOMElement')) $data_dom = $data; 
        else{            
            if(is_string($data)){                
                $dom = new DOMDocument();
                $dom->preserveWhiteSpace = $whitespaces;
                $dom->formatOutput = $formatOutput;
                $dom->loadXML((!mb_detect_encoding($data, 'UTF-8', true))?mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1'):$data);
                $data = new SimpleXMLElement($dom->saveXML());
            }
            if(is_a($data,'SimpleXMLElement')) $data = dom_import_simplexml($data);
            $data_dom = $this->root()->importNode(($data), true);
        } return (empty($data_dom)?false:$data_dom);
    }
    public function addProcessingInstruction( $name, $value ) {         
        $dom = $this->root();       
        $xpath = new DOMXPath($dom); // Find the topmost element of the domDocument 
        $first_element = $xpath->evaluate('/*[1]')->item(0);         
        $pi = $dom->createProcessingInstruction($name, $value); // Add the processing instruction before the topmost element            
        $dom->insertBefore($pi, $first_element); 
    } 
    public function addNode($node){ $this->toNode()->appendChild($node); }
    public function getNode($name){ $nodes=$this->getNodes($name); var_dump($nodes); return $nodes[0]; }
    public function getNodes($name){ return $this->xpath("//$name"); }
    public function setConfig($version,$charset) { 
        $dom=$this->root();
        $dom->version=$version;
        $dom->encoding=$charset;
    }
    public function canonicalizeData($canonicalizationAlgorithm, $xpath = null, $nsPrefixes = null){ 
        $exclusive = false; $withComments = false; $cd=$this->cd(); 
        switch ($canonicalizationAlgorithm){
            case $cd['C14N']:
                $exclusive = false; $withComments = false; break;
            case $cd['C14N_COMMENTS']:
                $exclusive = false; $withComments = true; break;
            case $cd['EXC_C14N']:
                $exclusive = true; $withComments = false; break;
            case $cd['EXC_C14N_COMMENTS']:
                $exclusive = true;$withComments = true; break;            
        }
        return $this->toNode()->C14N($exclusive, $withComments, $xpath, $nsPrefixes);
    }
    public function setVersion($version='1.0') { $this->root()->version=$version; }
    public function setEncoding($charset='UTF-8') { $this->root()->encoding=$charset; }
    public function setStandalone($standalone=true) { $this->root()->xmlStandalone = $standalone;  }
   
    public function addCData($cdata_text){ $this->toNode()->appendChild($this->root()->createCDATASection($cdata_text));  } 
    public function addChildCData($name,$cdata_text){ 
        $child = $this->addChild($name); 
        $child->addCData($cdata_text); 
    } 
    public function setMainComment($str,$replace=true){
        $dom=$this->root();
        $xpath = new DOMXPath($dom);
        $comments = $xpath->query('/comment()');
        if($comments->length>0){
            $comment=$comments->item(0);            
            $comment->nodeValue=$replace?$str:$comment->nodeValue.' - '.$str;
        }else{
            $comment=$dom->createComment($str);
            $comment->nodeValue=$str;            
            $this->toNode()->parentNode->insertBefore($comment, $this->toNode());                      
        } return $comment;
    }
    function setAttribute($attribute,$value=null){
        $attr=$this->attributes(); 
        if(!empty($value)){
            if(isset($attr[$attribute])) $attr[$attribute]=$value;
            else $this->addAttribute($attribute,$value); 
        }else{
            if(isset($attr[$attribute])) unset($attr[$attribute]);
        } return $this;
    }
    function getAttribute($attribute){
        $attr=$this->attributes(); 
        if(isset($attr[$attribute]))
            return (string) $attr[$attribute];
    }
    function removeAttribute($attribute){ return $this->setAttribute($attribute); }
    function asFormatedXML($formatOutput=true,$whitespaces=false){
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = $whitespaces;
        $dom->formatOutput = $formatOutput;
        $dom->loadXML($this->asXML());
        return $dom->saveXML();
    }
    public function appendXML($insert, $formatOutput=false, $whitespaces=false){    
        $insert_dom = $this->convertToNode($insert);  //var_dump(simplexml_import_dom($insert_dom));
        return (!$insert_dom)?false:$this->toNode()->appendChild($insert_dom);        
    }
    public function toNode($node=null){ return dom_import_simplexml(empty($node)?$this:$node); }
    public function root(){ $dom_sxe=dom_import_simplexml($this); return $dom_sxe->ownerDocument; }
    public static function &createFromDom($dom) {
       $xml_doc=new XmlDoc($dom->saveXML());
       return $xml_doc;
    }
    public static function &createFromArray($root_node, $arr=array()) {
       require_once('Array2XML.php');
       $xml = Array2XML::createXML($root_node,$arr);
       $xml_doc=new XmlDoc($xml->saveXML());
       return $xml_doc;
    }
    public static function &createFromString($xml) {
        $doc = new DOMDocument();
        $doc->formatOutput = false;
        $doc->loadXml((!mb_detect_encoding($xml, 'UTF-8', true))?mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1'):$xml);
        $xml_doc=new XmlDoc($doc->saveXML());
        return $xml_doc;
    }
    public static function &createFromFile($file) {        
        $xml = file_get_contents($file); 
        return self::createFromString($xml);;
    }
    public function toArray() {    
        require_once('XML2Array.php');	
        return XML2Array::createArray($this->getAsDOMDocument());
    }   
    public static function &createFromStringToArray($xml) {
            require_once('XML2Array.php');
            return XML2Array::createArray((!mb_detect_encoding($xml, 'UTF-8', true))?mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1'):$xml);
    }
    public static function &createFromFileToArray($file) {		
            $xml = file_get_contents($file); 
            return self::createFromStringToArray($xml);
    }
} 