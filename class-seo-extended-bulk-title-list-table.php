<?php 
	/*
	 * Comments to come later
	 *
	 *
	 */

	if( ! class_exists( 'WP_List_Table' ) ) {
    	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	class SEO_Extended_Bulk_Title_List_Table extends WP_List_Table {

		function __construct() {
			parent::__construct( array( 
				'singular' => 'seo_extended_bulk_title',
				'plural' => 'seo_extended_bulk_titles',
				'ajax' => true
			) );
		}

		function display_tablenav( $which ) {
			?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">

				<form id="posts-filter" action="" method="get">
					<input type="hidden" name="page" value="seo_extended_titles" />
					<?php if( !empty( $_REQUEST['post_status'] ) ) {?> 
						<input type="hidden" name="post_status" value="<?php echo esc_attr($_REQUEST['post_status']); ?>" />
					<?php } ?>
					
					<?php
						$this->extra_tablenav( $which );
						$this->pagination( $which );
					?>

					<br class="clear" />
				</form>
			</div>

			<?php
		}

		function get_views() {
			global $wpdb;


			$status_links = array();

			$post_types = get_post_types( array( 'public' => true, 'exclude_from_search' => false ) );
			$post_types = "'" . implode( "', '", $post_types ) . "'";

			$states = get_post_stati( array('show_in_admin_all_list' => true) );
			$states['trash'] = 'trash';
			$all_states = "'" . implode( "', '", $states ) . "'";

			$total_posts = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status IN ($all_states) AND post_type IN ($post_types)" );


			$class = empty( $_REQUEST['post_status'] ) ? ' class="current"' : '';
			$status_links['all'] = "<a href='admin.php?page=seo_extended_titles'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

			foreach( get_post_stati( array('show_in_admin_all_list' => true), 'objects' ) as $status ) {

				$status_name = $status->name;

				$total = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status IN ('$status_name') AND post_type IN ($post_types)" );

				if( $total == 0 ) {
					continue;
				}

				if( isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status'] ) {
					$class = ' class="current"';
				} else {
					$class = '';
				}

				$status_links[ $status_name ] = "<a href='admin.php?page=seo_extended_titles&amp;post_status=$status_name'$class>" . sprintf( translate_nooped_plural( $status->label_count, $total ), number_format_i18n( $total ) ) . '</a>';

			}
			$trashed_posts = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status IN ('trash') AND post_type IN ($post_types)" );
			$class =  ( isset($_REQUEST['post_status']) && 'trash' == $_REQUEST['post_status'] ) ? 'class="current"' : '';
			$status_links[ 'trash' ] = "<a href='admin.php?page=seo_extended_titles&amp;post_status=trash'$class>" . sprintf( _nx( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $trashed_posts, 'posts' ), number_format_i18n( $trashed_posts ) ) . '</a>';

			return $status_links;
		}

		function extra_tablenav( $which ) {

			if( 'top' == $which ) {
				echo '<div class="alignleft actions">';
				global $wpdb;

				$post_types = get_post_types( array( 'public' => true, 'exclude_from_search' => false ) );
				$post_types = "'" . implode( "', '", $post_types ) . "'";

				$states = get_post_stati( array('show_in_admin_all_list' => true) );
				$states['trash'] = 'trash';
				$all_states = "'" . implode( "', '", $states ) . "'";

				$query = "SELECT DISTINCT post_type FROM $wpdb->posts WHERE post_status IN ($all_states) AND post_type IN ($post_types) ORDER BY 'post_type' ASC";
				$post_types = $wpdb->get_results( $query );

				$selected = !empty( $_REQUEST['post_type_filter'] ) ? $_REQUEST['post_type_filter'] : -1;

				$options = '<option value="-1">Show All Post Types</option>';

				foreach( $post_types as $post_type ) {
					$obj = get_post_type_object( $post_type->post_type );
					$options .= sprintf( '<option value="%2$s" %3$s>%1$s</option>', $obj->labels->name, $post_type->post_type, selected( $selected, $post_type->post_type, false ) );
				}



				echo sprintf( '<select name="post_type_filter">%1$s</select>' , $options );
				submit_button( __( 'Filter' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
				echo "</div>";
				echo "</form>";

			}
			else if( 'bottom' == $which ) {
				
			}

		}

		function get_columns() {
			return $columns = array(
				'col_page_title' => __('WP Page Title'),
				'col_post_type' => __('Post Type'),
				'col_post_status' => __('Post Status'),
				'col_page_slug' => __('Page URL/Slug'),
				'col_existing_yoast_seo_title' => __('Existing Yoast SEO Title'),
				'col_new_yoast_seo_title' => __('New Yoast SEO Title'),
				'col_row_action' => __('Action')
			);
		}

		function get_sortable_columns() {
			return $sortable = array(
				'col_page_title' => array( 'post_title', true ),
				'col_post_type' => array( 'post_type', false ),
				'col_existing_yoast_seo_title' => array( 'seo_title', false )
			);
		}

		function prepare_items() {
			global $wpdb, $_wp_column_headers;

			$screen = get_current_screen();

			$post_types = get_post_types( array( 'exclude_from_search' => false ) );
			$post_types = "'" . implode( "', '", $post_types ) . "'";

			$query = "SELECT ID, post_title, post_type, meta_value AS seo_title, post_status, post_modified FROM $wpdb->posts LEFT JOIN (SELECT * FROM $wpdb->postmeta WHERE meta_key = '_yoast_wpseo_title')a ON a.post_id = $wpdb->posts.ID WHERE post_status IN (%s)";

			//	Filter Block

			if( !empty( $_REQUEST['post_type_filter'] ) && get_post_type_object( $_REQUEST['post_type_filter'] ) ) {
				$query .= " AND post_type='{$_REQUEST['post_type_filter']}'";
			} else {
				$query .= " AND post_type IN ($post_types)";
			}

			//	Order By block

			$orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'post_title';
		    $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : 'ASC';
		    if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }


			$states = get_post_stati( array('show_in_admin_all_list' => true) );
			$states['trash'] = 'trash';
			$all_states = "'" . implode( "', '", $states ) . "'";

			if( empty( $_REQUEST['post_status'] ) ) {
				$query = sprintf( $query, $all_states );
			} else {
				$requested_state = $_REQUEST['post_status'];
				if( in_array( $requested_state, $states ) ) {
					$query = sprintf( $query, "'$requested_state'" );
				} else {
					$query = sprintf( $query, $all_states );
				}
			}
			// publish, draft, future, private

			$total_items = $wpdb->query( $query );

			$per_page = $this->get_items_per_page( 'seo_extended_posts_per_page', 10 );

			$paged = !empty( $_GET["paged"] ) ? mysql_real_escape_string( $_GET["paged"] ) : '';

			if( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0 ) {
				$paged = 1;
			}

			$total_pages = ceil( $total_items / $per_page );

			if( !empty( $paged ) && !empty( $per_page ) ) {
				$offset = ($paged - 1) * $per_page;
				$query .= ' LIMIT ' . (int)$offset . ',' . (int)$per_page;
			}

			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'total_pages' => $total_pages,
				'per_page' => $per_page
			) );

			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$this->items = $wpdb->get_results( $query );

		}

		function display_rows() {

			$records = $this->items;

			list( $columns, $hidden ) = $this->get_column_info();

			if( !empty( $records ) ) {
				foreach( $records as $rec ) {
					echo '<tr id="record_' . $rec->ID . '">';

					foreach( $columns as $column_name => $column_display_name ) {

						$class = sprintf('class="%1$s column-%1$s"', $column_name );
						$style = "";

						if( in_array( $column_name, $hidden ) ) {
							$style = ' style="display:none;"';
						}

						$attributes = $class . $style;

						switch( $column_name ) {
							case 'col_page_title':
								echo sprintf( '<td %2$s><strong>%1$s</strong>', stripslashes( $rec->post_title ), $attributes );

								$post_type_object = get_post_type_object( $rec->post_type );
								$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $rec->ID );

								$actions = array();

								if( $can_edit_post && 'trash' != $rec->post_status ) {
									$actions['edit'] = '<a href="' . get_edit_post_link( $rec->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">' . __( 'Edit' ) . '</a>';
								}

								if ( $post_type_object->public ) {
									if ( in_array( $rec->post_status, array( 'pending', 'draft', 'future' ) ) ) {
										if ( $can_edit_post )
											$actions['view'] = '<a href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $rec->ID ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $rec->post_title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
									} elseif ( 'trash' != $rec->post_status ) {
										$actions['view'] = '<a href="' . get_permalink( $rec->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $rec->post_title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
									}
								}

								echo $this->row_actions( $actions );

								echo '</td>';

								break;
							case 'col_page_slug':
								$permalink = get_permalink( $rec->ID );
								$display_slug = str_replace( get_bloginfo( 'url' ), '', $permalink );
								echo sprintf( '<td %2$s><a href="%3$s" target="_blank">%1$s</a></td>', stripslashes( $display_slug ), $attributes, $permalink );
								break;
							case 'col_post_type':
								$post_type = get_post_type_object( $rec->post_type );
								echo sprintf( '<td %2$s>%1$s</td>', $post_type->labels->singular_name, $attributes);
								break;
							case 'col_post_status':
								$post_status = get_post_status_object( $rec->post_status );
								echo sprintf( '<td %2$s>%1$s</td>', $post_status->label, $attributes );
								break;
							case 'col_existing_yoast_seo_title':
								echo sprintf( '<td %2$s id="seo-extended-existing-title-%3$s">%1$s</td>', ( ($rec->seo_title ) ? $rec->seo_title : '' ), $attributes, $rec->ID );
								break;
							case 'col_new_yoast_seo_title':
								$input = sprintf( '<input type="text" id="%1$s" name="%1$s" class="seo-extended-new-title" data-id="%2$s" />', 'seo-extended-new-title-' . $rec->ID, $rec->ID );
								echo sprintf( '<td %2$s>%1$s</td>', $input , $attributes );
								break;
							case 'col_row_action':
								$actions = sprintf( '<a href="#" class="seo-extended-save" data-id="%1$s">Save</a> | <a href="#" class="seo-extended-save-all">Save All</a>', $rec->ID );
								echo sprintf( '<td %2$s>%1$s</td>', $actions , $attributes );
								break;
						}

					}

					echo '</tr>';
				}
			}

		}
		
	}