<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once('./curl.php');

class PrepareData
{

    public function __construct() {

    }
    
    public static function send_data(){
        
        global $DB, $CFG;
        
        $log = array();
        $me = new self();
        $curl = new curl;
        
        $courses;
        $users;
        $members;

        $permission_read= $DB->get_record('config',array('name'=>'wspeoplesoftcourseenable'));
        if($permission_read->value == 1){
            $xml_path = $DB->get_record('config',array('name'=>'wspeoplesoftcoursepath'));
            $xml = new SimpleXMLElement($me->prepare_path($xml_path->value,'COFR'), NULL, TRUE);
            
            
            $categories = $me->prepare_xml($xml,"rootcategories");
            //print_r($categories);
            if(empty($categories['categories'])){
                array_push($log,array('root_categories'=>'sin nuevas categorias de ciclo'));
            }else{
                array_push($log,array('root_categories'=>$me->send($categories,"categorie",$curl)));
            }
            
            $categories = $me->prepare_xml($xml,"ciclocat");
            //print_r($categories);
            if(empty($categories['categories'])){
                array_push($log,array('ciclo_categories'=>'sin nuevas categorias padre'));
            }else{
                array_push($log,array('ciclo_categories'=>$me->send($categories,"categorie",$curl)));
            }
            
            $categories = $me->prepare_xml($xml,"categories");
            if(empty($categories['categories'])){
                array_push($log,array('categories'=>'sin nuevas categorias'));
            }else{
                array_push($log,array('categories'=>$me->send($categories,"categorie",$curl)));
            }
            //$temxml = new SimpleXMLElement($me->prepare_path($xml_path->value,'MEMB'), NULL, TRUE);
            $courses = $me->prepare_xml($xml,"course");

            if(empty($courses['courses'])){
                array_push($log,array('courses'=>'sin cursos nuevos'));
            }
            else {
                array_push($log,array('courses'=>$me->send($courses,"course",$curl)));
            }



            $xml = new SimpleXMLElement($me->prepare_path($xml_path->value,'PRSN'), NULL, TRUE);

            $users = $me->prepare_xml($xml,"user");
            

            if(empty($users['users'])){
                array_push($log,array('users'=>'sin usuarios nuevos'));
            }
            else{
                array_push($log,array('users'=>$me->send($users,"user",$curl)));
            }


            $xml = new SimpleXMLElement($me->prepare_path($xml_path->value,'MEMB'), NULL, TRUE);

            $members = $me->prepare_xml($xml,"member");
            if(empty($members)) {
                array_push($log,array('members'=>'Sin nuevas matriculas'));
            }
            else {
                foreach ($members as $member) {
                    if($log['members'] == null)array_push($log,array('members'=>$me->send($member,"member",$curl)));
                    else $log['members'] = array_merge($log['members'],$me->send($member,"member",$curl));
                }
                
            }
        }
        return $log;
        
    }
    
