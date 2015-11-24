<?php

defined('MOODLE_INTERNAL') || die;

if($hassiteconfig && isset($ADMIN)){
    include_once realpath(dirname(__FILE__)).'/lib/base.php';
    moo_webservices::set_adminsettings($ADMIN);

}