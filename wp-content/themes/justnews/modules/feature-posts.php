<?php
class WPCOM_Module_feature_posts extends WPCOM_Module {
    function __construct() {
        $options = array(
            array(
                'tab-name' => '常规设置',
                'from' => array(
                    'name' => '文章来源',
                    'type' => 's',
                    'value'  => '0',
                    'options' => array(
                        '0' => '使用文章推送',
                        '1' => '按文章分类'
                    )
                ),
                'cat' => array(
                    'name' => '文章分类',
                    'type' => 'cat-single',
                    'filter' => 'from:1',
                    'desc' => '如果文章来源选择的是[按文章分类]，请选择此项分类，否则可忽略'
                ),
                'posts_num' => array(
                    "name" => '文章数量',
                    "desc" => '调用的文章数量',
                    "value" => '5',
                ),
                'style' => array(
                    'name' => '显示风格',
                    'type' => 's',
                    'o' => array(
                        '' => '默认风格',
                        '1' => '风格1：单篇文章轮播+虚化背景',
                        '2' => '风格2：3篇文章一组轮播',
                        '3' => '风格3：4篇文章一组轮播',
                        '4' => '风格4：5篇文章一组轮播'
                    )
                ),
                'ratio' => array(
                    'f' => 'style:,style:1',
                    'name' => '显示宽高比',
                    'desc' => '固定格式：<b>宽度:高度</b>，例如<b>10:3</b>',
                    'value' => '10:3',
                )
            ),
            array(
                'tab-name' => '风格样式',
                'padding-top' => array(
                    'name' => '上内边距',
                    'desc' => '内间距为模块内容区域与边界的距离，单位建议为px。即 padding-top 值，例如： 10px',
                    'value'  => '0'
                ),
                'padding-bottom' => array(
                    'name' => '下内边距',
                    'desc' => '内间距为模块内容区域与边界的距离，单位建议为px。即 padding-bottom 值，例如： 10px',
                    'value'  => '0'
                ),
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
        parent::__construct('feature-posts', '推荐文章', $options, 'mti:view_module');
    }

    function style_inline_default( $atts ){
        $style = '';
        if(isset($atts['margin-top'])) $style .= 'margin-top: '.$atts['margin-top'].';';
        if(isset($atts['margin-bottom'])) $style .= 'margin-bottom: '.$atts['margin-bottom'].';';
        return $style;
    }

    function classes( $atts, $depth = 0 ){
        $style = isset($atts['style']) && $atts['style'] ? $atts['style'] : 0;
        $classes = $depth==0 ? 'container' : '';
        $classes .= ' feature-posts-style-' . $style;
        return $classes;
    }

    function style( $atts ){
        $style = isset($atts['style']) && $atts['style'] ? $atts['style'] : 0;
        $ratio = '';
        if($style==0||$style==1){
            $ratio = isset($atts['ratio']) && $atts['ratio'] ? $atts['ratio'] : '';
            $ratio = trim(str_replace('：', ':', $ratio));
            $ratio = explode(':', $ratio);
            if(isset($ratio[1]) && is_numeric($ratio[0]) && is_numeric($ratio[1])) $ratio = ($ratio[1] / $ratio[0]) * 100;
        }
        if( $style==0 && is_numeric($ratio)) { ?>
            #modules-<?php echo $atts['modules-id'];?> .post-loop-card .item:before{padding-top:<?php echo $ratio;?>%;}
        <?php }else if( $style==1 && is_numeric($ratio)){ ?>
            #modules-<?php echo $atts['modules-id'];?> .item-container{padding-top:<?php echo $ratio;?>%;}
        <?php }
    }

    function template( $atts, $depth ){
        global $feature_post, $feature_style;
        $feature_post= 1;
        $style = isset($atts['style']) && $atts['style'] ? $atts['style'] : 0;
        $feature_style = $style;
        $posts_num = isset($atts['posts_num']) && $atts['posts_num'] ? $atts['posts_num'] : 5;
        if(isset($atts['from']) && $atts['from']=='1'){
            $cat = isset($atts['cat']) && $atts['cat'] ? $atts['cat'] : 0;
            $posts = get_posts('posts_per_page='.$posts_num.'&cat='.$cat.'&post_type=post');
        }else{
            $posts = get_posts('posts_per_page='.$posts_num.'&meta_key=_show_as_slide&meta_value=1&post_type=post');
        }
        $wrap_attr = 'class="feature-posts-wrap wpcom-slider"';
        $wrap_style = '';
        if(isset($atts['padding-top'])) $wrap_style .= 'padding-top: '.$atts['padding-top'].';';
        if(isset($atts['padding-bottom'])) $wrap_style .= 'padding-bottom: '.$atts['padding-bottom'].';';
        $wrap_attr .= ' style="'.$wrap_style.'"'; ?>
        <div <?php echo $wrap_attr;?>>
            <ul class="post-loop post-loop-card cols-3 swiper-wrapper">
                <?php if($posts){
                    global $post;
                    if($style==3||$style==4){
                        $post_array = array();
                        $per = $style==3 ? 4 : 5;
                        $i = 0;
                        foreach ($posts as $post) {
                            $key = intval($i/$per);
                            if(!isset($post_array[$key])) $post_array[$key] = array();
                            $post_array[$key][] = $post;
                            $i++;
                        }
                        if($post_array){
                            foreach ($post_array as $array){
                                echo '<li class="swiper-slide">';
                                foreach ($array as $post){ setup_postdata($post);
                                    get_template_part('templates/loop', 'card');
                                }
                                echo  '</li>';
                            }
                        }
                    }else {
                        foreach ($posts as $post) { setup_postdata($post);
                            get_template_part('templates/loop', 'card');
                        }
                    }
                } wp_reset_postdata(); ?>
            </ul>
            <!-- Add Pagination -->
            <div class="swiper-pagination"></div>
            <!-- Add Navigation -->
            <div class="swiper-button-prev swiper-button-white"></div>
            <div class="swiper-button-next swiper-button-white"></div>
        </div>
        <script>
            jQuery(document).ready(function() {
                var _swiper_<?php echo $atts['modules-id'];?> = {
                    onInit: function (el) {
                        if (el.slides.length < 4) {
                            this.autoplay = false;
                            this.touchRatio = 0;
                            el.stopAutoplay();
                        }
                        $(el.container[0]).on('click', '.swiper-button-next', function () {
                            el.slideNext();
                        }).on('click', '.swiper-button-prev', function () {
                            el.slidePrev();
                        });
                        setTimeout(function () {
                            jQuery(window).trigger('scroll');
                        }, 800);
                    },
                    pagination: '.swiper-pagination',
                    slideClass: 'item',
                    paginationClickable: true,
                    simulateTouch: false,
                    loop: true,
                    autoplay: _wpcom_js.slide_speed ? _wpcom_js.slide_speed : 5000,
                    effect: 'slide',
                    onSlideChangeEnd: function(){
                        jQuery(window).trigger('scroll');
                    }
                };
                <?php if($style==2){?>
                _swiper_<?php echo $atts['modules-id'];?>.slidesPerView = 3;
                _swiper_<?php echo $atts['modules-id'];?>.spaceBetween = 0;
                _swiper_<?php echo $atts['modules-id'];?>.slidesPerGroup = 3;
                _swiper_<?php echo $atts['modules-id'];?>.breakpoints = {
                    767: {
                        slidesPerView: 1,
                        slidesPerGroup: 1,
                        spaceBetween: 1
                    }
                };
                <?php }else if($style==3||$style==4){ ?>
                _swiper_<?php echo $atts['modules-id'];?>.slideClass = 'swiper-slide';
                <?php } ?>
                new Swiper('#modules-<?php echo $atts['modules-id'];?> .feature-posts-wrap', _swiper_<?php echo $atts['modules-id'];?>);
            });
        </script>
    <?php }
}

register_module( 'WPCOM_Module_feature_posts' );