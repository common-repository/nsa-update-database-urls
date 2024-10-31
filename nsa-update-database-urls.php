<?php
/*
Plugin Name: NSA Update Wordpress Database URLs
Plugin URI: 
Description: This plugin <strong>updates all urls in your website</strong> by replacing old urls with new urls. To get started: 1) Click the "Activate", 2) Go to your <a href="options-general.php?page=nsa-update-database-urls.php">Update URLs</a> page to use it.
Author: Mr.Boss
Author URI: 
Author Email: chahatsharmaa32@gmail.com
Version: 1.0
License: GPLv2 or later
Text Domain: nsa-update-database-urls
*/
add_action('admin_menu', 'nsa_add_admin_page');

function nsa_add_admin_page(){
	add_options_page("Update URLs", "Update URLs With NSA", "manage_options", basename(__FILE__), "nsa_update_management_page");
}

function nsa_update_management_page() {
	
	if ( !function_exists( 'nsa_update_urls' ) ) {
		function nsa_update_urls($options,$oldurl,$newurl){	
			global $wpdb;
			$results = array();
			$queries = array(
			'content' =>		array("UPDATE $wpdb->posts SET post_content = replace(post_content, %s, %s)",  __('Content Items (Posts, Pages, Custom Post Types, Revisions)','nsa-update-urls') ),
			'excerpts' =>		array("UPDATE $wpdb->posts SET post_excerpt = replace(post_excerpt, %s, %s)", __('Excerpts','nsa-update-urls') ),
			'attachments' =>	array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s) WHERE post_type = 'attachment'",  __('Attachments','nsa-update-urls') ),
			'links' =>			array("UPDATE $wpdb->links SET link_url = replace(link_url, %s, %s)", __('Links','nsa-update-urls') ),
			'custom' =>			array("UPDATE $wpdb->postmeta SET meta_value = replace(meta_value, %s, %s)",  __('Custom Fields','nsa-update-urls') ),
			'guids' =>			array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s)",  __('GUIDs','nsa-update-urls') )
			);
			foreach($options as $option){
				if( $option == 'custom' ){
					$n = 0;
					$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta" );
					$page_size = 10000;
					$pages = ceil( $row_count / $page_size );
					
					for( $page = 0; $page < $pages; $page++ ) {
						$current_row = 0;
						$start = $page * $page_size;
						$end = $start + $page_size;
						$pmquery = "SELECT * FROM $wpdb->postmeta WHERE meta_value <> ''";
						$items = $wpdb->get_results( $pmquery );
						foreach( $items as $item ){
						$value = $item->meta_value;
						if( trim($value) == '' )
							continue;
						
							$edited = nsa_unserialize_replace( $oldurl, $newurl, $value );
						
							if( $edited != $value ){
								$fix = $wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '".$edited."' WHERE meta_id = ".$item->meta_id );
								if( $fix )
									$n++;
							}
						}
					}
					$results[$option] = array($n, $queries[$option][1]);
				}
				else{
					$result = $wpdb->query( $wpdb->prepare( $queries[$option][0], $oldurl, $newurl) );
					$results[$option] = array($result, $queries[$option][1]);
				}
			}
			return $results;			
		}
	}
	if ( !function_exists( 'nsa_unserialize_replace' ) ) {
		function nsa_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {
			try {
				if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
					$data = nsa_unserialize_replace( $from, $to, $unserialized, true );
				}
				elseif ( is_array( $data ) ) {
					$_tmp = array( );
					foreach ( $data as $key => $value ) {
						$_tmp[ $key ] = nsa_unserialize_replace( $from, $to, $value, false );
					}
					$data = $_tmp;
					unset( $_tmp );
				}
				else {
					if ( is_string( $data ) )
						$data = str_replace( $from, $to, $data );
				}
				if ( $serialised )
					return serialize( $data );
			} catch( Exception $error ) {
			}
			return $data;
		}
	}
	if ( isset( $_POST['nsa_settings_submit'] ) && !check_admin_referer('nsa_submit','nsa_nonce')){
		if(isset($_POST['nsa_oldurl']) && isset($_POST['nsa_newurl'])){
			if(function_exists('esc_attr')){
				$nsa_oldurl = esc_attr(trim($_POST['nsa_oldurl']));
				$nsa_newurl = esc_attr(trim($_POST['nsa_newurl']));
			}else{
				$nsa_oldurl = attribute_escape(trim($_POST['nsa_oldurl']));
				$nsa_newurl = attribute_escape(trim($_POST['nsa_newurl']));
			}
		}
		echo '<div id="message" class="error fade"><p><strong>'.__('ERROR','nsa-update-urls').' - '.__('Please try again.','nsa-update-urls').'</strong></p></div>';
	}
	elseif( isset( $_POST['nsa_settings_submit'] ) && !isset( $_POST['nsa_update_links'] ) ){
		if(isset($_POST['nsa_oldurl']) && isset($_POST['nsa_newurl'])){
			if(function_exists('esc_attr')){
				$nsa_oldurl = esc_attr(trim($_POST['nsa_oldurl']));
				$nsa_newurl = esc_attr(trim($_POST['nsa_newurl']));
			}else{
				$nsa_oldurl = attribute_escape(trim($_POST['nsa_oldurl']));
				$nsa_newurl = attribute_escape(trim($_POST['nsa_newurl']));
			}
		}
		echo '<div id="message" class="error fade"><p><strong>'.__('ERROR','nsa-update-urls').' - '.__('Your URLs have not been updated.','nsa-update-urls').'</p></strong><p>'.__('Please select at least one checkbox.','nsa-update-urls').'</p></div>';
	}
	elseif( isset( $_POST['nsa_settings_submit'] ) ){
		$nsa_update_links = $_POST['nsa_update_links'];
		if(isset($_POST['nsa_oldurl']) && isset($_POST['nsa_newurl'])){
			if(function_exists('esc_attr')){
				$nsa_oldurl = esc_attr(trim($_POST['nsa_oldurl']));
				$nsa_newurl = esc_attr(trim($_POST['nsa_newurl']));
			}else{
				$nsa_oldurl = attribute_escape(trim($_POST['nsa_oldurl']));
				$nsa_newurl = attribute_escape(trim($_POST['nsa_newurl']));
			}
		}
		if(($nsa_oldurl && $nsa_oldurl != 'http://www.oldurl.com' && trim($nsa_oldurl) != '') && ($nsa_newurl && $nsa_newurl != 'http://www.newurl.com' && trim($nsa_newurl) != '')){
			$results = nsa_update_urls($nsa_update_links,$nsa_oldurl,$nsa_newurl);
			$empty = true;
			$emptystring = '<strong>'.__('Why do the results show 0 URLs updated?','nsa-update-urls').'</strong><br/>'.__('This happens if a URL is incorrect OR 
			if it is not found in the content. Check your URLs and try again.','nsa-update-urls').'<br/><br/>
			<strong>'.__('Want us to do it for you?','nsa-update-urls').'</strong>';

			$resultstring = '';
			foreach($results as $result){
				$empty = ($result[0] != 0 || $empty == false)? false : true;
				$resultstring .= '<br/><strong>'.$result[0].'</strong> '.$result[1];
			}
			
			if( $empty ):
			?>
<div id="message" class="error fade">
<table>
<tr>
	<td><p><strong>
			<?php _e('ERROR: Something may have gone wrong.','nsa-update-urls'); ?>
			</strong><br/>
			<?php _e('Your URLs have not been updated.','nsa-update-urls'); ?>
		</p>
		<?php
			else:
			?>
		<div id="message" class="updated fade">
			<table>
				<tr>
					<td><p><strong>
							<?php _e('Your URLs have been updated <b>Successfully !!</b>.','nsa-update-urls'); ?>
							</strong></p>
						<?php
			endif;
			?>
						<p><u>
							<?php _e('Results','nsa-update-urls'); ?>
							</u><?php echo $resultstring; ?></p>
						<?php echo ($empty)? '<p>'.$emptystring.'</p>' : ''; ?></td>
					<td width="60"></td>
					<td align="center"></td>
				</tr>
			</table>
		</div>
		<?php
		}
		else{
			echo '<div id="message" class="error fade"><p><strong>'.__('ERROR','nsa-update-urls').' - '.__('Your URLs have not been updated.','nsa-update-urls').'</p>
			</strong><p>'.__('Please enter values for both the old url and the new url.','nsa-update-urls').'</p></div>';
		}
	}
?>
<div class="wrap nsa-wrap">
		<h2>Update Wordpress Database URLs</h2>
		
		<form method="post" action="options-general.php?page=<?php echo basename(__FILE__); ?>">
			<?php wp_nonce_field('nsa_submit','nsa_nonce'); ?>
			<p><?php printf(__("After moving a website, %s with <a href='#'>NSA</a> now lets fix old URLs in content, excerpts, links, and custom fields.",'nsa-update-urls'),'<strong>Update URLs</strong>'); ?></p>
			<p><strong>
				<?php _e('WE RECOMMEND THAT YOU BACKUP YOUR WEBSITE AND DATABASE.','nsa-update-urls'); ?>
				</strong><br/>
				<?php _e('You may need to restore previous if incorrect URLs are entered in the fields below.','nsa-update-urls'); ?>
			</p>
			<h3 style="margin-bottom:5px;">
				<?php _e('Step'); ?>
				1:
				<?php _e('Enter your URLs in the fields below','nsa-update-urls'); ?>
			</h3>
			<table class="form-table">
				<tr valign="middle">
					<th scope="row" width="140" style="width:140px"><strong>
						<?php _e('Old URL','nsa-update-urls'); ?>
						</strong><br/>
						<span class="description">
						<?php _e('Old Site Address','nsa-update-urls'); ?>
						</span></th>
					<td><input name="nsa_oldurl" type="text" id="nsa_oldurl" value="<?php echo (isset($nsa_oldurl) && trim($nsa_oldurl) != '')? $nsa_oldurl : 'http://www.old-domain.com'; ?>" style="width:300px;font-size:20px;" onfocus="if(this.value=='http://www.old-domain.com') this.value='';" onblur="if(this.value=='') this.value='http://www.old-domain.com';" /></td>
				</tr>
				<tr valign="middle">
					<th scope="row" width="140" style="width:140px"><strong>
						<?php _e('New URL','nsa-update-urls'); ?>
						</strong><br/>
						<span class="description">
						<?php _e('New Site Address','nsa-update-urls'); ?>
						</span></th>
					<td><input name="nsa_newurl" type="text" id="nsa_newurl" value="<?php echo (isset($nsa_newurl) && trim($nsa_newurl) != '')? $nsa_newurl : 'http://www.new-domain.com'; ?>" style="width:300px;font-size:20px;" onfocus="if(this.value=='http://www.new-domain.com') this.value='';" onblur="if(this.value=='') this.value='http://www.new-domain.com';" /></td>
				</tr>
			</table>
			<br/>
			<h3 style="margin-bottom:5px;">
				<?php _e('Step'); ?>
				2:
				<?php _e('Choose which URLs should be updated','nsa-update-urls'); ?>
			</h3>
			<table class="form-table">
				<tr>
					<td><p style="line-height:20px;">
							<input name="nsa_update_links[]" type="checkbox" id="nsa_update_true" value="content" checked="checked" />
							<label for="nsa_update_true"><strong>
								<?php _e('URLs in page content','nsa-update-urls'); ?>
								</strong> (
								<?php _e('posts, pages, custom post types, revisions','nsa-update-urls'); ?>
								)</label>
							<br/>
							<input name="nsa_update_links[]" type="checkbox" id="nsa_update_true" value="excerpts" />
							<label for="nsa_update_true"><strong>
								<?php _e('URLs in excerpts','kinex-update-urls'); ?>
								</strong></label>
							<br/>
							<input name="nsa_update_links[]" type="checkbox" id="nsa_update_true" value="links" />
							<label for="nsa_update_true"><strong>
								<?php _e('URLs in links','kinex-update-urls'); ?>
								</strong></label>
							<br/>
							<input name="nsa_update_links[]" type="checkbox" id="nsa_update_true" value="attachments" />
							<label for="nsa_update_true"><strong>
								<?php _e('URLs for attachments','kinex-update-urls'); ?>
								</strong> (
								<?php _e('images, documents, general media','kinex-update-urls'); ?>
								)</label>
							<br/>
							<input name="nsa_update_links[]" type="checkbox" id="nsa_update_true" value="custom" />
							<label for="nsa_update_true"><strong>
								<?php _e('URLs in custom fields and meta boxes','kinex-update-urls'); ?>
								</strong></label>
							<br/>
							<input name="nsa_update_links[]" type="checkbox" id="nsa_update_true" value="guids" />
							<label for="nsa_update_true"><strong>
								<?php _e('Update ALL GUIDs','nsa-update-urls'); ?>
								</strong> <span class="description" style="color:#f00;">
								<?php _e('GUIDs for posts should only be changed on development sites.','nsa-update-urls'); ?>
								</span></label>
						</p></td>
				</tr>
			</table>
			<p>
				<input class="button-primary" name="nsa_settings_submit" value="<?php _e('Update URLs NOW','nsa-update-urls'); ?>" type="submit" />
			</p>
		</form>
		</div>
		<style>
.nsa-wrap h3:before {
    content: ">";
    float: left;
    background: #4696A5;
    padding: 13px;
    margin-top: -13px;
    position: relative;
    margin-left: -13px;
}

.nsa-wrap h3 {
    background: #48BFD4;
    padding: 13px;
    color: #fff;
    text-align: center;
    width: 50%;
}
.nsa-wrap input[type=text] {
    padding: 9px;
    border-radius: 3px;
}
table.form-table {
    width: 51.5%;
    border: 1px solid #ccc;
}
table.form-table th {
    padding: 17px;
}
</style>
<?php
	} // end of main function
?>