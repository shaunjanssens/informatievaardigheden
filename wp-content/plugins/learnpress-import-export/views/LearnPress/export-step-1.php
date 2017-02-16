<?php
$courses     = $this->get_courses();
$selected    = !empty( $_REQUEST['courses'] ) ? $_REQUEST['courses'] : array();
$all_courses = array();
?>

<h3><?php _e( 'Select courses', 'learnpress-import-export' ); ?></h3>
<?php if ( $courses ): $course_ids = array(); ?>
	<table class="list-export-courses">
		<thead>
		<tr>
			<th><?php _e( 'Course', 'learnpress-import-export' ); ?></th>
			<th><?php _e( 'Author/Instructor', 'learnpress-import-export' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $courses as $course ): $user = get_userdata( $course->post_author ); ?>
			<?php
			$course_ids[] = $course->ID;
			$is_checked   = in_array( $course->ID, $selected );
			if ( $is_checked ) {
				$all_courses[] = $course->ID;
			}
			?>
			<tr>
				<th>
					<label>
						<input type="checkbox" name="courses[]" value="<?php echo $course->ID; ?>" <?php checked( $is_checked ); ?> />
						<?php echo get_the_title( $course->ID ); ?>
					</label>
				</th>
				<td>
					<?php echo sprintf( '%s (%s)', $user->user_login, $user->user_email ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<th colspan="2">
				<label>
					<input type="checkbox" id="learn-press-import-export-select-all" <?php checked( !array_diff( $course_ids, $all_courses ) ); ?> />
					<?php _e( 'Select all', 'learnpress-import-export' ); ?>
				</label>
			</th>
		</tr>
		</tbody>
	</table>
<?php endif; ?>
<p class="lpie-error-message hide-if-js" id="lpie-no-course-selected">
	<?php _e( 'No course selected', 'learnpress-import-export' ); ?>
</p>
<p>
	<button class="button button-primary" id="button-export-next" disabled="disabled"><?php _e( 'Next', 'learnpress-import-export' ); ?></button>
	<button class="button" id="lpie-button-back-step"><?php _e( 'Back', 'learnpress-import-export' ); ?></button>
	<button class="button" id="lpie-button-cancel"><?php _e( 'Cancel', 'learnpress-import-export' ); ?></button>
</p>
