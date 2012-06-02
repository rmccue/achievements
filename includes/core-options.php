<?php
/**
* Achievement options
*
* @package Achievements
* @subpackage CoreOptions
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the default site options and their values
 *
 * @return array Option names and values
 * @since 3.0
 */
function dpa_get_default_options() {
	$options = array(
		'_dpa_db_version'            => '0',             // Initial DB version
		'_dpa_achievement_slug'      => 'achievements',  // Achievement post type slug

		// Achievement post type
		'_dpa_achievements_per_page' => 15,              // Achievements per page

		// Settings
		'_dpa_run_networkwide'       => false,           // Run plugin across entire network, rather than on a specific site (for multisite)
	);

	return apply_filters( 'dpa_get_default_options', $options );
}

/**
 * Add default options to DB
 *
 * This is only called when the plugin is activated and is non-destructive,
 * so existing settings will not be overridden.
 *
 * @since 3.0
 */
function dpa_add_options() {
	$options = dpa_get_default_options();

	// Add default options
	foreach ( $options as $key => $value )
		add_option( $key, $value );

	// Run an action for other plugins
	do_action( 'dpa_add_options' );
}

/**
 * Delete default options
 *
 * Hooked to dpa_uninstall, it is only called when the plugin is uninstalled.
 * This is destructive, so existing settings will be destroyed.
 *
 * @since 3.0
 */
function dpa_delete_options() {
	$options = dpa_get_default_options();

	// Delete default options
	foreach ( $options as $key => $value )
		delete_option( $key );

	// Run an action for other plugins
	do_action( 'dpa_delete_options' );
}

/**
 * Add filters to each Achievements option and allow them to be overloaded
 * from inside the achievements()->options array.
 * 
 * @since 3.0
 */
function dpa_setup_option_filters() {
	$options = dpa_get_default_options();

	// Add filters to each option
	foreach ( $options as $key => $value )
		add_filter( 'pre_option_' . $key, 'dpa_pre_get_option' );

	// Run an action for other plugins
	do_action( 'dpa_setup_option_filters' );
}

/**
 * Filter default options and allow them to be overloaded from inside the
 * achievements()->options array.
 *
 * @param bool $value
 * @return mixed
 * @since 3.0
 */
function dpa_pre_get_option( $value = false ) {
	// Get the name of the current filter so we can manipulate it, and remove the filter prefix
	$option = str_replace( 'pre_option_', '', current_filter() );

	// Check the options global for preset value
	if ( ! empty( achievements()->options[$option] ) )
		$value = achievements()->options[$option];

	return $value;
}


/**
 * Numeric settings
 */

/**
 * Return the achievements per page setting
 *
 * @return int
 * @since 3.0
 */
function dpa_get_achievements_per_page() {
	$default = 15;

	// Get database option and cast as integer
	$per = $retval = (int) get_option( '_dpa_achievements_per_page', $default );

	// If return val is empty, set it to default
	if ( empty( $retval ) )
		$retval = $default;

	// Filter and return
	return (int) apply_filters( 'dpa_get_achievements_per_page', $retval, $per );
}

/**
 * Boolean functions (aka is this thing on?)
 */

/**
 * Checks if the plugin is set to run network-wide (for multisite).
 *
 * @return true
 * @since 3.0
 */
function dpa_is_running_networkwide() {
	return (bool) apply_filters( 'dpa_is_running_networkwide', (bool) get_option( '_dpa_run_networkwide' ) );
}

/**
 * Slug functions
 */

/**
 * Return the achievement post type slug
 *
 * @return string
 * @since 3.0
 */
function dpa_get_achievement_slug() {
	return apply_filters( 'dpa_get_achievement_slug', get_option( '_dpa_achievement_slug' ) );
}