<?php

/*******************************************************************************
* Script :  PDF_Code128
* Version : 1.0
* Date :    20/05/2008
* Auteur :  Roland Gautier
*
* Code128($x, $y, $code, $w, $h)
*     $x,$y :     angle supérieur gauche du code à barre
*     $code :     le code à créer
*     $w :        largeur hors tout du code dans l'unité courante
*                 (prévoir 5 à 15 mm de blanc à droite et à gauche)
*     $h :        hauteur hors tout du code dans l'unité courante
*
* Commutation des jeux ABC automatique et optimisée.
*******************************************************************************/

require('fpdf.php');

class PDF_Code128 extends FPDF {

var $T128;                                             // tableau des codes 128
var $ABCset="";                                        // jeu des caractères éligibles au C128
var $Aset="";                                          // Set A du jeu des caractères éligibles
var $Bset="";                                          // Set B du jeu des caractères éligibles
var $Cset="";                                          // Set C du jeu des caractères éligibles
var $SetFrom;                                          // Convertisseur source des jeux vers le tableau
var $SetTo;                                            // Convertisseur destination des jeux vers le tableau
var $JStart = array("A"=>103, "B"=>104, "C"=>105);     // Caractères de sélection de jeu au début du C128
var $JSwap = array("A"=>101, "B"=>100, "C"=>99);       // Caractères de changement de jeu

var $tablewidths;   // VARIABLE PARA LAS TABLAS
var $footerset;     // VARIABLE PARA LAS TABLAS

//____________________________ Extension du constructeur _______________________
function PDF_Code128($orientation='P', $unit='mm', $format='A4') {

    parent::FPDF($orientation,$unit,$format);

    $this->T128[] = array(2, 1, 2, 2, 2, 2);           //0 : array( )               // composition des caractères
    $this->T128[] = array(2, 2, 2, 1, 2, 2);           //1 : array(!)
    $this->T128[] = array(2, 2, 2, 2, 2, 1);           //2 : array(")
    $this->T128[] = array(1, 2, 1, 2, 2, 3);           //3 : array(#)
    $this->T128[] = array(1, 2, 1, 3, 2, 2);           //4 : array($)
    $this->T128[] = array(1, 3, 1, 2, 2, 2);           //5 : array(%)
    $this->T128[] = array(1, 2, 2, 2, 1, 3);           //6 : array(&)
    $this->T128[] = array(1, 2, 2, 3, 1, 2);           //7 : array(')
    $this->T128[] = array(1, 3, 2, 2, 1, 2);           //8 : array(()
    $this->T128[] = array(2, 2, 1, 2, 1, 3);           //9 : array())
    $this->T128[] = array(2, 2, 1, 3, 1, 2);           //10 : array(*)
    $this->T128[] = array(2, 3, 1, 2, 1, 2);           //11 : array(+)
    $this->T128[] = array(1, 1, 2, 2, 3, 2);           //12 : array(,)
    $this->T128[] = array(1, 2, 2, 1, 3, 2);           //13 : array(-)
    $this->T128[] = array(1, 2, 2, 2, 3, 1);           //14 : array(.)
    $this->T128[] = array(1, 1, 3, 2, 2, 2);           //15 : array(/)
    $this->T128[] = array(1, 2, 3, 1, 2, 2);           //16 : array(0)
    $this->T128[] = array(1, 2, 3, 2, 2, 1);           //17 : array(1)
    $this->T128[] = array(2, 2, 3, 2, 1, 1);           //18 : array(2)
    $this->T128[] = array(2, 2, 1, 1, 3, 2);           //19 : array(3)
    $this->T128[] = array(2, 2, 1, 2, 3, 1);           //20 : array(4)
    $this->T128[] = array(2, 1, 3, 2, 1, 2);           //21 : array(5)
    $this->T128[] = array(2, 2, 3, 1, 1, 2);           //22 : array(6)
    $this->T128[] = array(3, 1, 2, 1, 3, 1);           //23 : array(7)
    $this->T128[] = array(3, 1, 1, 2, 2, 2);           //24 : array(8)
    $this->T128[] = array(3, 2, 1, 1, 2, 2);           //25 : array(9)
    $this->T128[] = array(3, 2, 1, 2, 2, 1);           //26 : array(:)
    $this->T128[] = array(3, 1, 2, 2, 1, 2);           //27 : array(;)
    $this->T128[] = array(3, 2, 2, 1, 1, 2);           //28 : array(<)
    $this->T128[] = array(3, 2, 2, 2, 1, 1);           //29 : array(=)
    $this->T128[] = array(2, 1, 2, 1, 2, 3);           //30 : array(>)
    $this->T128[] = array(2, 1, 2, 3, 2, 1);           //31 : array(?)
    $this->T128[] = array(2, 3, 2, 1, 2, 1);           //32 : array(@)
    $this->T128[] = array(1, 1, 1, 3, 2, 3);           //33 : array(A)
    $this->T128[] = array(1, 3, 1, 1, 2, 3);           //34 : array(B)
    $this->T128[] = array(1, 3, 1, 3, 2, 1);           //35 : array(C)
    $this->T128[] = array(1, 1, 2, 3, 1, 3);           //36 : array(D)
    $this->T128[] = array(1, 3, 2, 1, 1, 3);           //37 : array(E)
    $this->T128[] = array(1, 3, 2, 3, 1, 1);           //38 : array(F)
    $this->T128[] = array(2, 1, 1, 3, 1, 3);           //39 : array(G)
    $this->T128[] = array(2, 3, 1, 1, 1, 3);           //40 : array(H)
    $this->T128[] = array(2, 3, 1, 3, 1, 1);           //41 : array(I)
    $this->T128[] = array(1, 1, 2, 1, 3, 3);           //42 : array(J)
    $this->T128[] = array(1, 1, 2, 3, 3, 1);           //43 : array(K)
    $this->T128[] = array(1, 3, 2, 1, 3, 1);           //44 : array(L)
    $this->T128[] = array(1, 1, 3, 1, 2, 3);           //45 : array(M)
    $this->T128[] = array(1, 1, 3, 3, 2, 1);           //46 : array(N)
    $this->T128[] = array(1, 3, 3, 1, 2, 1);           //47 : array(O)
    $this->T128[] = array(3, 1, 3, 1, 2, 1);           //48 : array(P)
    $this->T128[] = array(2, 1, 1, 3, 3, 1);           //49 : array(Q)
    $this->T128[] = array(2, 3, 1, 1, 3, 1);           //50 : array(R)
    $this->T128[] = array(2, 1, 3, 1, 1, 3);           //51 : array(S)
    $this->T128[] = array(2, 1, 3, 3, 1, 1);           //52 : array(T)
    $this->T128[] = array(2, 1, 3, 1, 3, 1);           //53 : array(U)
    $this->T128[] = array(3, 1, 1, 1, 2, 3);           //54 : array(V)
    $this->T128[] = array(3, 1, 1, 3, 2, 1);           //55 : array(W)
    $this->T128[] = array(3, 3, 1, 1, 2, 1);           //56 : array(X)
    $this->T128[] = array(3, 1, 2, 1, 1, 3);           //57 : array(Y)
    $this->T128[] = array(3, 1, 2, 3, 1, 1);           //58 : array(Z)
    $this->T128[] = array(3, 3, 2, 1, 1, 1);           //59 : array(array()
    $this->T128[] = array(3, 1, 4, 1, 1, 1);           //60 : array(\)
    $this->T128[] = array(2, 2, 1, 4, 1, 1);           //61 : array())
    $this->T128[] = array(4, 3, 1, 1, 1, 1);           //62 : array(^)
    $this->T128[] = array(1, 1, 1, 2, 2, 4);           //63 : array(_)
    $this->T128[] = array(1, 1, 1, 4, 2, 2);           //64 : array(`)
    $this->T128[] = array(1, 2, 1, 1, 2, 4);           //65 : array(a)
    $this->T128[] = array(1, 2, 1, 4, 2, 1);           //66 : array(b)
    $this->T128[] = array(1, 4, 1, 1, 2, 2);           //67 : array(c)
    $this->T128[] = array(1, 4, 1, 2, 2, 1);           //68 : array(d)
    $this->T128[] = array(1, 1, 2, 2, 1, 4);           //69 : array(e)
    $this->T128[] = array(1, 1, 2, 4, 1, 2);           //70 : array(f)
    $this->T128[] = array(1, 2, 2, 1, 1, 4);           //71 : array(g)
    $this->T128[] = array(1, 2, 2, 4, 1, 1);           //72 : array(h)
    $this->T128[] = array(1, 4, 2, 1, 1, 2);           //73 : array(i)
    $this->T128[] = array(1, 4, 2, 2, 1, 1);           //74 : array(j)
    $this->T128[] = array(2, 4, 1, 2, 1, 1);           //75 : array(k)
    $this->T128[] = array(2, 2, 1, 1, 1, 4);           //76 : array(l)
    $this->T128[] = array(4, 1, 3, 1, 1, 1);           //77 : array(m)
    $this->T128[] = array(2, 4, 1, 1, 1, 2);           //78 : array(n)
    $this->T128[] = array(1, 3, 4, 1, 1, 1);           //79 : array(o)
    $this->T128[] = array(1, 1, 1, 2, 4, 2);           //80 : array(p)
    $this->T128[] = array(1, 2, 1, 1, 4, 2);           //81 : array(q)
    $this->T128[] = array(1, 2, 1, 2, 4, 1);           //82 : array(r)
    $this->T128[] = array(1, 1, 4, 2, 1, 2);           //83 : array(s)
    $this->T128[] = array(1, 2, 4, 1, 1, 2);           //84 : array(t)
    $this->T128[] = array(1, 2, 4, 2, 1, 1);           //85 : array(u)
    $this->T128[] = array(4, 1, 1, 2, 1, 2);           //86 : array(v)
    $this->T128[] = array(4, 2, 1, 1, 1, 2);           //87 : array(w)
    $this->T128[] = array(4, 2, 1, 2, 1, 1);           //88 : array(x)
    $this->T128[] = array(2, 1, 2, 1, 4, 1);           //89 : array(y)
    $this->T128[] = array(2, 1, 4, 1, 2, 1);           //90 : array(z)
    $this->T128[] = array(4, 1, 2, 1, 2, 1);           //91 : array({)
    $this->T128[] = array(1, 1, 1, 1, 4, 3);           //92 : array(|)
    $this->T128[] = array(1, 1, 1, 3, 4, 1);           //93 : array(})
    $this->T128[] = array(1, 3, 1, 1, 4, 1);           //94 : array(~)
    $this->T128[] = array(1, 1, 4, 1, 1, 3);           //95 : array(DEL)
    $this->T128[] = array(1, 1, 4, 3, 1, 1);           //96 : array(FNC3)
    $this->T128[] = array(4, 1, 1, 1, 1, 3);           //97 : array(FNC2)
    $this->T128[] = array(4, 1, 1, 3, 1, 1);           //98 : array(SHIFT)
    $this->T128[] = array(1, 1, 3, 1, 4, 1);           //99 : array(Cswap)
    $this->T128[] = array(1, 1, 4, 1, 3, 1);           //100 : array(Bswap)                
    $this->T128[] = array(3, 1, 1, 1, 4, 1);           //101 : array(Aswap)
    $this->T128[] = array(4, 1, 1, 1, 3, 1);           //102 : array(FNC1)
    $this->T128[] = array(2, 1, 1, 4, 1, 2);           //103 : array(Astart)
    $this->T128[] = array(2, 1, 1, 2, 1, 4);           //104 : array(Bstart)
    $this->T128[] = array(2, 1, 1, 2, 3, 2);           //105 : array(Cstart)
    $this->T128[] = array(2, 3, 3, 1, 1, 1);           //106 : array(STOP)
    $this->T128[] = array(2, 1);                       //107 : array(END BAR)

    for ($i = 32; $i <= 95; $i++) {                                            // jeux de caractères
        $this->ABCset .= chr($i);
    }
    $this->Aset = $this->ABCset;
    $this->Bset = $this->ABCset;
    for ($i = 0; $i <= 31; $i++) {
        $this->ABCset .= chr($i);
        $this->Aset .= chr($i);
    }
    for ($i = 96; $i <= 126; $i++) {
        $this->ABCset .= chr($i);
        $this->Bset .= chr($i);
    }
    $this->Cset="0123456789";

    for ($i=0; $i<96; $i++) {                                                  // convertisseurs des jeux A & B  
        @$this->SetFrom["A"] .= chr($i);
        @$this->SetFrom["B"] .= chr($i + 32);
        @$this->SetTo["A"] .= chr(($i < 32) ? $i+64 : $i-32);
        @$this->SetTo["B"] .= chr($i);
    }
}
// R E C T A N G U L O 
function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }


