<?php
/**
 * Helper functions
 */


/**
 * Retrieve BadgeOS saved post type
 *
 * @param $post_type
 *
 * @return mixed
 */
if( !function_exists('bos_get_post_type') ) {
	function bos_get_post_type( $post_type ) {

		$bos_post_type_settings = array(
			'achievement-type' => 'achievement_main_post_type',
			'step'             => 'achievement_step_post_type',
			'rank_types'       => 'ranks_main_post_type',
			'rank_requirement' => 'ranks_step_post_type',
			'point_type'       => 'points_main_post_type',
			'point_award'      => 'points_award_post_type',
			'point_deduct'     => 'points_deduct_post_type'
		);

		$bos_settings = get_option( 'badgeos_settings', array() );

		$bos_post_type = $bos_settings[ $bos_post_type_settings[ $post_type ] ];

		if ( isset( $bos_post_type ) && ! empty( $bos_post_type ) ) {
			$post_type = $bos_post_type;
		}

		return $post_type;
	}
}

/**
 * Retrieve BadgeOS saved post type by slug
 *
 * @param $post_type_slug
 *
 * @return string|NULL $post_type
 */
if( !function_exists('bos_get_post_type_by_slug') ) {
	function bos_get_post_type_by_slug( $post_type_slug ) {

		$bos_post_type_slugs = array(
			'achievement'       => 'achievement-type',
			'achievement_step'  => 'step',
			'rank'              => 'rank_types',
			'rank_step'         => 'rank_requirement',
			'point'             => 'point_type',
			'point_award_step'  => 'point_award',
			'point_deduct_step' => 'point_deduct'
		);

		$post_type = bos_get_post_type( $bos_post_type_slugs[ $post_type_slug ] );

		return $post_type;
	}
}


/**
 * Generic method to retrieve plugin settings
 *
 * @param bool $get_post_var
 *
 * @return array
 */
function bos_pmpro_get_settings($get_post_var=false) {

	$default_settings = array(
		'revoke_achievements' => 0,
		'revoke_ranks' => 0
	);

	if(!$get_post_var) {
		$settings = get_option( 'wn_bos_pmpro_settings', array() ); //Use saved settings instead
	} else {
		$settings = @$_POST['wn_bos_pmpro_settings'];
		foreach ($default_settings as $key => $val) {

			if(!isset($settings[$key])) {
				$settings[$key] = $val;
			}
		}
	}

	return wp_parse_args($settings, $default_settings);
}