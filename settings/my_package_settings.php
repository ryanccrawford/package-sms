<?php
/* 
 * Settings
 */

//SET SOME VARS
$state = "FL";
$is_admin_path = true;
define ("DELIVERED_STATUS",28);
define ("PROMO_TABLE", "ts_orders_after_ship");
define ("COUPON_TABLE", "va_coupons");
define ("ORDERS_TABLE", "va_orders");
$status_error = "";
$site_root = $_SERVER['DOCUMENT_ROOT'];
$site_host = 'https://' . $_SERVER['HTTP_HOST'] ;

//Required Files
require_once ( "sms_sender/api_keys.php");
require_once ( "../vendor/easypost/easypost-php/lib/easypost.php");
require_once ( "../includes/common.php");
require_once ( "../includes/navigator.php");
require_once ( "../includes/record.php");
require_once ( "../includes/products_functions.php");
require_once ( "../includes/items_properties.php");
require_once ( "../includes/order_items.php");
require_once ( "debug_log/debuglog.php"); 
require_once ( "sms_sender/sendSms.php");
require_once ( "sms_sender/smsQueue.php");

//GLOBALS FROM VIART FRAMEWORK
global $debug_buffer;
global $db;
 
//LOAD EASY POST API LIB 
\EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);

//DEFINE CONST
define ('INSTALL_KEY', getenv( 'INSTALL_KEY' ) );
define ('IMAGES_LOCATION', 'assets/images/');
define ('ICONS_LOCATION', 'assets/images/shipping_icons/');
define ('LOGO_IMAGE', $site_host . '/package_sms/assets/images/shippingLogo.png');
define ('EMAIL_LOGO_IMAGE', $site_host . 'assets//images/myTheme/logo-fit.png');
define ('NOTIFY_ICON', 'assets/images/notifications.png');
define ('PACKAGE_ICON', 'assets/images/package.png');
define ('TRACKIT_BUTTON_ICON', 'assets/images/trackitbutton.png');
define ('PRODUCTS_LINK_HREF', $site_host  . '/products_list.php');
define ('PRODUCTS_LINK_ICON', 'fas fa-broadcast-tower');
define ('KNOWLEDGEBASE_LINK_HREF', $site_host . '/knowledge_base');
define ('KNOWLEDGEBASE_LINK_ICON', 'far fa-question-circle');
define ('SUPPORT_LINK_HREF', $site_host  . '/support.php');
define ('SUPPORT_LINK_ICON', 'fas fa-phone-square');
define ('USERS_LINK_HREF', $site_host  . '/user_login.php');
define ('USERS_LINK_ICON', 'fas fa-user-circle');
define ('SMS_NUMBER_10DIGIT', getenv( 'SMS_NUMBER_10DIGIT_' . $state ) );