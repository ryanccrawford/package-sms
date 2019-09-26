<?php

require_once('settings/my_package_settings.php');
$o = get_param("order_id");
$order_id  = filter_var($o, FILTER_SANITIZE_STRING);

if (!$order_id) {
    die;
}


$t = new VA_Template("templates/");
$t->set_file("main", "my_package.html");
$t->set_var("", "my_package.php");
$t->set_var("", LOGO_IMAGE);
$t->set_var("", NOTIFY_ICON);
$t->set_var("", PRODUCTS_LINK_HREF);
$t->set_var("", PRODUCTS_LINK_ICON);
$t->set_var("", KNOWLEDGEBASE_LINK_HREF);
$t->set_var("", KNOWLEDGEBASE_LINK_ICON);
$t->set_var("", SUPPORT_LINK_HREF);
$t->set_var("", SUPPORT_LINK_ICON);
$t->set_var("", USERS_LINK_HREF);
$t->set_var("", USERS_LINK_ICON);


$sqll = "SELECT oi.item_id, i.item_name, i.manufacturer_code, i.small_image,i.friendly_url FROM va_orders_items oi join va_items i on i.item_id=oi.item_id where order_id=" . $dbi->tosql($order_id, INTEGER);
$dbi->query($sqll);
$order_items_ids = array();
$html_r = "";
while ($dbi->next_record()) {
    $order_items_ids[] = array(
        "id" => "" . $dbi->f('item_id') . "",
        "name" => "" . $dbi->f('item_name') . "",
        "code" => "" . $dbi->f('manufacturer_code') . "",
        "img" => "" . $dbi->f('small_image') . "",
        "friendly" => "" . $dbi->f('friendly_url') . "",
    );
}



$t->pparse("main");
