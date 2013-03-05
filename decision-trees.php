<?php 
/*
Plugin Name: Decision Trees
Plugin URI: https://github.com/cftp/decision-trees
Description: Provides a custom post type to create decision trees in WordPress
Version: 1.1
Author: Code for the People
Author URI: http://www.codeforthepeople.com/ 
Text Domain: cftp_dt
Domain Path: /languages/
*/

/*  Copyright 2013 Code for the People Ltd

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

require_once( dirname( __FILE__ ) . '/class-plugin.php' );
require_once( dirname( __FILE__ ) . '/class-answers-simple.php' );

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
	
	public $post_type = 'decision_tree';

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

		# Filters
		add_filter( 'the_content',           array( $this, 'filter_the_content' ) );
		add_filter( 'the_title',             array( $this, 'filter_the_title' ), 0, 2 );

		$this->version = 2;

		parent::__construct( __FILE__ );
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
				'label' => __( 'Decision Node', 'cftp_dt' ),
				'name_admin_bar' => _x( 'Decision Tree', 'add new on admin bar', 'cftp_dt' ),
				'name' => __( 'Decision Trees', 'cftp_dt' ),
				'singular_name' => __( 'Decision Node', 'cftp_dt' ),
				'add_new' => __( 'Add New', 'cftp_dt' ),
				'add_new_item' => __( 'Add New Decision Node', 'cftp_dt' ),
				'edit_item' => __( 'Edit Decision Node', 'cftp_dt' ),
				'new_item' => __( 'New Decision Node', 'cftp_dt' ),
				'view_item' => __( 'View Decision Node', 'cftp_dt' ),
				'search_items' => __( 'Search Decision Nodes', 'cftp_dt' ),
				'not_found' => __( 'No Decision Nodes found.', 'cftp_dt' ),
				'not_found_in_trash' => __( 'No Decision Trees found in Trash.', 'cftp_dt' ),
				'parent_item_colon' => 'Parent Decision Node:',
				'all_items' => __( 'All Decision Trees', 'cftp_dt' ),
				'menu_name' => __( 'Decision Trees', 'cftp_dt' ),
				'label' => __( 'Decision Node', 'cftp_dt' ),
			),
			'public' => true,
			'publicly_queryable' => true,
			'capability_type' => 'page', // @TODO: Set this to `decision_tree` and map meta caps
			// 'map_meta_cap' => true,
			'menu_position' => 20,
			'hierarchical' => true,
			'rewrite' => true,
			'query_var' => 'help',
			'delete_with_user' => false,
			'supports' => array( 'title', 'editor', 'page-attributes' ),
		);
		$args = apply_filters( 'cftp_dt_cpt_args', $args );
		$cpt = register_post_type( $this->post_type, $args );
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
			$this->plugin_url( 'admin.css' ),
			array( 'wp-admin' ),
			$this->plugin_ver( 'admin.css' )
		);
		wp_enqueue_script(
			'cftp-dt-admin',
			$this->plugin_url( 'admin.js' ),
			array( 'jquery' ),
			$this->plugin_ver( 'admin.js' )
		);


	}

	function filter_the_content( $content ) {

		global $post;

		if ( $this->post_type != $post->post_type )
			return $content;

		$answers = cftp_dt_get_post_answers( $post->ID );

		$vars = array();
		remove_filter( 'the_title', array( $this, 'filter_the_title' ), 0, 2 );
		$vars[ 'title' ] = get_the_title( $post->ID );
		add_filter( 'the_title', array( $this, 'filter_the_title' ), 0, 2 );
		$vars[ 'content' ] = $content;
		$vars[ 'answers' ] = $answers;

		return $this->capture( 'content.php', $vars );
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
		$this->init_answer_providers_for_post( $post->ID );

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

		// N.B. Remember to increment $this->version in self::__construct above when you add a new IF

		delete_option( "{$option_name}_running", true, null, 'no' );
		update_option( $option_name, $this->version );
		error_log( "CFTP DT: Done upgrade, now at version " . $this->version );
	}

	function init_answer_providers_for_post( $post ) {
		$post = get_post( $post );
		if ( ! isset( $this->answer_providers[ $post->ID ] ) )
			$this->answer_providers[ $post->ID ] = apply_filters( 'cftp_dt_answer_providers', array(), $post->ID );
	}

	function get_answer_provider_for_post( $type, $post ) {
		$post = get_post( $post );
		$this->init_answer_providers_for_post( $post->ID );
		
		if ( isset( $this->answer_providers[ $post->ID ][$type] ) )
			return $this->answer_providers[ $post->ID ][$type];
		else
			return false;
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
