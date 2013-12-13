<?php 
/*
Plugin Name: Decision Trees
Plugin URI:  https://github.com/cftp/decision-trees
Description: Provides a custom post type to create decision trees in WordPress
Version:     1.4
Author:      Code for the People
Author URI:  http://codeforthepeople.com/ 
Text Domain: cftp_dt
Domain Path: /languages/
*/

/*  Copyright 2013 Code for the People Ltd

                _____________
               /      ____   \
         _____/       \   \   \
        /\    \        \___\   \
       /  \    \                \
      /   /    /          _______\
     /   /    /          \       /
    /   /    /            \     /
    \   \    \ _____    ___\   /
     \   \    /\    \  /       \
      \   \  /  \____\/    _____\
       \   \/        /    /    / \
        \           /____/    /___\
         \                        /
          \______________________/


    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

// Exit if accessed directly
defined( 'ABSPATH' ) or die();

require_once dirname( __FILE__ ) . '/class-plugin.php';
require_once dirname( __FILE__ ) . '/class-answers-simple.php';

/**
 * Decision Trees
 *
 * @package Decision-Trees
 * @subpackage Main
 */
class CFTP_Decision_Trees extends CFTP_DT_Plugin {
	
	/**
	 * A version for cache busting, DB updates, etc.
	 *
	 * @var string
	 **/
	public $version;
	
	public $post_type = 'decision_node';

	public $no_recursion = false;