// Fonction encodage et dessin du code 128 */
function Code128($x, $y, $code, $w, $h) {
    $Aguid = "";                                                                      // Création des guides de choix ABC
    $Bguid = "";
    $Cguid = "";
    for ($i=0; $i < strlen($code); $i++) {
        $needle = substr($code,$i,1);
        $Aguid .= ((strpos($this->Aset,$needle)===false) ? "N" : "O"); 
        $Bguid .= ((strpos($this->Bset,$needle)===false) ? "N" : "O"); 
        $Cguid .= ((strpos($this->Cset,$needle)===false) ? "N" : "O");
    }

    $SminiC = "OOOO";
    $IminiC = 4;

    $crypt = "";
    while ($code > "") {
                                                                                    // BOUCLE PRINCIPALE DE CODAGE
        $i = strpos($Cguid,$SminiC);                                                // forçage du jeu C, si possible
        if ($i!==false) {
            $Aguid [$i] = "N";
            $Bguid [$i] = "N";
        }

        if (substr($Cguid,0,$IminiC) == $SminiC) {                                  // jeu C
            $crypt .= chr(($crypt > "") ? $this->JSwap["C"] : $this->JStart["C"]);  // début Cstart, sinon Cswap
            $made = strpos($Cguid,"N");                                             // étendu du set C
            if ($made === false) {
                $made = strlen($Cguid);
            }
            if (fmod($made,2)==1) {
                $made--;                                                            // seulement un nombre pair
            }
            for ($i=0; $i < $made; $i += 2) {
                $crypt .= chr(strval(substr($code,$i,2)));                          // conversion 2 par 2
            }
            $jeu = "C";
        } else {
            $madeA = strpos($Aguid,"N");                                            // étendu du set A
            if ($madeA === false) {
                $madeA = strlen($Aguid);
            }
            $madeB = strpos($Bguid,"N");                                            // étendu du set B
            if ($madeB === false) {
                $madeB = strlen($Bguid);
            }
            $made = (($madeA < $madeB) ? $madeB : $madeA );                         // étendu traitée
            $jeu = (($madeA < $madeB) ? "B" : "A" );                                // Jeu en cours

            $crypt .= chr(($crypt > "") ? $this->JSwap[$jeu] : $this->JStart[$jeu]); // début start, sinon swap

            $crypt .= strtr(substr($code, 0,$made), $this->SetFrom[$jeu], $this->SetTo[$jeu]); // conversion selon jeu

        }
        $code = substr($code,$made);                                           // raccourcir légende et guides de la zone traitée
        $Aguid = substr($Aguid,$made);
        $Bguid = substr($Bguid,$made);
        $Cguid = substr($Cguid,$made);
    }                                                                          // FIN BOUCLE PRINCIPALE

    $check = ord($crypt[0]);                                                   // calcul de la somme de contrôle
    for ($i=0; $i<strlen($crypt); $i++) {
        $check += (ord($crypt[$i]) * $i);
    }
    $check %= 103;

    $crypt .= chr($check) . chr(106) . chr(107);                               // Chaine Cryptée complète

    $i = (strlen($crypt) * 11) - 8;                                            // calcul de la largeur du module
    $modul = $w/$i;

    for ($i=0; $i<strlen($crypt); $i++) {                                      // BOUCLE D'IMPRESSION
        $c = $this->T128[ord($crypt[$i])];
        for ($j=0; $j<count($c); $j++) {
            $this->Rect($x,$y,$c[$j]*$modul,$h,"F");
            $x += ($c[$j++]+$c[$j])*$modul;
        }
    }
}
/* AGREGADO X ERIK (MULTILINEA) */
var $C_widths;
var $C_aligns;
var $C_Fonts;
function SetCWidths($w){
	//Set the array of column widths
	$this->C_widths=$w;
}
function SetCAligns($a){
	//Set the array of column alignments
	$this->C_aligns=$a;
}
function SetCFonts($a){
	//Set the array of column fonts
	$this->C_Fonts=$a;
}

