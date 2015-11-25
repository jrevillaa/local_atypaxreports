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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localwstemplate
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// We defined the web service functions to install.
$functions = array(
    'local_atypaxreports_hello_world' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'hello_world',
        'classpath' => 'local/atypaxreports/externallib.php',
        'description' => 'Return Hello World FIRSTNAME. Can change the text (Hello World) sending a new text as parameter',
        'type' => 'read',
    ),
    'local_atypaxreports_create_course' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'create_courses',
        'classpath' => 'local/atypaxreports/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_course_create_courses()',
        'type' => 'write',
        'capabilities' => 'moodle/course:create,moodle/course:visibility',
    ),
    'local_atypaxreports_create_users' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'create_users',
        'classpath' => 'local/atypaxreports/externallib.php',
        'description' => 'Create users.',
        'type' => 'write',
        'capabilities' => 'moodle/user:create',
    ),
    'local_atypaxreports_enrol_users' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'enrol_users',
        'classpath' => 'local/atypaxreports/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as enrol_manual_enrol_users()',
        'capabilities' => 'enrol/manual:enrol',
        'type' => 'write',
    ),
    'local_atypaxreports_grade_course' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'grade_course',
        'classpath' => 'local/atypaxreports/externallib.php',
        'description' => 'Return grades by course and student.',
        'type' => 'read',
    ),
    'local_atypaxreports_grade_course_detail' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'grade_course_detail',
        'classpath' => 'local/atypaxreports/externallib.php',
        'description' => 'Return grades by course and student.',
        'type' => 'read',
    ),
    'local_atypaxreports_get_courses' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'get_courses',
        'classpath' => 'local/atypaxreports/externallib.php',        
        'description' => 'Return course details',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:view,moodle/course:update,moodle/course:viewhiddencourses',
    ),
    'local_atypaxreports_create_course_categorie' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'create_categories',
        'classpath' => 'local/atypaxreports/externallib.php',        
        'description' => 'Create course category',
        'type'        => 'write',
        'capabilities'=> 'moodle/category:manage',
    ),    
    'local_atypaxreports_get_users_by_id' => array(
        'classname' => 'local_atypaxreports_external',
        'methodname' => 'get_users_by_id',
        'classpath' => 'local/atypaxreports/externallib.php',        
        'description' => 'Get users by id.',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update',
    ),
    'local_atypaxreports_get_categories' => array(
        'classname'   => 'local_atypaxreports_external',
        'methodname'  => 'get_categories',
        'classpath'   => 'local/atypaxreports/externallib.php',
        'description' => 'Return category details',
        'type'        => 'read',
        'capabilities'=> 'moodle/category:viewhiddencategories',
    ),    
    'local_atypaxreports_course_config' => array(
        'classname'   => 'local_atypaxreports_external',
        'methodname'  => 'course_config',
        'classpath'   => 'local/atypaxreports/externallib.php',
        'description' => 'Return grade details',
        'type'        => 'read',
    ),
    'local_atypaxreports_core_group_get_course_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_course_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'Returns all groups in specified course.',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:managegroups',
    ),
    'local_atypaxreports_core_group_add_group_members' => array(
        'classname'   => 'local_atypaxreports_external',
        'methodname'  => 'add_group_members',
        'classpath'   => 'local/atypaxreports/externallib.php',
        'description' => 'Adds group members.',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:managegroups',
    ),
    'local_atypaxreports_core_group_create_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'create_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'Creates new groups.',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:managegroups',
    ),    
    'local_atypaxreports_save_key' => array(
        'classname'   => 'local_atypaxreports_external',
        'methodname'  => 'save_key',
        'classpath'   => 'local/atypaxreports/externallib.php',
        'description' => 'Update token.',
        'type'        => 'write',
    ),   
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'People Soft WebService' => array(
        'functions' => array('local_atypaxreports_hello_world',
            'local_atypaxreports_create_course',
            'local_atypaxreports_create_users',
            'local_atypaxreports_enrol_users',
            'local_atypaxreports_grade_course',
            'local_atypaxreports_grade_course_detail',
            'local_atypaxreports_get_courses',
            'local_atypaxreports_create_course_categorie',
            'local_atypaxreports_get_users_by_id',
            'local_atypaxreports_get_categories',
            'local_atypaxreports_course_config',
            'local_atypaxreports_core_group_get_course_groups',
            'local_atypaxreports_core_group_add_group_members',
            'local_atypaxreports_core_group_create_groups',
            'local_atypaxreports_save_key',),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
