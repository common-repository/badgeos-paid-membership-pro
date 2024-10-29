<?php
/**
* General Options
*/

defined( 'ABSPATH' ) || exit;


$wn_bos_pmpro_options = get_option( 'wn_bos_pmpro_options', array() );
$bos_pmpro_quiz_point_type = isset($wn_bos_pmpro_options['bos_pmpro_quiz_point_type']) ? $wn_bos_pmpro_options['bos_pmpro_quiz_point_type'] : 0;
$quiz_points_as_badgeos_points = !empty( $wn_bos_pmpro_options['quiz_points_as_badgeos_points']) ? $wn_bos_pmpro_options['quiz_points_as_badgeos_points'] : 'no';
$badgeos_learndash_quiz_score_multiplier = !empty( $wn_bos_pmpro_options['badgeos_learndash_quiz_score_multiplier']) ? (int) $wn_bos_pmpro_options['badgeos_learndash_quiz_score_multiplier'] : '1';

?>
<div id="wn-bos-pmpro-general-options">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="wn_bos_pmpro_admin_settings">
		<?php wp_nonce_field( 'wn_bos_pmpro_admin_settings_action', 'wn_bos_pmpro_admin_settings_field' ); ?>
		<table class="form-table">
			<tbody>
            <tr>
                <th><label><?php _e('Revoke achievements on cancel/expiry', 'bos-pmpro'); ?></label></th>
                <td><input type="checkbox" name="bos_pmpro_settings[bos_pmpro_settings][revoke_awarded_achievements]" value="1" <?php checked(1,1); ?>>
                <p><?php _e('Revoke enrollment based achievements after enrollment get canceled or expired'); ?>/p>
                </td>
            </tr>
			</tbody>
		</table>
		<p>
			<?php submit_button( __( 'Save Settings', 'badgeos-learndash' ), 'primary', 'wn_bos_pmpro_settings_submit' ); ?>
		</p>
	</form>
</div>