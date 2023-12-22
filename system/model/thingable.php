<?php

date_default_timezone_set('America/Sao_Paulo');
require './system/functions/erro.php';

class Thingable
{

    protected $conn_class;

    /**
     * Construtor da classe SigFox
     */
    public function __construct()
    {
        try {
            require './system/database/connection.php';
            $this->conn_class = $conn;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO __construct()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (__construct()): ',  $e->getMessage(), "\n";
        }
    }

    /**
     * Função que fecha conexão com DB
     */
    public function closeConnection()
    {
        try {
            $this->conn_class = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO closeConnection()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (closeConnection()): ',  $e->getMessage(), "\n";
        }
    }



    /**
     * Função que verifica sites ativos de energia vinculados ao Santander
     */
    function getAllSitesDevicesSantander()
    {
        try {
            $pdo = $this->conn_class;
            $sql  = "select * from telemetria.view_device_site_energy vdse " .
            " inner join telemetria.branch_site bs on(vdse.site_id = bs.fk_site_id) " .
            " inner join telemetria.branch b on(bs.fk_branch_id = b.branch_id) " .
            " inner join telemetria.client_branch cb on(b.branch_id = cb.fk_branch_id) " .
            " inner join telemetria.client c on(cb.fk_client_id = c.client_id) " .
            " inner join telemetria.device_hardware dh on(vdse.device_id = dh.fk_device_id) " .
			" inner join telemetria.hardware h on(dh.fk_hardware_id = h.hardware_id) " .
            " where c.client_id = 4;";
            $stm = $pdo->prepare($sql);            
            $stm->execute();
            $result = $stm->fetchAll(PDO::FETCH_ASSOC);

            if ($result == true) {
                return $result;
            }

            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO getAllSitesDevicesSantander()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (getAllSitesDevicesSantander()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

   /**
    * Função que pesquisa dados do site tendo como parâmetros: @device_id e @device_meter_serial_number
    */
    function getDeviceEnergy($device_id, $device_meter_serial_number)
    {
        try {
            $pdo = $this->conn_class;
            $sql  = "SELECT * FROM telemetria.view_device_site_energy";
            $stm = $pdo->prepare($sql);            
            $stm->execute();
            $result = $stm->fetchAll(PDO::FETCH_ASSOC);

            if ($result == true) {
                return $result;
            }

            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO getAllSitesDevices()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (getAllSitesDevices()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
    * Função que pesquisa dados do site em relação ao processo tarifário, picos, afins pela concessionária do site
    */
    function getEnergyConfig($site_id)
    {
        try {
            $pdo = $this->conn_class;
            $sql  = "SELECT * FROM telemetria.config where config_table_name = 'site' and config_table_id = ? and config_type = ?";
            $stm = $pdo->prepare($sql);  
            $stm->bindValue(1, $site_id);
            $stm->bindValue(2, '');          
            $stm->execute();
            $result = $stm->fetchAll(PDO::FETCH_ASSOC);

            if ($result == true) {
                return $result;
            }

            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO getAllSitesDevices()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (getAllSitesDevices()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que verifica se determinado site possui consumo em referido INSTANTE (datetime)
     * na view [view_client_consumption]
     */
    public function verifyConsumption($site_id, $date)
    {
        try {
            $date = explode(" ", $date);
            $date_hours = substr(trim($date[1]), 0, 5);
            $pdo = $this->conn_class;
            $sql  = "SELECT * FROM telemetria.view_client_consumption WHERE v_site_id = ? " .
                " and to_char(v_consumption_datetime_message, 'YYYY-MM-DD') = '{$date[0]}' " .
                " and to_char(v_consumption_time_message, 'HH24:MI') = '{$date_hours}' ";
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $site_id);
            $stm->execute();
            $pdo = null;
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erro encontrado (verifyConsumption()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }


      /**
     * Função que faz delete de registro antigo de consumo do site
     */
    public function deleteOldRegisters($consumption_id)
    {
        try {
            $pdo = $this->conn_class;
            $sql = 'DELETE FROM telemetria.consumption WHERE consumption_id = ?';
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $consumption_id);
            $result = $stm->execute();
            if ($result == true) {
                return true;
            }
            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO deleteOldRegisters()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (deleteOldRegisters()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que retorna dados do site vinculados ao device
     */
    public function getSiteDevice($device_id){
        try {
            $pdo = $this->conn_class;
            $sql  = "select * from telemetria.view_site_device where site_device_end_of_link is null and device_id = ?";
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $device_id);
            $stm->execute();
            $pdo = null;
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erro encontrado (getSiteDevice()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }


    /**
     * Função que retorna dados de consumo de referido instante de um site
     * na view [view_client_consumption]
     */
    public function getLastConsumption($site_id, $date)
    {
        try {
            $date = explode(" ", $date);
            $pdo = $this->conn_class;
            $sql  = "SELECT * FROM telemetria.view_client_consumption WHERE v_site_id = ? " .
                " ORDER BY v_consumption_datetime DESC LIMIT 1  ; ";
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $site_id);
            $stm->execute();
            $pdo = null;
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erro encontrado (getLastConsumption()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que retorna [site_id] do device que está com [site_device_end_of_link] is NULL (ativo)
     */
    public function getSiteIDByDevice($device_id)
    {
        try {
            $pdo = $this->conn_class;
            $sql  = "SELECT site_id FROM telemetria.view_site_device WHERE site_device_end_of_link is null and device_id = ? LIMIT 1";
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $device_id);
            $stm->execute();
            $result = $stm->fetchAll(PDO::FETCH_ASSOC);

            if (sizeof($result) > 0) {
                return intval($result[0]["site_id"]);
            } else {
                return null;
            }

            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO getSiteIDByDevice()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (getSiteIDByDevice()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que cadastra mensagens dos devices no banco de dados (tabela: message)
     */
    public function registerEnergyMessage($device_id, $time_message, $data, $seqNumber, $array_data_message)
    {

        try {

            $pdo = $this->conn_class;
            $sql = 'INSERT INTO telemetria.message(message_date_register, message_data, message_time, message_seq_number, message_device_id) VALUES (?, ?, ?, ?, ?)';
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, 'now()');
            $stm->bindValue(2, $data);
            $stm->bindValue(3, $time_message);
            $stm->bindValue(4, $seqNumber);
            $stm->bindValue(5, $device_id);
            $result = $stm->execute();
            $getLastId = $pdo->lastInsertId();
            $pdo = null;

            if ($result == true) {
                $consumption_id = $this->registerConsumptionEnergy($getLastId, $array_data_message);
                $site_id = $this->getSiteIDByDevice($device_id);
                $this->registerSiteConsumption($site_id, $consumption_id);
                $this->registerDeviceMessage($device_id, $getLastId);
            }
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO registerEnergyMessage()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (registerEnergyMessage()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que cadastra mensagens dos devices no banco de dados (tabela: consumption)
     */
    public function registerConsumptionEnergy($lastInsertIdMessage, $array_data_message)
    {
        try {
            $pdo = $this->conn_class;
            $sql = 'INSERT INTO telemetria.consumption(consumption_date_register, consumption_value,  consumption_reverse_pules, consumption_circuit_temperature, consumption_battery_voltage, consumption_flags, consumption_datetime, consumption_type, consumption_full_value, consumption_energy_active_kwh, consumption_energy_active_peak_kwh, consumption_energy_active_out_peak_kwh, consumption_energy_reactive_kvarh, consumption_energy_reactive_peak_kvarh, consumption_energy_reactive_out_peak_kvarh, consumption_demand_active_kw, consumption_demand_active_peak_kw, consumption_demand_active_out_peak_kw, consumption_demand_reactive_kvar, consumption_demand_reactive_peak_kvar, consumption_demand_reactive_out_peak_kvar, consumption_full_energy_active_kwh, consumption_full_energy_reactive_kvarh, consumption_full_demand_active_kw, consumption_full_demand_reactive_kvar
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, 'now()');
            $stm->bindValue(2, $array_data_message[0]);
            $stm->bindValue(3, $array_data_message[1]);
            $stm->bindValue(4, $array_data_message[2]);
            $stm->bindValue(5, $array_data_message[3]);
            $stm->bindValue(6, $array_data_message[4]);
            $stm->bindValue(7, $array_data_message[5]);
            $stm->bindValue(8, 2);
            $stm->bindValue(9, $array_data_message[6]);
            $stm->bindValue(10, $array_data_message[7]);
            $stm->bindValue(11, $array_data_message[8]);
            $stm->bindValue(12, $array_data_message[9]);
            $stm->bindValue(13, $array_data_message[10]);
            $stm->bindValue(14, $array_data_message[11]);
            $stm->bindValue(15, $array_data_message[12]);
            $stm->bindValue(16, $array_data_message[13]);
            $stm->bindValue(17, $array_data_message[14]);
            $stm->bindValue(18, $array_data_message[15]);
            $stm->bindValue(19, $array_data_message[16]);
            $stm->bindValue(20, $array_data_message[17]);
            $stm->bindValue(21, $array_data_message[18]);
            $stm->bindValue(22, $array_data_message[19]);
            $stm->bindValue(23, $array_data_message[20]);
            $stm->bindValue(24, $array_data_message[21]);
            $stm->bindValue(25, $array_data_message[22]);
            $result = $stm->execute();
            $getLastId = $pdo->lastInsertId();
            $pdo = null;
            if ($result == true) {
                $this->registerMessageConsumption($lastInsertIdMessage, $getLastId);
                return intval($getLastId);
            }
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO registerConsumptionEnergy()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (registerConsumptionEnergy()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que faz insert na tabela device_message
     */
    public function registerDeviceMessage($device_id, $message_id)
    {
        try {
            $pdo = $this->conn_class;
            $sql = 'INSERT INTO telemetria.device_message(fk_device_id, fk_message_id) VALUES (?, ?)';
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $device_id);
            $stm->bindValue(2, $message_id);
            $stm->execute();
            $getLastId = $pdo->lastInsertId();
            if ($getLastId) {
                return true;
            }
            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO registerDeviceMessage()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (registerDeviceMessage()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que faz insert na tabela messagem_consumption
     */
    public function registerMessageConsumption($getIdMessage, $getIdConsumption)
    {
        try {
            $pdo = $this->conn_class;
            $sql = 'INSERT INTO telemetria.message_consumption(fk_message_id, fk_consumption_id) VALUES (?, ?)';
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $getIdMessage);
            $stm->bindValue(2, $getIdConsumption);
            $stm->execute();
            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO registerMessageConsumption()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (registerMessageConsumption()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que faz insert na tabela site_consumption
     */
    public function registerSiteConsumption($site_id, $consumption_id)
    {
        try {
            $pdo = $this->conn_class;
            $sql = 'INSERT INTO telemetria.site_consumption(fk_site_id, fk_consumption_id) VALUES (?, ?)';
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $site_id);
            $stm->bindValue(2, $consumption_id);
            $stm->execute();
            $pdo = null;
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO registerSiteConsumption()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (registerSiteConsumption()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }

    /**
     * Função que retorna último consumo de um device
     */
    public function getLastConsumptionDevice($device_id)
    {
        try {
            $pdo = $this->conn_class;
            $site_id = $this->getSiteIDByDevice($device_id);
            $sql  = "SELECT * FROM telemetria.consumption c " .
                " INNER JOIN telemetria.message_consumption mc on (c.consumption_id = mc.fk_consumption_id) " .
                " INNER JOIN telemetria.message m on(m.message_id = mc.fk_message_id) " .
                " INNER JOIN telemetria.site_consumption sc on(c.consumption_id = sc.fk_consumption_id) " .
                " WHERE m.message_device_id = ? and sc.fk_site_id = ? " .
                " ORDER BY c.consumption_id DESC LIMIT 1 ";
            $stm = $pdo->prepare($sql);
            $stm->bindValue(1, $device_id);
            $stm->bindValue(2, $site_id);
            $stm->execute();
            //$total_registros = $stm->rowCount();
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $erro = new Erro();
            $erro->registerErroJSON($e->getMessage(), 'ERRO getLastConsumptionDevice()', './sigfox-monitor-error.json');
            echo 'Erro encontrado (getLastConsumptionDevice()): ',  $e->getMessage(), "\n", $e->getLine(), "\n";
        }
    }
}
