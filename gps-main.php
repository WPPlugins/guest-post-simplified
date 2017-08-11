<?php
/*
Plugin Name: Guest Post Simplified
Plugin URI: http://susantaslab.com/plugins/gps
Description: A simple and secure Guest Post/Blogging Plugin allow your visitors to submit contents for your site without registration.
Version: 1.4
Author: Susanta K Beura
Author URI: http://susantaslab.com/
License: GPL v2
Usages: [guestpost pstat="draft|pending|published" excerpt="true|false" image="true|false" ping="closed|open" comment="closed|open" redirect="URL to redirect after post submitted"]
*/
if( !defined( 'ABSPATH') )
    exit();
else
    require_once(ABSPATH .'wp-includes/pluggable.php');

if ( !isset( $_POST['GPS_Post_Form_Nonce'] ) || !wp_verify_nonce( $_POST['GPS_Post_Form_Nonce'], 'GPS_Post_Form' ) ) {

    add_shortcode( 'guestpost', 'Function_GuestPostSimplified' );
    add_filter( 'plugin_row_meta', 'GPS_Plugin_Links', 10, 2 );

} else {
    $Post_Title = $_POST["title"];
    $Post_Content = $_POST["description"];
    $Post_Excerpt = (isset($_POST["excerpt"])?$_POST["excerpt"]:'');
    $Post_Tags = $_POST["tags"];
    $Post_Author = $_POST["author"];
    $Author_EmailID = $_POST["email"];
    $Author_WebSite = $_POST["site"];
    $Post_Authorid = $_POST["authorid"];
    $Post_Category = $_POST["category"];
    $Success_Redirect = $_POST["redirect"];
    $Post_Status = $_POST["postatus"];
    $Post_Ping = $_POST["ping"];
    $Post_Comment = $_POST["comment"];
    $WP_Nonce=$_POST["GPS_Post_Form_Nonce"];


    $user = get_user_by("login",$Post_Authorid);
    $Post_Authorid = $user->ID;

    $new_post = array(
            'post_title'    => sanitize_text_field($Post_Title),
            'post_content'  => $Post_Content,
            'post_excerpt'  => $Post_Excerpt,
            'post_category' => array($_POST['cat']),
            'tags_input'    => sanitize_text_field($Post_Tags),
            'post_status'   => $Post_Status,
            'post_type'     => 'post',
            'ping_status'   => $Post_Ping,
            'comment_status'=> $Post_Comment,
            'post_author'   => $Post_Authorid
    );

    $New_PID = wp_insert_post($new_post);
//    echo 'Post created ';

    add_post_meta($New_PID, 'GP_Author', sanitize_text_field($Post_Author), true);
    add_post_meta($New_PID, 'GP_Author_Email', sanitize_email($Author_EmailID), true);
    add_post_meta($New_PID, 'GP_Author-URL', sanitize_text_field($Author_WebSite), true);

    $headers[] = 'From: Guest Post Simplufied Plugin <'.sanitize_email($Author_EmailID).'>';

    $message = 'Dear Site Admin,';
    $message .= 'A new guest post submitted on your website <b>'. get_option( 'blogname' ).'</b><br /><br />';
    $message .= 'Post Title: '. sanitize_text_field($Post_Title) .'<br />';
    $message .= 'Post Content: '.sanitize_text_field($Post_Excerpt).'<br />';
    $message .= 'Author: '.sanitize_text_field($Post_Author).'<br />';
    $message .= 'Email: '.sanitize_email($Author_EmailID).'<br />';
    $message .= 'The submitted post is saved as '.$Post_Status.'.<br /><br />';
    $message .= 'Thank you.<br /><br />';
    $message .= 'Kind regards<br />';
    $message .= 'GPS on '.get_option( 'blogname' ). '<br /><br />';
    $message .= '<i>PS: Please rate our Guest Post Simplified plugin at <a href="https://wordpress.org/support/view/plugin-reviews/guest-post-simplified?rate=5#postform" target="_blank"  rel="external">WordPress.org</a>. If you have any question/problem with our plugin please consider write to us at WordPress plugin <a href="http://wordpress.org/support/plugin/guest-post-simplified" target="_blank" rel="external">support forum</a>.</i>';
    $toID = get_option( 'admin_email' );

    add_filter( 'wp_mail_content_type', 'set_html_content_type' );
        if (!wp_mail( $toID, '[GPS] New Guest Post Submitted', $message, $headers )){
            remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
            header("Location: $Success_Redirect");
        } else {
            remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
            header("Location: $Success_Redirect"."?msg=Success");
        }

}

