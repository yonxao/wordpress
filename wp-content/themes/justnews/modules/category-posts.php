<?php
class WPCOM_Module_category_posts extends WPCOM_Module {
    function __construct() {
        $options = array(
            array(
                'tab-name' => '常规设置',
                'title' => array(
                    'name' => '模块标题'
                ),
                'sub-title' => array(
                    'name' => '模块副标题'
                ),
                'cat' => array(
                    'name' => '文章分类',
                    'type' => 'cat-single'
                ),
                'style' => array(
                    'name' => '显示风格',
                    'type' => 's',
                    'o' => array(
                        '' => '默认列表',
                        'image' => '图文列表',
                        'card' => '卡片列表',
                        'list' => '文章列表'
                    )
                ),
                'cols' => array(
                    'name' => '每行显示',
                    'type' => 's',
                    'filter' => 'style:image,style:card',
                    'value'  => '4',
                    'o' => array(
                        '2' => '2篇',
                        '3' => '3篇',
                        '4' => '4篇',
                        '5' => '5篇'
                    )
                ),
                'number' => array(
                    'name' => '显示数量',
                    'value'  => '12'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'margin-top' => array(
                    'name' => '上外边距',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-top 值，例如： 10px',
                    'value'  => '0'
                ),
                'margin-bottom' => array(
                    'name' => '下外边距',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-bottom 值，例如： 10px',
                    'value'  => '20px'
                )
            )
        );
        parent::__construct('category-posts', '分类文章', $options, 'folder');
    }

    function template( $atts, $depth ){
        global $is_sticky;
        $is_sticky = 0;
        $cols = isset($atts['cols']) && $atts['cols'] ? $atts['cols'] : 4;
        $style = isset($atts['style']) && $atts['style'] ? $atts['style'] : 'default';
        $cat = isset($atts['cat']) ? $atts['cat'] : '';
        $cat_link = $cat ? get_category_link($cat) : '';
        $title = isset($atts['title']) ? $atts['title'] : '';
        if($title && $cat_link) $title = '<a href="'.$cat_link.'" target="_blank">'.$title.'</a>';
        $child_cats = $cat ? get_terms(array(
            'taxonomy' => 'category',
            'parent' => $cat
        )) : '';?>
        <div class="sec-panel">
            <?php if(isset($atts['title']) && $atts['title']){ ?>
                <div class="sec-panel-head">
                    <h2>
                        <span><?php echo $title; ?></span>
                        <small><?php echo $atts['sub-title']; ?></small>
                        <?php if($child_cats) {
                            echo '<div class="sec-panel-more">';
                            $i = 0;
                            foreach ($child_cats as $c){ if($i<3){ if($i>0) echo '<span class="split">/</span>'; ?>
                                <a href="<?php echo get_category_link($c->term_id);?>" target="_blank"><?php echo $c->name;?></a>
                            <?php $i++;}}
                            echo '</div>';
                        }else if($cat_link){ ?><a class="more" href="<?php echo $cat_link;?>" target="_blank"><?php _e('More', 'wpcom');?> <i class="fa fa-angle-right"></i></a><?php } ?>
                    </h2>
                </div>
            <?php } ?>
            <div class="sec-panel-body">
                <ul class="post-loop post-loop-<?php echo $style;?> cols-<?php echo $cols;?> clearfix">
                    <?php
                    $posts = get_posts('posts_per_page='.($atts['number']?$atts['number']:12).'&cat='.$atts['cat']);
                    if($posts){ global $post;foreach ( $posts as $post ) { setup_postdata( $post );?>
                        <?php get_template_part( 'templates/loop' , $style ); ?>
                    <?php } wp_reset_postdata(); } ?>
                </ul>
            </div>
        </div>
    <?php }
}

register_module( 'WPCOM_Module_category_posts' );