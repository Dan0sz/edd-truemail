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

		$this->init();
	}

	private function init() {
	}
}