function Function_GuestPostSimplified( $atts ) {
    extract ( shortcode_atts (array(
        'pstat'     => 'draft',
        'excerpt'   => 1,
        'image'     => 0,
        'ping'      => 'closed',
        'comment'   => 'closed',
        'redirect'  => get_bloginfo('home'),
    ), $atts ) );

    $retVal = '<form class="SLB-Guest-Post-Simplified" action="" method="post" autocomplete="on">'; //'. plugins_url( 'gps-submit-data.php' , __FILE__ ).'
    $retVal .= '<p>The (*) marked fields are mandatory.</p>';
    $retVal .= '<div style="width:580px;border:1px solid #eeeeee;">';
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <strong>'. __('Post Title</strong> <em>(Shorter is Better)</em><strong>:*') .'</strong><br />';
    $retVal .= '        <input type="text" name="title" size="60" maxlength="120" required="required" placeholder="'.__('Post Title Goes Here').'" autofocus />';
    $retVal .= '    </div>';
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <strong>'.__('Post Author Name:*').'</strong><br />';
    $retVal .= '        <input type="text" name="author" size="60" required="required" placeholder="'.__('Post Author Name').'" />';
    $retVal .= '    </div>';
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <strong>'.__('Email Address:*').'</strong><br />';
    $retVal .= '        <input type="email" name="email" size="60" required="required" placeholder="'.__('Your Valid Email').'" />';
    $retVal .= '    </div>';
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <strong>'.__('Website URL:*').'</strong><br />';
    $retVal .= '        <input type="text" required="required" name="site" size="60" placeholder="'.__('URL of Your Website').'" />';
    $retVal .= '    </div>';
    if ($excerpt){
        /* Excerpt Textbox Starts */
        $retVal .= '    <div style="width:578px;margin:5px auto;">';
        $retVal .= '        <strong>'.__('Post Summery/Excerpt:*').'</strong><br />';
        ob_start();
        wp_editor( '', 'excerpt', array("wpautop" => true, "media_buttons" => false, "textarea_name" => "excerpt", "textarea_rows" => "5", "drag_drop_upload" => false) );
        $retVal .= ob_get_clean();

        $retVal .= '    </div>';
        /* Excerpt Textbox Ends */
    }
    /* Post Textbox Starts */
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <strong>'.__('Post Content:*').'</strong><br />';
    $retVal .= wp_nonce_field('GPS_Post_Form','GPS_Post_Form_Nonce');

    ob_start();
    wp_editor( '', 'description', array("wpautop" => true, "media_buttons" => ($image), "textarea_name" => "description", "textarea_rows" => "25", "drag_drop_upload" => false) );
    $retVal .= ob_get_clean();

    $retVal .= '    </div>';
    /* Post Textbox Ends */
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <strong>'.__('Select a Category:*').' </strong>';
    $retVal .=      wp_dropdown_categories('show_option_none=Select a category...&tab_index=4&taxonomy=category&hide_empty=0&echo=0');
    $retVal .= '        <input type="hidden" required="required" value="0'. $cat .'" name="category"><br />';
    $retVal .= '    </div>';
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <strong>'.__('Keyword or Tags:*').'</strong><br />';
    $retVal .= '        <input type="text" required="required" name="tags" size="60" placeholder="'.__('Separate keywords/tags with commas').'" autocomplete="on">';
    $retVal .= '    </div>';
    $retVal .= '    <div style="width:578px;margin:5px auto;">';
    $retVal .= '        <input type="hidden" value="'. $author .'" name="authorid">';
    $retVal .= '        <input type="hidden" value="'. $redirect .'" name="redirect">';
    $retVal .= '        <input type="hidden" value="'. $pstat .'" name="postatus">';
    $retVal .= '        <input type="hidden" value="'. $ping .'" name="ping">';
    $retVal .= '        <input type="hidden" value="'. $comment .'" name="comment">';
    $retVal .= '        <input type="hidden" value="'. get_home_url() .' name="siteurl">';
    $retVal .= '        <input type="submit" value="' . __('✓ Submit Your Post', 'SLB-Guest-Post-Simplified') . '"><br />';
    $retVal .= '        <p style="text-align:justify;">By submitting your post for publication indicates that you have read, understood and agree with our web site terms of service. Please remember, you will be held responsible for any copyright violation due to your post. Site owner or plugin developer shall not be held responsible for any copyright infringement. If you have any objection on any guest post on our website, please <a href="mailto:'. get_option( 'admin_email' ) .'">write us</a> with proper proof of your claim.';
    $retVal .= '    </div>';
    $retVal .= '</div>';
    $retVal .= '</form>';

    return $retVal;
    }

function GPS_Plugin_Links( $links, $file ) {
   if ( strpos( $file, 'gps-main.php' ) !== false ) {
      $new_links = array(
               '<a href="http://wordpress.org/support/view/plugin-reviews/guest-post-simplified?rate=5#postform" target="_blank">' . __( 'Rate us' ) . '</a>',
               '<a href="http://support.susantaslab.com/" target="_blank">' . __( 'Support Forum' ) . '</a>',
            );
      $links = array_merge( $links, $new_links );
   }
   return $links;
}

function set_html_content_type() {

    return 'text/html';
}

?>