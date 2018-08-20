<?php
require 'tad/lib/TADFactory.php';
require 'tad/lib/TAD.php';
require 'tad/lib/TADResponse.php';
require 'tad/lib/Providers/tadSoap.php';
require 'tad/lib/Providers/tadZKLib.php';
require 'tad/lib/Exceptions/ConnectionError.php';
require 'tad/lib/Exceptions/FilterArgumentError.php';
require 'tad/lib/Exceptions/UnrecognizedArgument.php';
require 'tad/lib/Exceptions/UnrecognizedCommand.php';

//require_once 'lib/functions.php';

use TADPHP\TADFactory;
use TADPHP\TAD;
use TAD\PHP\TADSoap;
  
$absen = new Absen();
$data = $absen->getJson();
$ipok = $absen->getIp($data);
$arr = $absen->makeArray($ipok);
$res = $absen->makeCurl($arr);
$absen->makeLog($ipok['jam'], $res);


class Absen {

  public function getJson(){
    $ch = curl_init('http://absensi.ekalloyd.id/api/mesin.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $data = json_decode($result, true);
    curl_close($ch);
    return $data;
  }

  public function getIp($data){
    foreach($data as $var){
      if(TAD::is_device_online($var['ip'])){
        switch($var['cabang']){
          case 'Jakarta' : $cbg = 0;$jam = "Asia/Jakarta";break;
          case 'Medan' : $cbg = 200;$jam = "Asia/Jakarta";break;
          case 'Pekanbaru' : $cbg = 300;$jam = "Asia/Jakarta";break;
          case 'Surabaya' : $cbg = 400;$jam = "Asia/Jakarta";break;
          case 'Semarang' : $cbg = 500;$jam = "Asia/Jakarta";break;
          case 'Jogja' : $cbg = 600;$jam = "Asia/Jakarta";break;
          case 'Makassar' : $cbg = 700;$jam = "Asia/Makassar";break;
          case 'Padang' : $cbg = 800;$jam = "Asia/Jakarta";break;
          case 'Manado' : $cbg = 1200;$jam = "Asia/Makassar";break;
        }

        $ipok = array(
              "ip"      =>  $var['ip'],
              "cabang"  =>  $cbg,
              "jam"     =>  $jam
          );
      }
    }
    return $ipok;
  }

  public function makeArray($ipok){
    $options = [
    'ip' => $ipok['ip'],   // '169.254.0.1' by default (totally useless!!!).
    'internal_id' => 1,    // 1 by default.
    'com_key' => 0,        // 0 by default.
    'description' => 'TAD1', // 'N/A' by default.
    'soap_port' => 80,     // 80 by default,
    'udp_port' => 4370,      // 4370 by default.
    'encoding' => 'utf-8'    // iso8859-1 by default.
  ];
      
    $tad_factory = new TADFactory($options);
    $tad = $tad_factory->get_instance();            
    $data = $tad->get_att_log();
    //$data = $data->filter_by_date(['start'=>'2018-08-15', 'end'=>'2018-08-15']);
    $data = $data->to_array();
    foreach($data["Row"] as $var){
      $arr[] = array
              (
                "PIN" => $ipok['cabang']+$var["PIN"],
                "DateTime" => $var["DateTime"],
                "Verified" => $var["Verified"],
                "Status" => $var["Status"],
                "WorkCode" => $var["WorkCode"]
              );
    }
    return $arr;
  }

  public function makeCurl($arr){
    $data_string = json_encode($arr);

    $ch = curl_init('http://absensi.ekalloyd.id/api/json.php');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );

    $result = curl_exec($ch);
    print_r ($result);
    $res = json_decode($result, true);
    return $res;
  }

  public function makeLog($jam, $res){
    date_default_timezone_set($jam);
    $now = array("Time"=>date("Y-m-d h:i:sa"));    
    $gabung = array_merge($res, $now);
    $txt = print_r($gabung, true);
    $myfile = file_put_contents('logs.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
  }
}

   
      
?>