<?php
// TEMPLATE NAME: 评论模板
global $options, $post;
wp_enqueue_script( 'comment-reply' );
$sidebar = get_post_meta( $post->ID, 'wpcom_sidebar', true );
$sidebar = !(!$sidebar && $sidebar!=='');
$hide_title = get_post_meta( $post->ID, 'wpcom_hide_title', true);
$class = $sidebar ? 'main' : 'main main-full';
get_header(); ?>
    <div class="wrap container">
        <div class="<?php echo esc_attr($class);?>">
            <?php if( !$hide_title && isset($options['breadcrumb']) && $options['breadcrumb']=='1' ) wpcom_breadcrumb('breadcrumb entry-breadcrumb'); ?>
            <?php while( have_posts() ) : the_post();?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry">
                        <?php if(!$hide_title){ ?>
                            <div class="entry-head">
                                <h1 class="entry-title"><?php the_title();?></h1>
                            </div>
                        <?php } ?>
                        <div class="entry-content clearfix">
                            <?php the_content();?>
                        </div>
                        <?php comments_template();?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <?php if( $sidebar ){ ?>
            <aside class="sidebar">
                <?php get_sidebar();?>
            </aside>
        <?php } ?>
    </div>
<?php get_footer();?>