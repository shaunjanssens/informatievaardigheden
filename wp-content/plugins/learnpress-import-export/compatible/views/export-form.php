<?php
$exporter = LPR_Export::instance();
$plugins = $exporter->get_enable_plugins();
?>
<div class="wrap">
    <h2><?php _e( 'Export', 'learnpress-import-export' );?></h2>
    <?php if( $plugins ):?>
    <div class="updated">
        <p><?php _e( 'We detected that some of lms systems is activated on your site and we can export their courses to import into LearnPress', 'learnpress-import-export' );?></p>
        <p><?php _e( 'Please select the lms system you want to exports their courses', 'learnpress-import-export' );?></p>
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
    <button class="button button-primary"><?php _e( 'Export', 'learnpress-import-export' );?></button>
    </form>
    <?php endif;?>
</div>