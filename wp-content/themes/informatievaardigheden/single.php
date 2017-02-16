<?php get_header(); ?>
<?php if ( have_posts() ) : ?>

<?php while ( have_posts() ) : the_post();  ?>

<h2><?php the_title(); ?></h2>

<?php the_content(); ?>

<?php comment_form(); ?>`

<?php endwhile; ?>

<?php else : ?>
    <p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif; ?>

<?php get_footer(); ?>
