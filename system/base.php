<?php defined('SYSPATH') or die('No direct script access.');

if (!defined('KOHANA_START_TIME')) {
    /**
     * Define the start time of the application, used for profiling.
     */
    define('KOHANA_START_TIME', microtime(true));
}

if (!defined('KOHANA_START_MEMORY')) {
    /**
     * Define the memory usage at the start of the application, used for profiling.
     */
    define('KOHANA_START_MEMORY', memory_get_usage());
}

/**
 * Kohana translation/internationalization function. The PHP function
 * [strtr](http://php.net/strtr) is used for replacing parameters.
 *
 *    ___('Welcome back, :user', array(':user' => $username));
 *
 * @uses    I18n::get
 * @param   string  text to translate
 * @param   array   values to replace in the translated text
 * @param   string  target language
 * @return  string
 */
function ___($string, array $values = null, $lang = 'en-us')
{
    if ($lang !== I18n::$lang) {
        // The message and target languages are different
        // Get the translation for this message
        $string = I18n::get($string);
    }

    return empty($values) ? $string : strtr($string, $values);
}