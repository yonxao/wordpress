<?php
$term_id = get_queried_object_id();
$tpl = get_term_meta( $term_id, 'wpcom_tpl', true );
$sidebar = get_term_meta( $term_id, 'wpcom_sidebar', true );
$sidebar = !(!$sidebar && $sidebar!=='');
$banner = get_term_meta( $term_id, 'wpcom_banner', true );
if($tpl=='image-fullwidth') {
    $tpl = 'image';
    update_term_meta($cat, 'wpcom_tpl', $tpl);
    update_term_meta($cat, 'wpcom_sidebar', '0');
}
if ( ! ($tpl && locate_template('templates/loop-' . $tpl . '.php') != '' ) ) {
    $tpl = 'default';
}
$cols = 0;
if($tpl=='image'||$tpl=='card') {
    $cols = get_term_meta($term_id, 'wpcom_cols', true);
    $cols = $cols ? $cols : ($sidebar ? 3 : 4);
}
$class = $sidebar ? 'main' : 'main main-full';
get_header();
if($banner){
    $banner_height = get_term_meta( $term_id, 'wpcom_banner_height', true );
    $text_color = get_term_meta( $term_id, 'wpcom_text_color', true );
    $bHeight = intval($banner_height ? $banner_height : 300);
    $bColor = ($text_color ? $text_color : 0) ? ' banner-white' : '';
    ?>
    <div <?php echo wpcom_lazybg($banner, 'banner'.$bColor, 'height:'.$bHeight.'px;');?>>
        <div class="banner-inner">
            <h1><?php single_cat_title(); ?></h1>
            <div class="page-description"><?php echo term_description();?></div>
        </div>
    </div>
<?php } ?>
    <div class="container wrap">
        <div class="<?php echo esc_attr($class);?>">
            <div class="sec-panel sec-panel-<?php echo esc_attr($tpl);?>">
                <?php if($banner==''){ ?>
                    <div class="sec-panel-head">
                        <h1><span><?php single_cat_title(); ?></span></h1>
                    </div>
                <?php } ?>
                <ul class="post-loop post-loop-<?php echo esc_attr($tpl);?> cols-<?php echo $cols;?> clearfix">
                    <?php while( have_posts() ) : the_post();?>
                        <?php get_template_part( 'templates/loop' , $tpl ); ?>
                    <?php endwhile; ?>
                </ul>
                <?php wpcom_pagination(5);?>
            </div>
        </div>
        <?php if( $sidebar ){ ?>
            <aside class="sidebar">
                <?php get_sidebar();?>
            </aside>
        <?php } ?>
    </div>
<?php get_footer();?>