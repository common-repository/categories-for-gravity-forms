<?php

GFForms::include_addon_framework();

class GFormsCategoriesAddOn extends GFAddOn {

	protected $_version = GFORMS_CATEGORIES_ADDON_VERSION;
	protected $_min_gravityforms_version = '2.5.0';
	protected $_slug = 'gravityforms-categories';
	protected $_path = 'gravityforms-categories/gravityforms-categories.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Form Categories';
	protected $_short_title = 'Categories';
	protected $_gforms_category_items;
	protected $_gforms_categories_plugin_settings = null;

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFormsCategoriesAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFormsCategoriesAddOn();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function pre_init() {

		parent::pre_init();

		//Registering taxonomy for gravity forms
		add_action( 'init', array( $this, 'register_taxonomy_gforms_categories'), 10, 1 );
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {

		parent::init();
		
		$plugin_settings = $this->_gforms_categories_plugin_settings = parent::get_plugin_settings();

		$this->_title = (isset($plugin_settings['title']) && !empty($plugin_settings['title'])) ? $plugin_settings['title'] : $this->_title;
		$this->_short_title = (isset($plugin_settings['short_title']) && !empty($plugin_settings['short_title'])) ? $plugin_settings['short_title'] : $this->_short_title;

		add_filter( 'gform_form_list_columns', array( $this, 'add_gform_listing_column_for_categories'), 10, 1 );
		add_action( 'gform_form_list_column_gforms_categories', array( $this, 'gform_listing_column_value_for_categories'), 99, 1 );
		add_filter( 'gform_form_list_forms', array( $this, 'filter_forms_listing_for_categories'), 10, 6 );

	}

	/**
	 * Registering taxonomy for gravity forms
	 * @return string
	 */
	public function register_taxonomy_gforms_categories() {
		
		if ( ! taxonomy_exists( 'gforms_categories' ) ) {

			register_taxonomy(
				'gforms_categories',
				'', //Not tied with any post type. Using separetly with Gravity form 
				array(
					'label'             => __( 'Gforms Categories', 'gravityforms-categories' ),
					'rewrite'           => array( 'slug' => 'gforms_categories' ),
					'hierarchical'      => true,
				)
			);
		}
		
		$gforms_categories_terms = get_terms( array(
			'taxonomy' => 'gforms_categories',
			'hide_empty' => false,
		) );
		//var_dump($gforms_categories_terms); //exit();

		if (is_array($gforms_categories_terms) && count($gforms_categories_terms)) {

			foreach ($gforms_categories_terms as $key => $term) {
				$this->_gforms_category_items[$term->term_id] = $term;
			}
		}
		//var_dump($this->_gforms_category_items); exit();

	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 * @return string
	 */
	public function get_menu_icon() {
		$plugin_settings = $this->_gforms_categories_plugin_settings;
		return (isset($plugin_settings['menu_icon']) && !empty($plugin_settings['menu_icon'])) ? $plugin_settings['menu_icon'] : 'dashicons-category';
	}

	/**
	 * Override this function to customize the plugin page title
	 */
	public function plugin_page_title() {
		return $this->_title;
	}

	/**
	 * Override this function to customize the plugin page icon
	 */
	public function plugin_page_icon() {
		return '';
	}

	/**
	 * Creates a custom page for this add-on.
	 */
	public function plugin_page() {

		GFForms::admin_header( array(), false );
		
		//global $taxnow, $taxonomy;
		$taxnow = $taxonomy = 'gforms_categories';
		$tax = get_taxonomy( $taxnow );
		//var_dump($this->_gforms_category_items); exit();

		wp_enqueue_script( 'admin-tags' );
		if ( current_user_can( $tax->cap->edit_terms ) ) {
			wp_enqueue_script( 'inline-edit-tax' );
		}

		require_once ABSPATH . 'wp-admin/includes/edit-tag-messages.php';
		?>
		<style type="text/css">
			.gforms-categories-main-div{
				display: flex;
			}
			.gform-settings-panel-gforms-categories.left-part{
				flex: 3;
				margin-right: 15px;
				height: max-content;
			}
			.gform-settings-panel-gforms-categories.right-part {
				flex: 7;
				margin-left: 15px;
				box-shadow: none;
				height: max-content;
				border: 0;
				background-color: transparent;
			}
			.gform-settings-panel-gforms-categories.right-part .gform-settings-panel__content {
				padding: 0;
				border-top: 0;
			}
			.gform-settings-panel-gforms-categories.right-part .gform-settings-panel__content .tablenav.top {
				margin-top: 0;
				padding-top: 0;
			}
			.gform_settings_form {
				display: block;
			}
			.gforms-categories-term-add-container {
				margin-top: 20px;
			}
			.hidden {
				display: none !important;
			}
			.tablenav-pages .current-page {
				max-width: unset;
				width: unset !important;
			}
			.column-posts a {
				color: unset;
			}
			.has-row-actions .row-actions span.edit,
			.has-row-actions .row-actions span.view {
				display: none;
			}
		</style>
		<?php
			if ( $message ) :
				$class = ( isset( $_REQUEST['error'] ) ) ? 'error' : 'updated'; ?>
				<div id="message" class="<?php esc_html_e($class); ?> notice is-dismissible"><p><?php esc_html_e($message); ?></p></div>
				<?php $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message', 'error' ), $_SERVER['REQUEST_URI'] );
			endif;
		?>
		<div id="ajax-response"></div>
		<div class="gforms-categories-main-div">
			<div class="gform-settings-panel gform-settings-panel-gforms-categories left-part">
				<header class="gform-settings-panel__header">
					<legend class="gform-settings-panel__title"><?php esc_html_e( 'Add New', 'gravityforms-categories' ) ; esc_html_e('&nbsp;'.$this->_short_title); ?></legend>
				</header>
				<div class="gform-settings-panel__content">
					<form id="addtag" class="gform_settings_form" action="edit-tags.php" method="post" enctype="multipart/form-data">
						<input type="hidden" name="action" value="add-tag">
						<input type="hidden" name="screen" value="edit-gforms_categories">
						<input type="hidden" name="taxonomy" value="gforms_categories">
						<input type="hidden" name="post_type" value="post">
						<?php wp_nonce_field( 'add-tag', '_wpnonce_add-tag' ); ?>

						<div id="gform_setting_gforms_categories_name" class="gform-settings-field gform-settings-field__text">
							<div class="gform-settings-field__header">
								<label class="gform-settings-label" for="tag-name"><?php _e( 'Name' ); ?></label>
							</div>
							<span class="gform-settings-input__container">
								<input type="text" name="tag-name" value="" id="tag-name">
							</span>
						</div>

						<div id="gform_setting_gforms_categories_slug" class="gform-settings-field gform-settings-field__text">
							<div class="gform-settings-field__header">
								<label class="gform-settings-label" for="tag-slug"><?php _e( 'Slug' ); ?></label>
							</div>
							<span class="gform-settings-input__container">
								<input type="text" name="slug" value="" id="tag-slug">
							</span>
						</div>

						<div id="gform_setting_gforms_categories_description" class="gform-settings-field gform-settings-field__textarea">
							<div class="gform-settings-field__header">
								<label class="gform-settings-label" for="tag-description"><?php _e( 'Description' ); ?></label>
							</div>
							<span class="gform-settings-input__container">
								<textarea name="description" id="tag-description" rows="5" cols="40" spellcheck="false"></textarea>
							</span>
						</div>

						<div class="gforms-categories-term-add-container submit">
							<button type="submit" id="submit" name="submit" form="addtag" class="primary button large"><?php _e( 'Add' ); ?> &nbsp;→</button>
							<span class="spinner"></span>
						</div>
					</form>
				</div>
			</div>
			<div class="gform-settings-panel gform-settings-panel-gforms-categories right-part">
				<div class="gform-settings-panel__content">
					<form id="posts-filter" action="<?php echo esc_url(admin_url(wp_nonce_url("edit-tags.php?taxonomy=$taxonomy"))); ?>" method="post" enctype="multipart/form-data">
						<input type="hidden" name="taxonomy" value="gforms_categories" />
						<input type="hidden" name="post_type" value="post" />

						<?php
							
							$wp_list_table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-' . $taxonomy ) );
							//$wp_list_table->search_box( $tax->labels->search_items, 'tag' );
							//$wp_list_table->prepare_items();
							//$wp_list_table->display();
							$wp_list_table->display_tablenav( 'top' );
							?>
							<table class="wp-list-table widefat fixed striped table-view-list tags">
								<thead>
									<tr>
										<td id="cb" class="manage-column column-cb check-column">
											<label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All' ); ?></label>
											<input id="cb-select-all-1" type="checkbox">
										</td>

										<th scope="col" class="manage-column column-name column-primary sortable desc">
											<a><span><?php _e( 'Name' ); ?></span></a>
										</th>

										<th scope="col" class="manage-column column-description sortable desc">
											<a><span><?php _e( 'Description' ); ?></span></a>
										</th>

										<th scope="col" class="manage-column column-slug sortable desc">
											<a><span><?php _e( 'Slug' ); ?></span></a>
										</th>

										<th scope="col" class="manage-column column-posts sortable desc">
											<a><span><?php _e( 'Count' ); ?></span></a>
										</th>
									</tr>
								</thead>
								<tbody id="the-list" data-wp-lists="list:tag">
									<?php
										//var_dump($this->_gforms_category_items);
										if (is_array($this->_gforms_category_items) && count($this->_gforms_category_items)) {

											foreach ($this->_gforms_category_items as $key => $value) { 
                                                                                                $cat_id = $value->term_id;
                                                                                                $cat_name = $value->name;
                                                                                                $cat_slug = $value->slug;
                                                                                                $cat_desc = $value->description;
                                                                                                $cat_count = $value->count;
                                                                                                ?>
												<tr id="tag-<?php _e($cat_id); ?>" class="gf-locking level-0">
													<th scope="row" class="check-column">
														<label class="screen-reader-text" for="cb-select-1"><?php _e( 'Select' ); ?></label>
														<input type="checkbox" class="gform_list_checkbox" name="delete_tags[]" value="<?php _e($cat_id); ?>">
													</th>
													<td class="column-name has-row-actions column-primary" data-colname="Name">
														<strong><a><?php _e($cat_name); ?></a></strong>
														<br>
														<div class="hidden" id="inline_<?php _e($cat_id); ?>">
															<div class="name"><?php _e($cat_name); ?></div>
															<div class="slug"><?php _e($cat_slug); ?></div>
															<div class="parent">0</div>
														</div>
														<div class="row-actions">
															<span class="gf_form_toolbar_editor inline hide-if-no-js"><button type="button" class="button-link editinline" aria-label="Quick edit" aria-expanded="false"><?php _e( 'Edit' ); ?></button> | </span>
															<span class="trash"><a class="gf_toolbar_active " aria-label="Editor" href="<?php echo esc_url( admin_url( wp_nonce_url( "edit-tags.php?action=delete&taxonomy=$taxonomy&tag_ID=$cat_id", 'delete-tag_' . $cat_id ) ) ); ?>" target=""><?php _e( 'Delete' ); ?></a></span>
														</div>
													</td>
													<td class="column-description" data-colname="Description"><?php _e($cat_desc); ?></td>
													<td class="column-slug" data-colname="Slug"><?php _e($cat_slug); ?></td>
													<td class="column-posts" data-colname="ID"><?php _e($cat_count); ?></td>
												</tr>
											<?php }	
										} else {
											?>
											<tr class="no-items">
												<td class="colspanchange" colspan="5"><?php printf( esc_html( 'Not have any %s yet.', 'gravityforms-categories' ), $this->_short_title ); ?></td>
											</tr>
											<?php
										}
									?>
								</tbody>
								<tfoot>
									<tr>
										<td class="manage-column column-cb check-column">
											<label class="screen-reader-text" for="cb-select-all-2"><?php _e( 'Select All' ); ?></label><input id="cb-select-all-2" type="checkbox">
										</td>

										<th scope="col" class="manage-column column-primary sortable desc">
											<a><span><?php _e( 'Name' ); ?></span></a>
										</th>

										<th scope="col" class="manage-column sortable desc">
											<a><span><?php _e( 'Description' ); ?></span></a>
										</th>

										<th scope="col" class="manage-column sortable desc">
											<a><span><?php _e( 'Slug' ); ?></span></a>
										</th>

										<th scope="col" class="manage-column sortable desc">
											<a><span><?php _e( 'Count' ); ?></span></a>
										</th>
									</tr>
								</tfoot>
							</table>
							<?php
							$wp_list_table->display_tablenav( 'bottom' );
						?>
					</form>
				</div>
			</div>
		</div>
		<?php

		$wp_list_table->inline_edit();

		GFForms::admin_footer();
	}

	/**
	 * Plugin page container
	 * Target of the plugin menu left nav icon. Displays the outer plugin page markup and calls plugin_page() to render the actual page.
	 * Override plugin_page() in order to provide a custom plugin page
	 */
	public function plugin_page_container() {
		?>
		<div class="wrap">
			<?php

				$this->plugin_page();
			?>
		</div>
	<?php
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'Form Categories Add-On Settings', 'gravityforms-categories' ),
				//'description'  => esc_html__( 'Plugin Settings page description', 'gravityforms-categories' ),
				'fields' => array(
					array(
						'name'              => 'title',
						'tooltip'           => esc_html__( 'Rename categories label (Example: \'Categories\')', 'gravityforms-categories' ),
						'label'             => esc_html__( 'Categories Label', 'gravityforms-categories' ),
						'type'              => 'text',
						'class'             => 'small',
					),
					array(
						'name'              => 'short_title',
						'tooltip'           => esc_html__( 'Short label for Categories (Example: \'Category\')', 'gravityforms-categories' ),
						'label'             => esc_html__( 'Categories Short Label', 'gravityforms-categories' ),
						'type'              => 'text',
						'class'             => 'small',
					),
					array(
						'name'              => 'uncategorized_name',
						'tooltip'           => esc_html__( 'Choose label for Uncategorized forms (Example: \'Uncategorized\')', 'gravityforms-categories' ),
						'label'             => esc_html__( 'Uncategorized Label', 'gravityforms-categories' ),
						'type'              => 'text',
						'class'             => 'small',
					),
					array(
						'name'              => 'menu_icon',
						'tooltip'           => esc_html__( 'Add dashicons icon class for menu item (Example: \'dashicons-category\')', 'gravityforms-categories' ),
						'label'             => esc_html__( 'Menu Icon Class (dashicons)', 'gravityforms-categories' ),
						'type'              => 'text',
						'class'             => 'small',
					),
				)
			),
		);
	}

	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		
		$form_category_choices = array();

		if (is_array($this->_gforms_category_items) && count($this->_gforms_category_items)) {

			foreach ($this->_gforms_category_items as $key => $value) {
				array_push($form_category_choices, array(
					'label' => $value->name,
					'name'  => 'gforms_categories['.$value->term_id.']',
				));
			}	
		}
		
		return array(
			array(
				'title'  => $this->_title,
				'fields' => array(
					array(
						'label'   => esc_html__( 'Select', 'gravityforms-categories' ).' '.$this->_short_title,
						'type'    => 'checkbox',
						'name'    => 'gforms_categories',
						'choices' => $form_category_choices,
						/*'save_callback'  => function( $values ) use ( &$form ) {
							var_dump($values);
							var_dump($form['gravityforms-categories']);
							exit();
						},*/
					),
				),
			),
		);
	}

	/**
	 * Saves form settings to form object
	 *
	 * @param array $form
	 * @param array $settings
	 *
	 * @return true|false True on success or false on error
	 */
	public function save_form_settings( $form, $settings ) {

		$previous_settings = isset($form['gravityforms-categories']['gforms_categories']) ? $form['gravityforms-categories']['gforms_categories'] : array();
		$new_settings = $settings['gforms_categories'];
		//var_dump($previous_settings);
		//var_dump($new_settings);

		//Updating term post/form count directly in db
		foreach ($new_settings as $term_id => $val) {
			if ( is_array($previous_settings) && array_key_exists($term_id, $previous_settings)) {

				//For items already created
				if ($new_settings[$term_id] != $previous_settings[$term_id]) {

					$term = get_term( $term_id , 'gforms_categories' );
					
					if ( !is_wp_error( $term ) ) {

						$term_taxonomy_id = $term->term_taxonomy_id;
						$count = $term->count;

						if ($new_settings[$term_id] == '1') { //Increase count

							$count++;
							$this->update_term_form_count( $term_taxonomy_id, $count );

						} elseif ($new_settings[$term_id] == '0') { //Decrease count

							if ( $count > 0 ) {
								$count--;
							}
							$this->update_term_form_count( $term_taxonomy_id, $count );

						} else {
							//No action
						}
					}
				}
			} else {
				//For new created items (which will not there in $previous_settings)
				if ($new_settings[$term_id] == '1') { //Increase count

					$term = get_term( $term_id , 'gforms_categories' );

					if ( !is_wp_error( $term ) ) {

						$term_taxonomy_id = $term->term_taxonomy_id;
						$count = $term->count;

						$count++;
						$this->update_term_form_count( $term_taxonomy_id, $count );
					}
				}
			}
		}

		$form[ $this->_slug ] = $settings;
		$result = GFFormsModel::update_form_meta( $form['id'], $form );

		return ! ( false === $result );
	}

	/**
	 * Update term count directly in db table
	 *
	 * @param array $term_taxonomy_id
	 * @param array $count
	 */
	public function update_term_form_count( $term_taxonomy_id = 0, $count = 0 ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'term_taxonomy';

		if ( is_numeric($term_taxonomy_id) && is_numeric($count) && ($term_taxonomy_id > 0) && ($count > -1) ) {

			$result = $wpdb->query( $wpdb->prepare( "UPDATE `$table_name` SET `count` = %d WHERE `$table_name`.`term_taxonomy_id` = %d", $count, $term_taxonomy_id ) );
		}
	}

	/**
	 * Get categories assigned to provided form
	 */
	public function get_gforms_categories( $form ){

		if (empty($form)) {
			return;
		}

		if (is_numeric($form) && !is_object($form)) {
			$form = GFAPI::get_form( $form );
		} else {
			$form = GFAPI::get_form( $form->id );
		}

		$settings = parent::get_form_settings($form);
		return (isset($settings['gforms_categories']) && is_array($settings['gforms_categories']) ) ? $settings['gforms_categories'] : null;
	}

	/**
	 * Form list view add category column
	 */
	public function add_gform_listing_column_for_categories( $columns ){

            $columns = array_slice( $columns, 0, 3, true ) + array( 'gforms_categories' => $this->_short_title ) + array_slice( $columns, 3, count( $columns ) - 3, true );
	    return $columns;
	}

	/**
	 * Form list view add category column
	 */
	public function gform_listing_column_value_for_categories( $form ){

		$_gforms_categories = $this->get_gforms_categories($form);
		$has_categories = array();

		$plugin_settings = $this->_gforms_categories_plugin_settings;
		$uncategorized_lbl = (isset($plugin_settings['uncategorized_name']) && !empty($plugin_settings['uncategorized_name'])) ? $plugin_settings['uncategorized_name'] : 'Uncategorized';

		if ( empty($_gforms_categories) ) {

			_e($uncategorized_lbl);

		} else {

			if ( !empty($_gforms_categories) && count($_gforms_categories) && isset($this->_gforms_category_items) && count($this->_gforms_category_items) ) {

				foreach ($_gforms_categories as $term_id => $is_set) {

					if (!array_key_exists($term_id, $this->_gforms_category_items)) {
						continue;
					}

					if ($is_set) {
						$has_categories[$term_id] = $this->_gforms_category_items[$term_id]->name;
					}
				}

				if (count($has_categories)) {

					$categories_markup = array();

					foreach ($has_categories as $slug => $name) {

						$categories_markup[] = '<a href="'. esc_url(admin_url('admin.php?page=gf_edit_forms&category='.$slug)).'" >'.$name.'</a>';
					}
					
					_e(implode(', ', $categories_markup));

				} else {
					_e($uncategorized_lbl);
				}
			} else {
				_e($uncategorized_lbl);
			}
		}
	}

	/**
	 * Filter form on list view
	 */
	public function filter_forms_listing_for_categories( $forms, $search_query, $active, $sort_column, $sort_direction, $trash ){

		if (isset($_GET['page']) && $_GET['page'] == 'gf_edit_forms' && isset($_GET['category']) && !empty($_GET['category'])) {

                    $filter_category = sanitize_text_field( $_GET['category'] );

		    foreach ($forms as $key => $form) {

		    	$_gforms_categories = $this->get_gforms_categories($form);

				if ( !empty($_gforms_categories) && count($_gforms_categories) ) {

					if (!array_key_exists($filter_category, $_gforms_categories) || $_gforms_categories[$filter_category] == 0 ) {
						unset($forms[$key]);
					}
				} else {
					unset($forms[$key]);
				}
		    }
		}

	    return $forms;
	}

	/**
	 * Returns the plugin license status
	 *
	 * @since Unknown
	 *
	 * @return array|false
	 */
	public function get_license_status() {
		return get_option( 'gravityformsaddon_' . $this->_slug . '_license_status_settings' );
	}

}