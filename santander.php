<?php

require_once('system/model/santander.php');
require_once('system/functions/Email.php');

//notifyThingsboard("WEBHOOK ACIONADO", "ALERT");

$santander = new Santander();

// Captura o corpo JSON enviado pelo ThingsBoard (via Rule Chain)
$get_json_data = json_decode(file_get_contents('php://input'), true);

// Para testes - remover em produção
/*
$get_json_data = '{
    "30003": 383.893798828125,
    "30005": 2.3831329345703125,
    "30007": -0.9794793128967285,
    "30009": 1584.60107421875,
    "30011": 319.36767578125,
    "30013": -1552.083984375,
    "30015": 60.050113677978516,
    "30017": 221.32330322265625,
    "30019": 221.59922790527344,
    "30021": 222.0010223388672,
    "30023": 77.24256134033203,
    "30025": 68.51966857910156,
    "30027": 77.00713348388672,
    "30029": 3011.28955078125,
    "30031": 11118.62890625,
    "30033": -15682.001953125,
    "30035": -16828.27734375,
    "30037": 10340.5546875,
    "30039": 6807.09033203125,
    "30041": 17095.578125,
    "30043": 15183.9052734375,
    "30045": 17095.662109375,
    "30047": "0.17614434659481049",
    "30049": 0.7322641015052795,
    "30051": -0.9173088669776917,
    "30053": 51866.61328125,
    "30055": 45147.63671875,
    "30057": -72173.5859375,
    "30059": -79655.4453125,
    "30061": 23.553319931030273,
    "30063": "0.008030286990106106",
    "30065": 31.547693252563477,
    "30067": 2.3403327465057373,
    "30085": 0,
    "30087": 0,
    "30089": 366.5650329589844,
    "30091": 311.7629699707031,
    "30093": 407.240234375,
    "ns": 2200920,
    "timestamp": "1744513193574"
}';

$get_json_data = json_decode($get_json_data, true);
*/

$device_id = $get_json_data["ns"];
$timestamp = convertEpochToDatetime(trim($get_json_data["timestamp"]));
$meter_info = $santander->getMeterSite($device_id);

// Conta a quantidade de chaves
if(count($get_json_data)>=2 && $get_json_data["ns"] && $get_json_data["timestamp"]){
    execute($get_json_data, $device_id, $timestamp, $meter_info, $santander);
}



function execute($get_json_data, $device_id, $timestamp, $meter_info, $santander)
{

    // Verifica se veio algo válido
    if (!$get_json_data || !isset($get_json_data['ns'])) {
        http_response_code(400);
        exit(json_encode(['error' => 'JSON inválido ou NS ausente.']));
    }



    // 1. Salva no log (pode ser via model ou direto no banco)
    $santander->saveLogRawData($get_json_data); // método fictício

    // Verifica se retornou um array válido
    if (!is_array($meter_info) || !isset($meter_info[0]) || !isset($meter_info[0]["meter_name"])) {
        http_response_code(500);
        exit(json_encode(['error' => 'Não foi possível obter informações do medidor.']));
    }

    $meter_name = $meter_info[0]["meter_name"];
    $parameters = $santander->getAllParametersMeters($meter_name);

    // Lista de parâmetros permitidos
    $allowed_parameters = ['EA+', 'ER+', 'EA', 'ER', 'DA', 'DR', 'FP', 'P0', 'S0'];

    // Array para armazenar resultados temporários
    $result = [];

    // Primeiro, processar os parâmetros para criar a estrutura intermediária
    foreach ($parameters as $param) {
        $register = $param['meter_register'];
        if (isset($get_json_data[$register])) {
            $result[] = [
                'meter_id'  => $param['meter_id'],
                'parameter' => $param['meter_parameter'],
                'register'  => $register,
                'value'     => (float) $get_json_data[$register],
            ];
        }
    }

    // Agora transformar para o formato final desejado
    $final_result = [];

    foreach ($result as $item) {
        // Verificar se o parâmetro está na lista de permitidos
        if (isset($item['parameter']) && in_array($item['parameter'], $allowed_parameters)) {
            // Construir a tag com o formato $meter_name:$device_id:$parameter
            $tag = $meter_name . ":" . $device_id . ":" . $item['parameter'];

            // Criar o item no formato desejado
            $new_item = [
                "tag" => $tag,
                "uuid" => generateUUID(),
                "value" => $item['value'],
                "ts" => $timestamp,
                "meta" => [
                    "manufacturer" => strtolower($meter_name),
                    "identification" => (int) $item['register'],
                    "model" => "",
                    "type" => "eletric-meter"
                ]
            ];

            // Adicionar ao array final
            $final_result[] = $new_item;
        }
    }

    // Substituir o array result pelo final_result
    $result = $final_result;

    $return = [];

    $return[0] = $result;

    //$json = json_encode($result, JSON_PRETTY_PRINT);

    processEnergy($device_id, $meter_name, $santander, $return);
}
/**
 * Função que executa processo de inserção dos dados de consumo no database
 */
