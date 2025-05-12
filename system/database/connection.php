<?php

require 'pdoconfig.php';


try {
    $pdoThingsboard = new PDO("pgsql:host={$host};port={$port};dbname=thingsboard", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo "Erro ao conectar no banco thingsboard: " . $e->getMessage() . "\n";
    $pdoThingsboard = null;
}

try {
    $pdoTelemetria = new PDO("pgsql:host={$host};port={$port};dbname=telemetria", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo "Erro ao conectar no banco telemetria: " . $e->getMessage() . "\n";
    $pdoTelemetria = null;
}

return [
    'thingsboard' => $pdoThingsboard,
    'telemetria'  => $pdoTelemetria,
];
