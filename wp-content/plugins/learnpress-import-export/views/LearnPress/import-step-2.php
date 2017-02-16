<div id="importing">
	<?php _e( 'Importing...', 'learnpress-import-export' );?>
</div>
<script type="text/javascript">
	var model = new LP_Import_LearnPress_Model(),
		view = new LP_Import_LearnPress_View(model);
	view.doImport(<?php echo json_encode($_REQUEST);?>);
</script>