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

class Ajax {
    public function __construct() {
	    $this->init();
    }

    private function init() {
        add_action( 'wp_ajax_edd_truemail_verify_email', [ $this, 'verify' ] );
    }

    public function verify() {
        $email  = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : false;
        $client = new Client();
        $result = $client->verify( $email );

        if ( ! $result ) {
            wp_send_json_error( [ 'message' => __( 'Email address doesn\'t seem to exist.', 'edd-truemail' ) ] );
        }

        wp_send_json_success(
            [
				'message' => __( 'Email address verified.', 'edd-truemail' ),
				'status'  => 200,
            ]
        );
    }
}
