<?php
get_header();
global $options, $post;
$sidebar = get_post_meta( $post->ID, 'wpcom_sidebar', true );
$sidebar = !(!$sidebar && $sidebar!=='');
$body_classes = implode(apply_filters( 'body_class', array() ), ' ');
$hide_title = 0;
if(preg_match('/(qapress|member-profile|member-account|member-login|member-register|member-lostpassword)/i', $body_classes)) {
    $hide_title = 1;
}
$class = $sidebar ? 'main' : 'main main-full';
$page_template = get_post_meta($post->ID, '_wp_page_template', true);
if($page_template == 'page-fullwidth.php' || $page_template == 'page-fullnotitle.php'){
    update_post_meta($post->ID, '_wp_page_template', 'default');
    update_post_meta($post->ID, 'wpcom_sidebar', '0');
}
if($page_template == 'page-notitle.php' || $page_template == 'page-fullnotitle.php'){
    update_post_meta($post->ID, '_wp_page_template', 'default');
}?>
    <div class="wrap container">
        <div class="<?php echo esc_attr($class);?>">
            <?php if( !$hide_title && isset($options['breadcrumb']) && $options['breadcrumb']=='1' ) wpcom_breadcrumb('breadcrumb entry-breadcrumb'); ?>
            <?php while( have_posts() ) : the_post();?>
                <article id="post-<?php the_ID(); ?>" <?php post_class();?>>
                    <div class="entry">
                        <?php if(!$hide_title){ ?>
                            <div class="entry-head">
                                <h1 class="entry-title"><?php the_title();?></h1>
                            </div>
                        <?php } ?>
                        <div class="entry-content clearfix">
                            <?php the_content();?>
                        </div>
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