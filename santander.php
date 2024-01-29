<?php

require_once('system/model/thingable.php');
require_once('system/functions/Email.php');

/***
 * Função que retorna dados da API Rest da Thingable!
 * {Importante: device_id = identificador único do gateway}
 */
function getDataAPI($device_id, $type_device)
{
    $token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjbGllbnQiOiJ3LWVuZXJneSIsImRvbWFpbiI6IlRoaW5nYWJsZSIsIm5hbWVzcGFjZSI6InBsYXRmb3JtIiwidXNlcklkIjoiVVNFUl8xIiwiaXNBZG1pbiI6InRydWUifQ.RdBAIutY026hOGyNDlXrvvqv7vhV_VouAyMjMOvfpig";

    $curl = curl_init();

    $headers = array(
        "Accept: application/json",
        "Authorization: Bearer {$token} ",
    );

    $param_energy_active = "{$type_device}" . "%3A" . "{$device_id}" . "%3A" . "EA%2B"; //EX: KRON:DEVICE:PARAMETRO -> ENERGIA ATIVA
    $param_energy_reactive = "{$type_device}" . "%3A" . "{$device_id}" . "%3A" . "ER%2B"; //EX: KRON:DEVICE:PARAMETRO  -> ENERGIA REATIVA
    $param_demand_active = "{$type_device}" . "%3A" . "{$device_id}" . "%3A" . "DA"; //EX: KRON:DEVICE:PARAMETRO -> DEMANDA ATIVA
    $param_demand_reactive = "{$type_device}" . "%3A" . "{$device_id}" . "%3A" . "DR"; //EX: KRON:DEVICE:PARAMETRO -> DEMANDA REATIVA
    $param_power_factor = "{$type_device}" . "%3A" . "{$device_id}" . "%3A" . "FP"; //EX: KRON:DEVICE:PARAMETRO -> FATOR DE POTÊNCIA
    $param_active_power = "{$type_device}" . "%3A" . "{$device_id}" . "%3A" . "P0"; //EX: KRON:DEVICE:PARAMETRO -> POTÊNCIA ATIVA
    $param_apparent_power = "{$type_device}" . "%3A" . "{$device_id}" . "%3A" . "S0"; //EX: KRON:DEVICE:PARAMETRO -> POTÊNCIA APARENTE

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, "https://infra-prodv0.thingable.com/rtd-ws/tag-values?tags={$param_energy_active}&tags={$param_energy_reactive}&tags={$param_demand_active}&tags={$param_demand_reactive}&tags={$param_power_factor}&tags={$param_active_power}&tags={$param_apparent_power}");
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);


      $return = [];

    $array = json_decode($response, true);
    if (array_key_exists("message", $array)) {
        //sendEmail("O device {$device_id} não possui registros no API Thingable", "tecnologia@wenergy.com.br", "Santander - API Thingable");
    } else {
        $return[0] = $array;
    }

    curl_close($curl);

    // 1. Implementar aqui código que conecta na API da thingable e retorna lista com mensagens enviadas no dia de hoje
    // 2. Ficar atento ao processo de paginação dos resultados porque é importante
    // 3. Recomenda-se criar um [array] com todos os dados, de todas as páginas da API Rest, e retornar ao final desta função
    // 4. Lembrar de implementar questão dos feriados no PONTA/FORA PONTA 
    //$return[] = ["time" => 1702243800000, "energy_active_kwh" => 0, "energy_reactive_kvarh" => 0, "demand_active_kw" => 0, "demand_reactive_kvar" => 0];
    return $return;
}

/**
 * Função que executa processo de inserção dos dados de consumo no database
 */
