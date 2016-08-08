<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Math_Shortcode_Test extends GV_UnitTestCase {

	/**
	 * Just covers that it renders something and requires the content if the formula parameter does not exist
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 */
	function test_shortcode_content() {

		// test gvmath
		$value = do_shortcode( '[gvmath]' );
		$this->assertTrue( strpos( $value, 'gv-math-') !== false );

		// test gvmath with content
		$value = do_shortcode( '[gvmath]1+1[/gvmath]' );
		$this->assertTrue( strpos( $value,"2") !== false );
	}

	/**
	 * Just covers that it renders something and requires the formula parameter if the content is not set.
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 */
	function test_shortcode_formula() {

		// test gv_math with formula
		$value = do_shortcode( '[gv_math formula="1+1"]' );
		$this->assertTrue( strpos( $value,"2") !== false );
	}

	/**
	 * Test the decimals parameter to ensure that it produces the expected number of decimal places
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 */
	function test_shortcode_decimals() {

		// test 16 decimal places
		$value = do_shortcode( '[gv_math formula="3.1415926535897932385" decimals="16" /]' );
		$this->assertTrue( strpos( $value,"3.1415926535897931") !== false );
		
		// test 2 decimal places
		$value = do_shortcode( '[gv_math formula="3.1415926535897932385" decimals="2" /]' );
		$this->assertTrue( strpos( $value, '3.14') !== false );

		// test 0 decimal places
		$value = do_shortcode( '[gv_math formula="3.1415926535897932385" decimals="0" /]' );
		$this->assertTrue( strpos( $value, '3') !== false );

		// test without the decimal param set
		$value = do_shortcode( '[gv_math formula="5/2" /]' );
		$this->assertTrue( strpos( $value, '2.5') !== false );

		// test 2 decimal places where the second place is a 0
		$value = do_shortcode( '[gv_math formula="5/2" decimals="2" /]' );
		$this->assertTrue( strpos( $value, '2.50') !== false );
	}

	/**
	 * Test the format parameter to ensure that it skips formatting when format="raw"
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 */
	function test_shortcode_format() {

		// test number formatted
		$value = do_shortcode( '[gv_math formula="3.1415926536 * 1000000" /]' );
		$this->assertTrue( strpos( $value, '3,141,592.6536') !== false );

		// test raw parameter
		$value = do_shortcode( '[gv_math formula="3.1415926536 * 1000000" format="raw" /]' );
		$this->assertTrue( strpos( $value, '3141592.6536') !== false );
	}
	
	/**
	 * Test the default_value parameter to ensure that when entry field is empty the default value is used
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 * @covers GravityView_Math_Shortcode::is_valid_default_value
	 */
	function test_default_value() {

		$form = $this->factory->form->create_and_get();

		$editor = $this->factory->user->create_and_set( array(
			'user_login' => 'editor',
			'role'       => 'editor'
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id'    => $form['id'],
			'created_by' => $editor->ID,
		) );

		$entry[2] = '';

		GFAPI::update_entry( $entry, $entry['id'] );

		//tests default value from shortcode
		$value = do_shortcode( '[gv_math scope="entry" id="' . $entry['id'] . '" default_value="1000" ]1 * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, "1,000") !== false );

		//tests no default value supplied for the shortcode or GF
		$value = do_shortcode( '[gv_math scope="entry" id="' . $entry['id'] . '" ]1 * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, "gv-math-") !== false );

		//test no default value supplied for the shortcode, but 500 is set for the form
		$form['gravityview-math']['default_value'] = '500';
		GFAPI::update_form( $form );
		$value = do_shortcode( '[gv_math scope="entry" id="' . $entry['id'] . '" ]1 * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, "500") !== false );

		//tests default value from GF when shortcode supplied default value is not valid
		$form['gravityview-math']['default_value'] = '1234';
		GFAPI::update_form( $form );
		$value = do_shortcode( '[gv_math scope="entry" id="' . $entry['id'] . '" default_value="nothing" ]1 * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, "1,234") !== false );
	}
}
