<?php
$user = "wp_badminu_0_r";
$pass = "WFwqZ27WKL1BdLZb";
$name = "wp_badminu_db0";
$ip = "sql494.your-server.de";
$table_name_users = "d9y56_registration_users";
$table_name_dates = "d9y56_registration_dates";

try
{
    $db = new PDO("mysql:host=". $ip . ";dbname=" . $name, $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
    echo "Connection Error: " . $e->getMessage();
}
echo "Connection successful.";

function getDates($age, $dbase)
{
    echo "getDates";
    if ($age == 'youth')
    {
        $query = 'SELECT * FROM d9y56_registration_dates WHERE youth = 1';
    }
    elseif ($age == 'all')
    {
        $query = 'SELECT * FROM d9y56_registration_dates';
    }
    else
    {
        $query = 'SELECT * FROM d9y56_registration_dates WHERE adult = 1';
    }

    try
    {
        $entries = $dbase->query($query);
    }
    catch (PDOException $e)
    {
        echo "Query Error: " . $e->getMessage();
    }

    foreach ($entries as $entry)
    {
        $dates[$entry['id']] = $entry['datum'];
    }

    return $dates;
}

function getNames($date, $age, $dbase)
{
    echo "getNames";


    try
    {
        $query = $dbase->prepare("SELECT * FROM d9y56_registration_users WHERE datum = :datum AND age = :age");
        $query->bindParam(':datum', $date, PDO::PARAM_STR);
        $query->bindParam(':age', $age, PDO::PARAM_STR);
        $query->execute();
    }
    catch (PDOException $e)
    {
        echo "Query Error: " . $e->getMessage();
    }

    while ($sub = $query->fetch())
    {
        $name[] = $sub['name'];
        $famname[] = $sub['familyname'];
    }
    return array($name, $famname);
}

function sendMail($database)
{
    echo "sendMail";
    $error = TRUE;
    $ages = ["youth", "adult"];
    $currentDate = date("d.m.");
    $msg = "<html><head><title>Teilnehmer Badminton-Training</title></head><body><h3>Teilnehmer Badminton-Training am " . $currentDate . "</h3>";
    foreach ($ages as $age)
    {
        if ($age == "youth")
        {
            $msg .= "<h5>Jugendliche</h5>";
        }
        else
        {
            $msg .= "<h5>Erwachsene</h5>";
        }
        $dates = getDates($age, $database);
        echo $dates[0];
        
        $subDates = array();
        foreach ($dates as $date)
        {
            if ($currentDate == substr($date, 3))
            {
                $msg .= "<p><ol>";
                list($names, $famnames) = getNames($date, $age, $database);
                echo $names[0];
                foreach ($names as $id => $name)
                {
                    $msg .= "<li>" . $name . " " . $famnames[$id] . "</li>";
                    $error = FALSE;
                }
                $msg .= "</ol></p>";
                
            }
        }

    }
    $msg .= "<p>Bei Fragen bitte bei Malte Becker (malte@badminton-paderborn.de / 01637017691) melden.</p>";
    $msg .= "<p>- Diese E-Mail wurde automatisch generiert</p>";

    // für HTML-E-Mails muss der 'Content-type'-Header gesetzt werden
    $header[] = 'MIME-Version: 1.0';
    $header[] = 'Content-type: text/html; charset=iso-8859-1';
    
    $to = "info@tv1875paderborn.de, malte@badminton-paderborn.de";
    $subject = "Teilnehmer Badminton-Training " . $currentDate;

    // send email
    if ($error == FALSE)
    {
        mail($to, $subject, $msg, implode("\r\n", $header));
        echo "Mail is send.";
    }
    else
    {
        echo "No Mail send.";
    }
}

sendMail($db);
?>