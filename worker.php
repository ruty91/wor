<?php
require('fpdf/fpdf.php');
require('PHPMailer/PHPMailerAutoload.php');
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg){
  	$exp = explode(' ',$msg->body);
echo " [x] Generuje pdf ze zdjecia: ", $exp[0], "\n";  
	$pdf = new FPDF();
	$pdf->AddPage();
	$pdf->Image($exp[0]);
	$pdfpath = explode('/',$exp[0]);
	$pdf->Output('pdf/'.$pdfpath[1].'.pdf');
sleep(2);
echo " [x] Wysylam e-email na adres: ", $exp[1], "\n"; 

$mail = new PHPMailer;
$mail->From = 'rutyubuntu@gmail.com';
$mail->FromName = 'Ruty';
$mail->addAddress($exp[1]);     // Add a recipient
$mail->addAttachment('pdf/'.$pdfpath[1].'.pdf');
$mail->Subject = 'wygenerowany pdf';
$mail->Body    = 'pdf w zalaczniku';
$mail->send();



  echo " [x] Wyslano e-mail na adres: ", $exp[1], "\n"; 
  $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);


while(count($channel->callbacks)) {
    $channel->wait();
}



$channel->close();
$connection->close();
?>


