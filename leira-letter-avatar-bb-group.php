<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://leira.dev
 * @since             1.0.0
 * @package           Leira_Letter_Avatar_Buddyboss_Group
 *
 * @wordpress-plugin
 * Plugin Name:       Leira Letter Avatar for Buddyboss Group
 * Plugin URI:        https://wordpress.org/plugins/leira-letter-avatar/
 * Description:       Enables custom avatars for buddyboss groups base on its initial letters.
 * Version:           1.0.0
 * Author:            Ariel
 * Author URI:        https://leira.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       leira-letter-avatar-buddyboss-group
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LEIRA_LETTER_AVATAR_BUDDYBOSS_GROUP_VERSION', '1.0.0' );

/**
 * BuddyPress compatibility
 * Generate image avatar for buddy press.
 *
 * @param string $avatar_default
 * @param array  $params
 *
 * @return string
 * @since 1.0.0
 */
function leira_letter_avatar_bb_group_bp_core_default_avatar( $avatar_default, $params ) {

	$default = isset( $params['default'] ) ? $params['default'] : false;
	/**
	 * Generate letter avatar for specific user, group or site
	 */
	if ( $default == 'leira_letter_avatar' || leira_letter_avatar()->is_active() ) {
		/**
		 * Our avatar method is enable
		 */
		if ( isset( $params['object'] ) ) {
			$object = $params['object'];
		} else {
			$object            = 'user';
			$params['item_id'] = bp_displayed_user_id();//bp_loggedin_user_id()
			/**
			 * We use "bp_displayed_user_id()" as in "bp_get_user_has_avatar" method in file bp-core-avatars.php
			 */
		}
		if ( $object == 'user' ) {
			/**
			 * Do nothing...
			 */
		} elseif ( $object == 'group' ) {
			if ( isset( $params['item_id'] ) ) {
				/**
				 * Generate avatars for groups
				 */
				$group = groups_get_group( $params['item_id'] );

				if ( isset( $params['width'] ) ) {
					$size = $params['width'];
				} else {
					if ( isset( $params['type'] ) && 'thumb' === $params['type'] ) {
						$size = bp_core_avatar_thumb_width();
					} else {
						$size = bp_core_avatar_full_width();
					}
				}

				$args = array(
					'size' => $size
				);

				$url = leira_letter_avatar()->public->generate_letter_avatar_url( $group, $args );
				if ( $url ) {
					/**
					 * If it was generated correctly use this avatar url
					 */
					$avatar_default = $url;
				}
			}
		}
	}

	return $avatar_default;
}

add_filter( 'bp_core_default_avatar_group', 'leira_letter_avatar_bb_group_bp_core_default_avatar', 50, 2 );
//add_filter( 'bp_core_avatar_default', 'leira_letter_avatar_bb_group_bp_core_default_avatar', 50, 2 );

/**
 * Filter the parameters to send to the generate method.
 * We are going to provide our letters, background and color
 *
 * @param array  $url_args    The arguments to show the avatar
 * @param object $id_or_email The object to generate letter avatar for
 *
 * @return array
 * @since 1.0.0
 */
function leira_letter_avatar_bb_group_url_args( $url_args, $id_or_email ) {

	/**
	 * Handle only BB Groups
	 */
	if ( $id_or_email instanceof BP_Groups_Group ) {

		$group_id  = bp_get_group_id( $id_or_email );
		$sanitizer = leira_letter_avatar()->sanitizer;

		/**
		 * Determine background
		 */
		$current_option = bp_get_option( 'leira_letter_avatar_bb_group_method', 'auto' );
		$current_option = $sanitizer->method( $current_option );
		switch ( $current_option ) {
			case  'fixed':
				$bg = bp_get_option( 'leira_letter_avatar_bb_group_bg' );
				$bg = $sanitizer->background( $bg );
				break;
			case 'random':

				$bg = groups_get_groupmeta( $group_id, '_leira_letter_avatar_bg' );

				$backgrounds = bp_get_option( 'leira_letter_avatar_bb_group_bgs', 'fc91ad' );
				$backgrounds = $sanitizer->backgrounds( $backgrounds );
				$backgrounds = explode( ',', $backgrounds );
				if ( empty( $backgrounds ) ) {
					$backgrounds = array( 'fc91ad' );
				}

				if ( empty( $bg ) || ! in_array( $bg, $backgrounds ) || ! ctype_xdigit( $bg ) ) {
					//calculate and save
					$bg = rand( 0, count( $backgrounds ) - 1 );
					$bg = $backgrounds[ $bg ]; //random background from array

					groups_update_groupmeta( $group_id, '_leira_letter_avatar_bg', $bg );
				}
				break;
			default:
				$bg = 'fc91ad';
		}
		$bg = trim( trim( $bg ), '#' );
		$bg = $sanitizer->background( $bg );

		/**
		 * Determine the letters color now that we have background color
		 */
		$color_method = bp_get_option( 'leira_letter_avatar_bb_group_color_method', 'auto' );
		$color_method = $sanitizer->color_method( $color_method );
		//By default find the best contrast color for the background
		$color = leira_letter_avatar()->public->get_contrast_color( $bg );
		if ( $color_method == 'fixed' ) {
			$color = bp_get_option( 'leira_letter_avatar_bb_group_color', 'ffffff' );
		}
		$color = trim( trim( $color ), '#' );
		$color = $sanitizer->background( $color );

		/**
		 * Determine Letters to use
		 */
		$letters_count = get_option( 'leira_letter_avatar_bb_group_letters', 2 );
		$letters_count = filter_var( $letters_count, FILTER_VALIDATE_INT );
		$letters_count = $letters_count > 2 ? 2 : $letters_count;
		$letters_count = $letters_count < 1 ? 1 : $letters_count;

		$regex   = '/([^\pL]*(\pL)\pL*)/u';
		$letters = bp_get_group_name( $id_or_email );
		$letters = trim( $letters );
		$letters = preg_replace( $regex, "$2", $letters );//get all initials in the string
		$letters = mb_substr( $letters, 0, $letters_count, 'UTF-8' );//reduce to 2 or less initials

		if ( get_option( 'leira_letter_avatar_bb_group_uppercase', true ) ) {
			/**
			 * Use mb_strtoupper in case initials contains letters with accents
			 */
			$letters = mb_strtoupper( $letters );
		}

		$url_args['name']       = $letters;
		$url_args['rounded']    = bp_get_option( 'leira_letter_avatar_bb_group_rounded', true );
		$url_args['background'] = $bg;
		$url_args['color']      = $color;
		$url_args['format']     = bp_get_option( 'leira_letter_avatar_bb_group_format', 'svg' );
	}

	return $url_args;
}

add_filter( 'leira_letter_avatar_url_args', 'leira_letter_avatar_bb_group_url_args', 10, 2 );

/**
 * Plugin admin area functionalities
 */
if ( is_admin() ) {

	require_once __DIR__ . '/leira-letter-avatar-bb-group-admin.php';

	Leira_Letter_Avatar_BB_Group_Admin::instance();

}