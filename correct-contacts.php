<?php
/**
 * Plugin Name: Correct Contacts for WordPress
 * Plugin URI: https://correct.contact
 * Description: This plugin adds email address validation to any field in any WordPress form
 * Version: 1.1.8
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev/about/
 * Text Domain: correct-contacts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Define constants.
 */
define( 'CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CC_PLUGIN_FILE', __FILE__ );
define( 'CC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
require_once CC_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * All systems GO!!!
 *
 * @var \CorrectContacts\Plugin $cc_basic
 */
$cc_basic = new CorrectContacts\Plugin();
