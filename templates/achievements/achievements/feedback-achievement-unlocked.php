<?php
/**
 * Achievement unlocked template part
 *
 * @package Achievements
 * @subpackage ThemeCompatibility
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wrapper function used when displaying the "achievement unlocked" feedback template.
 * This is used to avoid polluting global scope with variables.
 *
 * @since Achievements (3.0)
 * @todo It is not ideal to get the DB information in this template; and as such, this function has no actions as everything here's liable to change.
 * @todo Support multiple unlocked notifications at the same time.
 */
function dpa_feedback_achievement_unlock_wrapper() {
	$notifications = get_posts( array(
		'numberposts' => 1,
		'post_type'   => dpa_get_achievement_post_type(),
		'post__in'    => array_keys( dpa_get_user_notifications() ),
	) );

	$user_profile_url = dpa_get_user_avatar_link( array(
		'type'    => 'url',
		'user_id' => get_current_user_id(),
	) );
?>

<div id="dpa-notifications-wrapper">
	<div id="dpa-notifications" aria-live="polite">

		<?php foreach ( $notifications as $notification ) :
			$title = apply_filters( 'dpa_get_achievement_title', $notification->post_title, $notification->ID );
			$url   = home_url() . '/?p=' . $notification->ID;

			$share_msg    = sprintf( _x( 'I\'ve unlocked the "%1$s" achievement on %2$s!', 'Twitter message: achievement name, site name', 'dpa' ), $title, get_option( 'blogname' ) );
			$facebook_url = sprintf( 'https://www.facebook.com/sharer/sharer.php?u=%1$s', urlencode( esc_url( $url ) ) );
			$twitter_url  = sprintf( 'https://twitter.com/intent/tweet?url=%1$s&amp;text=%2$s', urlencode( esc_url( $url ) ), urlencode( $share_msg ) );
			?>

			<div>
				<h1><?php _e( 'Achievement Unlocked', 'dpa' ); ?></h1>

				<?php
				if ( has_post_thumbnail( $notification->ID ) )
					echo '<a href="' . esc_url( $url ) . '">' . get_the_post_thumbnail( $notification->ID, 'medium', array( 'class' => 'attachment-medium dpa-achievement-unlocked-thumbnail' ) ) . '</a>';
				?>

				<h2><?php echo $title; ?></h2>

				<div>
					<p><?php printf( __( "Hey, you've unlocked the &#8220;%s&#8221; achievement. Congratulations!", 'dpa' ), $title ); ?></p>

					<p><?php
						printf(
							__( 'Celebrate and share with your friends on %1$s and %2$s.', 'dpa' ),
							sprintf( '<a href="%1$s" target="_new">%2$s</a>', esc_url( $facebook_url ), __( 'Facebook', 'dpa' ) ),
							sprintf( '<a href="%1$s" target="_new">%2$s</a>', esc_url( $twitter_url ),  __( 'Twitter',  'dpa' ) )
						);
					?></p>

					<p><a class="dpa-notification-cta" href="<?php echo esc_url( $user_profile_url ); ?>"><?php _e( 'See your other achievements', 'dpa' ); ?></a></p>
				</div>
			</div>

		<?php endforeach; ?>

	</div><!-- #dpa-notifications -->
</div><!-- #dpa-notifications-wrapper -->

<?php
}  // function dpa_feedback_achievement_unlock_wrapper()
dpa_feedback_achievement_unlock_wrapper();