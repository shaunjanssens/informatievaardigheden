<ul class="form-options">
	<li>
		<label>
			<?php _e( 'Course date', 'learnpress-import-export' ); ?>
		</label>
		<input type="checkbox" name="update_date" value="1" />
		<p class="description">
			<?php _e( 'Set the date of course to current time.', 'learnpress-import-export' ); ?>
		</p>
	</li>
	<li>
		<label>
			<?php _e( 'Check duplicate', 'learnpress-import-export' ); ?>
		</label>
		<input type="checkbox" name="check_duplicate" value="1" />
		<p class="description">
			<?php _e( 'No import if already existing a course with the slug is same.', 'learnpress-import-export' ); ?>
		</p>
	</li>
	<?php if ( $instructors = lpie_get_import_instructors( 'import/' . basename( $this->_import_data['file']['file'] ) ) ) { ?>
		<li>
			<label><?php _e( 'Assign instructors', 'learnpress-import-export' ); ?></label>
			<table class="wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th><?php _e( 'Instructor', 'learnpress-import-export' ); ?></th>
					<th><?php _e( 'Assign to', 'learnpress-import-export' ); ?></th>
					<th><?php _e( 'Create new', 'learnpress-import-export' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $instructors as $instructor ) { ?>
					<tr>
						<th><?php printf( '%s (%s/%s) ', $instructor['author_login'], $instructor['author_email'], $instructor['author_display_name'] ); ?></th>
						<td>
							<?php wp_dropdown_users( array( 'name' => "map_authors[" . $instructor['author_id'] . "]", 'class' => "instructor_map", 'multi' => true, 'show_option_all' => __( 'Create new Instructor', 'learnpress-import-export' ) ) ); ?>
						</td>
						<td>
							<input type="text" name="new_authors[<?php echo $instructor['author_id']; ?>]" class="new_instructor" />
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</li>
	<?php } ?>
</ul>
<input name="import-file" type="hidden" value="<?php echo learn_press_get_request( 'import-file' ); ?>" />
<input name="nonce" type="hidden" value="<?php echo learn_press_get_request( 'nonce' ); ?>" />
<input name="tab" type="hidden" value="import-course" />
<input name="step" type="hidden" value="2" />
<br />
<p>
	<button class="button button-primary"><?php _e( 'Import', 'learnpress-import-export' ); ?></button>
	&nbsp;&nbsp;<a href="<?php echo admin_url( 'admin.php?page=learnpress-import-export' ); ?>"><?php _e( 'Cancel', 'learnpress-import-export' ); ?></a>
</p>