<?php
/*
 * Comments to come later
 *
 *
 */

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MetaSeo_Content_List_Table extends WP_List_Table { 
    function __construct() {
        parent::__construct(array(
            'singular' => 'metaseo_content',
            'plural' => 'metaseo_contents',
            'ajax' => true
        ));
		
    }

    function display_tablenav($which) {
        ?>
        <div class="tablenav <?php echo esc_attr($which); ?>">
                      
            <?php #if($which=='top'): ?>
            <input type="hidden" name="page" value="metaseo_content_meta" />           
            <?php #endif ?>
                           
            <input type="hidden" name="page" value="metaseo_content_meta" />
            <?php if (!empty($_REQUEST['post_status'])): ?> 
            <input type="hidden" name="post_status" value="<?php echo esc_attr($_REQUEST['post_status']); ?>" />
            <?php endif ?>
                
            <?php $this->extra_tablenav($which); ?>
			
			<div style="float:right;margin-left:8px;">
                <input type="number" required min="1" value="<?php echo $this->_pagination_args['per_page'] ?>" maxlength="3" name="metaseo_posts_per_page" class="metaseo_imgs_per_page screen-per-page" max="999" min="1" step="1">
                <input type="submit" name="btn_perpage" class="button_perpage button" id="button_perpage" value="Apply" >
            </div>
			
			<?php $this->pagination($which); ?>                
            <br class="clear" />
        </div>

        <?php
    }

    function get_views() {
        global $wpdb;


        $status_links = array();

        $post_types = get_post_types(array('public' => true, 'exclude_from_search' => false));
        $post_types = "'" . implode("', '", $post_types) . "'";

        $states = get_post_stati(array('show_in_admin_all_list' => true));
        $states['trash'] = 'trash';
        $all_states = "'" . implode("', '", $states) . "'";

        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status IN ($all_states) AND post_type IN ($post_types)");

        $class = empty($_REQUEST['post_status']) ? ' class="current"' : '';
        $status_links['all'] = "<a href='admin.php?page=metaseo_content_meta'$class>" . sprintf(_nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts'), number_format_i18n($total_posts)) . '</a>';

        foreach (get_post_stati(array('show_in_admin_all_list' => true), 'objects') as $status) {

            $status_name = $status->name;

            $total = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status IN ('$status_name') AND post_type IN ($post_types)");

            if ($total == 0) {
                continue;
            }

            if (isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status']) {
                $class = ' class="current"';
            } else {
                $class = '';
            }

            $status_links[$status_name] = "<a href='admin.php?page=metaseo_content_meta&amp;post_status=$status_name'$class>" . sprintf(translate_nooped_plural($status->label_count, $total), number_format_i18n($total)) . '</a>';
        }
        $trashed_posts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status IN ('trash') AND post_type IN ($post_types)");
        $class = ( isset($_REQUEST['post_status']) && 'trash' == $_REQUEST['post_status'] ) ? 'class="current"' : '';
        $status_links['trash'] = "<a href='admin.php?page=metaseo_content_meta&amp;post_status=trash'$class>" . sprintf(_nx('Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $trashed_posts, 'posts'), number_format_i18n($trashed_posts)) . '</a>';

        return $status_links;
    }

    function extra_tablenav($which) {

        #if ('top' == $which) {
            echo '<div class="alignleft actions">';
            global $wpdb;

            $post_types = get_post_types(array('public' => true, 'exclude_from_search' => false));
            $post_types = "'" . implode("', '", $post_types) . "'";

            $states = get_post_stati(array('show_in_admin_all_list' => true));
            $states['trash'] = 'trash';
            $all_states = "'" . implode("', '", $states) . "'";

            $query = "SELECT DISTINCT post_type FROM $wpdb->posts WHERE post_status IN ($all_states) AND post_type IN ($post_types) ORDER BY 'post_type' ASC";
            $post_types = $wpdb->get_results($query);

            $selected = !empty($_REQUEST['post_type_filter']) ? $_REQUEST['post_type_filter'] : -1;

            $options = '<option value="-1">Show All Post Types</option>';

            foreach ($post_types as $post_type) {
                $obj = get_post_type_object($post_type->post_type);
                $options .= sprintf('<option value="%2$s" %3$s>%1$s</option>', $obj->labels->name, $post_type->post_type, selected($selected, $post_type->post_type, false));
            }

            if($which=='top') {
                echo sprintf('<select name="post_type_filter" class="metaseo-filter">%1$s</select>', $options);
                submit_button(__('Filter'), 'button', 'do_filter', false, array('id' => 'post-query-submit'));
            }else{                
                echo sprintf('<select name="post_type_filter" class="metaseo-filter">%1$s</select>', $options);
                submit_button(__('Filter'), 'button', 'do_filter', false, array('id' => 'post-query-submit'));
            }
            
            echo "</div>";
            #echo "</form>";
        #}
    }

    function get_columns() {
        return $columns = array(
            'col_id' => __('', 'wp-meta-seo'),
            'col_title' => __('Title', 'wp-meta-seo'),
            'col_meta_title' => __('Meta Title', 'wp-meta-seo'),
            'col_meta_desc' => __('Meta Description', 'wp-meta-seo')
        );
    }

    function get_sortable_columns() {
        return $sortable = array(
            'col_title' => array('post_title', true),
        );
    }
	
    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        //$GLOBALS['wp_filter']["manage_{$GLOBALS['screen']->id}_screen_columns"];

        $screen = get_current_screen();

        $where = array();
        $post_type = isset($_REQUEST['post_type_filter'])? $_REQUEST['post_type_filter'] : "";  
        if($post_type=="-1") {
            $post_type="";
        }
        
        $post_types = get_post_types( array('public' => true, 'exclude_from_search' => false) ) ;        
        if ( !empty( $post_type ) && !in_array( $post_type,$post_types ) )
            $post_type = '\'post\'';
         else if(empty ($post_type)) {
             $post_type = "'" . implode("', '", $post_types) . "'";             
         }else {
             $post_type = "'" . $post_type . "'";             
         }         
         $where[] = "post_type IN ($post_type)";
          
        $states = get_post_stati(array('show_in_admin_all_list' => true));
        $states['trash'] = 'trash';
        $all_states = "'" . implode("', '", $states) . "'";

        if (empty($_REQUEST['post_status'])) {
            $where[] = "post_status IN ($all_states)";            
        } else {
            $requested_state = $_REQUEST['post_status'];
            if (in_array($requested_state, $states)) {                
                $where[] = "post_status IN ('$requested_state')";                 
            } else {
                $where[] = "post_status IN ($all_states)"; 
            }
        }
        
        //Order By block
        $orderby = !empty($_GET["orderby"]) ? ($_GET["orderby"]) : 'post_title';
        $order = !empty($_GET["order"]) ? ($_GET["order"]) : 'asc';
        
        $sortable = $this->get_sortable_columns();
        if(in_array($orderby, $sortable)) {
            $orderStr = $orderby;
        }else {
            $orderStr = 'post_title';
        }
        
        if($order=="asc") {
            $orderStr .= " ASC";
        }else {
            $orderStr .= " DESC";
        }
        
        if (!empty($orderby) & !empty($order)) {
            $orderStr =' ORDER BY ' . $orderStr;
        }
        
        $query = "SELECT ID, post_title, post_name, post_type,  post_status , mt.meta_value AS metatitle, md.meta_value AS metadesc "
                . " FROM $wpdb->posts "
                . " LEFT JOIN (SELECT * FROM $wpdb->postmeta WHERE meta_key = '_metaseo_metatitle') mt ON mt.post_id = $wpdb->posts.ID "
                . " LEFT JOIN (SELECT * FROM $wpdb->postmeta WHERE meta_key = '_metaseo_metadesc') md ON md.post_id = $wpdb->posts.ID "
                . " WHERE ". implode(' AND ', $where) . $orderStr;       

        $total_items = $wpdb->query($query);
        
        if(!empty($_REQUEST['metaseo_posts_per_page'])){
			$_per_page = intval($_REQUEST['metaseo_posts_per_page']);
		}
		else {
			$_per_page = 0;
		}
		$per_page = get_user_option('metaseo_posts_per_page');    
		if( $per_page !== false) {
			if($_per_page && $_per_page !== $per_page ){
				$per_page = $_per_page;
				update_user_option(get_current_user_id(), 'metaseo_posts_per_page', $per_page);
			}
		}
		else{
            if($_per_page > 0) { 
                $per_page = $_per_page; 
            }
			else { $per_page = 10; }                        
			add_user_meta(get_current_user_id(), 'metaseo_posts_per_page', $per_page);
		}

        $paged = !empty($_GET["paged"]) ? $_GET["paged"] : '';
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }

        $total_pages = ceil($total_items / $per_page);

        if (!empty($paged) && !empty($per_page)) {
            $offset = ($paged - 1) * $per_page;
            $query .= ' LIMIT ' . (int) $offset . ',' . (int) $per_page;
        }

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'per_page' => $per_page
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = $wpdb->get_results($query);
    }

    function display_rows() {

        $records = $this->items;
        $i = 0;
        $alternate = "";
		$url = preg_replace('/(http|https):\/\/[w]*[.]?/', '', network_site_url('/'));

        list( $columns, $hidden ) = $this->get_column_info();

        if (!empty($records)) {
            foreach ($records as $rec) {
                $alternate = 'alternate' == $alternate ? '' : 'alternate';
                $i++;
                $classes = $alternate;
				$rec->link = $url;

                echo '<tr id="record_' . $rec->ID . '" class="' . $classes . '" >';

                foreach ($columns as $column_name => $column_display_name) {

                    $class = sprintf('class="%1$s column-%1$s"', $column_name);
                    $style = "";

                    if (in_array($column_name, $hidden)) {
                        $style = ' style="display:none;"';
                    }

                    $attributes = $class . $style;

                    switch ($column_name) {
                        case 'col_id':
                            echo '<td class="col_id" >';
                            echo $i;
                            echo '</td>';

                            break;

                        case 'col_title':
                            $post_title = stripslashes($rec->post_title);
                            if($post_title == '') $post_title = __('(no title)' , 'wp-meta-seo');
                            echo sprintf('<td %2$s><div class="action-wrapper"><strong id="post-title-'.$rec->ID.'">%1$s</strong>', $post_title, $attributes);
                            
                            $post_type_object = get_post_type_object($rec->post_type);
                            $can_edit_post = current_user_can($post_type_object->cap->edit_post, $rec->ID);

                            $actions = array();

                            if ($can_edit_post && 'trash' != $rec->post_status) {
                                $actions['edit'] = '<a href="' . get_edit_post_link($rec->ID, true) . '" title="' . esc_attr(__('Edit this item')) . '">' . __('Edit') . '</a>';
                            }

                            if ($post_type_object->public) {
                                if (in_array($rec->post_status, array('pending', 'draft', 'future'))) {
                                    if ($can_edit_post)
                                        $actions['view'] = '<a href="' . esc_url(add_query_arg('preview', 'true', get_permalink($rec->ID))) . '" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $rec->post_title)) . '" rel="permalink">' . __('Preview') . '</a>';
                                } elseif ('trash' != $rec->post_status) {
                                    $actions['view'] = '<a target="_blank" href="' . get_permalink($rec->ID) . '" title="' . esc_attr(sprintf(__('View &#8220;%s&#8221;'), $rec->post_title)) . '" rel="permalink">' . __('View') . '</a>';
                                }
                            }

                            echo $this->row_actions($actions);
                            echo '</div><div class="snippet-wrapper">';
                            $preview = __(" This is a rendering of what this post might look like in Google's search results.", 'wp-meta-seo');                            
                            $info = sprintf('<a class="info-content"><img src=' . WPMETASEO_PLUGIN_URL . 'img/info.png  href="#">'
                                    . '<p class="tooltip-metacontent">'
                                    .$preview
                                     .'</p></a>');
                            
                             echo '<div><strong>' . __('Snippet Preview', 'wp-meta-seo') . '</strong> ' . $info. '</div>';
                            
                             echo '<div class="snippet">
                                       <a id="snippet_title'.$rec->ID . '" class="snippet_metatitle">'.(!empty($rec->metatitle) ? $rec->metatitle : $rec->post_title).'</a>';
									   
								echo '
		                              <span class="snippet_metalink" id="snippet_metalink_'.$rec->ID.'">'.$rec->link.'</span>';
									   
									   echo '
                                       <p id="snippet_desc'.$rec->ID . '" class="snippet_metades">'.$rec->metadesc.'</p>
                                    </div>';
							 echo '
							<span id="savedInfo'.$rec->ID.'" style="position: relative; display: block;float:right" class="saved-info metaseo-msg-success"><span style="position:absolute; float:right" class="meta-update"></span></span>';	
                             //echo '<span id="savedInfo'.$rec->ID.'" class="savedInfo" style="display:none;"></span>';
                            echo '</div>';
                          //  echo sprintf('<div %2$s>%1$s</div>', $info, $attributes);
                            
                        
                            
                            break;
                        case 'col_page_slug':
                            $permalink = get_permalink($rec->ID);
                            $display_slug = str_replace(get_bloginfo('url'), '', $permalink);
                            echo sprintf('<td %2$s><a href="%3$s" target="_blank">%1$s</a></td>', stripslashes($display_slug), $attributes, $permalink);
                            break;

                        case 'col_meta_title':
                            $input = sprintf('</br><textarea class="large-text metaseo-metatitle" rows="3" id="%1$s" name="%2$s" autocomplete="off">%3$s</textarea>', 'metaseo-metatitle-' . $rec->ID, 'metatitle['. $rec->ID.']', ( ($rec->metatitle ) ? $rec->metatitle : ''));
                            $input .= sprintf('<div class="title-len" id="%1$s"></div>', 'metaseo-metatitle-len' . $rec->ID);
                            echo sprintf('<td %2$s>%1$s</td>', $input, $attributes);
                            break;

                        case 'col_meta_desc':
                            $input = sprintf('</br><textarea class="large-text metaseo-metadesc" rows="3" id="%1$s" name="%2$s" autocomplete="off">%3$s</textarea>', 'metaseo-metadesc-' . $rec->ID, ' metades['. $rec->ID.']', (($rec->metadesc ) ? $rec->metadesc : ''));
                            $input .= sprintf('<div class="desc-len" id="%1$s"></div>', 'metaseo-metadesc-len' . $rec->ID);
                            echo sprintf('<td %2$s>%1$s</td>', $input, $attributes);
                            break;
                    }
                }

                echo '</tr>';
            }
        }
    }

    protected function get_bulk_actions() {
        $actions = array();
        $actions = array(
            'update' => 'Update',
        );        

        return $actions;
    }
    
    function process_action() {
        $current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		$redirect = false;
		
        if (isset($_POST['do_filter']) and $_POST['do_filter'] === __('Filter')) {
            $current_url = add_query_arg(array( "post_type_filter" => $_POST['post_type_filter']), $current_url);
			$redirect = true;
        }
               
		if(!empty($_POST['paged'])){
			$current_url = add_query_arg(array( "paged" => intval($_POST['paged'])), $current_url);
			$redirect = true;
		}
     	
		if(!empty($_POST['metaseo_posts_per_page'])){
			$current_url = add_query_arg(array( "metaseo_posts_per_page" => intval($_POST['metaseo_posts_per_page'])), $current_url);
			$redirect = true;
		}
		
		if($redirect === true){
			wp_redirect($current_url);
			ob_end_flush();
	        exit();
		}
    }
	
	/**
	 * Get all posts that is public and contain images with a string seperated by comma
	 */
	public static function get_post_types(){
		global $wpdb;
		$post_types = get_post_types( array('public' => true, 'exclude_from_search' => false) ) ;
		if(!empty($post_types)){
			$post_types = "'" . implode("', '", $post_types) . "'";
		}
		
		return $post_types;
	} 
	
	public static function importMetaData(){
		global $wpdb;
		$meta_metaseo_keys = array('_metaseo_metatitle', '_metaseo_metadesc');
		$meta_other_keys = array(
			'_aio_' => array('_aioseop_title', '_aioseop_description'),
			'_yoast_' => array('_yoast_wpseo_title', '_yoast_wpseo_metadesc')
		);
		
		if(!empty($_POST['plugin']) and in_array(strtolower(trim($_POST['plugin'])), array('_aio_', '_yoast_'))){
			$plugin = strtolower(trim($_POST['plugin']));
			$metakeys = '';
			foreach($meta_metaseo_keys as $k => $mkey){
				$metakeys .= ' OR `meta_key` = \''. $mkey . '\' OR `meta_key` = \'' . $meta_other_keys[$plugin][$k] . '\'';
			}
			
			$metakeys = ltrim($metakeys, ' OR ');
			$query = "SELECT `post_id` as pID, `meta_key`, `meta_value` 
					  FROM $wpdb->postmeta 
					  WHERE  $metakeys
					  ORDER BY `meta_key`";
			$posts_metas = $wpdb->get_results($query);
			
			if(is_array($posts_metas) && count($posts_metas)  > 0){
				foreach($posts_metas as $postmeta){
					$_posts_metas[$postmeta->pID][$postmeta->meta_key] = $postmeta->meta_value;
				}
				unset($posts_metas);
				foreach($_posts_metas as $pID => $pmeta){
					foreach($meta_metaseo_keys as $k => $mkey){
						$mvalue = $pmeta[$mkey];
						$msynckey = $meta_other_keys[$plugin][$k];
						$msyncvalue = $pmeta[$msynckey];
						
						if( is_null($mvalue ) || ( $mvalue == '' && $msynckey != '' ) ){
							update_post_meta( $pID, $mkey, $msyncvalue );
						}
						elseif( is_null($msyncvalue ) || ( $msyncvalue == '' && $mvalue != '' ) ){
							update_post_meta( $pID, $msynckey, $mvalue );
						}
						elseif($mvalue != '' && $msyncvalue != ''){
							update_post_meta( $pID, $mkey, $msyncvalue );
						}
					}
					
				}
				
				unset($posts_metas);
			}
			
			
			$ret['success'] = true;
			
			update_option('_aio_import_notice_flag', 1);
			update_option('_yoast_import_notice_flag', 1);
			update_option('plugin_to_sync_with', $plugin);
		}else{
			$ret['success'] = false;
		}
				
		echo json_encode($ret);
		wp_die();
	}
	
	public static function dismissImport(){
		if(!empty($_POST['plugin']) and in_array(strtolower(trim($_POST['plugin'])), array('_aio_', '_yoast_'))){
			$plugin = strtolower(trim($_POST['plugin']));
			
			update_option($plugin.'import_notice_flag', 1);
			$ret['success'] = true;
		}else{
			$ret['success'] = false;
		}
		
		echo json_encode($ret);
		wp_die();
	}
	
	/**
	 * 
	 */
	public  static function updateMetaSync($meta_id, $object_id, $meta_key, $meta_value){
		if(!self::is_updateSync($meta_key)){
			return null;
		}
		
		if(self::_updateMetaSync('update', $object_id, $meta_key, $meta_value)){
			return true;
		}
		
		return null;
	}
	
	/**
	 * 
	 */
	 public static function deleteMetaSync($meta_ids, $object_id, $meta_key, $meta_value){
	 	
		if(!self::is_updateSync($meta_key)){
			return null;
		}
		
		if(self::_updateMetaSync('delete', $object_id, $meta_key, $meta_value)){
			return true;
		}
		
		return null;
	 }
	
	/**
	 * 
	 */
	private static function _updateMetaSync($type = '', $object_id, $meta_key, $meta_value){
		if( ! ( $sync = get_option('plugin_to_sync_with') ) or !in_array( $sync, array('_aio_', '_yoast_' ) ) ){
			return false;
		}
		
		$metakeys = array(
			'_metaseo_' => array('_metaseo_metatitle' , '_metaseo_metadesc'),
			'_aio_' => array('_aioseop_title', '_aioseop_description'),
			'_yoast_' => array('_yoast_wpseo_title', '_yoast_wpseo_metadesc')
		);
		
		$_metakeys = array();
		$_metakeys['_metaseo_'] = $metakeys['_metaseo_'];
		$_metakeys[$sync] = $metakeys[$sync];
		unset($metakeys);
		
		foreach($_metakeys as $identify => $mkeys){
			foreach($mkeys as $k => $mkey){
				if($meta_key === $mkey){
					if($identify === '_metaseo_' ){
						$mkeysync = $_metakeys[$sync][$k];
					}
					else{
						$mkeysync = $_metakeys['_metaseo_'][$k];
					} 
					
					if($type == 'update'){
						update_post_meta($object_id, $mkeysync, $meta_value);
						return true;
					}
					
					if($type == 'delete'){
						delete_post_meta($object_id, $mkeysync);
						return true;
					}
					
				}
			}
			
		}

		return false;
	}
	
	/**
	 * 
	 */
	public static function updateMetaSyncAll($meta_id, $object_id, $meta_key, $meta_value){
		if(!self::is_updateSync($meta_key)){
			return null;
		}	
		//These info may be got from database in later version
		$mseo = 'wp-meta-seo/wp-meta-seo.php';
		$yoast = 'wordpress-seo/wp-seo.php';
		$aio = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
		
		$metakeys = array(
			'mtitle' => array(
				$mseo => '_metaseo_metatitle',
				$yoast => '_yoast_wpseo_title',
				$aio => '_aioseop_title' ),
			'mdesciption' => array(
				$mseo => '_metaseo_metadesc', 
				$yoast => '_yoast_wpseo_metadesc', 
				$aio => '_aioseop_description' )
			);
		
		//Update post meta
		foreach($metakeys as $metakey){
			if(in_array($meta_key, $metakey)){
				foreach($metakey as $plg => $mkey){
					if($mkey !== $meta_key){
						if($plg === $mseo || is_plugin_active($plg)){
							update_post_meta($object_id, $mkey, $meta_value);
						}
					}
				}
			}
		}
		
		return null;
	}
	
	public static function is_updateSync($meta_key){
		$mkey_prefix = array('_metaseo_', '_yoast_', '_aio');
		foreach($mkey_prefix as $prefix){
			if(strpos($meta_key, $prefix) === 0){
				return true;
			}
		}
		
		return false;
	}
}