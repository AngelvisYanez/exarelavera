#!/php -q
<?php
// Run from command prompt > php -q ws_server.php
include "phpwebsocket.php";

$server_ip="104.36.166.5";  //what is the IP of your server
$server_port=8082;  //what is the port of your server

// Extended basic WebSocket as ws_server
class ws_server extends phpWebSocket{
    function send($client,$msg){
        if(is_array($msg))
            $msg="json:".json_encode($msg);
        $msg = $this->frame_encode($msg);
        socket_write($client, $msg);
        $this->say("> ".$msg." (".strlen($msg). " bytes) \n");
     }
    function setCookie($user,$msg){
        $cookie=explode(":",$msg);
        $this->say($cookie[1]);
        $user->cookie=$cookie[1];
        //$this->getSession($user);
    }
    function processJson($user,$msg){
        $jsonString=substr($msg, 5, strlen($msg)-1);
        $this->say($jsonString);
        $json=json_decode($jsonString,true);
        if(is_array($json)||is_object($json)){
            foreach($json AS $k => $v){
                $this->say($k ." = ". $v);
            }
            switch($json['action']){
                case 'init':
                    $user->cookie=$json['cookie'];
                    $user->Emp_Cod=$json['Emp_Cod'];
                    $user->Usu_Cod=$json['Usu_Cod'];
                    $user->main=isset($json['main'])?$json['main']:false;
                    break;
                case 'closeSessions':
                    $this->closeSessions($user,$json['Usu_Cod']);
                    break;
            }

        }
    }
    /*function getSession($user){
        $context = stream_context_create(array('http'=>array('method'=>'GET','header'=>"Cookie: PHPSESSID=".$user->cookie."\r\n")));
        $ses=json_decode(file_get_contents('http://'.$this->host.'/sessionid.php?pull=session', false, $context),true);
        $user->Emp_Cod=(!empty($ses)&&isset($ses['Ses_Emp_Cod'])&&!empty($ses['Ses_Emp_Cod'])?$ses['Ses_Emp_Cod']:0);
        $user->Usu_Cod=(!empty($ses)&&isset($ses['Ses_Usu_Cod'])&&!empty($ses['Ses_Usu_Cod'])?$ses['Ses_Usu_Cod']:0);
        return (!empty($ses)?:array());
    }*/
    function logout($user){
      $this->say("user: ".$user->cookie);
      foreach($this->users as $i=>$u){
        $this->say("user $i: ".$u->cookie);
        if($user->cookie==$u->cookie  && $u->id!=$user->id){
          $this->send($u->socket, array('action'=>'logout'));
        }
      }
      $this->disconnect($user->socket);
    }
    function closeSessions($user,$Usu_Cod){
      $this->say("user: ".$user->cookie);
      foreach($this->users as $i=>$u){
        $this->say("user $i: ".$u->cookie);
        if($u->main && $u->Usu_Cod==$Usu_Cod){
          $this->send($u->socket, array('action'=>'closeSession'));
        }
      }
    }
    //Overridden process function from websocket.class.php
    function process($user,$msg){
        $c=0;
        $this->say("(user: ".$user->id.") msg> ".$msg);
        //$this->say("< ".$msg);
        switch($msg){
            case "id" :    $this->send($user->socket,$user." \r\n"); break;
            case "cookie" : $this->send($user->socket,$user->cookie." \r\n"); break;
            case "name"  : $this->send($user->socket,php_uname("n") ); break;
            case "ping" :  $this->send($user->socket,"pong"); break; //heartbeat frame reply with pong
            case "date"  : $this->send($user->socket,date("Y-m-d")); break;
            case "time"  : $this->send($user->socket,date("H:i:s")); break;
            case "main" :  $user->main=true; $this->say("Is Main"); break;
            case "session":
                $this->send($user->socket,array('id'=>$user->id,"Ses_Emp_Cod"=>$user->Emp_Cod,"Ses_Usu_Cod"=>$user->Usu_Cod,'cookie'=>$user->cookie));
                break;
            case "login" :
                foreach($this->users as $u)
                if($user->cookie==$u->cookie &&$u->id!=$user->id){
                    $this->send($u->socket, array('action'=>'login', 'Emp_Cod'=>$user->Emp_Cod, 'Usu_Cod'=>$user->Usu_Cod));
                }
                break;
            case "logout" :
                $this->logout($user);
                break;
            case "users":
                $list="User's List \r\n";
                foreach($this->users as $u)
                if($user->cookie==$u->cookie)
                   $list.="user #".++$c.". {$u->id} {$u->cookie}\r\n";
                $this->send($user->socket, $list);
                break;
            case "bye"   :
                $this->send($user->socket, "bye");
                $this->disconnect($user->socket);
                break;
            default      :
                if(substr( $msg, 0, 7 ) === "cookie:") return $this->setCookie($user,$msg);
                if(substr( $msg, 0, 5 ) === "json:") return $this->processJson($user,$msg);
                $this->send($user->socket,$msg." not understood - ".date("H:i:s") ); break;
        }
    }
}
// $master = new ws_server($server_ip,$server_port);