	/**
	 * Singleton stuff.
	 * 
	 * @access @static
	 * 
	 * @return CFTP_Decision_Trees
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new CFTP_Decision_Trees;
		}

		return $instance;

	}
	
	/**
	 * Let's go!
	 *
	 * @access public
	 * 
	 * @return void
	 **/
	public function __construct() {

		# Actions
		add_action( 'admin_init',            array( $this, 'action_admin_init' ) );
		add_action( 'init',                  array( $this, 'action_init' ) );
		add_action( 'add_meta_boxes',        array( $this, 'action_add_meta_boxes' ), 10, 2 );
		add_action( 'save_post',             array( $this, 'action_save_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
		add_action( 'admin_menu',            array( $this, 'action_admin_menu' ) );
		add_action( 'admin_notices',         array( $this, 'action_admin_notices' ) );

		# Filters
		add_filter( 'the_content',           array( $this, 'filter_the_content' ) );
		add_filter( 'the_title',             array( $this, 'filter_the_title' ), 0, 2 );

		$this->version = 3;

		parent::__construct( __FILE__ );
	}

	function action_admin_notices() {
		if ( ( get_current_screen()->post_type == $this->post_type ) and isset( $_GET['answer_added'] ) ) {
			?>
			<div class="updated" id="cftp_dt_answer_added">
				<p><?php _e( 'Answer added.', 'cftp_dt' ); ?></p>
			</div>
			<?php
		}
	}

	// HOOKS
	// =====
	
	/**
	 * undocumented function
	 *
	 * @action init
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function action_admin_init() {
		$this->maybe_update();

		if ( isset( $_POST['action'] ) and ( 'cftp_dt_add_answer' == $_POST['action'] ) )
			$this->process_add_answer();

	}
	
	function process_add_answer() {

		check_admin_referer( 'cftp_dt_add_answer' );

		$post = get_post( $post_id = absint( $_POST['post_id'] ) );

		$answer_page_ids = get_post_meta( $post_id, '_cftp_dt_answers', true );

		if ( empty( $answer_page_ids ) )
			$answer_page_ids = array();

		# @TODO D.R.Y. This (and the code in action_save_meta) should be abstracted:

		foreach ( $_POST['cftp_dt_new'] as $answer_type => $answer ) {

			if ( !isset( $answer['page_title'] ) or empty( $answer['page_title'] ) ) {
				if ( isset( $answer['text'] ) and !empty( $answer['text'] ) )
					$answer['page_title'] = $answer['text'];
				else
					continue;
			}

			$answer_meta = array();

			$title = trim( $answer['page_title'] );
			$page  = get_page_by_title( $title, OBJECT, $this->post_type );

			if ( !$page ) {
				$this->no_recursion = true;
				$page_id = wp_insert_post( array(
					'post_title'  => $title,
					'post_type'   => $this->post_type,
					'post_status' => 'draft',
					'post_parent' => $post->ID,
				) );
				wp_update_post( array( 'ID' => $page_id, 'post_name' => sanitize_title_with_dashes( $answer['text'] ) ) );
				$page = get_post( $page_id );
				$this->no_recursion = false;
			}

			$answer_meta['_cftp_dt_answer_value'] = $answer['text'];
			$answer_meta['_cftp_dt_answer_type']  = $answer_type;
			$answer_page_ids[] = $page->ID;

			foreach ( $answer_meta as $k => $v )
				update_post_meta( $page->ID, $k, $v );

		}

		update_post_meta( $post->ID, '_cftp_dt_answers', $answer_page_ids );

		$redirect = add_query_arg( array(
			'post_type'    => $this->post_type,
			'page'         => 'cftp_dt_visualise',
			'answer_added' => 'true'
		), admin_url( 'edit.php' ) );

		wp_redirect( $redirect );
		die();

	}

	/**
	 * undocumented function
	 *
	 * @action init
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function action_init() {

		load_plugin_textdomain( 'cftp_dt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		$args = array(
			'labels' => array(
				'label'              => __( 'Decision Node', 'cftp_dt' ),
				'name_admin_bar'     => _x( 'Decision Node', 'add new on admin bar', 'cftp_dt' ),
				'name'               => __( 'Decision Trees', 'cftp_dt' ),
				'singular_name'      => __( 'Decision Node', 'cftp_dt' ),
				'add_new'            => __( 'Add New Node', 'cftp_dt' ),
				'add_new_item'       => __( 'Add New Decision Node', 'cftp_dt' ),
				'edit_item'          => __( 'Edit Decision Node', 'cftp_dt' ),
				'new_item'           => __( 'New Decision Node', 'cftp_dt' ),
				'view_item'          => __( 'View Decision Node', 'cftp_dt' ),
				'search_items'       => __( 'Search Decision Nodes', 'cftp_dt' ),
				'not_found'          => __( 'No nodes found.', 'cftp_dt' ),
				'not_found_in_trash' => __( 'No nodes found in Trash.', 'cftp_dt' ),
				'parent_item_colon'  => __( 'Parent Decision Node:', 'cftp_dt' ),
				'all_items'          => __( 'All Decision Nodes', 'cftp_dt' ),
				'menu_name'          => __( 'Decision Trees', 'cftp_dt' ),
				'label'              => __( 'Decision Node', 'cftp_dt' ),
			),
			'public'             => true,
			'publicly_queryable' => true,
			'capability_type'    => 'page', // @TODO: Set this to `$this->post_type` and map meta caps
		//	'map_meta_cap'       => true,
			'menu_position'      => 20,
			'hierarchical'       => true,
			'rewrite'            => array(
				'with_front' => false,
				'slug'       => 'decision-tree'
			),
			'query_var'          => 'help', // @TODO: is this the best qv name?
			'delete_with_user'   => false,
			'supports'           => array( 'title', 'editor', 'page-attributes' ),
		);
		$args = apply_filters( 'cftp_dt_cpt_args', $args );
		$cpt = register_post_type( $this->post_type, $args );
	}

	function action_admin_menu() {

		$pto = get_post_type_object( $this->post_type );

		add_submenu_page(
			'edit.php?post_type=decision_node',
			__( 'Visualise Nodes', 'cftp_dt' ),
			__( 'Visualise Nodes', 'cftp_dt' ),
			$pto->cap->edit_posts,
			'cftp_dt_visualise',
			array( $this, 'admin_page_visualise' )
		);

	}

	function admin_page_visualise() {

		# @TODO D.R.Y.:
		$post_status = get_post_stati();
		unset(
			$post_status['trash'],
			$post_status['auto-draft'],
			$post_status['inherit']
		);

		$tree = array();
		$tree[0] = get_pages( array(
			'post_type'   => $this->post_type,
			'post_status' => $post_status,
			'sort_column' => 'menu_order,post_title',
			'parent'      => 0,
		) );

		$tree = $this->populate_tree( $tree );

		$max = 0;
		foreach ( $tree as $nodes )
			$max = max( $max, count( $nodes ) );

		$vars['tree'] = $tree;
		$vars['max']  = $max;

		$this->render_admin( 'visualise.php', $vars );

	}

	function populate_tree( $tree, $level = 0 ) {

		# @TODO D.R.Y.:
		$post_status = get_post_stati();
		unset(
			$post_status['trash'],
			$post_status['auto-draft'],
			$post_status['inherit']
		);

		foreach ( $tree[$level] as $page ) {

			$children = get_pages( array(
				'post_type'   => $this->post_type,
				'post_status' => $post_status,
				'sort_column' => 'menu_order,post_title',
				'parent'      => $page->ID,
				'child_of'    => $page->ID, # This is required when using the 'parent' arg and is a WP bug. @TODO: file it
			) );

			$page->level = $level;

			if ( !empty( $children ) ) {

				if ( !isset( $tree[$level+1] ) )
					$tree[$level+1] = array();
				$tree[$level+1] = array_merge( $tree[$level+1], $children );
				$tree = $this->populate_tree( $tree, $level+1 );

			}

		}

		return $tree;

	}

	function action_save_post( $post_id, $post ) {

		if ( $this->no_recursion )
			return;
		if ( wp_is_post_revision( $post_id ) )
			return;
		if ( wp_is_post_autosave( $post_id ) )
			return;
		if ( $this->post_type != $post->post_type )
			return;

		if ( isset( $_POST["cftp_dt_post_{$post_id}_parent"] ) ) {

			# See: http://core.trac.wordpress.org/ticket/8592
			# A page with a non-published parent will get its parent removed
			# when you save the post because it won't be listed in the post parent
			# dropdown. We'll fix that manually.

			$this->no_recursion = true;
			wp_update_post( array(
				'ID'          => $post->ID,
				'post_parent' => absint( $_POST["cftp_dt_post_{$post_id}_parent"] ),
			) );
			$this->no_recursion = false;

		}

		if ( !isset( $_POST['cftp_dt_add'] ) and !isset( $_POST['cftp_dt_edit'] ) )
			return;

		$answer_page_ids = array();

		if ( isset( $_POST['cftp_dt_edit'] ) ) {

			foreach ( array_values( $_POST['cftp_dt_edit'] ) as $id => $answers ) {
				foreach ( $answers as $answer_type => $answer ) {

					$answer_meta = array();

					$page = get_post( $answer['page_id'] );

					$answer_meta['_cftp_dt_answer_value'] = $answer['text'];
					$answer_page_ids[] = $page->ID;

					foreach ( $answer_meta as $k => $v )
						update_post_meta( $page->ID, $k, $v );

				}
			}

		}

		if ( isset( $_POST['cftp_dt_add'] ) ) {

			foreach ( array_values( $_POST['cftp_dt_add'] ) as $id => $answers ) {
				foreach ( $answers as $answer_type => $answer ) {

					if ( !isset( $answer['page_title'] ) or empty( $answer['page_title'] ) ) {
						if ( isset( $answer['text'] ) and !empty( $answer['text'] ) )
							$answer['page_title'] = $answer['text'];
						else
							continue;
					}

					$answer_meta = array();

					$title = trim( $answer['page_title'] );
					$page  = get_page_by_title( $title, OBJECT, $this->post_type );

					if ( !$page ) {
						$this->no_recursion = true;
						$page_id = wp_insert_post( array(
							'post_title'  => $title,
							'post_type'   => $this->post_type,
							'post_status' => 'draft',
							'post_parent' => $post->ID,
						) );
						wp_update_post( array( 'ID' => $page_id, 'post_name' => sanitize_title_with_dashes( $answer['text'] ) ) );
						$page = get_post( $page_id );
						$this->no_recursion = false;
					}

					$answer_meta['_cftp_dt_answer_value'] = $answer['text'];
					$answer_meta['_cftp_dt_answer_type']  = $answer_type;
					$answer_page_ids[] = $page->ID;

					foreach ( $answer_meta as $k => $v )
						update_post_meta( $page->ID, $k, $v );

				}
			}

		}

		update_post_meta( $post->ID, '_cftp_dt_answers', $answer_page_ids );

	}

	function action_admin_enqueue_scripts() {

		if ( $this->post_type != get_current_screen()->post_type )
			return;

		wp_enqueue_style(
			'cftp-dt-admin',
			$this->plugin_url( 'css/admin.css' ),
			array( 'wp-admin', 'thickbox' ),
			$this->plugin_ver( 'css/admin.css' )
		);

		wp_register_script(
			'jquery.jsPlumb',
			$this->plugin_url( 'js/jquery.jsPlumb-1.3.16-all-min.js' ),
			array( 'jquery'/*, 'jquery-ui'*/ ), /* jQuery UI is only needed if we add drag-and-drop */
			'1.3.16'
		);
		wp_enqueue_script(
			'cftp-dt-admin',
			$this->plugin_url( 'js/admin.js' ),
			array( 'jquery', 'jquery.jsPlumb', 'thickbox' ),
			$this->plugin_ver( 'js/admin.js' )
		);

	}

	function filter_the_content( $content ) {

		global $post;

		if ( $this->post_type != $post->post_type )
			return $content;

		$answers = cftp_dt_get_post_answers( $post->ID );

		$start = false;
		$previous_answers = array();
		if ( $post->post_parent ) {
			$previous_answers = array_reverse( get_post_ancestors( $post->ID ) );
			$previous_answers[] = get_the_ID();
			$start = array_shift( $previous_answers );
			array_shift( $previous_answers );
			$start = get_post( $start );
		}

		remove_filter( 'the_title', array( $this, 'filter_the_title' ), 0, 2 );

		$vars = array();
		$vars[ 'start' ] = $start;
		$vars[ 'previous_answers' ] = $previous_answers;
		$vars[ 'title' ] = get_the_title( $post->ID );
		$vars[ 'content' ] = $content;
		$vars[ 'answers' ] = $answers;
		$vars[ 'answer_links' ] = $this->capture( 'content-answer-links.php', $vars );

		add_filter( 'the_title', array( $this, 'filter_the_title' ), 0, 2 );

		if ( $post->post_parent )
			return $this->capture( 'content-with-history.php', $vars );
		else
			return $this->capture( 'content-no-history.php', $vars );
	}

	function filter_the_title( $title, $post ) {
		if ( is_admin() )
			return $title;

		$post = get_post( $post );
		if ( 'decision_tree' != $post->post_type )
			return $title;

		if ( ! $post->post_parent )
			return $title;

		$ancestors = get_post_ancestors( $post->ID );
		$oldest = get_post( array_pop( $ancestors ) );
		return $oldest->post_title;
	}

	/**
	 * Hooks the WP action add_meta_boxes
	 *
	 * @action add_meta_boxes
	 *
	 * @param $post_type The name of the post type
	 * @param $post The post object
	 * @return void
	 * @author Simon Wheatley
	 **/
	function action_add_meta_boxes( $post_type, $post ) {
		if ( $this->post_type != $post_type )
			return;
		add_meta_box( 'cftp_dt_answers', __( 'Answers', 'cftp_dt' ), array( $this, 'callback_answers_meta_box' ), $this->post_type, 'advanced', 'default' );
	}

	// CALLBACKS
	// =========

	/**
	 * Callback to provide the HTML for the answers metabox.
	 *
	 * @param $post A post object
	 * @param $box The parameters for this meta box
	 * @return void
	 * @author Simon Wheatley
	 **/
	function callback_answers_meta_box( $post, $box ) {
		$this->init_answer_providers();

		$vars = array();
		$vars[ 'answers' ] = cftp_dt_get_post_answers( $post->ID );
		$this->render_admin( 'meta-box-answers.php', $vars );
	}

	// METHODS
	// =======
	
	/**
	 * Checks the DB structure is up to date, rewrite rules, 
	 * theme image size options are set, etc.
	 *
	 * @return void
	 **/
	public function maybe_update() {
		global $wpdb;
		$option_name = 'cftp_dt_version';
		$version = absint( get_option( $option_name, 0 ) );
		
		// Debugging and dev:
		// delete_option( "{$option_name}_running", true, null, 'no' );

		if ( $version == $this->version )
			return;

		// Institute a lock, for long running operations
		if ( $start_time = get_option( "{$option_name}_running", false ) ) {
			$time_diff = time() - $start_time;
			// Check the lock is less than 30 mins old, and if it is, bail
			if ( $time_diff < ( 60 * 30 ) ) {
				error_log( "CFTP DT: Existing update routine has been running for less than 30 minutes" );
				return;
			}
			error_log( "CFTP DT: Update routine is running, but older than 30 minutes; going ahead regardless" );
		} else {
			add_option( "{$option_name}_running", time(), null, 'no' );
		}

		// Flush the rewrite rules
		if ( $version < 2 ) {
			flush_rewrite_rules();
			error_log( "CFTP DT: Flush rewrite rules" );
		}

		// Change existing posts to our new post type name
		if ( $version < 3 ) {
			$q = $wpdb->prepare( "
				UPDATE {$wpdb->posts}
				SET post_type = %s
				WHERE post_type = 'decision_tree'
			", $this->post_type );
			$wpdb->query( $q );
			error_log( "CFTP DT: Updated old post type names" );
		}

		// N.B. Remember to increment $this->version in self::__construct above when you add a new IF

		delete_option( "{$option_name}_running", true, null, 'no' );
		update_option( $option_name, $this->version );
		error_log( "CFTP DT: Done upgrade, now at version " . $this->version );
	}

	function init_answer_providers() {

		if ( !isset( $this->answer_providers ) )
			$this->answer_providers = apply_filters( 'cftp_dt_answer_providers', array() );

	}

	function get_answer_provider( $answer_type ) {

		$this->init_answer_providers();
		
		if ( isset( $this->answer_providers[$answer_type] ) )
			return $this->answer_providers[$answer_type];
		else
			return false;
	}

	function get_answer_providers() {

		$this->init_answer_providers();

		return $this->answer_providers;

	}

}

