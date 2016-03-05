<?php

/**
 * Class Views_Layout_ControlPanel
 */
class Views_Layout_ControlPanel
{
    // add other tabs here and they will show as a tab in control panel
    public $tab_pages = array(
        array('caption' => 'General Settings', 'action' => 'controlpanel/index'),
        array('caption' => 'Page Routing', 'action' => 'controlpanel/routes'),
        array('caption' => 'Generator', 'action' => 'generator/index')
    );

    function nav_list()
    {
        $current_action = KWP_Plugin::globals('current_controller') . '/' . KWP_Plugin::globals('current_action');

        // tab_pages assigned as a property via pipe rendering from context Controller_ControlPanel
        foreach ($this->tab_pages as $page) {
            $navs[] = array(
                'class'   => $current_action == $page['action'] ? 'active' : '',
                'href'    => $this->app_url . '/' . $page['action'],
                'caption' => $page['caption']
            );
        }

        return $navs;
    }

    function flash()
    {
        ob_start();
        settings_errors();
        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }
}


 
