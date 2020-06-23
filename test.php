<?php
/*
Plugin Name: Test
Description: Wordpress Plugin to do a simple daily registration
Version: 1.0
Author: Malte Becker
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action('admin_menu', 'addBackend');


function addBackend()
{
    add_menu_page('Test', 'Test', 'manage_options', 'test', 'print_user_form');
}

function print_user_form() {
echo '<form method="POST">';
wp_nonce_field('user_info', 'user_info_nonce', true, true);
echo '<input type="text" name="cf-date" placeholder="Date" pattern="(0[1-9]|[1-2][0-9]|3[0-1]).(0[1-9]|1[0-2]).[0-9]{4}"/>';
submit_button('Send Data');
echo '</form>';
debugLog("backedn");;
}

add_action('template_redirect', function() {
    debugLog("Function is called.");
    if ( ( is_single() || is_page() ) &&
         isset($_POST[user_info_nonce]) &&
         wp_verify_nonce($_POST[user_info_nonce], 'user_info')) 
    {
         $date = $_POST['cf_date'];
    }
 });


 function debugLog($msg)
 {
    echo("<script>console.log('Log: " . $msg . "');</script>");
 }