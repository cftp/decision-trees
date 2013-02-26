<?php

// Exit if accessed directly
defined( 'ABSPATH' ) or die();

/**
 * A simple base plugin class. Very much still under development.
 **/
class CFTP_DT_Plugin {

	/**
	 * Class constructor
	 *
	 * @author John Blackbourn
	 **/
	function __construct( $file ) {
		$this->file = $file;

		add_action( 'admin_notices', array( $this, '_action_admin_notices' ) );
	}

	// HOOKS
	// =====

	/**
	 * Hooks the WP admin_notices action to render any notices
	 * that have been set with the set_admin_notice method.
	 *
	 * @action admin_notices
	 * 
	 * @return void
	 * @author Simon Wheatley
	 **/
	function _action_admin_notices() {
		$user_id = get_current_user_id();
		if ( ! $errors = get_user_meta( $user_id, 'cftp_dt_admin_errors', true ) )
			$errors =  array();
		if ( ! $notices = get_user_meta( $user_id, 'cftp_dt_admin_notices', true ) )
			$notices = array();

		if ( $errors )
			foreach ( $errors as $error )
				$this->render_admin_error( $error );

		if ( $notices )
			foreach ( $notices as $notice )
				$this->render_admin_notice( $notice );

		delete_user_meta( $user_id, 'cftp_dt_admin_errors' );
		delete_user_meta( $user_id, 'cftp_dt_admin_notices' );
	}

	// METHODS
	// =======


	/**
	 * Renders a template
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function render( $template_file, $vars = null, $is_admin_tpl = false ) {
		// Maybe override the template with our own file
		$template_file = $this->locate_template( $template_file, $is_admin_tpl );
		// Ensure we have the same vars as regular WP templates
		global $posts, $post, $wp_did_header, $wp_did_template_redirect, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

		if ( is_array($wp_query->query_vars) )
			extract($wp_query->query_vars, EXTR_SKIP);

		// Plus our specific template vars
		if ( is_array( $vars ) )
			extract( $vars );
		
		require( $template_file );
	}

	/**
	 * Renders an admin template from this plugin's /templates-admin/ directory.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function render_admin( $template_file, $vars = null ) {
		$this->render( $template_file, $vars, true );
	}
	
	/**
	 * Returns a section of user display code, returning the rendered markup.
	 *
	 * @param string $ug_name Name of the admin file (without extension)
	 * @param string $array Array of variable name=>value that is available to the display code (optional)
	 * @return void
	 * @author © John Godley
	 **/
	function capture( $template_file, $vars = null, $is_admin_tpl = false ) {
		ob_start();
		$this->render( $template_file, $vars, $is_admin_tpl );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	/**
	 * Returns a section of user display code, returning the rendered markup.
	 *
	 * @param string $ug_name Name of the admin file (without extension)
	 * @param string $array Array of variable name=>value that is available to the display code (optional)
	 * @return void
	 * @author © John Godley
	 **/
	function capture_admin( $template_file, $vars = null ) {
		return $this->capture( $template_file, $vars, true );
	}

	/**
	 * Takes a filename and attempts to find that in the designated plugin templates
	 *
	 * @param string $template_file A template filename to search for 
	 * @return string The path to the template file to use
	 * @author Simon Wheatley
	 **/
	function locate_template( $template_file, $is_admin_tpl = false ) {
		$file_path = $this->plugin_path( "templates/{$template_file}" );
		if ( $is_admin_tpl )
			$file_path = $this->plugin_path( "templates-admin/{$template_file}" );

		$file_path = apply_filters( 'cftp_dt_tpl_filepath', $file_path, $template_file, $is_admin_tpl );

		if ( file_exists( $file_path ) )
			return $file_path;

		// Oh dear. We can't find the template.
		$msg = sprintf( __( "This plugin template could not be found, perhaps you need to hook `sil_plugins_dir` and `sil_plugins_url`: %s" ), $file_path );
		error_log( "Template error: $msg" );
		echo "<p style='background-color: #ffa; border: 1px solid red; color: #300; padding: 10px;'>$msg</p>";
		return false;
	}

	/**
	 * Returns the URL for for a file/dir within this plugin.
	 *
	 * @param $path string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string URL
	 * @author John Blackbourn
	 **/
	function plugin_url( $file = '' ) {
		return $this->plugin( 'url', $file );
	}

	/**
	 * Returns the filesystem path for a file/dir within this plugin.
	 *
	 * @param $path string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Filesystem path
	 * @author John Blackbourn
	 **/
	function plugin_path( $file = '' ) {
		return $this->plugin( 'path', $file );
	}

	/**
	 * Returns a version number for the given plugin file.
	 *
	 * @param $path string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Version
	 * @author John Blackbourn
	 **/
	function plugin_ver( $file ) {
		return filemtime( $this->plugin_path( $file ) );
	}

	/**
	 * Returns the current plugin's basename, eg. 'my_plugin/my_plugin.php'.
	 *
	 * @return string Basename
	 * @author John Blackbourn
	 **/
	function plugin_base() {
		return $this->plugin( 'base' );
	}

	/**
	 * Populates and returns the current plugin info.
	 *
	 * @author John Blackbourn
	 **/
	function plugin( $item, $file = '' ) {
		if ( !isset( $this->plugin ) ) {
			$this->plugin = array(
				'url'  => plugin_dir_url( $this->file ),
				'path' => plugin_dir_path( $this->file ),
				'base' => plugin_basename( $this->file )
			);
		}
		return $this->plugin[$item] . ltrim( $file, '/' );
	}
	
	/**
	 * Echoes some HTML for an admin notice.
	 *
	 * @param string $notice The notice 
	 * @return void
	 * @author Simon Wheatley
	 **/
	function render_admin_notice( $notice ) {
		echo "<div class='updated'><p>$notice</p></div>";
	}
	
	/**
	 * Echoes some HTML for an admin error.
	 *
	 * @param string $error The error 
	 * @return void
	 * @author Simon Wheatley
	 **/
	function render_admin_error( $error ) {
		echo "<div class='error'><p>$error</p></div>";
	}
	
	/**
	 * Sets a string as an admin notice.
	 *
	 * @param string $msg A *localised* admin notice message 
	 * @return void
	 * @author Simon Wheatley
	 **/
	function set_admin_notice( $msg ) {
		$user_id = get_current_user_id();
		if ( ! $notices = get_user_meta( $user_id, 'cftp_dt_admin_notices', true ) )
			$notices = array();
		$notices[] = $msg;
		update_user_meta( $user_id, 'cftp_dt_admin_notices', $notices );
	}
	
	/**
	 * Sets a string as an admin error.
	 *
	 * @param string $msg A *localised* admin error message 
	 * @return void
	 * @author Simon Wheatley
	 **/
	function set_admin_error( $msg ) {
		$user_id = get_current_user_id();
		if ( ! $errors = get_user_meta( $user_id, 'cftp_dt_admin_errors', true ) )
			$errors =  array();
		// @TODO: Set hash of message as index, to prevent dupes
		$errors[] = $msg;
		update_user_meta( $user_id, 'cftp_dt_admin_errors', $errors );
	}

}

