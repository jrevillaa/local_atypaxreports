<?php


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

require_once($CFG->dirroot.'/report/participation/locallib.php');


$id         = required_param('id', PARAM_INT);
$roleid     = 5;
$instanceid = optional_param('instanceid', 0, PARAM_INT);
$timefrom   = 0;
$action     = 'view';

$course = $DB->get_record('course', array('id'=>$id));



$context = context_course::instance($course->id);
$roles = get_roles_used_in_context($context);
$guestrole = get_guest_role();
$roles[$guestrole->id] = $guestrole;
$roleoptions = role_fix_names($roles, $context, ROLENAME_ALIAS, true);

$modinfo = get_fast_modinfo($course);

$modules = $DB->get_records_select('modules', "visible = 1", null, 'name ASC');

$instanceoptions = array();
foreach ($modules as $module) {
    if (empty($modinfo->instances[$module->name])) {
        continue;
    }

    $instances = array();
    foreach ($modinfo->instances[$module->name] as $cm) {
      /*echo "<pre>";
      print_r(strrpos($cm->name, "Encuesta"));
      echo "</pre>";*/
            /*echo "<pre>";
            //print_r($cm->name);
            print_r(strrpos($cm->name, "cuesta"));
            echo "</pre>";*/
        if( !empty(strrpos($cm->name, "cuesta")) ){
          $instanceoptions[$cm->id] = $cm->name;
        }
        if($DB->record_exists('grade_items', array('courseid' => $id , 'itemname' => $cm->name))){
          $instanceoptions[$cm->id] = $cm->name;
        }
    }

}

/*echo "<pre>";
print_r($instanceoptions);
echo "</pre>";*/

/******************************************************************************************************/

$context = context_course::instance($course->id);

$actionoptions = report_participation_get_action_options();

$logtable = report_participation_get_log_table_name();

if (!empty($roleid)) {

    list($relatedctxsql, $params) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');
    $params['roleid'] = $roleid;
    $params['instanceid'] = array_keys($instanceoptions)[0];
    $params['timefrom'] = $timefrom;

        list($crudsql, $crudparams) = report_participation_get_crud_sql($action);
        $params = array_merge($params, $crudparams);

    $users = array();

        $sql = "SELECT ra.userid, u.firstname, u.lastname
                  FROM {user} u
                  JOIN {role_assignments} ra ON u.id = ra.userid AND ra.contextid $relatedctxsql AND ra.roleid = :roleid
                  LEFT JOIN {" . $logtable . "} l
                     ON l.contextinstanceid = :instanceid
                       AND l.timecreated > :timefrom" . $crudsql ."
                       AND l.edulevel = :edulevel
                       AND l.anonymous = 0
                       AND l.contextlevel = :contextlevel
                       AND (l.origin = 'web' OR l.origin = 'ws')
                       AND l.userid = ra.userid";
        $groupbysql = " GROUP BY ra.userid";

        $params['edulevel'] = core\event\base::LEVEL_PARTICIPATING;
        $params['contextlevel'] = CONTEXT_MODULE;

        $sql .= $groupbysql;

        $users = $DB->get_records_sql($sql, $params);
        $tmpo = array();
        foreach ($users as $key => $value) {
          $tmpo[] = $value;
        }
        $users = $tmpo;
        /*echo "<pre>";
        print_r($users);
        echo "</pre>";*/
        $temp = 0;
        foreach ($instanceoptions as $key => $value) {
          $params['instanceid'] = $key;
          $sql = "SELECT ra.userid, COUNT(DISTINCT l.timecreated) AS count
                    FROM {user} u
                    JOIN {role_assignments} ra ON u.id = ra.userid AND ra.contextid $relatedctxsql AND ra.roleid = :roleid
                    LEFT JOIN {" . $logtable . "} l
                       ON l.contextinstanceid = :instanceid
                         AND l.timecreated > :timefrom" . $crudsql ."
                         AND l.edulevel = :edulevel
                         AND l.anonymous = 0
                         AND l.contextlevel = :contextlevel
                         AND (l.origin = 'web' OR l.origin = 'ws')
                         AND l.userid = ra.userid";
          $groupbysql = " GROUP BY ra.userid";

          $params['edulevel'] = core\event\base::LEVEL_PARTICIPATING;
          $params['contextlevel'] = CONTEXT_MODULE;

          $sql .= $groupbysql;

          $notes = $DB->get_records_sql($sql, $params);
          $tmpo = array();
          foreach ($notes as $y => $ue) {
            $tmpo[] = $ue;
          }
          $notes = $tmpo;
          /*echo "<pre>";
          print_r($notes);
          echo "</pre>";*/

          $count = array();
          foreach ($notes as $k => $v) {
            $users[$k]->count[$temp]['ingreso_actividad'] = ($notes[$k]->count>0) ? 'Si': 'No';
            $users[$k]->count[$temp]['nombre_actividad'] = $value;
            $tm = $DB->get_record('grade_items',array('courseid'=>$course->id,'itemname'=> $value),'id,itemname');
            if(is_object($tm)){
                $tg = $DB->get_record('grade_grades',array('itemid' => $tm->id,'userid' => $v->userid),'rawgrade');
                if(is_object($tg)){
                  $users[$k]->count[$temp]['nota_actividad'] = $tg->rawgrade;
                  $count[$temp] = $tg->rawgrade;
                }else{
                  $users[$k]->count[$temp]['nota_actividad'] = '-';
                  $count[$temp] = '-';
                }
            }else{
                $users[$k]->count[$temp]['nota_actividad'] = '-';
                $count[$temp] = '-';
            }


          }
          $temp++;
        }

        foreach ($users as $ke => $va) {
          $rsm = array();
          foreach ($va->count as $kie => $alue) {
              $rsm[$kie] = $alue['nota_actividad'];
          }

          if(in_array('-', $rsm)){

            foreach ($rsm as $ue) {

              if($ue != '-'){
                $users[$ke]->estado = 'En proceso';
                break;
              }else{
                $users[$ke]->estado = 'Sin Iniciar';
              }

            }

          }else{
            $users[$ke]->estado = 'Finalizado';
          }

        }


      /*echo "<pre>";
      print_r($users);
      echo "</pre>";*/


      echo json_encode($users);




}
