<?php


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
//require_once('prepare_statements.php');

global $CFG;
global $DB;

/*$token = (isset($_GET['token']))?$_GET['token']:'';
$obj = $DB->get_record('config',array('name'=>'atypaxreportstoken'));

    if($obj->value == $token){
        $objData = new PrepareData();
        header('Content-Type: text/plain');
        print_r($objData->send_data());
    }else{
        print_r('invalid token');
    }
    */
   /*
   SELECT mdl_course.fullname, mdl_user.username, mdl_user.firstname, mdl_user.lastname FROM mdl_course

INNER JOIN mdl_context ON mdl_context.instanceid = mdl_course.id

INNER JOIN mdl_role_assignments ON mdl_context.id = mdl_role_assignments.contextid

INNER JOIN mdl_role ON mdl_role.id = mdl_role_assignments.roleid

INNER JOIN mdl_user ON mdl_user.id = mdl_role_assignments.userid

WHERE mdl_role.id = 5 AND mdl_course.id = 2
    */
   //Este Select te devuelve todos los usuarios de un curso en específico
   //

      //echo date('Y-m-d H:i:s',1447885732);
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

        $todo = array();

        $course = $DB->get_record('course',array('id'=>$idcourse),'id,fullname');

        $todo = array(
              'course'=> $course
        );
        $course_sections = orderRecords($DB->get_records('course_sections',array('course' => $course->id),'','id,name'));
        $grade_item = orderRecords($DB->get_records('grade_items',array('courseid' => $course->id),'iteminstance','id,itemname'));

        $todo['sections'] = $course_sections;
        foreach ($data as $key => $value) {
          $grades_temp = array();
          $temp = $todo;
          for($i=0;$i<count($course_sections);$i++) {
            $grade = $DB->get_record('grade_grades',array('itemid'=>$grade_item[$i]->id, 'userid' => $value->id),'id,rawgrade');
            $temp['sections'][$i]->name_item = $grade_item[$i]->itemname;
            echo '<pre>';
            print_r($grade);
            echo '</pre>';
            $temp['sections'][$i]->grade_item = (isset($grade->rawgrade))?"-":$grade;
            $grades_temp[] = (isset($grade->rawgrade))?"-":$grade;
          }
          $value->course = $temp;
          //echo '<pre>';
          //print_r($grades_temp);
          //echo '</pre>';
          if(in_array('-',$grades_temp)){
            $value->avance = 'En proceso';
          }else{
            $value->estado = 'Finalizado';
          }
        }
        function orderRecords($record){
          $temp = array();
          foreach ($record as $key => $value) {
            $temp[] = $value;
          }
          return $temp;
        }



echo '<pre>';
/*foreach ($data as $key => $value) {
  foreach ($value->course['sections'] as $key => $val) {

    print_r($val);
  }
}*/
print_r($data);
echo '</pre>';
//header('Content-type: text/html; charset=UTF-8');
//echo json_encode($data);
