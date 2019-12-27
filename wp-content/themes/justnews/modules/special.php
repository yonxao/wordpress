<?php
class WPCOM_Module_special extends WPCOM_Module {
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'title' => array(
                    'name' => '模块标题',
                    'value'  => '专题列表'
                ),
                'sub-title' => array(
                    'name' => '副标题'
                ),
                'more-title' => array(
                    'name' => '更多专题标题',
                    'value' => '全部专题'
                ),
                'more-url' => array(
                    'name' => '更多专题链接'
                ),
                'special' => array(
                    'name' => '显示专题',
                    'type' => 'cat-multi-sort',
                    'tax' => 'special',
                    'desc' => '选择需要展示的专题，按勾选顺序排序'
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
        parent::__construct('special', '专题展示', $options, 'mti:library_books');
    }

    function template( $atts, $depth ){ ?>
        <div class="sec-panel topic-recommend">
            <?php if(isset($atts['title']) && $atts['title']){ ?>
                <div class="sec-panel-head">
                    <h2><span><?php echo $atts['title'];?></span> <small><?php echo $atts['sub-title'];?></small> <?php if(isset($atts['more-url']) && $atts['more-url']){ ?><a href="<?php echo esc_url($atts['more-url']);?>" target="_blank" class="more"><?php $more_special = isset($atts['more-title']) && $atts['more-title'] ? $atts['more-title'] : __('All Topics', 'wpcom'); echo $more_special;?></a><?php } ?></h2>
                </div>
            <?php } ?>
            <div class="sec-panel-body">
                <ul class="list topic-list">
                    <?php if(isset($atts['special']) && $atts['special']){foreach($atts['special'] as $sp){
                        $term = get_term($sp, 'special');
                        $thumb = get_term_meta( $term->term_id, 'wpcom_thumb', true ); ?>
                        <li class="topic">
                            <a class="topic-wrap" href="<?php echo get_term_link($term->term_id);?>" target="_blank">
                                <div class="cover-container">
                                    <?php echo wpcom_lazyimg($thumb, $term->name);?>
                                </div>
                                <span><?php echo $term->name;?></span>
                            </a>
                        </li>
                    <?php } }?>
                </ul>
            </div>
        </div>
    <?php }
}
register_module( 'WPCOM_Module_special' );