<?php
/**
 * Options handler
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact;

defined( 'ABSPATH' ) || exit;

use CorrectContact\Admin\Settings;

class Options {
	const OPTION_NAME = 'correct_contact_options';
	
	/**
	 * Get a single setting from the options array.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public static function get( $key ) {
		$defaults = [
			Settings::DO_TOKEN        => '',
			Settings::FIELD_SELECTORS => 'input[type="email"]',
			Settings::BLOCK_PURCHASE  => '',
			Settings::ACCESS_TOKEN    => '',
			Settings::APP_URL         => '',
			Settings::REGION          => '',
		];
		$options  = get_option( self::OPTION_NAME, [] );
		
		if ( ! is_array( $options ) && isset( $defaults[ $key ] ) ) {
			return $defaults[ $key ];
		}
		
		return $options[ $key ] ?? $defaults[ $key ];
	}
	
	/**
	 * Update a single setting in the options array.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function update( $key, $value ) {
		$options         = get_option( self::OPTION_NAME, [] );
		$options[ $key ] = $value;
		
		return update_option( self::OPTION_NAME, $options );
	}
	
	/**
	 * Delete a single setting from the options array.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function delete( $key ) {
		$options = get_option( self::OPTION_NAME, [] );
		
		if ( isset( $options[ $key ] ) ) {
			unset( $options[ $key ] );
			
			return update_option( self::OPTION_NAME, $options );
		}
		
		return false;
	}
}
