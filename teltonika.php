<?php
$arquivo
    =
    fopen(
        'meuarquivo.txt',
        'w'
    );
if (
    $arquivo
    == false
)

die('Não foi possível criar o arquivo.');
require 'system/functions/Email.php';
sendEmailTeltonika('$data', 'denisson@wenergy.com.br');
//sendEmailTeltonika('$data', 'denissonanjos@hotmail.com');
//$data = json_decode(file_get_contents('php://input'), true);
//sendEmailTeltonika($data, 'denisson@wenergy.com.br');