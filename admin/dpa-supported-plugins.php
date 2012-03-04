<?php
/**
 * "Supported plugins" admin screens
 *
 * @package Achievements
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Supported Plugins admin screen
 *
 * @since 1.0
 */
function dpa_supported_plugins() {
	// See if a cookie has been set to remember which view the user was on last. Defaults to 'grid'.
	if ( ! empty( $_COOKIE['dpa_sp_view'] ) && in_array( trim( $_COOKIE['dpa_sp_view'] ), array( 'detail', 'list', 'grid', ) ) )
	 	$view = trim( $_COOKIE['dpa_sp_view'] );
	else
		$view = 'grid';
?>

	<div class="wrap">
		<?php screen_icon( 'options-general' ); ?>
		<h2><?php _e( 'Supported Plugins', 'dpa' ); ?></h2>

		<div id="poststuff">
			<div id="post-body">
				<div id="post-body-content">
					<?php dpa_supported_plugins_header(); ?>

					<div class="detail <?php if ( 'detail' == $view ) echo 'current'; ?>"><?php dpa_supported_plugins_detail(); ?></div>
					<div class="list <?php if ( 'list' == $view ) echo 'current'; ?>"><?php dpa_supported_plugins_list(); ?></div>
					<div class="grid <?php if ( 'grid' == $view ) echo 'current'; ?>"><?php dpa_supported_plugins_grid(); ?></div>
				</div>
			</div><!-- #post-body -->

		</div><!-- #poststuff -->
	</div><!-- .wrap -->

<?php
}

/**
 * Common toolbar header for supported plugins header screen
 *
 * @since 1.0
 */
function dpa_supported_plugins_header() {
	// See if a cookie has been set to remember which view the user was on last. Defaults to 'grid'.
	if ( ! empty( $_COOKIE['dpa_sp_view'] ) && in_array( trim( $_COOKIE['dpa_sp_view'] ), array( 'detail', 'list', 'grid', ) ) )
	 	$view = trim( $_COOKIE['dpa_sp_view'] );
	else
		$view = 'grid';

	// See if a cookie has been set to remember which filter the user was on last. Defaults to 'all'.
	if ( ! empty( $_COOKIE['dpa_sp_filter'] ) && in_array( trim( $_COOKIE['dpa_sp_filter'] ), array( 'all', '0', '1', ) ) )
	 	$filter = trim( $_COOKIE['dpa_sp_filter'] );
	else
		$filter = 'all';
	?>
	<form name="dpa-toolbar" method="post" enctype="multipart/form-data">

		<div id="dpa-toolbar-wrapper">
			<input type="search" results="5" name="dpa-toolbar-search" id="dpa-toolbar-search" placeholder="<?php esc_attr_e( 'Search for a plugin...', 'dpa' ); ?>" />

			<select class="<?php if ( ! $GLOBALS['is_gecko'] ) echo 'dpa-ff-hack'; ?>" name="dpa-toolbar-filter" id="dpa-toolbar-filter">
				<option value="all" <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All Plugins', 'dpa' ); ?></option>
				<option value="0"   <?php selected( $filter, '0'   ); ?>><?php esc_html_e( 'Available Plugins', 'dpa' ); ?></option>
				<option value="1"   <?php selected( $filter, '1'   ); ?>><?php esc_html_e( 'Installed Plugins', 'dpa' ); ?></option>
			</select>

			<ul id="dpa-toolbar-views">
				<li class="tab"><a class="grid <?php if ( 'grid' == $view ) echo 'current'; ?>" title="<?php esc_attr_e( 'Grid view', 'dpa' ); ?>" href="#"></a></li>
				<li class="tab"><a class="list <?php if ( 'list' == $view ) echo 'current'; ?>" title="<?php esc_attr_e( 'List view', 'dpa' ); ?>" href="#"></a></li>
				<li class="tab"><a class="detail <?php if ( 'detail' == $view ) echo 'current'; ?>" title="<?php esc_attr_e( 'Detail view', 'dpa' ); ?>" href="#"></a></li>
			</ul>
		</div>

	</form>
	<?php
}

/**
 * Supported Plugins detail view
 *
 * Detail view consists of a large display of a specific plugin's details,
 * and an RSS feed from the author's site. There is a list box on the side
 * of the screen to choose between different plugins.
 *
 * @since 1.0
 */
