<?php
require_once('system/model/thingable.php');

/***
 * Função que retorna dados da API Rest da Thingable!
 * {Importante: device_id = identificador único do gateway}
 */
function getDataAPI(){
    // 1. Implementar aqui código que conecta na API da thingable e retorna lista com mensagens enviadas no dia de hoje
    // 2. Ficar atento ao processo de paginação dos resultados porque é importante
    // 3. Recomenda-se criar um [array] com todos os dados, de todas as páginas da API Rest, e retornar ao final deste função
    return ["gateway_serial_number" => 0, "meter_serial_number" => 0, "time" => 0, "energy_active_kwh" => 0, "energy_reactive_kvarh" => 0, "demand_active_kw" => 0, "demand_reactive_kvar" => 0];
}

/**
 * Função que executa processo de inserção dos dados de consumo no database
 */
function processEnergy(){
    $thingable = new Thingable();
    $data_ = getDataAPI();
    foreach($data_ as $data){
        $device_id = formatDeviceID($data["gateway_serial_number"]);
        $meter_serial_number = $data["meter_serial_number"];
        $datetime = convertEpochToDatetime($data["meter_serial_number"]);
        $energy_active_kwh = $data["energy_active_kwh"];
        //$energy_active_peak_kwh = 
        //$energy_active_out_peak_kwh = 
        $energy_reactive_kvarh = $data["energy_reactive_kvarh"];
        //$energy_reactive_peak_kvarh = 
        //$energy_reactive_out_peak_kvarh = 
        $demand_active_kw = $data["demand_active_kw"];
        //$demand_active_peak_kw = 
        //$demand_active_out_peak_kw = 
        $demand_reactive_kvar = $data["demand_reactive_kvar"];
        //$demand_reactive_peak_kvar =
        //$demand_reactive_out_peak_kvar =
        $site_id = $thingable->getSiteIDByDevice($device_id);
    }
    
    // 1. 
}

/**
 * Função que converte Epoch UNIX para Datetime
 */
function convertEpochToDatetime($str){   
    $dt = new DateTime("@$str");
    $dt->sub(new DateInterval('PT3H'));
    $datetime = $dt->format('Y-m-d H:i:s');
    return $datetime;
}

/**
 * Função que formata device_id para energia
 */
function formatDeviceID($str){
    return "G" . $str;
}

