<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace sendSms;

/**
 * Description of smsQueue
 *
 * @author ryan
 */
class smsQueue
{

    protected  $limit = 5;
    protected  $queue = null;
    protected  $time_created;
    public $d;
    private $table = "va_sms_message_queue";


    //sms_id, to, from, sent, message, recv, created, status, has_been_sent, signature
    public function __construct()
    {
        //   global $db;
        $this->d = new \debuglog\logger("queLog.log");
    }


    private function insert_msg_into_db($message)
    {
        global $db;
        $this->d->append_var($message, " message- ");
        $d = "(`to`, `from`, `message`, `status`, `has_been_sent`, `signature`)";

        $d .= ' values ("' . $message['to'] . '", "' . $message['from'] . '", "' . $message['message'] . '", ' .  $message['status'] . ', ' . $message['has_been_sent'] . ', "' . $message['signature'] . '") ';
        $sql = "insert Into " . $this->table . " " . $d . ";";
        $this->d->append($sql);
        return $db->query($sql);
    }





    public function mark_message_sent($mes_id)
    {
        global $db;
        $sql = "update " . $this->table . " set has_been_sent = 1 where sms_id=" . $mes_id;
        $db->query($sql);

        if ($db->next_record()) {
            return true;
        }
        return false;
    }


    private function get_unsent_mess()
    {
        global $db;
        $sql = "select * from " . $this->table . " where has_been_sent = 0 order by sms_id desc limit " . $this->limit . ";";
        $db->query($sql);
        unset($this->queue);
        $this->queue = array();
        while ($db->next_record()) {
            $message = array(
                'sms_id' => $db->f('sms_id'),
                'to' => $db->f('to'),
                'from' => $db->f('from'),
                'sent' => $db->f('sent'),
                'message' => $db->f('message'),
                'recv' => $db->f('recv'),
                'created' => $db->f('created'),
                'status' => $db->f('status'),
                'has_been_sent' => $db->f('has_been_sent'),
                'signature' => $db->f('signature')
            );
            $this->queue[$db->f('sms_id')] = $message;
        }
        if (count($this->queue) > 0) {
            return true;
        }
        return false;
    }


    public function enqueue($message)
    {

        return $this->insert_msg_into_db($message);
    }

    public function process_queue($limit = 5)
    {

        $this->queue = array();
        $this->limit = $this->getLimit($limit);
        $this->time_created = date("Y-m-d H:i:s");;
        $this->d->append_var($this, " object dump  ");
        $this->get_unsent_mess();

        $niq = $this->size();
        $this->d->append_var($niq, " number of messages ");
        //if($niq == 0){die;}
        while ($this->size()) {
            $m = $this->dequeue();
            $this->d->append_var($m, " database message ");
            if ($m != 0) {
                $sms = new \sendSms\sendSms();
                $this->d->append_var($m['to'], " number to send ");
                $this->d->append_var($m['message'], " Message ");
                $sms->send($m['to'], $m['message']);
                sleep(1);
                $id = $m['sms_id'];

                $this->mark_message_sent($id);
            }
        }
    }



    public function dequeue()
    {
        if (!$this->isEmpty()) {
            $m = array_pop($this->queue);
            $this->d->append_var($m, " dequeuing ");
            return $m;
        }
        return false;
    }

    public function isEmpty()
    {
        if (count($this->queue) <= 0) {
            return true;
        }
        return false;
    }

    public function size()
    {
        return count($this->queue);
    }

    public function getType()
    {
        return gettype($this->queue);
    }

    private function getLimit($limit)
    {

        if (!((gettype($limit) == 'integer') && $limit > 0)) {
            $limit = -1;
        }
        return $limit;
    }
}
