<?php
$tabs = array(
	'export-course' => __( 'Export', 'learnpress-import-export' ),
	'import-course' => __( 'Import', 'learnpress-import-export' )
);
$current_tab = lpie_get_current_tab();
?>
<div class="wrap">
	<h1><?php _e( 'Import/Export', 'learnpress-import-export' );?></h1>
	<h2 class="nav-tab-wrapper lp-nav-tab-wrapper">
		<?php foreach( $tabs as $slug => $title ): ?>
			<a href="<?php echo admin_url( 'admin.php?page=learnpress-import-export&tab=' . $slug );?>" class="nav-tab<?php echo $slug == $current_tab ? ' nav-tab-active' : '';?>"><?php echo $title;?></a>
		<?php endforeach; ?>
	</h2>
	<div id="poststuff" class="learn-press-export-import">
		<?php include dirname( __FILE__ ) . "/{$current_tab}.php";?>
	</div>

	<?php /*if( $plugins ):?>
		<div class="updated">
			<p><?php _e( 'We detected that some of lms systems is activated on your site and we can export their courses to import into LearnPress', 'learnpress_import_export' );?></p>
			<p><?php _e( 'Please select the lms system you want to exports their courses', 'learnpress_import_export' );?></p>
		</div>
		<form method="post">
			<ul>
				<?php foreach( $plugins as $plugin_file => $details ):?>
					<li>
						<label>
							<input name="lsm_export[]" type="checkbox" <?php disabled( $details['status'] != 'activated' ? true : false, true );?> value="<?php echo $details['slug'];?>" />
							<?php echo $details['Name'];?>
						</label>
					</li>
				<?php endforeach;?>
			</ul>
			<button class="button button-primary"><?php _e( 'Export', 'learnpress_import_export' );?></button>
		</form>
	<?php endif;*/?>
</div>