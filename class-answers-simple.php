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
	 * The ID for the decision tree node/post in question
	 *
	 * @var int
	 **/
	var $post_id;
	
	/**
	 * Singleton stuff.
	 * 
	 * @access @static
	 * 
	 * @return void
	 */
	static public function init( $post_id = false ) {
		static $instance = false;

		if ( ! $instance ) {
			$class = get_called_class();
			$instance = new $class( $post_id );
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
	static public function filter_answer_providers( $answers, $post_id ) {
		$answers[] = self::init( $post_id );
		return $answers;
	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return void
	 **/
	public function __construct( $post_id = false ) {
		$this->post_id = $post_id;
	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return void
	 **/
	public function form() {
		echo "<p>Form for $this->post_id</p>";
	}
	
	/**
	 * @TODO
	 *
	 * @access public
	 * 
	 * @return void
	 **/
	public function get_answer() {
		
	}


}

add_filter( 'cftp_dt_answer_providers', 'CFTP_DT_Answers_Simple::filter_answer_providers', 0, 2 );
