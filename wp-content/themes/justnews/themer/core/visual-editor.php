<?php

class WPCOM_Visual_Editor{
    private static $_preview;
    function __construct(){
        add_action('admin_init', array($this, 'script_init'), 20);
        add_action('init', array($this, 'frontend_init'));
        add_action('admin_footer', array($this, 'editor_init'));
        add_action('admin_print_footer_scripts', array($this, 'footer_scripts'));
        add_action('visual_editor_preview_init' , array($this , 'live_preview' ));
        add_action('wpcom_render_page', array($this , 'render_page' ));
        add_action('wp_ajax_wpcom_page_modules', array($this, 'page_modules'));
        add_action('wp_ajax_wpcom_save_module', array($this, 'save_module'));
        add_action('wp_ajax_wpcom_get_module', array($this, 'get_module'));
        add_action('wp_ajax_wpcom_ve_save', array($this, 'save'));
        add_action('edit_form_after_title', array($this, 'remove_tinymce'));
        add_action('wp_head', array($this, 'modules_style'), 30 );
        add_action('admin_bar_menu', array($this, 'admin_bar_item'), 100 );

        add_filter('use_block_editor_for_post', array($this, 'block_editor'));
        add_filter('show_admin_bar', array($this, 'show_admin_bar'), 100);
        add_filter('admin_title', array($this, 'admin_title'));
        add_filter('wpcom_modules', array($this, 'save_module_options'));
        add_filter('wpcom_reset_module_id', array($this, 'reset_module_id'), 10, 2);
        add_filter('wpcom_exclude_post_metas', array($this, 'exclude_css_meta'));
    }
    function script_init(){
        if(!current_user_can('customize') || !$this->is_visual_editor()) return false;
        wp_enqueue_style('wpcom-visual-editor', FRAMEWORK_URI . "/assets/css/visual-editor.css", false, FRAMEWORK_VERSION, "all");
        wp_enqueue_script('wpcom-visual-editor', FRAMEWORK_URI . "/assets/js/visual-editor.js", array('jquery'), FRAMEWORK_VERSION, true);
        remove_all_actions('add_meta_boxes');
        remove_all_actions('do_meta_boxes');
        WPCOM::panel_script();
    }
    function editor_init(){
        if(!current_user_can('customize') || !$this->is_visual_editor()) return false;
        global $post;
        $url = add_query_arg(array(
            'post_id' => $post->ID,
            'visual-editor' => 'true',
            '_nonce' => wp_create_nonce( 'wpcom-ve-preview-' . $post->ID )
        ), get_preview_post_link()); ?>
        <header id="ve-header" class="visual-editor-header clearfix">
            <div class="ve-header-left">
                <a class="ve-header-item ve-header-close" href="<?php echo get_permalink($post->ID);?>">
                    <i class="material-icons">&#xe5cd;</i>
                </a>
                <div class="ve-header-item ve-header-add">
                    <i class="material-icons">&#xe148;</i>
                </div>
            </div>
            <div class="ve-header-right">
                <div class="ve-header-item ve-header-pc active">
                    <i class="material-icons">&#xe30b;</i>
                </div>
                <div class="ve-header-item ve-header-mobile">
                    <i class="material-icons">&#xe325;</i>
                </div>
                <?php if($post->post_type !== 'page_module'){ ?>
                <div class="ve-header-item ve-header-setting">
                    <i class="material-icons">&#xe8b8;</i>
                </div>
                <?php } ?>
                <div class="ve-header-submit loading">发布</div>
                <?php $nonce = wp_create_nonce('wpcom-ve-save-' . $post->ID);?>
                <input type="hidden" id="ve-nonce" value="<?php echo $nonce;?>">
            </div>
            <div id="ve-notice" class="ve-notice active"><span class="ve-notice-icon"><svg class="ve-notice-loading-svg" viewBox="22 22 44 44"><circle class="ve-notice-loading-circle" cx="44" cy="44" r="20.2" fill="none" stroke-width="3.6"></circle></svg></span><span>可视化编辑器加载中</span></div>
        </header>
        <div id="ve-wrapper" class="visual-editor-wrapper">
            <div class="ve-iframe-inner">
                <iframe class="ve-iframe" id="ve-iframe" src="<?php echo $url;?>"></iframe>
                <div class="ve-loading"><i class="dashicons-wpcom-logo"></i></div>
            </div>
        </div>
    <?php $this->module_panel();}

