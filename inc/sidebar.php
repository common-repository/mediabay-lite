<?php

use Mediabay\Helper;

class Mediabay_Sidebar {

	public function __construct() 
	{
		add_filter( 'restrict_manage_posts', array($this, 'mediabayRestrictManagePosts'));
		add_filter( 'posts_clauses', array($this, 'mediabayPostsClauses'), 10, 2);
		
		add_action( 'admin_enqueue_scripts', array($this, 'mediabayEnqueueStyles' )); 									// load style files
		add_action( 'admin_enqueue_scripts', array($this, 'mediabayEnqueueScripts' ));									// load js files
		
		add_action( 'init', array($this,'mediabayAddFolderToAttachments' ));												// register MEDIABAY taxonomy
		add_action( 'admin_footer-upload.php', array($this,'mediabayInitSidebar'));										// get interface
		
		add_action( 'wp_ajax_mediabayAjaxAddCategory', array($this,'mediabayAjaxAddCategory'));							// ajax: add new category
		add_action( 'wp_ajax_mediabayAjaxDeleteCategory', array($this,'mediabayAjaxDeleteCategory'));					// ajax: delete existing category
		add_action( 'wp_ajax_mediabayAjaxClearCategory', array($this,'mediabayAjaxClearCategory'));						// ajax: delete existing category
		add_action( 'wp_ajax_mediabayAjaxRenameCategory', array($this,'mediabayAjaxRenameCategory'));					// ajax: rename existing category
		
		add_action( 'wp_ajax_mediabayAjaxUpdateSidebarWidth', array($this,'mediabayAjaxUpdateSidebarWidth'));			// ajax: update sidebar width
		
		add_action( 'wp_ajax_mediabayAjaxMoveMultipleMedia', array($this,'mediabayAjaxMoveMultipleMedia'));				// ajax: move multiple media
		add_action( 'wp_ajax_mediabayAjaxGetTermsByMedia', array($this,'mediabayAjaxGetTermsByMedia'));					// ajax: get terms by media for single media
		add_action( 'wp_ajax_mediabayAjaxMoveSingleMedia', array($this,'mediabayAjaxMoveSingleMedia'));					// ajax: move singe media
		
		add_action( 'wp_ajax_mediabayAjaxCheckDeletingMedia', array($this,'mediabayAjaxCheckDeletingMedia'));			// ajax: check deleting media	
		
		add_action( 'wp_ajax_mediabayAjaxMoveCategory', array($this,'mediabayAjaxMoveCategory'));						// move category
		add_action( 'wp_ajax_mediabayAjaxUpdateFolderPosition', array($this,'mediabayAjaxUpdateFolderPosition' ));		// update folder position
		
		add_option( 'mediabay_sidebar_width', 280);																	// add option for sidebar width
		
		add_filter( 'pre-upload-ui', array($this, 'mediabayPreUploadUserInterface'));									// upload uploader category to "Add new" 
		
		
		if(MEDIABAY_PLUGIN_NAME != 'Mediabay'){
			add_action( 'admin_notices', [$this, 'pro_version_notice'] );
		}
		//Support Elementor
        if (defined('ELEMENTOR_VERSION')) {
            add_action('elementor/editor/after_enqueue_scripts', [$this, 'mediabayScripts']);
            add_action('elementor/editor/after_enqueue_scripts', [$this, 'mediabayStyles']);
        }
		
	}
	
	
	public function pro_version_notice(){
		global $pagenow;
		if ( $pagenow == 'upload.php' ) {
			 echo '<div class="notice notice-warning is-dismissible">
					 <p>'.esc_html__('Mediabay PRO has more handy features. You could rename a folder, add subfolders easily, clear folders, and search for folders. It also enables folders panel on the media pop-up window.', MEDIABAY_TEXT_DOMAIN).' <a href="https://mediabay.frenify.com/1/" target="_blank">Mediabay PRO</a></p>
				 </div>';
		}
	}
	
	
	public function mediabayEnqueueStyles(){
		$this->mediabayStyles();
	}
	
	
	public function mediabayStyles()
	{
		wp_enqueue_style( 'iaoalert', MEDIABAY_ASSETS_URL . 'css/iaoalert.css', array(), MEDIABAY_PLUGIN_NAME, 'all' );
		wp_enqueue_style( 'mediabay-admin', MEDIABAY_ASSETS_URL . 'css/core.css', array(), MEDIABAY_PLUGIN_NAME, 'all' );
		wp_enqueue_style( 'mediabay-front', MEDIABAY_ASSETS_URL . 'css/front.css', array(), MEDIABAY_PLUGIN_NAME, 'all' );
		wp_enqueue_style( 'mediabay-rtl', MEDIABAY_ASSETS_URL . 'css/rtl.css', array(), MEDIABAY_PLUGIN_NAME, 'all' );
		
		if(MEDIABAY_PLUGIN_NAME == 'Mediabay'){
			$custom_css = "#mediabay-attachment-filters{display: none;}";
			wp_add_inline_style( 'mediabay-admin', $custom_css );
		}
		
	}
	

