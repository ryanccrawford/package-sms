<?php

namespace sendSms;

/**
 * Description of sendSms
 *
 * @author ryan
 */
class sendSms extends \debuglog\logger
{

    private $endpoint = "https://sms.telnyx.com/messages";
    private $num;
    private $mes;
    private $auth;


    public function __construct($logFileName = 'Sendsms.log')
    {
        parent::__construct($logFileName);
        $this->append(" Send SMS object Created \r\n");
    }

    public function send($number, $message)
    {
        if (strlen($number) == 10) {
            $this->num = "+1" . $number;
        }
        $this->mes = $message;
        $this->auth = new smsAuth();
        $auth = $this->auth;
        $request = new smsRequest($auth);
        $this->append_var($request, "\r\n SMS Request - ");
        $response = $this->get_response($request);
        $this->append_var($response, "\r\n SMS Response - ");
        return $response;
    }

    private function get_response(smsRequest $request)
    {
        $this->append_var($request, " get_response Request: ");
        $json = $request->createRequest($this->num, $this->mes);
        $content_type =  "Content-Type: application/json";
        $a = $request->get_curl_auth();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $content_type,
            $a,
        ));
        $aa = curl_exec($ch);
        $f = print_r($aa, true);
        $this->append_var($f);
        return $this->decode_response($aa);
    }

    private function decode_response($re)
    {
        $d = json_decode($re);
        if (isset($d)) {
            return $d;
        }
        if (strpos($re, "{") < 2) {
            return $re;
        }

        return '{error: "The response can not be decoded"}';
    }
}

class smsRequest extends \debuglog\logger
{

    public $smsAuth;

    public function __construct(smsAuth $auth, $logFileName = 'smsRequest.log')
    {
        parent::__construct($logFileName);

        $this->smsAuth = clone $auth;
    }
    public  function get_curl_auth()
    {
        $curlauth = $this->smsAuth->createSmsAuth();
        $this->append_var($curlauth, " Auth from get_curl_auth - ");
        return $curlauth;
    }
    public function createRequest($num, $mess)
    {


        if (strlen($num) == 12) {

            $from = $this->smsAuth->get_from();

            $requestreturn = '{"from": "' . $from . '","to": "' . $num . '", "body": "' . $mess . '"}';

            return $requestreturn;
        } else {

            return false;
        }
    }
}

class smsAuth extends \debuglog\logger
{

    protected $from;
    protected $secert;



    public function __construct($fromnumber = "", $api_secret = SMS_SECRET, $logfile = "smsAuth.log")
    {
        parent::__construct($logfile);
        if ($fromnumber == "") {
            $fromnumber = "+1" . SMS_NUMBER_10DIGIT;
        }
        $this->secert = $api_secret;
        $this->from = $fromnumber;
    }
    public function get_from()
    {

        return $this->from;
    }
    public function get_secert()
    {

        return $this->secert;
    }
    public function createSmsAuth()
    {
        return "x-profile-secret: " . $this->get_secert();
    }
}

class smsResponse extends \debuglog\logger
{

    public $success;
    public $status;
    public $message;

    public function __construct($obj, $logfile = "sms_response.log")
    {
        parent::__construct($logfile);
        $response = "";
        try {
            $response = json_decode($obj);
            if (strlen($response)) {
                $this->success = true;
                $this->status = 200;
                $this->message = $obj;
                return $response;
            }
        } catch (Exception $e) {
            $this->success = false;
            $array = json_decode($e);
            $this->status = $array['status'];
            $this->message = $array['message'];
            return $e;
        }
    }
}

class smsMesage
{

    static protected  $id = 0;
    public $mess_to;
    public $mess_from;
    public $mess_signature;
    public $mess_body;
    public $mess_created;
    public $mess_sent;
    public $mess_recv;
    public $mess_status;


    public function __construct($from, $to, $body, $signature, $created, $sent, $recv, $status)
    {
        $this->mess_to = $to;
        $this->mess_from = $from;
        $this->mess_signature = $signature;
        $this->mess_body = $body;
        $this->mess_created = $created;
        $this->mess_sent = $sent;
        $this->mess_recv = $recv;
        $this->mess_status = $status;
        $this->id++;
    }
}
