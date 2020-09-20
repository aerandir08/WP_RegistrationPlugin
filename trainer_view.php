<?php
/*
Plugin Name: Trainer View
Description: Trainer can view and delete paticipents
Version: 1.0
Author: Malte Becker
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$start_trainerview = new TrainerView();

class TrainerView
{
    public function __construct()
    {
        add_shortcode( 'trainer_view', array( $this, 'shortcode' ));
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

    function trainer_view()
    {
        echo '<h1>Trainer View</h1>';        
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
                    echo $name . ' ' . $famname;
                    echo '</li>';
                }
                echo '</ol>';
                echo '</p>';
            }
        } 
    }

    function shortcode()
    {
        $this->trainer_view();
    }
}
?>
