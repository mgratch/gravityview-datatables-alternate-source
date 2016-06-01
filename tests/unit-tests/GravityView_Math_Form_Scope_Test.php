<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Math_Form_Scope_Test extends GV_UnitTestCase {

	/**
	 * Tests each built in HOA function for expected results in form scope
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 */
	function test_math_functions() {

		//Create a new form
		$form = $this->factory->form->create_and_get();

		$form_id = $form['id'];

		//create a user with proper caps
		$editor = $this->factory->user->create_and_set( array(
			'user_login' => 'editor',
			'role'       => 'editor'
		) );

		//create 10 entries for the new form
		$entries = $this->factory->entry->create_many( 10,
			array(
				'form_id'    => $form_id,
				'created_by' => $editor->ID
			)
		);

		foreach ( $entries as $entry ) {
			GFAPI::update_entry_field( $entry, '3', '-0.1' );
			GFAPI::update_entry_field( $entry, '2', '10' );
		}

		//Test all the HOA Math Functions Supported with -1 as an input
		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="abs( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="acos( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'3.1415926535898') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="asin( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-1.5707963267949') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="atan( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-0.78539816339745') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="average( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="avg( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="ceil( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="cos( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'0.54030230586814') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="count( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="deg2rad( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-0.017453292519943') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="exp( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'0.36787944117144') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="floor( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="rad2deg( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-57.295779513082') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="sin( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-0.8414709848079') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="tan( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-1.5574077246549') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="sum( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		//Test the HOA math functions that require special inputs
		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="max( {Label for field three (number):3:sum}, 1 )"]' );
		$this->assertTrue( strpos( $value,'1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="min( {Label for field three (number):3:sum}, 1 )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="sqrt( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value,'10') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="log( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value,'2') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="pow( {Label for field two (hidden):2:sum}, 2 )"]' );
		$this->assertTrue( strpos( $value,'10,000') !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" formula="ln( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value,'4.6051701859881') !== false );

	}

	function test_nested_math_functions() {


		//Create a new form
		$form = $this->factory->form->create_and_get();

		$form_id = $form['id'];

		//create a user with proper caps
		$editor = $this->factory->user->create_and_set( array(
			'user_login' => 'editor',
			'role'       => 'editor'
		) );

		//create 10 entries for the new form
		$entries = $this->factory->entry->create_many( 10, array(
			'form_id'    => $form_id,
			'created_by' => $editor->ID,
			'3'          => '-0.1'
		) );

		//testing nested functions
		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]abs( 1 * {Label for field three (number):3:sum} )[/gv_math]' );
		$this->assertTrue( strpos( $value,"1") !== false );

		//testing nested functions with Constants
		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]abs( 1 * ( PI * {Label for field three (number):3:sum} ) )[/gv_math]' );
		$this->assertTrue( strpos( $value,"3.1415926535898") !== false );

	}

	function test_aggregated_data() {

		//Create a new form
		$form = $this->factory->form->create_and_get();

		$form_id = $form['id'];

		//create a user with proper caps
		$editor = $this->factory->user->create_and_set( array(
			'user_login' => 'editor',
			'role'       => 'editor'
		) );

		//create 9 entries for the new form
		$entries = $this->factory->entry->create_many( 9, array(
			'form_id'    => $form_id,
			'created_by' => $editor->ID,
			'3'          => '1'
		) );

		//create 9 entries for the new form
		$entries[] = $this->factory->entry->create( array(
			'form_id'    => $form_id,
			'created_by' => $editor->ID,
			'3'          => '11'
		) );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]{Label for field three (number):3}[/gv_math]' );
		$this->assertTrue( strpos( $value,"20") !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]{Label for field three (number):3:sum}[/gv_math]' );
		$this->assertTrue( strpos( $value,"20") !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]{Label for field three (number):3:count}[/gv_math]' );
		$this->assertTrue( strpos( $value,"10") !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]{Label for field three (number):3:min}[/gv_math]' );
		$this->assertTrue( strpos( $value,"1") !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]{Label for field three (number):3:max}[/gv_math]' );
		$this->assertTrue( strpos( $value,"11") !== false );

		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '"]{Label for field three (number):3:avg}[/gv_math]' );
		$this->assertTrue( strpos( $value,"2") !== false );
	}
}
