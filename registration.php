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
        register_activation_hook( __FILE__, array( $this, 'registratorActivation' ) );
        register_deactivation_hook( __FILE__, array( $this, 'registratorDeactivation' ) );

        add_action( 'cleanTableEvent', array( $this, 'clean_table' ));

        add_shortcode( 'registration_form', array( $this, 'shortcode' ));
    }

    // Public
    public function registratorActivation()
    {
        $this->createTable();
        if (! wp_next_scheduled ( 'cleanTableEvent' ))
        {
			wp_schedule_event( strtotime('00:00:00'), 'daily', 'cleanTableEvent' );
		}
    }

    public function registratorDeactivation()
    {
        $this->deleteTable();
        if ( wp_next_scheduled( 'cleanTableEvent' ) )
        {
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
                familyname tinytext NOT NULL,
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


    // Returns number of subscribers of current day
    function getSubscribers()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration";

        $subscribers = $wpdb->get_results('SELECT * FROM ' . $table_name);

        $adults = 0;
        $youth = 0;
        foreach ($subscribers as $sub)
        {
            if ($sub->age == 'adult')
            {
                $adults = $adults + 1;
            }
            else
            {
                $youth = $youth + 1;
            }
        }

        return array($adults, $youth);
    }

    // Writes data from form to database
    function writeToDB()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration";

        if ( isset($_POST['cf-submitted']))
        {
            $name = strtolower(sanitize_text_field($_POST["cf-name"]));
            $familyname = strtolower(sanitize_text_field($_POST["cf-familyname"]));
            $age = strtolower(sanitize_text_field($_POST["cf-age"]));

            $msg = $this->formValidation($name, $familyname);

            if ($msg[0])
            {
                //Write to DB
                $wpdb->insert(
                    $table_name,
                    array(
                        'name' => $name,
                        'familyname' => $familyname,
                        'age' => $age
                    ), 
                    array( 
                        '%s',
                        '%s',
                        '%s'
                    ) 
                    );
            }
            echo $msg[1];
        }

    }

    // Removes all entries from database
    function cleanTable()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration";
        $delete = $wpdb->query("TRUNCATE TABLE $table_name");
    }

    function html_form()
    {   
        //Check for free space
        $subscribers = $this->getSubscribers();
        $free_places_adult = 16 - $subscribers[0];
        $free_places_youth = 16 - $subscribers[1];
        
        $timestamp = time();
        $datum = date("d.m.Y", $timestamp);
        echo '<h2>Anmeldung für das Training am ' . $datum . '</h2>';
        echo '<p>';
        echo 'Freie Plätze Erwachsene: ' . $free_places_adult;
        echo '<br>';
        echo 'Freie Plätze Jugendliche: ' . $free_places_youth;
        echo '</p>';

        // Free space everywhere
        if ($free_places_adult != "0" && $free_places_youth != "0")
        {
            echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
            echo '<label>';
            echo '<input type="text" name="cf-name" placeholder="Vorname" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
            echo '<input type="text" name="cf-familyname" placeholder="Nachname" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
            echo '</label>';
            echo '<label>';
            echo '<select name="cf-age">';
            echo '<option value="youth">Jugendliche</option>';
            echo '<option value="adult" selected="selected">Erwachsene</option>';
            echo '</select>';
            echo '</label>';
            echo '<label>';
            echo '<input type="submit" name="cf-submitted" value="Anmelden" />';
            echo '</label>';
            echo '</form>';
        }
        //Free space adults
        elseif ($free_places_youth != "0")
        {
            echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
            echo '<label>';
            echo '<input type="text" name="cf-name" placeholder="Name" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
            echo '<input type="text" name="cf-familyname" placeholder="Nachname" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
            echo '</label>';
            echo '<label>';
            echo '<select name="cf-age">';
            echo '<option value="adult" selected="selected">Erwachsene</option>';
            echo '</select>';
            echo '</label>';
            echo '<label>';
            echo '<input type="submit" name="cf-submitted" value="Absenden" />';
            echo '</label>';
            echo '</form>';
        }
        //Free space youth
        elseif ($free_places_adult != "0")
        {
            echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
            echo '<label>';
            echo '<input type="text" name="cf-name" placeholder="Name" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
            echo '<input type="text" name="cf-familyname" placeholder="Nachname" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
            echo '</label>';
            echo '<label>';
            echo '<select name="cf-age">';
            echo '<option value="youth" selected="selected">Jugendliche</option>';
            echo '</select>';
            echo '</label>';
            echo '<label>';
            echo '<input type="submit" name="cf-submitted" value="Absenden" />';
            echo '</label>';
            echo '</form>';
        }
        //No free space
        else
        {
            echo '<p>Leider sind für das Training keine Plätze mehr verfügbar.</p>';
        }
    }

    function formValidation($name, $familyname)
    {
        if ($name == '')
        {
            return array(FALSE, "Vorname darf nicht leer sein.");
        }

        if ($familyname == '')
        {
            return array(FALSE, "Nachname darf nicht leer sein.");
        }

        global $wpdb;
        $table_name = $wpdb->prefix . "registration";

        $exists = $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE name = '$name' AND familyname = '$familyname'")
        );
        if ($exists)
        {
            return array(FALSE, "Du bist bereits angemeldet.");
        }
        else
        {
            return array(TRUE, "Anmeldung war erfolgreich.");
        }
    }

    function shortcode()
    {
        $this->html_form();
        $this->writeToDB();
    }
}

?>