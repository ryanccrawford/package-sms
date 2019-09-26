<?php
$status_error = "";
include_once("settings/my_package_settings.php");

//Logging file to log all access to the system
$dbl = new \debuglog\logger('package_notify.log');
//If json_decode failed, the JSON is invalid.
$hk = get_param('hook_key');

//Simple security to prevent fake webhooks from atttacking database
$hook_key = filter_var($hk, FILTER_SANITIZE_STRING);

if ($hook_key != WEB_HOOK_SECURITY_KEY) {
    $dbl->append("!!!! UNAUTHORIZED ACCESS FROM " . $_SERVER['REMOTE_ADDR'] . "  -  " . $_SERVER['HTTP_X_FORWARDED_FOR'] . " !!!!");
    $dbl->append("TRIED USING - " . $hook_key . " as the web hook key.");
    return_error(401, 'Sorry, The Key you provided is invalid.');
}


//Receive the RAW post data.
$sms_company = "CWS";
$content = trim(file_get_contents("php://input"));
//Attempt to decode the incoming RAW post data from JSON.
$event = json_decode($content, true);

if (!is_array($event)) {
    return_error(401, 'The data was not an valid event. Access Denied');
}

$tracker_update = null;
$tracker_created = null;
$dbl->append_var($event['result'], " event result  ");
$tracker = $event['result'];
$tracker_id = $tracker['id'];
$order_id = get_order_id_from_tracker_id($tracker_id);
http_response_code(200);
$dbl->append(" Passed 200 Check Point - ");
$tracker_status = $tracker['status'];

$is_business_hours = false;
$is_delivered = false;
$hour = date('H');
if ($hour < 21 && $hour > 7) {
    $is_business_hours = true;
}

if (isset($tracker_status) && $tracker_status == "delivered") {

    $is_delivered = true;
}


$send_notification = is_signed_up($order_id);

$dbl->append_var($send_notification, " send_notification - ");

$unsubcribe = false;

$que = new sendSms\smsQueue();

if ($send_notification) {

    $pn = get_phone_number_from_notify($order_id);

    if (!$pn) {
        die;
    }

    $message = get_message_from_tracker($tracker);
    $text_to_send = convert_to_mes($pn, $message);
    $que->enqueue($text_to_send);
}
if ($is_delivered) {
    if (update_status_if_delivered($order_id)) {
        $unsubcribe = true;
    }
}
if ($unsubcribe) {
    unsubcribe($order_id);
}

if ($is_business_hours) {
    $que->process_queue();
}


function order_exisit($order_id)
{
    global $db, $dbl;
    $sql = "SELECT order_id FROM va_orders WHERE order_id=" . $db->tosql($order_id, INTEGER) . ";";
    $db->query($sql);

    if ($db->next_record()) {
        return $db->f('order_id') !== "" ? true : false;
    }

    return false;
}

function update_status_if_delivered($order_id)
{
    global $db, $dbl;
    $new_order_status = 28;
    $this_status = 29;
    $dbl->append(" INSIDE update_status_if_delivered ");
    $sql = "SELECT order_status FROM va_orders WHERE order_id=" . $order_id . ";";
    try {
        $db->query($sql);
        if ($db->next_record()) {
            $status = $db->f('order_status');
            if ($status !== $new_order_status && $status == $this_status) {
                $sql = "UPDATE va_orders SET order_status=" . $new_order_status . " WHERE order_id=" . $order_id . ";";
                $db->query($sql);
                return true;
            }
        }
    } catch (Exception $e) {
        $dbl->append_var($e->getMessage(), " TRY ERROR ");
    }
    return false;
}

function convert_to_mes($pn, $message)
{

    return array(
        'to' => $pn,
        'from' => SMS_NUMBER_10DIGIT,
        'message' => $message,
        'status' => 200,
        'has_been_sent' => 0,
        'signature' => EASY_HOOK_TEST_ID,
    );
}

function get_order_id_from_tracker_id($tracker_id)
{
    global $db, $dbl;
    $dbl->append(" IN SIDE Function get_order_id_from_tracker_id - ");
    $sql = "SELECT order_id FROM va_trackers WHERE id='" . $tracker_id . "';";
    $dbl->append(" QUERY - " . $sql . "<br>");
    $db->query($sql);
    $dbl->append(" passed QUERY - ");
    if ($db->next_record()) {
        $rid = $db->f('order_id');
        $dbl->append(" Returning - ORDER ID " . $rid);
        return $rid;
    }

    return 0;
}

function has_tracker($order_id)
{
    global $db, $dbl;
    $dbl->append(" IN SIDE Function has_tracker - ");
    $sql = "SELECT id FROM va_trackers WHERE order_id=" . $db->tosql($order_id, INTEGER) . ";";
    $dbl->append(" QUERY - " . $sql . "<br>");

    $db->query($sql);
    $dbl->append(" passed QUERY - ");
    if ($db->next_record()) {
        $id = $db->f('id');
        $dbl->append(" Returning - ID " . $id);
        return $id !== "" ? true : false;
    }

    return false;
}

function is_signed_up($order_id)
{
    global $db, $dbl;
    $sql = "SELECT sms_delivery_active, status FROM va_trackers WHERE order_id=" . $db->tosql($order_id, INTEGER) . " AND status!='delivered';";


    $db->query($sql);

    if ($db->next_record()) {
        return $db->f('sms_delivery_active') == 1 ? true : false;
    }

    return false;
}

function get_phone_number_from_notify($order_id)
{
    global $db, $dbl;
    $sql = "SELECT sms_number FROM va_trackers WHERE order_id=" . $db->tosql($order_id, INTEGER) . ";";
    $db->query($sql);

    if ($db->next_record()) {

        return $db->f('sms_number');
    }

    return 0;
}

function get_message_from_tracker($tracker)
{
    global $dbl;
    global $unsubcribe;
    $last_tracking_detail = "";

    if ($tracker["status"] == "delivered") {
        $unsubcribe = true;
    }
    $tracking_details = $tracker['tracking_details'];
    $dbl->append_var($tracking_details);
    if (is_array($tracking_details) && count($tracking_details) > 0) {
        $detail = array_pop($tracking_details);

        $mesg = $detail['message'];

        $status = str_replace("_", " ", $detail['status']);

        $date = strtotime($detail['datetime']);
        $date_time = date("M jS g:ia", $date);

        $last_tracking_detail = "Hello! This is a package notification sent from CWS. ";
        $last_tracking_detail .= $date_time . " Status: " . $mesg;
    }

    return $last_tracking_detail;
}

function unsubcribe($order_id)
{

    global $db, $dbl;

    $date = date('Y-m-d H:i:s');
    $dbl->append("<br> IN SIDE Function unsubcribe - ");
    $sql = "UPDATE va_trackers SET sms_number='', is_active=0, sms_delivery_active=0, status='delivered', delivered_on='$date' WHERE order_id=" . $db->tosql($order_id, INTEGER);
    $dbl->append("<br> SQL - " . $sql);
    $db->query($sql);

    $db->next_record();
    $dbl->append("<br> RETURNING - ");
}

function return_error($code, $message)
{
    http_response_code($code);
    $response = array(
        'error' => 'true',
        'mess' => $message
    );
    echo json_encode($response);
    die;
}

function get_easy_post_tracker($id)
{
    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
    return \EasyPost\Tracker::retrieve($id);
}
