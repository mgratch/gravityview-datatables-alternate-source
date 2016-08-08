<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Math_Constants_Test extends GV_UnitTestCase {

	/**
	 * Tests each built in HOA Constant for expected results
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 */
	function test_math_constants_base() {

		//Test all the HOA Math Functions Supported with -1 as an input
		$value = do_shortcode( '[gv_math formula="PI"]' );
		$this->assertTrue( strpos( $value, '3.1415926535898' ) > 0 );

		$value = do_shortcode( '[gv_math formula="PI_2"]' );
		$this->assertTrue( strpos( $value, '1.5707963267949' ) > 0 );

		$value = do_shortcode( '[gv_math formula="PI_4"]' );
		$this->assertTrue( strpos( $value, '0.78539816339745' ) > 0 );

		//$value = do_shortcode( '[gv_math formula=\'E\']' );
		//$this->assertEquals( '2.718281828459', $value );

		$value = do_shortcode( '[gv_math formula="SQRT_PI"]' );
		$this->assertTrue( strpos( $value, '1.7724538509055' ) > 0 );

		$value = do_shortcode( '[gv_math formula="SQRT_2"]' );
		$this->assertTrue( strpos( $value, '1.4142135623731' ) > 0 );

		$value = do_shortcode( '[gv_math formula="SQRT_3"]' );
		$this->assertTrue( strpos( $value, '1.7320508075689' ) > 0 );

		$value = do_shortcode( '[gv_math formula="LN_PI"]' );
		$this->assertTrue( strpos( $value, '1.1447298858494' ) > 0 );

	}

	function test_math_constants_form_scope() {

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
			'form_id'    => $form['id'],
			'created_by' => $editor->ID,
		) );

		//testing constants with form scope
		$value = do_shortcode( '[gv_math scope="form" id="' . $form_id . '" ]PI * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, '104.7196503999' ) > 0 );

	}

	function test_math_constants_view_scope() {

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
			'form_id'    => $form['id'],
			'created_by' => $editor->ID,
		) );

		$post = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$fields = gravityview_get_form_fields( $form_id );

		$this->factory->view->update_object( $post->ID, $fields );

		//testing constants with view scope
		$value = do_shortcode( '[gv_math scope="view" id="' . $post->ID . '" ]PI * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, '104.7196503999' ) > 0 );

	}

	function test_math_constants_visible_scope() {

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
			'form_id'    => $form['id'],
			'created_by' => $editor->ID,
		) );

		$post = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$fields = gravityview_get_form_fields( $form_id );

		$this->factory->view->update_object( $post->ID, $fields );

		//testing constants with view scope
		$value = do_shortcode( '[gravityview id="' . $post->ID . '"][gv_math scope="visible"]PI * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, '104.7196503999' ) > 0 );
	}

	function test_math_constants_entry_scope() {

		//Create a new form
		$form = $this->factory->form->create_and_get();

		//create a user with proper caps
		$editor = $this->factory->user->create_and_set( array(
			'user_login' => 'editor',
			'role'       => 'editor'
		) );

		//create 10 entries for the new form
		$entries = $this->factory->entry->create_many( 10, array(
			'form_id'    => $form['id'],
			'created_by' => $editor->ID,
		) );

		//testing constants with entry scope
		$value = do_shortcode( '[gv_math scope="entry" id="' . $entries[1] . '" ]PI * {Number:2}[/gv_math]' );
		$this->assertTrue( strpos( $value, '10.47196503999' ) > 0 );

	}
}
