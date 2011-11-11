<?php

/*
  Plugin Name: WP-TheatreData-To-Post
  Plugin URI: http://sabirul-mostofa.blogspot.com
  Description: Import and show contents from http://lastminutetheatretickets.eolts.co.uk/xmlencore.php
  Version: 1.0
  Author: Sabirul Mostofa
  Author URI: http://sabirul-mostofa.blogspot.com
 */


$wpTheatreData = new wpTheatreData();

class wpTheatreData {

    public $xml_url = 'http://lastminutetheatretickets.eolts.co.uk/xmlencore.php';
    public $info = array('SHOWNAME', 'SHOWSHORT', 'BOOKINGUNTIL', 'RUNTIME', 'MATINEES', 'VENUE_NAME', 'EVENINGS', 'FACEVALUE', 'OFFERPRICE', 'OFFERTEXT', 'OFFERVALID', 'LINK_URL');
    public $info_array = array();

    function __construct() {
        add_action('wp_print_styles', array($this, 'front_css'));
        //add_action('wpTdi_cron',array($this,'start_cron'));            
        //register_activation_hook(__FILE__, array($this, 'init_cron'));
        register_deactivation_hook(__FILE__, array($this, 'disable_cron'));
        add_action('wp_dashboard_setup', array($this, 'add_widget'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_import-theatre-posts', array($this, 'ajax_func'));
        add_action('plugins_loaded', array($this, 'redirect_to_old'));
        add_action('save_post', array($this, 'check_and_edit'));
        add_filter('pre_get_posts', array($this, 'fb_exclude_filter'));
    }
    
    function generate_xml(){		
        $args = array('numberposts' => -1, 'category' => 1);
        $myposts = get_posts($args);   
        $count = 0;       
        $casts = array();
        foreach ($myposts as $single):
            $title = $single->post_title;
            if (preg_match('/interview\s+with\s+(.*)\s+([^:]*)(\s|:|$)/iU', $title, $ar)) {
                $casts[$single->ID] = $ar[2];
            }

        endforeach;

        natcasesort($casts);
// making the post content
header("Content-Type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<rss version=\"2.0\">\n";
echo "<channel>";
$extra=<<<FO
      <title>Meet the cast posts</title>
      <link>http://www.lastminutetheatretickets.com/blog/</link>
      <description>Meet the casts posts in alphabetical order</description>
FO;
echo $extra;
       foreach ($casts as $id => $value):
            //if($find && $id==$find  )continue;
            $post = get_post($id);
            if(!$post)continue;
            $title = $post->post_title;
            preg_match('/interview\s+with\s+(.*)\s+([^:]*)(\s|:|$)/iU', $title, $ar);
            $name = $ar[2] . ', ' . $ar[1];
            $perma = get_permalink($id);
            echo "<item>\n";
            
            echo "<title>$name</title>\n";
            echo "<link>$perma</link>\n";
           
            echo "<description><![CDATA[{$post->post_content}]]></description>\n";
            echo "</item>\n";
        endforeach;
        echo "</channel>";
        echo "</rss>";
        exit;
		
		}

    function fb_exclude_filter($query) {
        if (!$query->is_admin && $query->is_feed) {
            $find = get_option('theatre_post_all_casts');
            if($find)
            $query->set('post__not_in', array($find)); // id of page or post
        }
        return $query;
    }

    function check_and_edit($post_id) {
        if (!(in_category(546, $post_id)))
            return;
        $find = get_option('theatre_post_all_casts');
        if ($find && $post_id == $find)
            return;
        $args = array('numberposts' => -1, 'category' => 546);
        $myposts = get_posts($args);
        $count = 0;
        $post_content = '<table id="theatre-all-casts" class="box-rotate">';
        $casts = array();
        foreach ($myposts as $single):
            $title = $single->post_title;

            if (preg_match('/interview\s+with\s+(.*)\s+([^:]*)(\s|:|$)/iU', $title, $ar)) {
                $casts[$single->ID] = $ar[2];
            }

        endforeach;

        natcasesort($casts);
// making the post content
        foreach ($casts as $id => $value):
            //if($find && $id==$find  )continue;
            $post = get_post($id);
            $title = $post->post_title;
            preg_match('/interview\s+with\s+(.*)\s+([^:]*)(\s|:|$)/iU', $title, $ar);
            $name = $ar[2] . ', ' . $ar[1];
            $text = "<a href=\"" . get_permalink($id) . '">' . $name . '</a>';
            if ($count % 5 == 0)
                $post_content .= ($count == 0) ? "<tr><td>$text</td>" : "</tr><tr><td>$text</td>";
            else
                $post_content .="<td>$text</td>";

            $count++;

        endforeach;

        $post_content .=(preg_match('/<\/tr>$/', $post_content)) ? '</table>' : '</tr></table>';


        if (!$find || !get_post($find)) {
            $new_post = array();
            $new_post['post_title'] = 'Cast Interviews in Alphabetical Order';
            $new_post['post_content'] = $post_content;
            $new_post['post_status'] = 'publish';
            $new_post['post_category'] = array(546);
            $new_post_id = wp_insert_post($new_post);
            update_option('theatre_post_all_casts', $new_post_id);
        } else {
            $to_update = get_post($find);
            $post_date = date('Y-m-d H:i:s', time() + 2);
            $new_post = array( );
            $new_post['ID'] = $find;
            $new_post['post_content'] = $post_content;
            $new_post['post_status'] = $to_update -> post_status;
            $new_post['post_category'] = array(546);
            $new_post['post_date'] = $post_date;
            $new_post['post_date_gmt'] = $post_date;
            wp_update_post($new_post);
        }
    }

    function redirect_to_old() {
		if(isset($_REQUEST['get-alpha-list']))$this->generate_xml();
        if (stripos($_SERVER['REQUEST_URI'], 'london-theatre-special-offers-2') !== false) {
            if (get_post(get_option('theatrepost'))) {
                wp_redirect(get_permalink(get_option('theatrepost')));
                exit;
            }
        }
    }

    function add_widget() {
        wp_add_dashboard_widget('theatredata_dashboard_widget', 'Import offers as Post', array($this, 'dashboard_widget_function'));
    }

    function dashboard_widget_function() {
        echo '<button class="button-primary" id="import-theatre-post"> Import Offers</button>';
    }

    function admin_scripts() {
        wp_enqueue_script('jquery');

        wp_enqueue_script('theatre_front_script', plugins_url('/', __FILE__) . 'js/script_front.js');
        wp_localize_script('theatre_front_script', 'wpTheatreSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'pluginurl' => plugins_url('/', __FILE__),
            'site_url' => site_url()
        ));
    }

    function front_css() {
        if (!(is_admin())):
            wp_enqueue_style('theatre_front_css', plugins_url('/', __FILE__) . 'css/style_front.css');
        endif;
    }

    function init_cron() {
        if (!wp_get_schedule('wpTdi_cron'))
            wp_schedule_event(time(), 'daily', 'wpTdi_cron');
    }

    function disable_cron() {
        wp_clear_scheduled_hook('wpTdi_cron');
    }

    function start_cron() {
        global $wpdb;
        $this->extract_process_data();
    }

    function end_cron_func() {
        
    }

    function extract_process_data() {
        $cat = false;
        $count = 0;
        if ($cat = get_term_by('slug', 'london-theatre', 'category'))
            $cat = $cat->cat_id;
        $check = false;
        $all_info = array();
        $dom = $this->return_dom($this->xml_url);
        $doc = $dom->getElementsByTagName('OFFERS')->item(0);
        // building show id array
        $show_ids = get_option('wptheatresdata');
        $wp_post_theatre = get_option('theatrepost');
        if (!is_array($show_ids))
            $show_ids = array();

        $added_shows = array();


        $post_extra = '<div class="containter-all-theatres">';
        $post_extra .='<div class="theatre-info-container"><a href="http://www.lastminutetheatretickets.com/blog/index.php/9553/london-theatre-special-offers-2/specialoffers1/" rel="attachment wp-att-9576"><img class="alignleft size-full wp-image-9576" title="London Theatre Special Offers" src="http://www.lastminutetheatretickets.com/blog/wp-content/uploads/2011/10/specialoffers1.jpg" alt="London Theatre Special Offers" width="100" height="100" /></a>
Book London theatre tickets from our list of SPECIAL OFFERS!
Tickets available from £10.99 for London West End shows.
Save more than 50% with some ticket offers.<div class="clear:both;"></div></div>';
        foreach ($doc->getElementsByTagName('OFFER') as $offer):
            $show = $offer->getElementsByTagName('SHOW')->item(0);
            if (!in_array($offer->getAttribute('OID'), $added_shows)) {
                $post_extra .= $this->pre_make_post($this->get_info($offer));
                $added_shows[] = $offer->getAttribute('OID');
                $count++;
            }


        endforeach;
        $last = '</div><div style="clear:both"></div>';
        $post_extra .= $last;

        if ($count == 0)
            return $count;
        if ($wp_post_theatre && get_post($wp_post_theatre)) {
            $post = get_post($wp_post_theatre);
            $new_post = array();
            $new_post['ID'] = $wp_post_theatre;
            $new_post['post_content'] = $post_extra;
            wp_update_post($new_post);
        } else {
            $new_post = array();
            $new_post['post_title'] = 'London Theatre Special Offers';
            $new_post['post_content'] = $post_extra;
            $new_post['post_status'] = 'draft';
            $new_post_id = wp_insert_post($new_post);
            update_option('theatrepost', $new_post_id);
        }



        update_option('wptheatresdata', array_merge($show_ids, $added_shows));

        return $count;
    }

    function make_post($all_info) {
        $post['post_title'] = 'London Theatre Special Offers';
        $post['post_content'] = <<<M
            <img src="$all_info[IMAGE_LINK]" class="show-poster-image"/>
         $all_info[SHOWSHORT]
           <div style='clear:both;'></div>
             <p style='font-weight:bold;margin: 0 10px;'>Offer: $all_info[OFFERTEXT]</p>
           <ul class="theatre-booking-info">
           <li>Venue:  $all_info[VENUE_NAME] </li>
           <li>Booking Link:  <a href="$all_info[LINK_URL]">$all_info[LINK_URL]</a> </li>
   
           </ul>
                     
                     
M;
        return $post;
    }

    function pre_make_post($all_info) {

        $saveVal = $all_info['FACEVALUE'] - $all_info['OFFERPRICE'];

        $post_extra = <<<EX
        <div class="theatre-info-container">
                     <h2 class="theatre-showname">$all_info[SHOWNAME]</h2>
        <div class='image-theatre'> <img src="$all_info[IMAGE_LINK]" class="show-poster-image"/></div>
        <div class='desc-theatre'> $all_info[SHOWSHORT]</div>
        <div class='price-was'> Was: <br/> &pound;$all_info[FACEVALUE]</div>
        <div class='price-now'> Now: <br/> &pound;$all_info[OFFERPRICE]</div>          
     <div class='book-now'>Save: <br/> &pound;$saveVal<a href="$all_info[LINK_URL]" class='book-link'>Book Now</a></div>          
         <div style="clear:both"></div>  
         </div>
EX;

        return $post_extra;
    }

    function save_as_post($info, $cat) {
        //array_walk($info, create_function('&$value,$key','$value = mysql_real_escape_string($value);'));
        extract($info);
        $my_post = array(
            'post_title' => $post_title,
            'post_content' => $post_content,
            'post_status' => 'draft',
            'post_author' => 1,
            'post_excerpt' => substr($post_content, 0, 200)
        );

        // Insert the post into the database
        $res = wp_insert_post($my_post);
        if ($res && $cat)
            wp_set_object_terms($res, $cat, 'category');
    }

    function get_info($dom) {
        $all_info = array();
        foreach ($this->info as $val) {

            $all_info[$val] = $this->return_node_val($dom, $val);
        }
        $all_info['IMAGE_LINK'] = $this->return_node_val($dom, 'LINK_URL', false);
        $this->info_array = $all_info;
        return $all_info;
    }

    function return_dom($url) {
        $content = file_get_contents($url);
        $doc = new DOMDocument();
        $doc->loadXML($content);
        return $doc;
    }

    function return_node_val($doc, $name, $atr_not_check = true) {

        foreach ($doc->getElementsByTagName($name) as $item) {
            switch ($name):

                case 'LINK_URL':
                    if ($atr_not_check) {
                        if (!$item->hasAttributes() && $item->parentNode->getAttribute('LINKTYPE') == 'SPECIFIC')
                            return urldecode($item->nodeValue);
                    }else
                    if ($item->getAttribute('IMAGE_WIDTH') == 100)
                        return $item->nodeValue;

                    break;
                default :
                    return $item->nodeValue;

            endswitch;
        }
    }

    function exists_in_table_posts($name) {
        global $wpdb;
        //$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
        $query = "SELECT post_name FROM $wpdb->posts where post_name='$name'";
        $result = $wpdb->get_results($query);
        if (empty($result))
            return false;

        return true;
    }

    function ajax_func() {
        $count = 0;
        $count = $this->extract_process_data();
        echo json_encode(array('num' => $count));
        exit;
    }

}
