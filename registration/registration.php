<?php
/*
Plugin Name: Simple Registration
Description: Wordpress Plugin to do a simple daily registration
Version: 1.0
Author: Malte Becker
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function createTable()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "registration";

    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '". $table_name ."'"  ) != $table_name ) 
    {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            age tinytext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        if(!function_exists('dbDelta'))
        {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta( $sql );
    }
}

register_activation_hook( __FILE__, 'createTable' );

$start_registrator = new Registrator();

class Registrator
{
    public function __construct()
    {

    }

    // Returns number of subscribers of current day with given age
    public function getSubscribers($age)
    {

    }

    // Writes data from form to database
    public function writeToDB($name, $age)
    {

    }

    // Removes all entries from database
    public function cleanDB()
    {
        // global $wpdb;
        // $table_name = $wpdb->prefix . "registration";
        // $delete = $wpdb->query("TRUNCATE TABLE $table_name");
    }
}

?>