	public function mediabayEnqueueScripts()
	{
		$this->mediabayScripts();
	}
	
	public function mediabayScripts()
	{
		
		$allFilesText		= esc_html__('All Files', MEDIABAY_TEXT_DOMAIN);
		$uncategorizedText	= esc_html__('Uncategorized', MEDIABAY_TEXT_DOMAIN);
		$taxonomy 			= apply_filters('mediabay_taxonomy', MEDIABAY_FOLDER);
		$dropdownOptions 	= array(
			'taxonomy'        => $taxonomy,
			'hide_empty'      => false,
			'hierarchical'    => true,
			'orderby'         => 'name',
			'show_count'      => true,
			'walker'          => new Mediabay_Walker_Category_Mediagridfilter(),
			'value'           => 'id',
			'echo'            => false
		);
		$attachmentTerms 	= wp_dropdown_categories( $dropdownOptions );
		$attachmentTerms 	= preg_replace( array( "/<select([^>]*)>/", "/<\/select>/" ), "", $attachmentTerms );
		
		wp_register_script( 'inline-script-handle-header', '' );
		wp_enqueue_script( 'inline-script-handle-header' );
		wp_add_inline_script( 'inline-script-handle-header', '/* <![CDATA[ */ var mediabayFolders = [{"folderID":"all","folderName":"'. esc_html($allFilesText) .'"}, {"folderID":"-1","folderName":"'. esc_html($uncategorizedText) .'"},' . wp_kses_post(substr($attachmentTerms, 2)) . ']; /* ]]> */' );
		
		
		wp_enqueue_script('jquery-ui-draggable');
    	wp_enqueue_script('jquery-ui-droppable');

		wp_register_script('iaoalert', MEDIABAY_ASSETS_URL . 'js/third-party-plugins/iaoalert.js',['jquery'], MEDIABAY_PLUGIN_NAME, false);
		wp_register_script('nicescroll', MEDIABAY_ASSETS_URL . 'js/third-party-plugins/nicescroll.js',['jquery'], MEDIABAY_PLUGIN_NAME, false);
		wp_register_script('mediabay-resizable', MEDIABAY_ASSETS_URL . 'js/resizable.js',['jquery'], MEDIABAY_PLUGIN_NAME, false);
		wp_register_script('mediabay-core', MEDIABAY_ASSETS_URL . 'js/core.js',['jquery'], MEDIABAY_PLUGIN_NAME, true);
		wp_register_script('mediabay-filter', MEDIABAY_ASSETS_URL . 'js/filter.js',['jquery'], MEDIABAY_PLUGIN_NAME, false);
		wp_register_script('mediabay-select-filter', MEDIABAY_ASSETS_URL . '/js/select-filter.js', ['media-views'], MEDIABAY_PLUGIN_NAME, true );
		wp_register_script('mediabay-upload', MEDIABAY_ASSETS_URL . 'js/upload.js', ['jquery'], MEDIABAY_PLUGIN_NAME, false );

		wp_localize_script(
			'mediabay-core',
			'mediabayConfig',
			[
				'plugin' 						=> MEDIABAY_PLUGIN_NAME,
				'pluginURL' 					=> MEDIABAY_URL,
				'nonce' 						=> wp_create_nonce( 'ajax-nonce' ),
				'uploadURL' 					=> admin_url( 'upload.php' ),
				'ajaxUrl' 						=> admin_url( 'admin-ajax.php' ),
				'moveOneFile' 					=> esc_html__( 'Move 1 file', MEDIABAY_TEXT_DOMAIN ),
				'move' 							=> esc_html__( 'Move', MEDIABAY_TEXT_DOMAIN ),
		    	'files' 						=> esc_html__( 'files', MEDIABAY_TEXT_DOMAIN ),
				'newFolderText' 				=> esc_html__( 'New Subfolder', MEDIABAY_TEXT_DOMAIN ),
				'clearMediaText' 				=> esc_html__( 'Clear Media', MEDIABAY_TEXT_DOMAIN ),
				'renameText' 					=> esc_html__( 'Rename Folder', MEDIABAY_TEXT_DOMAIN ),
				'deleteText' 					=> esc_html__( 'Delete Folder', MEDIABAY_TEXT_DOMAIN ),
				'clearText' 					=> esc_html__( 'Clear Folder', MEDIABAY_TEXT_DOMAIN ),
				'cancelText' 					=> esc_html__( 'Cancel', MEDIABAY_TEXT_DOMAIN ),
				'confirmText' 					=> esc_html__( 'Confirm', MEDIABAY_TEXT_DOMAIN ),
				'areYouSure' 					=> esc_html__( 'Are you confident?', MEDIABAY_TEXT_DOMAIN ),
				'willBeMovedToUncategorized'	=> esc_html__( 'All media inside this folder gets moved to "Uncategorized" folder.', MEDIABAY_TEXT_DOMAIN ),
				'hasSubFolder'					=> esc_html__( 'This folder contains subfolders, you should delete the subfolders first!', MEDIABAY_TEXT_DOMAIN ),
				'slugError' 					=> esc_html__( 'Unfortunately, you already have a folder with that name.', MEDIABAY_TEXT_DOMAIN ),
				'enterName' 					=> esc_html__( 'Please, enter your folder name!', MEDIABAY_TEXT_DOMAIN ),
				'item' 							=> esc_html__( 'item', MEDIABAY_TEXT_DOMAIN ),
				'items' 						=> esc_html__( 'items', MEDIABAY_TEXT_DOMAIN ),
				'currentFolder' 				=> $this->getCurrentFolder(),
				'noItemDOM' 					=> $this->noItemForListMode(),
				'mediabayAllTitle' 			=> esc_html__('All categories', MEDIABAY_TEXT_DOMAIN),
			]
		);
		wp_localize_script(
			'mediabay-filter',
			'mediabayConfig2',
			[
				'pluginURL' 					=> MEDIABAY_URL,
				'ajaxUrl' 						=> admin_url( 'admin-ajax.php' ),
				'nonce' 						=> wp_create_nonce( 'ajax-nonce' ),
				'moveOneFile' 					=> esc_html__( 'Move 1 file', MEDIABAY_TEXT_DOMAIN ),
				'move' 							=> esc_html__( 'Move', MEDIABAY_TEXT_DOMAIN ),
		    	'files' 						=> esc_html__( 'files', MEDIABAY_TEXT_DOMAIN ),
			]
		);
		
		wp_localize_script(
			'mediabay-select-filter',
			'mediabayConfig',
			[
				'mediabayFolder' 				=> MEDIABAY_FOLDER,
				'mediabayAllTitle' 				=> esc_html__('All categories', MEDIABAY_TEXT_DOMAIN),
				'showhideTrigger' 				=> esc_html__('Show/Hide Mediabay panel', MEDIABAY_TEXT_DOMAIN),
				'uploadURL' 					=> admin_url( 'upload.php' ),
				'assetsURL' 					=> MEDIABAY_ASSETS_URL
			]
		);
		
		wp_localize_script(
			'mediabay-upload',
			'mediabayConfig',
			[
				'nonce' 						=> wp_create_nonce('ajax-nonce')
			]
		);

		wp_enqueue_script( 'iaoalert' );
		wp_enqueue_script( 'nicescroll' );
		wp_enqueue_script( 'mediabay-resizable' );
		wp_enqueue_script( 'mediabay-core' );
		wp_enqueue_script( 'mediabay-filter' );
		wp_enqueue_script( 'mediabay-select-filter' );
		wp_enqueue_script( 'mediabay-upload' );
		
		
		
	}
	
