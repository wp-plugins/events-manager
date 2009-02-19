<?php
// phpmailer support
function dbem_send_mail($subject="no title",$body="No message specified", $receiver='') {

	global $smtpsettings, $phpmailer, $cformsSettings;

	if ( file_exists(dirname(__FILE__) . '/class.phpmailer.php') && !class_exists('PHPMailer') ) {
		require_once(dirname(__FILE__) . '/class.phpmailer.php');
		require_once(dirname(__FILE__) . '/class.smtp.php');
}



		$mail = new PHPMailer();
		$mail->ClearAllRecipients();
		$mail->ClearAddresses();
		$mail->ClearAttachments();
		$mail->CharSet = 'utf-8';
        $mail->SetLanguage('en', dirname(__FILE__).'/');

		$mail->PluginDir = dirname(__FILE__).'/';
    get_option('dbem_rsvp_mail_send_method') == 'qmail' ?       
			$mail->IsQmail() :
			$mail->Mailer = get_option('dbem_rsvp_mail_send_method');                     
		$mail->Host = get_option('dbem_smtp_host');
		$mail->port = get_option('dbem_rsvp_mail_port');  
 		if(get_option('dbem_rsvp_mail_SMTPAuth') == '1')
			$mail->SMTPAuth = TRUE;
		$mail->Username = get_option('dbem_smtp_username');  
		$mail->Password = get_option('dbem_smtp_password');  
		$mail->From = get_option('dbem_mail_sender_address');
		//$mail->SMTPDebug = true;        

	 // This HAVE TO be your gmail adress
		$mail->FromName = get_option('dbem_mail_sender_name'); // This is the from name in the email, you can put anything you like here
		$mail->Body = $body;
		$mail->Subject = $subject;  
		if ($receiver == '')
			$receiver = get_option('dbem_mail_receiver_address');
	 
		$mail->AddAddress($receiver);  
		// This is where you put the email adress of the person you want to mail
		if(!$mail->Send()){   
			echo "Message was not sent<br/ >";   
			echo "Mailer Error: " . $mail->ErrorInfo;
		 // print_r($mailer);
		} else {   
		 // echo "Message has been sent";                          
		}
}
?>