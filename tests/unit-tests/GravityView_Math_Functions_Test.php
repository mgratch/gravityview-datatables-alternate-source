<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Math_Functions_Test extends GV_UnitTestCase {

	/**
	 * Tests each built in HOA function for expected results
	 * @covers GravityView_Math_Shortcode::do_shortcode
	 */
	function test_math_functions_base() {

		//Test all the HOA Math Functions Supported with -1 as an input
		$value = do_shortcode( '[gv_math formula="abs( -1 )"]' );
		$this->assertTrue( strpos( $value, '1') !== false );
		$this->assertTrue( strpos( $value, "id='gv-math-" ) !== false );
		$value = do_shortcode( '[gv_math formula="acos( -1 )"]' );
		$this->assertTrue( strpos( $value,'3.1415926535898') !== false );

		$value = do_shortcode( '[gv_math formula="asin( -1 )"]' );
		$this->assertTrue( strpos( $value,'-1.5707963267949') !== false );

		$value = do_shortcode( '[gv_math formula="atan( -1 )"]' );
		$this->assertTrue( strpos( $value,'-0.78539816339745') !== false );

		$value = do_shortcode( '[gv_math formula="average( -1 )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math formula="avg( -1 )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math formula="ceil( -1 )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math formula="cos( -1 )"]' );
		$this->assertTrue( strpos( $value,'0.54030230586814') !== false );

		$value = do_shortcode( '[gv_math formula="count( -1 )"]' );
		$this->assertTrue( strpos( $value,'1') !== false );

		$value = do_shortcode( '[gv_math formula="deg2rad( -1 )"]' );
		$this->assertTrue( strpos( $value,'-0.017453292519943') !== false );

		$value = do_shortcode( '[gv_math formula="exp( -1 )"]' );
		$this->assertTrue( strpos( $value,'0.36787944117144') !== false );

		$value = do_shortcode( '[gv_math formula="floor( -1 )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math formula="rad2deg( -1 )"]' );
		$this->assertTrue( strpos( $value,'-57.295779513082') !== false );

		$value = do_shortcode( '[gv_math formula="sin( -1 )"]' );
		$this->assertTrue( strpos( $value,'-0.8414709848079') !== false );

		$value = do_shortcode( '[gv_math formula="tan( -1 )"]' );
		$this->assertTrue( strpos( $value,'-1.5574077246549') !== false );

		$value = do_shortcode( '[gv_math formula="sum( -1 )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		//Test the HOA math functions that require special inputs
		$value = do_shortcode( '[gv_math formula="max( -1, 1 )"]' );
		$this->assertTrue( strpos( $value,'1') !== false );

		$value = do_shortcode( '[gv_math formula="min( -1, 1 )"]' );
		$this->assertTrue( strpos( $value,'-1') !== false );

		$value = do_shortcode( '[gv_math formula="sqrt( 4 )"]' );
		$this->assertTrue( strpos( $value,'2') !== false );

		$value = do_shortcode( '[gv_math formula="log( 10 )"]' );
		$this->assertTrue( strpos( $value,'1') !== false );

		$value = do_shortcode( '[gv_math formula="pow( 7, 2 )"]' );
		$this->assertTrue( strpos( $value,'49') !== false );

		$value = do_shortcode( '[gv_math formula="ln( 10 )"]' );
		$this->assertTrue( strpos( $value,'2.302585092994') !== false );

	}

	function test_nested_math_functions() {

		//testing nested functions
		$value = do_shortcode( '[gv_math]abs( 1 * 3.33333 )[/gv_math]' );
		$this->assertTrue( strpos( $value,"3.33333") !== false );

		$value = do_shortcode( '[gv_math]abs( -1 * 3.33333 )[/gv_math]' );
		$this->assertTrue( strpos( $value,"3.33333") !== false );

		//testing nested functions with Constants
		$value = do_shortcode( '[gv_math]abs( -1 * ( PI * 3.33333 ) )[/gv_math]' );
		$this->assertTrue( strpos( $value,"10.47196503999") !== false );

	}
}
