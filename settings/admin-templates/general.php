<?php defined('ABSPATH') || exit; ?>
<div id="wn_bos_pmpro_general_settings_wrapper">
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="wn_bos_pmpro_submit_settings">
	<?php wp_nonce_field( 'wn_bos_pmpro_settings', 'wn_bos_pmpro_nonce' ); ?>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="wn_bos_pmpro_settings_revoke_achievements"><?php _e('Revoke Achievements on Cancel/Expiry'); ?></label></th>
            <td>
                <input type="checkbox" name="wn_bos_pmpro_settings[revoke_achievements]" id="wn_bos_pmpro_settings_revoke_achievements" value="1" <?php checked($this->settings['revoke_achievements'], 1); ?>>
                <p class="description"><?php _e('Revoke awarded membership achievement(s) after membership level get cancelled/expired.'); ?></p>
            </td>

        </tr>
        <tr>
            <th><label for="wn_bos_pmpro_settings_revoke_ranks"><?php _e('Revoke Ranks on Cancel/Expiry'); ?></label></th>
            <td>
                <input type="checkbox" name="wn_bos_pmpro_settings[revoke_ranks]" id="wn_bos_pmpro_settings_revoke_ranks" value="1" <?php checked($this->settings['revoke_ranks'], 1); ?>>
                <p class="description"><?php _e('Revoke awarded membership rank(s) after membership level get cancelled/expired.'); ?></p>
            </td>
        </tr>
        </tbody>

    </table>
    <p><?php submit_button( __( 'Save Settings', 'bos-pmpro' ), 'primary', 'wn_bos_pmpro_submit_settings_button' ); ?></p>
</form>
</div>