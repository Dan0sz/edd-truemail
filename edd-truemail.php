<?php
/**
 * Plugin Name: Truemail for Easy Digital Downloads
 * Plugin URI: https://daan.dev/wordpress/easy-moneybird-edd/
 * Description: This plugin adds Truemail email address validation to Easy Digital Downloads' checkout.
 * Version: 1.1.6
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev/about/
 * Text Domain: edd-truemail
 */

defined( 'ABSPATH' ) || exit;

/**
 * Define constants.
 */
define( 'EDD_TM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDD_TM_PLUGIN_FILE', __FILE__ );
define( 'EDD_TM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
require_once EDD_TM_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * All systems GO!!!
 *
 * @var \EDD\Truemail\Plugin $edd_tm_basic
 */
$edd_tm_basic = new EDD\Truemail\Plugin();
