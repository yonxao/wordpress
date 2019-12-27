<?php
defined( 'ABSPATH' ) || exit;

if( !function_exists('wpcom_related_post') ) :
function wpcom_related_post( $showposts = 10, $title = '相关文章', $tpl = '', $class = '',$img = false ){
    if( $showposts == '0' ) return false;
    global $post, $options;

    $args = array(
        'post__not_in' => array($post->ID),
        'showposts' => $showposts,
        'ignore_sticky_posts' => 1,
        'orderby' => 'rand'
    );

    if(isset($options['related_by']) && $options['related_by']=='1'){
        $tag_list = array();
        $tags = get_the_tags($post->ID);
        if($tags) {
            foreach ($tags as $tag) {
                $tid = $tag->term_id;
                if (!in_array($tid, $tag_list)) {
                    $tag_list[] = $tid;
                }
            }
        }
        $args['tag__in'] = $tag_list;
    }else{
        $cat_list = array();
        $categories = get_the_category($post->ID);
        if($categories) {
            foreach ($categories as $category) {
                $cid = $category->term_id;
                if (!in_array($cid, $cat_list)) {
                    $cat_list[] = $cid;
                }
            }
        }
        $args['category'] = join(',', $cat_list);
    }

    if($img) $args['meta_query'] = array(array('key' => '_thumbnail_id'));

    $posts = get_posts($args);
    $output = '';
    if( $posts ) {
        $output .= '<h3 class="entry-related-title">'.$title.'</h3>';
        $output .=  '<ul class="entry-related clearfix '.$class.'">';
        foreach ( $posts as $post ) { setup_postdata($post);
            if ( $tpl ) {
                ob_start();
                get_template_part( $tpl );
                $output .= ob_get_contents();
                ob_end_clean();
            } else {
                $output .= '<li><a href="' . get_the_permalink() . '" title="' . esc_attr(get_the_title()) . '">' . get_the_title() . '</a></li>';
            }
        }
        $output = str_replace(array('<h2 ', '</h2>'), array('<h4 ', '</h4>'), $output);
        $output .= '</ul>';
    }
    wp_reset_postdata();
    echo $output;
}
endif;