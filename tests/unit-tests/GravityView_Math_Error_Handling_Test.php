<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Math_Error_Handling_Test extends GV_UnitTestCase {

	function test_scope_error_end_user() {

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );


		$this->assertTrue( strpos( $value, "id='gv-math-" ) !== false );
	}

	function test_scope_error_end_user_notice() {

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" notices="true" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '**' ) !== false );
		$this->assertTrue( strpos( $value, 'No Results Currently Available' ) !== false );
	}

	function test_scope_warning_end_user() {

		//Create a new form
		$form = $this->factory->form->create_and_get();

		$form_id = $form['id'];

		//create 10 entries for the new form
		$entries = $this->factory->entry->create_many( 10,
			array(
				'form_id'    => $form_id,
				'created_by' => 1
			)
		);

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" id="' . $form_id . '" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '10' ) !== false );
	}

	function test_scope_warning_end_user_notice() {

		//Create a new form
		$form = $this->factory->form->create_and_get();

		$form_id = $form['id'];

		//create 10 entries for the new form
		$entries = $this->factory->entry->create_many( 10,
			array(
				'form_id'    => $form_id,
				'created_by' => 1
			)
		);

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" notices="true" id="' . $form_id . '" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );


		$this->assertTrue( strpos( $value, '10*' ) !== false );
		$this->assertTrue( strpos( $value, '* Results may not be accurate.' ) !== false );
	}

	function test_scope_warning_admin_user() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" id="' . $form_id . '" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '10<sup>' ) !== false );
		$this->assertTrue( strpos( $value, 'gv-math-warning' ) !== false );
		$this->assertTrue( strpos( $value, 'empty_form_field' ) !== false );
		$this->assertTrue( strpos( $value, 'You can only see this message because you are logged in and have permissions.' ) !== false );
	}

	function test_scope_error_admin_user() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, 'gv-math-error' ) !== false );
		$this->assertTrue( strpos( $value, 'ID_not_set' ) !== false );
		$this->assertTrue( strpos( $value, 'You can only see this message because you are logged in and have permissions.' ) !== false );
	}

	function test_scope_warning_admin_user_debug_false() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		define( 'GV_MATH_DEBUG', false );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" debug="false" id="' . $form_id . '" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '10<sup>' ) == false );
		$this->assertTrue( strpos( $value, 'gv-math-warning' ) == false );
		$this->assertTrue( strpos( $value, 'empty_form_field' ) == false );
		$this->assertTrue( strpos( $value, 'You can only see this message because you are logged in and have permissions.' ) == false );
	}

	function test_scope_error_admin_user_debug_false() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		define( 'GV_MATH_DEBUG', false );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" debug="false" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, 'gv-math-error' ) == false );
		$this->assertTrue( strpos( $value, 'ID_not_set' ) == false );
		$this->assertTrue( strpos( $value, 'You can only see this message because you are logged in and have permissions.' ) == false );
	}

	function test_scope_error_admin_user_suppress_filter() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		add_filter( 'gravityview/math/suppress_errors', '__return_true' );
		define( 'GV_MATH_DEBUG', true );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '<sup>' ) == false );
		$this->assertTrue( strpos( $value, 'gv-math-error' ) == false );
		$this->assertTrue( strpos( $value, 'ID_not_set' ) == false );
		$this->assertTrue( strpos( $value, 'You can only see this message because you are logged in and have permissions.' ) == false );
	}

	function test_scope_error_admin_user_notice_filter() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		add_filter( 'gravityview/math/admin_notice', array( $this, 'change_notice' ) );
		define( 'GV_MATH_DEBUG', true );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '<sup>' ) !== false );
		$this->assertTrue( strpos( $value, 'gv-math-error' ) !== false );
		$this->assertTrue( strpos( $value, 'ID_not_set' ) !== false );
		$this->assertTrue( strpos( $value, 'test message' ) !== false );
	}

	function test_scope_error_end_user_notice_filter() {

		//Create a new form
		$form = $this->factory->form->create_and_get();

		$form_id = $form['id'];

		//create 10 entries for the new form
		$entries = $this->factory->entry->create_many( 10,
			array(
				'form_id'    => $form_id,
				'created_by' => 1
			)
		);

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		add_filter( 'gravityview/math/no_results_message', array( $this, 'change_notice' ) );
		define( 'GV_MATH_DEBUG', true );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" notices="true" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '<sup>' ) == false );
		$this->assertTrue( strpos( $value, 'gv-math-error' ) == false );
		$this->assertTrue( strpos( $value, 'ID_not_set' ) == false );
		$this->assertTrue( strpos( $value, 'test message' ) !== false );
	}

	function test_scope_warning_end_user_notice_filter() {

		//Create a new form
		$form = $this->factory->form->create_and_get();

		$form_id = $form['id'];

		//create 10 entries for the new form
		$entries = $this->factory->entry->create_many( 10,
			array(
				'form_id'    => $form_id,
				'created_by' => 1
			)
		);

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		add_filter( 'gravityview/math/accuracy_message', array( $this, 'change_notice' ) );
		define( 'GV_MATH_DEBUG', true );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" notices="true" id="' . $form_id . '" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '10*' ) !== false );
		$this->assertTrue( strpos( $value, '<sup>' ) == false );
		$this->assertTrue( strpos( $value, 'gv-math-error' ) == false );
		$this->assertTrue( strpos( $value, 'ID_not_set' ) == false );
		$this->assertTrue( strpos( $value, 'test message' ) !== false );
	}

	function test_scope_override_warning_message() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		add_filter( 'gravityview/math/debug_message', array( $this, 'edit_empty_entry_message' ), 11, 2 );
		define( 'GV_MATH_DEBUG', true );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" notices="true" id="' . $form_id . '" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '<sup>' ) !== false );
		$this->assertTrue( strpos( $value, 'gv-math-warning' ) !== false );
		$this->assertTrue( strpos( $value, 'empty_form_field' ) !== false );
		$this->assertTrue( strpos( $value, 'test message' ) !== false );
	}

	function test_scope_multiple_warning_messages() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '2', '1' );
				GFAPI::update_entry_field( $entry, '3', '' );
			} elseif ( $key == 1 ) {
				GFAPI::update_entry_field( $entry, '2', '' );
				GFAPI::update_entry_field( $entry, '3', '1' );
			} else {
				GFAPI::update_entry_field( $entry, '2', '1' );
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		add_filter( 'gravityview/math/debug_message', array( $this, 'edit_empty_entry_message' ), 11, 2 );
		define( 'GV_MATH_DEBUG', true );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" notices="true" id="' . $form_id . '" formula="{Label for field three (number):3:sum} + {Label for field two (hidden):2:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '19<sup>' ) !== false );
		$this->assertTrue( substr_count( $value, '#gv-math-warning-' ) == 2 );
		$this->assertTrue( strpos( $value, 'empty_form_field' ) !== false );
		$this->assertTrue( strpos( $value, 'test message' ) !== false );
	}

	function test_scope_override_error_message() {

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

		foreach ( $entries as $key => $entry ) {
			if ( $key == 0 ) {
				GFAPI::update_entry_field( $entry, '3', '' );
			} else {
				GFAPI::update_entry_field( $entry, '3', '1' );
			}
		}

		add_filter( 'gravityview/math/debug_message', array( $this, 'edit_empty_entry_message' ), 11, 2 );
		define( 'GV_MATH_DEBUG', true );

		$post = $this->factory->post->create_and_get(
			array(
				'post_content' => '[gv_math scope="form" notices="true" formula="{Label for field three (number):3:sum} + 1"]'
			)
		);

		setup_postdata( $post );

		$content = get_the_content();
		$value   = apply_filters( 'the_content', $content );

		$this->assertTrue( strpos( $value, '<sup>' ) !== false );
		$this->assertTrue( strpos( $value, 'gv-math-error' ) !== false );
		$this->assertTrue( strpos( $value, 'ID_not_set' ) !== false );
		$this->assertTrue( strpos( $value, 'test message' ) !== false );
	}

	public function change_notice( $notice ) {
		$notice = 'test message';
		return $notice;
	}

	public function edit_empty_entry_message( $message, $data ) {
		if ( 'empty_form_field' == $data['code'] || 'ID_not_set' == $data['code'] ) {
			$message = 'test message';
		}

		return $message;
	}


}