// Initiate the singleton
CFTP_Decision_Trees::init();

function cftp_dt_get_post_answers( $post_id = null ) {

	if ( ! $post = get_post( $post_id ) )
		return array();

	$answers = get_post_meta( $post->ID, '_cftp_dt_answers', true );

	if ( empty( $answers ) )
		$answers = array();

	foreach ( $answers as &$answer )
		$answer = new CFTP_DT_Answer( $answer );

	return $answers;

}

function cftp_dt_get_previous_answers( $post_id = null ) {

	if ( ! $post = get_post( $post_id ) )
		return array();

	if ( ! $post->post_parent )
		return array();

	// $ancestors = 
}


class CFTP_DT_Answer {

	function __construct( $post_id ) {
		$this->post = get_post( $post_id );
	}

	function get_post() {
		return $this->post;
	}

	function get_page_title() {
		return get_the_title( $this->post->ID );
	}

	function get_answer_value() {
		return get_post_meta( $this->post->ID, '_cftp_dt_answer_value', true );
	}

	function get_answer_type() {
		return get_post_meta( $this->post->ID, '_cftp_dt_answer_type', true );
	}

}

if(!function_exists('enqueue_scroll_js')) {
	add_action('init','enqueue_scroll_js');
	
	function enqueue_scroll_js() {
	    wp_enqueue_script( 'decision_tree_scroll', plugins_url( '/js/dt_scroll.js', __FILE__ ), array('jquery'), '0.9', true);
	};
};
