<div class="learn-press-question question-fill-in-blank" id="learn-press-question-<?php echo $this->id; ?>" data-type="<?php echo str_replace( '_', '-', $this->type ); ?>" data-id="<?php echo $this->id; ?>">
	<textarea name="learn_press_question[<?php echo $this->id; ?>][0]"><?php echo esc_html( $this->passage ); ?></textarea>
	<div class="description">
		<?php _e( 'Use shortcode <code>[fib fill="WORD"]</code> to insert a blank placeholder with fill text is <code>WORD</code>', 'learnpress-fill-in-blank' ); ?>
		<?php if ( !preg_match( $this->_shortcode_pattern, $this->passage ) ): ?>
			<p style="color:#FF0000;"><?php esc_html_e( 'There is not any placeholder for filling', 'learnpress' ); ?></p>
		<?php endif; ?>
	</div>
</div>