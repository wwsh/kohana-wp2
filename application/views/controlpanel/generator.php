<?php

/**
 * Class Views_ControlPanel_Generator
 */
class Views_ControlPanel_Generator
{
    /**
     * @return string
     */
    function page_templates()
    {
        ob_start();
        page_template_dropdown(get_option('kwp_page_template'));
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }
}
