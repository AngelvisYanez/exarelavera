/**
* @fileoverview Libreria con funciones de validaciones
*
* @author Lewis Chimarro
* @version 0.1
*/

/**
* Funcion que envia del modal a la pagina principal 
*/
function ponPrefijo(pref,aux,Ite_Lar,Cat_Cod,Cat_Cdc,x,marca)
{   
	document.getElementById('Ite_Cod').value=pref;
	document.getElementById('Ite_Cod2').value=pref;
    document.getElementById('Ite_Cor').value=aux;
	document.getElementById('Ite_Lar').value=Ite_Lar;
	document.getElementById('CatCod1').value=Cat_Cod;	
	document.getElementById('Cat_Cod').value=Cat_Cod;
	document.getElementById('Cat_Cdc').value=Cat_Cdc;
	document.getElementById('Mar_Des1').value=marca;
	closeModal();				
}
