<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_html_code extends WPCOM_Module {
    function __construct() {
        $options = array(
            array(
                'tab-name' => '常规设置',
                'code' => array(
                    'name' => 'HTML代码',
                    'type' => 'ta',
                    'code' => ''
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
                    'value'  => '0'
                )
            )
        );
        parent::__construct( 'html-code', 'HTML代码', $options, 'mti:code' );
    }

    function template($atts, $depth) {
        echo isset($atts['code']) ? $atts['code'] : '';
    }
}

register_module( 'WPCOM_Module_html_code' );