    private function prepare_xml($xml,$type,$aux=null){
        global $DB;
        $namespaces = $xml->getNamespaces(true);
        $data = $xml->children($namespaces['bdems']); 
        
        switch($type){
         case "rootcategories":
            $rootcat = array();

            foreach($data as $singular){



                $id = explode("-",$singular->parameterSet->parameterRecord->parameterValue);
                //print_r('holaaaaaaaaaaa ' . $singular);
                $temp = new stdClass();
                $temp->name = $id[6];
                $temp->parent = 0;
                $temp->idnumber = $id[6];
                
                $valid = 0;
                foreach ($rootcat as $key => $value) {
                    if($temp->idnumber == $value->idnumber) $valid = 1;
                }
                
                $tempi = $DB->get_record('course_categories', array('idnumber'=>$temp->idnumber));

                if($valid == 0 && !is_object($tempi))$rootcat[] =$temp;
                
            }
            //print_r($rootcat);
            return array('categories'=>$rootcat);
         break;
         case "ciclocat":
            $ciclcat = array();
            
            foreach($data as $singular){
                $id = explode("-",$singular->parameterSet->parameterRecord->parameterValue);
                //print_r('holaaaa ' . $singular);
                $cic = str_split($id[2]);
                
                $temp = new stdClass();
                $temp->name = '20' . $cic[0] . $cic[1] . '-' . $cic[2];
                $temp->parent = $id[6];
                $temp->idnumber = $id[6] . '_' . $id[2];
                
                $valid = 0;
                foreach ($ciclcat as $key => $value) {
                    if($temp->idnumber == $value->idnumber) $valid = 1;
                }

                $tempi = $DB->get_record('course_categories', array('idnumber'=>$temp->idnumber));
                
                if($valid == 0 && !is_object($tempi))$ciclcat[] =$temp;
                
            }
            //print_r($ciclcat);
            return array('categories'=>$ciclcat);
         break;
         case "categories":

            $categories = array();
            foreach($data as $singular){
                $id = explode("-",$singular->parameterSet->parameterRecord->parameterValue);
                
                $temp = new stdClass();
                $temp->name = $id[4] . ' ' . $id[2] . ' ' . $id[7];
                $temp->parent = $id[6] . '_' . $id[2];
                $temp->idnumber = $id[4] . '_' . $id[2] . '_' . $id[7];
                
                $valid = 0;
                foreach ($categories as $key => $value) {
                    if($temp->idnumber == $value->idnumber) $valid = 1;
                }
                
                $tempi = $DB->get_record('course_categories', array('idnumber'=>$temp->idnumber));
                
                
                if($valid == 0 && !is_object($tempi))$categories[] =$temp;
                
            }
            //print_r($categories);
            return array('categories'=>$categories);
         break;            
         case "course":
            $courses = array();
            foreach($data as $singular){


                $id = explode("-",$singular->parameterSet->parameterRecord->parameterValue);
                $namespaces = $singular->parameterSet->parameterRecord[1]->parameterValue->getNameSpaces(true);
                $courss = $singular->parameterSet->parameterRecord[1]->parameterValue->children($namespaces['cms']);
                $fullname = $courss->courseOfferingRecord->courseOffering->title->textString;
                
                $cuorse = new stdClass();

                $cuorse->fullname=$fullname."-".$id[2]."-".$id[3]."-".$id[5];
                $cuorse->shortname=$id[2].$id[3].$id[5];
                $cuorse->categoryid=$id[4] . '_' . $id[2] . '_' . $id[7];
                $cuorse->idnumber = $id[0].$id[3].$id[5];
                //$cuorse->idnumber = $id[0];
                $cuorse->summaryformat=1;
                $cuorse->format='weeks';
                $cuorse->showgrades=1;
                $cuorse->newsitems=5;
                $cuorse->maxbytes=0;
                $cuorse->showreports=1;
                $cuorse->visible=1;
                $cuorse->groupmode=0;
                $cuorse->groupmodeforce=0;
                $cuorse->defaultgroupingid=0;
                //print_r($cuorse);
                $objCourse= $DB->get_record('course',array('shortname'=>$cuorse->shortname));
                //print_r($objCourse);
                
                if(!is_object($objCourse))array_push($courses,$cuorse);

            }
            
            return array("courses"=>$courses);
         break;
        case "user":
            $users = array();
            //print_r($data);
            foreach($data as $singular){
                $id = explode('-',$singular->parameterSet->parameterRecord[0]->parameterValue);

                $objuser= $DB->get_record('user',array('username'=>$id[0]));
                    //print_r($id[0]. '------');
                if(!is_object($objuser)){
                   $user = new stdClass();
                    $user->username = (string)$id[0];                
                    $user->password = (string)$id[0];
                    $user->firstname = (string)$id[1];
                    $user->lastname = (string)$id[2].' '.(string)$id[3];
                    $user->email = (string)$id[4];
                    $user->auth = 'centuria';                
                    $user->idnumber = (string)$id[0];
                    $user->lang = 'es';                
                    $user->calendartype = 'gregorian'; 
                    
                    $valid = 0;
                    foreach ($users as $key => $value) {
                        if($user->username == $value->username) $valid = 1;
                    }
                    if($valid == 0)array_push($users,$user);


                }
                
                
            }
            return array("users"=>$users);
         break;
         case "member":

            $enrols = array();
            foreach($data as $singular){
                $id = explode('-',$singular->parameterSet->parameterRecord[0]->parameterValue);
                $courss = $singular->parameterSet->parameterRecord[1]->parameterValue->children($namespaces['mms']);
                
                //print_r($id);
                $assistant = $DB->get_record('role', array('shortname'=>'teaching_assistant'));
                
                if($courss->membershipRecord->membership->member->role->roleType == 'Instructor'){
                    $role = 3;
                }
                else if($courss->membershipRecord->membership->member->role->roleType == 'TeachingAssistant'){
                    if(is_object($assistant))$role = $assistant->id;
                    else $role = 4;
                }
                else{
                    $role = 5;
                }
                            
                $enrolment = new stdClass();
                $enrolment->roleid = $role;                
                $enrolment->userid = $id[6];
                
                //$enrolment->courseid = $id[2].$id[3].$id[5];
                $enrolment->courseid = $id[2].$id[3].substr($id[5],1);
                //$enrolment->courseid = $id[0];

                //print_r($enrolment);
                $objEnrol= $DB->get_record('course',array('shortname'=>$enrolment->courseid));
                //print_r($objEnrol);
                if(is_object($objEnrol))array_push($enrols,$enrolment);
            }
                //print_r($enrols);
                $enrol_finish = array();
                if(count($enrols) > 300){
                    $total = array_chunk($enrols, 300);
                    foreach ($total as $value) {
                        $enrol_finish[] = array('enrolments'=>$value);
                    }
                }else{
                    $enrol_finish['enrolments'] = $enrols;
                }
                //print_r($total);
            return $enrol_finish;
         break;
        }
    }
    