function processEnergy($device_id, $type_device, $santander, $json)
{

    $cont = 0;

    $data_ = $json;
    //var_dump($data_);

    if (sizeof($data_) > 0) {
        print("DEU CERTO");
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

                if (strpos($tag, ":EA")) {
                    $api_energy_active_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":EA+")) {
                    $api_energy_active_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":ER")) {
                    $api_energy_reactive_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":ER+")) {
                    $api_energy_reactive_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":DA")) {
                    $api_demand_active_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":DR")) {
                    $api_demand_reactive_full = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":FP")) {
                    $api_power_factor = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":P0")) {
                    $api_active_power = floatval($data[$i]["value"]);
                } else if (strpos($tag, ":S0")) {
                    $api_apparent_power = floatval($data[$i]["value"]);
                }
            }

            //Validando fator de potência {FP = KW / KVA}
            if ($api_power_factor != 0) {
                $value_power_factor = $api_power_factor;
            } else {
                //echo "POTÊNCIA ATIVA: {$api_active_power} - POTÊNCIA APARENTE: {$api_apparent_power}" . PHP_EOL;
                if ($api_apparent_power != 0) {
                    $value_power_factor = $api_active_power / $api_apparent_power;
                }
            }

            $site_id = $santander->getSiteIDByDevice(formatDeviceID($meter_serial_number));
            $date =  convertDatetimeUTCBR(strDatetimeSearch($instante));
            $result =  $santander->verifyConsumption($site_id, $date);

            //var_dump($result);
            //echo "Site: {$site_id} e Instante: {$date}" . PHP_EOL;

            //Verifica se já existe consumo para esse [site_id] e [date]
            if (sizeof($result) <= 0) {
                //Pega o último consumo no database
                $result = $santander->getLastConsumption($site_id, $date);
                //Se houver registro no banco de dados
                if (sizeof($result) > 0) {

                    $db_energy_active_full = floatval($result[0]["v_consumption_full_energy_active_kwh"]);
                    $db_energy_reactive_full = floatval($result[0]["v_consumption_full_energy_reactive_kvarh"]);
                    $db_demand_active_full = floatval($result[0]["v_consumption_full_demand_active_kw"]);
                    $db_demand_reactive_full = floatval($result[0]["v_consumption_full_demand_reactive_kvar"]);

                    if ($db_energy_active_full != null) {

                        if ($type_device != "KHOMP") {
                            $value_energy_active = $api_energy_active_full - $db_energy_active_full;
                            $value_energy_reactive = $api_energy_reactive_full - $db_energy_reactive_full;
                            $value_demand_active = $api_demand_active_full - $db_demand_active_full;
                            $value_demand_reactive = $api_demand_reactive_full - $db_demand_reactive_full;
                        } else {
                            $value_energy_active = $api_energy_active_full;
                            $value_energy_reactive = $api_energy_reactive_full;
                            $value_demand_active = $api_demand_active_full;
                            $value_demand_reactive = $api_demand_reactive_full;
                        }

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
                $santander->registerEnergyMessage(formatDeviceID($meter_serial_number), strtotime(strDatetimeSearch($date)), $tag, $port_rs485, $array_data_message);
                notifyThingsboard("Registros de energia cadastrados no instante {$instante}", $device_id);
            } else {
                notifyThingsboard("Já existem dados de energia cadastrados no instante {$instante}", $device_id);
            }

            $result = $santander->getSiteDevice(formatDeviceID($device_id));
            $site_name = "Não identificado";
            if (sizeof($result) > 0) {
                $site_name = $result[0]["site_name"];
            }

            //if ($type_device == "KHOMP")
            echo "Item: {$cont} - Site: {$site_name} - Medidor: {$meter_serial_number} / {$meter_description} - Porta RS485: {$port_rs485} - Instante: {$date} - Energia Ativa: {$api_energy_active_full} - Energia Reativa: {$api_energy_reactive_full} - Demanda Ativa: {$api_demand_active_full} - Demanda Reativa: {$api_demand_reactive_full} - Fator de Potência: {$value_power_factor}" . PHP_EOL;

            //if ($cont == 1) {
            //var_dump($data);
            //}
        }
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

            $site_id = $santander->getSiteIDByDevice($device_id);

            $result_verify = $santander->verifyConsumption($site_id, strDatetimeSearch($date));

            if (sizeof($result_verify) > 0) {
                echo "O consumo {$energy_active_kwh} no instante {$date} já existe" . PHP_EOL;
            } else {
                echo "O consumo {$energy_active_kwh} no instante {$date} NÃO existe" . PHP_EOL;
            }
        }*/
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
function convertEpochToDatetime2($str)
{
    strlen($str) > 10 ? $str = substr($str, 0, 10) : null;
    $dt = new DateTime("@$str");
    $dt->sub(new DateInterval('PT3H'));
    $datetime = $dt->format('Y-m-d H:i:s');
    return $datetime;
}

