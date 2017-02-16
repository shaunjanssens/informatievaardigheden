<?php
$export_options = LP()->session->get( LPIE_EXPORT_OPTIONS_SESSION_KEY );//['learn_press_export_options'];
?>
<h3><?php _e( 'Export LearnPress', 'learnpress-import-export' ); ?></h3>
<table>
	<tr id="total-courses">
		<td><?php _e( 'Total courses', 'learnpress-import-export' ); ?></td>
		<td><?php echo sizeof( $export_options['courses'] ); ?></td>
	</tr>
	<tr id="exported-courses">
		<td><?php _e( 'Exported courses', 'learnpress-import-export' ); ?></td>
		<td><span class="exported-courses">0</span></td>
	</tr>
	<tr id="exporting">
		<td><?php _e( 'Exporting...', 'learnpress-import-export' ); ?></td>
		<td><span class="exporting">0</span></td>
	</tr>
	<tr id="complete" class="hide-if-js">
		<td><?php _e( 'Complete', 'learnpress-import-export' ); ?></td>
		<td><span class="complete"></span></td>
	</tr>
</table>
<script type="text/javascript">
	var model = new LP_Export_LearnPress_Model(<?php echo json_encode( $export_options );?>),
		view = new LP_Export_LearnPress_View(model);
	view.doExport();
</script>