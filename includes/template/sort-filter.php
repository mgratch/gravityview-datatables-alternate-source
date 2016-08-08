<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global WP_Post $post
 */
global $post;

$curr_form = gravityview_get_form_id( $post->ID );

// View template settings
$current_settings = gravityview_get_template_settings( $post->ID );

?>
<table class="form-table striped" id="gaddon-setting-row-sort_field">
	<thead>

	<?php

	do_action( 'gravityview_metabox_sort_filter_before', $current_settings );

	// Begin Sort fields
	do_action( 'gravityview_metabox_sort_before', $current_settings );

	/**
	 * @since 1.7
	 */
	GravityView_Render_Settings::render_setting_row( 'sort_columns', $current_settings );

	echo "</thead>";

	$html = '';

	/* Build HTML string */
	$key_field_html = '<th>' .
	                  '<select class="key key_{i}" name="sort_field_key" id="gravityview_sort_field"></select>' .
	                  '</th>';

	/* Build HTML string */
	$value_field_html = '<select class="value value_{i}" name="sort_field_custom_value" id="gravityview_direction_field">' .
	                    '<option value="asc">ASC</option>' .
	                    '<option value="desc">DESC</option>' .
	                    '</select>';


	$html .= '
                <tbody class="repeater">
	                <tr>
	                    ' . $key_field_html . '
	                    <td>' .
	         $value_field_html . '
						</td>
						<td>
							{buttons}
						</td>
	                </tr>
                </tbody>';

	echo $html;


	//$GF_Field_List = new GF_Field_List();
	//echo $GF_Field_List->get_field_input(GFAPI::get_form($curr_form));

	//GravityView_Render_Settings::render_setting_row( 'sort_field', $current_settings, $sort_fields_input );

	//GravityView_Render_Settings::render_setting_row( 'sort_direction', $current_settings );


	// End Sort fields
	do_action( 'gravityview_metabox_sort_after', $current_settings );

	// Begin Filter fields
	do_action( 'gravityview_metabox_filter_before', $current_settings );

	echo "<tfoot>";

	GravityView_Render_Settings::render_setting_row( 'start_date', $current_settings );

	GravityView_Render_Settings::render_setting_row( 'end_date', $current_settings );

	echo "</tfoot>";

	// End Filter fields
	do_action( 'gravityview_metabox_filter_after', $current_settings );

	do_action( 'gravityview_metabox_sort_filter_after', $current_settings );

	//$after_table = "<input type='hidden' id='sort_field' name='template_settings[sort_field]' value='' />";

	$after_table = "
			<script type=\"text/javascript\">
			
				var dynamicFieldMap = new gfieldmap({
					
					'baseURL':      '". GFCommon::get_base_url() ."',
					'fieldId':      'sort_field',
					'fieldName':    'sort_field',
					'keyFieldName': 'sort_field_key',
					'limit':        '0'
										
				});
			
			</script>";
	?>

</table>

<?php

GravityView_Render_Settings::render_setting_row( 'multiple_sort_field', $current_settings, $override_input = null, $name = 'template_settings[multiple_sort_field]', $id = 'sort_field' );
echo $after_table;

?>