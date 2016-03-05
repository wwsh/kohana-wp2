<?php defined('KWP_DOCROOT') or die('No direct script access.');

/**
 * Class KWP_Bootstrapper
 */
final class KWP_Bootstrapper
{

    /**
     * Bootstraps Kohana system from a route.
     *
     * There is NECESSARY complexity in bootstrapping Kohana. Applications can have their own modules and system.
     * This means Kohana cannot be bootstrapped generically. Unfortunately, Kohana MVC at bootstrap defines constants
     * such as APPPATH, MODPATH, SYSPATH which cannot be redefined. This means Kohana-WP cannot take advantage
     * of Kohana's features such as autoloading until the Kohana-WP plugin has enough info to bootstrap
     * an application. This explains why there are 'require' in some parts of the code. The code is more complex than
     * it has to be for a Kohana based application.
     *
     * @static
     * @param  $route application/controller(/action(/args(...(/argn)))
     * @return void
     */
    static function boot($route)
    {
        static $last_app = "";
        list($app, $controller) = explode('/', $route, 2);

        $strapper = new KWP_Bootstrapper();
        $docroot  = $strapper->route_specific_constants($route);

        // no need to reload if using the same app
        if ($last_app == $app) {
            return;
        }
        $last_app = $app;

        // use Kohana-WP's default system if application does not provide it
        if (is_file($docroot . '/system/classes/kohana/core.php')) {
            $system = 'system';
        } else {
            $system = KWP_DOCROOT . 'system';
        }

        $strapper->index($docroot, 'application', 'modules', $system);
        $strapper->bootstrap();
    }

    /**
     * @param $route
     * @return string
     * @throws Exception
     */
    private function route_specific_constants($route)
    {
        @list($app_name, $controller, $action, $args) = explode('/', $route, 4);

        $app_root        = KOHANA_APPS_ROOT . $app_name;
        $controller      = str_replace('_', DIRECTORY_SEPARATOR, $controller); // widgets_sandbox etc.
        $controller_path = "$app_root/application/classes/controller/$controller.php";
        if (!is_file($controller_path)) {
            if (defined('KWP_IN_ADMIN')) {
                $app_root        = KOHANA_FIXED_APPS_ROOT . $app_name;
                $controller      = str_replace('_', DIRECTORY_SEPARATOR, $controller); // widgets_sandbox etc.
                $controller_path = "$app_root/application/classes/controller/$controller.php";
                if (!is_file($controller_path)) {
                    throw new Exception(sprintf('Invalid kohana route = %s/%s', $app_name, $controller));
                }
            } else {
                throw new Exception(sprintf('Invalid kohana route = %s/%s', $app_name, $controller));
            }
        }


        KWP_Plugin::set_global('current_controller', $controller);
        KWP_Plugin::set_global('current_action', empty($action) ? 'index' : $action);
        KWP_Plugin::set_global('current_arguments', $args);

        // define constants for URL helpers
        $page_url = $this->page_url();

        // get rid of existing kr since any outgoing URL will be rebuilt (multiple appends otherwise)
        $page_url = preg_replace('/(&|\?)kr=.*/i', '', $page_url);

        $prefix = strpos($page_url, '?') ? '&kr=' : '?kr=';
        KWP_Plugin::set_global('current_page_url', $page_url . $prefix);
        KWP_Plugin::set_global('current_app_url', $page_url . $prefix . $app_name);
        KWP_Plugin::set_global('current_controller_url', $page_url . $prefix . $app_name . "/$controller");

        return $app_root;
    }


    /**
     * recreate Kohana 3.0 index.php file with some modifications
     */
    private function index($docroot, $app_dir = 'application', $mod_dir = 'modules', $sys_dir = 'system')
    {
        /**
         * The directory in which your application specific resources are located.
         * The application directory must contain the bootstrap.php file.
         *
         * @see  http://kohanaframework.org/guide/about.install#application
         */
        $application = $app_dir;

        /**
         * The directory in which your modules are located.
         *
         * @see  http://kohanaframework.org/guide/about.install#modules
         */
        $modules = $mod_dir;

        /**
         * The directory in which the Kohana resources are located. The system
         * directory must contain the classes/kohana.php file.
         *
         * @see  http://kohanaframework.org/guide/about.install#system
         */
        $system = $sys_dir;

        /**
         * The default extension of resource files. If you change this, all resources
         * must be renamed to use the new extension.
         *
         * @see  http://kohanaframework.org/guide/about.install#ext
         */
        define('EXT', '.php');

        /**
         * Set the PHP error reporting level. If you set this in php.ini, you remove this.
         * @see  http://php.net/error_reporting
         *
         * When developing your application, it is highly recommended to enable notices
         * and strict warnings. Enable them by using: E_ALL | E_STRICT
         *
         * In a production environment, it is safe to ignore notices and strict warnings.
         * Disable them by using: E_ALL ^ E_NOTICE
         *
         * When using a legacy application with PHP >= 5.3, it is recommended to disable
         * deprecated notices. Disable with: E_ALL & ~E_DEPRECATED
         */
        //error_reporting(E_ALL | E_STRICT);

        /**
         * End of standard configuration! Changing any of the code below should only be
         * attempted by those with a working knowledge of Kohana internals.
         *
         * @see  http://kohanaframework.org/guide/using.configuration
         */

        // Set the full path to the docroot
        define('DOCROOT', realpath($docroot) . DIRECTORY_SEPARATOR);

        // Make the application relative to the docroot
        if (!is_dir($application) AND is_dir(DOCROOT . $application)) {
            $application = DOCROOT . $application;
        }

        // Make the modules relative to the docroot
        if (!is_dir($modules) AND is_dir(DOCROOT . $modules)) {
            $modules = DOCROOT . $modules;
        }

        // Make the system relative to the docroot
        if (!is_dir($system) AND is_dir(DOCROOT . $system)) {
            $system = DOCROOT . $system;
        }

        // Define the absolute paths for configured directories
        if (!defined('APPPATH')) {
            define('APPPATH', realpath($application) . DIRECTORY_SEPARATOR);
        }
        if (!defined('MODPATH')) {
            define('MODPATH', realpath($modules) . DIRECTORY_SEPARATOR);
        }
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath($system) . DIRECTORY_SEPARATOR);
        }

