<?php get_header() ?>

    <!--数据处理-->
<?php
global $wp_the_query, $posts;
$course = $wp_the_query->queried_object;
//var_dump($course);
//var_dump($posts);

$lesson_list = array();
for ($i = 0; $i < sizeof($posts); $i++) {
    $lesson = array();
    $lesson['name'] = $posts[$i]->post_title;
    $lesson['url'] = get_post_meta($posts[$i]->ID, 'lesson_url', true);
    array_push($lesson_list, $lesson);
}

$lesson_list = array_sort($lesson_list, 'name', 'asc', 'yes');
//var_dump($lesson_list);

function array_sort($arr, $keys, $orderby = 'asc', $key = 'no')
{
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($orderby == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        if ($key == 'yes') {
            $new_array[$k] = $arr[$k];
        } else {
            $new_array[] = $arr[$k];
        }
    }
    return $new_array;
}

?>

    <script>

        var activated_lesson = "li_1";

        function openLesson(id) {
            // 获取到课程连接
            var path = $('#' + id).attr('href');
            // var path2 = document.getElementById(id).href;
            // alert(path);
            // alert(path2);
            document.getElementById("video").setAttribute("src", path);
            // alert(id);
            // var ulid = id.substring(id.charAt("_"),id.length);
            // alert(ulid);
            changeActiveLesson(id);
        }

        function changeActiveLesson(item) {

            document.getElementById(activated_lesson).setAttribute("class", "");
            document.getElementById(item).setAttribute("class", "lesson-activated");
            activated_lesson = item;
        }
    </script>

    <style>

        .lesson-container {
            margin: 15px auto;
            /*width: 100%;*/
            height: 560px;
            text-align: center;
            width: 1200px;
            /*背景颜色*/
            background-color: #f1f1f1;

        }

        .lesson-list {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 20%;
            float: left;
        }

        .video-player {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 80%;
            float: left;
            background-color: #282923;
        }

        .course-name {
            background-color: #ccc;
            /*text-align: left;*/
            padding-left: 3px;
            font-size: 20px;
        }

        .lesson-list ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #f1f1f1;
            text-align: left;
        }

        .lesson-list li a {
            display: block;
            color: #000;
            padding: 8px 16px;
            text-decoration: none;
        }

        .lesson-list li a.active {
            background-color: #4CAF50;
            color: white;
        }

        .lesson-activated {
            background-color: #4CAF50;
            color: white;
        }

        .lesson-list li a:hover:not(.active) {
            background-color: #555;
            color: white;
        }


    </style>

    <div class="lesson-container">


        <div class="lesson-list">

            <div id="course-name" class="course-name"><b><?php echo $course->name ?></b></div>

            <ul>
                <?php
                $i = 1;
                $lesson_1_url = "";
                foreach ($lesson_list as $lesson) {
                    if ($i == 1) {
                        $lesson_1_url = $lesson['url'];
                    }
                    ?>
                    <li <?php echo " id=\"li_$i\"";
                    if ($i == 1) {
                        echo " class=\"lesson-activated\"";
                    }
                    ?>>
                        <a href="<?php echo $lesson['url'] ?>" id="lesson_<?php echo $i ?>"
                           onclick="openLesson(this.id);return false;">
                            <?php echo $lesson['name'] ?>
                        </a>
                    </li>
                    <?php $i++;
                } ?>
            </ul>
        </div>

        <div class="video-player">
            <video id="video" src="<?php echo $lesson_1_url ?>" width="100%" height="100%" controls="true"
                   controlslist="nodownload"></video>
        </div>


    </div>


<?php get_footer() ?>