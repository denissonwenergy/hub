<?php

class Erro{

  /**
   * Função que registra erro em arquivo JSON
   */
  function registerErroJSON($error, $type, $file_name){
        // extrai a informação do ficheiro
        $string = file_get_contents($file_name);
        // faz o decode o json para uma variavel php que fica em array
        $json = json_decode($string, true);  
     
        // aqui é onde adiciona a nova linha ao ao array assignment
        $json["sigfox"][] = array(
          "type" => $type,
          "file_name" => $file_name,
          "error" => $error
        );
     
        // abre o ficheiro em modo de escrita
        $fp = fopen($file_name, 'w');
        // escreve no ficheiro em json
        fwrite($fp, json_encode($json));
        // fecha o ficheiro
        fclose($fp);
  }

  /**
  * Função que envia messagens dos hardwares sigfox por e-mail
  * **/ 
  function sendEmail($erro, $email){
      
      try{

        require('../../vendor/autoload.php');
        require('../../vendor/phpmailer/phpmailer/src/PHPMailer.php');

        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->SMTPSecure = 'ssl';
        $mail->Host = "email-ssl.com.br";
        $mail->Port = 465;
        $mail->SMTPAuth = true;
        $mail->Username = "denisson@wenergy.com.br";
        $mail->Password = "Talita@2306";
        $mail->setFrom('no-reply@wenergy.com.br', 'Error Conector SigFox App - Telemetria');
        $mail->addAddress($email, mb_convert_encoding('Você', 'ISO-8859-1', 'UTF-8'));
        $mail->Subject = 'Modo Assíncrono by Denisson Anjos';
        $mail->msgHTML(mb_convert_encoding($erro , 'ISO-8859-1', 'UTF-8'));        
      
        if (!$mail->send()) {
            echo 'Erro no envio de e-mail: ' . $mail->ErrorInfo;
        } 

      }catch (Exception $e){
        $this->registerErroJSON($e->getMessage(), 'sendEmail()', './sigfox-monitor-error.json');
      }
  }



}

?>