<?php

/*
include.php
*/

/**
 *
 * @category        snippet
 * @package         newslist
 * @version         0.2.8
 * @authors         Martin Hecht (mrbaseman) <mrbaseman@gmx.de>
 * @copyright       (c) 2019, Martin Hecht (mrbaseman)
 * @link            https://github.com/WebsiteBaker-modules/newslist
 * @license         GNU General Public License, Version 3
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 5.4 and higher
 *
 **/


/* -------------------------------------------------------- */
// Must include code to stop this file being accessed directly
if(!defined('WB_PATH')) {
        // Stop this file being access directly
        if(!headers_sent()) header("Location: ../index.php",TRUE,301);
        die('<head><title>Access denied</title></head><body><h2 style="color:red;margin:3em auto;text-align:center;">Cannot access this file directly</h2></body></html>');
}
/* -------------------------------------------------------- */


if (!function_exists('list_news')) {

function addBracketNewsList()
{
    $aList = func_get_args();
    return preg_replace('/^(.*)$/', '[$1]', $aList);
};


function list_news($section_id = 0, $g = 0, $p = 0) {

// the following is mainly taken from view.php of the news module

global $post_id, $post_section, $TEXT, $MESSAGE, $MOD_NEWS, $database;
$table="news";
// determine which module to use
$sql = 'SELECT * '
     . 'FROM `'.TABLE_PREFIX.'mod_'.$table.'_settings` '
     . 'WHERE `section_id`='.(int)$section_id;
$query_settings = $database->query($sql);
if ((NULL == $query_settings) or ($query_settings->numRows()==0)) $table="news_img";

// load module language file
$lang = (WB_PATH.'/modules/'.$table.'/languages/' . LANGUAGE . '.php');
require_once(!file_exists($lang) ? WB_PATH.'/modules/'.$table.'/languages/EN.php' : $lang );

//overwrite php.ini on Apache servers for valid SESSION ID Separator
if (function_exists('ini_set')) {
    ini_set('arg_separator.output', '&amp;');
}

// Get user's username, display name, email, and id - needed for insertion into post info
$users = array();
$sql = 'SELECT `user_id`,`username`,`display_name`,`email` FROM `'.TABLE_PREFIX.'users`';
if (($resUsers = $database->query($sql))) {
    while ($recUser = $resUsers->fetchRow()) {
        $users[$recUser['user_id']] = $recUser;
    }
}
// Get all groups (id, title, active, image)
$groups = array(
    0 => array(
        'group_id'  => 0,
        'title'     => '',
        'active'    => true,
        'image'     => ''
    )
);
$sql = 'SELECT `group_id`, `title`, `active` FROM `'.TABLE_PREFIX.'mod_'.$table.'_groups` '
     . 'WHERE `section_id`='.(int)$section_id.' '
     . 'ORDER BY `position` ASC';
if (($query_users = $database->query($sql))) {
    while (($group = $query_users->fetchRow())) {
        // Insert user info into users array
        $groups[$group['group_id']] = $group;
        $sImageUrl = MEDIA_DIRECTORY.'/.'.$table.'/image'.$group['group_id'].'.jpg';
        $groups[$group['group_id']]['image'] = (is_readable(WB_PATH.$sImageUrl) ? WB_URL.$sImageUrl : '');
    }
}
    // Check if we should only list posts from a certain group
    if ($g != 0) {
        $query_extra = 'AND `group_id`='.(int)$g.' ';
    } else {
        $query_extra = '';
    }

    // Get settings
    $setting_header = $setting_post_loop = $setting_footer = $setting_posts_per_page = '';
    $sql = 'SELECT `header`, `post_loop`, `footer`, `posts_per_page` '
         . 'FROM `'.TABLE_PREFIX.'mod_'.$table.'_settings` '
         . 'WHERE `section_id`='.(int)$section_id;
    if (($resSettings = $database->query($sql))) {
        if (($recSettings = $resSettings->fetchRow(MYSQL_ASSOC))) {
            foreach ($recSettings as $key=>$val) {
                ${'setting_'.$key} = $val;
            }
        }
    }
    // Get total number of posts relatet to now
    $t = time();
    $sql = 'SELECT COUNT(*) FROM `'.TABLE_PREFIX.'mod_'.$table.'_posts` '
         . 'WHERE `section_id`='.(int)$section_id.' AND `active`=1 '
         .        'AND `title`!=\'\' '
         .        'AND (`published_when`=0 OR `published_when`<='.$t.') '
         .        'AND (`published_until`=0 OR `published_until`>='.$t.') '
         .        $query_extra;
    $total_num = intval($database->get_one($sql));
    // Work-out if we need to add limit code to sql
    if ($setting_posts_per_page != 0 && $p!=0) {
        $limit_sql = ' LIMIT '.$p.', '.$setting_posts_per_page;
    } else {
        $limit_sql = '';
    }
    // Query posts (for this page)
    $sql = 'SELECT * FROM `'.TABLE_PREFIX.'mod_'.$table.'_posts` '
         . 'WHERE `section_id`='.$section_id.' '
         .        'AND `active`=1 '
         .        'AND `title`!=\'\' '
         .        'AND (`published_when`=0 OR `published_when`<='.$t.') '
         .        'AND (`published_until`=0 OR `published_until`>='.$t.') '
         .        $query_extra
         . 'ORDER BY `position` DESC'.$limit_sql;
    $query_posts = $database->query($sql);
    $num_posts = $query_posts->numRows();
    $display_previous_next_links = 'none';
    if ($num_posts === 0) {
        $setting_header = '';
        $setting_post_loop = '';
        $setting_footer = '';
        $setting_posts_per_page = '';
    }
// Print header
    $aPlaceHolders = addBracketNewsList(
        'DISPLAY_PREVIOUS_NEXT_LINKS',
        'NEXT_PAGE_LINK',
        'NEXT_LINK',
        'PREVIOUS_PAGE_LINK',
        'PREVIOUS_LINK',
        'OUT_OF',
        'OF'
    );
   $aReplacements = array(
       $display_previous_next_links
   );
    print (preg_replace($aPlaceHolders, $aReplacements, $setting_header));
    if ($num_posts > 0)
    {
        $aPlaceHolders = addBracketNewsList(
            'PAGE_TITLE',
            'GROUP_ID',
            'GROUP_TITLE',
            'GROUP_IMAGE',
            'DISPLAY_GROUP',
            'DISPLAY_IMAGE',
            'TITLE',
            'IMAGE',
            'SHORT',
            'MODI_DATE',
            'MODI_TIME',
            'CREATED_DATE',
            'CREATED_TIME',
            'PUBLISHED_DATE',
            'PUBLISHED_TIME',
            'LINK',
            'SHOW_READ_MORE',
            'TEXT_READ_MORE',
            'USER_ID',
            'USERNAME',
            'DISPLAY_NAME',
            'EMAIL'
        );
        while (($post = $query_posts->fetchRow()))
        {
            if (
                isset($groups[$post['group_id']]['active']) AND
                $groups[$post['group_id']]['active'] != false
            ) { // Make sure parent group is active
                $uid = $post['posted_by']; // User who last modified the post
                // Workout date and time of last modified post
                if ($post['published_when'] === '0') {
                    $post['published_when'] = time();
                }
                if ($post['published_when'] > $post['posted_when']) {
                    $post_date = date(DATE_FORMAT, $post['published_when']+TIMEZONE);
                    $post_time = date(TIME_FORMAT, $post['published_when']+TIMEZONE);
                } else {
                    $post_date = date(DATE_FORMAT, $post['posted_when']+TIMEZONE);
                    $post_time = date(TIME_FORMAT, $post['posted_when']+TIMEZONE);
                }
                $publ_date      = date(DATE_FORMAT,$post['published_when']);
                $publ_time      = date(TIME_FORMAT,$post['published_when']);
                // Work-out the post link
                $post_link      = page_link($post['link']);
                $post_link_path = str_replace(WB_URL, WB_PATH,$post_link);
                if(!isset($post['created_when']))$post['created_when']=0;
                $create_date    = date(DATE_FORMAT, $post['created_when']);
                $create_time    = date(TIME_FORMAT, $post['created_when']);
                if ($p > 0) {
                    $post_link .= '?p='.$p;
                }
                if ($g!=0) {
                    if ($p > 0) {
                        $post_link .= '&amp;';
                    } else {
                        $post_link .= '?';
                    }
                    $post_link .= 'g='.$g;
                }
                // Get group id, title, and image
                $group_id      = $post['group_id'];
                $group_title   = $groups[$group_id]['title'];
                $group_image   = $groups[$group_id]['image'];
                $display_image = ($group_image == '') ? "none" : "inherit";
                $display_group = ($group_id == 0) ? 'none' : 'inherit';

                if ($group_image != "") {
                    $group_image= "<img src='".$group_image."' alt='".$group_title."' />";
                }
                $post_image = "";
                if(isset($post['image'])){
                    if ($post['image'] != "") {
                        $post_img = "<img src='".WB_URL.MEDIA_DIRECTORY.'/.news_img/'.$post['post_id'].'/'.$post['image']."' alt='".$post['title']."' />";
                    } else {
                        $post_img = "<img src='".WB_URL."/modules/news_img/images/nopic.png' alt='empty placeholder' />";
                    }
                }
                // Replace [wblink--PAGE_ID--] with real link
                $short = "";
                // Replace vars with values
                $post_long_len = strlen($post['content_long']);
                // set replacements for exchange
                $aReplacements = array(
                    PAGE_TITLE,
                    $group_id,
                    $group_title,
                    $group_image,
                    $display_group,
                    $display_image,
                    $post['title'],
                    $post_image,
                    $short,
                    $post_date,
                    $post_time,
                    $create_date,
                    $create_time,
                    $publ_date,
                    $publ_time
                );
                if (isset($users[$uid]['username']) AND $users[$uid]['username'] != '') {
                        $aReplacements[] = $post_link;
                        $aReplacements[] = 'hidden';
                        $aReplacements[] = '';
                        $aReplacements[] = $uid;
                        $aReplacements[] = $users[$uid]['username'];
                        $aReplacements[] = $users[$uid]['display_name'];
                        $aReplacements[] = $users[$uid]['email'];
                } else {
                        $aReplacements[] = $post_link;
                        $aReplacements[] = 'hidden';
                        $aReplacements[] = '';
                }
                print (str_replace($aPlaceHolders, $aReplacements, $setting_post_loop));
            }
        }
    }
    // Print footer
    $aPlaceHolders = addBracketNewsList(
        'DISPLAY_PREVIOUS_NEXT_LINKS',
        'NEXT_PAGE_LINK',
        'NEXT_LINK',
        'PREVIOUS_PAGE_LINK',
        'PREVIOUS_LINK',
        'OUT_OF',
        'OF'
    );
    $aReplacements = array(
        $display_previous_next_links
    );
    print (str_replace($aPlaceHolders, $aReplacements, $setting_footer));


} // function list_news
} // if !function_exists
