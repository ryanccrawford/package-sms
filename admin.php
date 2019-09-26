<?php

require_once('settings/my_package_settings.php');
global $db;
$k = $_GET['key'];
if(!isset($k)){die;}
if(isset($k) && check_key($k)){
    
    



if(isset($_GET['create_data'])){
    createData();
}

if(isset($_GET['drop_data'])){
    dropDate();
    
}





}

echo "<br><br><br>GUI ADMIN COMMING SOON!";

function check_key($key){
  
  if(!$key === INSTALL_KEY){
      echo "<BR>NOT ALLOWED";
      die;
      
  }
    return true;
}

function createData(){
    global $db;
echo "<br>Creating Data Tables<br>";
$sql = file_get_contents('data/create_data.sql');
$sql2 = file_get_contents('data/create_data2.sql');

if(strlen($sql) && strlen($sql2)){   
  echo $db->query($sql);
  sleep(1);
  echo $db->query($sql2);
  echo "<br>Data Tables Created";

}else{
    echo "Missing Data File.";
    
}
  return;
    
}

function dropDate(){
   global $db;
echo "<br>Dropping Data Tables<br>";
$sql = file_get_contents('data/drop_data.sql');
$sql2 = file_get_contents('data/drop_data_2.sql');
if(strlen($sql) && strlen($sql2)){
    
  $db->query($sql);
  sleep(1);
  $db->query($sql2);
  echo "<br>Data Tables Removed!";

  
    
}else{
    echo "<br>Missing Data File.";
    
}
  return;

  
    
}