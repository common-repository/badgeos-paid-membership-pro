<?php
/**
 * Custom Rules
 *
 * @package BadgeOS Paid Memberships Pro
 * @author WooNinjas
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://wooninjas.com
 */

defined( 'ABSPATH' ) || exit;

if(!class_exists('BOS_PMPRO_Rules_Engine')) {

	class BOS_PMPRO_Rules_Engine {

		use BOS_PMPro_Singleton;

		private $trigger_type = 'pmpro_trigger';
		private $trigger_key = '_bos_pmpro_trigger';
		private $achievement_entries_meta_key = '_bos_pmpro_earned_achievements';
		private $earned_ranks_meta_key = '_bos_pmpro_earned_ranks';
		private $trigger; //Hold current trigger name
		private $args; //Holds trigger arguments

		/**
		 * PUBLIC METHODS
		 */

		/**
		 * BOS_PMPRO_Rules_Engine initialize.
		 */
		public function init() {
			$pmpro_triggers = BOS_PMPRO_Integration::$triggers;

			if ( !empty( $pmpro_triggers ) ) {
				foreach ( $pmpro_triggers as $trigger => $trigger_label ) {

					if ( is_array( $trigger_label ) ) {
						$triggers = $trigger_label;

						foreach ( $triggers as $trigger_hook => $trigger_name ) {
							add_action( $trigger_hook, [$this, 'capture_event'], 0, 20 );
						}
					} else {
						add_action( $trigger, [$this, 'capture_event'], 0, 20 );
					}
				}
			}

			add_filter( 'badgeos_is_achievement', [$this, 'badgeos_is_achievement_cb'], 15, 2);
			add_filter( 'user_deserves_achievement', [$this, 'badgeos_user_deserves_achievement_cb'], 15, 6);
			add_filter( 'badgeos_user_deserves_rank_step', [$this, 'badgeos_user_deserves_rank_step_cb'], 15, 7);
			add_filter( 'badgeos_user_deserves_rank_award', [$this, 'badgeos_user_deserves_rank_award_cb'], 15, 7 );
			add_filter( 'badgeos_user_deserves_credit_award', [$this, 'badgeos_user_deserves_credit_award_cb'], 15, 7 );
			add_filter( 'badgeos_user_deserves_credit_deduct', [$this, 'badgeos_user_deserves_credit_deduct_cb'], 15, 7 );

			$settings = bos_pmpro_get_settings();

			//If revoking achievement option enabled
			if( !empty($settings['revoke_achievements']) ) {
				add_filter( 'badgeos_award_achievement', [ $this, 'badgeos_award_achievement_cb' ], 10, 6 );
			}

			//If revoking rank option enabled
			if( !empty($settings['revoke_ranks']) ) {
				add_filter( 'badgeos_after_award_rank', [ $this, 'badgeos_after_award_rank_cb' ], 10, 7 );
			}

			//Set membership cancel and expiry triggers
			add_action( 'pmpro_before_change_membership_level', [$this, 'membership_cancel_cb'], 10, 4 );
			add_action( 'pmpro_membership_post_membership_expiry', [$this, 'membership_expiry_cb'], 10, 2 );
		}

		/**
		 * Capture trigger and sets trigger parameters
		 */
		public function capture_event() {

			$this->trigger = current_filter();
			$this->args = func_get_args();

			$this->execute_event();
		}

		public function membership_expiry_cb($user_id, $cancelled_level_id) {
			$this->membership_cancel_cb(null, $user_id, null, $cancelled_level_id);
		}

		/**
		 * Execute membership cancellation routine, revoke any achievements and ranks awarded based on current trigger
		 *
		 * @param $level_id
		 * @param $user_id
		 * @param $old_levels
		 * @param $cancelled_level_id
		 */
		public function membership_cancel_cb($level_id, $user_id, $old_levels, $cancelled_level_id) {

			//Revoke achievements
			$earned_achievements = $this->get_achievement_entries($user_id);

			//Check if user has earned any achievements
			if( ! empty($earned_achievements) ) {

				//Check if user has cancelled membership, then revoke any associated achievements with it
				if ( ! empty( $cancelled_level_id ) ) {

					$this->revoke_cancelled_level_achievements($user_id, $cancelled_level_id, $earned_achievements);

					update_user_meta( $user_id, $this->achievement_entries_meta_key, $earned_achievements );
				}

				//Check if user had any previous membership level, then revoke any associated achievements with each level
				if ( ! empty( $old_levels ) ) {

					foreach ( $old_levels as $old_level ) {

						$cancelled_level_id = $old_level->id;

						$this->revoke_cancelled_level_achievements($user_id, $cancelled_level_id, $earned_achievements);
					}

					update_user_meta( $user_id, $this->achievement_entries_meta_key, $earned_achievements );
				}
			}

			//Revoke Ranks
			$earned_ranks = $this->get_earned_ranks($user_id);

			//Check if user has earned any ranks
			if(!empty($earned_ranks)) {

				//Check if user has cancelled membership, then revoke any associated ranks with it
				if ( ! empty( $cancelled_level_id ) ) {

					$this->revoke_cancelled_level_ranks( $user_id, $cancelled_level_id, $earned_ranks );

					update_user_meta( $user_id, $this->earned_ranks_meta_key, $earned_achievements );
				}

				//Check if user had any previous membership level, then revoke any associated ranks with each level
				if ( ! empty( $old_levels ) ) {

					foreach ( $old_levels as $old_level ) {

						$cancelled_level_id = $old_level->id;

						$this->revoke_cancelled_level_ranks($user_id, $cancelled_level_id, $earned_ranks);
					}

					update_user_meta( $user_id, $this->earned_ranks_meta_key, $earned_ranks);
				}
			}
		}

		/**
		 * Check if valid achievement type is processed
		 *
		 * @param $return
		 * @param $post
		 *
		 * @return bool
		 */
		public function badgeos_is_achievement_cb($return, $post) {

			$bos_step_post_type = bos_get_post_type_by_slug('achievement_step');

			if( get_post_type($post) == $bos_step_post_type ) {
				$return = true;
			}

			return $return;
		}

		/**
		 * Check if user is eligible to earn achievement then return true, false otherwise
		 *
		 * @param $return
		 * @param $user_id
		 * @param $bos_post_id
		 * @param $trigger
		 * @param $site_id
		 * @param $args
		 *
		 * @return bool
		 */
		public function badgeos_user_deserves_achievement_cb($return, $user_id, $bos_post_id, $trigger, $site_id, $args) {

			$post_type = get_post_type( $bos_post_id );
			$bos_step_post_type = bos_get_post_type_by_slug( 'achievement_step' );

			/**
			 * If not a valid step
			 */
			if( $bos_step_post_type != $post_type ) {
				return $return;
			}

			/**
			 * Unsupported trigger
			 */
			if ( ! isset( BOS_PMPRO_Integration::$triggers[ $trigger ] ) ) {
				return false;
			}

			/**
			 * Does not meet trigger requirements
			 */
			if( !$this->meets_event_requirements($bos_post_id, 'achievement') ) {
				return false;
			}

			/**
			 * All is well
			 */
			return true;

		}

		/**
		 * Check if user is eligible to earn rank step then return true, false otherwise
		 *
		 * @param $return
		 * @param $bos_post_id
		 * @param $rank_id
		 * @param $user_id
		 * @param $trigger
		 * @param $site_id
		 * @param $args
		 *
		 * @return bool
		 */
		public function badgeos_user_deserves_rank_step_cb($return, $bos_post_id, $rank_id, $user_id, $trigger, $site_id, $args) {

			$post_type = get_post_type( $bos_post_id );
			$bos_step_post_type = bos_get_post_type_by_slug( 'rank_step' );

			/**
			 * If not a valid step
			 */
			if( $bos_step_post_type != $post_type ) {
				return $return;
			}

			/**
			 * Unsupported trigger
			 */
			if ( ! isset( BOS_PMPRO_Integration::$triggers[ $trigger ] ) ) {
				return false;
			}

			/**
			 * Does not meet trigger requirements
			 */
			if( !$this->meets_event_requirements($bos_post_id,'rank_step') ) {
				return false;
			}

			/**
			 * All is well
			 */
			return true;

		}

		/**
		 * Check if user is eligible to earn rank then return true, false otherwise
		 *
		 * @param $completed
		 * @param $bos_step_id
		 * @param $rank_id
		 * @param $user_id
		 * @param $trigger
		 * @param $site_id
		 * @param $args
		 *
		 * @return bool
		 */
		public function badgeos_user_deserves_rank_award_cb($completed, $bos_step_id, $rank_id, $user_id, $trigger, $site_id, $args) {

			$post_type = get_post_type( $bos_step_id );
			$bos_step_post_type = bos_get_post_type_by_slug( 'rank_step' );

			/**
			 * If not a valid step
			 */
			if( $bos_step_post_type != $post_type ) {
				return $completed;
			}

			/**
			 * Get the requirement rank
			 */
			$rank = badgeos_get_rank_requirement_rank( $bos_step_id );

			/**
			 * Get all requirements of this rank
			 */
			$requirements = badgeos_get_rank_requirements( $rank_id );

			$completed = true;

			foreach( $requirements as $requirement ) {

				/**
				 * Check if rank requirement has been earned
				 */
				if( ! badgeos_get_user_ranks( array(
					'user_id' => $user_id,
					'rank_id' => $requirement->ID,
					'since' => strtotime( $rank->post_date_gmt ),
					'no_steps' => false
				) ) ) {
					$completed = false;
					break;
				}
			}

			return $completed;

		}

		/**
		 * Check if user is eligible to earn credit then return true, false otherwise
		 *
		 * @param $return
		 * @param $bos_post_id
		 * @param $credit_parent_id
		 * @param $user_id
		 * @param $trigger
		 * @param $site_id
		 * @param $args
		 *
		 * @return bool
		 */
		public function badgeos_user_deserves_credit_award_cb($return, $bos_post_id, $credit_parent_id, $user_id, $trigger, $site_id, $args) {

			$post_type = get_post_type( $bos_post_id );

			$bos_step_post_type = bos_get_post_type_by_slug( 'point_award_step' );

			/**
			 * If not a valid step
			 */
			if( $bos_step_post_type != $post_type ) {
				return $return;
			}

			/**
			 * Unsupported trigger
			 */
			if ( ! isset( BOS_PMPRO_Integration::$triggers[ $trigger ] ) ) {
				return false;
			}

			/**
			 * Does not meet trigger requirements
			 */
			if( !$this->meets_event_requirements($bos_post_id, 'point_award') ) {
				return false;
			}

			/**
			 * All is well
			 */
			return true;
		}

		/**
		 * Check if user is eligible for credit deduction then return true, false otherwise
		 *
		 * @param $return
		 * @param $bos_post_id
		 * @param $credit_parent_id
		 * @param $user_id
		 * @param $trigger
		 * @param $site_id
		 * @param $args
		 *
		 * @return bool
		 */
		public function badgeos_user_deserves_credit_deduct_cb($return, $bos_post_id, $credit_parent_id, $user_id, $trigger, $site_id, $args) {
			$post_type = get_post_type( $bos_post_id );
			$bos_step_post_type = bos_get_post_type_by_slug( 'point_deduct_step' );

			/**
			 * If not a valid step
			 */
			if( $bos_step_post_type != $post_type ) {
				return $return;
			}

			/**
			 * Unsupported trigger
			 */
			if ( ! isset( BOS_PMPRO_Integration::$triggers[ $trigger ] ) ) {
				return false;
			}

			/**
			 * Does not meet trigger requirements
			 */
			if( !$this->meets_event_requirements($bos_post_id, 'point_deduct') ) {
				return false;
			}

			/**
			 * All is well
			 */
			return true;
		}

		/**
		 * Callback when user earn an achievement then save achievement ID to usermeta for each membership level
		 *
		 * @param $user_id
		 * @param $id
		 * @param $trigger
		 * @param $site_id
		 * @param $args
		 * @param $entry_id
		 */
		public function badgeos_award_achievement_cb($user_id, $id, $trigger, $site_id, $args, $entry_id) {

			//Ignore if achievement step
			if( get_post_type($id) == bos_get_post_type_by_slug('achievement_step') ) {
				return;
			}

			list($level_id, $user_id, $cancel_level_id) = $this->args;

			$earned_achievements = get_user_meta($user_id, $this->achievement_entries_meta_key, true);

			if( !is_array($earned_achievements) ) {
				$earned_achievements = array();
			}

			if( !isset($earned_achievements[$level_id]) || !is_array($earned_achievements[$level_id]) ) {
				$earned_achievements[$level_id] = array();
			}

			$earned_achievements[$level_id][] = $id;

			update_user_meta($user_id, $this->achievement_entries_meta_key,$earned_achievements);
		}

		/**
		 *
		 * Callback when user earn a rank then save rank ID to usermeta for each membership level
		 *
		 * @param $user_id
		 * @param $id
		 * @param $post_type
		 * @param $credit_id
		 * @param $credit_amount
		 * @param $admin_id
		 * @param $trigger
		 */
		public function badgeos_after_award_rank_cb( $user_id, $id, $post_type, $credit_id, $credit_amount, $admin_id, $trigger ) {

			//Ignore if achievement step
			if( get_post_type($id) == bos_get_post_type_by_slug('rank_step') ) {
				return;
			}

			list($level_id, $user_id, $cancel_level_id) = $this->args;

			$earned_ranks = get_user_meta($user_id, $this->earned_ranks_meta_key, true);

			if( !is_array($earned_ranks) ) {
				$earned_ranks = array();
			}

			if( !isset($earned_ranks[$level_id]) || !is_array($earned_ranks[$level_id]) ) {
				$earned_ranks[$level_id] = array();
			}

			$earned_ranks[$level_id][] = $id;

			update_user_meta($user_id, $this->earned_ranks_meta_key,$earned_ranks);
		}

		/**
		 * PRIVATE METHODS
		 */

		private function execute_event() {

			if( $this->trigger == 'bos_pmpro_enrol_expire' ) {
				list( $user_id, $cancelled_level_id ) = $this->args;
			} else {
				list( $level_id, $user_id, $cancel_level_id ) = $this->args;
			}

			$bos_steps = $this->get_steps_by_trigger();

			if( !empty($bos_steps) ) {
				foreach ($bos_steps as $bos_step) {
					$bos_step_id = $bos_step->ID;
					$bos_step_post_type = $bos_step->post_type;

					$this->maybe_award_achievement($bos_step_id, $user_id);
					$this->maybe_award_rank($bos_step_id, $user_id);
					$this->maybe_award_points($bos_step_id, $bos_step_post_type, $user_id);
					$this->maybe_deduct_points($bos_step_id, $bos_step_post_type, $user_id);
				}
			}
		}

		/**
		 * Get all BadgeOS steps associated with current trigger
		 *
		 * @return int[]|WP_Post[]
		 */
		private function get_steps_by_trigger() {

			global $wpdb;

			$query = "SELECT P.ID, P.post_type FROM {$wpdb->posts} P 
						INNER JOIN {$wpdb->postmeta} PM ON P.ID=PM.post_id 
						WHERE PM.meta_key=%s AND PM.meta_value=%s;";

			$results = $wpdb->get_results($wpdb->prepare($query, $this->trigger_key, $this->trigger));

			return $results;
		}

		private function maybe_award_achievement($bos_step_id, $user_id) {

			global $blog_id;

			$parents = badgeos_get_achievements( array( 'parent_of' => $bos_step_id ) );
			if( count( $parents ) > 0 ) {
				if( $parents[0]->post_status == 'publish' ) {
					$user_data = get_user_by('id', $user_id );
					/**
					 * Update hook count for this user
					 */
					$new_count = badgeos_update_user_trigger_count( $user_id, $this->trigger, $blog_id );

					/**
					 * Mark the count in the log entry
					 */
					badgeos_post_log_entry( null, $user_id, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'bos-pmpro' ), $user_data->user_login, $this->trigger, $new_count ) );
					badgeos_maybe_award_achievement_to_user( $bos_step_id, $user_id, $this->trigger, $blog_id, $this->args );
				}
			}
		}

		private function maybe_award_rank($bos_step_id, $user_id) {

			global $blog_id;

			$parent_id = badgeos_get_parent_id( $bos_step_id );

			if( absint($parent_id) > 0) {
				badgeos_ranks_update_user_trigger_count( $bos_step_id, $parent_id,$user_id, $this->trigger, $blog_id, $this->args );
				badgeos_maybe_award_rank( $bos_step_id,$parent_id,$user_id, $this->trigger, $blog_id, $this->args );
			}
		}

		private function maybe_award_points($bos_step_id, $point_post_type, $user_id) {

			global $blog_id;

			$parent_id = badgeos_get_parent_id( $bos_step_id );
			if( absint($parent_id) > 0) {
				if($point_post_type == 'point_award') {
					badgeos_points_update_user_trigger_count($bos_step_id, $parent_id, $user_id, $this->trigger, $blog_id, 'Award', $this->args);
					badgeos_maybe_award_points_to_user($bos_step_id, $parent_id, $user_id, $this->trigger, $blog_id, $this->args);
				}
			}
		}

		private function maybe_deduct_points($bos_step_id, $point_post_type, $user_id) {

			global $blog_id;

			$parent_id = badgeos_get_parent_id( $bos_step_id );
			if( absint($parent_id) > 0) {
				if($point_post_type == 'point_deduct') {
					badgeos_points_update_user_trigger_count($bos_step_id, $parent_id, $user_id, $this->trigger, $blog_id, 'Deduct', $this->args);
					badgeos_maybe_deduct_points_to_user($bos_step_id, $parent_id, $user_id, $this->trigger, $blog_id, $this->args);
				}
			}
		}

		/**
		 * Check if cancelled level has any associated achievements in usermeta, revoke all of them
		 *
		 * @param $user_id
		 * @param $cancelled_level_id
		 * @param $earned_achievements
		 */
		private function revoke_cancelled_level_achievements($user_id, $cancelled_level_id, &$earned_achievements) {

			if ( isset( $earned_achievements[ $cancelled_level_id ] ) && is_array( $earned_achievements[ $cancelled_level_id ] ) ) {

				foreach ( $earned_achievements[ $cancelled_level_id ] as $achievement_id ) {
					$this->revoke_achievement( $achievement_id, $user_id );
				}

				unset( $earned_achievements[ $cancelled_level_id ] );
			}
		}

		/**
		 * Check if cancelled level has any associated ranks in usermeta, revoke all of them
		 *
		 * @param $user_id
		 * @param $cancelled_level_id
		 * @param $earned_ranks
		 */
		private function revoke_cancelled_level_ranks($user_id, $cancelled_level_id, &$earned_ranks) {

			if ( isset( $earned_ranks[ $cancelled_level_id ] ) && is_array( $earned_ranks[ $cancelled_level_id ] ) ) {

				foreach ( $earned_ranks[ $cancelled_level_id ] as $rank_id ) {
					$this->revoke_rank( $rank_id, $user_id );
				}

				unset( $earned_ranks[ $cancelled_level_id ] );
			}
		}

		/**
		 * Check if trigger's all requirement met for defined object type
		 *
		 * @param $bos_post_id
		 * @param string $type
		 *
		 * @return bool
		 */
		private function meets_event_requirements($bos_post_id, $type = 'achievement') {

			/**
			 * Grab requirements
			 */
			if($type == 'rank_step') {
				$requirements = badgeos_get_rank_req_step_requirements( $bos_post_id );
			} elseif($type == 'rank') {
				$requirements = badgeos_get_rank_requirements( $bos_post_id );
			} elseif($type == 'point_award') {
				$requirements = badgeos_get_award_step_requirements( $bos_post_id );
			} elseif($type == 'point_deduct') {
				$requirements = badgeos_get_deduct_step_requirements( $bos_post_id );
			} else {
				$requirements = badgeos_get_step_requirements( $bos_post_id );
			}

			if ( $this->trigger_type == $requirements['trigger_type'] ) {

				if($this->trigger == 'bos_pmpro_enrol_level') {

					list($level_id, $user_id, $cancel_level_id) = $this->args;

					if( empty($level_id) ) return false; //Only process this trigger if level id is defined

					$level = pmpro_getLevel($level_id);

					$level_type = 'free';
					if( !empty($level->cycle_period) ) {
						$level_type = 'subscription';
					} elseif( !empty($level->initial_payment) ) {
						$level_type = 'paid';
					}

					$step_meta = $requirements['pmpro_step_meta'];

					return ( $step_meta['level_type'] == 'any' || $step_meta['level_type'] == $level_type );

				} elseif( $this->trigger == 'bos_pmpro_enrol_cancel' ) {

					list($level_id, $user_id, $cancel_level_id) = $this->args;

					if( !empty($cancel_level_id) ) {
						return true;
					}

				} elseif( $this->trigger == 'bos_pmpro_enrol_expire' ) {

					list($user_id, $membership_id) = $this->args;

					return true;
				}
			}

			return false;
		}

		private function get_achievement_entries($user_id) {
			$earned_achievements = get_user_meta($user_id, $this->achievement_entries_meta_key, true);

			if( !is_array($earned_achievements) ) {
				$earned_achievements = array();
			}

			return $earned_achievements;
		}

		private function get_earned_ranks($user_id) {
			$earned_ranks = get_user_meta($user_id, $this->earned_ranks_meta_key, true);

			if( !is_array($earned_ranks) ) {
				$earned_ranks = array();
			}

			return $earned_ranks;
		}

		private function revoke_achievement($achievement_id, $user_id) {

			global $wpdb;

			$table_achievements = "{$wpdb->prefix}badgeos_achievements";

			$query = "DELETE FROM {$table_achievements} WHERE ID=%d ORDER BY entry_id DESC LIMIT 1;";

			//Get achievement child steps
			$children = badgeos_get_achievements( array( 'children_of' => $achievement_id) );

			// Loop through each achievement child and drop it
			foreach( $children as $child ) {
				$wpdb->query($wpdb->prepare($query, $child->ID));
				badgeos_decrement_user_trigger_count( $user_id, $child->ID, $achievement_id);
			}

			//Drop the achievement itself
			$wpdb->query($wpdb->prepare($query, $achievement_id));

			//Grab the user's earned achievements
			$user_earned_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );

			// Update user's earned achievements
			badgeos_update_user_achievements( array(
				'user_id'          => $user_id,
				'all_achievements' => array_values($user_earned_achievements)
			));

			// Available hook for taking further action when an achievement is revoked
			do_action( 'badgeos_revoke_achievement', $user_id, $achievement_id );
		}

		private function revoke_rank($rank_id, $user_id) {
			badgeos_revoke_rank_from_user_account($user_id, $rank_id);
		}
	}
}