function processEnergy()
{
    $thingable = new Thingable();

    $results = $thingable->getAllSitesDevicesSantander();
    $cont = 0;
    foreach ($results as $result) {
        $device_id = $result["device_id"];
        $hardware_id = $result["hardware_id"];
        $type_device = null;

        switch ($hardware_id) {
            case 5:
                $type_device = "KRON";
                break;
            case 6:
                $type_device = "ABB";
                break;
            case 7:
                $type_device = "MERLIN";
                break;
            case 8:
                $type_device = "SCHN";
                break;
        }

        $data_ = getDataAPI(str_replace("M", "", $device_id), $type_device);
        //var_dump($data_);


        foreach ($data_ as $data) {

            $cont++;
            $size = sizeof($data);
            $api_energy_active_full = 0;
            $api_energy_reactive_full = 0;
            $api_demand_active_full = 0;
            $api_demand_reactive_full = 0;
            $api_power_factor = 0;
            $api_active_power = 0;
            $api_apparent_power = 0;
            $value_energy_active = 0;
            $value_energy_active_peak = 0;
            $value_energy_active_out_peak = 0;
            $value_energy_reactive = 0;
            $value_energy_reactive_peak = 0;
            $value_energy_reactive_out_peak = 0;
            $value_demand_active = 0;
            $value_demand_active_peak = 0;
            $value_demand_active_outpeak = 0;
            $value_demand_reactive = 0;
            $value_demand_reactive_peak = 0;
            $value_demand_reactive_out_peak = 0;
            $value_power_factor = 0;
            $meter_description =  null;
            $meter_serial_number = null;
            $port_rs485 = null;
            $instante =  null;
            $tag = null;


            for ($i = 0; $i < $size; $i++) {

                $tag = $data[$i]["tag"];
                $tag_ = explode(":", $tag);
                $meter_serial_number = $tag_[1];
                $meter_description = $data[$i]["meta"]["manufacturer"];
                $port_rs485 = $data[$i]["meta"]["identification"];
                $instante = $data[$i]["ts"];

                if (strpos($tag, ":EA+")) {
                    $api_energy_active_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":ER+")) {
                    $api_energy_reactive_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":DA")) {
                    $api_demand_active_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":DR")) {
                    $api_demand_reactive_full = floatval($data[$i]["value"]);
                }else if(strpos($tag, ":FP")){
                    $api_power_factor = floatval($data[$i]["value"]);
                }else if(strpos($tag, ":P0")){
                    $api_active_power = floatval($data[$i]["value"]);
                }else if(strpos($tag, ":S0")){
                    $api_apparent_power = floatval($data[$i]["value"]);
                }
            }

            //Validando fator de potência {FP = KW / KVA}
            if($api_power_factor != 0){
                $value_power_factor = $api_power_factor;
            }else{
                //echo "POTÊNCIA ATIVA: {$api_active_power} - POTÊNCIA APARENTE: {$api_apparent_power}" . PHP_EOL;
                if($api_apparent_power != 0)                
                    $value_power_factor = $api_active_power / $api_apparent_power;
            }

            $site_id = $thingable->getSiteIDByDevice(formatDeviceID($meter_serial_number));
            $date =  convertDatetimeUTCBR(strDatetimeSearch($instante));
            $result =  $thingable->verifyConsumption($site_id, $date);

            //var_dump($result);
            //echo "Site: {$site_id} e Instante: {$date}" . PHP_EOL;

            //Verifica se já existe consumo para esse [site_id] e [date]
            if (sizeof($result) <= 0) {
                //Pega o último consumo no database
                $result = $thingable->getLastConsumption($site_id, $date);
                //Se houver registro no banco de dados
                if (sizeof($result) > 0) {

                    $db_energy_active_full = floatval($result[0]["v_consumption_full_energy_active_kwh"]);
                    $db_energy_reactive_full = floatval($result[0]["v_consumption_full_energy_reactive_kvarh"]);
                    $db_demand_active_full = floatval($result[0]["v_consumption_full_demand_active_kw"]);
                    $db_demand_reactive_full = floatval($result[0]["v_consumption_full_demand_reactive_kvar"]);

                    if ($db_energy_active_full != null) {

                        $value_energy_active = $api_energy_active_full - $db_energy_active_full;
                        $value_energy_reactive = $api_energy_reactive_full - $db_energy_reactive_full;
                        $value_demand_active = $api_demand_active_full - $db_demand_active_full;
                        $value_demand_reactive = $api_demand_reactive_full - $db_demand_reactive_full;

                        if (verfiyTimePeak(strDatetimeSearch($date)) === true) {
                            //Ponta
                            $value_energy_active_peak = $value_energy_active;
                            $value_energy_reactive_peak = $value_energy_reactive;
                            $value_demand_active_peak = $value_demand_active;
                            $value_demand_reactive_peak = $value_demand_reactive;
                        } else {
                            //Fora ponta
                            $value_energy_active_out_peak = $value_energy_active;
                            $value_energy_reactive_out_peak = $value_energy_reactive;
                            $value_demand_active_outpeak = $value_demand_active;
                            $value_demand_reactive_out_peak = $value_demand_reactive;
                        }
                    }
                } else {

                    //Preencher array com registros aqui $array_data = [];

                }


                //echo "BR:" . convertDatetimeUTCBR(strDatetimeSearch($date)) . PHP_EOL;

                $array_data_message = [
                    $value_energy_active,
                    null,
                    null,
                    null,
                    null,
                    $date,
                    null,
                    $value_energy_active,
                    $value_energy_active_peak,
                    $value_energy_active_out_peak,
                    $value_energy_reactive,
                    $value_energy_reactive_peak,
                    $value_energy_reactive_out_peak,
                    $value_demand_active,
                    $value_demand_active_peak,
                    $value_demand_active_outpeak,
                    $value_demand_reactive,
                    $value_demand_reactive_peak,
                    $value_demand_reactive_out_peak,
                    $api_energy_active_full,
                    $api_energy_reactive_full,
                    $api_demand_active_full,
                    $api_demand_reactive_full,
                    $value_power_factor
                ];
    
                //FAZER INSERT AQUI
                $thingable->registerEnergyMessage(formatDeviceID($meter_serial_number), strtotime(strDatetimeSearch($date)), $tag, $port_rs485, $array_data_message);

            }

            $result = $thingable->getSiteDevice($device_id);
            $site_name = "Não identificado";
            if(sizeof($result)>0){
                $site_name = $result[0]["site_name"];
            }
            echo "Item: {$cont} - Site: {$site_name} - Medidor: {$meter_serial_number} / {$meter_description} - Porta RS485: {$port_rs485} - Instante: {$date} - Energia Ativa: {$api_energy_active_full} - Energia Reativa: {$api_energy_reactive_full} - Demanda Ativa: {$api_demand_active_full} - Demanda Reativa: {$api_demand_reactive_full} - Fator de Potência: {$value_power_factor}" . PHP_EOL;

            //if ($cont == 1) {
            //var_dump($data);
            //}
        }
        //Loop que percorre array com dados da API da Thingable
        /*foreach ($data_ as $data) {

            $date = convertEpochToDatetime($data["time"]);

            $energy_active_kwh = $data["energy_active_kwh"];
            $energy_active_peak_kwh = 0;
            $energy_active_out_peak_kwh = $energy_active_kwh;

            $energy_reactive_kvarh = $data["energy_reactive_kvarh"];
            $energy_reactive_peak_kvarh = 0;
            $energy_reactive_out_peak_kvarh = $energy_reactive_kvarh;

            $demand_active_kw = $data["demand_active_kw"];
            $demand_active_peak_kw = 0;
            $demand_active_out_peak_kw = $demand_active_kw;

            $demand_reactive_kvar = $data["demand_reactive_kvar"];
            $demand_reactive_peak_kvar = 0;
            $demand_reactive_out_peak_kvar = $demand_reactive_kvar;

            if (verfiyTimePeak($date) === true) {
                //Fora ponta
                $energy_active_out_peak_kwh = 0;
                $energy_reactive_out_peak_kvarh = 0;
                $demand_active_out_peak_kw = 0;
                $demand_reactive_out_peak_kvar = 0;
                //Ponta
                $energy_active_peak_kwh = $energy_active_kwh;
                $energy_reactive_peak_kvarh = $energy_reactive_kvarh;
                $demand_active_peak_kw = $demand_active_kw;
                $demand_reactive_peak_kvar = $demand_reactive_kvar;
            }

            $site_id = $thingable->getSiteIDByDevice($device_id);

            $result_verify = $thingable->verifyConsumption($site_id, strDatetimeSearch($date));

            if (sizeof($result_verify) > 0) {
                echo "O consumo {$energy_active_kwh} no instante {$date} já existe" . PHP_EOL;
            } else {
                echo "O consumo {$energy_active_kwh} no instante {$date} NÃO existe" . PHP_EOL;
            }
        }*/
    }
}

