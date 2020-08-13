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

        add_action('admin_enqueue_scripts', array($this, 'kb_admin_style'));
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

    function kb_admin_style() {
        wp_enqueue_style('admin-styles', get_template_directory_uri().'/style-admin.css');
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
        if (isset($_POST['submit_delete_sub']) && check_admin_referer('delete_sub_clicked')) {
            $this->deleteSubscription();
        }

        echo '<h1>Registrator Backend</h1>';
        echo '<form action="options-general.php?page=registrator" method="post">';
        wp_nonce_field('add_date_clicked');
        echo '<label>';
        echo '<input type="text" name="cf-date" placeholder="Date"/>';
        echo '</label>';
        echo '<label><input type="checkbox" name="cf-adult">Adult  </label>';
        echo '<label><input type="checkbox" name="cf-youth">Youth</label>';
        echo '<input type="hidden" value="true" name="submit_add_date" />';
        submit_button('Datum hinzufügen');
        echo '</form>';

        $dates = $this->getDates('all');
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
        $ages = ['youth', 'adult'];
        foreach ($ages as $age)
        {
            echo '<h4>' . $age . '</h4>';
            $dates = $this->getDates($age);
            foreach ($dates as $date)
            {
                list($names, $famnames) = $this->getNames($date, $age);

                echo '<h5>' . $date . '</h5>';
                echo '<p>';
                echo '<ol>';
                foreach ($names as $id => $name)
                {
                    $famname = $famnames[$id];
                    echo '<li>';
                    
                    echo '<form class="form-inline" action="options-general.php?page=registrator" method="post">';
                    wp_nonce_field('delete_sub_clicked');
                    echo '<input type="hidden" value="' . $name . '" name="cf-name" id="idone"/>';
                    echo '<input type="hidden" value="' . $famname . '" name="cf-famname" id="idtwo"/>';
                    echo '<input type="hidden" value="' . $date . '" name="cf-date" id="idthree"/>';
                    echo '<input type="hidden" value="true" name="submit_delete_sub" />';
                    echo '<label for="submit">' . $name . ' ' . $famname . '</label>';
                    echo '<button type="submit" class="custom-button" id="submit">&#10006;</button>';
                    echo '</form>';
                    echo '</li>';
                }
                echo '</ol>';
                echo '</p>';
            }
        } 
    }

    function deleteSubscription()
    {
        global $wpdb;
        if (isset($_POST['submit_delete_sub']))
        {
            $name = $_POST["cf-name"];
            $famname = $_POST["cf-famname"];
            $date = $_POST["cf-date"];

            // echo '<script type="text/javascript">alert("' . $name . $famname . $date . '");</script>';

            $table_name = $wpdb->prefix . "registration_users";

            $query = "DELETE FROM $table_name WHERE name='$name' AND familyname='$famname' AND datum='$date'";
            $wpdb->query($query);

            echo '<p>Anmeldung wurde entfernt.</p>';
        }
    }

    function addDate()
    {
        global $wpdb;

        if (isset($_POST['submit_add_date']))
        {
            $table_name = $wpdb->prefix . "registration_dates";

            $date = $_POST["cf-date"];
            $adult = $_POST["cf-adult"];
            if ($adult == 'on')
            {
                $adult = TRUE;
            }
            else
            {
                $adult = FALSE;
            }
            $youth = $_POST["cf-youth"];
            if ($youth == 'on')
            {
                $youth = TRUE;
            }
            else
            {
                $youth = FALSE;
            }

            //Write to DB
            $wpdb->insert(
                $table_name,
                array(
                    'datum' => $date,
                    'adult' => $adult,
                    'youth' => $youth
                ), 
                array( 
                    '%s',
                    '%d',
                    '%d'
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

    function getDates($age)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "registration_dates";
        if ($age == 'youth')
        {
            $query = 'SELECT * FROM ' . $table_name . ' WHERE youth = 1';
        }
        elseif ($age == 'all')
        {
            $query = 'SELECT * FROM ' . $table_name;
        }
        else
        {
            $query = 'SELECT * FROM ' . $table_name . ' WHERE adult = 1';
        }
        $entries = $wpdb->get_results($query);

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
                adult boolean,
                youth boolean,
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
    function getNames($date, $age)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";
        $query = "SELECT * FROM $table_name WHERE datum = '$date' AND age = '$age'";
        $subscribers = $wpdb->get_results($query);

        foreach ($subscribers as $sub)
        {
            $name[] = $sub->name;
            $famname[] = $sub->familyname;
        }
        return array($name, $famname);
    }

    // Returns number of subscribers of current day
    function getSubscribers($date, $age)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";
        $query = "SELECT * FROM $table_name WHERE datum = '$date' AND age = '$age'";

        $subscribers = $wpdb->get_results($query);

        return count($subscribers);
    }

    // Writes data from form to database
    function writeToDB()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "registration_users";

        if ( isset($_POST['cf-submitted-youth']))
        {
            $name = strtolower(sanitize_text_field($_POST["cf-name"]));
            $familyname = strtolower(sanitize_text_field($_POST["cf-familyname"]));
            // $age = strtolower(sanitize_text_field($_POST["cf-age"]));
            $age = "youth";
            $date = $_POST["cf-date"];

            $msg = $this->formValidation($name, $familyname, $date, $age);

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
            echo '<script type="text/javascript">alert("'.$msg[1].'");</script>';
        }

        if ( isset($_POST['cf-submitted-adult']))
        {
            $name = strtolower(sanitize_text_field($_POST["cf-name"]));
            $familyname = strtolower(sanitize_text_field($_POST["cf-familyname"]));
            // $age = strtolower(sanitize_text_field($_POST["cf-age"]));
            $age = "adult";
            $date = $_POST["cf-date"];

            $msg = $this->formValidation($name, $familyname, $date, $age);

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
            echo '<script type="text/javascript">alert("'.$msg[1].'");</script>';
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
        echo '<h2>Informationen</h2>';
        echo '<p>Hier könnt ihr euch für das Training anmelden. Das Training findet zu den gewohnten und unter Trainingszeiten aufgelisteten Zeiten in der Kaukenberghalle statt.<p>';
        echo '<p>Falls ihr euch angemeldet hab und doch nicht kommen könnt meldet euch bitte bei Malte ab.</br>';
        echo 'Handy: 0163-7017691 (Anruf, SMS, Whatsapp, Telegram, ...)</br>';
        echo 'Mail: malte@badminton-paderborn.de</p>';

        $ages = ['youth', 'adult'];
        
        foreach ($ages as $age)
        {
            if ($age == youth)
            {
                echo '<h2>Jugendtraining</h2>';
            }
            else
            {
                echo '<h2>Erwachsenentraining</h2>';
            }
            echo '<p><ul>';

            $dates = $this->getDates($age);
            $freeDates = array();
            foreach ($dates as $date)
            {
                $subscribers = $this->getSubscribers($date, $age);
                $free = 30 - $subscribers;
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
            echo '<input type="checkbox" name="cf-terms" required> Ich akzeptiere die <a href="https://badminton-paderborn.de/wp-content/uploads/2020/06/TV_1875_PB_-_Datenschutzerklärung_-_Mitglieder.pdf" target="_blank">Datenschutzerklärung</a> sowie das <a href="https://badminton-paderborn.de/wp-content/uploads/2020/08/Corona_Maßnahmen_Badminton3.0.pdf" target="_blank">Hygienekonzept</a>.';
            echo '</label>';
            echo '<label>';
            if ($age == 'youth')
            {
                echo '<input type="submit" name="cf-submitted-youth" value="Absenden" />';
            }
            else
            {
                echo '<input type="submit" name="cf-submitted-adult" value="Absenden" />';
            }
            echo '</label>';
            echo '</form>';
        }
    }

    function formValidation($name, $familyname, $date, $age)
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
                    $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE name = '$name' AND familyname = '$familyname' AND datum = '$date' AND age = '$age'")
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
