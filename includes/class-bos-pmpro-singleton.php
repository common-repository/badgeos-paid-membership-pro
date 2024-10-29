<?php
/**
 * Singleton class
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access is not allowed.' );
}

trait BOS_PMPro_Singleton {
	/**
	 * The current instance
	 */
	protected static $instance;

	/**
	 * Returns the current instance
	 */
	final public static function get_instance() {
		return isset( static::$instance )
			? static::$instance
			: static::$instance = new static;
	}

	/**
	 * WP_BP_Singleton constructor.
	 */
	final private function __construct() {
		$this->init();
	}

	// Prevent instances
	protected function init() {
	}

	final private function __wakeup() {
	}

	final private function __clone() {
	}
}