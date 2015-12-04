<?php

/*
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');


global $CFG;
global $DB;


      $idcourse = $_GET['idcourse'];
        $sql_group = "SELECT mdl_user.id, mdl_user.username, mdl_user.firstname, mdl_user.lastname, mdl_user_info_data.data, mdl_user.email, mdl_user_lastaccess.timeaccess AS tiempo_ingreso
                  FROM mdl_course
                  INNER JOIN mdl_context ON mdl_context.instanceid = mdl_course.id
                  INNER JOIN mdl_role_assignments ON mdl_context.id = mdl_role_assignments.contextid
                  INNER JOIN mdl_role ON mdl_role.id = mdl_role_assignments.roleid
                  INNER JOIN mdl_user ON mdl_user.id = mdl_role_assignments.userid
                  INNER JOIN mdl_user_info_data ON mdl_user_info_data.userid = mdl_role_assignments.userid
                  LEFT JOIN mdl_user_lastaccess ON mdl_user_lastaccess.userid = mdl_user.id
                  AND mdl_user_lastaccess.courseid = mdl_course.id
                  WHERE mdl_role.id =5
                  AND mdl_course.id =$idcourse";
        $data = orderRecords($DB->get_records_sql($sql_group));


        $course = $DB->get_record('course',array('id'=>$idcourse),'id,fullname');

        $grade_item = orderRecords($DB->get_records('grade_items',array('courseid' => $course->id),'iteminstance','id,itemname'));


        foreach ($data as $key => $value) {
          $grades_temp = array();
          $course_sections = orderRecords($DB->get_records('course_sections',array('course' => $course->id),'','id,name'));

          $temp = array(
              'course' => $course,
              'sections' => $course_sections,
          );
          for($i=0;$i<count($course_sections);$i++) {
            $grade = $DB->get_record('grade_grades',array('itemid'=>$grade_item[$i]->id, 'userid' => $value->id),'id,rawgrade');
            $grades_temp[] = (is_object($grade)) ? $grade->rawgrade : '-';
            $temp['sections'][$i]->grade_item = (is_object($grade)) ? explode('.',$grade->rawgrade)[0] : 0;

            $temp['sections'][$i]->name_item = $grade_item[$i]->itemname;

          }
          $data[$key]->course = $temp;
          if(in_array('-',$grades_temp)){
            foreach ($grades_temp as $v) {
                 ($v != '-') ? $data[$key]->avance = 'En proceso' : $data[$key]->avance = 'Sin iniciar';
            }
          }else{
            $data[$key]->avance = 'Finalizado';
          }
        }
          $tabla = '<table border="1">';
          foreach ($data as $fila) {
            $tabla .= "<tr>";
            $tabla .= "<td>";
            $tabla .= $fila->id;
            $tabla .= "</td>";
            $tabla .= "<td>";
            $tabla .= $fila->username;
            $tabla .= "</td>";
            $tabla .= "<td>";
            $tabla .= $fila->lastname;
            $tabla .= "</td>";
            $tabla .= "<td>";
            $tabla .= $fila->data;
            $tabla .= "</td>";
            $tabla .= "<td>";
            $tabla .= $fila->email;
            $tabla .= "</td>";
            $tabla .= "<td>";
            $tabla .= $fila->tiempo_ingreso;
            $tabla .= "</td>";
            $tabla .= "<td>";
            $tabla .= $fila->course['course']->fullname;
            $tabla .= "</td>";
            $tabla .= "<td>";

            foreach ($fila->course['sections'] as $sction) {
              $tabla .= "</td>";
              $tabla .= "<td>";
              $tabla .= $sction->name_item;
              $tabla .= "</td>";
              $tabla .= "<td>";
              $tabla .= $sction->grade_item;
            }
            $tabla .= "</td>";
            $tabla .= "<td>";
            $tabla .= $fila->avance;
            $tabla .= "</td>";
            $tabla .= "</tr>";
          }
          $tabla .= "</table>";

          echo $tabla;
          echo '<pre>';
          //header('Content-type: text/html; charset=UTF-8');
          //echo json_encode($data);
          print_r($data);

          echo '</pre>';



        function orderRecords($record){
          $_temp = array();
          foreach ($record as $key => $value) {
            $_temp[] = $value;
          }
          return $_temp;
        }
*/

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

require_once($CFG->dirroot.'/report/participation/locallib.php');

$course = $DB->get_record('course',array('id'=>2));;



// TODO: we need a new list of roles that are visible here.
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
    /*echo "<pre>";
    print_r($modinfo);
    echo "</pre>";*/
    $instances = array();
    foreach ($modinfo->instances[$module->name] as $cm) {

        $instances[$cm->id] = $cm->name;
    }

    $instanceoptions[] = $instances;
}

echo "<pre>";
print_r($instanceoptions);
echo "</pre>";




/******************************************************************************************************/
$id         = required_param('id', PARAM_INT); // course id.
$roleid     = 5; // which role to show
$instanceid = optional_param('instanceid', 0, PARAM_INT); // instance we're looking at.
$timefrom   = 0; // how far back to look...
$action     = 'view';

$course = $DB->get_record('course', array('id'=>$id));



//require_login($course);
$context = context_course::instance($course->id);
require_capability('report/participation:view', $context);


$actionoptions = report_participation_get_action_options();


$logtable = report_participation_get_log_table_name();



if (!empty($instanceid) && !empty($roleid)) {

    // We want to query both the current context and parent contexts.
    list($relatedctxsql, $params) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');
    $params['roleid'] = $roleid;
    $params['instanceid'] = $instanceid;
    $params['timefrom'] = $timefrom;



        list($crudsql, $crudparams) = report_participation_get_crud_sql($action);
        $params = array_merge($params, $crudparams);

    $usernamefields = get_all_user_name_fields(true, 'u');
    $users = array();


        $sql = "SELECT ra.userid as 'usuario', $usernamefields, u.idnumber, COUNT(DISTINCT l.timecreated) AS count
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
        $groupbysql = " GROUP BY ra.userid, $usernamefields, u.idnumber";

        $params['edulevel'] = core\event\base::LEVEL_PARTICIPATING;
        $params['contextlevel'] = CONTEXT_MODULE;

        $sql .= $groupbysql;
    echo '<pre>';
    print_r($params);
    echo '</pre>';

        echo $sql;
        if ($u = $DB->get_records_sql($sql, $params)) {
            if (empty($users)) {
                $users = $u;
            } else {
                foreach ($u as $key => $value) {
                    if (isset($users[$key]) && !empty($users[$key]->count)) {
                        if ($value->count) {
                            $users[$key]->count += $value->count;
                        }
                    } else {
                        $users[$key] = $value;
                    }
                }
            }
            unset($u);
            $u = null;
        }



      echo "<pre>";
      print_r($users);
      echo "</pre>";



}
