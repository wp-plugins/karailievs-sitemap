<?php
	/*
		Plugin Name: Karailiev's sitemap
		Plugin URI: http://www.karailiev.net/karailievs-sitemap/
		Description: Generates sitemap for spiders.
		Version: 0.51
		Author: Valentin Karailiev
		Author URI: http://www.karailiev.net/
	*/
	$ksm_sitemap_version = "0.5.1";

	// Add some default options if they don't exist
	add_option('ksm_active', true);
	add_option('ksm_comments', false);
	add_option('ksm_attachments', true);
	add_option('ksm_categories', true);
	add_option('ksm_tags', true);
	add_option('ksm_path', "./");
	add_option('ksm_last_ping', 0);

	// Get options for form fields
	$ksm_active = get_option('ksm_active');
	$ksm_comments = get_option('ksm_comments');
	$ksm_attachments = get_option('ksm_attachments');
	$ksm_categories = get_option('ksm_categories');
	$ksm_tags = get_option('ksm_tags');
	$ksm_path = get_option('ksm_path');

	// Add hooks
	add_action('admin_menu', 'ksm_add_pages');
	if ($ksm_active) {
		add_action('edit_post', 'ksm_generate_sitemap');
		add_action('delete_post', 'ksm_generate_sitemap');
		add_action('private_to_published', 'ksm_generate_sitemap');
		add_action('publish_page', 'ksm_generate_sitemap');
		add_action('publish_phone', 'ksm_generate_sitemap');
		add_action('publish_post', 'ksm_generate_sitemap');
		add_action('save_post', 'ksm_generate_sitemap');
		add_action('xmlrpc_publish_post', 'ksm_generate_sitemap');

		if ($ksm_comments) {
			add_action('comment_post', 'ksm_generate_sitemap');
			add_action('edit_comment', 'ksm_generate_sitemap');
			add_action('delete_comment', 'ksm_generate_sitemap');
			add_action('pingback_post', 'ksm_generate_sitemap');
			add_action('trackback_post', 'ksm_generate_sitemap');
			add_action('wp_set_comment_status', 'ksm_generate_sitemap');
		}

		if ($ksm_attachments) {
			add_action('add_attachment', 'ksm_generate_sitemap');
			add_action('edit_attachment', 'ksm_generate_sitemap');
			add_action('delete_attachment', 'ksm_generate_sitemap');
		}
	}

	//Add config page
	function ksm_add_pages() {
		add_options_page("Karailiev's sitemap", "Sitemap", 8, basename(__FILE__), "ksm_admin_page");
	}


	function ksm_permissions() {
		$ksm_path = ABSPATH . get_option('ksm_path');
		$ksm_file_path = $ksm_path . "sitemap.xml";

		if (is_file($ksm_file_path)){
			if (is_writable($ksm_file_path)) $ksm_permission = 1;
			else $ksm_permission = 3;
		}
		elseif (is_writable($ksm_path)) {
			$ksm_permission = 2;
			$fp = fopen($ksm_file_path, 'w');
			fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" />");
			fclose($fp);
			if (is_file($ksm_file_path)) $ksm_permission = 1;
		}
		else $ksm_permission = 4;

		return $ksm_permission;
	}


	function ksm_generate_sitemap() {
		global $ksm_sitemap_version, $table_prefix;

		$ksm_permission = ksm_permissions();
		if ($ksm_permission > 1) return;

		mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_query("SET NAMES '".DB_CHARSET."'");
		mysql_select_db(DB_NAME);

		$t = $table_prefix;
		$ksm_active = get_option('ksm_active');
		$ksm_comments = get_option('ksm_comments');
		$ksm_categories = get_option('ksm_categories');
		$ksm_tags = get_option('ksm_tags');

		$urls = array();

		$home = get_option('home') . "/";
		$result = mysql_query("
			SELECT `post_modified`
			FROM `".$t."posts`
			WHERE
				(`post_type`='page' OR `post_type`='post')
				AND `post_status`='publish'
			ORDER BY `post_modified` DESC
			LIMIT 1
		");
		$data = mysql_fetch_assoc($result);
		$homeLastUpdate = $data['post_modified'];
		$homeLastUpdate = substr($homeLastUpdate, 0, 10);

		$result = mysql_query("
			SELECT `".$t."posts`.`ID`, `".$t."posts`.`post_modified`, `".$t."posts`.`post_name`
			FROM `".$t."posts`
			WHERE
				(`".$t."posts`.`post_type`='page' OR `".$t."posts`.`post_type`='post')
				AND `".$t."posts`.`post_status`='publish'
			ORDER BY `".$t."posts`.`post_modified` DESC
		");


		while ($data = mysql_fetch_assoc($result)) {
			$date = substr($data['post_modified'], 0, 10);

			if ($ksm_comments) {
				$cresult = mysql_query("
					SELECT `".$t."comments`.`comment_date`
					FROM `".$t."comments`
					WHERE
						`".$t."comments`.`comment_post_ID`='".$data['ID']."'
						AND `".$t."comments`.`comment_approved`='1'
					ORDER BY `".$t."comments`.`comment_date` DESC
					LIMIT 1
				");

				if (mysql_num_rows($cresult) > 0) {
					$cdata = mysql_fetch_assoc($cresult);
					$commentDate = getdate(strtotime($cdata['comment_date']));
					$postDate = getdate(strtotime($data['post_modified']));
					if ($commentDate[0] > $postDate[0]) $date = substr($cdata['comment_date'], 0, 10);
				}
			}

			$urls[$data['ID']] = array(
				"url"		=> get_permalink($data['ID']),
				"lastmod"	=> $date,
				"changes"	=> "weekly",
				"priority"	=> "0.3"
			);
		}

		if ($ksm_categories || $ksm_tags) {
			$what_kind = "";
			if ($ksm_categories) $what_kind = "`".$t."term_taxonomy`.`taxonomy`='category'";
			if ($ksm_tags) {
				if ($what_kind == "") $what_kind = "`".$t."term_taxonomy`.`taxonomy`='post_tag'";
				else $what_kind = "(" . $what_kind . " OR `".$t."term_taxonomy`.`taxonomy`='post_tag')";
			}

			$result = mysql_query("
				SELECT `".$t."term_taxonomy`.`term_id`, `".$t."term_taxonomy`.`taxonomy`
				FROM `".$t."term_taxonomy`
				WHERE
					`".$t."term_taxonomy`.`count` > 0
					AND ".$what_kind."
			");
			while ($data = mysql_fetch_assoc($result)) {
				if ($data['taxonomy'] == "category") $u = get_category_link($data['term_id']);
				elseif ($data['taxonomy'] == "post_tag") $u = get_tag_link($data['term_id']);
				else continue;
				$urls['testm_'.$data['term_id']] = array(
					"url"		=> $u,
					"changes"	=> "weekly",
					"priority"	=> "0.1"
				);
			}
		}

		$out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$out .= "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
	<!-- Generated by Karailiev's sitemap ".$ksm_sitemap_version." plugin -->
	<!-- http://www.karailiev.net/karailievs-sitemap/ -->
	<!-- Crated ".date("F d, Y, H:i")."-->";
		if (!$ksm_active) $out .= "
	<!-- Plugin is off. Showing only homepage. -->";

		$out .= "
	<url>
		<loc>".$home."</loc>
		<lastmod>".$homeLastUpdate."</lastmod>
		<changefreq>weekly</changefreq>
		<priority>1.0</priority>
	</url>";

		if ($ksm_active) {
			foreach ($urls as $u) {
				$out .= "
	<url>
		<loc>".$u['url']."</loc>
		".(isset($u['lastmod'])?"<lastmod>".$u['lastmod']."</lastmod>":'')."
		<changefreq>".$u['changes']."</changefreq>
		<priority>".$u['priority']."</priority>
	</url>";
			}
		}

		$out .= "\n</urlset>";
		$ksm_path = get_option('ksm_path');
		$fp = fopen(ABSPATH . $ksm_path . "sitemap.xml", 'w');
		fwrite($fp, $out);
		fclose($fp);

		$ksm_last_ping = get_option('ksm_last_ping');
		if ((time() - $ksm_last_ping) > 60 * 60) {
			//get_headers("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $ksm_path . "sitemap.xml"));
			$fp = fopen("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $ksm_path . "sitemap.xml"), 80);
			fclose($fp);
			update_option('ksm_last_ping', time());
		}
	}



	//Config page
	function ksm_admin_page() {
		$msg = "";

		// Check form submission and update options
		if ('ksm_submit' == $_POST['ksm_submit']) {
			update_option('ksm_active', $_POST['ksm_active']);
			update_option('ksm_comments', $_POST['ksm_comments']);
			update_option('ksm_attachments', $_POST['ksm_attachments']);
			update_option('ksm_categories', $_POST['ksm_categories']);
			update_option('ksm_tags', $_POST['ksm_tags']);
			$newPath = trim($_POST['ksm_path']);
			if ($newPath == "" || $newPath == "/") $newPath = "./";
			elseif ($newPath[strlen($newPath)-1] != "/") $newPath .= "/";
			update_option('ksm_path', $newPath);
			ksm_generate_sitemap();
		}
		$ksm_active = get_option('ksm_active');
		$ksm_comments = get_option('ksm_comments');
		$ksm_attachments = get_option('ksm_attachments');
		$ksm_categories = get_option('ksm_categories');
		$ksm_tags = get_option('ksm_tags');
		$ksm_path = get_option('ksm_path');

		$ksm_permission = ksm_permissions();
		if ($ksm_permission == 3) $msg = "Error: sitemap.xml file exists but is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
		elseif ($ksm_permission == 4) $msg = "Error: sitemap.xml file does not exist and the plugin can not create it. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
		elseif ($ksm_permission == 2) $msg = "Error: sitemap.xml file does not exist and the plugin can not create it. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
?>
	<div class="wrap">
<?php	if ($msg) {	?>
	<div id="message" class="error"><p><strong><?php echo $msg; ?></strong></p></div>
<?php	}	?>
		<form name="form1" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>&amp;updated=true">
			<input type="hidden" name="ksm_submit" value="ksm_submit" />
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Sitemap settings</th>
					<td>
						<label for="ksm_active">
							<input name="ksm_active" type="checkbox" id="ksm_active" value="1" <?php echo $ksm_active?'checked="checked"':''; ?> />
							Acivate sitemap plugin.
						</label><br />
						<br />
						<label for="ksm_categories">
							<input name="ksm_categories" type="checkbox" id="ksm_categories" value="1" <?php echo $ksm_categories?'checked="checked"':''; ?> />
							Show categories.
						</label><br />
						<label for="ksm_tags">
							<input name="ksm_tags" type="checkbox" id="ksm_tags" value="1" <?php echo $ksm_tags?'checked="checked"':''; ?> />
							Show tags.
						</label><br />
						<label for="ksm_comments">
							<input name="ksm_comments" type="checkbox" id="ksm_comments" value="1" <?php echo $ksm_comments?'checked="checked"':''; ?> />
							Use comments' dates.
						</label><br />
						<label for="ksm_attachments">
							<input name="ksm_attachments" type="checkbox" id="ksm_attachments" value="1" <?php echo $ksm_attachments?'checked="checked"':''; ?> />
							Rebuild on attachments modifications.
						</label><br />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Advanced settings</th>
					<td>
						Sitemap path (relativly to blog's home): <input name="ksm_path" type="text" id="ksm_path" value="<?php echo $ksm_path?>" />
						<br />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" value="Save &amp; Rebuild" />
			</p>
		</form>
	</div>
<?php
	}
?>