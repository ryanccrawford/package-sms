<?php

$dbl_promo = new \debuglog\logger('promo_sign_up.log');

function sign_up_now($oid, $email)
{
    global $db, $dbl_promo;
    $code = generate_coupon_code($oid, $email);
    $dbl_promo->append(' INSIDE sign_up_now ');
    $dbl_promo->append_var($code);
    $dbl_promo->append_var($email);
    $dbl_promo->append_var($oid);
    if (!strlen($code)) {
        return false;
    }
    $fields = init_coupon_fields();
    $dbl_promo->append_var($fields);
    return create_coupon($code, $fields, $email, $oid);
}

function create_coupon($code, $fields, $email, $order_id)
{
    global $db;
    global $dbl_promo;
    //Check to see if signed up
    $sql = "SELECT email FROM " . ts_orders_after_ship . " WHERE email='" . $email . "';";
    $dbl_promo->append($sql);
    $db->query($sql);
    if ($db->next_record()) {
        return false;
    }

    //create a time stamp for creating coupon
    $todaysDate = date("Y-m-d H:i:s");
    //create a date of expire
    $addYear = strtotime('+1 year', strtotime($todaysDate));
    $YearFromNow = date("Y-m-d H:i:s", $addYear);

    //create coupon
    $fields['coupon_code'] =  "'" . $code . "'";
    $fields['coupon_title'] = "'Order Promo 5% Off'";
    $fields['is_active'] = 1;
    $fields['is_auto_apply'] = 0;
    $fields['apply_order'] = 1;
    $fields['discount_type'] = 1;
    $fields['discount_amount'] = 5.00;
    $fields['discount_quantity'] = NULL;
    $fields['discount_tax_free'] = 0;
    $fields['free_postage'] = 0;
    $fields['coupon_tax_free'] = 0;
    $fields['order_tax_free'] = 0;
    $fields['items_all'] = 1;
    $fields['items_ids'] = NULL;
    $fields['items_types_ids'] = NULL;
    $fields['cart_items_all'] = 1;
    $fields['cart_items_ids'] = NULL;
    $fields['cart_items_types_ids'] = NULL;
    $fields['sites_all'] = 1;
    $fields['users_all'] = 1;
    $fields['users_ids'] = NULL;
    $fields['users_types_ids'] = NULL;
    $fields['users_use_limit'] = 1;
    $fields['min_quantity'] = NULL;
    $fields['max_quantity'] = NULL;
    $fields['minimum_amount'] = NULL;
    $fields['maximum_amount'] = NULL;
    $fields['orders_period'] = NULL;
    $fields['orders_interval'] = NULL;
    $fields['orders_min_goods'] = NULL;
    $fields['orders_max_goods'] = NULL;
    $fields['friends_discount_type'] = 0;
    $fields['friends_all'] = 1;
    $fields['friends_ids'] = NULL;
    $fields['friends_types_ids'] = NULL;
    $fields['friends_period'] = NULL;
    $fields['friends_interval'] = NULL;
    $fields['friends_min_goods'] = NULL;
    $fields['friends_max_goods'] = NULL;
    $fields['start_date'] = "'" . $todaysDate . "'";
    $fields['expiry_date'] = "'" . $YearFromNow . "'";
    $fields['is_exclusive'] = 1;
    $fields['quantity_limit'] = 1;
    $fields['coupon_uses'] = 0;
    $fields['admin_id_added_by'] = 2;
    $fields['admin_id_modified_by'] = 0;
    $fields['date_added'] = "'" . $todaysDate . "'";
    $fields['date_modified'] = NULL;
    $fields['free_postage_all'] = 1;
    $fields['free_postage_ids'] = NULL;
    $fields['order_min_goods_cost'] = NULL;
    $fields['order_max_goods_cost'] = NULL;
    $fields['order_min_weight'] = NULL;
    $fields['order_max_weight'] = NULL;
    $fields['min_cart_quantity'] = NULL;
    $fields['max_cart_quantity'] = NULL;
    $fields['min_cart_cost'] = NULL;
    $fields['max_cart_cost'] = NULL;
    $fields['items_categories_ids'] = NULL;
    $fields['use_friends_code'] = 0;
    $fields['orders_min_quantity'] = NULL;
    $fields['orders_max_quantity'] = NULL;
    $fields['orders_items_type'] = 1;
    $fields['orders_items_ids'] = NULL;
    $fields['orders_types_ids'] = NULL;
    $fields['items_rule'] = 1;

    //add coupon to data base
    $sql = "INSERT INTO " . COUPON_TABLE . " ";
    $col_names = "";
    $col_val = "";
    foreach ($fields as $fieldname => $value) {
        if ($value == NULL) {
            continue;
        }

        $col_names .= $fieldname . ", ";
        $col_val .= $value . ", ";
    }

    $sql .= make_post_insert($col_names, $col_val);
    $dbl_promo->append($sql);
    //add coupon information to after ship table or exit if above query failed 
    if ($db->query($sql)) {
        //order_id, order_shipment_id, coupon_id, email, got_coupon, is_signed_up, date_signed_up, order_amount, order_shipping_amount
        //get order_shipment_id
        $order_shipment_id = get_order_shipping_id($order_id);
        $dbl_promo->append(" ORDER ID " . $order_shipment_id);
        if ($order_shipment_id < 1) {
            return false;
        }

        //get the coupon id from data that the above created     
        $coupon_id = get_coupon_id($code);
        if (!$coupon_id) {
            return false;
        }

        $cn = "(order_id, order_shipment_id, coupon_id, email, got_coupon, is_signed_up, date_signed_up, order_amount, order_shipping_amount)";
        $cv = "($order_id, $order_shipment_id, $coupon_id, '$email', 1, 1, '$todaysDate' , 0.00, 0.00)";
        $sql = "INSERT INTO " . PROMO_TABLE . " " . $cn . " VALUES " . $cv . ";";
        $dbl_promo->append($sql);
        if ($db->query($sql)) {
            return $code;
        }
    }
    return false;
}
function get_coupon_id($code)
{
    global $db;
    $sql = "SELECT coupon_id FROM " . COUPON_TABLE . " WHERE coupon_code='" . $code . "';";
    $db->query($sql);
    if ($db->next_record()) {
        return $db->f('coupon_id');
    } else {
        return false;
    }
}
function make_post_insert($col, $val)
{
    $new_col = trim_last_comma($col);
    $new_val = trim_last_comma($val);
    return "(" . $new_col . ") VALUES (" . $new_val . ");";
}

