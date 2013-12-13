<?php 

// Exit if accessed directly
defined( 'ABSPATH' ) or die();

/**
 * Decision Trees simple answers class
 *
 * @package Decision-Trees
 * @subpackage Main
 */
class CFTP_DT_Answers_Simple {

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
			$class = get_called_class();
			$instance = new $class();
		}

		return $instance;
	}
	
	/**
	 * Hook into the CFTP_DT answer_providers filter
	 * 
	 * @filter cftp_dt_answer_providers
	 * 
	 * @access @static
	 * 
	 * @return void
	 */
	static public function filter_answer_providers( $answers ) {
		$answers['simple'] = self::init();
		return $answers;
	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return void
	 **/
	public function __construct() {
		// Nothing
	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return string
	 **/
	public function get_edit_form( $id, CFTP_DT_Answer $answer ) {

		return sprintf( '<input type="text" class="regular-text" name="cftp_dt_edit[%s][simple][text]" placeholder="%s" value="%s" />',
			$id,
			__( 'Answer link text', 'cftp_dt' ),
			esc_attr( $answer->get_answer_value() )
		);

	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return string
	 **/
	public function get_add_form() {
		return sprintf( '<input type="text" class="regular-text" name="cftp_dt_new[simple][text]" placeholder="%s" />',
			__( 'Answer link text', 'cftp_dt' )
		);
	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return string
	 **/
	public function get_answer( CFTP_DT_Answer $answer ) {
		return sprintf( '<a class="cftp_dt_answer_link" href="%1$s">%2$s</a>',
			get_permalink( $answer->get_post()->ID ),
			$answer->get_answer_value()
		);
	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return string
	 **/
	public function get_edit_answer_url( CFTP_DT_Answer $answer ) {
		return get_permalink( $answer->get_post()->post_parent );
	}

}

add_filter( 'cftp_dt_answer_providers', 'CFTP_DT_Answers_Simple::filter_answer_providers', 0 );
