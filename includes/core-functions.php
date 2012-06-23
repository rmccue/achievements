<?php
/**
 * Achievements core functions
 *
 * The architecture of Achievements is straightforward, making use of custom post types,
 * statuses, and taxonomies (no custom tables or SQL queries). Custom rewrite rules and
 * endpoints are used to register and display templates, but this file primarily takes
 * care of the core logic -- when a user does something, how does that trigger an event
 * and award an achievement?
 *
 * The dpa_achievement post type has a taxonomy called dpa_event. An "event" is any
 * do_action in WordPress. An achievement post is assigned a term from that taxonomy.
 *
 * On every page load, we grab all terms from the dpa_event taxonomy that have been
 * associated with a post. The dpa_handle_event() function is then registered with
 * those actions, and that's what lets us detect when something interesting happens.
 *
 * dpa_handle_event() makes a WP_Query query of the dpa_achievement post type, passing
 * the name of the current action (aka event) as the "tax_query" parameter. This is
 * because multiple achievements could use the same event and we need details of each
 * of those achievements. At this point, we know that the user has maybe unlocked an
 * achievement.
 *
 * The aptly named dpa_maybe_unlock_achievement() function takes over. An achievement
 * has a criteria of how many times an event has to occur (in post meta) for a user
 * before that achievement is unlocked. If the criteria has not been met, then a
 * record of the progress is stored in another custom post type, dpa_progress. If the
 * criteria was met, the dpa_progress post's status is changed to "unlocked".
 *
 * Each achievement has points (in post meta) and those are added to the user's score
 * (in user meta). The user is then made aware that they've unlocked an achievement.
 *
 * @package Achievements
 * @subpackage CoreFunctions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Check if any of the plugin extensions need to be set up or updated
 *
 * @since 3.0
 */
function dpa_maybe_update_extensions() {
	$orig_versions = $versions = dpa_get_extension_versions();

	foreach ( achievements()->extensions as $extension ) {
		// Extensions must inherit the DPA_Extension class
		if ( ! is_a( $extension, 'DPA_Extension' ) )
			continue;

		// No previous version in $versions, so add the extension's actions to the dpa_event taxonomy
		$id = $extension->get_id();
		if ( ! isset( $versions[$id] ) ) {
			$actions = $extension->get_actions();

			// Add the actions to the dpa_event taxonomy
			foreach ( $actions as $action_name => $action_desc )
				wp_insert_term( $action_name, dpa_get_event_tax_id(), array( 'description' => $action_desc ) );

			// Record version
			$versions[$id] = $extension->get_version();

		// An update is available.
		} elseif ( version_compare( $extension->get_version(), $versions[$id], '>' ) ) {
			$extension->do_update( $versions[$id] );
			$versions[$id] = $extension->get_version();
		}
	}

	// Update the version records in the database
	if ( $orig_versions != $versions )
		dpa_update_extension_versions( $versions );
}

/**
 * Achievement actions are stored as a custom taxonomy. This function queries that taxonomy to find items,
 * and then using the items' slugs (which are the name of a WordPress action), registers a handler action
 * in Achievements. The user needs to be logged in for this to hapen.
 *
 * Posts in trash are returned by get_terms() even if hide_empty is set. We double-check the post status
 * before we actually give the award.
 *
 * This function is invoked on every page load but as get_terms() provides built-in caching, we don't
 * have to worry about that.
 *
 * @since 3.0
 */
function dpa_register_events() {
	// Only do things if the user is logged in
	if ( ! is_user_logged_in() )
		return;

	// Get all valid events from the event taxononmy. A valid event is one associated with a post type.
	$events = get_terms( achievements()->event_tax_id, array( 'hide_empty' => true )  );
	if ( is_wp_error( $events ) )
		return;

	$events = wp_list_pluck( (array) $events, 'slug' );
	$events = apply_filters( 'dpa_register_events', $events );

	// For each event, add a handler function to the action.
	foreach ( (array) $events as $event )
		add_action( $event, 'dpa_handle_event', 12, 10 );  // Priority 12 in case object modified by other plugins
}

/**
 * Implements the Achievement actions and unlocks if criteria met.
 *
 * @param string $name Action name
 * @param array $func_args Optional; action's arguments, from func_get_args().
 * @see dpa_register_events()
 * @since 3.0
 */
