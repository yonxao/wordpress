<?php
class WPJAM_Path {
	private $page_key;
	private $page_type	= '';
	private $post_type	= '';
	private $taxonomy	= '';
	private $title		= '';
	private $paths		= [];
	private static $wpjam_paths	= [];

	public function __construct($page_key, $args=[]){
		$this->page_key		= $page_key;
		$this->page_type	= $args['page_type'] ?? '';
		$this->title		= $args['title'] ?? '';

		if($this->page_type == 'post_type'){
			$this->post_type	= $args['post_type'] ?? $this->page_key;
		}elseif($this->page_type == 'taxonomy'){
			$this->taxonomy		= $args['taxonomy'] ?? $this->page_key;
		}
	}

	public function get_title(){
		return $this->title;
	}

	public function get_page_type(){
		return $this->page_type;
	}

	public function get_post_type(){
		return $this->post_type;
	}

	public function get_taxonomy(){
		return $this->taxonomy;
	}

	public function set_title($title){
		$this->title	= $title;
	}

	public function set_path($type, $path=''){
		$this->paths[$type]	= $path;
	}

	public function get_path($type, $args=[]){
		if($type == 'template'){

		}else{
			$path	= $this->paths[$type] ?? '';

			if(empty($path)){
				return false;
			}
			
			if($this->page_type == 'post_type'){
				$post_id	= $args[$this->post_type.'_id'] ?? 0;

				if(empty($post_id)){
					$pt_object	= get_post_type_object($this->post_type);
					return new WP_Error('empty_'.$this->post_type.'_id', $pt_object->label.'ID不能为空。');
				}

				$path		= str_replace(['%post_id%', '%'.$this->post_type.'_id%'], [$post_id, $post_id], $path);
			}elseif($this->page_type == 'taxonomy'){
				$term_id	= $args[$this->taxonomy.'_id'] ?? 0;

				if(empty($term_id)){
					$tax_object	= get_taxonomy($this->taxonomy);
					return new WP_Error('empty_'.$this->taxonomy.'_id', $tax_object->label.'ID不能为空。');
				}

				$path	= str_replace('%term_id%', $term_id, $path);

				if(strpos($path, '%term_parent%')){
					$term	= get_term($term_id, $this->taxonomy);
					$parent	= ($term && $term->parent) ? $term->parent : $term_id;
					$path	= str_replace('%term_parent%', $parent, $path);
				}
			}

			$args['page_type']	= $this->page_type;

			return apply_filters('wpjam_path', $path, $this->page_key, $args);
		}
	}

	public function get_raw_path($type){
		return $this->paths[$type] ?? '';
	}

	public function has_path($type){
		return isset($this->paths[$type]);
	}

	public static function create($page_key, $args=[]){
		$path_obj	= self::get_instance($page_key);

		if(is_null($path_obj)){
			$path_obj	= new WPJAM_Path($page_key, $args);

			self::$wpjam_paths[$page_key]	= $path_obj;
		}

		if(!empty($args['path_type'])){
			$path	= $args['path'] ?? '';
			$path_obj->set_path($args['path_type'], $path);
		}

		return $path_obj;
	}

	public static function get_instance($page_key){
		return self::$wpjam_paths[$page_key] ?? null;
	}

	public static function get_all(){
		return self::$wpjam_paths;
	}
}