<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/edd-truemail
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023 Daan van den Bergh
 */
namespace EDD\Truemail;

defined( 'ABSPATH' ) || exit;

class Plugin {
	public function __construct() {
        new Admin\Settings();
		new Ajax();

		$this->init();
	}

	private function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts() {
		if ( edd_is_checkout() ) {
			wp_enqueue_script( 'edd-truemail', plugin_dir_url( EDD_TM_PLUGIN_FILE ) . 'assets/js/edd-truemail.js', [ 'edd-ajax' ], filemtime( plugin_dir_path( EDD_TM_PLUGIN_FILE ) . 'assets/js/edd-truemail.js' ), false );
			wp_enqueue_style( 'edd-truemail', plugin_dir_url( EDD_TM_PLUGIN_FILE ) . 'assets/css/edd-truemail.css', [], filemtime( plugin_dir_path( EDD_TM_PLUGIN_FILE ) . 'assets/css/edd-truemail.css' ) );
		}
	}
}
