<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External Web Service Template
 *
 * @package    localatypaxreports
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_atypaxreports_external extends external_api {

    public static function grade_course_detail_parameters() {
        return new external_function_parameters(
                array(
            'itemUser' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'userid' => new external_value(PARAM_RAW, 'userid'),
                'itemid' => new external_value(PARAM_INT, 'itemid'),
                    )
                    )
            )
                )
        );
    }

    public static function grade_course_detail($itemUser) {
        global $DB;
        $returnValue = array();
        //validamos los parametros ingrasados en la llamada al webservice
        $params = self::validate_parameters(self::grade_course_detail_parameters(), array('itemUser' => $itemUser));
        $userid = 0;
        $itemid = 0;
        foreach ($params['itemUser'] as $value) {
            $u = $DB->get_record('user', array('username' => $value['userid']));
            if (is_object($u)) {
                $value['userid'] = $u->id;
            } else {
                throw new moodle_exception('El identificador del usuario: ' . $value['userid'] . ' no existe en Moodle', 'enrol_manual');
            }
//            $userid = $value['userid'];
//            $itemid = $value['itemid'];
//            break;
            $item = $DB->get_record('grade_items', array('id' => $value['itemid']));
            if (is_object($item)) {
                $return['IdCurso'] = $item->courseid;
                $return['ID'] = $item->id;
                $return['item'] = $item->itemname;
                $return['type'] = $item->itemtype;
                $return['module'] = $item->itemmodule;
                $return['peso'] = $item->multfactor;
                $return['Nota'] = NULL;
                $return['Username'] = $u->username;
                $grade = $DB->get_record('grade_grades', array('itemid' => $item->id, 'userid' => $value['userid']));
                if (is_object($grade)) {
                    $return['Nota'] = $grade->finalgrade;
                }
            }

            array_push($returnValue, $return);
        }
        return $returnValue;
    }

    public static function grade_course_detail_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'IdCurso' => new external_value(PARAM_INT, 'id of course'),
            'ID' => new external_value(PARAM_INT, 'id of item'),
            'item' => new external_value(PARAM_RAW, 'name of item'),
            'type' => new external_value(PARAM_RAW, 'type of item'),
            'module' => new external_value(PARAM_RAW, 'module of item'),
            'peso' => new external_value(PARAM_RAW, 'multiplicador del item'),
            'Nota' => new external_value(PARAM_RAW, 'Nota final del item'),
            'Username' => new external_value(PARAM_RAW, 'username'),
                )
                )
        );
    }

    public static function grade_course_parameters() {
        return new external_function_parameters(
                array(
            'userlist' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'userid' => new external_value(PARAM_RAW, 'userid'),
                'courseid' => new external_value(PARAM_RAW, 'courseid'),
                    )
                    )
            )
                )
        );
    }

    public static function grade_course($userlist) {
        global $DB;
        $returnValue = array();
        //validamos los parametros ingrasados en la llamada al webservice
        $params = self::validate_parameters(self::grade_course_parameters(), array('userlist' => $userlist));
        $userid = 0;
        $courseid = 0;
        foreach ($params['userlist'] as $value) {
            //reemplazamos los valores del courseId
            $ci = $DB->get_record('course', array('idnumber' => $value['courseid']));
            if (is_object($ci)) {
                $value['courseid'] = $ci->id;
            } else {
                throw new moodle_exception('El identificador del curso: ' . $value['courseid'] . ' no existe en Moodle', 'enrol_manual');
                //$value['courseid'] = 0;
            }
            //reemplazamos los valores del usuario
            $u = $DB->get_record('user', array('username' => $value['userid']));
            if (is_object($u)) {
                $value['userid'] = $u->id;
            } else {
                throw new moodle_exception('El identificador del usuario: ' . $value['userid'] . ' no existe en Moodle', 'enrol_manual');
                //$value['userid'] = 0;
            }
            $userid = $value['userid'];
            $courseid = $value['courseid'];
            break;
        }
//        //listamos las notas del curso del estudiante en forma general
        $items = $DB->get_records('grade_items', array('courseid' => $courseid));
        if (is_array($items) && count($items) > 0) {
            foreach ($items as $indice => $item) {
                //filtramos los items de tipo curso
                if ($item->itemtype != 'course') {
                    $return['IDCurso'] = $item->courseid;
                    $return['IDAlumno'] = $userid;
                    $return['IDCriterio'] = $item->id;
                    $return['Nota'] = NULL;
                    $return['idnumber'] = $item->idnumber;
                    $return['calculation'] = $item->calculation;
                    //obtenemos la nota de cada alumno
                    $grade = $DB->get_record('grade_grades', array('itemid' => $item->id, 'userid' => $userid));
                    if (is_object($grade)) {
                        $return['Nota'] = $grade->finalgrade;
                    }
                    //agregamos al arreglo de notas
                    array_push($returnValue, $return);
                }
            }
        }
        return $returnValue;
    }

    public static function grade_course_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'IDCurso' => new external_value(PARAM_INT, 'id of course'),
            'IDAlumno' => new external_value(PARAM_INT, 'id of student'),
            'IDCriterio' => new external_value(PARAM_INT, 'id of item'),
            'Nota' => new external_value(PARAM_RAW, 'nota'),
            'idnumber' => new external_value(PARAM_RAW, 'id number'),
            'calculation' => new external_value(PARAM_RAW, 'formula de calculo'),
                )
                )
        );
    }

    public static function course_config_parameters() {
        return new external_function_parameters(
                array('courseid' => new external_value(PARAM_RAW, 'course ID'))
        );
    }

    public static function course_config($courseid) {
        global $DB, $CFG, $USER;
        $returnValue = array();
        $params = self::validate_parameters(self::course_config_parameters(), array('courseid' => $courseid));
        if (is_array($params)) {
            //reemplazamos los valores del courseId
            $ci = $DB->get_record('course', array('idnumber' => $params['courseid']));
            if (is_object($ci)) {
                $params['courseid'] = $ci->id;
            } else {
                throw new moodle_exception('El identificador del curso: ' . $params['courseid'] . ' no existe en Moodle', 'configuration');
                //$arrayParam['courseid'] = 0;
            }
        }
        //obtenemos el objeto del curso en el item
        $objItemCourse = $DB->get_record('grade_items', array('courseid' => $params['courseid'], 'itemtype' => 'course'));
        $formula = '';
        if (is_object($objItemCourse)) {
            $formula = $objItemCourse->calculation;
        }
        //$formula = '=(0.10*##gi3810##+0.20*##gi4015##+0.20*##gi4023##+0.20*##gi4026##+0.30*##gi4032##)';
        preg_match_all('/##gi(\d+)##/', $formula, $matches);
        //eliminamos el primer array
        $matches = array_pop($matches);
        //buscamos los items
        $itemsArray = array();
        if (is_array($matches)) {
            foreach ($matches as $indice => $gradesItems) {
                $objItem = $DB->get_record('grade_items', array('id' => $gradesItems));
                if (is_object($objItem)) {
                    //$item = array();
                    $item['Id'] = $objItem->id;
                    $item['Actividad'] = $objItem->itemname;
                    $item['Peso'] = $objItem->multfactor;
                    array_push($itemsArray, $item);
                }
            }
        }
        //$r = self::localize($formula);
        preg_match_all('/[\w.]+|".*?"|(?!\s)\W/', $formula, $list);
        $list = array_shift($list);
        $arrayPeso = array();
        if (is_array($list)) {
            $cont = 0;
            foreach ($list as $index => $value) {
                if (self::is_decimal($value)) {
                    array_push($arrayPeso, $value);
                }
            }
        }
        //agregamos los pesos en los items encontrados
        if (is_array($itemsArray)) {
            foreach ($itemsArray as $ind => $val) {
                $val['Peso'] = $arrayPeso[$ind];
                $itemsArray[$ind] = $val;
            }
        }
        return array(array(
                'course' => $ci->fullname,
                'calculation' => $formula,
                'items' => $itemsArray
        ));
    }

    public static function is_decimal($val) {
        return is_numeric($val) && floor($val) != $val;
    }

    public static function course_config_returns() {
        //return new external_value(PARAM_TEXT, 'The welcome message + user first name');
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'course' => new external_value(PARAM_TEXT, 'full name of course'),
            'calculation' => new external_value(PARAM_TEXT, 'full name of course'),
            'items' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'Id' => new external_value(PARAM_RAW, 'Id del item que se muestra en la formula'),
                'Actividad' => new external_value(PARAM_RAW, 'Nombre de la actividad'),
                'Peso' => new external_value(PARAM_RAW, 'El peso por el que se multiplica el item')
                    )
                    ), 'Lista de items con sus respectivos pesos que se encuentran en la formula para calcular la nota del curso', VALUE_OPTIONAL),
                ), 'configuration of course'
                )
        );
    }

    public static function localize($formula) {
        $formula = str_replace('.', '$', $formula); // temp placeholder
        $formula = str_replace(',', get_string('listsep', 'langconfig'), $formula);
        $formula = str_replace('$', get_string('decsep', 'langconfig'), $formula);
        return $formula;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hello_world_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function hello_world($welcomemessage = 'Hello world, ') {
        global $USER;
        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::hello_world_parameters(), array('welcomemessage' => $welcomemessage));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        return $params['welcomemessage'] . $USER->firstname.' '.$USER->lastname;
        //return $USER;

    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function create_users_parameters() {
        global $CFG;

        return new external_function_parameters(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'username' =>
                                new external_value(PARAM_USERNAME, 'Username policy is defined in Moodle security config.'),
                            'password' =>
                                new external_value(PARAM_RAW, 'Plain text password consisting of any characters'),
                            'firstname' =>
                                new external_value(PARAM_NOTAGS, 'The first name(s) of the user'),
                            'lastname' =>
                                new external_value(PARAM_NOTAGS, 'The family name of the user'),
                            'email' =>
                                new external_value(PARAM_EMAIL, 'A valid and unique email address'),
                            'auth' =>
                                new external_value(PARAM_PLUGIN, 'Auth plugins include manual, ldap, imap, etc', VALUE_DEFAULT,
                                    'manual', NULL_NOT_ALLOWED),
                            'idnumber' =>
                                new external_value(PARAM_RAW, 'An arbitrary ID code number perhaps from the institution',
                                    VALUE_DEFAULT, ''),
                            'lang' =>
                                new external_value(PARAM_SAFEDIR, 'Language code such as "en", must exist on server', VALUE_DEFAULT,
                                    $CFG->lang, NULL_NOT_ALLOWED),
                            'calendartype' =>
                                new external_value(PARAM_PLUGIN, 'Calendar type such as "gregorian", must exist on server',
                                    VALUE_DEFAULT, $CFG->calendartype, VALUE_OPTIONAL),
                            'theme' =>
                                new external_value(PARAM_PLUGIN, 'Theme name such as "standard", must exist on server',
                                    VALUE_OPTIONAL),
                            'timezone' =>
                                new external_value(PARAM_TIMEZONE, 'Timezone code such as Australia/Perth, or 99 for default',
                                    VALUE_OPTIONAL),
                            'mailformat' =>
                                new external_value(PARAM_INT, 'Mail format code is 0 for plain text, 1 for HTML etc',
                                    VALUE_OPTIONAL),
                            'description' =>
                                new external_value(PARAM_TEXT, 'User profile description, no HTML', VALUE_OPTIONAL),
                            'city' =>
                                new external_value(PARAM_NOTAGS, 'Home city of the user', VALUE_OPTIONAL),
                            'country' =>
                                new external_value(PARAM_ALPHA, 'Home country code of the user, such as AU or CZ', VALUE_OPTIONAL),
                            'firstnamephonetic' =>
                                new external_value(PARAM_NOTAGS, 'The first name(s) phonetically of the user', VALUE_OPTIONAL),
                            'lastnamephonetic' =>
                                new external_value(PARAM_NOTAGS, 'The family name phonetically of the user', VALUE_OPTIONAL),
                            'middlename' =>
                                new external_value(PARAM_NOTAGS, 'The middle name of the user', VALUE_OPTIONAL),
                            'alternatename' =>
                                new external_value(PARAM_NOTAGS, 'The alternate name of the user', VALUE_OPTIONAL),
                            'preferences' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the preference'),
                                        'value' => new external_value(PARAM_RAW, 'The value of the preference')
                                    )
                                ), 'User preferences', VALUE_OPTIONAL),
                            'customfields' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the custom field'),
                                        'value' => new external_value(PARAM_RAW, 'The value of the custom field')
                                    )
                                ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL)
                        )
                    )
                )
            )
        );
    }

    /**
     * Create one or more users.
     *
     * @throws invalid_parameter_exception
     * @param array $users An array of users to create.
     * @return array An array of arrays
     * @since Moodle 2.2
     */
    public static function create_users($users) {
        global $CFG, $DB;
        require_once($CFG->dirroot."/lib/weblib.php");
        require_once($CFG->dirroot."/user/lib.php");
        require_once($CFG->dirroot."/user/profile/lib.php"); // Required for customfields related function.

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:create', $context);

        // Do basic automatic PARAM checks on incoming data, using params description.
        // If any problems are found then exceptions are thrown with helpful error messages.
        $params = self::validate_parameters(self::create_users_parameters(), array('users' => $users));

        $availableauths  = core_component::get_plugin_list('auth');
        unset($availableauths['mnet']);       // These would need mnethostid too.
        unset($availableauths['webservice']); // We do not want new webservice users for now.

        $availablethemes = core_component::get_plugin_list('theme');
        $availablelangs  = get_string_manager()->get_list_of_translations();

        $transaction = $DB->start_delegated_transaction();

        $userids = array();
        foreach ($params['users'] as $user) {
            // Make sure that the username doesn't already exist.
            if ($DB->record_exists('user', array('username' => $user['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
                throw new invalid_parameter_exception('Username already exists: '.$user['username']);
            }

            // Make sure auth is valid.
            if (empty($availableauths[$user['auth']])) {
                throw new invalid_parameter_exception('Invalid authentication type: '.$user['auth']);
            }

            // Make sure lang is valid.
            if (empty($availablelangs[$user['lang']])) {
                throw new invalid_parameter_exception('Invalid language code: '.$user['lang']);
            }

            // Make sure lang is valid.
            if (!empty($user['theme']) && empty($availablethemes[$user['theme']])) { // Theme is VALUE_OPTIONAL,
                                                                                     // so no default value
                                                                                     // We need to test if the client sent it
                                                                                     // => !empty($user['theme']).
                throw new invalid_parameter_exception('Invalid theme: '.$user['theme']);
            }

            $user['confirmed'] = true;
            $user['mnethostid'] = $CFG->mnet_localhost_id;

            // Start of user info validation.
            // Make sure we validate current user info as handled by current GUI. See user/editadvanced_form.php func validation().
            if (!validate_email($user['email'])) {
                throw new invalid_parameter_exception('Email address is invalid: '.$user['email']);
            } else if ($DB->record_exists('user', array('email' => $user['email'], 'mnethostid' => $user['mnethostid']))) {
                throw new invalid_parameter_exception('Email address already exists: '.$user['email']);
            }
            // End of user info validation.

            // Create the user data now!
            $user['id'] = user_create_user($user, true, false);

            // Custom fields.
            if (!empty($user['customfields'])) {
                foreach ($user['customfields'] as $customfield) {
                    // Profile_save_data() saves profile file it's expecting a user with the correct id,
                    // and custom field to be named profile_field_"shortname".
                    $user["profile_field_".$customfield['type']] = $customfield['value'];
                }
                profile_save_data((object) $user);
            }

            // Trigger event.
            \core\event\user_created::create_from_userid($user['id'])->trigger();

            // Preferences.
            if (!empty($user['preferences'])) {
                foreach ($user['preferences'] as $preference) {
                    set_user_preference($preference['type'], $preference['value'], $user['id']);
                }
            }
            //var_dump(array('id' => $user['id'], 'username' => $user['username']));
            $userids[] = array('id' => $user['id'], 'username' => $user['username']);
        }

        $transaction->allow_commit();

        return $userids;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function create_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'user id'),
                    'username' => new external_value(PARAM_USERNAME, 'user name'),
                )
            )
        );
    }



    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }

    public static function enrol_users_parameters() {
        return new external_function_parameters(
                array(
            'enrolments' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                'userid' => new external_value(PARAM_RAW, 'The id user that is going to be enrolled'),
                'courseid' => new external_value(PARAM_TEXT, 'The course to enrol the user role in',VALUE_OPTIONAL),
                'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL),
                'firstname' => new external_value(PARAM_TEXT, 'set Firstname', VALUE_OPTIONAL),
                'lastname' => new external_value(PARAM_TEXT, 'set Lastname', VALUE_OPTIONAL),
                'email' => new external_value(PARAM_TEXT, 'set email', VALUE_OPTIONAL),
                'password' => new external_value(PARAM_TEXT, 'set password', VALUE_OPTIONAL),
                'city' => new external_value(PARAM_RAW, 'City', VALUE_OPTIONAL),
                'idnumber' => new external_value(PARAM_RAW, 'idnumber', VALUE_OPTIONAL),
                'institution' => new external_value(PARAM_RAW, 'institution', VALUE_OPTIONAL),
                'department' => new external_value(PARAM_RAW, 'department', VALUE_OPTIONAL)
                    )
                    )
            )
                )
        );
    }

    public static function enrol_users($enrolments) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');
        require_once($CFG->libdir . '/../user/lib.php');
        require_once($CFG->libdir . "/../lib/weblib.php");
        $params = self::validate_parameters(self::enrol_users_parameters(), array('enrolments' => $enrolments));
        $transaction = $DB->start_delegated_transaction();
        //retrieve the manual enrolment plugin
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }

        //throw new moodle_exception('El email no es correcto', 'enrol_manual');

            //print_r($params['enrolments']);
            //print_r($params['enrolments']);
        foreach ($params['enrolments'] as $enrolment) {
            //reemplazamos los valores del courseId
            $ci = $DB->get_record('course', array('shortname' => $enrolment['courseid']));
            //print_r($enrolment['courseid'].'--');
            //print_r($enrolment);
            if (is_object($ci)) {
                $enrolment['courseid'] = $ci->id;
            } else {

                throw new moodle_exception('El identificador del curso: ' . $enrolment['courseid'] . ' no existe en Moodle', 'enrol_manual');
                //$enrolment['courseid'] = 0;
            }

            //reemplazamos los valores del usuario
            $u = $DB->get_record('user', array('idnumber' => $enrolment['userid']));
            if(!is_object($u)){
                $u = $DB->get_record('user', array('username' => $enrolment['userid']));
            }
            if (is_object($u)) {
                $enrolment['userid'] = $u->id;

            } else {
                    throw new invalid_parameter_exception('userId is invalid: ');

            }
            // Ensure the current user is allowed to run this function in the enrolment context

            $context = get_context_instance(CONTEXT_COURSE, $enrolment['courseid']);
            self::validate_context($context);

            //check that the user has the permission to manual enrol
            require_capability('enrol/manual:enrol', $context);

            //throw an exception if user is not able to assign the role
            $roles = get_assignable_roles($context);

            if (!array_key_exists($enrolment['roleid'], $roles)) {
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->courseid = $enrolment['courseid'];
                $errorparams->userid = $enrolment['userid'];
                throw new moodle_exception('wsusercannotassign', 'enrol_manual', '', $errorparams);
            }

            //check manual enrolment plugin instance is enabled/exist
            $enrolinstances = enrol_get_instances($enrolment['courseid'], true);
            foreach ($enrolinstances as $courseenrolinstance) {
                if ($courseenrolinstance->enrol == "manual") {
                    $instance = $courseenrolinstance;
                    break;
                }
            }
            if (empty($instance)) {
                $errorparams = new stdClass();
                $errorparams->courseid = $enrolment['courseid'];
                throw new moodle_exception('wsnoinstance', 'enrol_manual', $errorparams);
            }

            //check that the plugin accept enrolment (it should always the case, it's hard coded in the plugin)
            if (!$enrol->allow_enrol($instance)) {
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->courseid = $enrolment['courseid'];
                $errorparams->userid = $enrolment['userid'];
                throw new moodle_exception('wscannotenrol', 'enrol_manual', '', $errorparams);
            }

            //finally proceed the enrolment
            $enrolment['timestart'] = isset($enrolment['timestart']) ? $enrolment['timestart'] : 0;
            $enrolment['timeend'] = isset($enrolment['timeend']) ? $enrolment['timeend'] : 0;
            $enrolment['status'] = (isset($enrolment['suspend']) && !empty($enrolment['suspend'])) ?
                    ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;
            //var_dump($enrolment);die();
            $enrol->enrol_user($instance, $enrolment['userid'], $enrolment['roleid'], $enrolment['timestart'], $enrolment['timeend'], $enrolment['status']);
        }

        $transaction->allow_commit();
        //var_dump($enrolment);die();
        return 'Usuarios Matriculados';
    }

    public static function enrol_users_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_users_by_id_parameters() {
        return new external_function_parameters(
                array(
            'userids' => new external_multiple_structure(new external_value(PARAM_RAW, 'user username')),
                )
        );
    }

    /**
     * Get user information
     * - This function is matching the permissions of /user/profil.php
     * - It is also matching some permissions from /user/editadvanced.php for the following fields:
     *   auth, confirmed, idnumber, lang, theme, timezone, mailformat
     *
     * @param array $userids  array of user ids
     * @return array An array of arrays describing users
     * @since Moodle 2.2
     */
    public static function get_users_by_id($userids) {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot . "/user/lib.php");
        //iteramos los parametros y reemplazamos por los ID
        if (is_array($userids)) {
            foreach ($userids as $indice => $dni) {
                $u = $DB->get_record('user', array('username' => $dni));
                if (is_object($u)) {
                    $userids[$indice] = $u->id;
                }
            }
        }
        $params = self::validate_parameters(self::get_users_by_id_parameters(), array('userids' => $userids));

        list($uselect, $ujoin) = context_instance_preload_sql('u.id', CONTEXT_USER, 'ctx');
        list($sqluserids, $params) = $DB->get_in_or_equal($userids);
        $usersql = "SELECT u.* $uselect
                      FROM {user} u $ujoin
                     WHERE u.id $sqluserids";
        $users = $DB->get_recordset_sql($usersql, $params);

        $result = array();
        $hasuserupdatecap = has_capability('moodle/user:update', get_system_context());
        foreach ($users as $user) {
            if (!empty($user->deleted)) {
                continue;
            }
            context_instance_preload($user);
            $usercontext = get_context_instance(CONTEXT_USER, $user->id);
            self::validate_context($usercontext);
            $currentuser = ($user->id == $USER->id);

            if ($userarray = user_get_user_details($user)) {
                //fields matching permissions from /user/editadvanced.php
                if ($currentuser or $hasuserupdatecap) {
                    $userarray['auth'] = $user->auth;
                    $userarray['confirmed'] = $user->confirmed;
                    $userarray['idnumber'] = $user->idnumber;
                    $userarray['lang'] = $user->lang;
                    $userarray['theme'] = $user->theme;
                    $userarray['timezone'] = $user->timezone;
                    $userarray['mailformat'] = $user->mailformat;
                }
                $result[] = $userarray;
            }
        }
        $users->close();

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_users_by_id_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'id' => new external_value(PARAM_NUMBER, 'ID of the user'),
            'username' => new external_value(PARAM_RAW, 'Username policy is defined in Moodle security config', VALUE_OPTIONAL),
            'firstname' => new external_value(PARAM_NOTAGS, 'The first name(s) of the user', VALUE_OPTIONAL),
            'lastname' => new external_value(PARAM_NOTAGS, 'The family name of the user', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_NOTAGS, 'The fullname of the user'),
            'email' => new external_value(PARAM_TEXT, 'An email address - allow email as root@localhost', VALUE_OPTIONAL),
            'address' => new external_value(PARAM_MULTILANG, 'Postal address', VALUE_OPTIONAL),
            'phone1' => new external_value(PARAM_NOTAGS, 'Phone 1', VALUE_OPTIONAL),
            'phone2' => new external_value(PARAM_NOTAGS, 'Phone 2', VALUE_OPTIONAL),
            'icq' => new external_value(PARAM_NOTAGS, 'icq number', VALUE_OPTIONAL),
            'skype' => new external_value(PARAM_NOTAGS, 'skype id', VALUE_OPTIONAL),
            'yahoo' => new external_value(PARAM_NOTAGS, 'yahoo id', VALUE_OPTIONAL),
            'aim' => new external_value(PARAM_NOTAGS, 'aim id', VALUE_OPTIONAL),
            'msn' => new external_value(PARAM_NOTAGS, 'msn number', VALUE_OPTIONAL),
            'department' => new external_value(PARAM_TEXT, 'department', VALUE_OPTIONAL),
            'institution' => new external_value(PARAM_TEXT, 'institution', VALUE_OPTIONAL),
            'interests' => new external_value(PARAM_TEXT, 'user interests (separated by commas)', VALUE_OPTIONAL),
            'firstaccess' => new external_value(PARAM_INT, 'first access to the site (0 if never)', VALUE_OPTIONAL),
            'lastaccess' => new external_value(PARAM_INT, 'last access to the site (0 if never)', VALUE_OPTIONAL),
            'auth' => new external_value(PARAM_PLUGIN, 'Auth plugins include manual, ldap, imap, etc', VALUE_OPTIONAL),
            'confirmed' => new external_value(PARAM_NUMBER, 'Active user: 1 if confirmed, 0 otherwise', VALUE_OPTIONAL),
            'idnumber' => new external_value(PARAM_RAW, 'An arbitrary ID code number perhaps from the institution', VALUE_OPTIONAL),
            'lang' => new external_value(PARAM_SAFEDIR, 'Language code such as "en", must exist on server', VALUE_OPTIONAL),
            'theme' => new external_value(PARAM_PLUGIN, 'Theme name such as "standard", must exist on server', VALUE_OPTIONAL),
            'timezone' => new external_value(PARAM_TIMEZONE, 'Timezone code such as Australia/Perth, or 99 for default', VALUE_OPTIONAL),
            'mailformat' => new external_value(PARAM_INTEGER, 'Mail format code is 0 for plain text, 1 for HTML etc', VALUE_OPTIONAL),
            'description' => new external_value(PARAM_RAW, 'User profile description', VALUE_OPTIONAL),
            'descriptionformat' => new external_format_value('description', VALUE_OPTIONAL),
            'city' => new external_value(PARAM_NOTAGS, 'Home city of the user', VALUE_OPTIONAL),
            'url' => new external_value(PARAM_URL, 'URL of the user', VALUE_OPTIONAL),
            'country' => new external_value(PARAM_ALPHA, 'Home country code of the user, such as AU or CZ', VALUE_OPTIONAL),
            'profileimageurlsmall' => new external_value(PARAM_URL, 'User image profile URL - small version'),
            'profileimageurl' => new external_value(PARAM_URL, 'User image profile URL - big version'),
            'customfields' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'type' => new external_value(PARAM_ALPHANUMEXT, 'The type of the custom field - text field, checkbox...'),
                'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                'name' => new external_value(PARAM_RAW, 'The name of the custom field'),
                'shortname' => new external_value(PARAM_RAW, 'The shortname of the custom field - to be able to build the field class in the code'),
                    )
                    ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL),
            'preferences' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'name' => new external_value(PARAM_ALPHANUMEXT, 'The name of the preferences'),
                'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                    )
                    ), 'User preferences', VALUE_OPTIONAL),
            'enrolledcourses' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'id' => new external_value(PARAM_INT, 'Id of the course'),
                'fullname' => new external_value(PARAM_RAW, 'Fullname of the course'),
                'shortname' => new external_value(PARAM_RAW, 'Shortname of the course')
                    )
                    ), 'Courses where the user is enrolled - limited by which courses the user is able to see', VALUE_OPTIONAL)
                )
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
                array('options' => new external_single_structure(
                    array('ids' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'Course id')
                        , 'List of course id. If empty return all courses
                                            except front page course.', VALUE_OPTIONAL)
                    ), 'options - operator OR is used', VALUE_DEFAULT, array())
                )
        );
    }

    /**
     * Get courses
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     * @since Moodle 2.2
     */
    public static function get_courses($options = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        //iteramos los cursos para reemplazar por los IDs
        if (is_array($options)) {
            foreach ($options['ids'] as $indice => $courseCode) {
                $ci = $DB->get_record('course', array('idnumber' => $courseCode));
                if (is_object($ci)) {
                    $options['ids'][$indice] = $ci->id;
                }
            }
        }
        //validate parameter
        $params = self::validate_parameters(self::get_courses_parameters(), array('options' => $options));

        //retrieve courses
        if (!array_key_exists('ids', $params['options'])
                or empty($params['options']['ids'])) {
            $courses = $DB->get_records('course');
        } else {
            $courses = $DB->get_records_list('course', 'id', $params['options']['ids']);
        }

        //create return value
        $coursesinfo = array();
        foreach ($courses as $course) {

            // now security checks
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $course->id;
                throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:view', $context);

            $courseinfo = array();
            $courseinfo['id'] = $course->id;
            $courseinfo['fullname'] = $course->fullname;
            $courseinfo['shortname'] = $course->shortname;
            $courseinfo['categoryid'] = $course->category;
            list($courseinfo['summary'], $courseinfo['summaryformat']) = external_format_text($course->summary, $course->summaryformat, $context->id, 'course', 'summary', 0);
            $courseinfo['format'] = $course->format;
            $courseinfo['startdate'] = $course->startdate;
            $courseinfo['numsections'] = $course->numsections;

            //some field should be returned only if the user has update permission
            $courseadmin = has_capability('moodle/course:update', $context);
            if ($courseadmin) {
                $courseinfo['categorysortorder'] = $course->sortorder;
                $courseinfo['idnumber'] = $course->idnumber;
                $courseinfo['showgrades'] = $course->showgrades;
                $courseinfo['showreports'] = $course->showreports;
                $courseinfo['newsitems'] = $course->newsitems;
                $courseinfo['visible'] = $course->visible;
                $courseinfo['maxbytes'] = $course->maxbytes;
                $courseinfo['hiddensections'] = $course->hiddensections;
                $courseinfo['groupmode'] = $course->groupmode;
                $courseinfo['groupmodeforce'] = $course->groupmodeforce;
                $courseinfo['defaultgroupingid'] = $course->defaultgroupingid;
                $courseinfo['lang'] = $course->lang;
                $courseinfo['timecreated'] = $course->timecreated;
                $courseinfo['timemodified'] = $course->timemodified;
                $courseinfo['forcetheme'] = $course->theme;
                $courseinfo['enablecompletion'] = $course->enablecompletion;
                $courseinfo['completionstartonenrol'] = $course->completionstartonenrol;
                $courseinfo['completionnotify'] = $course->completionnotify;
            }

            if ($courseadmin or $course->visible
                    or has_capability('moodle/course:viewhiddencourses', $context)) {
                $coursesinfo[] = $courseinfo;
            }
        }

        return $coursesinfo;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_courses_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'id' => new external_value(PARAM_INT, 'course id'),
            'shortname' => new external_value(PARAM_TEXT, 'course short name'),
            'categoryid' => new external_value(PARAM_INT, 'category id'),
            'categorysortorder' => new external_value(PARAM_INT, 'sort order into the category', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_TEXT, 'full name'),
            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
            'summary' => new external_value(PARAM_RAW, 'summary'),
            'summaryformat' => new external_format_value('summary'),
            'format' => new external_value(PARAM_PLUGIN, 'course format: weeks, topics, social, site,..'),
            'showgrades' => new external_value(PARAM_INT, '1 if grades are shown, otherwise 0', VALUE_OPTIONAL),
            'newsitems' => new external_value(PARAM_INT, 'number of recent items appearing on the course page', VALUE_OPTIONAL),
            'startdate' => new external_value(PARAM_INT, 'timestamp when the course start'),
            'numsections' => new external_value(PARAM_INT, 'number of weeks/topics'),
            'maxbytes' => new external_value(PARAM_INT, 'largest size of file that can be uploaded into the course', VALUE_OPTIONAL),
            'showreports' => new external_value(PARAM_INT, 'are activity report shown (yes = 1, no =0)', VALUE_OPTIONAL),
            'visible' => new external_value(PARAM_INT, '1: available to student, 0:not available', VALUE_OPTIONAL),
            'hiddensections' => new external_value(PARAM_INT, 'How the hidden sections in the course are displayed to students', VALUE_OPTIONAL),
            'groupmode' => new external_value(PARAM_INT, 'no group, separate, visible', VALUE_OPTIONAL),
            'groupmodeforce' => new external_value(PARAM_INT, '1: yes, 0: no', VALUE_OPTIONAL),
            'defaultgroupingid' => new external_value(PARAM_INT, 'default grouping id', VALUE_OPTIONAL),
            'timecreated' => new external_value(PARAM_INT, 'timestamp when the course have been created', VALUE_OPTIONAL),
            'timemodified' => new external_value(PARAM_INT, 'timestamp when the course have been modified', VALUE_OPTIONAL),
            'enablecompletion' => new external_value(PARAM_INT, 'Enabled, control via completion and activity settings. Disbaled,
                                        not shown in activity settings.', VALUE_OPTIONAL),
            'completionstartonenrol' => new external_value(PARAM_INT, '1: begin tracking a student\'s progress in course completion
                                        after course enrolment. 0: does not', VALUE_OPTIONAL),
            'completionnotify' => new external_value(PARAM_INT, '1: yes 0: no', VALUE_OPTIONAL),
            'lang' => new external_value(PARAM_SAFEDIR, 'forced course language', VALUE_OPTIONAL),
            'forcetheme' => new external_value(PARAM_PLUGIN, 'name of the force theme', VALUE_OPTIONAL),
                ), 'course'
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function create_courses_parameters() {
        $courseconfig = get_config('moodlecourse'); //needed for many default values
        return new external_function_parameters(
            array(
                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'fullname' => new external_value(PARAM_TEXT, 'full name'),
                            'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                            'categoryid' => new external_value(PARAM_TEXT, 'category id'),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                            'summary' => new external_value(PARAM_RAW, 'summary', VALUE_OPTIONAL),
                            'summaryformat' => new external_format_value('summary', VALUE_DEFAULT),
                            'format' => new external_value(PARAM_PLUGIN,
                                    'course format: weeks, topics, social, site,..',
                                    VALUE_DEFAULT, $courseconfig->format),
                            'showgrades' => new external_value(PARAM_INT,
                                    '1 if grades are shown, otherwise 0', VALUE_DEFAULT,
                                    $courseconfig->showgrades),
                            'newsitems' => new external_value(PARAM_INT,
                                    'number of recent items appearing on the course page',
                                    VALUE_DEFAULT, $courseconfig->newsitems),
                            'startdate' => new external_value(PARAM_INT,
                                    'timestamp when the course start', VALUE_OPTIONAL),
                            'numsections' => new external_value(PARAM_INT,
                                    '(deprecated, use courseformatoptions) number of weeks/topics',
                                    VALUE_OPTIONAL),
                            'maxbytes' => new external_value(PARAM_INT,
                                    'largest size of file that can be uploaded into the course',
                                    VALUE_DEFAULT, $courseconfig->maxbytes),
                            'showreports' => new external_value(PARAM_INT,
                                    'are activity report shown (yes = 1, no =0)', VALUE_DEFAULT,
                                    $courseconfig->showreports),
                            'visible' => new external_value(PARAM_INT,
                                    '1: available to student, 0:not available', VALUE_OPTIONAL),
                            'hiddensections' => new external_value(PARAM_INT,
                                    '(deprecated, use courseformatoptions) How the hidden sections in the course are displayed to students',
                                    VALUE_OPTIONAL),
                            'groupmode' => new external_value(PARAM_INT, 'no group, separate, visible',
                                    VALUE_DEFAULT, $courseconfig->groupmode),
                            'groupmodeforce' => new external_value(PARAM_INT, '1: yes, 0: no',
                                    VALUE_DEFAULT, $courseconfig->groupmodeforce),
                            'defaultgroupingid' => new external_value(PARAM_INT, 'default grouping id',
                                    VALUE_DEFAULT, 0),
                            'enablecompletion' => new external_value(PARAM_INT,
                                    'Enabled, control via completion and activity settings. Disabled,
                                        not shown in activity settings.',
                                    VALUE_OPTIONAL),
                            'completionnotify' => new external_value(PARAM_INT,
                                    '1: yes 0: no', VALUE_OPTIONAL),
                            'lang' => new external_value(PARAM_SAFEDIR,
                                    'forced course language', VALUE_OPTIONAL),
                            'forcetheme' => new external_value(PARAM_PLUGIN,
                                    'name of the force theme', VALUE_OPTIONAL),
                            'courseformatoptions' => new external_multiple_structure(
                                new external_single_structure(
                                    array('name' => new external_value(PARAM_ALPHANUMEXT, 'course format option name'),
                                        'value' => new external_value(PARAM_RAW, 'course format option value')
                                )),
                                    'additional options for particular course format', VALUE_OPTIONAL),
                        )
                    ), 'courses to create'
                )
            )
        );
    }

    /**
     * Create  courses
     *
     * @param array $courses
     * @return array courses (id and shortname only)
     * @since Moodle 2.2
     */
    public static function create_courses($courses) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');
        $params = self::validate_parameters(self::create_courses_parameters(),
                        array('courses' => $courses));

        $availablethemes = core_component::get_plugin_list('theme');
        $availablelangs = get_string_manager()->get_list_of_translations();

        $transaction = $DB->start_delegated_transaction();



        foreach ($params['courses'] as $course) {
	//print_r($course['idnumber']. '-');
            // Ensure the current user is allowed to run this function
            $tem = $DB->get_record('course_categories', array('idnumber' => $course['categoryid']));
            $context = context_coursecat::instance($tem->id, IGNORE_MISSING);

            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->catid = $tem->id;
                throw new moodle_exception('errorcatcontextnotvalid', 'webservice', '', $exceptionparam);
            }

            require_capability('moodle/course:create', $context);
            // Make sure lang is valid
            if (array_key_exists('lang', $course) and empty($availablelangs[$course['lang']])) {
                throw new moodle_exception('errorinvalidparam', 'webservice', '', 'lang');
            }

            // Make sure theme is valid
            if (array_key_exists('forcetheme', $course)) {
                if (!empty($CFG->allowcoursethemes)) {
                    if (empty($availablethemes[$course['forcetheme']])) {
                        throw new moodle_exception('errorinvalidparam', 'webservice', '', 'forcetheme');
                    } else {
                        $course['theme'] = $course['forcetheme'];
                    }
                }
            }

            //force visibility if ws user doesn't have the permission to set it

            $category = $DB->get_record('course_categories', array('idnumber' => $course['categoryid']));

            if (!has_capability('moodle/course:visibility', $context)) {
                $course['visible'] = $category->visible;
            }

            //set default value for completion
            $courseconfig = get_config('moodlecourse');
            if (completion_info::is_enabled_for_site()) {
                if (!array_key_exists('enablecompletion', $course)) {
                    $course['enablecompletion'] = $courseconfig->enablecompletion;
                }
            } else {
                $course['enablecompletion'] = 0;
            }

            $course['category'] = $category->id;

            // Summary format.
            $course['summaryformat'] = external_validate_format($course['summaryformat']);

            if (!empty($course['courseformatoptions'])) {
                foreach ($course['courseformatoptions'] as $option) {
                    $course[$option['name']] = $option['value'];
                }
            }

            //Note: create_course() core function check shortname, idnumber, category
            $course['id'] = create_course((object) $course)->id;

            $resultcourses[] = array('id' => $course['id'], 'shortname' => $course['shortname']);
        }

        $transaction->allow_commit();

        return $resultcourses;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function create_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'course id'),
                    'shortname' => new external_value(PARAM_TEXT, 'short name'),
                )
            )
        );
    }



    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     *
     */
    public static function create_categories_parameters() {
        return new external_function_parameters(
            array(
                'categories' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'name' => new external_value(PARAM_TEXT, 'new category name'),
                                'parent' => new external_value(PARAM_TEXT,
                                        'the parent category id inside which the new category will be created
                                         - set to 0 for a root category',
                                        VALUE_DEFAULT, 0),
                                'idnumber' => new external_value(PARAM_TEXT,
                                        'the new category idnumber', VALUE_OPTIONAL),
                                'description' => new external_value(PARAM_RAW,
                                        'the new category description', VALUE_OPTIONAL),
                                'descriptionformat' => new external_format_value('description', VALUE_DEFAULT),
                                'theme' => new external_value(PARAM_THEME,
                                        'the new category theme. This option must be enabled on moodle',
                                        VALUE_OPTIONAL),
                        )
                    )
                )
            )
        );
    }

    /**
     * Create categories
     *
     * @param array $categories - see create_categories_parameters() for the array structure
     * @return array - see create_categories_returns() for the array structure
     *
     */
    public static function create_categories($categories) {
        global $CFG, $DB;
        require_once($CFG->libdir . "/coursecatlib.php");

        $params = self::validate_parameters(self::create_categories_parameters(),
                        array('categories' => $categories));

        $transaction = $DB->start_delegated_transaction();

        $createdcategories = array();
        foreach ($params['categories'] as $category) {
            $parentCategory = $DB->get_record('course_categories', array('idnumber' => $category['parent']));
            $category['parent'] = $parentCategory->id;
            if ($category['parent']) {

                if (!is_object($parentCategory)) {
                    throw new moodle_exception('unknowcategory');
                }
                $context = context_coursecat::instance($category['parent']);
            } else {
                $context = context_system::instance();
            }
            self::validate_context($context);
            require_capability('moodle/category:manage', $context);

            // this will validate format and throw an exception if there are errors
            external_validate_format($category['descriptionformat']);

            $newcategory = coursecat::create($category);

            $createdcategories[] = array('id' => $newcategory->id, 'name' => $newcategory->name);
        }

        $transaction->allow_commit();

        return $createdcategories;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     *
     */
    public static function create_categories_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'new category id'),
                    'name' => new external_value(PARAM_TEXT, 'new category name'),
                )
            )
        );
    }



    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function get_categories_parameters() {
        return new external_function_parameters(
                array(
            'criteria' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'key' => new external_value(PARAM_ALPHA, 'The category column to search, expected keys (value format) are:' .
                        '"id" (int) the category id,' .
                        '"name" (string) the category name,' .
                        '"parent" (int) the parent category id,' .
                        '"idnumber" (string) category idnumber' .
                        ' - user must have \'moodle/category:manage\' to search on idnumber,' .
                        '"visible" (int) whether the returned categories must be visible or hidden. If the key is not passed,
                                             then the function return all categories that the user can see.' .
                        ' - user must have \'moodle/category:manage\' or \'moodle/category:viewhiddencategories\' to search on visible,' .
                        '"theme" (string) only return the categories having this theme' .
                        ' - user must have \'moodle/category:manage\' to search on theme'),
                'value' => new external_value(PARAM_RAW, 'the value to match')
                    )
                    ), 'criteria', VALUE_DEFAULT, array()
            ),
            'addsubcategories' => new external_value(PARAM_BOOL, 'return the sub categories infos
                                          (1 - default) otherwise only the category info (0)', VALUE_DEFAULT, 1)
                )
        );
    }

    /**
     * Get categories
     *
     * @param array $criteria Criteria to match the results
     * @param booln $addsubcategories obtain only the category (false) or its subcategories (true - default)
     * @return array list of categories
     * @since Moodle 2.3
     */
    public static function get_categories($criteria = array(), $addsubcategories = true) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        // Validate parameters.
        $params = self::validate_parameters(self::get_categories_parameters(), array('criteria' => $criteria, 'addsubcategories' => $addsubcategories));

        // Retrieve the categories.
        $categories = array();
        if (!empty($params['criteria'])) {

            $conditions = array();
            $wheres = array();
            foreach ($params['criteria'] as $crit) {
                $key = trim($crit['key']);

                // Trying to avoid duplicate keys.
                if (!isset($conditions[$key])) {

                    $context = context_system::instance();
                    $value = null;
                    switch ($key) {
                        case 'id':
                            $value = clean_param($crit['value'], PARAM_INT);
                            break;

                        case 'idnumber':
                            if (has_capability('moodle/category:manage', $context)) {
                                $value = clean_param($crit['value'], PARAM_RAW);
                            } else {
                                // We must throw an exception.
                                // Otherwise the dev client would think no idnumber exists.
                                throw new moodle_exception('criteriaerror', 'webservice', '', null, 'You don\'t have the permissions to search on the "idnumber" field.');
                            }
                            break;

                        case 'name':
                            $value = clean_param($crit['value'], PARAM_TEXT);
                            break;

                        case 'parent':
                            $value = clean_param($crit['value'], PARAM_INT);
                            break;

                        case 'visible':
                            if (has_capability('moodle/category:manage', $context)
                                    or has_capability('moodle/category:viewhiddencategories', context_system::instance())) {
                                $value = clean_param($crit['value'], PARAM_INT);
                            } else {
                                throw new moodle_exception('criteriaerror', 'webservice', '', null, 'You don\'t have the permissions to search on the "visible" field.');
                            }
                            break;

                        case 'theme':
                            if (has_capability('moodle/category:manage', $context)) {
                                $value = clean_param($crit['value'], PARAM_THEME);
                            } else {
                                throw new moodle_exception('criteriaerror', 'webservice', '', null, 'You don\'t have the permissions to search on the "theme" field.');
                            }
                            break;

                        default:
                            throw new moodle_exception('criteriaerror', 'webservice', '', null, 'You can not search on this criteria: ' . $key);
                    }

                    if (isset($value)) {
                        $conditions[$key] = $crit['value'];
                        $wheres[] = $key . " = :" . $key;
                    }
                }
            }

            if (!empty($wheres)) {
                $wheres = implode(" AND ", $wheres);

                $categories = $DB->get_records_select('course_categories', $wheres, $conditions);

                // Retrieve its sub subcategories (all levels).
                if ($categories and ! empty($params['addsubcategories'])) {
                    $newcategories = array();

                    // Check if we required visible/theme checks.
                    $additionalselect = '';
                    $additionalparams = array();
                    if (isset($conditions['visible'])) {
                        $additionalselect .= ' AND visible = :visible';
                        $additionalparams['visible'] = $conditions['visible'];
                    }
                    if (isset($conditions['theme'])) {
                        $additionalselect .= ' AND theme= :theme';
                        $additionalparams['theme'] = $conditions['theme'];
                    }

                    foreach ($categories as $category) {
                        $sqlselect = $DB->sql_like('path', ':path') . $additionalselect;
                        $sqlparams = array('path' => $category->path . '/%') + $additionalparams; // It will NOT include the specified category.
                        $subcategories = $DB->get_records_select('course_categories', $sqlselect, $sqlparams);
                        $newcategories = $newcategories + $subcategories;   // Both arrays have integer as keys.
                    }
                    $categories = $categories + $newcategories;
                }
            }
        } else {
            // Retrieve all categories in the database.
            $categories = $DB->get_records('course_categories');
        }

        // The not returned categories. key => category id, value => reason of exclusion.
        $excludedcats = array();

        // The returned categories.
        $categoriesinfo = array();

        // We need to sort the categories by path.
        // The parent cats need to be checked by the algo first.
        usort($categories, "core_course_external::compare_categories_by_path");

        foreach ($categories as $category) {

            // Check if the category is a child of an excluded category, if yes exclude it too (excluded => do not return).
            $parents = explode('/', $category->path);
            unset($parents[0]); // First key is always empty because path start with / => /1/2/4.
            foreach ($parents as $parentid) {
                // Note: when the parent exclusion was due to the context,
                // the sub category could still be returned.
                if (isset($excludedcats[$parentid]) and $excludedcats[$parentid] != 'context') {
                    $excludedcats[$category->id] = 'parent';
                }
            }

            // Check category depth is <= maxdepth (do not check for user who can manage categories).
            if ((!empty($CFG->maxcategorydepth) && count($parents) > $CFG->maxcategorydepth)
                    and ! has_capability('moodle/category:manage', $context)) {
                $excludedcats[$category->id] = 'depth';
            }

            // Check the user can use the category context.
            $context = context_coursecat::instance($category->id);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $excludedcats[$category->id] = 'context';

                // If it was the requested category then throw an exception.
                if (isset($params['categoryid']) && $category->id == $params['categoryid']) {
                    $exceptionparam = new stdClass();
                    $exceptionparam->message = $e->getMessage();
                    $exceptionparam->catid = $category->id;
                    throw new moodle_exception('errorcatcontextnotvalid', 'webservice', '', $exceptionparam);
                }
            }

            // Return the category information.
            if (!isset($excludedcats[$category->id])) {

                // Final check to see if the category is visible to the user.
                if ($category->visible
                        or has_capability('moodle/category:viewhiddencategories', context_system::instance())
                        or has_capability('moodle/category:manage', $context)) {

                    $categoryinfo = array();
                    $categoryinfo['id'] = $category->id;
                    $categoryinfo['name'] = $category->name;
                    list($categoryinfo['description'], $categoryinfo['descriptionformat']) = external_format_text($category->description, $category->descriptionformat, $context->id, 'coursecat', 'description', null);
                    $categoryinfo['parent'] = $category->parent;
                    $categoryinfo['sortorder'] = $category->sortorder;
                    $categoryinfo['coursecount'] = $category->coursecount;
                    $categoryinfo['depth'] = $category->depth;
                    $categoryinfo['path'] = $category->path;

                    // Some fields only returned for admin.
                    if (has_capability('moodle/category:manage', $context)) {
                        $categoryinfo['idnumber'] = $category->idnumber;
                        $categoryinfo['visible'] = $category->visible;
                        $categoryinfo['visibleold'] = $category->visibleold;
                        $categoryinfo['timemodified'] = $category->timemodified;
                        $categoryinfo['theme'] = $category->theme;
                    }

                    $categoriesinfo[] = $categoryinfo;
                } else {
                    $excludedcats[$category->id] = 'visibility';
                }
            }
        }

        // Sorting the resulting array so it looks a bit better for the client developer.
        usort($categoriesinfo, "core_course_external::compare_categories_by_sortorder");

        return $categoriesinfo;
    }

    /**
     * Sort categories array by path
     * private function: only used by get_categories
     *
     * @param array $category1
     * @param array $category2
     * @return int result of strcmp
     * @since Moodle 2.3
     */
    private static function compare_categories_by_path($category1, $category2) {
        return strcmp($category1->path, $category2->path);
    }

    /**
     * Sort categories array by sortorder
     * private function: only used by get_categories
     *
     * @param array $category1
     * @param array $category2
     * @return int result of strcmp
     * @since Moodle 2.3
     */
    private static function compare_categories_by_sortorder($category1, $category2) {
        return strcmp($category1['sortorder'], $category2['sortorder']);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function get_categories_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'id' => new external_value(PARAM_INT, 'category id'),
            'name' => new external_value(PARAM_TEXT, 'category name'),
            'idnumber' => new external_value(PARAM_RAW, 'category id number', VALUE_OPTIONAL),
            'description' => new external_value(PARAM_RAW, 'category description'),
            'descriptionformat' => new external_format_value('description'),
            'parent' => new external_value(PARAM_INT, 'parent category id'),
            'sortorder' => new external_value(PARAM_INT, 'category sorting order'),
            'coursecount' => new external_value(PARAM_INT, 'number of courses in this category'),
            'visible' => new external_value(PARAM_INT, '1: available, 0:not available', VALUE_OPTIONAL),
            'visibleold' => new external_value(PARAM_INT, '1: available, 0:not available', VALUE_OPTIONAL),
            'timemodified' => new external_value(PARAM_INT, 'timestamp', VALUE_OPTIONAL),
            'depth' => new external_value(PARAM_INT, 'category depth'),
            'path' => new external_value(PARAM_TEXT, 'category path'),
            'theme' => new external_value(PARAM_THEME, 'category theme', VALUE_OPTIONAL),
                ), 'List of categories'
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function add_group_members_parameters() {
        return new external_function_parameters(
                array(
            'members' => new external_multiple_structure(
                    new external_single_structure(
                    array(
                'groupid' => new external_value(PARAM_INT, 'group record id'),
                'userid' => new external_value(PARAM_RAW, 'DNI user'),
                    )
                    )
            )
                )
        );
    }

    /**
     * Add group members
     *
     * @param array $members of arrays with keys userid, groupid
     * @since Moodle 2.2
     */
    public static function add_group_members($members) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::add_group_members_parameters(), array('members' => $members));

        $transaction = $DB->start_delegated_transaction();
        foreach ($params['members'] as $member) {
            $userid = $member['userid'];
            //validamos el DNI para obtener su ID y ahcer el recorrido
            $u = $DB->get_record('user', array('username' => $member['userid']));
            if (is_object($u)) {
                $userid = $u->id;
            } else {
                throw new moodle_exception('El dni del usuario: ' . $member['userid'] . ' no existe en Moodle', 'add_group_members');
            }
            // validate params
            $groupid = $member['groupid'];
            //$userid = $member['userid'];

            $group = groups_get_group($groupid, 'id, courseid', MUST_EXIST);
            $user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id), '*', MUST_EXIST);

            // now security checks
            $context = get_context_instance(CONTEXT_COURSE, $group->courseid);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $group->courseid;
                throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:managegroups', $context);

            // now make sure user is enrolled in course - this is mandatory requirement,
            // unfortunately this is slow
            if (!is_enrolled($context, $userid)) {
                throw new invalid_parameter_exception('Only enrolled users may be members of groups');
            }

            groups_add_member($group, $user);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.2
     */
    public static function add_group_members_returns() {
        return null;
    }

    public static function save_key_parameters() {
        return new external_function_parameters(
                array(
            'key' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'username' => new external_value(PARAM_TEXT, 'Username user'),
                            'token' => new external_value(PARAM_TEXT, 'Password key from PeopleSoft'),
                        )
                    )
            )
                )
        );
    }

    public static function save_key($data) {
        global $DB, $CFG;

        $object = new stdClass();

        if($data[0]['username'] != null && $data[0]['token'] != null){
            $object->username = $data[0]['username'];
            $object->token = $data[0]['token'];
            $object->date = time();
            $temp = $DB->get_record('local_atypaxreports', array('username'=>$data[0]['username']));
            if(is_object($temp)){
                $temp->token = $data[0]['token'];
                $DB->update_record('local_atypaxreports', $temp);
            }else{

                $DB->insert_record('local_atypaxreports', $object);
            }
            return "successful transaction";
        }else{
            return "Error, invalid data";
        }

    }

    public static function save_key_returns() {
        return new external_value(PARAM_TEXT, 'The status message - Successful or Error');
    }

    public static function ucic_report_parameters() {
        return new external_function_parameters(
                array(
            'key' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'username' => new external_value(PARAM_TEXT, 'Username user'),
                            'token' => new external_value(PARAM_TEXT, 'Password key from PeopleSoft'),
                        )
                    )
            )
                )
        );
    }

    public static function ucic_report($data){
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
                AND mdl_course.id =2";
      $data = $this->orderRecords($DB->get_records_sql($sql_group));

      $todo = array();

      $course = $DB->get_record('course',array('id'=>2),'id,fullname');

      $todo = array(
            'course'=> $course
      );
      $course_sections = orderRecords($DB->get_records('course_sections',array('course' => $course->id),'','id,name'));
      $grade_item = $this->orderRecords($DB->get_records('grade_items',array('courseid' => $course->id),'iteminstance','id,itemname'));

      $todo['sections'] = $course_sections;
      foreach ($data as $key => $value) {
        $temp = $todo;
        for($i=0;$i<count($course_sections);$i++) {
          $grade = $DB->get_record('grade_grades',array('itemid'=>$grade_item[$i]->id, 'userid' => $value->id),'id,rawgrade');
          $temp['sections'][$i]->name_item = $grade_item[$i]->itemname;
          $temp['sections'][$i]->grade_item = $grade;
        }
        $value->course = $temp;
      }
    }

    public static function ucic_report_returns() {
        return new external_value(PARAM_TEXT, 'The status message - Successful or Error');
    }

    function orderRecords($record){
      $temp = array();
      foreach ($record as $key => $value) {
        $temp[] = $value;
      }
      return $temp;
    }
}
