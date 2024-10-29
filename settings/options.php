<?php
defined( 'ABSPATH' ) || exit;

if( !class_exists('BOS_PMPRO_OPTIONS') ) {

	class BOS_PMPRO_Options {

		private $settings_page_slug = 'bos_pmpro_settings';
		private $default_tab = 'general';
		private $current_tab;
		private $settings;

		public function __construct() {

			$tab = filter_input(INPUT_GET, 'tab');

			$this->current_tab = !empty($tab) ? $tab : $this->default_tab;

			add_action( 'admin_menu', array( $this, 'admin_menu_cb' ), 15 );

			add_filter( 'admin_footer_text', array( $this, 'remove_footer_admin' ), 15 );

			add_action('admin_post_wn_bos_pmpro_submit_settings', [$this, 'submit_settings_cb'], 15);

			add_action('admin_notices', [$this, 'settings_saved_notice'], 15);

			$this->settings = bos_pmpro_get_settings();

		}

		public function admin_menu_cb() {
			add_submenu_page(
				'badgeos_badgeos',
				__( 'BadgeOS PMPro', 'bos-pmpro' ),
				__( 'BadgeOS PMPro', 'bos-pmpro' ),
				'manage_options',
				$this->settings_page_slug,
				[ $this, 'settings_page_cb' ]
			);
		}

		public function settings_page_cb() {
			?>
			<div class="wrap">
				<div class="icon-options-general icon32"></div>
				<h1><?php echo __( 'BadgeOS PMPro Settings', 'bos-pmpro' ); ?></h1>
				<div class="nav-tab-wrapper">
					<?php
					$settings_sections = $this->get_setting_sections();

					foreach( $settings_sections as $tab => $section ) {
						$settings_tab_url = $this->get_settings_page_url($tab);
					    ?>
						<a href="<?php echo $settings_tab_url; ?>"
						   class="nav-tab <?php echo $this->current_tab == $tab ? 'nav-tab-active' : ''; ?>">
							<span class="dashicons <?php echo $section['icon']; ?>"></span>
							<?php _e( $section['title'], 'bos-pmpro' ); ?>
						</a>
						<?php
					}
					?>
				</div>
				<?php include( 'admin-templates/' . $this->current_tab . '.php' ); ?>
			</div>
			<?php
		}

		function get_settings_page_url($tab=false) {
		    $url = admin_url('admin.php');
		    $url = add_query_arg('page', $this->settings_page_slug, $url);
		    if($tab) {
			    $url = add_query_arg( 'tab', $tab, $url );
		    }
		    return $url;
        }

		function get_setting_sections() {

			$settings_sections = array(

				'general' => array(
					'title' => __( 'General', 'bos-pmpro' ),
					'icon'  => 'dashicons-admin-settings',
				)
			);

			return $settings_sections;
		}

		/**
		 * Submit settings callback
		 */
		public function submit_settings_cb() {

		    if(check_admin_referer('wn_bos_pmpro_settings', 'wn_bos_pmpro_nonce')) {

                $settings = bos_pmpro_get_settings(true);
                update_option('wn_bos_pmpro_settings', $settings);

                $settings_tab_url = $this->get_settings_page_url($this->current_tab);
                $settings_tab_url = add_query_arg('settings_saved', 1, $settings_tab_url);
			    //pmpro_cron_expire_memberships();
                wp_redirect($settings_tab_url);
            }
        }

        public function settings_saved_notice() {

		    $page = filter_input(INPUT_GET, 'page');
	        $setting_saved = filter_input(INPUT_GET, 'settings_saved');

	        if($page == $this->settings_page_slug && $setting_saved==1) {
		        $class   = 'notice notice-success is-dismissible';
		        $message = __( 'Settings Saved', 'badgeos-learndash' );
		        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	        }
        }

			/**
		 * Add footer branding
		 *
		 * @param $footer_text
		 * @return mixed
		 */
		function remove_footer_admin ( $footer_text ) {
			if( isset( $_GET['page'] ) && ( $_GET['page'] == 'badgeos_learndash_settings' ) ) {
				_e('Fueled by <a href="http://www.wordpress.org" target="_blank">WordPress</a> | developed and designed by <a href="https://wooninjas.com" target="_blank">The WooNinjas</a></p>', 'badgeos-learndash' );
			} else {
				return $footer_text;
			}
		}

	}
}

new BOS_PMPRO_OPTIONS();