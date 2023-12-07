<?php

/**
 * Função que envia messagens dos hardwares khomp por e-mail
 * **/
function sendEmail($message, $email)
{

  try {

    if(file_exists('./../vendor/autoload.php')){
      require('../../vendor/autoload.php');
      require('../../vendor/phpmailer/phpmailer/src/PHPMailer.php');
    }else{
      require('vendor/autoload.php');
      require('vendor/phpmailer/phpmailer/src/PHPMailer.php');
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPSecure = 'ssl';
    $mail->Host = "email-ssl.com.br";
    $mail->Port = 465;
    $mail->SMTPAuth = true;
    $mail->Username = "robogreen@wenergy.com.br";
    $mail->Password = "W@energy#9080";
    $mail->setFrom('no-reply@wenergy.com.br', 'Mensagem Conector Khomp App - Telemetria');
    $mail->addAddress($email, mb_convert_encoding('Você', 'ISO-8859-1', 'UTF-8'));
    $mail->Subject = mb_convert_encoding('Modo Assíncrono by Denisson Anjos', 'ISO-8859-1', 'UTF-8');
    $mail->msgHTML(mb_convert_encoding($message, 'ISO-8859-1', 'UTF-8'));

    if (!$mail->send()) {
      echo 'Erro no envio de e-mail: ' . $mail->ErrorInfo;
    }
  } catch (Exception $e) {
   echo $e->getMessage();
  }
}

/**
 * Função que envia messagens dos hardwares khomp por e-mail
 * **/
function sendEmailTeltonika($message, $email)
{

  try {

    if(file_exists('./../vendor/autoload.php')){
      require('../../vendor/autoload.php');
      require('../../vendor/phpmailer/phpmailer/src/PHPMailer.php');
    }else{
      require('vendor/autoload.php');
      require('vendor/phpmailer/phpmailer/src/PHPMailer.php');
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPSecure = 'ssl';
    $mail->Host = "email-ssl.com.br";
    $mail->Port = 465;
    $mail->SMTPAuth = true;
    $mail->Username = "robogreen@wenergy.com.br";
    $mail->Password = "W@energy#9080";
    $mail->setFrom('no-reply@wenergy.com.br', 'Mensagem Device Teltonika');
    $mail->addAddress($email, mb_convert_encoding('Você', 'ISO-8859-1', 'UTF-8'));
    $mail->Subject = mb_convert_encoding('Santander Energia', 'ISO-8859-1', 'UTF-8');
    $mail->msgHTML(mb_convert_encoding($message, 'ISO-8859-1', 'UTF-8'));

    if (!$mail->send()) {
      echo 'Erro no envio de e-mail: ' . $mail->ErrorInfo;
    }
  } catch (Exception $e) {
   echo $e->getMessage();
  }
}

//sendEmail("Teste", "denisson@wenergy.com.br");
