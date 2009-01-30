<?php
// phpmailer support
function dbem_send_mail($subject="no title",$body="No message specified") {

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
    $mail->IsSMTP();                    // send via SMTP
		$mail->Host = 'ssl://smtp.gmail.com:465';
		$mail->port = 465;
		$mail->SMTPAuth = TRUE;
		$mail->Username = get_option('dbem_smtp_username');  
		$mail->Password = get_option('dbem_smtp_password');  
		$mail->From = get_option('dbem_mail_sender_address');
		//$mail->SMTPDebug = true;        

	 // This HAVE TO be your gmail adress
		$mail->FromName = 'Events Manager Abuzzese'; // This is the from name in the email, you can put anything you like here
		$mail->Body = $body;
		$mail->Subject = $subject;
		$mail->AddAddress(get_option('dbem_mail_receiver_address'));  
		// This is where you put the email adress of the person you want to mail
		if(!$mail->Send()){   
			echo "Message was not sent<br/ >";   
			echo "Mailer Error: " . $mailer->ErrorInfo;
		 // print_r($mailer);
		} else {   
			echo "Message has been sent";                          
		}
}
?>