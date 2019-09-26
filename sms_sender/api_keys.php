<?php

$dotenv = Dotenv\Dotenv::create("../" . __DIR__);

$dotenv->load();

$state = "FL";

define ( 'SMS_SECRET', getenv( 'SMS_SECRET_' . $state ) );

define ('IP_ADDRESS', getenv( 'IP_ADDRESS_' . $state ) );

define ('WEB_HOOK_SECURITY_KEY', getenv( 'WEB_HOOK_SECURITY_KEY' ) );

define ('EASY_POST_KEY_TEST', getenv( 'EASY_POST_KEY_ID_' . $state ) );

define ('EASY_HOOK_TEST_ID', getenv( 'EASY_HOOK_TEST_ID_' . $state ) );
