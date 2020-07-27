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

        add_shortcode( 'registration_form', array( $this, 'shortcode' ));
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
        echo '<select id="dates" name="cf-remove-date">';
        foreach ($dates as $id => $date)
        {
            echo '<option value="' . $id . '">' . $date . '</option>';
        }
        echo '</select>';
        echo '</label>';
        echo '<input type="hidden" value="true" name="submit_remove_date" />';
        submit_button('Datum entfernen');
        echo '</form>';
        
        echo '<h2>Anmeldungen</h2>';
        foreach ($dates as $date)
        {
            list($names, $famnames) = $this->getNames($date);

            echo '<h5>' . $date . '</h5>';
            echo '<p>';
            echo '<ol>';
            foreach ($names as $id => $name)
            {
                echo '<li>' . $name . ' ' . $famnames[$id] . '</li>';
            }
            echo '</ol>';
            echo '</p>';
        }   
    }

    function removeUser()
    {
        global $wpdb;
        if (isset($_POST['submit_remove_user']))
        {
            $table_name = $wpdb->prefix . "registration_users";
            $date = $_POST["user-date"];
            $name = $_POST["user-name"];
            $famname = $_POST["user-famname"];

            $wpdb->delete($table_name, array('datum' => $date, 'name' => $name, 'familyname' => $famname));

            echo '<p>Datum wurde entfernt.</p>';
        }
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

    // Returns Names
    function getNames($date)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";
        $query = 'SELECT * FROM ' . $table_name . ' WHERE datum = \'' . $date . '\'';
        $subscribers = $wpdb->get_results($query);

        foreach ($subscribers as $sub)
        {
            if ($sub->age == 'adult')
            {
                $name[] = $sub->name;
                $famname[] = $sub->familyname;
            }
            else
            {
                $youth = $youth + 1;
            }
        }
        return array($name, $famname);
    }

    // Returns number of subscribers of current day
    function getSubscribers($date)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";
        $query = 'SELECT * FROM ' . $table_name . ' WHERE datum = \'' . $date . '\'';
        $subscribers = $wpdb->get_results($query);

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
            // $age = strtolower(sanitize_text_field($_POST["cf-age"]));
            $age = "adult";
            $date = $_POST["cf-date"];

            $msg = $this->formValidation($name, $familyname, $date);

            if ($msg[0])
            {
                //Write to DB
                $wpdb->insert(
                    $table_name,
                    array(
                        'name' => $name,
                        'familyname' => $familyname,
                        'age' => $age,
                        'datum' => $date
                    ), 
                    array( 
                        '%s',
                        '%s',
                        '%s',
                        '%s'
                    ) 
                    );
            }
            echo '<p><b>' . $msg[1] . '</b></p>';
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
        $dates = $this->getDates();
        echo '<p>19.30 Uhr - Niesenteichhalle</br>An den Lothewiesen 6, 33100 Paderborn</p>';
        echo '<p><ul>';
        foreach ($dates as $date)
        {
            $subscribers = $this->getSubscribers($date);
            $free = 16 - $subscribers[0];
            echo '<li>' . $date . ' (' .$free . ' freie Plätze)</li>';
            if ($free > 0)
            {
                $freeDates[] = $date;
            }
        }
        echo '</ul></p>';

    
        echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
        echo '<label>';
        echo '<select id="dates" name="cf-date">';
        foreach ($freeDates as $date)
        {
            echo '<option value="' . $date . '">' . $date . '</option>';
        }
        echo '</select>';
        echo '</label>';
        echo '<label>';
        echo '<input type="text" name="cf-name" placeholder="Vorname" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
        echo '<input type="text" name="cf-familyname" placeholder="Nachname" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf_name"] ) ? esc_attr( $_POST["cf_name"] ) : '' ) . '"/>';
        echo '</label>';
        echo '<label>';
        echo '<input type="checkbox" name="cf-terms" required> Ich akzeptiere die <a href="https://badminton-paderborn.de/wp-content/uploads/2020/06/TV_1875_PB_-_Datenschutzerklärung_-_Mitglieder.pdf" target="_blank">Datenschutzerklärung</a> sowie das <a href="https://badminton-paderborn.de/wp-content/uploads/2020/06/Corona_Maßnahmen_Badminton2.0.pdf" target="_blank">Hygienekonzept</a>.';
        echo '</label>';
        echo '<label>';
        echo '<input type="submit" name="cf-submitted" value="Absenden" />';
        echo '</label>';
        echo '</form>';

        echo '</br>';
        echo 'Falls ihr euch angemeldet hab und doch nicht kommen könnt meldet euch bitte bei Malte ab.</br>';
        echo 'Handy: 0163-7017691 (Anruf, SMS, Whatsapp, Telegram, ...)</br>';
        echo 'Mail: malte@badminton-paderborn.de</br></br>';
    }

    function formValidation($name, $familyname, $date)
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
                    $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE name = '$name' AND familyname = '$familyname' AND datum = '$date'")
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
