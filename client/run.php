<?php


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once('prepare_statements.php');

global $CFG;
global $DB;

$token = (isset($_GET['token']))?$_GET['token']:'';
$obj = $DB->get_record('config',array('name'=>'atypaxreportstoken'));

    if($obj->value == $token){
        $objData = new PrepareData();
        header('Content-Type: text/plain');
        print_r($objData->send_data());
    }else{
        print_r('invalid token');
    }
    
//var_dump($CFG);