function dpa_handle_event() {
	// Look at the current_filter to find out what action has occured
	$event_name = current_filter();
	$func_args  = func_get_args();

	// Let other plugins do things before anything happens
	do_action( 'dpa_before_handle_event', $event_name, $func_args );

	// Allow other plugins to bail out early
	$event_name = apply_filters( 'dpa_handle_event_name', $event_name, $func_args );
	if ( false === $event_name )
		return;

	// This filter allows the user ID to be updated (e.g. for draft posts which are then published by someone else)
	$user_id = absint( apply_filters( 'dpa_handle_event_user_id', get_current_user_id(), $event_name, $func_args ) );
	if ( ! $user_id )
		return;

	// Find achievements that are associated with the $event_name taxonomy
	$args = array(
		'ach_event'             => $event_name,  // Get posts in the event taxonomy matching the event name
		'ach_populate_progress' => $user_id,     // Fetch Progress posts for this user ID
		'no_found_rows'         => true,         // Disable SQL_CALC_FOUND_ROWS
		'posts_per_page'        => -1,           // No pagination
		's'                     => '',           // Stop sneaky people running searches on this query
	);

	// Loop through achievements found
	if ( dpa_has_achievements( $args ) ) {

		while ( dpa_achievements() ) {
			dpa_the_achievement();

			/**
			 * Check the achievement post is published.
			 *
			 * get_terms() in dpa_register_events() can retrieve taxonomies which are
			 * associated only with posts in the trash. We only want to process
			 * 'active' achievements (post_status = published).
			 */
			if ( 'publish' != achievements()->achievement_query->post->post_status )
				continue;

			// Let other plugins do things before we maybe_unlock_achievement
			do_action( 'dpa_handle_event', $event_name, $func_args, $user_id, $args );

			// Allow plugins to stop any more processing for this achievement
			if ( false === apply_filters( 'dpa_handle_event_maybe_unlock_achievement', true, $event_name, $func_args, $user_id, $args ) )
				continue;

			// Look in the progress posts and match against a post_parent which is the same as the current achievement.
			$progress = wp_filter_object_list( achievements()->progress_query->posts, array( 'post_parent' => dpa_get_the_achievement_ID() ) );
			$progress = array_shift( $progress );

			// If the achievement hasn't already been unlocked, maybe_unlock_achievement.
			if ( empty( $progress ) || dpa_get_unlocked_status_id() != $progress->post_status )
				dpa_maybe_unlock_achievement( $user_id, false, $progress );
		}
	}

	unset( achievements()->achievement_query );
	unset( achievements()->progress_query    );

	// Everything's done. Let other plugins do things.
	do_action( 'dpa_after_handle_event', $event_name, $func_args, $user_id, $args );
}

/**
 * If the specified achievement's criteria has been met, we unlock the
 * achievement. Otherwise we record progress for the achievement for next time.
 *
 * $skip_validation is the second parameter for backpat with Achievements 2.x
 *
 * @param int     $user_id
 * @param string  $skip_validation  Optional. Set to "skip_validation" to skip Achievement validation (unlock achievement regardless of criteria).
 * @param object  $progress_obj     Optional. The Progress post object. Defaults to Progress object in the Progress loop.
 * @param object  $achievement_obj  Optional. The Achievement post object to maybe_unlock. Defaults to current object in Achievement loop.
 * @since 2.0
 */
function dpa_maybe_unlock_achievement( $user_id, $skip_validation = '', $progress_obj = null, $achievement_obj = null ) {
	// Default to current object in the achievement loop
	if ( empty( $achievement_obj ) )
		$achievement_obj = achievements()->achievement_query->post;

	// Default to progress object in the progress loop
	if ( empty( $progress_obj ) ) {
		$progress_obj = wp_filter_object_list( achievements()->progress_query->posts, array( 'post_parent' => $achievement_obj->ID ) );
		$progress_obj = array_shift( $progress_obj );
	}

	// Has the user already unlocked the achievement?
	if ( ! empty( $progress_obj ) && dpa_get_unlocked_status_id() == $progress_obj->post_status )
		return;

	// Prepare default values to create/update a progress post
	$progress_args = array(
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_author'    => $user_id,
		'post_parent'    => $achievement_obj->ID,
		'post_title'     => $achievement_obj->post_title,
		'post_type'      => dpa_get_progress_post_type(),
	);

	// If achievement already has some progress, grab the ID so we update the post later
	if ( ! empty( $progress_obj->ID ) )
		$progress_args['ID'] = $progress_obj->ID;

	// Does the achievement not have a target set or are we skipping validation?
	$achievement_target = (int) get_post_meta( $achievement_obj->ID, '_dpa_target', true );
	if ( empty( $achievement_target ) || 'skip_validation' === $skip_validation ) {

		// Unlock the achievement
		$progress_args['post_status'] = dpa_get_unlocked_status_id();


	// Does the achievement have a target set?
	} elseif ( ! empty( $achievement_target ) ) {

		// Increment progress count
		$progress_obj->content = (int) $progress_obj->content + apply_filters( 'dpa_maybe_unlock_achievement_progress_increment', 1 );

		// Does the progress count now meet the achievement target?
		if ( (int) $progress_obj->content >= $achievement_target ) {

			// Yes. Unlock achievement.
			$progress_args['post_status'] = dpa_get_unlocked_status_id();
		}
	}

	// Create or update the progress post
	$progress_id = wp_insert_post( $progress_args );

	// If the achievement was just unlocked, do stuff.
	if ( dpa_get_unlocked_status_id() == $progress_args['post_status'] ) {

		// Update user's points
		$points = dpa_get_user_points( $user_id ) + get_post_meta( $achievement_obj->ID, '_dpa_points', true );
		dpa_update_user_points( $user_id, $points );

		// Achievement was unlocked. Let other plugins do things.
		do_action( 'dpa_unlock_achievement', $achievement_obj, $user_id, $progress_obj, $progress_id );
	}
}