<?php
class WPCOM_Module_sidebar_layout extends WPCOM_Module{
    function __construct() {
        $sidebar = array('' => ' 默认边栏');
        if(isset($GLOBALS['options']['sidebar_id']) && $GLOBALS['options']['sidebar_id']) {
            foreach ($GLOBALS['options']['sidebar_id'] as $i => $id) {
                if($id && $GLOBALS['options']['sidebar_name'][$i]) {
                    $sidebar[$id] = $GLOBALS['options']['sidebar_name'][$i];
                }
            }
        }

        $options = array(
            array(
                'tab-name' => '常规设置',
                'sidebar' => array(
                    'name' => '显示边栏',
                    'type' => 's',
                    'desc' => '选择需要显示的边栏',
                    'o' => $sidebar
                ),
                'float' => array(
                    'name' => '边栏位置',
                    'type' => 's',
                    'desc' => '选择需要显示的边栏',
                    'value'  => 'right',
                    'o' => array(
                        'left' => '左边',
                        'right' => '右边',
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
        parent::__construct( 'sidebar-layout', '边栏布局', $options, 'columns' );
    }

    function classes( $atts, $depth = 0 ){
        $classes = 'container j-modules-wrap';
        return $classes;
    }

    function style( $atts ){
        if( isset($atts['float']) ) {
            $float = isset($atts['float']) && $atts['float'] ? $atts['float'] : 'right'; ?>
            #modules-<?php echo $atts['modules-id'];?> .main{float: <?php echo $float=='left'?'right':'left';?>;}
            #modules-<?php echo $atts['modules-id'];?> .sidebar{float: <?php echo $float;?>;}
        <?php }
    }

    function template($atts, $depth){ ?>
        <div class="main j-modules-inner">
            <?php if(isset($atts['modules']) && count($atts['modules'])){ foreach ($atts['modules'] as $module) {
                $module['settings']['modules-id'] = (isset($atts['parent-id']) && $atts['parent-id'] ? $atts['parent-id'].'-' : '') . $module['id'];
                $module['settings']['fullwidth'] = isset($atts['fluid']) && $atts['fluid'] ? 0 : 1;
                do_action('wpcom_modules_' . $module['type'], $module['settings'], $depth+1);
            } } ?>
        </div>
        <aside class="sidebar">
            <?php
            $sidebar = isset($atts['sidebar']) && $atts['sidebar'] ? $atts['sidebar'] : 'primary';
            dynamic_sidebar($sidebar);
            ?>
        </aside>
    <?php }
}

add_action('after_setup_theme', 'wpcom_sidebar_layout_init', 20);
function wpcom_sidebar_layout_init(){
    register_module( 'WPCOM_Module_sidebar_layout' );
}