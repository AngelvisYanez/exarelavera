<?php
use \Exception;
require_once dirname(__file__).'/Expr.php';
class AbstractModel {
    //protected $_debug=true; //protected $_db; //protected $_schema = '';
    protected $_primary = null;
    protected $_state = null;
    protected $_name = null;
    protected $_detail = array();
    protected $_fields = array();
    protected $_defaults = array();

    function getPrimary() { return $this->_primary; }
    function getState() { return $this->_state; }
    function getName() { return $this->_name; }
    function getFields() { return $this->_fields; }

    function _construct($name,$primary=null,$estado='') { $this->_name=$name; $this->_primary=$primary; $this->_state=$estado; $this->_init(); }
    function __construct($name=null,$primary=null,$estado='') { if($name!=null) $this->_construct($name,$primary,$estado); }
    public function _init(){  }
    public function select($setFrom=true,$array='*'){
        require_once dirname(__file__).'/Select.php';
        $sel=new Zend_Db_Select(null);
        if($setFrom && $this->_name!=null && !empty($this->_name)) $sel->from($this->_name,$array);
        return $sel;
    }
    public function selectOrmSql($sel){ return is_object($sel)? $sel->__toString() :''; }
    private function setField($val){ $field="";
        if(is_string($val)) $field="'$val'"; else if(is_numeric($val)) $field="$val"; else if(is_null($val)) $field="NULL";
        return $field;
    }
    private function _formatData($data,$type){
        $fData=array_merge(array(),$data); $fields=array();
        if(is_array($this->_fields)&&!empty($this->_fields)){
            foreach($this->_fields as $k=> $v) if(is_numeric($k)) $fields[$v]=null; else $fields[$k]=$v;
            $result=array_intersect_key($data,$fields);
            if($type=='I') foreach($fields as $k=>$v) if(!is_null($v)&&!$this->hasVal($result,$k) ) $result[$k]=$v;
            $fData=$result;//$type=='I'?array_merge($this->_fields,$result):$result;
        }
        if(is_array($this->_defaults)) foreach($this->_defaults as $k=>$v){ if(is_numeric($k)&&isset($fData[$v])&&is_string($fData[$v])&&trim($fData[$v])=='') $fData[$v]=null; else if(!is_numeric($k)&&!$this->hasVal($fData,$k)) $fData[$k]=$v;}
        return $this->formatData($fData,$type,$data);
    }
    public function formatData($data, $type, $allData=null){ return $data; }
    public function save($data){ return ($this->hasPK($data))? $this->update($data,true) : $this->insert($data); }
    public function update($data,$valid=false){
        $sql=""; $where=null;
        if(!isset($data['where'])){
            if(!$valid&&!$this->hasPK($data)) throw new Exception ("Error PRIMARY_KEY incompleta!");
        }else { $where=$data['where']; unset($data['where']); }
        if(is_array($data)){
            $d=$this->_formatData($data,'U'); $sql1="";
            foreach ($d as $k => $v)
                $sql1.=(($sql1==""?"":", ").$k."=".$this->setField($v));
            $sql= "UPDATE ".$this->_name." SET $sql1 WHERE ".($where!=null?$this->createWhere($where):$this->setUpPK($data)).";";
        } return $sql;
    }
    public function insert($data){
        $sql=""; $keys=array(); $values=array();
        if(is_array($data)){
            if(!isset($data[0])) $data=array($data);
            $da=$this->_formatData($data[0],'I');
            foreach ($da as $k => $v) array_push($keys,$k);
            foreach ($data as $f){
                $valAux=array();
                $d=$this->_formatData($f,'I');
                foreach ($keys as $k) array_push($valAux,$this->setField($d[$k]));
                array_push($values,"(".implode(",", $valAux).")");
            } $sql.="INSERT INTO $this->_name(".implode(",",$keys).") VALUES".implode(",",$values).";";
        } return $sql;
    }
    private function validPk($data,$k){ return isset($data[$k])&&!empty($data[$k])&&$data[$k]!=''&&$data[$k]!=null;  }
    private function hasPK($data){
        if(is_string($this->_primary)){
            if($this->validPk($data,$this->_primary)) return true;
        }else if(is_array($this->_primary)){
            foreach ($this->_primary AS $p){
                if(!$this->validPk($data, $p)) return false;
            } return true;
        } return false;
    }
    private function setUpPK($PK){
        $pkStr="";
        if(is_string($this->_primary)){
            $pkStr=$this->_name.".".$this->_primary."=".((!is_array($PK))?$PK:$PK[$this->_primary]);
        }else if(is_array($this->_primary)&&is_array($PK))
            foreach ($this->_primary AS $p){
                if(isset($PK[$p]))
                    $pkStr.=((trim($pkStr)==""?"":"AND ").$this->_name.".".$p."='".$PK[$p]."'");
                else throw new Exception ("Error PRIMARY_KEY incompleta!");
            } else throw new Exception ("Error no se entendio PRIMARY_KEY");
        if(trim($pkStr)=="") throw new Exception ("Error seteando PRIMARY_KEY");
        return $pkStr;
    }
    public function hasVal($var,$key){ return !isset($var[$key])||is_null($var[$key])||empty($var[$key])||(is_string($var[$key])&&trim($var[$key])=='')?false:true; }
    public function orNull($var,$key){ return (!$this->hasVal($var,$key))?null:$var[$key]; }
    private function isEmpty($val,$key=null){
        if(is_array($val)&&(!isset($val[0]))&&$key==null) throw new Exception ("No seteo la key a buscar en el array!");
        return is_array($val)?(isset($val[0])&&$key!=null? true:(isset($val[$key])?(empty($val[$key]) ||$val[$key]==null || $val[$key]==''):true) ):(empty($val) || $val==null || $val=="" );
    }
    public function _selectBasic($cond=null){ return $this->select(); }
    public function _selectBasicGrid($cond=null){ return $this->_selectBasic(); }
    public function selectByPk($pk){ $this->_selectBasic()." WHERE ".$this->setUpPK($pk);  }
    private function createFieldCond($k,$v){ return (is_numeric($k)?(is_numeric($v)?"$k='$v'":$v):(strpos($k,'.')==!1&&strpos($k,'(')==!1&&strpos($k,',')==!1?"`$k`":(strpos($k,'(')==!1&&strpos($k,',')==!1?"`".join("`.`",explode('.',$k))."`":$k)).(is_null($v)?" IS NULL":(in_array(strtoupper(trim("$v")),array('IS NULL','IS NOT NULL'))||$this->startsWith(strtoupper($v),'IS')||$this->startsWith(strtoupper($v),'=')||$this->startsWith(strtoupper($v),'!=')?" $v":"='$v'")) ); }
    public function createWhere($cond){
        $where="";
        if(is_array($cond)){
            if($this->isEmpty($cond, 'where')){
                foreach ($cond as $k => $v){ $waux='';
                    if(is_array($v)){ $or=array(); foreach($v as $o) $or[]=$this->createFieldCond($k, $o); $waux.="(".implode(" OR ",$or).")"; }else $waux=$this->createFieldCond($k, $v);
                    $where.=((trim($where)==""?"":" AND ").$waux);
                }
            }else $where=$this->createWhere($cond['where']);
        }else if(is_string($cond)) $where=$cond; else throw new Exception ("Error seteando WHERE");
        if(trim($where)=="") throw new Exception ("Error creando WHERE");
        return $where;
    }
    public function selectWhere($cond,$obj=false){
        $where=' '; $distinct=false; $order=' '; $group=' '; $limits=' ';
        if(is_array($cond)){
            $clean=isset($cond['clean'])?$cond['clean']:false; if(isset($cond['clean'])) unset($cond['clean']);
            $having=isset($cond['having'])?$cond['having']:false; if(isset($cond['having'])) unset($cond['having']);
            $unsetCols=isset($cond['unsetCols'])?$cond['unsetCols']:array(); if(isset($cond['unsetCols'])) unset($cond['unsetCols']);
            $unsetColsInit=isset($cond['unsetColsInit'])?$cond['unsetColsInit']:null; if(isset($cond['unsetColsInit'])) unset($cond['unsetColsInit']);
            $setWhere=(isset($cond['setWhere'])?$cond['setWhere']:array()); if(!empty($setWhere)) unset($cond['setWhere']);
            $addCols=(isset($cond['addCols'])?$cond['addCols']:array()); if(!empty($addCols)) unset($cond['addCols']);
            $join=(isset($cond['join'])?$cond['join']:array()); if(!empty($join)) unset($cond['join']);
            if(isset($cond['distinct'])){ $distinct=$cond['distinct']; unset($cond['distinct']); }
            if(!$this->isEmpty($cond,'order') && (!isset($cond['isGrid'])||(isset($cond['isGrid'])&&!$this->isEmpty($cond,'limits')))){
                $order=is_array($cond['order'])?implode (", " ,$cond['order']):$cond['order'];
                unset($cond['order']);
            }else $order='';
            if(!$this->isEmpty($cond,'group')){
                $group=is_array($cond['group'])?implode (", " ,$cond['group']):$cond['group'];
                unset($cond['group']);
            }else $group='';
            if(!$this->isEmpty($cond,'limits')){
                $limits.=$cond['limits'];
                unset($cond['limits']);
            }else $limits='';
            if(isset($cond['where'])){
                $where=(!empty($cond['where'])&&!is_null($cond['where']))?$this->createWhere($cond['where']):'';
            }else{ $where=(!empty($cond)&&!isset($cond['isGrid']))?$this->createWhere($cond):''; }
        }else{
            $where=(is_string($cond)&&trim($cond)!='')?$cond:'';
        }
        $cond['limits']=(trim($limits)!='');
        $sel=$this->isEmpty($cond,'isGrid')?($clean==true?$this->select():$this->_selectBasic($cond)):$this->_selectBasicGrid($cond);
        $selData=(is_object($sel)?$sel->getDataSelect():array('where'=>array()));

        if(is_object($sel)){
            if($distinct==true) $sel->distinct();
            if(!empty($unsetColsInit)) $sel->unsetCols(null);
            if(!empty($setWhere)){ $this->setWhere($sel, $setWhere, $cond); }
            if(!empty($join)){ foreach($join as $tbl=>$val){ if(is_string($tbl))$tbl=trim($tbl);else if(isset($val['table'])){ $tbl=$val['table']; if(is_array($val['table'])) list($firstKey)=array_keys($val['table']); } $jt=isset($val['type'])?$val['type']:'join'; $sel->$jt($tbl,isset($val['on'])?$val['on']:((is_string($tbl)?$tbl:$tbl[$firstKey]).".$val[pk]=$this->_name.$val[pk]"),(isset($val['cols'])?$val['cols']:array())); } }
            if(trim($where)!='') $sel->where($where,null);
            if(trim($group)!='') $sel->group($group);
            if(trim($order)!='') $sel->order($order);
            if(trim($having)!='') $sel->having($having);
            if(!empty($unsetCols)) $sel->unsetCols(is_array($unsetCols)?$unsetCols:null);
            if(!empty($addCols)){ foreach($addCols as $tbl=>$cols){ $sel->addCols(trim($tbl)==''?null:trim($tbl),$cols); } }
            if(!$cond['limits']&&!$this->isEmpty($cond,'isGrid')) $this->setCount($sel);
            $sql=$this->getSqlString($sel)."\n$limits;";
        }else{
            if(trim($where)!='') if(empty($setWhere) && count($selData['where'])==0){ $where=" WHERE ".$where; }else{ $where=" AND ($where)"; }
            if(!empty($setWhere)) $where=$this->setWhere($sel, $setWhere, $cond).$where;
            if(trim($group)!='') $group=" GROUP BY $group";
            if(trim($order)!='') $order=" ORDER BY $order";
            $sql=$sel."\n$where\n$group\n$order\n$limits;";
        } return $obj?$sel:$sql;
    }
    public function setCount($sel){ $count=array('total'=>$this->expr('COUNT(*)')); $sel->unsetCols()->addCols(null,$count); if(!empty($this->_detail)){ $data=$sel->getDataSelect(); $detail=""; foreach($this->_detail as $d){ if(array_key_exists($d,$data['from'])){ $detail=$d; break; } } if($detail!=""){ $total=$this->select(false)->from(array('tbl'=>$this->expr('('.$this->getSqlString($sel).')')),$count); $sel->setDataSelect($total->getDataSelect()); } } }
    public function selectCountWhere($cond){ $sel=$this->selectWhere($cond,true); $this->setCount($sel); return $sel; }
    public function getSqlString($sel){ return is_object($sel)?$sel->__toString()." ":$sel; } function toStr($sel){ return $this->getSqlString($sel); }
    public function setEstado($pk,$est){  if(empty($this->_state) || $this->_state==null) throw new Exception ("Error seteando campo estado"); return "UPDATE ".$this->_name." SET ".$this->_state."='".$est."'"." WHERE ".$this->setUpPK($pk).";"; }
    public function setInactive($pk){ return $this->setEstado($pk,'I'); }
    public function setActive($pk){ return $this->setEstado($pk,'A'); }