	public function noItemForListMode()
	{
		return '<tr class="no-items"><td class="colspanchange" colspan="6">'.esc_html__('No media files found.', MEDIABAY_TEXT_DOMAIN).'</td></tr>';
	}
	
	public function getCurrentFolder()
	{
		if(isset($_GET['cc_mediabay_folder'])){
			return sanitize_text_field($_GET['cc_mediabay_folder']);
		}
		return '';
	}
	
	public function mediabayRestrictManagePosts()
	{
	    $scr 	= get_current_screen();
	    if($scr->base !== 'upload'){
	        return;
	    }
	    echo '<select id="mediao-attachment-filters" class="wpmediacategory-filter attachment-filters" name="cc_mediabay_folder"></select>';
	}

	public function getSidebarWidth()
	{
		$sidebarWidth 		= (int) get_option('mediabay_sidebar_width', 380);
		if($sidebarWidth < 250 || $sidebarWidth > 750){
			$sidebarWidth 	= 380;
		}
		return $sidebarWidth;
	}

	public function mediabayInitSidebar()
	{
		$output  		= '';
		$helper	 		= new Helper;
		$sidebarWidth 	= $this->getSidebarWidth().'px;';
		
		$output .= '<div class="cc_mediabay_temporary">';
			$output .= '<div id="mediabay_sidebar" class="cc_mediabay_sidebar" style="width:'.$sidebarWidth.'">';
				$output .= '<div class="cc_mediabay_sidebar_in" style="width:'.$sidebarWidth.'">';
					$output .= $helper->getSidebarHeader();
					$output .= $helper->getSidebarContent();
					$output .= '<input type="hidden" id="mediabay_hidden_terms">';
				$output .= '</div>';
			$output .= '</div>';
			$output .= $this->splitter();
		$output .= '</div>';
		
		
		echo wp_kses_post($output);
	}
	