/**
 * Função que verifica se determinado horário, do instante de envio de dados de consumo, é ponta ou fora ponta
 * YYYY-MM-DD HH:MM:SS
 */
function verfiyTimePeak($str)
{
    $hour = substr($str, 11, 8);
    if (strtotime($hour) >= strtotime('17:45:00') && strtotime($hour) <= strtotime('20:30:00')) {
        return true;
    } else {
        return false;
    }
}
/**
 * Função que converte Epoch UNIX para Datetime
 */
function convertEpochToDatetime($str)
{
    strlen($str) > 10 ? $str = substr($str, 0, 10) : null;
    $dt = new DateTime("@$str");
    $dt->sub(new DateInterval('PT3H'));
    $datetime = $dt->format('Y-m-d H:i:s');
    return $datetime;
}

function convertDatetimeUTCBR($str)
{
    $dt = new DateTime($str);
    $dt->sub(new DateInterval('PT3H'));
    $datetime = $dt->format('Y-m-d H:i:s');
    return $datetime;
}

/**
 * Função que formata device_id para energia
 */
function formatDeviceID($str)
{
    return "M" . $str;
}

/**
 * Retorna data no padrão necessário para pesquisa no banco de dados (Ex: "2023-01-01 01:15")
 */
function strDatetimeSearch($str)
{
    echo str_replace("T", " ", substr(trim($str), 0, 16)) . PHP_EOL;
    return str_replace("T", " ", substr(trim($str), 0, 16));
    //return substr(trim($str), 0, 16);
}

processEnergy();
