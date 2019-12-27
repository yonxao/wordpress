<?php get_header(); ?>

<!--处理数据-->
<?php
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

// 课程标签数组
$course_tag = array();

// 课程信息和标签整合起来的数组
$course_cat_info_array = array();

// 获取所有课程分类，遍历获取课程分类的标签
for ($i = 0; $i < sizeof($course_cat_terms); $i++) {

    $course_cat = $course_cat_terms[$i];
    $id = $course_cat->term_id;

    // 将获取到的课程标签转换为数组后 ,合并到所有课程标签数组中
    $tags = get_term_meta($id, 'course_tag', true);
    $course_tag = array_merge($course_tag, explode(',', $tags));

    $course_cat_info = array();
    $course_cat_info['term_id'] = $id;
    $course_cat_info['category_link'] = get_category_link($id);
    $course_poster_url = get_term_meta($id, 'course_poster', true);
    $course_cat_info['course_poster'] = $course_poster_url['guid'];
    $course_cat_info['name'] = $course_cat_terms[$i]->name;;
    $course_cat_info['count'] = $course_cat_terms[$i]->count;
    $course_cat_info['course_author'] = get_term_meta($id, 'course_author', true);
    $course_cat_info['tags'] = strtoupper($tags);
    array_push($course_cat_info_array, $course_cat_info);

}

// 课程标签转大写后去重排序
array_walk($course_tag, function (&$v) {
    $v = strtoupper($v);
});
$unique_tag = array_unique($course_tag);
sort($unique_tag);

//var_dump($course_cat_info_array);
//var_dump($unique_tag);
//exit;
?>