    private function send($data,$type,$curl,$format="json"){
        global $CFG;
        global $DB;
        $objData = $DB->get_record('config',array('name'=>'wspeoplesofttoken'));
        $token = $objData->value;
        $domainname = $CFG->wwwroot;

        switch($type){
            case "categorie":
                $functionname = 'local_wspeoplesoft_create_course_categorie';
            break;
            case "course":
                $functionname = 'local_wspeoplesoft_create_course';
            break;
            case "user":
                $functionname = 'local_wspeoplesoft_create_users';
            break;
            case "member":
                $functionname = 'local_wspeoplesoft_enrol_users';
            break;
        }
        
        $serverurl = $domainname . '/webservice/rest/server.php'. '?wstoken=' . $token.'&wsfunction='.$functionname;
        
        $post = $data;
        $format = ($format == 'json')?'&moodlewsrestformat=' . $format:'';
        $resp = $curl->post($serverurl.$format, $post);

        return $resp;

    }
    
    
    private function prepare_path($tempPath,$type){
        global $CFG;

        $directorio = opendir($tempPath); 
        $output = array();
        while ($archivo = readdir($directorio))
        {
            if (!is_dir($archivo))
            {
                //$output [] = array('name' => $tempPath.'/'.$archivo,'date' => date("F d Y H:i:s",filectime($tempPath.'/'.$archivo)));
                $output [] = array('name' => $tempPath.'/'.$archivo,'date' => filectime($tempPath.'/'.$archivo));
            }
        }


        foreach ($output as $key => $row) {
            $name[$key]  = $row['name'];
            $date[$key] = $row['date'];
        }

        array_multisort($name, SORT_DESC, $date, SORT_ASC, $output);
	       //var_dump($output);
	foreach ($output as $temp) {
            if(strpos($temp['name'],$type)){
               //var_dump($temp['name']);
	           //var_dump(date("F d Y H:i:s",$temp['date'])); 
                print_r($temp['name'].'<br>');
                return $temp['name'];
            }
        }

        //var_dump($output[0]['name']);
        //return $output[0]['name'];
       // var_dump($output[0]['name']);
       // return $output[0]['name'];
    }
    
    
}
