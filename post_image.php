<?php
/*
  Plugin Name: Perfomance Evaluation and Charts
  Plugin URI: http://abidkt.in/post-image
  Version: 1.0
  Author: Zainul Abideen K T
  Author URI: http://abidkt.in
  Description: Displays first image from popular search engines .Currently it supports Google.
 */

function post_image_shortcode($atts) {
    global $post;
    extract(shortcode_atts(array(
        'keywords' => '',
                    ), $atts));

    if ($keywords == '') {
        $keywords = $post->post_title;
    }
    $img = post_image_getImage($keywords);
    return $img;
}

function post_image_auto($content) {
    global $post;
    if (!get_option('post_image_post_post') && $post->post_type == 'post')
        return $content;
    if (!get_option('post_image_post_page') && $post->post_type == 'page')
        return $content;
    $keywords = $post->post_title;
    $img = post_image_getImage($keywords);
    return $img . $content;
}

function post_image_getImage($keywords) {
    global $wpdb;
    $keywords = str_replace(' ', '+', trim($keywords));
    $keywords = str_replace(',', '+', trim($keywords));
    $imgsize = get_option('post_image_size');
	$safesearch  = get_option('post_image_safe_search');
    $dbresult = $wpdb->get_row($wpdb->prepare("select imageurl from " . $wpdb->prefix . "post_image where query = '" . $keywords . "' and imagesize = '" . $imgsize . "' and safesearch = '".$safesearch."'"), ARRAY_A);
    if (!$dbresult) {
        $provider = get_option('post_image_provider');
        $url = post_image_getImageByProvider($keywords, $provider, $imgsize, $safesearch);
        $wpdb->insert($wpdb->prefix . 'post_image', array('query' => $keywords, 'imageurl' => $url, 'provider' => $provider, 'imagesize' => $imgsize, 'safesearch' => $safesearch ), array('%s', '%s', '%s', '%s', '%s'));
    } else {
        $url = $dbresult['imageurl'];
    }
    $img = "<img src='{$url}' />";
    return $img;
}

function post_image_getImageByProvider($query, $provider, $imgsize, $safesearch) {
    if ($provider == 'google') {
        $jsonurl = "http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=" . $query . "&imgsz=" . $imgsize."&safe=".$safesearch;
        $result = json_decode(file_get_contents($jsonurl), true);
        $img = $result['responseData']['results'][0]['url'];
        return $img;
    }
}

if (get_option('post_image_plugin_shortcode')) {
    add_shortcode('post-image', 'post_image_shortcode');
}
if (get_option('post_image_plugin_auto')) {
    add_filter('the_content', 'post_image_auto');
}

function post_image_option() {
    ?>
    <div class="wrap">
        <h2>Post Image Plugin Admin</h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options'); ?>
            <table class="form-table">

                <tr valign="top">
                    <th scope="row">Plugin type</th>
                    <td>
                        <p>
                            <input type="checkbox" name="post_image_plugin_auto" <?php if (get_option('post_image_plugin_auto')) echo "checked=\"checked\""; ?> > Automatic<br/>
                            Automatically adds images to every posts/pages
                        </p>
                        <p>
                            <input type="checkbox" name="post_image_plugin_shortcode" <?php if (get_option('post_image_plugin_shortcode')) echo "checked=\"checked\""; ?> > Shortcode<br/>
                            You can specify by shortcode <b>[post-image]</b> or <b>[post-image keywords="some keywords to search"]</b><br/>
                            <b>[post-image]</b> searches images based on the post/page title.<br/>
                            <b>[post-image keywords="some keywords to search"]</b> searches images based on the keyword specified. 
                        </p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Plugin applies to (for automatic posting)</th>
                    <td>
                        <p>
                            <input type="checkbox" name="post_image_post_post" <?php if (get_option('post_image_post_post')) echo "checked=\"checked\""; ?>> Posts
                        </p>
                        <p>
                            <input type="checkbox" name="post_image_post_page" <?php if (get_option('post_image_post_page')) echo "checked=\"checked\""; ?>> Pages
                        </p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Search engine</th>
                    <td>
                        <select name="post_image_provider">
                            <option value="google" <?php if (get_option('post_image_provider') == 'google') echo "selected=\"selected\""; ?>>Google</option>
                        </select>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Image size</th>
                    <td>
                        <select name="post_image_size">
                            <option value="" <?php if (get_option('post_image_size') == '') echo "selected=\"selected\""; ?>>Any</option>
                            <option value="small" <?php if (get_option('post_image_size') == 'small') echo "selected=\"selected\""; ?>>Small</option>
                            <option value="medium" <?php if (get_option('post_image_size') == 'medium') echo "selected=\"selected\""; ?>>Medium</option>
                            <option value="xxlarge" <?php if (get_option('post_image_size') == 'xxlarge') echo "selected=\"selected\""; ?>>Large</option>
                            <option value="huge" <?php if (get_option('post_image_size') == 'huge') echo "selected=\"selected\""; ?>>Huge</option>
                        </select>
                    </td>
                </tr>
				
				<tr valign="top">
                    <th scope="row">Safe search for google</th>
                    <td>
                        <select name="post_image_safe_search">
                            <option value="active" <?php if (get_option('post_image_safe_search') == 'active') echo "selected=\"selected\""; ?>>Active</option>
							<option value="moderate" <?php if (get_option('post_image_safe_search') == 'moderate') echo "selected=\"selected\""; ?>>Moderate</option>
                            <option value="off" <?php if (get_option('post_image_safe_search') == 'off') echo "selected=\"selected\""; ?>>Off</option>
                        </select>
                    </td>
                </tr>


            </table>

            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="post_image_provider, post_image_post_post,post_image_post_page, post_image_plugin_auto, post_image_plugin_shortcode,post_image_size, post_image_safe_search" />
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>



    </div>
    <?php
}

function post_image_add_admin() {
    add_options_page('Post Image', 'Post Image', 'manage_options', 'post_image', 'post_image_option');
}

function post_image_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . "post_image";
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        query text NOT NULL,
        imageurl text NOT NULL,
        provider text NOT NULL,
        imagesize text NOT NULL,
		safesearch text NOT NULL,
        UNIQUE KEY id (id)
    );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
    add_option('post_image_provider', "google");
    add_option('post_image_post_page', "on");
    add_option('post_image_post_post', "on");
    add_option('post_image_plugin_auto', "on");
    add_option('post_image_plugin_shortcode', "on");
    add_option('post_image_size', "");
	add_option('post_image_safe_search','on');
}

add_action('admin_menu', 'post_image_add_admin');
register_activation_hook(__FILE__, "post_image_install");
?>
