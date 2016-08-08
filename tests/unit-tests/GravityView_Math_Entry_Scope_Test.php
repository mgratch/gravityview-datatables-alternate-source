<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Math_Entry_Scope_Test extends GV_UnitTestCase {

	function test_nested_math_functions_with_entry_scope() {

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

		//testing nested functions
		$value = do_shortcode( '[gv_math scope="entry" id="' . $entries[1] . '" ]abs( 1 * {Number:2})[/gv_math]' );
		$this->assertTrue( strpos( $value, '3.33333' ) > 0 );

		$value = do_shortcode( '[gv_math scope="entry" id="' . $entries[1] . '" ]abs( -1 * ({Number:2}))[/gv_math]' );
		$this->assertTrue( strpos( $value, '3.33333' ) > 0 );

		//tests default value from shortcode
		$value = do_shortcode( '[gv_math scope="entry" id="' . $entries[1] . '" ]abs( -1 * ( PI * {Number:2}) )[/gv_math]' );
		$this->assertTrue( strpos( $value, '10.47196503999' ) > 0 );

	}
}
