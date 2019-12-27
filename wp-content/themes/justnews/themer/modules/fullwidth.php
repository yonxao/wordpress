<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_fullwidth extends WPCOM_Module {
    function __construct() {
        $options = array(
            array(
                'tab-name' => '常规设置',
                'fluid' => array(
                    'name' => '固定宽度',
                    'type' => 't',
                    'desc' => '模块内容宽度固定，居中显示；否则内容宽度不固定，为100%',
                    'value'  => '1'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'bg-color' => array(
                    'name' => '背景颜色',
                    'type' => 'c',
                    'gradient' => 1
                ),
                'bg-video' => array(
                    'name' => '背景视频',
                    'type' => 'u',
                    'desc' => '可选，MP4格式视频，另外由于手机端无法自动播放，所以为兼容手机端建议再设置背景图片选项'
                ),
                'bg-image' => array(
                    'name' => '背景图片',
                    'type' => 'u',
                ),
                'bg-image-size' => array(
                    'name' => '背景铺满',
                    'type' => 's',
                    'desc' => '自动调整背景图片显示，使背景图片完全覆盖内容区域，选择了背景平铺请关闭此选项',
                    'value'  => '1',
                    'o' => array(
                        '0' => '不使用',
                        '1' => '全部铺满整个区域',
                        '2' => '按宽度100%铺满'
                    )
                ),
                'bg-image-repeat' => array(
                    'name' => '背景平铺',
                    'type' => 's',
                    'desc' => '选择了背景平铺的话，则需要关闭<b>背景铺满</b>选项',
                    'value'  => 'no-repeat',
                    'o' => array(
                        'no-repeat' => '不平铺',
                        'repeat' => '平铺',
                        'repeat-x' => '水平平铺',
                        'repeat-y' => '垂直平铺'
                    )
                ),
                'bg-image-position' => array(
                    'name' => '背景位置',
                    'type' => 's',
                    'desc' => '分别为左右对齐方式和上下对齐方式',
                    'value'  => 'center center',
                    'o' => array(
                        'left top' => '左 上',
                        'left center' => '左 中',
                        'left bottom' => '左 下',
                        'center top' => '中 上',
                        'center center' => '中 中',
                        'center bottom' => '中 下',
                        'right top' => '右 上',
                        'right center' => '右 中',
                        'right bottom' => '右 下',
                    )
                ),
                'bg-image-attachment' => array(
                    'name' => '背景固定',
                    'type' => 't',
                    'desc' => '背景图片固定，不跟随滚动，若开启则需要确保图片高度足够'
                ),
                'bg-image-shadow' => array(
                    'name' => '背景处理',
                    'type' => 's',
                    'desc' => '优化处理背景图片',
                    'value'  => '0',
                    'o' => array(
                        '0' => '不处理',
                        '1' => '暗化处理',
                        '2' => '亮化处理'
                    )
                ),
                'margin-top' => array(
                    'name' => '上外边距',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-top 值，例如： 10px',
                    'value'  => '0'
                ),
                'margin-bottom' => array(
                    'name' => '下外边距',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-bottom 值，例如： 10px',
                    'value'  => '0'
                ),
                'padding-top' => array(
                    'name' => '上内边距',
                    'desc' => '内间距为模块内容区域与边界的距离，单位建议为px。即 padding-top 值，例如： 10px',
                    'value'  => '20px'
                ),
                'padding-bottom' => array(
                    'name' => '下内边距',
                    'desc' => '内间距为模块内容区域与边界的距离，单位建议为px。即 padding-bottom 值，例如： 10px',
                    'value'  => '20px'
                )
            )
        );
        parent::__construct( 'fullwidth', '全宽模块', $options, 'mti:crop_landscape' );
    }

    function classes($atts, $depth){
        $classes = 'j-modules-wrap';
        if(isset($atts['bg-image']) && $atts['bg-image']) {
            global $options;
            if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
                $classes .= ' j-lazy';
            }
        }
        return $classes;
    }

    function style( $atts ){
        if( isset($atts['bg-color']) ) { ?>
            #modules-<?php echo $atts['modules-id'];?>{
            <?php if(isset($atts['bg-image-shadow']) && $atts['bg-image-shadow'])
                echo 'position: relative;';
            if(isset($atts['bg-color']) && $atts['bg-color'])
                echo WPCOM::gradient_color($atts['bg-color']);
            if(isset($atts['bg-image-repeat']) && $atts['bg-image-repeat'])
                echo 'background-repeat: '.$atts['bg-image-repeat'].';';
            if(isset($atts['bg-image-size']) && $atts['bg-image-size']) {
                if($atts['bg-image-size']=='1') echo 'background-size: cover;';
                if($atts['bg-image-size']=='2') echo 'background-size: 100% auto;';
            }
            if(isset($atts['bg-image-position']) && $atts['bg-image-position']) echo 'background-position: '.$atts['bg-image-position'].';';
            if(isset($atts['bg-image-attachment']) && $atts['bg-image-attachment']=='1')
                echo 'background-attachment: fixed;-webkit-backface-visibility: hidden;';
            ?>
            }
        <?php }
    }

    function style_inline($atts){
        $style = '';
        if(isset($atts['bg-image']) && $atts['bg-image']) {
            global $options;
            if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
                $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI.'/assets/images/lazy.png';
                $style .= 'background-image: url('.$lazy_img.');';
            }else{
                $style .= 'background-image: url('.$atts['bg-image'].');';
            }
        }
        return $style;
    }

    function _style_inline( $atts ){
        $style = '';
        $default = $this->style_inline_default( $atts );
        $more = $this->style_inline( $atts );
        if($default || $more) $style = 'style="'.$default.''.$more.'"';
        if(isset($atts['bg-image']) && $atts['bg-image']) {
            global $options;
            if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
                $style .= ' data-original="'.$atts['bg-image'].'"';
            }
        }
        return $style;
    }

    function template($atts, $depth) { ?>
        <?php if(isset($atts['bg-video']) && $atts['bg-video']) {
            $video_class = 'module-bg-video';
            if(isset($atts['bg-image-attachment']) && $atts['bg-image-attachment']=='1') $video_class .= ' module-bg-fixed';
            ?>
        <div class="<?php echo esc_attr($video_class);?>">
            <video muted autoplay loop playsinline preload="auto" src="<?php echo esc_url($atts['bg-video']);?>"></video>
        </div>
        <?php } if(isset($atts['bg-image-shadow']) && $atts['bg-image-shadow']=='1'){?><div class="module-shadow"></div><?php } ?>
        <?php if(isset($atts['bg-image-shadow']) && $atts['bg-image-shadow']=='2'){?><div class="module-shadow module-shadow-white"></div><?php } ?>
        <div class="j-modules-inner container<?php echo isset($atts['fluid']) && $atts['fluid']?'':'-fluid';?>"<?php     echo isset($atts['bg-image-shadow']) && $atts['bg-image-shadow'] ? ' style="position: relative;"':''; ?>>
            <?php if(isset($atts['modules']) && count($atts['modules'])){ foreach ($atts['modules'] as $module) {
                $module['settings']['modules-id'] = $module['id'];
                $module['settings']['parent-id'] = $atts['modules-id'];
                $module['settings']['fullwidth'] = isset($atts['fluid']) && $atts['fluid'] ? 0 : 1;
                do_action('wpcom_modules_' . $module['type'], $module['settings'], $depth+1);
            } } ?>
        </div>
    <?php }
}

register_module( 'WPCOM_Module_fullwidth' );