	public function splitter()
	{
		if(MEDIABAY_PLUGIN_NAME == 'Mediabay'){
			$html = '<div class="mediabay_splitter active">
					<span class="splitter_holder">
						<span class="splitter_a"></span>
						<span class="splitter_b"></span>
						<span class="splitter_c"></span>
					</span>
				</div>';
		}else{
			$html = '<div class="mediabay_splitter"></div>';
		}
		return $html;
	}
	
	public function mediabayPreUploadUserInterface() 
	{
		$helper	 	 	= new Helper;
        $terms 		 	= $helper->mediabayTermTreeArray(MEDIABAY_FOLDER, 0);
		$otherOptions 	= $helper->mediabayTermTreeOption($terms);
		$text 		 	= esc_html__("New files go to chosen category", MEDIABAY_TEXT_DOMAIN);
		$output			= '';
		
		// top section
		$output		.= '<p class="cc_upload_paragraph attachments-category">';
			$output		.= $text;
		$output		.= '</p>';
		
		// select section
		$output		.= '<p class="cc_upload_paragraph">';
			$output		.= '<select name="ccFolder" class="mediabay-editcategory-filter">';
				$output		.= '<option value="-1">1.'.esc_html__('Uncategorized', MEDIABAY_TEXT_DOMAIN).'</option>';
				$output		.= $otherOptions;
			$output		.= '</select>';
		$output		.= '</p>';
		
		// echo result
		echo wp_kses_post($output);
	}
	
	public function mediabayAjaxAddCategory()
	{
		$categoryName 	= sanitize_text_field($_POST["categoryName"]);
		$parent 		= sanitize_text_field($_POST["parent"]);
		
		
		// check category name
		$name 			= self::mediabayCheckMetaName($categoryName, $parent);
		$newTerm 		= wp_insert_term($name, MEDIABAY_FOLDER, array(
			'name' 		=> $name,
			'parent' 	=> $parent
		));

		if (is_wp_error($newTerm)){
			echo 'error';
		}else{
			add_term_meta( $newTerm["term_id"], 'folder_position', 9999 );
			
			
			$buffyArray = array(
				'termID' 			=> $newTerm["term_id"],
				'termName' 			=> $name,
			);

			die(json_encode($buffyArray));
		}
		
	}
	
