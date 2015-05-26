<?php
/*
Plugin Name: Popular Posts by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/
Description: This plugin will help you display the most popular blog posts in the widget.
Author: BestWebSoft
Version: 0.1.2
Author URI: http://bestwebsoft.com/
License: GPLv3 or later
*/

/*  @ Copyright 2015  BestWebSoft  ( http://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Add option page in admin menu */
if ( ! function_exists( 'pplrpsts_admin_menu' ) ) {
	function pplrpsts_admin_menu() {
		bws_add_general_menu( plugin_basename( __FILE__ ) );
		add_submenu_page( 'bws_plugins', __( 'Popular Posts Settings', 'popular_posts' ), 'Popular Posts', 'manage_options', "popular-posts.php", 'pplrpsts_settings_page' );
	}
}

/* Plugin initialization - add internationalization and size for image*/
if ( ! function_exists ( 'pplrpsts_init' ) ) {
	function pplrpsts_init() {
		global $pplrpsts_plugin_info;	
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'popular_posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_functions.php' );
		
		if ( empty( $pplrpsts_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$pplrpsts_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version  */
		bws_wp_version_check( plugin_basename( __FILE__ ), $pplrpsts_plugin_info, "3.1" );

		if ( ! session_id() )
			@session_start();
		
		add_image_size( 'popular-post-featured-image', 60, 60, true );
	}
}

/* Plugin initialization for admin page */
if ( ! function_exists ( 'pplrpsts_admin_init' ) ) {
	function pplrpsts_admin_init() {
		global $bws_plugin_info, $pplrpsts_plugin_info, $pagenow;
		
		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '177', 'version' => $pplrpsts_plugin_info["Version"] );

		/* Call register settings function */
		if ( 'widgets.php' == $pagenow || ( isset( $_GET['page'] ) && "popular-posts.php" == $_GET['page'] ) )
			pplrpsts_set_options();
	}
}

/* Setting options */
if ( ! function_exists( 'pplrpsts_set_options' ) ) {
	function pplrpsts_set_options() {
		global $pplrpsts_options, $pplrpsts_plugin_info;

		$pplrpsts_options_defaults	=	array(
			'plugin_option_version'	=>	$pplrpsts_plugin_info["Version"],			
			'widget_title'			=>	__( 'Popular Posts', 'popular_posts' ),
			'count'					=>	'5',
			'excerpt_length'		=>	'10',
			'excerpt_more'			=>	'...',
			'no_preview_img'		=>	plugins_url( 'images/no_preview.jpg', __FILE__ ),
			'order_by'				=>	'comment_count'
		);

		if ( ! get_option( 'pplrpsts_options' ) )
			add_option( 'pplrpsts_options', $pplrpsts_options_defaults );

		$pplrpsts_options = get_option( 'pplrpsts_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $pplrpsts_options['plugin_option_version'] ) || $pplrpsts_options['plugin_option_version'] != $pplrpsts_plugin_info["Version"] ) {
			$pplrpsts_options = array_merge( $pplrpsts_options_defaults, $pplrpsts_options );
			$pplrpsts_options['plugin_option_version'] = $pplrpsts_plugin_info["Version"];
			update_option( 'pplrpsts_options', $pplrpsts_options );
		}
	}
}

