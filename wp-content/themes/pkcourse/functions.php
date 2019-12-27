<?php

/*
 * 注册自定义类型的内容（post）：课程课时
 */
function pk_custom_lesson()
{
    $labels = array(
        'menu_name' => '派学院',
        'name' => '课时管理',
        "singular_name" => '课时',
        'add_new' => '添加课时',
        'add_new_item' => '添加课时',
        'edit_item' => '编辑课时',
        'new_item' => '新课时',
        'all_items' => '课时',
        'view' => '查看课时',
        'search_item' => '搜索课时',
        'not_found' => '没有找到课时',
        'not_found_in_trash' => '回收站中没有找到课时'
    );
    $args = array(
        'public' => true,
        'labels' => $labels,
        'menu_position' => 5,
        // 编辑视频都有哪些组件,excerpt表示开启摘要,custom-fields表示自定义字段
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'reversion'),
        // 允许固定链接
        // 'has_archive'=>true,
        // 固定连接替换部分
        // 'rewrite'=>array('slug'=>'packcourse', 'with_front' => false)
    );
    // 注册自定义类型的内容 pk_custom_lesson
    register_post_type('course', $args);
}

add_action('init', 'pk_custom_lesson');
// 添加一个缩略图尺寸
// add_image_size('course_poster', 128, 180, true);


/**
 * 注册自定义分类大类（taxonomy）：课程
 */
function pk_custom_course()
{
    $labels = array(
        'menu_name' => '课程管理',
        'name' => '课程',
        'singular_name' => '课程',
        'search_items' => '搜索课程',
        'all_items' => '所有课程',
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => '编辑课程',
        'update_item' => '更新课程',
        'add_new_item' => '添加新课程',
        'new_item_name' => '新课程',
        'popular_items' => '热门课程',
        'separate_items_with_commas' => '使用逗号分隔不同的课程',
        'add_or_remove_items' => '添加或移除课程',
        'choose_from_most_used' => '从使用最多的课程里选择',
        'course_poster' => '课程海报'
    );

    $args = array(
        'public' => true,
        'labels' => $labels,
        // 允许子课程
        'hierarchical' => true
    );
    // 注册自定义分类大类（taxonomy）：课程
    register_taxonomy('course_cat', 'course', $args);
}

add_action('init', 'pk_custom_course');

function get_ajax_video()
{
    // 输出响应
    header("Content-Type: application/json");

    $tagValue = $_POST['tagValue'];

    // 查询课程的参数
    $args = array(
        'parent' => '',
        'orderby' => 'name',
        'order' => 'ASC',
        'hide_empty' => false,
        'hierarchical' => 1,
        'exclude' => '',
        'include' => '',
        'number' => '',
        'taxonomy' => 'course_cat',
        'pad_counts' => true
    );
    // 获取所有课程
    $course_cat_terms = get_categories($args);

    // 课程信息和标签整合起来的数组
    $course_cat_info_array = array();

    // 获取所有课程分类，遍历获取课程分类的标签
    for ($i = 0; $i < sizeof($course_cat_terms); $i++) {

        $course_cat = $course_cat_terms[$i];
        $id = $course_cat->term_id;

        $course_cat_info = array();
        // 将获取到的课程标签转换为数组后 ,合并到所有课程标签数组中
        $tags = get_term_meta($id, 'course_tag', true);
        $course_cat_info['tags'] = strtoupper($tags);
        if ($tagValue != 0 || $tagValue !== "全部") {
            if (!strstr(strtoupper($tags), $tagValue)) {
                continue;
            }
        }
        $course_cat_info['term_id'] = $id;
        $course_cat_info['category_link'] = get_category_link($id);
        $course_poster_url = get_term_meta($id, 'course_poster', true);
        $course_cat_info['course_poster'] = $course_poster_url['guid'];
        $course_cat_info['name'] = $course_cat_terms[$i]->name;;
        $course_cat_info['count'] = $course_cat_terms[$i]->count;
        $course_cat_info['course_author'] = get_term_meta($id, 'course_author', true);

        array_push($course_cat_info_array, $course_cat_info);
    }

    echo json_encode($course_cat_info_array);


    // 这个停止一定要写
    exit;
}

//函数名对应添加上，第一个表示用户没有登录时，这里全部都一样处理
add_action('wp_ajax_nopriv_get_ajax_video', 'get_ajax_video');
add_action('wp_ajax_get_ajax_video', 'get_ajax_video');






