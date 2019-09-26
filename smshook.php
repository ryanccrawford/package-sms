<?php

require_once('settings/my_package_settings.php');

$dbl = new \debuglog\logger('debuglog.log');
//Receive the RAW post data.
$content = trim(file_get_contents("php://input"));
//Attempt to decode the incoming RAW post data from JSON.

$sms_message = json_decode($content, true);

$sms_secret = SMS_SECRET;
$sms_from_number = $sms_message['from'];
$sms_from_number = str_replace("+1", "", $sms_from_number);
$sms_to_number = '+1' . SMS_NUMBER_10DIGIT;
$message = $sms_message['body'];

if (!$sms_message['to'] == $sms_to_number) {
    http_response_code(401);
    die;
}
$sms_to_number = SMS_NUMBER_10DIGIT;
http_response_code(200);

//echo var_dump($sms_message);
switch (strtolower($message)) {
    case "help":
        help($sms_from_number);
        die;
        break;
    case "date":
        deliverydate($sms_from_number);
        die;
        break;
    case "status":
        status($sms_from_number);
        die;
        break;
    case "test": //"stop"
        die;
        break;
    default:
        die;
}

function stop($phone)
{ }

function help($phone)
{
    $mess = "Help. You can reply with:"
        . " Date - Delivery Date."
        . " Status - See If Delivered."
        . " Start - Starts after Stop.";
    global $dbl;

    $que = new sendSms\smsQueue();


    $message = array(
        'from' => SMS_NUMBER_10DIGIT,
        'to' => $phone,
        'message' => $mess,
        'has_been_sent' => 0,
        'status' => "200",
        'signature' => "",

    );

    $dbl->append_var($message, "message to save to data base");
    $que->enqueue($message);
    $que->process_queue();
}

function deliverydate($phone)
{
    global $dbl;

    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    $count = get_number_tracker_per_number($phone);
    $dbl->append_var($count, " number of packages ");
    for ($d = 0; $d < $count; $d++) {
        $que = new sendSms\smsQueue();
        $tid = get_tracker_id($phone, $d);
        $dbl->append_var($tid, " tracker id ");
        $eta = get_eta_from_tracker_id($tid);
        $dbl->append_var($eta, " eta ");
        $message = array(
            'from' => SMS_NUMBER_10DIGIT,
            'to' => $phone,
            'message' => "The ETA of your package is " . $eta,
            'has_been_sent' => 0,
            'status' => "200",
            'signature' => "",

        );


        $que->enqueue($message);
    }
    $que->process_queue();
}

function status($phone)
{
    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    $que = new sendSms\smsQueue();
    $que->process_queue();

    $count = get_number_tracker_per_number($phone);



    for ($d = 0; $d < $count; $d++) {

        $tid = get_tracker_id($phone, $d);
        $tracker = get_ep_tracker($tid);
        $r = "";
        if ($count > 1) {
            $r = "You have " . $count . " packages coming. ";
            $r .= " Package #" . ($d + 1) . " status: ";
        } else {

            $r = "Your package status: ";
        }
        $r .= $tracker->status ==  "delivered" ? "delivered" : str_replace("_", " ", $tracker->status);
        $message = array(
            'from' => SMS_NUMBER_10DIGIT,
            'to' => $phone,
            'message' => $r,
            'has_been_sent' => 0,
            'status' => "200",
            'signature' => "",

        );


        $que->enqueue($message);
    }
    $que->process_queue();
}

function get_eta_from_tracker_id($id)
{
    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    $ep_tracker = get_ep_tracker($id);
    $d = strtotime($ep_tracker->est_delivery_date);
    return date('l F d', $d);
}




function get_eta(\EasyPost\Tracker $ep_tracker)
{
    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    $d = strtotime($ep_tracker->est_delivery_date);

    if ($d) {
        return array(
            'dow' => '' . date('l', $d) . '',
            'month' => '' . date('F', $d) . '',
            'day' => '' . date('d', $d) . '',
        );
    }
    return false;
}

function is_sms_active_by_id($tracker_id)
{
    global $db;
    $sql = "SELECT sms_delivery_active FROM va_trackers WHERE tracker_id=" . $tracker_id . ";";
    $db->query($sql);

    if ($db->next_record()) {
        return $db->f('sms_delivery_active') == 1 ? true : false;
    }

    return null;
}

function is_sms_active_by_tracker($id)
{
    global $db;
    $sql = "SELECT sms_delivery_active FROM va_trackers WHERE id=" . $db->tosql($id, INTEGER) . ";";
    $db->query($sql);

    if ($db->next_record()) {
        return $db->f('sms_delivery_active') == 1 ? true : false;
    }

    return null;
}


function get_ep_tracker($id)
{

    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    return \EasyPost\Tracker::retrieve($id);
}





function get_tracker_id($sms_number, $index = 0)
{
    global $db;
    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    $sql = "SELECT id FROM va_trackers WHERE sms_number=" . $sms_number . " and sms_delivery_active=1;";
    $db->query($sql);
    $records = array();
    while ($db->next_record()) {
        $records[] =  $db->f('id');
    }

    return get_ep_tracker($records[$index]);
}

function get_number_tracker_per_number($sms_number)
{
    global $db;
    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    $sql = "SELECT id FROM va_trackers WHERE sms_number=" . $sms_number . " and sms_delivery_active=1;";
    $db->query($sql);
    $c = 0;
    while ($db->next_record()) {
        $c++;
    }

    return $c;
}