    function footer_scripts(){
        if(current_user_can('customize') && $this->is_visual_editor()) {
            global $wpcom_panel;
            if ($wpcom_panel && $wpcom_panel->get_demo_config()) {
                echo '<script>var _modules = ' . wp_json_encode($this->modules()) . ';var _page_modules = {};</script>';
            }
        }
    }

    function frontend_init(){
        if(current_user_can('customize') && $this->is_visual_page()){
            do_action('visual_editor_preview_init');
        }
    }

    function live_preview() {
        global $wpcom_panel;
        if( $wpcom_panel && $wpcom_panel->get_demo_config() ) {
            self::$_preview = 1;
            wp_enqueue_style("themer-customizer", FRAMEWORK_URI . "/assets/css/customizer.css", false, FRAMEWORK_VERSION, "all");
            wp_enqueue_script('themer-sortable', FRAMEWORK_URI . '/assets/js/sortable.min.js', array(), FRAMEWORK_VERSION, true);
            wp_enqueue_script('themer-customizer', FRAMEWORK_URI . '/assets/js/customizer.js', array('jquery', 'themer-sortable'), FRAMEWORK_VERSION, true);
            add_filter('get_post_metadata', array($this, 'module_preview_filter' ), 5, 3);
            add_filter('body_class', array($this, 'body_class'));
        }
    }

    function module_preview_filter($res, $object_id, $meta_key){
        if(isset($_POST['module-datas']) && $_POST['module-datas'] && isset($_GET['post_id']) && $_GET['post_id'] == $object_id){
            if($meta_key === '_page_modules') {
                $_data = base64_decode($_POST['module-datas']);
                $data = $_data ? json_decode($_data, true) : '';
                if ($data) $res = array($data);
            }else if($meta_key === 'wpcom_css' && isset($_POST['css'])) {
                $css = base64_decode($_POST['css']);
                $res = array($css?$css:'');
            }
        }
        return $res;
    }

    function module_panel(){ ?>
        <div id="wpcom-panel" class="wpcom-module-modal"><module-panel :ready="ready" /></div>
        <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
        <script>_panel_options = <?php echo $this->init_panel_options();?>;</script>
    <?php }

    function init_panel_options(){
        global $post;
        $res = array();
        $res['type'] = 'module';
        $res['ver'] = THEME_VERSION;
        $res['theme-id'] = THEME_ID;
        $res['framework_ver'] = FRAMEWORK_VERSION;
        $res = apply_filters('wpcom_module_panel_options', $res);
        $res['settings'] = array(
            'title' => $post->post_title,
            'home' => get_option('show_on_front')==='page' && get_option('page_on_front') == $post->ID,
            'css' => get_post_meta($post->ID, 'wpcom_css', true)
        );
        return wp_json_encode($res);
    }

    function page_modules(){
        $id = $_POST['id'];
        if($id && current_user_can( 'customize' ) && $modules = get_post_meta($id, '_page_modules', true)){
            if(is_array($modules) && isset($modules['type'])) $modules = array($modules);
            echo json_encode($modules);
        }else{
            echo '[]';
        }
        exit;
    }

    function save_module(){
        $res = array(
            'result' => -1
        );
        if(current_user_can( 'customize' )){
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $excerpt = isset($_POST['desc']) ? $_POST['desc'] : '';
            $module = isset($_POST['module']) ? $_POST['module'] : '';
            $module = json_decode(stripslashes($module), true);
            $post = array(
                'post_title' => $title,
                'post_excerpt' => $excerpt,
                'post_type' => 'page_module',
                'post_status' => 'publish'
            );
            $post_id = wp_insert_post($post);
            if(!is_wp_error($post_id) && $module){
                $this->save_page_modules($post_id, $module);
                $res['result'] = 0;
                $res['id'] = $post_id;
                $res['title'] = $title;
            }
        }
        echo wp_json_encode($res);
        exit;
    }

    function get_module(){
        $res = array(
            'result' => -1
        );
        if(isset($_POST['id']) && $_POST['id'] && current_user_can( 'customize' )){
            $mds = get_post_meta($_POST['id'], '_page_modules', true);
            $mid = isset($_POST['mid']) && $_POST['mid'] ? $_POST['mid'] : 0;
            if ($mds && is_array($mds)) {
                ob_start();
                if(isset($mds['type'])) $mds = array($mds);
                $data = array();
                foreach($mds as $i => $md){
                    $data[$i] = apply_filters('wpcom_reset_module_id', $md, $mid);
                    do_action( 'wpcom_modules_' . $data[$i]['type'], $data[$i]['settings'], 0);
                }
                $html = ob_get_contents();
                ob_end_clean();
                $res['data'] = $data;
                $res['html'] = $html;
                $res['result'] = 0;
            }
        }
        echo wp_json_encode($res);
        exit;
    }