function convertEpochToDatetime_back($timestampMs)
{
    $timestampSec = $timestampMs / 1000;
    $dt = new DateTime("@$timestampSec"); // já é UTC
    $dt->setTimezone(new DateTimeZone('UTC')); // garante UTC explícito
    return $dt->format('Y-m-d H:i:s');
}


function convertEpochToDatetime($timestampMs) {
    $timestampSec = $timestampMs / 1000;
    $dt = new DateTime("@$timestampSec"); // UTC
    $dt->setTimezone(new DateTimeZone("America/Sao_Paulo")); // Definido explicitamente
    return $dt->format('Y-m-d H:i:s');
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
    //echo str_replace("T", " ", substr(trim($str), 0, 16)) . PHP_EOL;
    return str_replace("T", " ", substr(trim($str), 0, 16));
    //return substr(trim($str), 0, 16);
}

// Função para gerar UUID v4
function generateUUID()
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function notifyThingsboard($message, $device_id)
{

    $thingsboardUrl = "https://hml.thingsboard.wenergy.com.br/api/v1/Aq2HCKw0v8hgKcGl8xWD/telemetry";

    // Corpo da requisição (JSON com telemetria de alarme)
    $payload = [
        "GatewaySantander" => [
            [
                "ts" => round(microtime(true) * 1000),
                "values" => [
                    "gateway_alarm" => true,
                    "message" => "{$device_id}:{$message}"
                ]
            ]
        ]
    ];

    // Inicializa cURL
    $ch = curl_init($thingsboardUrl);

    // Configurações da requisição
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    // Executa requisição
    $response = curl_exec($ch);

    // Verifica erros
    if (curl_errno($ch)) {
        echo 'Erro: ' . curl_error($ch);
    } else {
        echo "Resposta do ThingsBoard: " . $response;
    }

    // Finaliza cURL
    curl_close($ch);
}

//processEnergy();
