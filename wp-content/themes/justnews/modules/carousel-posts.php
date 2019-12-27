<?php
class WPCOM_Module_carousel_posts extends WPCOM_Module {
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
                'per-view' => array(
                    'name' => '每栏显示',
                    'type' => 's',
                    'value'  => '4',
                    'o' => array(
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
        parent::__construct('carousel-posts', '轮播文章', $options, 'file-text');
    }

    function template( $atts, $depth ){
        global $is_sticky;
        $is_sticky = 0;?>
        <div class="sec-panel">
            <?php if(isset($atts['title']) && $atts['title']){ ?>
                <div class="sec-panel-head">
                    <div class="sec-panel-more">
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>
                    <h2><span><?php echo $atts['title']; ?></span> <small><?php echo $atts['sub-title']; ?></small></h2>
                </div>
            <?php } ?>
            <div class="sec-panel-body carousel-slider">
                <div class="j-slider-<?php echo $atts['modules-id'];?> cs-inner">
                    <ul class="swiper-wrapper post-loop post-loop-image cols-<?php echo isset($atts['per-view']) && $atts['per-view'] ? $atts['per-view'] : 4;?>">
                        <?php
                        $posts = get_posts('posts_per_page='.($atts['number']?$atts['number']:12).'&cat='.$atts['cat']);
                        if($posts){ global $post;foreach ( $posts as $post ) { setup_postdata( $post );?>
                            <?php get_template_part( 'templates/loop' , 'image' ); ?>
                        <?php } wp_reset_postdata(); } ?>
                    </ul>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function(){
                new Swiper('.j-slider-<?php echo $atts['modules-id'];?>', {
                    onInit: function(el){
                        el.slides.addClass('swiper-slide');
                        $(el.container[0]).closest('.wpcom-modules').on('click', '.swiper-button-next', function () {
                            el.slideNext();
                        }).on('click', '.swiper-button-prev', function () {
                            el.slidePrev();
                        });
                    },
                    paginationClickable: true,
                    autoplay: _wpcom_js.slide_speed ? _wpcom_js.slide_speed : 5000,
                    loop: true,
                    effect: 'slide',
                    slidesPerView: <?php echo (isset($atts['per-view']) && $atts['per-view'] ? $atts['per-view'] : 4)?>,
                    spaceBetween: 15,
                    slidesPerGroup: 2,
                    slideClass: 'item',
                    simulateTouch: false,
                    // Responsive breakpoints
                    breakpoints: {
                        480: {
                            slidesPerView: 2,
                            spaceBetween: 10
                        },
                        767: {
                            slidesPerView: 2,
                            spaceBetween: 10
                        },
                        1024: {
                            slidesPerView: 2,
                            spaceBetween: 15
                        }
                    },
                    onSlideChangeEnd: function(){
                        jQuery(window).trigger('scroll');
                    }
                });
            })
        </script>
    <?php }
}

register_module( 'WPCOM_Module_carousel_posts' );