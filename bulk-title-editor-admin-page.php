<?php
	/*
	 *
	 *
	 *
	 *
	 */

	if( !class_exists( 'SEO_Extended_Bulk_Title_List_Table' ) ) {
		require_once( 'class-seo-extended-bulk-title-list-table.php' );
	}

	$seo_extended_list_table = new SEO_Extended_Bulk_Title_List_Table();

	if ( ! empty($_REQUEST['_wp_http_referer']) ) {
		wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI']) ) );
		exit;
	}

	$seo_extended_list_table->prepare_items();
?>

<div class="wrap seo_extended_table_page">
	<div id="icon-edit-pages" class="icon32 icon32-posts-page"></div>
	<h2>SEO Extended &ndash; Bulk Title Editor</h2>

	<?php $seo_extended_list_table->views(); ?>

	<?php $seo_extended_list_table->display(); ?>

</div>