<?php
/**
 * Custom Steps UI.
 *
 * @package BadgeOS Paid Memberships Pro
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

if( !class_exists('BOS_PMPRO_Steps_UI') ) {

    class BOS_PMPRO_Steps_UI {

        function __construct() {

            //Set steps requirements
	        add_filter( 'badgeos_get_step_requirements', [$this, 'step_requirements'], 10, 2 );
	        add_filter( 'badgeos_get_rank_req_step_requirements', [$this, 'step_requirements'], 10, 2 );
	        add_filter( 'badgeos_get_award_step_requirements', [$this, 'step_requirements'], 10, 2 );
	        add_filter( 'badgeos_get_deduct_step_requirements', [$this, 'step_requirements'], 10, 2 );

	        //Set activity triggers
	        add_filter( 'badgeos_activity_triggers', [$this, 'activity_triggers'] );
	        add_filter( 'badgeos_ranks_req_activity_triggers', [$this, 'activity_triggers'] );
	        add_filter( 'badgeos_award_points_activity_triggers', [$this, 'activity_triggers'] );
	        add_filter( 'badgeos_deduct_points_activity_triggers', [$this, 'activity_triggers'] );

	        //Set parent triggers
	        add_action( 'badgeos_steps_ui_html_after_trigger_type', [$this, 'parent_trigger_select'], 10, 2 );
	        add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', [$this, 'parent_trigger_select'], 10, 2 );
	        add_action( 'badgeos_award_steps_ui_html_after_achievement_type', [$this, 'parent_trigger_select'], 10, 2 );
	        add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', [$this, 'parent_trigger_deduct_point_select'], 10, 2 );

	        //Set child triggers
	        add_action( 'badgeos_steps_ui_html_after_trigger_type', [$this, 'step_trigger_select'], 10, 2 );
	        add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', [$this, 'step_trigger_select'], 10, 2 );
	        add_action( 'badgeos_award_steps_ui_html_after_achievement_type', [$this, 'step_trigger_select'], 10, 2 );
	        add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', [$this, 'step_trigger_select'], 10, 2 );

	        //Save step handler
	        add_filter( 'badgeos_save_step', [$this, 'save_step'], 10, 3 );

	        //Step JS Renderer
	        add_action( 'admin_footer', [$this, 'step_js'] );
        }

	    /**
	     * Include our custom trigger requirements.
	     *
	     * @param $requirements
	     * @param $step_id
	     * @return mixed
	     */
	    function step_requirements( $requirements, $step_id ) {

		    /**
		     * Add our new requirements to the list
		     */
		    $requirements[ 'pmpro_trigger' ] = get_post_meta( $step_id, '_bos_pmpro_trigger', true );
		    $requirements[ 'pmpro_step_meta' ] = get_post_meta( $step_id, '_bos_pmpro_step_meta', true );

		    return $requirements;
	    }

	    /**
	     * Filter the BadgeOS Triggers selector with our own options.
	     *
	     * @param $triggers
	     * @return mixed
	     */
	    function activity_triggers( $triggers ) {
		    $triggers[ 'pmpro_trigger' ] = __( 'Paid Memberships Pro Activity', 'bos-pmpro' );

		    return $triggers;
	    }

	    /**
	     * Add Paid Memberships Pro Triggers selector to the Steps UI.
	     *
	     * @param $step_id
	     * @param $post_id
	     */
	    function parent_trigger_select( $step_id, $post_id ) {

		    /**
		     * Setup our select input
		     */
		    echo '<select name="pmpro_trigger" class="select-pmpro-trigger">';
		    echo '<option value="">' . __( 'Select a Paid Memberships Pro Trigger', 'bos-pmpro' ) . '</option>';

		    /**
		     * Loop through all of our Paid Memberships Pro trigger groups
		     */
		    $current_trigger = get_post_meta( $step_id, '_bos_pmpro_trigger', true );

		    $pmpro_triggers = BOS_PMPRO_Integration::$triggers;

		    if ( ! empty( $pmpro_triggers ) ) {
			    foreach ( $pmpro_triggers as $trigger => $trigger_label ) {
				    if ( is_array( $trigger_label ) ) {
					    $optgroup_name = $trigger;
					    $triggers      = $trigger_label;

					    echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';

					    /**
					     * Loop through each trigger in the group
					     */
					    foreach ( $triggers as $trigger_hook => $trigger_name ) {
						    echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
					    }
					    echo '</optgroup>';
				    } else {

					    if ( in_array( $trigger, array( 'bos_pmpro_enrol_expire', 'bos_pmpro_enrol_cancel' ) ) ) {
						    continue;
					    }

					    echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';


				    }
			    }
		    }

		    echo '</select>';

	    }

	    /**
	     * Add Paid Memberships Pro Triggers selector to the Steps UI.
	     *
	     * @param $step_id
	     * @param $post_id
	     */
	    function parent_trigger_deduct_point_select( $step_id, $post_id ) {

		    /**
		     * Setup our select input
		     */
		    echo '<select name="pmpro_trigger" class="select-pmpro-trigger">';
		    echo '<option value="">' . __( 'Select a Paid Memberships Pro Trigger', 'bos-pmpro' ) . '</option>';

		    /**
		     * Loop through all of our Paid Memberships Pro trigger groups
		     */
		    $current_trigger = get_post_meta( $step_id, '_bos_pmpro_trigger', true );

		    $pmpro_triggers = BOS_PMPRO_Integration::$triggers;

		    if ( ! empty( $pmpro_triggers ) ) {
			    foreach ( $pmpro_triggers as $trigger => $trigger_label ) {
				    if ( is_array( $trigger_label ) ) {
					    $optgroup_name = $trigger;
					    $triggers      = $trigger_label;

					    echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';

					    /**
					     * Loop through each trigger in the group
					     */
					    foreach ( $triggers as $trigger_hook => $trigger_name ) {
						    echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
					    }
					    echo '</optgroup>';
				    } else {
					    echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';
				    }
			    }
		    }

		    echo '</select>';
	    }

	    /**
	     * Add Paid Memberships Pro Triggers selector to the Steps UI.
	     *
	     * @param $step_id
	     * @param $post_id
	     */
	    function step_trigger_select( $step_id, $post_id ) {

		    $step_meta = get_post_meta( $step_id, '_bos_pmpro_step_meta', true );

		    /**
		     * Level Types Select
		     */
		    $selected_value = @$step_meta['level_type'];

		    if(empty($selected_value)) {
			    $selected_value = 'any';
		    }

		    $pmpro_level_types = array(
			    'any' => __('Any', 'bos-pmpro'),
			    'free' => __('Free', 'bos-pmpro'),
			    'paid' => __('Paid', 'bos-pmpro'),
			    'subscription' => __('Subscription', 'bos-pmpro')
		    );

		    echo '<select name="pmpro_level_type" class="select-pmpro-level-type">';
		    foreach ($pmpro_level_types as $level_type_value => $pmpro_level_type_text) {
			    echo sprintf('<option value="%s" %s>%s</option>', $level_type_value, selected($selected_value, $level_type_value, false), $pmpro_level_type_text);
		    }
		    echo '</select>';

	    }

	    /**
	     * AJAX Handler for saving all steps.
	     *
	     * @param $title
	     * @param $step_id
	     * @param $step_details
	     * @return string
	     */
	    function save_step( $title, $step_id, $step_details ) {
		    /**
		     * If we're working on a Paid Memberships Pro trigger
		     */
		    if ( 'pmpro_trigger' == $step_details[ 'trigger_type' ] ) {

			    /**
			     * Update our Paid Memberships Pro trigger post meta
			     */
			    update_post_meta( $step_id, '_bos_pmpro_trigger', $step_details[ 'pmpro_trigger' ] );

			    /**
			     * Rewrite the step title
			     */
			    $title = $step_details[ 'pmpro_trigger_label' ];

			    $step_meta = array();

			    if($step_details['pmpro_trigger'] == 'bos_pmpro_enrol_level') {

				    if( !empty($step_details['step_meta']['level_type']) ) {
					    $title = __('Enroll to %s Level', 'bos-pmpro');
					    $title = sprintf($title, $step_details['pmpro_level_type_label']);
				    }

				    $step_meta = $step_details['step_meta'];
			    }

			    /**
			     * Store our Object ID in meta
			     */
			    update_post_meta( $step_id, '_bos_pmpro_step_meta', $step_meta);
		    } else {
			    delete_post_meta( $step_id, '_bos_pmpro_trigger' );
			    delete_post_meta( $step_id, '_bos_pmpro_step_meta' );
		    }

		    return $title;
	    }

	    /**
	     * Include custom JS for the BadgeOS Steps UI.
	     */
	    function step_js() {
		    ?>
            <script type="text/javascript">
                jQuery( document ).ready( function ( $ ) {

                    var times = $( '.required-count' ).val();

                    /**
                     * Listen for our change to our trigger type selector
                     */
                    $( document ).on( 'change', '.select-trigger-type', function () {

                        var trigger_type = $( this );

                        /**
                         * Show our group selector if we're awarding based on a specific group
                         */
                        if ( 'pmpro_trigger' == trigger_type.val() ) {
                            trigger_type.siblings( '.select-pmpro-trigger' ).show().change();
                            var trigger = $('.select-pmpro-trigger').val();

                        }  else {
                            trigger_type.siblings( '.select-pmpro-trigger' ).val('').hide().change();
                            $( '.input-quiz-grade' ).parent().hide();

                            $( '.required-count' ).val( times );
                        }
                    } );

                    /**
                     * Listen for our change to our trigger type selector
                     */
                    $( document ).on( 'change', '.select-pmpro-trigger', function () {
                        bos_pmpro_step_change( $( this ) , times);
                    } );

                    /**
                     * Trigger a change so we properly show/hide our Paid Memberships Pro menus
                     */
                    $( '.select-trigger-type' ).change();

                    /**
                     * Inject our custom step details into the update step action
                     */
                    $( document ).on( 'update_step_data', function ( event, step_details, step ) {

                        step_details.step_meta = {}; //To hold necessary step requirement data
                        step_details.pmpro_trigger = $( '.select-pmpro-trigger', step ).val();
                        step_details.pmpro_trigger_label = $( '.select-pmpro-trigger option', step ).filter( ':selected' ).text();
                        step_details.step_meta.level_type = $( '.select-pmpro-level-type option', step ).filter( ':selected' ).val();
                        step_details.pmpro_level_type_label = $( '.select-pmpro-level-type option', step ).filter( ':selected' ).text();
                    } );

                    function bos_pmpro_step_change( $this , times) {

                        var trigger_parent = $this.parent();
                        var	trigger_parent_value = trigger_parent.find( '.select-trigger-type' ).val();

                        if ( trigger_parent_value != 'pmpro_trigger' ) {
                            trigger_parent.find('.required-count')
                                .val(times);
                        }

                        if($this.val() == 'bos_pmpro_enrol_level') {
                            trigger_parent.find('.select-pmpro-level-type').show();
                        } else {
                            trigger_parent.find('.select-pmpro-level-type').hide();
                        }

                    }

                } );

            </script>
		    <?php
	    }
    }
}

new BOS_PMPRO_Steps_UI();