    public function deleteByPk($pk){ return "DELETE FROM ".$this->_name." WHERE ".$this->setUpPK($pk).";"; }
    public function deleteWhere($cond){
        if(empty($cond)) throw new Exception ("Error condicion vacia en DELETE WHERE");
        return "DELETE FROM ".$this->_name." WHERE ".$this->createWhere($cond).";";
    }
    public function startsWith($str,$init){ return strncmp($str,$init,strlen($init))===0; }
    public function subParam($str,$init){ return strlen($str)<=strlen($init)?'':substr($str,strlen($init)); }
    public function setWhere($sel, $where, $cond ){
        if(is_string($where)) $where=array($where);
        $sql=" WHERE ";
        foreach ($where as $wh){
            $fun=(is_numeric($wh)?'sqlByNumero':'sqlByNombre');
            if(is_object($sel))
                $this->$fun($wh, $sel, $cond);
            else
               $sql.= $this->$fun($wh,$cond);
        } return $sql;
    }
    public function sqlByParams($id,$sql,$Params=array()){
        if(strtoupper($id)=='DISTINCT'){ $sql->distinct(); return true; }
        foreach($Params AS $p=>$s){
            if($this->startsWith($id,$p)){
                if($this->startsWith($p,'is')||$this->startsWith($p,'notIs')||$this->startsWith($p,'has')||$this->startsWith($p,'notHas')){ $sql->where($s,$this->subParam($id,$p)); return true; }else
                if($this->startsWith($p,'orderBy')){ $sql->order($s); return true; }else
                if($this->startsWith($p,'groupBy')){ $sql->group($s); return true; }
            }
        } throw new Exception("No se ha declarado la funcion $id!"); }
    public function sqlByNombre($id,$Par_Sql,$cond=null){ throw new Exception("No se ha declarado la funcion sqlByNombre!"); }
    public function sqlByNumero($id,$Par_Sql,$cond=null){ throw new Exception("No se ha declarado la funcion sqlByNumero!"); }
    public function expr($str){ return new Zend_Db_Expr($str); }
    public function castDecimal($str){ return "CAST( $str AS DECIMAL(20,2))"; }
    public function concat($arr,$pfx='',$glue=' '){ $ini="CAST( ".(empty($pfx)?'':"$pfx."); $end=" AS CHAR)"; $fields= implode($end.($glue==null||$glue==''?'':",'$glue',").$ini, $arr); return "CONCAT( $ini$fields$end )"; }
}