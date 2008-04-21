<?php
	/*
		Plugin Name: Karailiev's sitemap
		Plugin URI: http://www.karailiev.net
		Description: Generates sitemap for users and for spiders.
		Version: 0.1
		Author: Valentin Karailiev
		Author URI: http://www.karailiev.net
	*/


	// Add some default options if they don't exist
	add_option('ksm_active', false);


	add_action('admin_menu', 'ksm_add_pages');




	function ksm_generate_sitemap() {
		$ksm_path = "../";

		file_put_contents($ksm_path."sitemap.xml", "hi!!!!");
	}

	// Check form submission and update options
	if ('ksm_submit' == $_POST['ksm_submit']) {
		update_option('ksm_active', $_POST['ksm_active']);
	//	$ksm_active = stripslashes(get_option('ksm_active'));
		ksm_generate_sitemap();
	}

	//Add config page
	function ksm_add_pages() {
		add_options_page("Karailiev's sitemap", "Sitemap", 8, basename(__FILE__), "ksm_admin_page");
	}

	//Config page
	function ksm_admin_page() {

		// Get options for form fields
		$ksm_active = stripslashes(get_option('ksm_active'));
?>

	<div class="wrap">
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
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" value="Save Changes" />
			</p>
		</form>
	</div>
<?php
	}
?>