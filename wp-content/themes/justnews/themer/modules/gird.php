<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_gird extends WPCOM_Module {
    function __construct() {
        $options = array(
            array(
                'tab-name' => '常规设置',
                'columns' => array(
                    'name' => '栅格列数',
                    'type' => 'columns',
                    'desc' => '设置栅格的列数，然后在下面设置每列对应的宽度，页面采用12列计算，下面所有栅格相加等于12即可，超过12将会换行，小于12页面无法填满',
                    'value'  => array('6', '6')
                ),
                'offset' => array(
                    'name' => '栅格偏移#PC端',
                    'desc' => '栅格向右边偏移的格数，例如需要添加一个居中的8格宽度栅格，则此处可以偏移2格',
                    'value'  => '0'
                ),
                'offset_mobile' => array(
                    'name' => '栅格偏移#手机端',
                    'value'  => '0'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'padding' => array(
                    'name' => '左右内边距',
                    'desc' => '单位：px，通过修改左右内边距可以改变栅格左右之间的距离，设置为0则无边距，默认为15px，建议设置不超过15px'
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
        parent::__construct( 'gird', '栅格布局', $options, 'mti:view_column' );
    }

    function template($atts, $depth){
        $padding = isset($atts['padding']) ? $atts['padding'] : '';
        $padding = $padding!=='' ? $padding : (isset($atts['no-padding']) && $atts['no-padding']=='1' ? 0 : '');
        $mod_style = '';
        $row_style = 'style="';
        if($padding!==''){
            $mod_style = 'padding: 0 '.intval($padding).'px;';
            $row_style .= 'margin-left: -'.intval($padding).'px;';
            $row_style .= 'margin-right: -'.intval($padding).'px;';
        }
        $row_style .= '"'; ?>
        <div class="row" <?php echo $row_style;?>>
        <?php for($i=0;$i<count($atts['columns']);$i++){
            $class = 'j-modules-inner';
            if( $atts['columns'][$i] == '0'){
                $class .= ' hidden-md hidden-lg';
            }else{
                $class .= ' col-md-'.$atts['columns'][$i];
            }
            if($i==0 && isset($atts['offset']) && $atts['offset']) $class .= ' col-md-offset-'.$atts['offset'];
            if( isset($atts['columns_mobile']) && isset($atts['columns_mobile'][$i]) ){
                if( $atts['columns_mobile'][$i] == '0'){
                    $class .= ' hidden-sm hidden-xs';
                }else{
                    $class .= ' col-sm-' . $atts['columns_mobile'][$i] . ' col-xs-' . $atts['columns_mobile'][$i];
                }
                if($i==0 && isset($atts['offset_mobile']) && $atts['offset_mobile'])
                    $class .= ' col-sm-offset-'.$atts['offset_mobile'].' col-xs-offset-'.$atts['offset_mobile'];
            } ?>
            <div class="<?php echo $class;?>" style="<?php echo $mod_style;?>">
                <?php if(isset($atts['girds']) && isset($atts['girds'][$i])){ foreach ($atts['girds'][$i] as $v) {
                    $v['settings']['modules-id'] = $v['id'];
                    $v['settings']['parent-id'] = $atts['modules-id'];
                    do_action('wpcom_modules_' . $v['type'], $v['settings'], $depth+1);
                } } ?>
            </div>
        <?php } ?>
        </div>
    <?php }
}

register_module( 'WPCOM_Module_gird' );