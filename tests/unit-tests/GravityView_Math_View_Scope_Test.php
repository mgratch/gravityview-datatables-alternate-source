<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Math_View_Scope_Test extends GV_UnitTestCase {

	/**
	 * Tests each built in HOA function for expected results
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
		$entries = $this->factory->entry->create_many( 10, array(
			'form_id'    => $form_id,
			'created_by' => $editor->ID,
		) );

		//update all the entries with more predictable values
		foreach ( $entries as $entry ) {
			GFAPI::update_entry_field( $entry, '2', '-0.1' );
			GFAPI::update_entry_field( $entry, '3', '10' );
		}

		$view = $this->factory->view->create_and_get( array( 'form_id' => $form_id ) );

		$fields = gravityview_get_form_fields( $form_id );

		$this->factory->view->update_object( $view->ID, $fields );

		//Test all the HOA Math Functions Supported with -1 as an input
		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="abs( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="acos( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '3.1415926535898') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="asin( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-1.5707963267949') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="atan( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-0.78539816339745') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="average( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="avg( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="ceil( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="cos( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '0.54030230586814') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="count( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="deg2rad( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-0.017453292519943') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="exp( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '0.36787944117144') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="floor( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="rad2deg( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-57.295779513082') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="sin( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-0.8414709848079') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="tan( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-1.5574077246549') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="sum( {Label for field two (hidden):2:sum} )"]' );
		$this->assertTrue( strpos( $value, '-1') !== false );

		//Test the HOA math functions that require special inputs
		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="max( {Label for field two (hidden):2:sum}, 1 )"]' );
		$this->assertTrue( strpos( $value, '1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="min( {Label for field two (hidden):2:sum}, 1 )"]' );
		$this->assertTrue( strpos( $value, '-1') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="sqrt( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value, '10') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="log( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value, '2') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="pow( {Label for field three (number):3:sum}, 2 )"]' );
		$this->assertTrue( strpos( $value, '10,000') !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '" formula="ln( {Label for field three (number):3:sum} )"]' );
		$this->assertTrue( strpos( $value, '4.6051701859881') !== false );

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
		) );

		//update all the entries with more predictable values
		foreach ( $entries as $entry ) {
			GFAPI::update_entry_field( $entry, '3', '-0.1' );
		}

		$view = $this->factory->view->create_and_get( array( 'form_id' => $form_id ) );

		$fields = gravityview_get_form_fields( $form_id );

		$this->factory->view->update_object( $view->ID, $fields );

		//testing nested functions
		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]abs( 1 * {Label for field three (number):3:sum} )[/gv_math]' );
		$this->assertTrue( strpos( $value, "1") !== false );

		//testing nested functions with Constants
		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]abs( 1 * ( PI * {Label for field three (number):3:sum} ) )[/gv_math]' );
		$this->assertTrue( strpos( $value, "3.1415926535898") !== false );
		$this->assertTrue( strpos( $value, '3141592.6536') !== false );
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

		$view = $this->factory->view->create_and_get( array( 'form_id' => $form_id ) );

		$fields = gravityview_get_form_fields( $form_id );

		$this->factory->view->update_object( $view->ID, $fields );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]{Label for field three (number):3}[/gv_math]' );
		$this->assertTrue( strpos( $value, "20") !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]{Label for field three (number):3:sum}[/gv_math]' );
		$this->assertTrue( strpos( $value, "20") !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]{Label for field three (number):3:count}[/gv_math]' );
		$this->assertTrue( strpos( $value, "10") !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]{Label for field three (number):3:min}[/gv_math]' );
		$this->assertTrue( strpos( $value, "1") !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]{Label for field three (number):3:max}[/gv_math]' );
		$this->assertTrue( strpos( $value, "11") !== false );

		$value = do_shortcode( '[gv_math scope="view" id="' . $view->ID . '"]{Label for field three (number):3:avg}[/gv_math]' );
		$this->assertTrue( strpos( $value, "2") !== false );
	}
}