/* Function for display popular_posts settings page in the admin area */
if ( ! function_exists( 'pplrpsts_settings_page' ) ) {
	function pplrpsts_settings_page() {
		global $pplrpsts_options, $pplrpsts_plugin_info;
		$error = $message = "";

		/* Save data for settings page */
		if ( isset( $_REQUEST['pplrpsts_form_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'pplrpsts_nonce_name' ) ) {

			$pplrpsts_options['widget_title']	= ( ! empty( $_POST['pplrpsts_widget_title'] ) ) ? stripslashes( esc_html( $_POST['pplrpsts_widget_title'] ) ) : null;
			$pplrpsts_options['count']			= ( ! empty( $_POST['pplrpsts_count'] ) ) ? intval( $_POST['pplrpsts_count'] ) : 2;
			$pplrpsts_options['excerpt_length'] = ( ! empty( $_POST['pplrpsts_excerpt_length'] ) ) ? stripslashes( esc_html( $_POST['pplrpsts_excerpt_length'] ) ) : 10;
			$pplrpsts_options['excerpt_more']   = ( ! empty( $_POST['pplrpsts_excerpt_more'] ) ) ? stripslashes( esc_html( $_POST['pplrpsts_excerpt_more'] ) ) : '...';
			if ( ! empty( $_POST['pplrpsts_no_preview_img'] ) && pplrpsts_is_200( $_POST['pplrpsts_no_preview_img'] ) && getimagesize( $_POST['pplrpsts_no_preview_img'] ) )
				$pplrpsts_options['no_preview_img'] = $_POST['pplrpsts_no_preview_img'];
			else
				$pplrpsts_options['no_preview_img'] = plugins_url( 'images/no_preview.jpg', __FILE__ );
			$pplrpsts_options['order_by'] 		= ( ! empty( $_POST['pplrpsts_order_by'] ) ) ? $_POST['pplrpsts_order_by'] : 'comment_count';

			if ( "" == $error ) {
				/* Update options in the database */
				update_option( 'pplrpsts_options', $pplrpsts_options );
				$message = __( "Settings saved.", 'popular_posts' );
			}
		} /* Display form on the setting page */ ?>
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2><?php _e( 'Popular Posts Settings', 'popular_posts' ); ?></h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="admin.php?page=popular-posts.php"><?php _e( 'Settings', 'popular_posts' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/popular-posts/faq/" target="_blank"><?php _e( 'FAQ', 'popular_posts' ); ?></a>
			</h2>
			<div id="pplrpsts_settings_notice" class="updated fade" style="display:none"><p><strong><?php _e( "Notice:", 'popular_posts' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'popular_posts' ); ?></p></div>
			<div class="updated fade" <?php if ( ! isset( $_REQUEST['pplrpsts_form_submit'] ) || $error != "" ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><?php echo $error; ?></p></div>
			<form id="pplrpsts_settings_form" method="post" action="admin.php?page=popular-posts.php">
				<p><?php _e( 'If you would like to display popular posts with a widget, you need to add the widget "Popular Posts" in the Widgets tab.', 'popular_posts' ); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Widget title', 'popular_posts' ); ?></th>
						<td>
							<input name="pplrpsts_widget_title" type="text" value="<?php echo $pplrpsts_options['widget_title']; ?>"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Number of posts', 'popular_posts' ); ?></th>
						<td>
							<input name="pplrpsts_count" type="text" value="<?php echo $pplrpsts_options['count']; ?>"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Excerpt length', 'popular_posts' ); ?></th>
						<td>
							<input name="pplrpsts_excerpt_length" type="text" value="<?php echo $pplrpsts_options['excerpt_length']; ?>"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( '"Read more" text', 'popular_posts' ); ?></th>
						<td>
							<input name="pplrpsts_excerpt_more" type="text" value="<?php echo $pplrpsts_options['excerpt_more']; ?>"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Default image (full URL), if no featured image is available', 'popular_posts' ); ?></th>
						<td>
							<input name="pplrpsts_no_preview_img" type="text" value="<?php echo $pplrpsts_options['no_preview_img']; ?>"/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Order by number of', 'popular_posts' ); ?></th>
						<td>
							<label><input name="pplrpsts_order_by" type="radio" value="comment_count" <?php if ( 'comment_count' == $pplrpsts_options['order_by'] ) echo 'checked="checked"'; ?> /> <?php _e( 'comments', 'popular_posts' ); ?></label><br />
							<label><input name="pplrpsts_order_by" type="radio" value="views_count" <?php if ( 'views_count' == $pplrpsts_options['order_by'] ) echo 'checked="checked"'; ?> /> <?php _e( 'views', 'popular_posts' ); ?></label>
						</td>
					</tr>
				</table>
				<input type="hidden" name="pplrpsts_form_submit" value="submit" />
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'popular_posts' ); ?>" />
				</p>
				<?php wp_nonce_field( plugin_basename(__FILE__), 'pplrpsts_nonce_name' ); ?>
			</form>
			<?php bws_plugin_reviews_block( $pplrpsts_plugin_info["Name"], 'bws-popular-posts' ); ?>
		</div>
	<?php }
}

/* Create widget for plugin */
if ( ! class_exists( 'PopularPosts' ) ) {
	class PopularPosts extends WP_Widget {

		function PopularPosts() {
			/* Instantiate the parent object */
			parent::__construct( 
				'pplrpsts_popular_posts_widget', 
				__( 'Popular Posts Widget', 'popular_posts' ),
				array( 'description' => __( 'Widget for displaying Popular Posts by comments or views count.', 'popular_posts' ) )
			);
		}
		
		/* Outputs the content of the widget */
		function widget( $args, $instance ) {
			global $post, $pplrpsts_excerpt_length, $pplrpsts_excerpt_more, $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$widget_title     	= isset( $instance['widget_title'] ) ? $instance['widget_title'] : $pplrpsts_options['widget_title'];
			$count            	= isset( $instance['count'] ) ? $instance['count'] : $pplrpsts_options['count'];
			$excerpt_length 	= $pplrpsts_excerpt_length = isset( $instance['excerpt_length'] ) ? $instance['excerpt_length'] : $pplrpsts_options['excerpt_length'];
			$excerpt_more 		= $pplrpsts_excerpt_more = isset( $instance['excerpt_more'] ) ? $instance['excerpt_more'] : $pplrpsts_options['excerpt_more']; 
			$no_preview_img		= isset( $instance['no_preview_img'] ) ? $instance['no_preview_img'] : $pplrpsts_options['no_preview_img'];
			$order_by			= isset( $instance['order_by'] ) ? $instance['order_by'] : $pplrpsts_options['order_by'];
			echo $args['before_widget'];
			if ( ! empty( $widget_title ) ) { 
				echo $args['before_title'] . $widget_title . $args['after_title'];
			} ?>
			<div class="pplrpsts-popular-posts">
				<?php if ( 'comment_count' == $order_by )
					$query_args = array(
						'post_type'				=> 'post',
						'orderby'				=> 'comment_count',
						'order'					=> 'DESC',
						'posts_per_page'		=> $count,
						'ignore_sticky_posts' 	=> 1
					);
				else
					$query_args = array(
						'post_type'				=> 'post',
						'meta_key'				=> 'pplrpsts_post_views_count',
						'orderby'				=> 'meta_value_num',
						'order'					=> 'DESC',
						'posts_per_page'		=> $count,
						'ignore_sticky_posts' 	=> 1
					);

				if ( ! function_exists ( 'is_plugin_active' ) ) 
					include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

				if ( is_plugin_active( 'custom-fields-search-pro/custom-fields-search-pro.php' ) || is_plugin_active( 'custom-fields-search/custom-fields-search.php' ) ) {
					$cstmfldssrch_is_active = true;
					remove_filter( 'posts_join', 'cstmfldssrch_join' );
					remove_filter( 'posts_where', 'cstmfldssrch_request' );
				}
				$the_query = new WP_Query( $query_args );
				/* The Loop */
				if ( $the_query->have_posts() ) { 
					add_filter( 'excerpt_length', 'pplrpsts_popular_posts_excerpt_length' );
					add_filter( 'excerpt_more', 'pplrpsts_popular_posts_excerpt_more' );
					while ( $the_query->have_posts() ) { 
						$the_query->the_post(); ?>
						<article class="post type-post format-standard">
							<header class="entry-header">
								<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
								<div class="entry-meta">
									<?php _e( 'Posted on', 'popular_posts' ) ?>
									<a href="<?php the_permalink(); ?>" title="<?php the_time('g:i a'); ?>"><span class="entry-date"><?php the_time( 'd F, Y' ); ?></span></a> 
									 <?php _e( 'by', 'popular_posts' ) ?> <span class="author vcard">
										<a class="url fn n" rel="author" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
											<?php echo get_the_author(); ?>
										</a>
									</span>
								</div><!-- .entry-meta -->
							</header>
							<div class="entry-content">
								<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
									<?php if ( '' == get_the_post_thumbnail() ) { ?>
										<img width="60" height="60" class="attachment-popular-post-featured-image wp-post-image" src="<?php echo $no_preview_img; ?>" />
									<?php } else {
										$check_size = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'popular-post-featured-image' );
										if ( true === $check_size[3] )
											echo get_the_post_thumbnail( $post->ID, 'popular-post-featured-image' ); 
										else
											echo get_the_post_thumbnail( $post->ID, array( 60, 60 ) ); 
									} ?>
								</a>
								<?php the_excerpt(); ?>
							</div><!-- .entry-content -->
						</article><!-- .post -->
					<?php }
					remove_filter( 'excerpt_length', 'pplrpsts_popular_posts_excerpt_length' );
					remove_filter( 'excerpt_more', 'pplrpsts_popular_posts_excerpt_more' );
				} else {
					/* no posts found */
				}
				/* Restore original Post Data */
				wp_reset_postdata(); 
				if ( isset( $cstmfldssrch_is_active ) ) {
					add_filter( 'posts_join', 'cstmfldssrch_join' );
					add_filter( 'posts_where', 'cstmfldssrch_request' );
				} ?>
			</div><!-- .pplrpsts-popular-posts -->
			<?php echo $args['after_widget'];
		}
		
		/* Outputs the options form on admin */
		function form( $instance ) {
			global $pplrpsts_excerpt_length, $pplrpsts_excerpt_more, $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$widget_title	= isset( $instance['widget_title'] ) ? $instance['widget_title'] : $pplrpsts_options['widget_title']; 
			$count			= isset( $instance['count'] ) ? $instance['count'] : $pplrpsts_options['count'];
			$excerpt_length = $pplrpsts_excerpt_length = isset( $instance['excerpt_length'] ) ? $instance['excerpt_length'] : $pplrpsts_options['excerpt_length'];
			$excerpt_more 	= $pplrpsts_excerpt_more = isset( $instance['excerpt_more'] ) ? $instance['excerpt_more'] : $pplrpsts_options['excerpt_more'];
			$no_preview_img = isset( $instance['no_preview_img'] ) ? $instance['no_preview_img'] : $pplrpsts_options['no_preview_img'];
			$order_by		= isset( $instance['order_by'] ) ? $instance['order_by'] : $pplrpsts_options['order_by']; ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_title' ); ?>"><?php _e( 'Widget title', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" name="<?php echo $this->get_field_name( 'widget_title' ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of posts', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo esc_attr( $count ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( 'Excerpt length', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="text" value="<?php echo esc_attr( $excerpt_length ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'excerpt_more' ); ?>"><?php _e( '"Read more" text', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_more' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_more' ); ?>" type="text" value="<?php echo esc_attr( $excerpt_more ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'no_preview_img' ); ?>"><?php _e( 'Default image (full URL), if no featured image is available', 'popular_posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'no_preview_img' ); ?>" name="<?php echo $this->get_field_name( 'no_preview_img' ); ?>" type="text" value="<?php echo esc_attr( $no_preview_img ); ?>"/>
			</p>
			<p>
				<?php _e( 'Order by number of', 'popular_posts' ); ?>:<br />
				<label><input name="<?php echo $this->get_field_name( 'order_by' ); ?>" type="radio" value="comment_count" <?php if( 'comment_count' == esc_attr( $order_by ) ) echo 'checked="checked"'; ?> /> <?php _e( 'comments', 'popular_posts' ); ?></label><br />
				<label><input name="<?php echo $this->get_field_name( 'order_by' ); ?>" type="radio" value="views_count" <?php if( 'views_count' == esc_attr( $order_by ) ) echo 'checked="checked"'; ?> /> <?php _e( 'views', 'popular_posts' ); ?></label>
			</p>
		<?php }
		
		/* Processing widget options on save */
		function update( $new_instance, $old_instance ) {
			global $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$instance = array();
			$instance['widget_title']	= ( isset( $new_instance['widget_title'] ) ) ? stripslashes( esc_html( $new_instance['widget_title'] ) ) : $pplrpsts_options['widget_title'];
			$instance['count']			= ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : $pplrpsts_options['count'];
			$instance['excerpt_length'] = ( ! empty( $new_instance['excerpt_length'] ) ) ? stripslashes( esc_html( $new_instance['excerpt_length'] ) ) : $pplrpsts_options['excerpt_length'];
			$instance['excerpt_more']   = ( ! empty( $new_instance['excerpt_more'] ) ) ? stripslashes( esc_html( $new_instance['excerpt_more'] ) ) : $pplrpsts_options['excerpt_more'];
			if ( ! empty( $new_instance['no_preview_img'] ) && pplrpsts_is_200( $new_instance['no_preview_img'] ) && getimagesize( $new_instance['no_preview_img'] ) )
				$instance['no_preview_img'] = $new_instance['no_preview_img'];
			else
				$instance['no_preview_img'] = $pplrpsts_options['no_preview_img'];
			$instance['order_by'] 		= ( ! empty( $new_instance['order_by'] ) ) ? $new_instance['order_by'] : $pplrpsts_options['order_by'];
			return $instance;
		}
	}
}

/* Filter the number of words in an excerpt */
if ( ! function_exists ( 'pplrpsts_popular_posts_excerpt_length' ) ) {
	function pplrpsts_popular_posts_excerpt_length( $length ) {
		global $pplrpsts_excerpt_length;
		return $pplrpsts_excerpt_length;
	}
}

/* Filter the string in the "more" link displayed after a trimmed excerpt */
if ( ! function_exists ( 'pplrpsts_popular_posts_excerpt_more' ) ) {
	function pplrpsts_popular_posts_excerpt_more( $more ) {
		global $pplrpsts_excerpt_more;
		return $pplrpsts_excerpt_more;
	}
}

/* Proper way to enqueue scripts and styles */
if ( ! function_exists ( 'pplrpsts_admin_enqueue_scripts' ) ) {
	function pplrpsts_admin_enqueue_scripts() {
		if ( isset( $_REQUEST['page'] ) && ( 'popular-posts.php' == $_REQUEST['page'] ) ) {
			wp_enqueue_script( 'pplrpsts_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );
		}
	}
}

/* Proper way to enqueue scripts and styles */
if ( ! function_exists ( 'pplrpsts_wp_head' ) ) {
	function pplrpsts_wp_head() {
		wp_enqueue_style( 'pplrpsts_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
	}
}

/* Function to handle action links */
if ( ! function_exists( 'pplrpsts_plugin_action_links' ) ) {
	function pplrpsts_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename(__FILE__);

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=popular-posts.php">' . __( 'Settings', 'popular_posts' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/* Add costom links for plugin in the Plugins list table */
if ( ! function_exists ( 'pplrpsts_register_plugin_links' ) ) {
	function pplrpsts_register_plugin_links( $links, $file ) {
		$base = plugin_basename(__FILE__);
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[] = '<a href="admin.php?page=popular-posts.php">' . __( 'Settings','popular_posts' ) . '</a>';
			$links[] = '<a href="http://wordpress.org/plugins/bws-popular-posts/faq/" target="_blank">' . __( 'FAQ','popular_posts' ) . '</a>';
			$links[] = '<a href="http://support.bestwebsoft.com">' . __( 'Support','popular_posts' ) . '</a>';
		}
		return $links;
	}
}

/* Register a widget */
if ( ! function_exists ( 'pplrpsts_register_widgets' ) ) {
	function pplrpsts_register_widgets() {
		register_widget( 'PopularPosts' );
	}
}

/* Function for to gather information about viewing posts */
if ( ! function_exists ( 'pplrpsts_set_post_views' ) ) {
	function pplrpsts_set_post_views( $pplrpsts_post_ID ) {
		global $post;

		if ( empty( $pplrpsts_post_ID ) && ! empty( $post ) ) {
			$pplrpsts_post_ID = $post->ID;
		}
		
		/* Check post type */
		if ( @get_post_type( $pplrpsts_post_ID ) != 'post' )
			return;

		$pplrpsts_count = get_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', true );
		if ( $pplrpsts_count == '' ) {
			delete_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count' );
			add_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', '1' );
		} else {
			$pplrpsts_count++;
			update_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', $pplrpsts_count );
		}
	}
}

/* Check if image status = 200 */
if ( ! function_exists ( 'pplrpsts_is_200' ) ) {
	function pplrpsts_is_200( $url ) {
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE )
			return false;

		$options['http'] = array(
				'method' => "HEAD",
				'ignore_errors' => 1,
				'max_redirects' => 0
		);
		$body = file_get_contents( $url, NULL, stream_context_create( $options ) );
		sscanf( $http_response_header[0], 'HTTP/%*d.%*d %d', $code );
		return $code === 200;
	}
}

/**
 * Delete plugin options
 */
if ( ! function_exists( 'pplrpsts_plugin_uninstall' ) ) {
	function pplrpsts_plugin_uninstall() {
		delete_option( 'pplrpsts_options' );
	}
}

/* Add option page in admin menu */
add_action( 'admin_menu', 'pplrpsts_admin_menu' );

/* Plugin initialization */
add_action( 'init', 'pplrpsts_init' );
/* Register a widget */
add_action( 'widgets_init', 'pplrpsts_register_widgets' );
/* Plugin initialization for admin page */
add_action( 'admin_init', 'pplrpsts_admin_init' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'pplrpsts_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'pplrpsts_register_plugin_links', 10, 2 );

add_action( 'admin_enqueue_scripts', 'pplrpsts_admin_enqueue_scripts' );
add_action( 'wp_enqueue_scripts', 'pplrpsts_wp_head' );

/* Function for to gather information about viewing posts */
add_action( 'wp_head', 'pplrpsts_set_post_views' );

register_uninstall_hook( __FILE__, 'pplrpsts_plugin_uninstall' );