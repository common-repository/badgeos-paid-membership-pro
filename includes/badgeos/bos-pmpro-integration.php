<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class BOS_PMPRO_Integration
 */
class BOS_PMPRO_Integration {

    /**
	 * BadgeOS Paid Memberships Pro Triggers
	 *
	 * @var array
	 */
	public static $triggers = array();

	/**
	 * Actions to forward for splitting an action up
	 *
	 * @var array
	 */
	public static $actions = array();

    /**
     * BOS_PMPRO_Integration initialize
     */
    public static function init() {

        /**
         * Paid Memberships Pro Action Hooks
         */
	    self::$triggers = array(
            'bos_pmpro_enrol_level' => __( 'Enroll to Level', 'bos-pmpro' ),
            'bos_pmpro_enrol_expire' => __( 'Membership Expire', 'bos-pmpro' ),
            'bos_pmpro_enrol_cancel' => __( 'Membership Cancel', 'bos-pmpro' ),
		);

		/**
         * Actions that we need split up
         */
		self::$actions = array(
			'pmpro_after_change_membership_level' =>  array(
			    'actions' => array(
			        'bos_pmpro_enrol_level',
                    'bos_pmpro_enrol_free_level',
                    'bos_pmpro_enrol_paid_level',
                    'bos_pmpro_enrol_subscription_level',
                    'bos_pmpro_enrol_cancel'
                )
            ),
			'pmpro_membership_post_membership_expiry' => array(
				'actions' => array(
					'bos_pmpro_enrol_expire'
				)
			),
        );

        add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ), 11 );

    }

	public static function activate() {

		if (!current_user_can('activate_plugins')) return;
	}

	public static function deactivate() {

		if (!current_user_can('activate_plugins')) return;
	}

	public static function missing_dependent_plugin_notice() {
		deactivate_plugins(plugin_basename(BOS_PMPRO_FILE), true);
		if (isset($_GET['activate'])) unset($_GET['activate']);
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e('BadgeOS Paid Memberships Pro requires <a href="https://wordpress.org/plugins/badgeos/" target="_blank">BadgeOS</a> and <a href="https://wordpress.org/plugins/paid-memberships-pro/" target="_blank">Paid Memberships Pro</a> plugins to be activated.', 'bos-pmpro'); ?></p>
		</div>
		<?php
	}

    /**
     * Setup action forwarding and initialize rules engine if required plugins activated, otherwise display error
     */
	public static function plugins_loaded() {

		if ( self::is_plugins_activated() ) {
		    self::action_forwarding();
			BOS_PMPRO_Rules_Engine::get_instance(); //Initialize rules engine
		} else {
			add_action('admin_notices', array(__CLASS__, 'missing_dependent_plugin_notice'));
		}
    }
    
    /**
     * Check if BadgeOS and PMPRO both
     *
     * @return bool
     */
	public static function is_plugins_activated() {

		if ( !class_exists('BadgeOS') || !defined('PMPRO_VERSION') ) {
		    return false;
		}

		return true;
	}

    /**
     * Forward WP actions into a new set of actions
     */
	public static function action_forwarding() {
		foreach ( self::$actions as $action => $args ) {
			$priority = 10;
			$accepted_args = 20;

			if ( is_array( $args ) ) {
				if ( isset( $args[ 'priority' ] ) ) {
					$priority = $args[ 'priority' ];
				}

				if ( isset( $args[ 'accepted_args' ] ) ) {
					$accepted_args = $args[ 'accepted_args' ];
				}
			}

			add_action( $action, array( __CLASS__, 'action_forward' ), $priority, $accepted_args );
		}
	}

    /**
     * Forward a specific WP action into a new set of actions
     *
     * @return mixed|null
     */
	public static function action_forward() {
		$action = current_filter();
		$args = func_get_args();

		if ( isset( self::$actions[ $action ] ) ) {
			if ( is_array( self::$actions[ $action ] )
				 && isset( self::$actions[ $action ][ 'actions' ] ) && is_array( self::$actions[ $action ][ 'actions' ] )
				 && !empty( self::$actions[ $action ][ 'actions' ] ) ) {
				foreach ( self::$actions[ $action ][ 'actions' ] as $new_action ) {
			
					$action_args = $args;

					array_unshift( $action_args, $new_action );

					call_user_func_array( 'do_action', $action_args );
				}

				return null;
			} elseif ( is_string( self::$actions[ $action ] ) ) {
				$action =  self::$actions[ $action ];
			}
		}
		array_unshift( $args, $action );

		return call_user_func_array( 'do_action', $args );
	}
}

BOS_PMPRO_Integration::init();