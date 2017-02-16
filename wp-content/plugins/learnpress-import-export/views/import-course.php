<?php
wp_enqueue_script( 'learn-press-global' );
$step = 0;
if ( !empty( $_REQUEST['import-nonce'] ) && wp_verify_nonce( $_REQUEST['import-nonce'], 'learnpress-import-export-import' ) ) {
	$step = learn_press_get_request( 'step', 1 );
}
?>
<form method="post" name="import-form" id="import-form" action="admin.php?page=learnpress-import-export&tab=import-course" enctype="multipart/form-data">
	<div id="import-form-postbox" class="postbox">
		<h2 class="hndle"><span><?php _e( 'Import', 'learnpress-import-export' ); ?></span></h2>

		<div class="inside">

			<input type="hidden" name="step" value="<?php echo $step + 1; ?>" />
			<input type="hidden" name="action" value="export" />
			<input type="hidden" name="import-nonce" value="<?php echo wp_create_nonce( 'learnpress-import-export-import' ); ?>">
			<?php if ( $step ) { ?>
				<?php do_action( 'lpie_import_view_step_' . $step ); ?>
			<?php } else { ?>
				<div id="import-uploader">
					<div id="import-upload-file"></div>
					<a id="import-uploader-select" href="javascript:;"><?php _e('Select file (xml)');?></a>
					<a id="import-start-upload" class="dashicons dashicons-upload" href="javascript:;"></a>
				</div>
			<?php } ?>


		</div>
	</div>
</form>
<script type="text/javascript">
	// Custom example logic
	jQuery(function ($) {
		LP_Importer.init({
			url: "<?php echo admin_url( 'admin.php?page=learnpress-import-export&tab=import-course' );?>"
		})
	})

</script>
<form action="admin.php?page=learnpress-import-export&tab=import-course">
	<?php $imports = lpie_get_import_files();
	$total         = sizeof( $imports ); ?>
	<div id="browse-imported-files" class="postbox">
		<h2 class="hndle">
			<span>
				<?php _e( 'Recent imported', 'learnpress-import-export' ); ?>
			</span>
		</h2>

		<div class="inside">
			<p>
				<strong><?php printf( $total ? _nx( '%d file', '%d files', $total, 'learnpress-import-export' ) : __( '%d file' ), $total ); ?></strong>
				<a href="" data-text="<?php _e( 'Remove selected', 'learnpress-import-export' ); ?>" data-url="<?php echo wp_nonce_url( admin_url( 'admin.php?page=learnpress-import-export&tab=' . lpie_get_current_tab() ), 'lpie-delete-import-file', 'nonce' ) . '&delete-import='; ?>" id="learn-press-remove-files" class="hide-if-js"><?php _e( 'Remove selected', 'learnpress-import-export' ); ?></a>
			</p>
			<table class="wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th width="20" style="text-align: right;">#</th>
					<th width="20"><input type="checkbox" id="learn-press-check-all-files" /></th>
					<th><?php _e( 'File', 'learnpress-import-export' ); ?></th>
					<th width="25%"><?php _e( 'Time', 'learnpress-import-export' ); ?></th>
					<th width="50"><?php _e( 'Size', 'learnpress-import-export' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( $imports ): ?>

					<?php
					$index = 0;
					foreach ( $imports as $file ) {
						$m_time = date( 'Y/m/d g:i:s a', $file['lastmodunix'] );
						?>
						<tr>
							<td style="text-align: right;"><?php echo ++ $index; ?></td>
							<td><input type="checkbox" class="check-file" value="<?php echo $file['name']; ?>" /></td>
							<th>
								<?php echo $file['name']; ?>
								<p>
									<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=learnpress-import-export&tab=' . lpie_get_current_tab() . '&download-import=' . $file['name'] ), 'lpie-download-import-file', 'nonce' ); ?>"><?php _e( 'Download', 'learnpress-import-export' ); ?></a>
									|
									<a href="<?php echo lpie_get_url( 'learnpress/import/' . $file['name'] ); ?>" target="_blank"><?php _e( 'View', 'learnpress-import-export' ); ?></a>
									|
									<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=learnpress-import-export&tab=' . lpie_get_current_tab() . '&import-file=import/' . $file['name'] ), 'lpie-import-file', 'nonce' ); ?>"><?php _e( 'Import', 'learnpress-import-export' ); ?></a>
									|
									<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=learnpress-import-export&tab=' . lpie_get_current_tab() . '&delete-import=' . $file['name'] ), 'lpie-delete-import-file', 'nonce' ); ?>"><?php _e( 'Remove', 'learnpress-import-export' ); ?></a>
								</p>
							</th>
							<td style="vertical-align: top;"><?php echo $m_time; ?></td>
							<td style="vertical-align: top;">
								<?php echo size_format( $file['size'] ); ?>
							</td>
						</tr>
					<?php } ?>
				<?php else: ?>
					<tr>
						<td colspan="3"><?php _e( 'No exported files', 'learnpress-import-export' ); ?></td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>

		</div>
	</div>
</form>