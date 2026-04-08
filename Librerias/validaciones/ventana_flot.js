
//ahora debajo de eso, para capturar el mouse dependiendo de cada navegador web se usa el siguiente codigo:

if (document.layers) { // Netscape
    document.captureEvents(Event.MOUSEMOVE);
    document.onmousemove = captureMousePosition;
} else if (document.all) { // Internet Explorer
    document.onmousemove = captureMousePosition;
} else { // Netcsape 6
    document.onmousemove = captureMousePosition;
}

//Luego de eso, declaramos algunas variables que usaremos mas adelante que serviran para mover el cuadro

xclick = 0;
yclick = 0;
curx = 0;
cury = 0;
moving = 0;
xMousePos = 0;
yMousePos = 0;
xMousePosMax = 0;
yMousePosMax = 0;

//La siguiente funcion captura la posicion del mouse

function captureMousePosition(e) {
    if (document.layers) {
        xMousePos = e.pageX;
        yMousePos = e.pageY;
        xMousePosMax = window.innerWidth+window.pageXOffset;
        yMousePosMax = window.innerHeight+window.pageYOffset;
    } else if (document.all) {
        xMousePos = window.event.x+document.body.scrollLeft;
        yMousePos = window.event.y+document.body.scrollTop;
        xMousePosMax = document.body.clientWidth+document.body.scrollLeft;
        yMousePosMax = document.body.clientHeight+document.body.scrollTop;
    } else {
        xMousePos = e.pageX;
        yMousePos = e.pageY;
        xMousePosMax = window.innerWidth+window.pageXOffset;
        yMousePosMax = window.innerHeight+window.pageYOffset;
    }
    if(moving){ move(); }	

}

//La siguiente funcion indica la pocicion del mouse al hacer click en el cuadro para moverlo

function startmove(){
    if(moving==0){
        xclick=xMousePos;
        yclick=yMousePos;
        curx=parseInt(document.getElementById('floatwin').style.left);
        cury=parseInt(document.getElementById('floatwin').style.top);
        moving=1;
    }
}

//La siguiente funcion indica el cambio de posicion y lo mueve a esa posicion

function move(){
    wid=curx + xMousePos - xclick;
    document.getElementById('floatwin').style.left=wid + 'px';
    wid=cury + yMousePos - yclick;
    document.getElementById('floatwin').style.top=wid + 'px';
}


function SetValues(e)
{
    //var s = window.event.clientX + '*' + window.event.clientY ;
    //document.getElementById('TxtPos').innerText = s;
    
    caract1= window.Event?1:0;
    caract2= !document.documentElement.clientWidth?document.body:document.documentElement; 
    scrollx= caract2.scrollLeft;
    scrolly= caract2.scrollTop;
    x = e.clientX+scrollx; 
    y = e.clientY+scrolly; 
    document.getElementById('TxtPos').value = x+'*'+y; 
}  

//Terminado todo el bloque de script solo falta cerrarlo
