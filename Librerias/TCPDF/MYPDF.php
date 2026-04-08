<?php 
ini_set("memory_limit","512M");
require_once('config/tcpdf_config.php');
require_once('tcpdf.php');

class MYPDF extends TCPDF {
	public function iniciar($title='Reporte',$subtitle='Administracion',$cabecera=null){
		// set document information
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor('EXA Software Contable');
		$this->SetTitle($title);
		$this->SetSubject($subtitle);
		$this->SetKeywords('EXA, Reporte, Contable');
		// set default header data
		$this->SetHeaderData(NULL, 0, NULL, "EXA Software Contable");
		// set header and footer fonts
		$this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		// set default monospaced font
		$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		// set margins
		$this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->SetFooterMargin(PDF_MARGIN_FOOTER);
		// set auto page breaks
		$this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$this->setImageScale(PDF_IMAGE_SCALE_RATIO);
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/spa.php')) {
			require_once(dirname(__FILE__).'/lang/spa.php');
			$this->setLanguageArray($l);
		}
		// add a page
		$this->AddPage();
		if(!empty($cabecera))
			$this->addCustomHtml($cabecera);
	}
	public function addCustomHtml($html){
		if(!empty($html))
			$this->writeHTMLCell(0, 0, '', '', $html , 0, 1, 0, true, '', true);
	}
	public function addCustomTbl($tbl){
		if(!empty($tbl))
			$this->writeHTML($tbl, true, false, false, false, '');
	}
	
}