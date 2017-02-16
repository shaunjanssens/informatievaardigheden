<div id="exporting" class="hide-if-js">
	Exporting...
</div>
<div id="export-step-2-options">
	<ul class="form-options">
		<li>
			<label>
				<?php _e( 'Download file', 'learnpress-import-export' ); ?>
			</label>
			<input type="hidden" name="download_export" value="0" />
			<input type="checkbox" name="download_export" value="1" checked="checked" />
			<p class="description">
				<?php _e( 'Download file after exporting is completed', 'learnpress-import-export' ); ?>
			</p>
		</li>
		<li>
			<label>
				<?php _e( 'Save exported file', 'learnpress-import-export' ); ?>
			</label>
			<input type="hidden" name="save_export" value="0" />
			<input type="checkbox" name="save_export" value="1" />
			<p class="description">
				<?php _e( 'Save exported file on your server so you can download it later', 'learnpress-import-export' ); ?>
			</p>
		</li>
	</ul>
	<input type="hidden" name="export_type" value="course" />
	<input type="hidden" name="export_type" value="<?php echo learn_press_get_request( 'export_type' ); ?>" />
	<?php if ( !empty( $_REQUEST['courses'] ) ): ?>
		<?php foreach ( $_REQUEST['courses'] as $course_id ): ?>
			<input type="hidden" name="courses[]" value="<?php echo $course_id; ?>" />
		<?php endforeach; ?>
	<?php endif; ?>
	<p>
		<input class="regular-text" value="<?php echo $this->get_export_file_name_without_ext(); ?>" type="text" name="learn-press-export-file-name" placeholder="<?php _e( 'Custom name of export file', 'learnpress-import-export' ); ?>" />.xml
	</p>
	<p>
		<button class="button button-primary" id="button-export" disabled="disabled"><?php _e( 'Export now', 'learnpress-import-export' ); ?></button>
		<button class="button" id="lpie-button-back-step"><?php _e( 'Back', 'learnpress-import-export' ); ?></button>
		<button class="button" id="lpie-button-cancel"><?php _e( 'Cancel', 'learnpress-import-export' ); ?></button>
	</p>
</div>
<script type="text/javascript">
	typeof jQuery != 'undefined' && jQuery(function ($) {
		var $form = $('form[name="export-courses"]'),
			$chks = $form.find('input[name="download_export"], input[name="save_export"]');
		$chks.on('change', function () {
			$form.find('#button-export').attr('disabled', $chks.filter(':checked').length == 0);
		}).trigger('change');
	})
</script>