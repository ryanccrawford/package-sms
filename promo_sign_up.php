<?php

require_once('settings/my_package_settings.php');
require_once('settings/my_coupon_settings.php');

$o = $_GET['orderid'];
$oid = filter_var($o, FILTER_SANITIZE_STRING);

$dbl_promo->append_var($oid, " ORDER ID ");

if (!isset($oid)) {
    $errors = array(
        "errors" => "The Order Id was empty",

    );
    echo json_encode($errors);
    die;
}
if ($oid > !0) {
    $errors = array(
        "errors" => "The Order Id was empty",

    );
    echo json_encode($errors);
    die;
}

$email = get_email_from_order($oid);

$already_got_promo = check_promo($email);

if ($already_got_promo) {
    $errors = array(
        "errors" => "You Have Already Signed UP!",

    );
    echo json_encode($errors);
    die;
}

$coupon_code = sign_up_now($oid, $email);

if (!strlen($coupon_code)) {
    $errors = array(
        "errors" => "You Have Already Signed UP!",

    );
    echo json_encode($errors);
    die;
}

$message = array(
    'errors' => '',
    'success' => true,
    'message' => 'You Coupon Code is ' . $coupon_code . '. Thanks!',
);

echo json_encode($message);
die;
