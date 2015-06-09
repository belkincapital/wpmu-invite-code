<?php
/*
    Plugin Name: WPMU Invite Code
    Plugin URI: https://github.com/belkincapital/wpmu-invite-code
    Description: Add invite code to wp-signup.php on WordPress Multisite and require a code for users to signup. Plugin is only compatible with WordPress Multisite.
    
    Author: Jason Jersey
    Author URI: https://www.twitter.com.com/degersey
    Version: 1.0
    Text Domain: wpmu_invite_code
    Domain Path: /languages/
    License: GNU General Public License 2.0 
    License URI: http://www.gnu.org/licenses/gpl-2.0.txt
    Network: true
    
    Copyright 2015 Belkin Capital Ltd (contact: https://belkincapital.com/contact/)
    
    This plugin is opensource; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License,
    or (at your option) any later version (if applicable).
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111 USA
*/

/* Exit if accessed directly
 * Since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpmu_invite_code_settings_page, $wpmu_invite_code_settings_page_long;

if ( version_compare($wp_version, '3.0.9', '>') ) {
	$wpmu_invite_code_settings_page = 'settings.php';
	$wpmu_invite_code_settings_page_long = 'network/settings.php';
} else {
	$wpmu_invite_code_settings_page = 'ms-admin.php';
	$wpmu_invite_code_settings_page_long = 'ms-admin.php';
}

add_action('init', 'wpmu_invite_code_init');
add_action('admin_menu', 'wpmu_invite_code_plug_pages');
add_action('network_admin_menu', 'wpmu_invite_code_plug_pages');
add_action('signup_extra_fields', 'wpmu_invite_code_field_wpmu');
add_action('bp_after_account_details_fields', 'wpmu_invite_code_field_bp');
add_filter('wpmu_validate_user_signup', 'wpmu_invite_code_filter_wpmu');
add_filter('bp_signup_validate', 'wpmu_invite_code_filter_bp');
add_action('wp_head', 'wpmu_invite_code_stylesheet');

function wpmu_invite_code_init() {
	if ( !is_multisite() )
		exit( 'The WPMU Invite Code plugin is only compatible with WordPress Multisite.' );

	load_plugin_textdomain('wpmu_invite_code', false, dirname(plugin_basename(__FILE__)).'/languages');
}

function wpmu_invite_code_plug_pages() {
	global $wpdb, $wp_roles, $current_user, $wp_version, $wpmu_invite_code_settings_page, $wpmu_invite_code_settings_page_long;
	if ( version_compare($wp_version, '3.0.9', '>') ) {
	    if ( is_network_admin() ) {
		add_submenu_page($wpmu_invite_code_settings_page, __('Invite Code', 'wpmu_invite_code'), __('Invite Code', 'wpmu_invite_code'), 'manage_network_options', 'wpmu_invite_code', 'wpmu_invite_code_site_admin_options');
	    }
	} else {
	    if ( is_super_admin() ) {
		add_submenu_page($wpmu_invite_code_settings_page, __('Invite Code', 'wpmu_invite_code'), __('Invite Code', 'wpmu_invite_code'), 'manage_network_options', 'wpmu_invite_code', 'wpmu_invite_code_site_admin_options');
	    }
	}
}

function wpmu_invite_code_stylesheet() {
?>
<style type="text/css">
	.mu_register #wpmu_invite_code { width:100%; font-size: 24px; margin:5px 0; }
</style>
<?php
}

function wpmu_invite_code_site_admin_options() {
	global $wpdb, $wp_roles, $current_user, $wpmu_invite_code_settings_page;

	if(!current_user_can('manage_options')) {
		echo "<p>" . __('Nice Try...', 'wpmu_invite_code') . "</p>";  //If accessed properly, this message doesn't appear.
		return;
	}
	if (isset($_GET['updated'])) {
		?><div id="message" class="updated fade"><p><?php _e(urldecode($_GET['updatedmsg']), 'wpmu_invite_code') ?></p></div><?php
	}
	echo '<div class="wrap">';
	switch( $_GET[ 'action' ] ) {
		default:
	?>
	<h2><?php _e('Invite Code', 'wpmu_invite_code') ?></h2>
	<form method="post" action="<?php print $wpmu_invite_code_settings_page; ?>?page=wpmu_invite_code&action=process">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Code', 'wpmu_invite_code') ?></th>
			<td><input name="wpmu_invite_code" type="text" id="wpmu_invite_code" value="<?php echo get_site_option('wpmu_invite_code'); ?>" style="width: 95%"/>
				<br />
				<?php _e('Users must enter this code in order to signup. Letters and numbers only.', 'wpmu_invite_code') ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Invite Code Branding', 'wpmu_invite_code') ?></th>
			<td><input name="wpmu_invite_code_branding" type="text" id="wpmu_invite_code_branding" value="<?php echo get_site_option('wpmu_invite_code_branding', 'Invite Code'); ?>" style="width: 95%"/>
				<br />
				<?php _e('The text label that is displayed on the signup form. ie: Invite Code', 'wpmu_invite_code') ?>
			</td>
		</tr>
	</table>
		<p class="submit">
			<input class="button button-primary" type="submit" name="Submit" value="<?php _e('Save Changes', 'wpmu_invite_code') ?>" />
			<input class="button button-secondary" type="submit" name="Reset" value="<?php _e('Reset', 'wpmu_invite_code') ?>" />
		</p>
        </form>
	<?php
		break;
	case "process":
			if ( isset( $_POST[ 'Reset' ] ) ) {
				update_site_option( 'wpmu_invite_code', "");
				update_site_option( 'wpmu_invite_code_branding', "");
				echo "
				<script type='text/javascript'>
				window.location='{$wpmu_invite_code_settings_page}?page=wpmu_invite_code&updated=true&updatedmsg=" . urlencode(__('Changes saved.', 'wpmu_invite_code')) . "';
				</script>
				";
			} else {
				update_site_option( 'wpmu_invite_code', stripslashes($_POST['wpmu_invite_code']) );
				update_site_option( 'wpmu_invite_code_branding', stripslashes($_POST['wpmu_invite_code_branding']) );
				echo "
				<script type='text/javascript'>
				window.location='{$wpmu_invite_code_settings_page}?page=wpmu_invite_code&updated=true&updatedmsg=" . urlencode(__('Changes saved.', 'wpmu_invite_code')) . "';
				</script>
				";
			}
		break;
	}
	echo '</div>';
}

function wpmu_invite_code_field_wpmu($errors) {
	if (!empty($errors)) {
		$error = $errors->get_error_message('wpmu_invite_code');
	} else {
		$error = false;
	}
	$wpmu_invite_code = get_site_option('wpmu_invite_code');
	if ( !empty( $wpmu_invite_code ) ) {
	?>
	<label for="password"><?php _e(get_site_option('wpmu_invite_code_branding', 'Signup Code'), 'wpmu_invite_code'); ?>:</label>
	<?php
        if($error) {
		echo '<p class="error">' . $error . '</p>';
        }
	?>
	<input type="text" name="wpmu_invite_code" id="wpmu_invite_code" value="<?php echo $_GET['code']; ?>" />
	<?php
	}
}

function wpmu_invite_code_field_bp() {
	$wpmu_invite_code = get_site_option('wpmu_invite_code');
	if ( !empty( $wpmu_invite_code ) ) {
	?>
    <div class="register-section" id="blog-details-section">
    <label for="password"><?php _e(get_site_option('wpmu_invite_code_branding', 'Invite Code'), 'wpmu_invite_code'); ?>:</label>
		<?php do_action( 'bp_wpmu_invite_code_errors' ) ?>
		<input type="text" name="wpmu_invite_code" id="wpmu_invite_code" value="<?php echo $_GET['code']; ?>" />
    </div>
	<?php
	}
}

function wpmu_invite_code_filter_wpmu($content) {
	$wpmu_invite_code = get_site_option('wpmu_invite_code');
	if ( !empty( $wpmu_invite_code ) ) {
		if($wpmu_invite_code != $_POST['wpmu_invite_code'] && $_POST['stage'] == 'validate-user-signup') {
			$content['errors']->add('wpmu_invite_code', __('Invalid ' . strtolower(get_site_option('wpmu_invite_code_branding', 'Invite Code')) . '.', 'wpmu_invite_code'));
		}
	}
	return $content;
}

function wpmu_invite_code_filter_bp() {
	global $bp;
	$wpmu_invite_code = get_site_option('wpmu_invite_code');
	if ( !empty( $wpmu_invite_code ) ) {
		if($wpmu_invite_code != $_POST['wpmu_invite_code'] && isset($_POST['signup_username'])) {
			$bp->signup->errors['wpmu_invite_code'] = __('Invalid ' . strtolower(get_site_option('wpmu_invite_code_branding', 'Invite Code')) . '.', 'wpmu_invite_code');
		}
	}
	return $content;
}
