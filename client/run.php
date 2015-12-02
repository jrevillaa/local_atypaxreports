<?php


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');


global $CFG;
global $DB;



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
        $grade_item = orderRecords($DB->get_records('grade_items',array('courseid' => $course->id),'iteminstance','id,itemname'));

        //$todo['sections'] = $course_sections;
        foreach ($data as $key => $value) {
          $grades_temp = array();
        $course_sections = orderRecords($DB->get_records('course_sections',array('course' => $course->id),'','id,name'));
          /*echo '<pre>';
          print_r($todo);
          echo '</pre>';*/
          $temp = array(
              'course' => $course,
              'sections' => $course_sections,
          );
          for($i=0;$i<count($course_sections);$i++) {
            $grade = $DB->get_record('grade_grades',array('itemid'=>$grade_item[$i]->id, 'userid' => $value->id),'id,rawgrade');
            $grades_temp[] = (is_object($grade)) ? $grade->rawgrade : '-';
            $temp['sections'][$i]->grade_item = (is_object($grade)) ? explode('.',$grade->rawgrade)[0] : 0;
            // echo '<pre>';
            // print_r(gettype($grade));
            // print_r('=>');
            // print_r((is_object($grade)) ? $grade->rawgrade : 0);
            // echo '</pre>';
            $temp['sections'][$i]->name_item = $grade_item[$i]->itemname;

          }
          $data[$key]->course = $temp;
          if(in_array('-',$grades_temp)){
            foreach ($grades_temp as $v) {
                 ($v != '-') ? $data[$key]->avance = 'En proceso' : $data[$key]->avance = 'Sin iniciar';
            }
          }else{
            $data[$key]->estado = 'Finalizado';
          }
        }
          //echo '<pre>';
          header('Content-type: text/html; charset=UTF-8');
          echo json_encode($data);
          //print_r($data);
          //echo '</pre>';



        function orderRecords($record){
          $_temp = array();
          foreach ($record as $key => $value) {
            $_temp[] = $value;
          }
          return $_temp;
        }
