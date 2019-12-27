<?php
class WPCOM_Module_main_list extends WPCOM_Module {
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'latest-title' => array(
                    'name' => '默认Tab标题',
                    'desc' => '第一个默认Tab标题显示文案',
                    'value'  => '最新文章'
                ),
                'exclude' => array(
                    'name' => '排除分类',
                    'type' => 'cat-multi',
                    'desc' => '文章列表排除的分类，排除分类的文章将不显示在最新文章列表'
                ),
                'cats' => array(
                    'name' => 'Tab切换分类',
                    'type' => 'cat-multi-sort',
                    'desc' => '列表切换栏展示的文章分类，按勾选顺序排序'
                ),
                'type' => array(
                    'name' => '显示方式',
                    'type' => 's',
                    'o' => array(
                        '' => '默认列表',
                        'list' => '文章列表',
                        'image' => '图片列表',
                        'card' => '卡片列表',
                    )
                ),
                'cols' => array(
                    'name' => '每行显示',
                    'type' => 's',
                    'value'  => '3',
                    'filter' => 'type:image,type:card',
                    'o' => array(
                        '2' => '2篇',
                        '3' => '3篇',
                        '4' => '4篇',
                        '5' => '5篇'
                    )
                ),
                'per_page' => array(
                    'name' => '显示数量',
                    'desc' => '分页加载每页显示数量',
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
        parent::__construct('main-list', '文章主列表', $options, 'mti:view_list');
    }

    function template( $atts, $depth ){
        global $is_sticky;
        $is_sticky = 1;
        $cats = isset($atts['cats']) && $atts['cats'] ? $atts['cats'] : array();
        $cols = isset($atts['cols']) && $atts['cols'] ? $atts['cols'] : 3;
        $type = isset($atts['type']) && $atts['type'] ? $atts['type'] : 'default';
        $per_page = isset($atts['per_page']) && $atts['per_page'] ? $atts['per_page'] : get_option('posts_per_page');
        ?>
        <div class="sec-panel main-list main-list-<?php echo $type;?>">
            <div class="sec-panel-head">
                <ul class="list tabs j-newslist" data-type="<?php echo $type;?>" data-per_page="<?php echo $per_page;?>">
                    <li class="tab active">
                        <a data-id="0" href="javascript:;">
                            <?php
                            $latest = isset($atts['latest-title']) && $atts['latest-title'] ? $atts['latest-title'] : __('Latest Posts', 'wpcom');
                            echo $latest;
                            ?>
                        </a>
                    </li>
                    <?php if($cats){ foreach($cats as $cat){ ?>
                        <li class="tab"><a data-id="<?php echo $cat;?>" href="javascript:;"><?php echo get_cat_name($cat);?></a></li>
                    <?php } } ?>
                </ul>
            </div>
            <ul class="post-loop post-loop-<?php echo $type;?> cols-<?php echo $cols;?> tab-wrap clearfix active">
                <?php
                $exclude = isset($atts['exclude']) ? $atts['exclude'] : array();
                $arg = array(
                    'posts_per_page' => $per_page,
                    'ignore_sticky_posts' => 0,
                    'post_type' => 'post',
                    'post_status' => array( 'publish' ),
                    'category__not_in' => $exclude
                );
                $posts = new WP_Query($arg);
                if( $posts->have_posts() ) { while ( $posts->have_posts() ) { $posts->the_post(); ?>
                    <?php get_template_part( 'templates/loop' , $type ); ?>
                <?php } } wp_reset_postdata(); ?>
                <?php if($posts->max_num_pages>1){ ?>
                    <li class="load-more-wrap">
                        <a class="load-more j-load-more" href="javascript:;" data-exclude="<?php echo implode($exclude, ',');?>"><?php _e('Load more posts', 'wpcom');?></a>
                    </li>
                <?php } ?>
            </ul>
            <?php if($cats){ foreach($cats as $cat){ ?>
                <ul class="post-loop post-loop-<?php echo $type;?> cols-<?php echo $cols;?> tab-wrap clearfix"></ul>
            <?php } } ?>
        </div>
    <?php }
}
register_module( 'WPCOM_Module_main_list' );