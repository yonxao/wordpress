<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_my_module extends WPCOM_Module{
    function __construct(){
        $args = array( 'post_type' => 'page_module', 'posts_per_page' => -1 );
        $_modules = get_posts($args);
        $modules = new stdClass();
        if ( $_modules ) {
            foreach ( $_modules as $module ) {
                $modules->{$module->ID} = $module->post_title;
            }
        }
        $options = array(
            array(
                'tab-name' => '常规设置',
                'mid' => array(
                    'name' => '选择模块',
                    'type' => 's',
                    'o' => $modules
                ),
                'type' => array(
                    'name' => '添加方式',
                    'type' => 's',
                    'desc' => '<b>两者区别</b>：引用类似电脑的快捷方式，复制则会拷贝一份完全一样的模块；引用的方式原模块修改会跟着修改，复制的方式添加后是独立模块，不受原模块影响',
                    'o' => array(
                        '0' => '引用：模块的快捷方式',
                        '1' => '复制：作为模板复制'
                    )
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
        parent::__construct( 'my-module', '我的模块', $options, 'mti:view_quilt' );
    }
    function template($atts, $depth){
        if(isset($atts['mid']) && $atts['mid']) {
            $mds = get_post_meta($atts['mid'], '_page_modules', true);
            if ($mds && is_array($mds)) {
                if(isset($mds['type'])) $mds = array($mds);
                $_depth = $depth;
                foreach($mds as $md){
                    $md = apply_filters('wpcom_reset_module_id', $md, $atts['modules-id']);
                    $md['settings']['modules-id'] = $md['id'];
                    $md['settings']['parent-id'] = $atts['modules-id'];
                    if ($_depth == 0 && $md['type'] != 'fullwidth') {
                        $depth = $_depth - 1;
                    }else{
                        $depth = $_depth;
                    }
                    do_action('wpcom_modules_' . $md['type'], $md['settings'], $depth + 1);
                }
            }
        }
    }
    function display( $atts, $depth = 0 ){
        if ( $this->get_cached_module( $atts ) ) return;
        ob_start();
        $classes = 'modules-'.$this->id; ?>
        <section class="section wpcom-modules <?php echo $classes;?>" id="modules-<?php echo $atts['modules-id'];?>" <?php echo $this->_style_inline($atts); ?>>
            <?php $this->template($atts, $depth);?>
        </section>
        <?php echo $this->cache_module( $atts, ob_get_clean() );
    }
}

register_module( 'WPCOM_Module_my_module' );