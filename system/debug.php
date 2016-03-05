<?php
/**
 * Created by PhpStorm.
 * User: Torris
 * Date: 2011-02-11
 * Time: 15:48:30
 */
function display_errors()
{
    if (ini_get('display_errors') == 'On' OR ini_get('display_errors') == true OR (int)ini_get('display_errors') == 1) {
        return true;
    }

    return false;
}

function mpr2($val, $die = false)
{
    echo '<pre>' . Debug::dump($val);

    if ($die) {
        die();
    }
}

function mpr($val = null, $die = false, $label = '')
{
    // don't run mpr when display_errors is off
    if (!display_errors() || !defined('DEBUG_MODE')) {
        $back = debug_backtrace();
        //logError(E_NOTICE, 'development', 'MPR ' . $back[0]['file'] . ":" . $back[0]['line']);

        return;
    }

    $back = debug_backtrace();
    $h    = ini_get('html_errors');
    ini_set('html_errors', 0);

    echo "<br /><strong>\n" . $back[0]['file'] . "@" . $back[0]['line'] . " " . $label . "\n</strong><br />\n";

    if (!is_null($val)) {
        echo "<pre style='text-align: left; background-color: white; border: 1px solid black;'>\n";

        switch (gettype($val)) {
            case 'array':
                print_r($val);
                break;

            case 'object':
                switch (true) {
                    case $val instanceof DOMDocument:
                        print_r(htmlspecialchars($val->saveXML()));
                        break;

                    case $val instanceof DOMNode:
                        print_r(htmlspecialchars($val->ownerDocument->saveXML($val)));
                        break;

                    case $val instanceof SoapClient:
                        print_r(htmlspecialchars($val->__getLastRequest()));
                        break;

                    default:
                        print_r($val);
                        break;
                }
                break;

            default:
                var_dump($val);
                break;
        }

        echo "\n</pre>";
    }

    ini_set('html_errors', $h);

    if (function_exists("xdebug_enable")) {
        xdebug_enable();
    }

    if ($die) {
        die();
    }
}

function consoleLog($msg, $type = Kohana_Log::INFO)
{
    Kohana::$log->add($type, $msg);
}