function dpa_supported_plugins_detail() {
	$last_plugin = '';

	// See if a cookie has been set to remember the last viewed plugin
	if ( ! empty( $_COOKIE['dpa_sp_lastplugin'] ) )
		$last_plugin = trim( $_COOKIE['dpa_sp_lastplugin'] );

	// Get supported plugins
	$plugins = dpa_get_supported_plugins();
?>

	<ul>
		<?php foreach ( $plugins as $plugin ) :
			$class = $plugin->slug;

			// Record if this plugin is installed by setting the class
			if ( in_array( $plugin->install_status['status'], array( 'latest_installed', 'newer_installed', 'update_available', ) ) )
				$class .= ' installed';
			else
				$class .= ' notinstalled';
		?>
			<li class="<?php echo esc_attr( $class ); if ( $last_plugin == $plugin->slug ) echo ' current'; ?>"><?php echo convert_chars( wptexturize( wp_kses_data( $plugin->name ) ) ); ?></li>
		<?php endforeach; ?>
	</ul>

	<div id="dpa-detail-contents">
		<?php foreach ( $plugins as $plugin ) :
			$class = $plugin->slug;

			// Record if this plugin is installed by setting the class
			if ( in_array( $plugin->install_status['status'], array( 'latest_installed', 'newer_installed', 'update_available', ) ) )
				$class .= ' installed';
			else
				$class .= ' notinstalled';
		?>

			<div class="<?php echo esc_attr( $class ); if ( $last_plugin == $plugin->slug ) echo ' current'; ?>">
				<h3><?php echo convert_chars( wptexturize( wp_kses_data( $plugin->name ) ) ); ?></h3>

				<div class="description">
					<h4><?php _e( 'Plugin Info', 'dpa' ); ?></h4>
					<p><?php echo convert_chars( wptexturize( wp_kses_data( $plugin->description ) ) ); ?></p>

					<?php
					// Is plugin installed?
					if ( in_array( $plugin->install_status['status'], array( 'latest_installed', 'newer_installed', 'update_available', ) ) ) {
						_e( '<p class="installed">Status: Ready</span>', 'dpa' );

					// It's not installed
					} else {
						// If current user can install plugins, link directly to the install screen
						if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) )
							printf( __( '<p>Status: <a class="thickbox" href="%1$s">Install Plugin</a></p>', 'dpa' ), esc_attr( $plugin->install_url ) );
						else
							_e( '<p>Status: Not installed</p>', 'dpa' );
					}
					?>
				</div>

				<div class="supported-events">
					<h4><?php _e( 'Supported Events', 'dpa' ); ?></h4>
					<p>@TODO Display supported events.</p>
				</div>

				<div class="author">
					<h4><?php _e( 'News From The Author', 'dpa' ); ?></h4>

					<?php
					// Fetch each plugin's RSS feed, and parse the updates.
					$rss = fetch_feed( esc_url( $plugin->rss_url ) );
					if ( ! is_wp_error( $rss ) ) {
						$content = '<ul>';
						$items   = $rss->get_items( 0, $rss->get_item_quantity( 5 ) );

						foreach ( $items as $item ) {
							// Prepare excerpt
							$excerpt = wp_html_excerpt( $item->get_content(), 200 );
							
							// Skip posts with no words
							if ( empty( $excerpt ) )
								continue;
							else
								$excerpt .= _x( '&#8230;', 'ellipsis character at end of post excerpt to show text has been truncated', 'dpa' );

							// Prepare date
							$date  = esc_html( strip_tags( $item->get_date() ) );
							$date  = gmdate( get_option( 'date_format' ), strtotime( $date ) );

							// Prepare title and URL back to the post's site
							$title = convert_chars( wptexturize( wp_kses_data( stripslashes( $item->get_title() ) ) ) );
							$url   = $item->get_permalink();

							// Build the output
							$content .= '<li>';

							// Translators: Links to blog post. Text is "name of blog post - date".
							$content .= sprintf( __( '<h5><a href="%1$s">%2$s - %3$s</a></h5>', 'dpa' ), esc_url( $url ), esc_html( $title ), esc_html( $date ) );
							$content .= '<p>' . convert_chars( wptexturize( wp_kses_data( $excerpt ) ) ) . '</p>';
							$content .= sprintf( __( '<p><a href="%1$s">Read More</a></p>', 'dpa' ), esc_url( $url ) );

							$content .= '</li>';
						}
						echo $content . '</ul>';

					} else {
						echo '<p>' . __( 'No news found.', 'dpa' ) . '</p>';
					}
					?>
				</div>
			</div>

		<?php endforeach; ?>
	</div>

<?php
}

/**
 * Supported Plugins list view
 *
 * Lists view consists of a table, with one row to a plugin.
 *
 * @since 1.0
 */
