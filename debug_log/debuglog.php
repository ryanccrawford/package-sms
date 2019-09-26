<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace debuglog;

class logger{
    
    protected $log_file;
    
    public function __construct($fileName = "debug_log.log"){
        
        $this->log_file = $fileName;
        
    }
    
    public function append($string){
        
        // Open the file to get existing content
        $current = $this->read_all();
        // Append a new person to the file
        $current .= $string . "\r\n";
        // Write the contents back to the file
        $this->write_all($current);
    }
     public function append_var($var, $name = " VAR "){
        $d = date('Y-m-d H:i:s') . " - ";
        // Open the file to get existing content
        $current = "\r\n " . $this->read_all();
        // Append a new person to the file
        $current .= $d . $name . var_export($var, true) . "\r\n";
        // Write the contents back to the file
        $this->write_all($current);
    }
    
    public function read_all(){
        
        return file_get_contents($this->log_file);
        
    }
    
    public function write_all($content){
        file_put_contents($this->log_file, $content);
        
    }
    
}