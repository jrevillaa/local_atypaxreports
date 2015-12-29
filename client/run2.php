<?php


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

require_once($CFG->dirroot.'/report/participation/locallib.php');

//$id         = required_param('id', PARAM_INT);
$roleid     = 5;
$instanceid = optional_param('instanceid', 0, PARAM_INT);
$groupname  = (isset($_GET['grupo'])) ? $_GET['grupo'] : null;
$timefrom   = 0;
$action     = '';

$id = $DB->get_record('groups', array('name'=>$groupname));

if(is_object($id)){
    $course = $DB->get_record('course', array('id'=>$id->courseid));



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

            if( !empty(strrpos($cm->name, "cuesta de")) ){
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


    //******************************************************************************************************

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

            $ex_groups = ($groupname != null) ? $DB->get_record( "groups" , array( 'courseid' => $course->id , 'name' =>  $groupname ) ) : null;
            if(is_object($ex_groups)){
              $sqlgroups = " JOIN {groups_members} gm ON (gm.userid = u.id AND gm.groupid = " . $ex_groups->id . ") ";
            }else{
              $sqlgroups = "";
            }

        $users = array();

            $sql = "SELECT ra.userid, u.username, u.firstname, u.lastname, u.email
                      FROM {user} u
                      JOIN {role_assignments} ra ON u.id = ra.userid AND ra.contextid $relatedctxsql AND ra.roleid = :roleid
                      $sqlgroups
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


            $temp = 0;
            foreach ($instanceoptions as $key => $value) {
              $params['instanceid'] = $key;
              $sql = "SELECT ra.userid, COUNT(DISTINCT l.timecreated) AS count
                        FROM {user} u
                        JOIN {role_assignments} ra ON u.id = ra.userid AND ra.contextid $relatedctxsql AND ra.roleid = :roleid
                        $sqlgroups
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


              $count = array();
              foreach ($notes as $k => $v) {
                $users[$k]->count[$temp]['ingreso_actividad'] = ($notes[$k]->count>0) ? 'Si': 'No';
                $users[$k]->count[$temp]['nombre_actividad'] = $value;
                $tm = $DB->get_record('grade_items',array('courseid'=>$course->id,'itemname'=> $value),'id,itemname,itemmodule,gradepass');
                if(is_object($tm)){
                    $tg = $DB->get_record('grade_grades',array('itemid' => $tm->id,'userid' => $v->userid),'rawgrade');
                    if(is_object($tg)){
                      $users[$k]->count[$temp]['tipo_actividad'] = 'calificada';
                      $users[$k]->count[$temp]['nota_actividad'] = $tg->rawgrade;
                      $count[$temp] = $tg->rawgrade;
                    }else{
                      $users[$k]->count[$temp]['tipo_actividad'] = 'calificada';
                      $users[$k]->count[$temp]['nota_actividad'] = '-';
                      $count[$temp] = '-';
                    }
                    /*echo "<pre>";
                    print_r($tm);
                    echo "</pre>";*/
                    switch ($tm->itemmodule) {
                      case 'scorm':
                        $users[$k]->count[$temp]['tipo_actividad'] = 'scorm';
                        break;
                      default:
                        # code...
                        break;
                    }
                    $users[$k]->count[$temp]['nota_aprobatoria'] = $tm->gradepass;
                }else{
                  if( !empty(strrpos($value, "cuesta")) ){
                    $users[$k]->count[$temp]['tipo_actividad'] = 'dirigida';
                  }else{
                    $users[$k]->count[$temp]['nota_actividad'] = '-';
                  }
                    $count[$temp] = '-';
                }


              }
              $temp++;
            }

            foreach ($users as $ke => $va) {
              $rsm = array();
              foreach ($va->count as $kie => $alue) {
                  if( empty(strrpos($alue['nombre_actividad'], "cuesta")) ){
                      $rsm[$kie] = $alue['nota_actividad'];
                  }
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
}else{
  echo json_encode(array('status'=>'error','message'=>'grupo no encontrado'));
}



