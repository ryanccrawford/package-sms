<?php
require_once('settings/my_package_settings.php');

$dbl = new \debuglog\logger('get_notify.log');

$p = $_GET['phonenumber'];
$phone  = filter_var($p, FILTER_SANITIZE_STRING);

$o = $_GET['orderid'];
$oid = filter_var($o, FILTER_SANITIZE_STRING);

$dbl->append_var($phone);
$dbl->append_var($oid);
if (!isset($phone) || !isset($oid)) {
    $errors = array(
        "errors" => "Sorry, Try again Later.",

    );
    echo json_encode($errors);
    die;
}
if (!$phone || !$oid) {
    $errors = array(
        "errors" => "Sorry, Try again Later.",

    );
    echo json_encode($errors);
    die;
}

register_for_notifications($phone, $oid);

function register_for_notifications($phone, $oid)
{
    global $dbl;
    global $db;
    $que = new sendSms\smsQueue();
    $message = array();
    $sql = "SELECT order_id, tracker_id, sms_delivery_active, is_active, sms_number, id FROM va_trackers where order_id=" . $db->tosql($oid, INTEGER);
    $db->query($sql);

    if ($db->next_record()) {

        $sms_is_active = $db->f('sms_delivery_active');
        $record_is_active = $db->f('is_active');
        if (!$record_is_active) {
            $message = array(
                'errors' => '',
                'success' => false,
                'message' => 'We are sorry but your order is not active',
            );
            echo json_encode($message);
            $que->process_queue();
            die;
        }
        if ($sms_is_active) {
            $message = array(
                'errors' => '',
                'success' => false,
                'message' => 'You are already signed up for text notifications',
            );
            echo json_encode($message);

            $que->process_queue();
            die;
        }
        if (!$sms_is_active) {
            $message = array(
                'errors' => '',
                'success' => true,
                'message' => 'Welcom to 3SI Notifications. For help on using this system, reply with HELP'
            );
            $sql = 'UPDATE va_trackers SET sms_delivery_active=1, sms_number=' . $phone . ' where order_id=' . $db->tosql($oid, INTEGER);
            $db->query($sql);
            echo json_encode($message);

            $message_to_send =  'Welcom to 3SI Notifications. For help on using this system, reply with HELP';
            $mts = array(
                'to' => $phone,
                'from' => SMS_NUMBER_10DIGIT,
                'message' => $message_to_send,
                'status' => 200,
                'has_been_sent' => 0,
                'signature' => "hook_7842fab920254ca0b27391725d585906"
            );
            $dbl->append_var($mts);
            $que->enqueue($mts);
            $que->process_queue();
            die;
        }
    } else {
        $message = array(
            'errors' => '',
            'success' => false,
            'message' => 'Record not found!'
        );
        echo json_encode($message);
        $que->process_queue();
        die;
    }


    echo json_encode($message);
    die;
}