	public function mediabayAjaxDeleteCategory()
	{
		$categoryID 		= sanitize_text_field($_POST["categoryID"]);
		$selectedTerm 		= get_term($categoryID , MEDIABAY_FOLDER );
		$count 				= $selectedTerm->count ? $selectedTerm->count : 0;
		$deleteTerm			= wp_delete_term( $categoryID, MEDIABAY_FOLDER );
		
		
		if(is_wp_error($deleteTerm)){
			$error		= 'yes';
		}else{
			$error		= 'no';
		}
		$buffyArray 	= array(
			'error' 	=> $error,
			'count' 	=> $count,
		);
		
		die(json_encode($buffyArray));
		
	}
	
	public function mediabayAjaxClearCategory()
	{
		global $wpdb;
		$categoryID 		= sanitize_text_field($_POST["categoryID"]);
		$selectedTerm 		= get_term($categoryID , MEDIABAY_FOLDER );
		$count 				= $selectedTerm->count ? $selectedTerm->count : 0;
		
		$wpdb->query($wpdb->prepare( "UPDATE {$wpdb->prefix}term_taxonomy SET count=%d WHERE term_id=%d AND taxonomy=%s", 0, $categoryID, MEDIABAY_FOLDER));
		$wpdb->query($wpdb->prepare( "DELETE FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id=%d", $categoryID));
		
		$buffyArray 	= array(
			'error' 	=> 'no',
			'count' 	=> $count,
		);
		die(json_encode($buffyArray));
		
	}
	
	public function mediabayAjaxRenameCategory()
	{
		$categoryID 		= sanitize_text_field($_POST["categoryID"]);
		$categoryTitle		= sanitize_text_field($_POST["categoryTitle"]);
		$newSlug			= $this->mediabaySlugGenerator($categoryTitle,$categoryID);
		$renameCategory		= wp_update_term($categoryID, MEDIABAY_FOLDER, array(
			'name' 			=> $categoryTitle,
			'slug' 			=> $newSlug
		));
		
		if(is_wp_error($renameCategory)){
			$error			= 'yes';
		}else{
			$error			= 'no';
		}
		$buffyArray 		= array(
			'error' 		=> $error,
			'title' 		=> $categoryTitle,
		);
		die(json_encode($buffyArray));
		
	}
	
	public function mediabayAjaxUpdateSidebarWidth()
	{
		$width 	= sanitize_text_field($_POST['width']);
		$error	= 'yes';
		
		if(update_option( 'mediabay_sidebar_width', $width )){
			$error			= 'no';
		}
		
		$buffyArray 		= array(
			'error' 		=> $error,
		);
		die(json_encode($buffyArray));
		
	}
	
	// SANITIZE ARRAY ELEMENTS
	public function recursive_sanitize_text_field($array_or_string) {
		if( is_string($array_or_string) ){
			$array_or_string = sanitize_text_field($array_or_string);
		}elseif( is_array($array_or_string) ){
			foreach ( $array_or_string as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = recursive_sanitize_text_field($value);
				}
				else {
					$value = sanitize_text_field( $value );
				}
			}
		}

