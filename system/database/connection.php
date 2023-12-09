<?php

require 'pdoconfig.php';

try {
    $conn = new PDO("pgsql:host=$host;dbname=$dbname;port=$port", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //return $conn;
    //echo "Conectado a $dbname em $host com sucesso.";
} catch (PDOException $pe) {
    die("Não foi possível se conectar ao banco de dados $dbname :" . $pe->getMessage());
}

