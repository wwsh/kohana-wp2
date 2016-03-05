<?php
/**
 * Plugin Name: Kohana-WP2
 * Plugin URI: http://wwsh.io/kohanawp2
 * Description: Enables the integration of Kohana 3.x PHP Applications with Wordpress 4.x
 * Author: Thomas Parys
 * Version: 2.0
 * Author URI: http://wwsh.io
 * Comment: Based on original work by Mario L Gutierrez. It's a heavy rework of his original
 * 2010' code, which was no longer working with recent WP editions.
 * See here: https://github.com/mgutz/kohana-wp
 */

require 'application/classes/kwp/plugin.php';

KWP_Plugin::factory()->run();