		return $array_or_string;
	}
	
	
	public function mediabayAjaxMoveMultipleMedia()
	{
		$IDs 		= $this->recursive_sanitize_text_field($_POST['IDs']);
		$folderID	= sanitize_text_field($_POST['folderID']);
        $result 	= array();

        foreach ($IDs as $ID){
            $termList 	= wp_get_post_terms( sanitize_text_field($ID), MEDIABAY_FOLDER, array( 'fields' => 'ids' ) );
            $from 		= -1;

            if(count($termList)){
                $from 	= $termList[0];
            }

            $obj 		= (object) array('id' => $ID, 'from' => $from, 'to' => $folderID);
            $result[] 	= $obj;

            wp_set_object_terms( $ID, intval($folderID), MEDIABAY_FOLDER, false );

        }

		
		$buffyArray 		= array(
			'result' 		=> $result,
		);
		die(json_encode($buffyArray));
		
	}
	
	public function mediabayAjaxGetTermsByMedia()
	{
		$error		= 'no';
		$nonce 		= sanitize_text_field($_POST['nonce']);
		$terms		= array();
		
		if(!wp_verify_nonce($nonce, 'ajax-nonce')){
			$error 	= 'yes';
		}
        if(!isset($_POST['ID'])){
            $error 	= 'yes';
        }else{
			$ID		= (int) sanitize_text_field($_POST['ID']);
			$terms  = get_the_terms($ID, MEDIABAY_FOLDER);
		}
		
		$buffyArray 		= array(
			'terms' 		=> $terms,
			'error' 		=> $error,
			'id' 			=> $ID,
		);
		die(json_encode($buffyArray));
	}
	
	public function mediabayAjaxMoveSingleMedia()
	{
		$error							= 'no';
		
		if (!isset($_POST['mediaID'])){
			 $error 					= 'yes';
		}else{
			$mediaID 					= absint(sanitize_text_field($_POST['mediaID']));
			
			if(empty($_POST['attachments']) || empty($_POST['attachments'][ $mediaID ])){
				 $error 				= 'yes';
			}else{
				$attachment_data 		= $_POST['attachments'][ $mediaID ];
				$post 					= get_post( $mediaID, ARRAY_A );
				if('attachment' != $post['post_type']){
					$error 				= 'yes';
				}else{
					$post 				= apply_filters( 'attachment_fields_to_save', $post, $attachment_data );

					if(isset($post['errors'])){
						$errors 		= $post['errors']; 
						unset( $post['errors'] );
					}

					wp_update_post($post);

					wp_set_object_terms( $mediaID, intval(sanitize_text_field($_POST['folderID'])), MEDIABAY_FOLDER, false );
					if (!$attachment 	= wp_prepare_attachment_for_js($mediaID)){
						$error 			= 'yes';
					}
				}
			}
		}
		
		
		$buffyArray 		= array(
			'attachment' 		=> $attachment,
			'error' 			=> $error,
		);
		die(json_encode($buffyArray));
		
	}
	
	
	public function mediabaySlugGenerator($categoryName,$ID)
	{
		global $wpdb;
		$categoryName 	= strtolower($categoryName);
	   	$newSlug		= preg_replace('/[^A-Za-z0-9-]+/', '-', $categoryName);
		
		$count 			= $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}terms WHERE slug='".$newSlug."' AND term_id<>".$ID );
		if($count > 0){
			$newSlug	= $newSlug . '1';
			$newSlug	= $this->mediabaySlugGenerator($newSlug,$ID);
		}
		return $newSlug;
	}
	
	public function mediabayAjaxUpdateFolderPosition()
	{
		$results 	= sanitize_text_field($_POST["data"]);
		$results 	= explode('#', $results);
		foreach ($results as $result) {
			$result = explode(',', $result);
			update_term_meta($result[0], 'folder_position', $result[1]);
		}
		die();
	}
	
	public function mediabayAjaxMoveCategory()
	{
		$current 		= sanitize_text_field($_POST["current"]);
		$parent 		= sanitize_text_field($_POST["parent"]);
		
		
		$checkError 	= wp_update_term($current, MEDIABAY_FOLDER, array(
			'parent' 	=> $parent
		));
				

		if(is_wp_error($checkError)){
			$error		= 'yes';
		}else{
			$error		= 'no';
		}
		$buffyArray 	= array(
			'error' 	=> $error,
		);
		die(json_encode($buffyArray));
		
	}
	
	public static function mediabayCheckMetaName($name, $parent)
	{
		if(!$parent){ $parent = 0; }
 		
		$terms 	= get_terms( MEDIABAY_FOLDER, array('parent' => $parent, 'hide_empty' => false) );
		$check 	= true;

		if(count($terms)){
			foreach ($terms as $term){
				if($term->name === $name){
					$check = false;
					break;
				}
			}
		}else{
			return $name;
		}

		
		if($check){
			return $name;			
		}

		$arr = explode('_', $name);	

		if($arr && count($arr) > 1){	
			$suffix = array_values(array_slice($arr, -1))[0];

			array_pop($arr);

			$originName = implode($arr);

			if(intval($suffix)){
				$name = $originName . '_' . (intval($suffix)+1);
			}

		}else{
			$name = $name . '_1';
		}		

		$name = self::mediabayCheckMetaName($name, $parent);

		return $name;

	}
	
	public function mediabayAddFolderToAttachments()
	{
		register_taxonomy(	MEDIABAY_FOLDER, 
			 array( "attachment" ), 
		 	 array( "hierarchical" 				=> true, 
				    "labels"					=> array(), 
					'show_ui' 					=> true,
					'show_in_menu' 				=> false,
					'show_in_nav_menus'			=> false,
					'show_in_quick_edit'		=> false,
					'update_count_callback' 	=> '_update_generic_term_count',
					'show_admin_column'			=> false,
					"rewrite" 					=> false 
			)
		);
	}
	
	
	public function mediabayPostsClauses($clauses, $query)
	{
		global $wpdb;
		
		if (isset($_GET['cc_mediabay_folder'])){
			
			$folder 		= sanitize_text_field($_GET['cc_mediabay_folder']);
			
			if (!empty($folder) != ''){
				$folder 	= (int)$folder;
				$wpdbPrefix	= $wpdb->prefix;
				
				if($folder > 0){
					$clauses['where'] 	.= ' AND ('.$wpdbPrefix.'term_relationships.term_taxonomy_id = '.$folder.')';
					$clauses['join'] 	.= ' LEFT JOIN '.$wpdbPrefix.'term_relationships ON ('.$wpdbPrefix.'posts.ID = '.$wpdbPrefix.'term_relationships.object_id)';
				}else{
					
					$folders = get_terms(MEDIABAY_FOLDER, array(
						'hide_empty' => false
					));
					$folderIDs = array();
					foreach ($folders as $k => $folder) {
						$folderIDs[] = $folder->term_id;
					}
					
					$folderIDs = esc_sql($folderIDs);
					
					$extraQuery = "SELECT `ID` FROM ".$wpdbPrefix."posts LEFT JOIN ".$wpdbPrefix."term_relationships ON (".$wpdbPrefix."posts.ID = ".$wpdbPrefix."term_relationships.object_id) WHERE (".$wpdbPrefix."term_relationships.term_taxonomy_id IN (".implode(', ', $folderIDs)."))";
					$clauses['where'] .= " AND (".$wpdbPrefix."posts.ID NOT IN (".$extraQuery."))";
				}
			}
		}
		
		return $clauses;
	}
	
	
	
	public function mediabayAjaxCheckDeletingMedia()
	{
		$attachmentID	= '';
		$error			= 'no';
		$terms			= array();
		$ajaxNonce		= sanitize_text_field($_POST['ajaxNonce']);

		if(!wp_verify_nonce($ajaxNonce,'ajax-nonce' )){
			$error		= 'yes';
		}
		
		if(!isset($_POST['attachmentID'])){
           $error		= 'yes';
        }
        if($error == 'no'){
			$attachmentID	= absint(sanitize_text_field($_POST['attachmentID']));
        	$terms  		= get_the_terms($attachmentID, MEDIABAY_FOLDER);
		}
		
		$buffyArray 	= array(
			'error' 	=> $error,
			'terms' 	=> $terms,
		);
		die(json_encode($buffyArray));
    }

}
new Mediabay_Sidebar();


// Custom Category Walker
class Mediabay_Walker_Category_Mediagridfilter extends \Walker_CategoryDropdown 
{
    function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 )
	{
		$space 				= str_repeat( '&nbsp;', $depth * 3 );
		
		if(isset($category->name)){
			$folderName		= $category->name;
			$folderID		= $category->term_id;
			$folderName 	= apply_filters( 'list_cats', $folderName, $category );
			
			$output .= ',{"folderID":"' . $folderID . '",';
			$output .= '"folderName":"' . $space . $folderName . '"}';
			
		}	
    }
}