function dpa_supported_plugins_list() {
	$plugins = dpa_get_supported_plugins();	
	uasort( $plugins, create_function( '$a, $b', 'return strnatcasecmp($a->name, $b->name);' ) );
?>

	<table class="widefat">
		<caption class="screen-reader-text"><?php _e( 'This table lists all of the plugins that Achievements has built-in support for. For each plugin, it shows a banner, its WordPress.org plugin rating, who contributed to its development, and whether your site has the plugin installed or not.', 'dpa' ); ?></caption>
		<thead>
			<tr>
				<th scope="col"></th>
				<th scope="col"><?php _e( 'Plugin', 'dpa' ); ?></th>
				<th scope="col"><?php _e( 'Rating', 'dpa' ); ?></th>
				<th scope="col"><?php _e( 'Status', 'dpa' ); ?></th>
				<th scope="col"><?php _e( 'Credits', 'dpa' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th></th>
				<th><?php _e( 'Plugin', 'dpa' ); ?></th>
				<th><?php _e( 'Rating', 'dpa' ); ?></th>
				<th><?php _e( 'Status', 'dpa' ); ?></th>
				<th><?php _e( 'Credits', 'dpa' ); ?></th>
			</tr>
		</tfoot>

		<tbody>

			<?php foreach ( $plugins as $plugin ) :
				// Record if this plugin is installed by setting the class
				if ( in_array( $plugin->install_status['status'], array( 'latest_installed', 'newer_installed', 'update_available', ) ) )
					$class = 'installed';
				else
					$class = 'notinstalled';
			?>
				<tr class="<?php echo esc_attr( $class ); ?>">
					<td class="plugin">
						<?php
						$image_url   = esc_url( $plugin->image->large );
						$plugin_name = convert_chars( wptexturize( wp_kses_data( $plugin->name ) ) );
 						printf( '<img src="%1$s" alt="%2$s" title="%3$s" class="%4$s" />', esc_attr( $image_url ), esc_attr( $plugin_name ), esc_attr( $plugin_name ), esc_attr( $plugin->slug ) );
						?>
					</td>

					<td class="name"><?php echo $plugin_name; ?></td>
					<td class="rating"><?php echo convert_chars( wptexturize( wp_kses_data( $plugin->rating ) ) ); ?></td>
					<td>
						<?php
						// Is plugin installed?
						if ( in_array( $plugin->install_status['status'], array( 'latest_installed', 'newer_installed', 'update_available', ) ) ) {
							_e( '<td class="installed"><span class="installed">Ready</span></td>', 'dpa' );

						// It's not installed
						} else {
							echo '<td class="notinstalled">';

							// If current user can install plugins, link directly to the install screen
							if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) )
								printf( __( '<a class="thickbox" href="%1$s">Not installed</a>', 'dpa' ), esc_attr( $plugin->install_url ) );
							else
								_e( 'Not installed', 'dpa' );

							echo '</td>';
						}
						?>
					</td>

					<td class="contributors">
						<?php
						foreach ( $plugin->contributors as $name => $gravatar_url ) {
							// Sanitise plugin info as it may have been fetched from wporg
							$gravatar_url = esc_url( $gravatar_url );
							$profile_url  = esc_url( 'http://profiles.wordpress.org/users/' . $name . '/profile/public/' );
							$name         = convert_chars( wptexturize( wp_kses_data( $name ) ) );

							printf( '<a href="%1$s"><img src="%2$s" alt="%3$s" title="%4$s" /></a>', esc_attr( $profile_url ), esc_attr( $gravatar_url ), esc_attr( $name ), esc_attr( $name ) );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>

		</tbody>
	</table>

<?php
}

/**
 * Supported Plugins grid view
 *
 * Grid view consists of rows and columns of large logos of plugins.
 *
 * @since 1.0
 */
function dpa_supported_plugins_grid() {
	// todo: Use media wueries to calculate width of images
	$plugins = dpa_get_supported_plugins();
	$style   = ( ( 6 / 10 ) * 772 ) . 'px';

	foreach ( $plugins as $plugin ) {
		// Record if this plugin is installed by setting the class
		if ( in_array( $plugin->install_status['status'], array( 'latest_installed', 'newer_installed', 'update_available', ) ) )
			$class = ' installed';
		else
			$class = ' notinstalled';

		printf( '<a href="#" class="%1$s"><img class="%2$s" src="%3$s" alt="%4$s" style="width: %5$s" /></a>', esc_attr( $class ), esc_attr( $plugin->slug ), esc_attr( $plugin->image->large ), esc_attr( $plugin->name ), esc_attr( $style ) );
	}
}
?>