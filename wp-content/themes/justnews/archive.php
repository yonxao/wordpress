<?php get_header();?>
    <div class="wrap container">
        <div class="main">
            <div class="sec-panel archive-list">
                <div class="sec-panel-head">
                    <h1><span><?php if( is_author() ) { ?><?php echo get_the_author(); ?>
                        <?php } elseif (is_day()) { ?><?php echo sprintf( __( 'Daily Archives: %s' , 'wpcom' ), get_the_date() ) ?>
                        <?php } elseif (is_month()) { ?><?php echo sprintf( __( 'Monthly Archives: %s' , 'wpcom' ), get_the_date(__( 'F Y', 'wpcom' )) ) ?>
                        <?php } elseif (is_year()) { ?><?php echo sprintf( __( 'Yearly Archives: %s' , 'wpcom' ), get_the_date(__( 'Y', 'wpcom' )) ) ?>
                        <?php } ?></span></h1>
                </div>
                <ul class="post-loop post-loop-default">
                    <?php while( have_posts() ) : the_post();?>
                        <?php get_template_part( 'templates/loop' , 'default' ); ?>
                    <?php endwhile; ?>
                </ul>
                <?php wpcom_pagination(5);?>
            </div>
        </div>
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
    </div>
<?php get_footer();?>