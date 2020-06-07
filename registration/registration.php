<?php
/*
Plugin Name: Simple Registration
Description: Wordpress Plugin to do a simple daily registration
Version: 1.0
Author: Malte Becker
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$start_registator = new Registrator();

class Registrator
{
    public function __construct()
    {
        register_activation_hook( __FILE__, array( $this, 'registratorActivation') );
        register_deactivation_hook( __FILE__, array( $this, 'registratorDeactivation') );

        add_action( 'cleanTableEvent', array( $this, 'clean_table'));
    }

    // Public
    public function registratorActivation()
    {
        $this->createTable();
        if (! wp_next_scheduled ( 'cleanTableEvent' )) {
			wp_schedule_event( strtotime('00:00:00'), 'daily', 'cleanTableEvent' );
		}
    }

    public function registratorDeactivation()
    {
        $this->deleteTable();
        if ( wp_next_scheduled( 'cleanTableEvent' ) ) {
			wp_clear_scheduled_hook( 'cleanTableEvent' );
		}
    }

    // Private

    // Creates table on plugin activation
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

    //Deletes table on plugin deactivation
    function deleteTable()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration";
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    }


    // Returns number of subscribers of current day with given age
    function getSubscribers($age)
    {

    }

    // Writes data from form to database
    function writeToDB($name, $age)
    {

    }

    // Removes all entries from database
    function cleanTable()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration";
        $delete = $wpdb->query("TRUNCATE TABLE $table_name");
    }
}

?>