<?php
	/*
		Plugin Name: Karailiev's sitemap
		Plugin URI: http://www.karailiev.net/karailievs-sitemap/
		Description: Generates sitemap for spiders.
		Version: 0.7
		Author: Valentin Karailiev
		Author URI: http://www.karailiev.net/
	*/
	$ksm_sitemap_version = "0.7";

	// Add some default options if they don't exist
	add_option('ksm_active', true);
	add_option('ksm_news_active', false);
	add_option('ksm_comments', false);
	add_option('ksm_attachments', true);
	add_option('ksm_categories', true);
	add_option('ksm_tags', true);
	add_option('ksm_path', "./");
	add_option('ksm_last_ping', 0);
	add_option('ksm_post_priority', 0.3);
	add_option('ksm_post_frequency', 'weekly');
	add_option('ksm_page_priority', 0.5);
	add_option('ksm_page_frequency', 'monthly');
	add_option('ksm_tag_priority', 0.1);
	add_option('ksm_tag_frequency', 'weekly');
	add_option('ksm_category_priority', 0.1);
	add_option('ksm_category_frequency', 'weekly');
	
	
	// Get options for form fields
	$ksm_active = get_option('ksm_active');
	$ksm_news_active = get_option('ksm_news_active');
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
		$ksm_active = get_option('ksm_active');
		$ksm_news_active = get_option('ksm_news_active');
		
		$ksm_path = ABSPATH . get_option('ksm_path');
		$ksm_file_path = $ksm_path . "sitemap.xml";
		$ksm_news_file_path = $ksm_path . "sitemap-news.xml";
		
		if ($ksm_active && is_file($ksm_file_path) && is_writable($ksm_file_path)) $ksm_permission = 0;
		elseif ($ksm_active && !is_file($ksm_file_path) && is_writable($ksm_path)) {
			$fp = fopen($ksm_file_path, 'w');
			fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" />");
			fclose($fp);
			if (is_file($ksm_file_path) && is_writable($ksm_file_path)) $ksm_permission = 0;
			else $ksm_permission = 1;
		}
		elseif ($ksm_active) $ksm_permission = 1;
		else $ksm_permission = 0;
		
		
		if ($ksm_news_active && is_file($ksm_news_file_path) && is_writable($ksm_news_file_path)) $ksm_permission += 0;
		elseif ($ksm_news_active && !is_file($ksm_news_file_path) && is_writable($ksm_path)) {
			$fp = fopen($ksm_news_file_path, 'w');
			fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\" />");
			fclose($fp);
			if (is_file($ksm_news_file_path) && is_writable($ksm_news_file_path)) $ksm_permission += 0;
			else $ksm_permission += 2;
		}
		elseif ($ksm_news_active) $ksm_permission += 2;
		else $ksm_permission += 0;

		return $ksm_permission;
	}


	function ksm_generate_sitemap() {
		global $ksm_sitemap_version, $table_prefix;
		
		$t = $table_prefix;
		$ksm_active = get_option('ksm_active');
		$ksm_news_active = get_option('ksm_news_active');
		$ksm_comments = get_option('ksm_comments');
		$ksm_categories = get_option('ksm_categories');
		$ksm_tags = get_option('ksm_tags');
		$ksm_post_priority = get_option('ksm_post_priority');
		$ksm_post_frequency = get_option('ksm_post_frequency');
		$ksm_page_priority = get_option('ksm_page_priority');
		$ksm_page_frequency = get_option('ksm_page_frequency');
		$ksm_tag_priority = get_option('ksm_tag_priority');
		$ksm_tag_frequency = get_option('ksm_tag_frequency');
		$ksm_category_priority = get_option('ksm_category_priority');
		$ksm_category_frequency = get_option('ksm_category_frequency');
		$ksm_path = get_option('ksm_path');

		$ksm_permission = ksm_permissions();
		if ($ksm_permission > 2 || (!$ksm_active && !$ksm_news_active)) return;

		mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_query("SET NAMES '".DB_CHARSET."'");
		mysql_select_db(DB_NAME);

		$home = get_option('home') . "/";
		
		$out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$out .= "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
	<!-- Generated by Karailiev's sitemap ".$ksm_sitemap_version." plugin -->
	<!-- http://www.karailiev.net/karailievs-sitemap/ -->
	<!-- Created ".date("F d, Y, H:i")."-->
	<url>
		<loc>".$home."</loc>
		<lastmod>".gmdate ("Y-m-d\TH:i:s\Z")."</lastmod>
		<changefreq>weekly</changefreq>
		<priority>1</priority>
	</url>";

		
		$out_news = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$out_news .= "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">
	<!-- Generated by Karailiev's sitemap ".$ksm_sitemap_version." plugin -->
	<!-- http://www.karailiev.net/karailievs-sitemap/ -->
	<!-- Created ".date("F d, Y, H:i")."-->";
		
		
		//$homeLastUpdate = str_replace(" ", "T", $data['post_date_gmt'])."Z";
		
		
		$result = mysql_query("
			SELECT 
				`".$t."posts`.`ID`, `".$t."posts`.`post_date_gmt`, `".$t."posts`.`post_date`, `".$t."posts`.`post_modified_gmt`, `".$t."posts`.`post_name`, `".$t."posts`.`post_type`,
				MAX(`".$t."comments`.`comment_date_gmt`) AS `comment_date_gmt`, MAX(`".$t."comments`.`comment_date`) AS `comment_date`
			FROM `".$t."posts`
			LEFT JOIN `".$t."comments` ON `".$t."comments`.`comment_post_ID` = `".$t."posts`.`ID`
			WHERE
				`".$t."posts`.`post_status`='publish'
				AND (
					`".$t."posts`.`post_type`='page'
					OR `".$t."posts`.`post_type`='post'
				)
				AND (
					`".$t."comments`.`comment_approved`='1'
					OR `".$t."comments`.`comment_approved` IS NULL
				)
			GROUP BY `".$t."posts`.`ID`
			ORDER BY `".$t."posts`.`post_modified_gmt` DESC
		");
		
		
		$now = time();
		$treeDays = 3*24*60*60;
		while ($data = mysql_fetch_assoc($result)) {
			if ($ksm_news_active && $ksm_permission != 2) {
				$postDate = strtotime($data['post_date']);
				if ($now - $postDate < $treeDays) {
					$out_news .= "
	<url>
		<loc>".get_permalink($data['ID'])."</loc>
		<news:news>
			<news:publication_date>".str_replace(" ", "T", $data['post_date_gmt'])."Z"."</news:publication_date>
		</news:news>
	</url>";
				}
			}
			
			if ($ksm_active && $ksm_permission != 1) {
				$date = str_replace(" ", "T", $data['post_date_gmt'])."Z";
				if ($ksm_comments && $data['comment_date_gmt']) {
					$postDate = strtotime($data['post_date_gmt']);
					$commentDate = strtotime($data['comment_date_gmt']);
					if ($commentDate > $postDate) $date = str_replace(" ", "T", $data['comment_date_gmt'])."Z";
				}
				$out .= "
	<url>
		<loc>".get_permalink($data['ID'])."</loc>
		<lastmod>".$date."</lastmod>
		<changefreq>".($data['post_type']=="post"?$ksm_post_frequency:$ksm_page_frequency)."</changefreq>
		<priority>".($data['post_type']=="post"?$ksm_post_priority:$ksm_page_priority)."</priority>
	</url>";
				
			}
		}
		
		
		if ($ksm_active && $ksm_permission != 1 && ($ksm_categories || $ksm_tags)) {
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
				$out .= "
	<url>
		<loc>".($data['taxonomy']=="post_tag"?get_tag_link($data['term_id']):get_category_link($data['term_id']))."</loc>
		<changefreq>".($data['taxonomy']=="post_tag"?$ksm_tag_frequency:$ksm_category_frequency)."</changefreq>
		<priority>".($data['taxonomy']=="post_tag"?$ksm_tag_priority:$ksm_category_priority)."</priority>
	</url>";
			}
		}


		$out_news .= "\n</urlset>";
		$out .= "\n</urlset>";
		
		
		if ($ksm_active && $ksm_permission != 1) {
			$fp = fopen(ABSPATH . $ksm_path . "sitemap.xml", 'w');
			fwrite($fp, $out);
			fclose($fp);
		}
		
		if ($ksm_news_active && $ksm_permission != 2) {
			$fp = fopen(ABSPATH . $ksm_path . "sitemap-news.xml", 'w');
			fwrite($fp, $out_news);
			fclose($fp);
		}
		

		$ksm_last_ping = get_option('ksm_last_ping');
		if ((time() - $ksm_last_ping) > 60 * 60) {
			//get_headers("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $ksm_path . "sitemap.xml"));	//PHP5+
			$fp = @fopen("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $ksm_path . "sitemap.xml"), 80);
			@fclose($fp);
			update_option('ksm_last_ping', time());
		}
	}



	//Config page
	function ksm_admin_page() {
		$msg = "";

		// Check form submission and update options
		if ('ksm_submit' == $_POST['ksm_submit']) {
			update_option('ksm_active', $_POST['ksm_active']);
			update_option('ksm_news_active', $_POST['ksm_news_active']);
			update_option('ksm_comments', $_POST['ksm_comments']);
			update_option('ksm_attachments', $_POST['ksm_attachments']);
			update_option('ksm_categories', $_POST['ksm_categories']);
			update_option('ksm_tags', $_POST['ksm_tags']);
			
			$newPath = trim($_POST['ksm_path']);
			if ($newPath == "" || $newPath == "/") $newPath = "./";
			elseif ($newPath[strlen($newPath)-1] != "/") $newPath .= "/";
			update_option('ksm_path', $newPath);
			
			if ($_POST['ksm_post_priority']>=0.1 && $_POST['ksm_post_priority']<=0.9) update_option('ksm_post_priority', $_POST['ksm_post_priority']);
			else update_option('ksm_post_priority', 0.3);
			
			if ($_POST['ksm_page_priority']>=0.1 && $_POST['ksm_page_priority']<=0.9) update_option('ksm_page_priority', $_POST['ksm_page_priority']);
			else update_option('ksm_page_priority', 0.5);
			
			if ($_POST['ksm_tag_priority']>=0.1 && $_POST['ksm_tag_priority']<=0.9) update_option('ksm_tag_priority', $_POST['ksm_tag_priority']);
			else update_option('ksm_tag_priority', 0.1);
			
			if ($_POST['ksm_category_priority']>=0.1 && $_POST['ksm_category_priority']<=0.9) update_option('ksm_category_priority', $_POST['ksm_category_priority']);
			else update_option('ksm_category_priority', 0.1);
			
			if ($_POST['ksm_post_frequency']=="hourly" || $_POST['ksm_post_frequency']=="daily" || $_POST['ksm_post_frequency']=="weekly" || $_POST['ksm_post_frequency']=="monthly" || $_POST['ksm_post_frequency']=="yearly") update_option('ksm_post_frequency', $_POST['ksm_post_frequency']);
			else update_option('ksm_post_frequency', "weekly");
			
			if ($_POST['ksm_page_frequency']=="hourly" || $_POST['ksm_page_frequency']=="daily" || $_POST['ksm_page_frequency']=="weekly" || $_POST['ksm_page_frequency']=="monthly" || $_POST['ksm_page_frequency']=="yearly") update_option('ksm_page_frequency', $_POST['ksm_page_frequency']);
			else update_option('ksm_page_frequency', "monthly");
			
			if ($_POST['ksm_tag_frequency']=="hourly" || $_POST['ksm_tag_frequency']=="daily" || $_POST['ksm_tag_frequency']=="weekly" || $_POST['ksm_tag_frequency']=="monthly" || $_POST['ksm_tag_frequency']=="yearly") update_option('ksm_tag_frequency', $_POST['ksm_tag_frequency']);
			else update_option('ksm_tag_frequency', "weekly");
			
			if ($_POST['ksm_category_frequency']=="hourly" || $_POST['ksm_category_frequency']=="daily" || $_POST['ksm_category_frequency']=="weekly" || $_POST['ksm_category_frequency']=="monthly" || $_POST['ksm_category_frequency']=="yearly") update_option('ksm_category_frequency', $_POST['ksm_category_frequency']);
			else update_option('ksm_category_frequency', "weekly");
			
			ksm_generate_sitemap();
		}
		$ksm_active = get_option('ksm_active');
		$ksm_news_active = get_option('ksm_news_active');
		$ksm_comments = get_option('ksm_comments');
		$ksm_attachments = get_option('ksm_attachments');
		$ksm_categories = get_option('ksm_categories');
		$ksm_tags = get_option('ksm_tags');
		$ksm_path = get_option('ksm_path');
		$ksm_post_priority = get_option('ksm_post_priority');
		$ksm_post_frequency = get_option('ksm_post_frequency');
		$ksm_page_priority = get_option('ksm_page_priority');
		$ksm_page_frequency = get_option('ksm_page_frequency');
		$ksm_tag_priority = get_option('ksm_tag_priority');
		$ksm_tag_frequency = get_option('ksm_tag_frequency');
		$ksm_category_priority = get_option('ksm_category_priority');
		$ksm_category_frequency = get_option('ksm_category_frequency');

		$ksm_permission = ksm_permissions();
		if ($ksm_permission == 1) $msg = "Error: there is a problem with <em>sitemap.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
		elseif ($ksm_permission == 2) $msg = "Error: there is a problem with <em>sitemap-news.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
		elseif ($ksm_permission == 3) $msg = "Error: there is a problem with <em>sitemap.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.<br/>Error: there is a problem with <em>sitemap-news.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
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
							Create general sitemap.
						</label><br />
						<label for="ksm_news_active">
							<input name="ksm_news_active" type="checkbox" id="ksm_news_active" value="1" <?php echo $ksm_news_active?'checked="checked"':''; ?> />
							Create news sitemap.
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
							Update on comment.
						</label><br />
						<label for="ksm_attachments">
							<input name="ksm_attachments" type="checkbox" id="ksm_attachments" value="1" <?php echo $ksm_attachments?'checked="checked"':''; ?> />
							Rebuild on attachments modifications.
						</label><br />
						
						Default post priority: 
						<select name="ksm_post_priority">
							<option <?php echo $ksm_post_priority==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_post_priority==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_post_priority==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_post_priority==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_post_priority==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_post_priority==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_post_priority==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_post_priority==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_post_priority==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default post change frequency: 
						<select name="ksm_post_frequency">
							<option <?php echo $ksm_post_frequency=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_post_frequency=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_post_frequency=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_post_frequency=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_post_frequency=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
						
						Default page priority: 
						<select name="ksm_page_priority">
							<option <?php echo $ksm_page_priority==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_page_priority==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_page_priority==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_page_priority==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_page_priority==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_page_priority==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_page_priority==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_page_priority==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_page_priority==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default page change frequency: 
						<select name="ksm_page_frequency">
							<option <?php echo $ksm_page_frequency=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_page_frequency=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_page_frequency=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_page_frequency=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_page_frequency=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
						
						Default tag priority: 
						<select name="ksm_tag_priority">
							<option <?php echo $ksm_tag_priority==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_tag_priority==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_tag_priority==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_tag_priority==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_tag_priority==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_tag_priority==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_tag_priority==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_tag_priority==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_tag_priority==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default tag change frequency: 
						<select name="ksm_tag_frequency">
							<option <?php echo $ksm_tag_frequency=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_tag_frequency=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_tag_frequency=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_tag_frequency=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_tag_frequency=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
						
						Default category priority: 
						<select name="ksm_category_priority">
							<option <?php echo $ksm_category_priority==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_category_priority==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_category_priority==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_category_priority==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_category_priority==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_category_priority==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_category_priority==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_category_priority==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_category_priority==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default category change frequency: 
						<select name="ksm_category_frequency">
							<option <?php echo $ksm_category_frequency=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_category_frequency=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_category_frequency=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_category_frequency=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_category_frequency=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />

						
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Advanced settings</th>
					<td>
						Sitemap path (relatively to blog's home): <input name="ksm_path" type="text" id="ksm_path" value="<?php echo $ksm_path?>" />
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
