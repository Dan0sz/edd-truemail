<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/correct-contacts
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContacts;

use CorrectContacts\Admin\Settings;
use WpOrg\Requests\Exception\InvalidArgument;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * Plugin constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		new Admin\Settings();
		new Ajax();
		new Compatibility\EDD();
		new Compatibility\WooCommerce();
		
		$this->init();
	}
	
	/**
	 * Initializes the class.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}
	
	/**
	 * Enqueues scripts and styles. Loads minified versions if SCRIPT_DEBUG is true.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$ext = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ? '.min' : '';
		
		wp_enqueue_script( 'correct-contacts', plugin_dir_url( CC_PLUGIN_FILE ) . "assets/js/correct-contacts$ext.js", [
			'wp-util',
		], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/js/correct-contacts.js' ), false );
		wp_enqueue_style( 'correct-contacts', plugin_dir_url( CC_PLUGIN_FILE ) . "assets/css/correct-contacts$ext.css", [], filemtime( plugin_dir_path( CC_PLUGIN_FILE ) . 'assets/css/correct-contacts.css' ) );
		
		$selectors = get_option( Settings::FIELD_SELECTORS, '#edd-email' );
		
		wp_localize_script( 'correct-contacts', 'cc_ajax_obj', [
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'selectors' => $selectors,
		] );
	}
}
