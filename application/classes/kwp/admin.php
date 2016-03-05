<?php defined('KWP_DOCROOT') or die('No direct script access.');

/**
 * Class KWP_Admin
 */
class KWP_Admin
{
    /**
     * Add settings link to plugin admin page
     * @param $links
     * @param $file
     * @return array
     */
    static function plugin_row_meta($links, $file)
    {
        $plugin = plugin_basename(__FILE__);
        // create link
        if ($file == $plugin) {
            return array_merge(
                $links,
                array(sprintf('<a href="options-general.php?page=%s">%s</a>',
                              'Kohana', __('Settings')))
            );
        }
        return $links;
    }

    /**
     * Function adds the Kohana options page to wordpress dashboard
     */
    static function show_admin_items()
    {
        add_options_page("kohana-wp2", "kohana-wp2", 'manage_options', "kohana-wp2", "KWP_Admin_Filter::show_control_panel");
        add_meta_box('kwp_routing', __('Kohana3-WordPress Integration', KWP_DOMAIN), 'Controller_PageOptions::index',
                     'page', 'advanced');
    }

    /**
     * Function includes the Kohana options/admin page for display
     */
    static function show_control_panel()
    {
        echo kohana('kohana-wp2/controlpanel/index');
    }
}
 
