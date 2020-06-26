<?php
/*
Plugin Name: Simple Registration Dev
Description: Wordpress Plugin to do a simple daily registration Development-Version
Version: 2.0
Author: Malte Becker
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$start_registator = new Registrator_dev();

class Registrator_dev
{
    public function __construct()
    {
        register_activation_hook( __FILE__, array( $this, 'installPlugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'uninstallPlugin' ) );

        add_action('admin_menu', array($this, 'addBackend'));

        add_shortcode( 'registration_form_dev', array( $this, 'shortcode' ));
    }

    // Public
    public function installPlugin()
    {
        $this->createTable_users();
        $this->createTable_dates();
    }

    public function uninstallPlugin()
    {
        $this->deleteTables();
    }

    // Private

    function addBackend()
    {
        add_action('add_date', array($this, 'addDate'));
        add_menu_page('Registrator', 'Registrator', 'manage_options', 'registrator', array($this, 'backend'));
    }

    function backend()
    {
       
        if (!current_user_can('manage_options'))
        {
            wp_die( __('You do not have sufficient pilchards to access this page.'));
        }

        if (isset($_POST['submit_add_date']) && check_admin_referer('add_date_clicked')) {
            $this->addDate();
        }
        if (isset($_POST['submit_remove_date']) && check_admin_referer('remove_date_clicked')) {
            $this->removeDate();
        }

        echo '<h1>Registrator Backend</h1>';
        echo '<form action="options-general.php?page=registrator" method="post">';
        wp_nonce_field('add_date_clicked');
        echo '<label>';
        echo '<input type="text" name="cf-date" placeholder="Date" pattern="(0[1-9]|[1-2][0-9]|3[0-1]).(0[1-9]|1[0-2]).[0-9]{4}"/>';
        echo '</label>';
        echo '<input type="hidden" value="true" name="submit_add_date" />';
        submit_button('Datum hinzufügen');
        echo '</form>';

        $dates = $this->getDates();
        echo '<form action="options-general.php?page=registrator" method="post">';
        wp_nonce_field('remove_date_clicked');
        echo '<label>';
        echo '<select id="dates name="cf-remove-date">';
        foreach ($dates as $id => $date)
        {
            echo '<option value="' . $id . '">' . $date . '</option>';
        }
        echo '</label>';
        echo '<input type="hidden" value="true" name="submit_remove_date" />';
        submit_button('Datum entfernen');
        echo '</form>';

        
    }

    function addDate()
    {
        global $wpdb;

        if (isset($_POST['submit_add_date']))
        {
            $table_name = $wpdb->prefix . "registration_dates";

            $date = $_POST["cf-date"];

            //Write to DB
            $wpdb->insert(
                $table_name,
                array(
                    'datum' => $date
                ), 
                array( 
                    '%s'
                ) 
            );

            echo '<p>Datum wurde hinzugefügt</p>';
        }
    }

    function removeDate()
    {
        global $wpdb;
        if (isset($_POST['submit_remove_date']))
        {
            $table_name = $wpdb->prefix . "registration_dates";
            $id = $_POST["cf-remove-date"];

            echo $id;
            $wpdb->delete($table_name, array('id' => $id));

            echo '<p>Datum wurde entfernt.</p>';
        }
    }

    function getDates()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "registration_dates";

        $entries = $wpdb->get_results('SELECT * FROM ' . $table_name);

        foreach ($entries as $entry)
        {
            $dates[$entry->id] = $entry->datum;
        }

        return $dates;
    }

    // Creates table on plugin activation
    function createTable_users()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";

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

    function createTable_dates()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_dates";

        $charset_collate = $wpdb->get_charset_collate();

        if ($wpdb->get_var("SHOW TABLES LIKE '". $table_name ."'"  ) != $table_name ) 
        {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                datum tinytext NOT NULL,
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
    function deleteTables()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
        $table_name = $wpdb->prefix . "registration_dates";
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    }


    // Returns number of subscribers of current day
    function getSubscribers()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";

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
        $table_name = $wpdb->prefix . "registration_users";

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
        $table_name = $wpdb->prefix . "registration_users";
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
        $table_name = $wpdb->prefix . "registration_users";

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