//		// Load the base, low-level functions
        require_once SYSPATH . 'base' . EXT;

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

        // Load the core Kohana class
        require_once SYSPATH . 'classes/kohana/core' . EXT;


        if (is_file(APPPATH . 'classes/kohana' . EXT)) {
            // Application extends the core
            require_once APPPATH . 'classes/kohana' . EXT;
        } else {
            // Load empty core extension
            require_once SYSPATH . 'classes/kohana' . EXT;
        }

        // define a constant for widgets which use AJAX
        if (!defined('KOHANA_AJAX_URL_TPL')) {
            define('KOHANA_AJAX_URL_TPL', '/redirect/admin-ajax.php?action=kohana_ajax&route=site/%s/jajax&kohana_action=%s');
        }

    }

    /**
     * Recreate the Kohana application/bootstrap.php process with some modifications
     *
     * @static
     * @return void
     */
    private function bootstrap()
    {
        //-- Environment setup --------------------------------------------------------

        /**
         * Set the default time zone.
         *
         * @see  http://kohanaframework.org/guide/using.configuration
         * @see  http://php.net/timezones
         */
        //date_default_timezone_set('America/Chicago');

        /**
         * Set the default locale.
         *
         * @see  http://kohanaframework.org/guide/using.configuration
         * @see  http://php.net/setlocale
         */
        //setlocale(LC_ALL, 'en_US.utf-8');

        /**
         * Enable the Kohana auto-loader.
         *
         * @see  http://kohanaframework.org/guide/using.autoloading
         * @see  http://php.net/spl_autoload_register
         */
        spl_autoload_register(array('Kohana', 'auto_load'));

        /**
         * enable composer autoloader
         */
        require APPPATH . '/vendor/autoload.php';

        /**
         * Enable the Kohana auto-loader for unserialization.
         *
         * @see  http://php.net/spl_autoload_call
         * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
         */
        ini_set('unserialize_callback_func', 'spl_autoload_call');

        //-- Configuration and initialization -----------------------------------------

        /**
         * Initialize Kohana, setting the default options.
         *
         * The following options are available:
         *
         * - string   base_url    path, and optionally domain, of your application   NULL
         * - string   index_file  name of your index file, usually "index.php"       index.php
         * - string   charset     internal character set used for input and output   utf-8
         * - string   cache_dir   set the internal cache directory                   APPPATH/cache
         * - boolean  errors      enable or disable error handling                   TRUE
         * - boolean  profile     enable or disable internal profiling               TRUE
         * - boolean  caching     enable or disable internal caching                 FALSE
         */
        Kohana::init(array(
                         'charset'    => 'utf-8',
                         'base_url'   => '/',
                         'index_file' => false,
                     ));

        /**
         * Attach the file write to logging. Multiple writers are supported.
         */
        //Kohana::$log->attach(new Kohana_Log_File(APPPATH . 'logs'));

        /**
         * Attach a file reader to config. Multiple readers are supported.
         */
        Kohana::$config->attach(new Kohana_Config_File);

        $modules = $this->get_combined_modules(array(
                                                   WP_PLUGIN_DIR . '/kohana-wp2/modules',
                                                   MODPATH
                                               ));

        Kohana::modules($modules);

        /**
         * Set the routes. Each route must have a minimum of a name, a URI and a set of
         * defaults for the URI.
         */
        Route::set('default', '(<app>/<controller>(/<action>(/<id>)))')
             ->defaults(array(
                            'controller' => 'welcome',
                            'action'     => 'index',
                        ));

        set_exception_handler(array('KWP_Bootstrapper', 'exception_handler'));

        // Default session = cache
        Session::$default = 'wpsession';
        // setup cookie salt
        Cookie::$salt = 'toolkit';
        // defining the cache connector
        Cache::$default = 'global';
        // set language
        I18n::lang(get_locale());
    }


    /**
     * Register Kohana modules.
     *
     * All modules located inside an applications modules/ folder are registered unless the module module
     * directory is suffixed with '.off'.
     */
    private function get_combined_modules($dirs)
    {
        foreach ($dirs as $dir) {
            $mod = $this->get_dir_names($dir);
            foreach ($mod as $name => $path) {
                if (substr($name, -4) != '.off') {
                    $modules[$name] = $path;
                }
            }
        }

        return $modules;
    }

    /**
     * Gets valid directory names from a path.
     * @param  $path The path.
     * @return array
     */
    private function get_dir_names($path)
    {
        $dirs = array();
        if (is_dir($path)) {
            if ($handle = opendir($path)) {
                while (false !== ($dir = readdir($handle))) {
                    if ($dir == "." || $dir == "..") {
                        continue;
                    }

                    $full_path = "$path/$dir";
                    if (is_dir($full_path)) {
                        $dirs[$dir] = $full_path;
                    }
                }
                closedir($handle);
            }
        }

        return $dirs;
    }

    private function page_url()
    {
        $pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }

        return $pageURL;
    }

    /**
     * @param Exception $e
     */
    public static function exception_handler(Exception $e)
    {
        $response = Kohana_Exception::_handler($e);

        // Send the response to the browser
        echo $e->getMessage();

        return;
    }
}