    function render_page( $modules = null ){
        global $post;
        $render = $modules ? $modules : get_post_meta($post->ID, '_page_modules', true);
        if(!$render) $render = array();
        if(self::$_preview==1) echo '<div class="wpcom-container">';
        if(is_array($render) && count($render)>0) {
            if(isset($render['type'])) $render = array($render);
            foreach ($render as $v) {
                $v['settings']['modules-id'] = $v['id'];
                if($v['type']==='my_module') $v['type'] = 'my-module';
                do_action('wpcom_modules_' . $v['type'], $v['settings'], 0);
            }
        }else{
            echo '<div class="wpcom-inner"></div>';
        }
        if(self::$_preview==1) echo '</div>';
    }

    public function save(){
        $nonce = isset($_POST['nonce']) ? $_POST['nonce']: '';
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        $verify = wp_verify_nonce($nonce, 'wpcom-ve-save-' . $id);
        $res = array(
            'result' => -1,
            'msg' => '保存失败，请重试'
        );
        if(current_user_can('customize') && $verify){
            $data = isset($_POST['data']) ? $_POST['data'] : '';
            $data = json_decode(stripslashes($data), true);
            $settings = isset($_POST['settings']) ? $_POST['settings'] : '';
            $settings = json_decode(stripslashes($settings), true);
            $this->save_page_modules($id, $data);
            if(isset($settings['home'])){
                if($settings['home']) { // 设为首页
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $id);
                }else if(get_option('show_on_front')==='page' && get_option('page_on_front') == $id){
                    // 不设为首页，需要判断之前是否是首页，是的话则取消
                    update_option('page_on_front', '');
                }
            }
            if(isset($settings['title']) && $settings['title']){
                wp_update_post(array('ID' => $id, 'post_title' => trim($settings['title'])));
            }
            if(isset($settings['css'])){
                update_post_meta($id, 'wpcom_css', $settings['css']);
            }
            $res = array(
                'result' => 0,
                'msg' => '提交发布成功！'
            );
        }
        echo wp_json_encode($res);
        exit;
    }

    public function reset_module_id($module, $mid){
        $module['id'] = $mid . '_' . $module['id'];
        if ($module['settings'] && isset($module['settings']['modules']) && $module['settings']['modules']) {
            foreach ($module['settings']['modules'] as $a => $s) {
                $module['settings']['modules'][$a] = $this->reset_module_id($s, $mid);
            }
        }
        if ($module['settings'] && isset($module['settings']['girds']) && $module['settings']['girds']) {
            foreach ($module['settings']['girds'] as $b => $girds) {
                foreach ($girds as $c => $gird) {
                    $module['settings']['girds'][$b][$c] = $this->reset_module_id($gird, $mid);
                }
            }
        }
        return $module;
    }

    public function modules_style(){
        global $post;
        if( is_singular() && (is_page_template('page-home.php') || is_singular('page_module')) ) {
            $modules = get_post_meta($post->ID, '_page_modules', true);
            if( !$modules ) $modules = array();
            if(isset($modules['type'])) $modules = array($modules);
        }else if( is_home() && function_exists('get_default_mods') ){
            $modules = get_default_mods();
        }

        if( isset($modules) && is_array($modules) && $modules ) {
            ob_start();
            if ( count($modules) > 0 ) foreach ($modules as $v) $this->get_module_style($v);
            $styles = ob_get_contents();
            ob_end_clean();

            if($post->ID) {
                $css = get_post_meta($post->ID, 'wpcom_css', true);
                if($css) $styles .= "\r\n" . $css;
            }

            if ( $styles != '' ) echo '<style>' . $styles . '</style>';
        }
    }

    private function get_module_style($module){
        global $wpcom_modules;
        $module['settings']['modules-id'] = (isset($module['settings']['parent-id']) && $module['settings']['parent-id'] ? $module['settings']['parent-id'].'-' : '') . $module['id'];
        if (isset($wpcom_modules[$module['type']]))
            $wpcom_modules[$module['type']]->style($module['settings']);

        if ($module['settings'] && isset($module['settings']['modules']) && $module['settings']['modules']) {
            foreach ($module['settings']['modules'] as $s) {
                if(isset($module['settings']['parent-id'])) $s['settings']['parent-id'] = $module['settings']['parent-id'];
                $this->get_module_style($s);
            }
        }
        if ($module['settings'] && isset($module['settings']['girds']) && $module['settings']['girds']) {
            foreach ($module['settings']['girds'] as $girds) {
                foreach ($girds as $gird) {
                    if(isset($module['settings']['parent-id'])) $gird['settings']['parent-id'] = $module['settings']['parent-id'];
                    $this->get_module_style($gird);
                }
            }
        }
        if($module['type']=='my_module' && isset($module['settings']['mid']) && $module['settings']['mid']){
            $post = get_post($module['settings']['mid']);
            if(isset($post->post_status) && $post->post_status === 'publish') {
                $mds = get_post_meta($post->ID, '_page_modules', true);
                if ($mds && is_array($mds)) {
                    if(isset($mds['type'])) $mds = array($mds);
                    foreach($mds as $md){
                        $md['settings']['parent-id'] = $module['id'];
                        $this->get_module_style($md);
                    }
                }
            }
        }
    }

    function show_admin_bar($show){
        if(current_user_can('customize') && $this->is_visual_page()) $show = false;
        return $show;
    }

    function admin_title($title){
        if(current_user_can('customize') && $this->is_visual_editor()){
            $title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress' ), '可视化编辑', get_bloginfo( 'name' ) );
        }
        return $title;
    }

    function block_editor($res){
        if(current_user_can('customize') && $this->is_visual_editor()) $res = false;
        return $res;
    }

    function remove_tinymce($post){
        global $_wp_post_type_features;
        if(current_user_can('customize') && $this->is_visual_editor())
            unset($_wp_post_type_features[$post->post_type]['editor']);
    }

    function body_class($classes){
        return array_merge( $classes, array( 'visual-editor' ) );
    }

    private function is_visual_page(){
        global $is_visual_page;
        if(isset($is_visual_page) && $is_visual_page) return true;
        $post_id = isset($_GET['post_id']) ? $_GET['post_id'] : '';
        $visual = isset($_GET['visual-editor']) && $_GET['visual-editor'];
        $nonce = isset($_GET['_nonce']) && $_GET['_nonce'] ? $_GET['_nonce'] : '';
        $is_visual_page = $visual && $post_id && wp_verify_nonce($nonce, 'wpcom-ve-preview-'.$post_id) && (get_page_template_slug($post_id) == 'page-home.php' || get_post_type($post_id)==='page_module');
        return $is_visual_page;
    }

    private function is_visual_editor(){
        global $pagenow;
        return $pagenow==='post.php' && isset($_GET['visual-editor']) && $_GET['visual-editor'];
    }

    private function modules(){
        return apply_filters( 'wpcom_modules', new stdClass() );
    }

    private function save_page_modules($id, $data){
        if($data){
            if(version_compare(PHP_VERSION,'5.4.0','<')){
                $data = wp_slash(wp_json_encode($data));
            }else{
                $data = wp_slash(wp_json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            }
            if($data) return update_post_meta($id, '_page_modules', $data);
        }
    }

    function save_module_options($modules){
        $modules->{'save-module'} = array(
            'options' => array(
                'title' => array(
                    'name' => '保存标题'
                ),
                'desc' => array(
                    'name' => '备注信息',
                    't' => 'ta'
                ),
                'type' => array(
                    'name' => '保存方式',
                    'desc' => '<b>两者区别</b>：引用类似电脑的快捷方式，复制则会拷贝一份完全一样的模块；选择引用保存会将当前模块保存起来并替换成该模块的引用，复制则不影响当前模块；引用可以方便后期统一调整，无需每个模块单独编辑',
                    'type' => 's',
                    'o' => array(
                        '0' => '引用保存',
                        '1' => '复制保存'
                    )
                ),
            )
        );
        $modules->{'page-setting'} = array(
            'options' => array(
                'title' => array(
                    'n' => '页面标题'
                ),
                'home' => array(
                    'n' => '设为首页',
                    'd' => '将当前页面设置为网站首页',
                    't' => 'toggle'
                ),
                'css' => array(
                    'n' => '自定义CSS',
                    't' => 'ta',
                    'd' => '此处添加的CSS代码仅在当前页面显示',
                    'code' => 'css'
                )
            )
        );
        return $modules;
    }

    function exclude_css_meta($metas){
        $metas += array('css');
        return $metas;
    }

    function admin_bar_item() {
        if ( !current_user_can( 'customize' ) ) return;
        global $wp_admin_bar, $post;
        if($post && $post->post_type == 'page' && get_page_template_slug($post->ID) == 'page-home.php') {
            $editor_url = add_query_arg(array('visual-editor' => 'true'), get_edit_post_link($post->ID));
            $wp_admin_bar->add_menu(array(
                'id' => 've-link',
                'title' => '<span class="ab-icon"><svg viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="rgba(240,245,250,.6)"><path d="M1013.27896214 489.69393246c-0.95053009-28.58495833-4.03070598-56.95119699-9.91806969-84.93097403-21.92467334-104.16357643-70.76841161-193.80937381-149.26933152-265.66645384C730.09842431 25.60359462 583.81717613-16.71635268 418.53355836 12.29618012c-114.08000084 20.02361412-209.37306318 76.06703818-285.64402219 163.22139682C59.98983349 258.8221251 19.54134476 356.29910354 10.43402053 466.58685284c-3.90572316 47.29953806-0.88310426 94.30964296 8.91491558 140.80830242 22.9311161 108.82084182 75.8236498 200.54530722 158.99828219 274.15050633 90.30360369 79.90862512 196.41921908 121.39973748 316.89638277 126.18198666l31.60264428 0.01315649c26.43393348-0.85350361 52.71492587-3.44690373 78.63412629-8.62712573 122.90446818-24.56576454 223.78888184-85.73843087 301.28664762-184.1856722 67.66356705-85.95386192 102.44176839-184.35505745 106.5234541-293.63143105-0.00164432-10.53476222-0.00493393-21.0695254-0.01151122-31.6026433z m-553.53057104 114.78220864a70040.09006017 70040.09006017 0 0 1-57.11071439 59.91296831c-14.04415786 14.71018576-30.73269554 23.6218127-51.59172285 22.27824371-15.64098111-1.00808807-29.55357789-7.30164619-42.67022903-15.51928791-19.69964441-12.33550632-37.04598843-27.65909693-53.64736651-43.70791774a24485.24042226 24485.24042226 0 0 1-152.32812931-148.56054548c-34.28484735-33.72242319-36.60525748-77.93027416-6.99905555-115.83140954 18.82311838-24.09872157 43.69640653-35.59717039 71.16967308-36.70392826 22.92453784 0.32396972 40.5685386 6.77869056 55.42179759 20.88040539 33.11395225 31.43983628 66.42360278 62.67575272 99.65102766 93.99718541 3.26107333 3.07359861 6.75895678 5.83802832 10.65481207 8.05812234 11.96055686 6.81486946 21.00045526 5.73771221 31.26387376-3.44854707 34.75188933-31.10435536 68.22763553-63.58023605 102.30034045-95.41475652 13.89615164-12.98509006 28.97141997-23.38006864 48.15633031-26.36486334 37.05256569-5.76566954 66.39400117 7.11417147 87.62140062 37.69557121 22.7337745 32.75051501 18.43994637 71.22887536-10.15652414 102.56181826-43.23265319 47.36202996-87.52108454 93.73077366-131.73551376 140.16694123z m440.93721146-138.22805782c-12.60027375 19.92987627-27.50122285 38.12314565-43.55991154 55.2474784-47.84222846 51.01614219-93.63210187 103.85276224-139.50749058 156.60551329-21.54314565 24.7696844-56.22432149 33.72077887-89.7263797 22.10885843-30.87905644-10.70250317-52.95831423-30.90207982-64.0045209-62.0705714-12.87326277-36.32897981-12.56244954-72.5050185 2.56050888-108.20579363 2.67562578-6.31658151 5.90380886-12.42924416 11.54449557-18.33140771 0 3.18049199-0.00986688 5.21969051 0 7.25724374 0.04769108 10.28972952 5.46965714 18.41856731 14.82694602 22.23548657 9.82597715 4.00932789 19.4282997 2.27929729 26.52767036-5.59628427 11.56258405-12.84366212 22.64497058-26.11654202 34.14341939-39.01776111 28.68527444-32.19302478 57.72905221-64.06865712 86.13146976-96.51000322 10.74690463-12.27301539 21.18299704-24.67430223 33.34582943-35.68432998 30.82149847-27.89590704 74.58204123-20.31140397 101.22482754-3.18707023 24.11845631 15.49955316 37.49000704 38.36653305 38.33693238 67.42511261 0.39632851 13.68236394-4.56023987 26.19712337-11.84379661 37.72352851z"></path></svg></span>可视化编辑<style>#wp-admin-bar-ve-link:hover svg{fill:#00b9eb;}</style>',
                'href' => $editor_url
            ));
        }
    }
}

new WPCOM_Visual_Editor();