<?php
class WPCOM_Module_links extends WPCOM_Module {
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'title' => array(
                    'name' => '模块标题',
                    'value'  => '友情链接'
                ),
                'sub-title' => array(
                    'name' => '副标题'
                ),
                'more-title' => array(
                    'name' => '更多标题',
                    'value' => '申请友链'
                ),
                'more-url' => array(
                    'name' => '更多链接'
                ),
                'cat' => array(
                    'name' => '链接分类',
                    'desc' => '请选择链接分类，不选择则显示所有公开链接',
                    'type' => 'cat-single',
                    'tax' => 'link_category'
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
        parent::__construct('links', '友情链接', $options, 'mti:link');
    }

    function template( $atts, $depth ){
        $link_cat = isset($atts['cat']) && $atts['cat'] ? $atts['cat'] : '';
        $bookmarks = get_bookmarks(array('limit' => -1, 'category' => $link_cat, 'category_name' => '', 'hide_invisible' => 1, 'show_updated' => 0 ));?>
        <div class="sec-panel">
            <?php if(isset($atts['title']) && $atts['title']){ ?>
                <div class="sec-panel-head">
                    <h2><span><?php echo $atts['title'];?></span> <small><?php echo $atts['sub-title'];?></small> <?php if($atts['more-url'] && $atts['more-title']){?><a href="<?php echo esc_url($atts['more-url']);?>" target="_blank" class="more"><?php echo $atts['more-title'];?></a><?php } ?></h2>
                </div>
            <?php } ?>
            <div class="sec-panel-body">
                <div class="list list-links">
                    <?php foreach($bookmarks as $link){ if($link->link_visible=='Y'){ ?>
                        <a <?php if($link->link_target){?>target="<?php echo $link->link_target;?>" <?php } ?><?php if($link->link_description){?>title="<?php echo esc_attr($link->link_description);?>" <?php } ?>href="<?php echo $link->link_url?>"<?php if($link->link_rel){?> rel="<?php echo $link->link_rel;?>"<?php } ?>><?php echo $link->link_name?></a>
                    <?php }} ?>
                </div>
            </div>
        </div>
    <?php }
}
register_module( 'WPCOM_Module_links' );