<?php
error_reporting(al);

include 'class.phpmailer.php';
include 'class.pop3.php';
 
class PHPMail{
	/**
	 * objeto phpmailer
	 * @var PHPMailer
	 */
	var $mail = null;
	
	/**
	 * pop3
	 * @var pop3
	 */
	var $pop = null;
	
	/**
	 * enables SMTP debug information (for testing)
	 * 1 = errors and messages
	 * 2 = messages only
	 * @var number
	 */
	var $SMTPDebug = 0;
	
	/**
	 * SMTP server
	 * sets GMAIL as the SMTP server
	 * @var string
	 */          
	var $Host = "ofsercont.com";
	//var $Host = "199.89.54.243";	
	
	/**
	 * set the SMTP port for the GMAIL server
	 * @var number
	 */
	var $Port = 10;
	
	/**
	 * GMAIL username
	 * @var string
	 */
	var $Username = "facturacion.electronica@ofsercont.com";
	
	/**
	 * GMAIL password
	 * @var string
	 */
	var $Password = "p.123456";
	
	/**
	 * mensaje de error o satisfacci�n
	 * @var string
	 */
	var $Msg = '';
	
	/**
	 * Crea y envia el mensaje
	 * devolviendo verdadero o falso segun el caso
	 * @param array $Destinatarios (Correo, Nombre)
	 * @param string $Asunto
	 * @param object $Mensaje puede estar en formato html o string
	 * @param array $Archivos (path, name, encoding, type)
	 * @param string $Comentario optional, comment out and test
	 * @return boolean
	 */
	function enviar($Destinatarios, $Asunto, $Mensaje, $Archivos, $Comentario, $copias = array()){
		try{	
			
			
			$this->pop = new POP3();
			$this->pop->Authorise($this->Host, $this->Port, 30, $this->Username, $this->Password, 1);
			//$this->pop->Authorise($this->Host, $this->Port, 587, $this->Username, $this->Password, 1);
			
			$this->mail = new PHPMailer(true);
			
			$this->mail->IsSMTP();

			//$this->mail->SMTPDebug  = $this->SMTPDebug;
			 
			$this->mail->Host = $this->Host;
			
			$this->mail->SetFrom($this->Username, $_SESSION['Ses_Emp_Nom']);
			
			$this->mail->Subject = $Asunto;
			$this->mail->AltBody = $Comentario;
			
			$this->mail->MsgHTML($Mensaje);
			
			foreach($Destinatarios as $row){
				$this->mail->AddAddress($row['Correo'], $row['Nombre']);
			}
			
			//foreach($copias as $row){
			//	$this->mail->AddCC($row['Correo'], $row['Nombre']);
			//}
			
			//$this->mail->AddBCC($this->Username, 'OFSERTCONT S.A.');
			
			foreach($Archivos as $row){
				$this->mail->AddAttachment($row['path'], $row['name'], $row['encoding'], $row['type']);
			}
			
			if(!$this->mail->Send()) {
			  $this->Msg = "Mailer Error: " . $this->mail->ErrorInfo;
			  return false;
			} else {
			  $this->Msg = "Message sent!";
			  return true;
			}
		} catch (phpmailerException $e) {
			echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
	}
}