<!--定义样式-->
<style>
    ul {
        margin-bottom: 0;
    }

    li {
        list-style: none; /*去除li前面的列表项标记类型*/
    }

    .row {
        margin-left: 0;
        padding-left: 0;
    }

    .col-md-3 {
        margin-left: 0;
        padding-left: 0;
    }


    .course-box {
        background-color: #fff;
        -webkit-box-shadow: 2px 6px 5px #ebeaea;
        box-shadow: 2px 6px 5px #ebeaea;
        padding: 0;
        border-radius: 0;
        border: solid 1px #e4e4e4;
        position: relative;
        /*min-height: 215px;*/
        min-height: 240px;
    }

    .course-box:hover {
        -webkit-box-shadow: 5px 8px 5px #d3d2d3;
        box-shadow: 5px 8px 5px #d3d2d3;
        cursor: pointer;
    }

    .thumbnail {
        display: block;
        padding: 4px;
        margin-bottom: 20px;
        line-height: 1.42857143;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        -webkit-transition: border .2s ease-in-out;
        -o-transition: border .2s ease-in-out;
        transition: border .2s ease-in-out;
    }

    .thumbnail .caption {
        padding: 9px;
        color: #333;
    }

    .course-box h4 {
        color: #333;
        margin-bottom: 8px;
        height: 45px;
        overflow: hidden;
    }

    .course-box .meta {
        color: #666;
    }

    .fa {
        display: inline-block;
        font: normal normal normal 14px/1 FontAwesome;
        font-size: inherit;
        text-rendering: auto;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .category-list {
        background: 0 0;
        padding: 20px 0;
        border-bottom: solid 1px #e4e4e4;
        float: left;
        width: 100%;
        margin-bottom: 15px;
    }

    .category-list li {
        color: #000;
        margin-bottom: 10px;
    }

    .list-inline > li {
        display: inline-block;
        padding-right: 5px;
        padding-left: 5px;
    }

    .category-list a {
        color: #333;
        cursor: pointer;
    }

    .category-list a:hover, a:focus {
        color: #008CEE;
        text-decoration: none;
    }

    /*.category-list li a:active {*/
        /*color: #FFF;*/
        /*background: #57A300;*/
        /*padding: 5px 10px;*/
    /*}*/

    .tag-active {
        color: #FFF !important;
        background: #57A300;
        padding: 5px 10px;
    }

    .course-container {
        /**/
        width: 1200px;
        /*背景颜色*/
        background-color: #fff;
        margin: 0 auto;
        margin-top: 15px;
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 4px;
    }

</style>

<!--ajax-->
<script>

    var atctied_tag = "all";

    var ajaxurl = '<?php echo admin_url('admin-ajax.php')?>';

    function selectCourseCatByCourseTag(tagValue) {

        // 必须使用以下里面才能正常使用jquery
        jQuery(document).ready(function ($) {
            var data = {
                tagValue: tagValue,
                // 这里尤为重要，action的参数要和请求的函数名一致
                action: 'get_ajax_video'
            };
            $.post(ajaxurl, data, function (data) {

                // debugger;
                // var jsonObjData = JSON.parse(data);
                // alert(JSON.stringify(data));//alert json对象
                console.log(data);
                // 调用一个函数，将Dom的内容用请求来的数据替换
                changeCourseList(data);

                // 调用一个函数，修改标签激活状态
                changeActiveTag(tagValue);
            });
        });
    }

    function changeCourseList(data) {
        // alert(JSON.stringify(data));
        // alert(document.getElementById('course_list').innerHTML);
        // json对象转数组
        var ulHtml = "";
        for (var i = 0; i < data.length; i++) {
            ulHtml += "<li class=\"col-md-3\"> <div class=\"thumbnail course-box\"> <a target=\"_blank\" href=\" "
                + data[i]['category_link']
                + "\"><img src=\""
                + data[i]['course_poster']
                + "\" style=\"height: 154px;width: 100%\"> </a> <div class=\"caption\"> <h4> <a href=\" "
                + data[i]['category_link'] + "\" target=\"_blank\">"
                + data[i]['name']
                + "</a> </h4> <div class=\"meta\"> <span class=\"length\"><i class=\"fa fa-clock-o\"></i> "
                + data[i]['count']
                + "课时 </span> <span class=\"teacher pull-right\"> 授课讲师："
                + data[i]['course_author']
                + "</span> </div> </div> </li>"
            ;
            // alert(data[i]['tags']);
        }
        document.getElementById('course_list').innerHTML = ulHtml;
    }

    function changeActiveTag(tagValue) {

        document.getElementById(atctied_tag).setAttribute("class", "");
        document.getElementById(tagValue).setAttribute("class", "tag-active");
        atctied_tag = tagValue;
    }

</script>

<!--整个输出内容div-->
<div class="course-container">

    <!--课程标签-->
    <div class="category-list mt30">

        <div class="pull-left" style="width: 8%">课程标签：</div>

        <ul class="list-inline pull-left" style="width: 90%;">
            <li>
                <a onclick="selectCourseCatByCourseTag('全部')" class="tag-active" id="all"> 全部 </a>
            </li>

            <?php foreach ($unique_tag as $v) { ?>
                <li>
                    <a id="<?php echo $v; ?>" onclick="selectCourseCatByCourseTag(this.innerText)">
                        <?php echo $v; ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>

    <!--课程列表-->
    <div>
        <ul class="row" id="course_list">
            <?php foreach ($course_cat_info_array as $course_cat_info) { ?>

                <li class="col-md-3">
                    <div class="thumbnail course-box">

                        <a target="_blank" href="<?php echo $course_cat_info['category_link']; ?>">
                            <img src="<?php echo $course_cat_info['course_poster']; ?>"
                                 alt="<?php echo $course_cat_info['name']; ?>"
                                 style="height: 154px;width: 100%"
                            >
                        </a>

                        <div class="caption">
                            <h4>
                                <a href="<?php echo $course_cat_info['category_link']; ?>" target="_blank">
                                    <?php echo $course_cat_info['name']; ?>
                                </a>
                            </h4>

                            <div class="meta">
                                <span class="length"><i class="fa fa-clock-o"></i>
                                    <?php echo $course_cat_info['count']; ?>课时
                                </span>

                                <span class="teacher pull-right">
                                    授课讲师：<?php echo $course_cat_info['course_author']; ?>
                                </span>
                            </div>
                        </div>

                    </div>
                </li>

            <?php } ?>
        </ul>
    </div>
</div>


<?php get_footer(); ?>
