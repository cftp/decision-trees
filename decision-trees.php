<?php 
/*
Plugin Name: Decision Trees
Plugin URI: https://github.com/cftp/decision-trees
Description: Provides a custom post type to create decision trees in WordPress
Version: 1.0
Author: Code for the People
Author URI: http://www.codeforthepeople.com/ 
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
	
	/**
	 * Singleton stuff.
	 * 
	 * @access @static
	 * 
	 * @return void
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance ) {
			load_plugin_textdomain( 'cftp_dt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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

		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ), 10, 2 );

		$this->version = 1;

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
	function action_init() {
		$args = array(
			'labels' => array(
				'label' => __( 'Decision Tree', 'cftp_dt' ),
				'name_admin_bar' => _x( 'Decision Tree', 'add new on admin bar', 'cftp_dt' ),
				'name' => __( 'Decision Trees', 'cftp_dt' ),
				'singular_name' => __( 'Decision Tree', 'cftp_dt' ),
				'add_new' => __( 'Add New', 'cftp_dt' ),
				'add_new_item' => __( 'Add New Decision Tree', 'cftp_dt' ),
				'edit_item' => __( 'Edit Decision Tree', 'cftp_dt' ),
				'new_item' => __( 'New Decision Tree', 'cftp_dt' ),
				'view_item' => __( 'View Decision Tree', 'cftp_dt' ),
				'search_items' => __( 'Search Decision Trees', 'cftp_dt' ),
				'not_found' => __( 'No Decision Trees found.', 'cftp_dt' ),
				'not_found_in_trash' => __( 'No Decision Trees found in Trash.', 'cftp_dt' ),
				'parent_item_colon' => 'Parent Decision Tree:',
				'all_items' => __( 'All Decision Trees', 'cftp_dt' ),
				'menu_name' => __( 'Decision Trees', 'cftp_dt' ),
				'label' => __( 'Decision Tree', 'cftp_dt' ),
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
			'supports' => array( 'title', 'editor' ),
		);
		$args = apply_filters( 'cftp_dt_cpt_args', $args );
		$cpt = register_post_type( 'decision_tree', $args );
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
		if ( 'decision_tree' != $post_type )
			return;
		add_meta_box( 'cftp_dt_answers', __( 'Answers', 'cftp_dt' ), array( $this, 'callback_answers_meta_box' ), 'decision_tree', 'advanced', 'default' );
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
		$vars = array();
		$vars[ 'answer_providers' ] = apply_filters( 'cftp_dt_answer_providers', array(), $post->ID );
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
		if ( $version < 1 ) {
			// Nothing
			error_log( "CFTP DT: Installed" );
		}

		// N.B. Remember to increment $this->version in self::__construct above when you add a new IF

		delete_option( "{$option_name}_running", true, null, 'no' );
		update_option( $option_name, $this->version );
		error_log( "CFTP DT: Done upgrade, now at version " . $this->version );
	}
}

// Initiate the singleton
CFTP_Decision_Trees::init();