function trim_last_comma($var)
{
    $a = trim($var, ", ");

    return $a;
}

function init_coupon_fields()
{
    global $dbl_promo;
    $feild_names = "coupon_id, order_id, order_item_id, coupon_code, coupon_title, is_active, is_auto_apply, apply_order, discount_type, discount_amount, discount_quantity, discount_tax_free, free_postage, coupon_tax_free, order_tax_free, items_all, items_ids, items_types_ids, cart_items_all, cart_items_ids, cart_items_types_ids, sites_all, users_all, users_ids, users_types_ids, users_use_limit, min_quantity, max_quantity, minimum_amount, maximum_amount, orders_period, orders_interval, orders_min_goods, orders_max_goods, friends_discount_type, friends_all, friends_ids, friends_types_ids, friends_period, friends_interval, friends_min_goods, friends_max_goods, start_date, expiry_date, is_exclusive, quantity_limit, coupon_uses, admin_id_added_by, admin_id_modified_by, date_added, date_modified, free_postage_all, free_postage_ids, order_min_goods_cost, order_max_goods_cost, order_min_weight, order_max_weight, min_cart_quantity, max_cart_quantity, min_cart_cost, max_cart_cost, items_categories_ids, use_friends_code, orders_min_quantity, orders_max_quantity, orders_items_type, orders_items_ids, orders_types_ids, items_rule";
    $feild_values = array();

    $feild_array = explode(", ", $feild_names);
    $dbl_promo->append(' INSIDE init_coupon_fields');
    $dbl_promo->append_var($feild_array);
    foreach ($feild_array as $indx => $name) {

        $feild_values[$name] = null;
    }
    $dbl_promo->append_var($feild_values);
    return $feild_values;
}

function generate_coupon_code($oid, $email)
{
    global $db;
    $code = "";
    do {
        $time_stamp = strtoupper(va_timestamp());
        $trycode = $time_stamp . $oid . $email;
        $trial_code = strtoupper(substr(md5($trycode), 0, 8));
        $sql = "SELECT coupon_code FROM va_coupons WHERE coupon_code='" . $trial_code . "';";
        $db->query($sql);
        $code = $trial_code;
    } while ($db->next_record());

    return $code;
}

function get_email_from_order($oid)
{
    global $db;

    $sql = "SELECT email FROM va_orders where order_id=" . $oid  . ";";
    $db->query($sql);
    if ($db->next_record()) {
        return $db->f('email');
    }
}

function check_promo($email)
{
    global $db;

    $sql = "SELECT email FROM " . PROMO_TABLE . " WHERE email='" . $email . "';";
    $db->query($sql);
    if ($db->next_record()) {
        return true;
    }

    return false;
}

function get_order_shipping_id($order_id)
{
    global $db;
    $sql = "Select order_shipping_id from va_orders_shipments where order_id=" . $db->tosql($order_id, INTEGER) . ";";
    $db->query($sql);
    if ($db->next_record()) {
        return $db->f('order_shipping_id');
    }
    return false;
}
