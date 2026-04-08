class SocketVentanas {
    constructor ( ) {
        this.debug=false;
        this.socket=null;
        this.url = null;
        this.host= '104.36.166.5';
        this.port='8082';
        this.path=null;
        this.main=false;
        this.reloadWS=true;
        this.reloadPage=true;
        this.extraInit=undefined;
    }
    log(msg){ if(this.debug===true) console.log(msg); }
    setMain(){
        this.main=true;
    }
    reload(){
        var t=this;
        if(t.reloadWS===true) setTimeout(function(){ t.init(); }, 3000);
    }
    proccessAction(action){
        this.log("Ws-Action=> ",action);
        switch(action['action']){
            case 'login':
                if(Ses_Prs_Cod*1!==1)
                if(Ses_Emp_Cod*1!==action['Emp_Cod']*1 && Ses_Usu_Cod*1!==action['Usu_Cod']*1)
                        this.quit();
            break;
            case 'logout':
                if(Ses_Prs_Cod*1!==1)
                    if(this.reloadPage===true) this.quit();
            case 'closeSession':
                if(Ses_Prs_Cod*1!==1)
                    this.closeSession();
            break;
        }
    }
    connectDefault(){
        this.log("Ws=> Conectando to "+this.host+":"+this.port);
        this.init();
    }
    connect(url_host,url_port){
        this.host=url_host.substring(0, url_host.length - 1);
        this.port=url_port;
        this.log("Ws=> Conectando to "+this.host+":"+this.port);
        this.init();
    }
    //extraInit(){  }
    init(){
        var t=this;
        this.url="ws://"+this.host.replace('https://','').replace('http://','')+":"+this.port;
        try{
            this.socket = new WebSocket(this.url);
            this.socket.onopen    = function(msg){
                t.log("Ws=> Conectado a "+t.url+"!");
                t.send('json:'+$.jsonParser({action:'init', cookie:$.getCookie('PHPSESSID'), main:t.main, Emp_Cod:Ses_Emp_Cod, Suc_Cod:Ses_Suc_Cod, Usu_Cod:Ses_Usu_Cod}));
                if($.vv(t.extraInit))
                    t.extraInit();
            };
            this.socket.onmessage = function(msg){
                t.log("Ws-data=> "+msg.data);
                if(msg.data.substring(0, 5)==='json:'){
                    var json=$.jsonParser(msg.data.substring(5));
                    if($.vv(json['action']))
                        t.proccessAction(json);
                }
            };
            this.socket.onclose   = function(msg){
                t.log("Ws=> El Servicio cerro!");
                t.reload();
            };
        }catch(ex){
            this.log(ex);
            this.reload();
        }
    }
    send(msg){
        try{
            this.log("Ws-Send=> "+msg+"!");
            this.socket.send(msg);
        }catch(ex){
            this.log(ex);
        }
    }
    quit(){
        this.socket.close(); this.socket=null;
        this.log("Ws=> Recargando!");
        if(this.reloadPage===true) location.reload();
    }
    closeSession(){
        this.log("Ws=> Force Logout!");
        if(this.reloadPage===true&&this.main===true) window.location.href = "../LOGICA/logout.php";
    }
}