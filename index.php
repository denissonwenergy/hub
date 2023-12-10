<?php
require_once('system/model/thingable.php');

/***
 * Função que retorna dados da API Rest da Thingable!
 * {Importante: device_id = identificador único do gateway}
 */
function getDataAPI()
{
    // 1. Implementar aqui código que conecta na API da thingable e retorna lista com mensagens enviadas no dia de hoje
    // 2. Ficar atento ao processo de paginação dos resultados porque é importante
    // 3. Recomenda-se criar um [array] com todos os dados, de todas as páginas da API Rest, e retornar ao final deste função
    $return[] = ["gateway_serial_number" => 0, "meter_serial_number" => 0, "time" => 1702243800000, "energy_active_kwh" => 0, "energy_reactive_kvarh" => 0, "demand_active_kw" => 0, "demand_reactive_kvar" => 0];
    return $return;
}

/**
 * Função que executa processo de inserção dos dados de consumo no database
 */
function processEnergy()
{
    $thingable = new Thingable();
    $data_ = getDataAPI();
    //var_dump($data_);

    foreach ($data_ as $data) {

        $device_id = formatDeviceID($data["gateway_serial_number"]);
        $meter_serial_number = $data["meter_serial_number"];
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

        if(sizeof($result_verify)>0){
            echo "O consumo {$energy_active_kwh} no instante {$date} já existe";
        }else{
            echo "O consumo {$energy_active_kwh} no instante {$date} NÃO existe";
        }
    }
}

/**
 * Função que verifica se determinado horário, do instante de envio de dados de consumo, é ponta ou fora ponta
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

/**
 * Função que formata device_id para energia
 */
function formatDeviceID($str)
{
    return "G" . $str;
}

/**
 * Retorna data no padrão necessário para pesquisa no banco de dados (Ex: "2023-01-01 01:15")
 */
function strDatetimeSearch($str){
    return substr(trim($str), 0, 16);
}

processEnergy();