function Row($data,$border=false)
{
	$f1=$this->FontFamily;
	$f2=$this->FontStyle;
	$f3=$this->FontSizePt;		
	//Calculate the height of the row
	$nb=0;
	for($i=0;$i<count($data);$i++)
		$nb=max($nb,$this->NbLines($this->C_widths[$i],$data[$i]));
	$h=5*$nb;
	//Issue a page break first if needed
	$this->CheckPageBreak($h);
	//Draw the cells of the row
	for($i=0;$i<count($data);$i++)
	{
		$w=$this->C_widths[$i];
		$a=isset($this->C_aligns[$i])? $this->C_aligns[$i] : 'L';
		$f=isset($this->C_Fonts[$i]) ? $this->C_Fonts[$i] : NULL;
		//Save the current position
		$x=$this->GetX();
		$y=$this->GetY();
		//Draw the border
		if($border) $this->Rect($x,$y,$w,$h);
		//Print the text
		if(!empty($f)){ 
			$this->SetFont($f[0],$f[1],$f[2]);
		}else{
			$this->SetFont($f1,$f2,$f3);
		}
		$this->MultiCell($w,5,$data[$i],0,$a);		
		//Put the position to the right of the cell
		$this->SetXY($x+$w,$y);
	}
	//Go to the next line
	$this->SetFont($f1,$f2,$f3);
	$this->Ln($h);
}

function CheckPageBreak($h){
	//If the height h would cause an overflow, add a new page immediately
	if($this->GetY()+$h>$this->PageBreakTrigger)
		$this->AddPage($this->CurOrientation);
}

function NbLines($w,$txt){
	//Computes the number of lines a MultiCell of width w will take
	$cw=&$this->CurrentFont['cw'];
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 and $s[$nb-1]=="\n")
		$nb--;
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
		$c=$s[$i];
		if($c=="\n")
		{
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
			continue;
		}
		if($c==' ')
			$sep=$i;
		$l+=$cw[$c];
		if($l>$wmax)
		{
			if($sep==-1)
			{
				if($i==$j)
					$i++;
			}
			else
				$i=$sep+1;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
		}
		else
			$i++;
	}
	return $nl;
}
/* Fin MULTILINEA */
}   


?>