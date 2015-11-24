<?php

//define('INTERNAL_ACCESS',1);
/**
 *
 * @package    wspeoplesoft
 * @subpackage lib
 * @copyright  2015 ATYPAX
 * @author     Jair Revilla
 * @version    1.0
 */

class moo_webservices
{
    protected $user;
    protected $config;
    protected $course;
    
    public function __construct(array $configs = null)
    {
        if ($configs !== null) {
            $this->set_configs($configs);
        }
    }

    public function set_configs(array $configs)
    {
        foreach ($configs as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }

        return $this;
    }
    
    /**
     * setup administrator links and settings
     *
     * @param object $admin
     */
    public static function set_adminsettings($admin)
    {
        $me = new self();
        $me->grab_moodle_globals();
        $context = context_course::instance(SITEID);

        $admin->add('localplugins', new admin_category('wspeoplesoft', $me->get_string('name')));
        //$admin->add('wspeoplesoft', new admin_externalpage('pathxmlwspeoplesoft', $me->get_string('insertpath'), $me->get_config('wwwroot') . '/local/globalmessage/index.php?id=' . SITEID, 'moodle/site:config', false, $context));

        $temp = new admin_settingpage('wspeoplesoftsettings', $me->get_string('wssettings'));
        $temp->add(new admin_setting_configcheckbox('wspeoplesoftcourseenable', $me->get_string('wsenabledcourse'), $me->get_string('enabledcoursedesc'), 0));
        $temp->add(new admin_setting_configtext('wspeoplesoftcoursepath', $me->get_string('insertpathcourse'),
                       $me->get_string('insertpathcoursedesc'), null, PARAM_TEXT));
        
       /* $temp->add(new admin_setting_configcheckbox('wspeoplesoftuserenable', $me->get_string('wsenableduser'), $me->get_string('enableduserdesc'), 0));
        $temp->add(new admin_setting_configtext('wspeoplesoftuserpath', $me->get_string('insertpathuser'),
                       $me->get_string('insertpathuserdesc'), null, PARAM_TEXT));
        
        $temp->add(new admin_setting_configcheckbox('wspeoplesoftmemberenable', $me->get_string('wsenabledmember'), $me->get_string('enabledmemberdesc'), 0));
        $temp->add(new admin_setting_configtext('wspeoplesoftmemberpath', $me->get_string('insertpathmembers'),
                       $me->get_string('insertpathmemberdesc'), null, PARAM_TEXT));*/
        
        $temp->add(new admin_setting_configtext('wspeoplesofttoken', $me->get_string('wstoken'),
                       $me->get_string('wstokendesc'), null, PARAM_TEXT));

        $admin->add('wspeoplesoft',$temp);

        $admin->add('localplugins', new admin_category('gradespeoplesoft', 'Grades Export to Peoplesoft'));
        $temp = new admin_settingpage('gradespeoplesoftsettings', 'Add web service path');
        $temp->add(new admin_setting_configtext('gradespeoplesoftpath', 'Insert Path',
                       'Path Web service Peoplesoft', null, PARAM_TEXT));

        $admin->add('gradespeoplesoft',$temp);


    }
    
    
    public function grab_moodle_globals()
    {
        global $CFG, $USER, $COURSE;

        $this->user = $USER;
        $this->course = $COURSE;
        $this->config = $CFG;

        return $this;
    }
    
    public function get_string($name, $a = null)
    {
        return stripslashes(get_string($name, 'local_wspeoplesoft', $a));
    }
    
    public function get_config($name = null)
    {
        if ($name !== null && isset($this->config->{$name})) {
            return $this->config->{$name};
        }
        return $this->config;
    }
    
    public function get_string_fromcore($name, $a = null)
    {
        return get_string($name, '', $a);
    }


}
