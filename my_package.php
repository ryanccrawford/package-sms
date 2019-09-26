<?php
      
        require_once('settings/my_package_settings.php');
		
        
		$o = get_param("order_id");
			$order_id  = filter_var( $o, FILTER_SANITIZE_STRING);
			if(!$order_id){
				die;
			}
		
       
	   $shipping_icons = array(
            'ups' => ICONS_LOCATION . 'ups_icon.svg',
            'fedex' => ICONS_LOCATION . 'fedex_icon.svg',
		    'fedexnailview' => ICONS_LOCATION . 'fedex_icon.svg',
		    'fedexsamedaycity' => ICONS_LOCATION . 'fedex_icon.svg',
		    'fedexuk' => ICONS_LOCATION . 'fedex_icon.svg',
		    'fedexsmartpost' => ICONS_LOCATION . 'fedex_icon.svg',
            'usps' => ICONS_LOCATION . 'usps_icon.svg',
            'rlcarriers' => ICONS_LOCATION . 'rnl_icon.png',
            'dhlexpress'=> ICONS_LOCATION . 'dhl_icon.svg',
            'dhlfreight'=> ICONS_LOCATION .'dhl_icon.svg',
            'dhlglobalMail'=> ICONS_LOCATION .'dhl_icon.svg',
            'dhlglobalmailInternational'=> ICONS_LOCATION .'dhl_icon.svg',
            'canadapost'=> ICONS_LOCATION .'canada_post_icon.svg',
            'abf' => ICONS_LOCATION . 'abf_icon.svg',
        );
       
        
        
   
        $dbi = new VA_SQL();
	$dbi->DBType      = $db_type;
	$dbi->DBDatabase  = $db_name;
	$dbi->DBUser      = $db_user;
	$dbi->DBPassword  = $db_password;
	$dbi->DBHost      = $db_host;
	$dbi->DBPort      = $db_port;
	$dbi->DBPersistent= $db_persistent;
	
    
        $t = new VA_Template("templates/");
		$t->set_file("main", "my_package2.html");
        
		$t->set_var("my_package_href", "my_package.php");
        $t->set_var("my_package_header_logo", LOGO_IMAGE);
        $t->set_var("notify_icon", NOTIFY_ICON);
        $t->set_var("menu_link1", PRODUCTS_LINK_HREF);
        $t->set_var("menu_link_icon1",PRODUCTS_LINK_ICON);
        $t->set_var("menu_link2", KNOWLEDGEBASE_LINK_HREF);
        $t->set_var("menu_link_icon2", KNOWLEDGEBASE_LINK_ICON);
        $t->set_var("menu_link3", SUPPORT_LINK_HREF);
        $t->set_var("menu_link_icon3", SUPPORT_LINK_ICON);
        $t->set_var("menu_link4", USERS_LINK_HREF);
        $t->set_var("menu_link_icon4", USERS_LINK_ICON);
        $t->set_var("site_host", $site_host);
	
        
       
        
        $status_icons = array(
                "unknown" => "fas fa-stopwatch",
                "pre_transit" => "fas fa-stopwatch",
                "in_transit" => "fas fa-shipping-fast",
                "out_for_delivery" => "fas fa-door-closed",
                "delivered" => "fas fa-box",
                "available_for_pickup" => "fas fa-exclamation-triangle",
                "return_to_sender" => "fas fa-exclamation-triangle",
                "failure" => "fas fa-exclamation-circle",
                "cancelled" => "fas fa-ban",
                "error" => "fas fa-exclamation-circle",
        );
        
    $icon_small = " fa-sm";
    $icon_2x = " fa-2x";
    $brown = " ";
    $black = " ";
    $tracking_number = getOrderShippingTracking($order_id);
    $raw_tracking = getOrderShippingTracking($order_id, false);
    $sms_active = is_sms_active($order_id);
    $ep_tracker =  make_tracker($order_id, $tracking_number);
    $pre_carrier_message = "";
    $section_one_card_size = null;

    if(isset($ep_tracker["error"])){
           
        $pre_carrier_message = "Your shipment is ready to be picked up by the carrier.";

    }elseif(!isset($ep_tracker)){

        $pre_carrier_message = "Your shipment is ready to be picked up by the carrier.";

    }
    
    $t->set_var("order_id", $order_id);
    $t->set_var("order_number", $order_id);
  

    
    
	if(strlen($pre_carrier_message)){
		
		$eta_month = "Awaiting Scan";
		$t->set_var("status_icon", "fas fa-stopwatch" . $icon_2x);
		$current_status = $pre_carrier_message;
		
	}  
	 
	 
	if(!strlen($pre_carrier_message)){
                        
        
                
            $tracker_id = get_tracker_id($order_id);

            $eta = get_eta($ep_tracker);
       

            if($eta){
				$section_one_card_size = " medium";
				$eta_day = $eta['day'] != null ? $eta['day'] : "";
				$eta_month = $eta['month'] != null ? $eta['month'] : "";
                $eta_dow = $eta['dow'] != null ? $eta['dow'] : "";
                
                $t->set_var("eta_dow", $eta_dow);
                $t->set_var("eta_month", $eta_month);
                $t->set_var("dd", $eta_day);
                   
            }else{
				
                $eta_month = "DATE: N/A";
                
                $t->set_var("eta_dow"," ");
                $t->set_var("eta_month",$eta_month);
                $t->set_var("dd"," ");
			}
                
        
        if(strlen($ep_tracker->carrier)){
            $t->set_var("shipping_icon",$shipping_icons[strtolower($ep_tracker->carrier)]);
        }
        
        if($ep_tracker->status != ""){
            $t->set_var("shipping_status_icon", $status_icons[$ep_tracker->status] . $icon_2x);
          
            $t->set_var("current_status", var_to_word($ep_tracker->status));
          
            $tool_tip = "";
            $tool_script = "";
                      
                    
                    switch  ($ep_tracker->status){
                        case "unknown":
                           $tool_tip = "We are awaiting the next tracking event.";
                        break;
                        case "pre_transit":
                             $current_status  = "Your order is awaiting pickup.";
                         $tool_tip = "Your order is awaiting pickup.";
                        break;
                        case "in_transit":
                         $tool_tip = "Your package is moving to its next destination";
                        break;
                        case "out_for_delivery":
                         $tool_tip = "Your package is arriving today!";
                         break;
				case "delivered":
                         $tool_tip = "Your package was delivered.";
                         break;
                        default :
                         $tool_tip = "No additional help at this time.";
                        break;
                    }
                   
			  
                     
                      $t->set_var("tool_tip", $tool_tip);
                      $tool_script = '$(document).ready(function(){$(\'.tooltipped\').tooltip();});';
                      $t->set_var("status_icon_script", $tool_script);
                      $t->sparse('shipping_status_icon',false);
                      $t->sparse("shipping_status", false);
                      $t->sparse("icon_script" ,false);
                     
		
                
        }else{
            $t->set_var("eta_dow"," ");
            $t->set_var("eta_month","");
            $t->set_var("dd","");
            $tool_script = '$(document).ready(function(){$(\'.tooltipped\').tooltip();});';        
            $t->set_var("status_icon_script", $tool_script);
            $t->set_var("current_status", "Package Awaiting Pickup");
            $t->sparse("shipping_status", false);
            $t->sparse("icon_script" ,false);
        }

         if(strlen($ep_tracker->tracking_code)){
          $t->set_var("tracking_number",$raw_tracking);
         }
         
          $tracker_details = array_reverse($ep_tracker->tracking_details);
        if(count($tracker_details) > 0){
            $acord_num = 2;
        
	      
      
            $html_l = ' <ul class="collapsible">';
         foreach ($tracker_details as $details  )   {
                $date = strtotime($details->datetime);
                $carrier_code = isset($details->carrier_code) ? $details->carrier_code : false;
                $da_st = date('m/d', $date);
                $icon = $status_icons[$details->status];
                $l_status = $details->message; 
                
                $location = $details->tracking_location;
                $l_city =  isset($location->city) ? $location->city : false; 
                $l_state = isset($location->state) ? $location->state : false; 
               $body = "";
			   
                if($carrier_code == "X" && $details->source == "UPS"){
					
					$body .= '<table border="0">
							  <tr><td class="importantmessage">'.$l_status.'</td><td></td></tr>
							  </table>';
					$html_l .= '<li>
							  <div class="collapsible-header"><i class="'.$icon.' fa-sm" style="font-size:1.1rem"> </i>Important Message - Click here</div>
							  <div class="collapsible-body">'.$body.'</span></div>
							</li>';
			                    
                }else{
					  if(strlen($l_city)){

                                        $body .= '<table border="0">';
                                        $body .= '<tr><td>Location: '.$l_city.'</td><td>, '.$l_state.'</td></tr>';
                                        $body .= '</table>';
                                }else{
                                        $body .= '<table border="0">';
						    $body .= '<tr><td>No additional data.</td><td></td></tr>';
                                        $body .= '</table>';
                                }
					$html_l .= '<li>
							  <div class="collapsible-header"><i class="'.$icon.' fa-sm" style="font-size:1.1rem"> </i> '.$l_status.' - '.$da_st.'</div>
							  <div class="collapsible-body">'.$body.'</span></div>
							</li>';
					
                    }
        }
            
             $html_l .= ' </ul>';
            
                        $acord_num++;
         
                        $t->set_var("location_details",$html_l);
                        $t->sparse("location_history",false);
                       
                }else{
                    
                    $t->set_var("location_details","<div class='noshippinginfo'><p>Your order is awaiting pickup. Once scanned by the carrier, tracking information can take up to 12 hours to display. Please check back often.</p></div>");
                    $t->sparse("location_history",false);
			
		}
            $t->sparse("status_tab", false);
            $t->sparse("section_one", false);
        
        
   }else{
       
               $tracking_number = getOrderShippingTracking($order_id);
               
               if(strlen($tracking_number)){
                   
                   $t->set_var("tracking_number",$tracking_number);
               
                   
               }else{
                 
                $t->set_var("tracking_number","Awaiting Tracking Details");
               }
            
               $t->set_var("eta_dow"," ");
               $t->set_var("eta_month","Awaiting Package");
               $t->set_var("dd"," ");
               $t->set_var("location_details","<div class='noshippinginfo'><p>Your order is awaiting pickup. Once scanned by the carrier, tracking information can take up to 12 hours to display. Please check back often.</p></div>");
               $t->sparse("location_history",false);
               $t->sparse("status_tab", false);
             
               
        }
       
        if($ep_tracker->status !== "delivered"){
        
            if(isset($sms_active) && $sms_active){
            
                $t->sparse('notify_on',false);
           
            
            }else{

				$t->sparse("notify_setup",false);
             
			}
            
            $t->sparse("section_two",false);
               
            $t->sparse("not_delivered",false);
                        
            $t->sparse("section_one", false);
    
        }else{
                
            $t->sparse("delivered",false);
            
             
            if(!check_promo_exist($order_id)){
            
                $t->sparse('discount_section', false);
             
                $t->sparse("section_one", false);
        }
            
            
        
    }
   
			 
		
                $sqll = "SELECT oi.item_id, i.item_name, i.manufacturer_code, i.small_image,i.friendly_url FROM va_orders_items oi join va_items i on i.item_id=oi.item_id where order_id=" . $dbi->tosql($order_id, INTEGER);
                $dbi->query($sqll);
                $order_items_ids = array();
                $html_r = "";
       while($dbi->next_record()){
             $order_items_ids[] = array(
                 "id" => "".$dbi->f('item_id')."" ,
                 "name" => "".$dbi->f('item_name')."",
                 "code" => "".$dbi->f('manufacturer_code')."",
                 "img" => "".$dbi->f('small_image')."",
                 "friendly" => "".$dbi->f('friendly_url')."",
                 );
            
             
        }
             $item_ids_recom = "";
        if($order_items_ids){
           
            foreach ($order_items_ids as $id) {
             
			$iid = $id['id'];
			if ($iid) { 
				if (strlen($item_ids_recom)) { $item_ids_recom .= ",";	}
				$item_ids_recom .= intval($iid);
			}
                
           $url_r = $site_host  . '/' . 'reviews.php?item_id='.$id['id'];// . '&order_id=' . $order_id;
           
            $img = $site_host  . '/' . $id['img'];
           
            $link = $site_host  . '/' . $id['friendly'];
            
            $name = $id['name'];
            
            $mpn = $id['code'];
       $html_r .= '<tr class="reviewrow" valign="top"><td class="reviewimage center" align="center" style="width:20%">';
       $html_r .= '<a href="'.$url_r.'" target="blank"><img src="'.$img.'" border="0" /></a></td><td class="reviewtitle" style="width:100%;"><a href="'.$url_r.'" target="blank" style="font-size:1em;line-height: 0;">'.$name.'</a></td>';//</td><td class="reviewmpn"><b>'.$mpn.'</b>';
       $html_r .= '<td class="reviewaction center" align="center"><a href="'.$url_r.'" target="blank"><button class="reviewbutton" style="width:100%;overflow: hidden;text-overflow:ellipsis;">Rate Product</button></a></td></tr>';
    
          }
          $t->set_var("table_reviews", $html_r);
          $t->sparse("html_review_items",false);
     
		if (strlen($item_ids_recom)) {
			
                    $html_recom = create_recommended_products($item_ids_recom);
			
                        If(strlen($html_recom)){
                            $t->set_var("recommended_items_display", $html_recom);
                           $t->sparse("recommended_items",false);
						   $t->sparse('recommended_items_section',false);
                        }
		}
        }
		
        $t->sparse("menu_a",false);
	  //  $t->sparse("menu_b",false);
        $t->sparse("menu_c",false);
        $t->sparse("menu_d",false);
        
        $t->sparse('reviews_section',false);
       $t->set_var("current_status", $current_status); 
		//Tool Tips init
	       $tool_script = '$(document).ready(function(){$(\'.tooltipped\').tooltip();});';
	       $t->set_var("status_icon_script", $tool_script);
	       $t->sparse("icon_script" ,false);
         
		 $t->sparse('shipping_status_icon',false);
		 $t->sparse("shipping_status", false);
		 $t->sparse("location_history",false);
	       $t->sparse("status_tab", false);
		        if($ep_tracker->status !== "delivered"){
			if(isset($sms_active) && $sms_active){
				$t->sparse('notify_on',false);
           
			}else{

				$t->sparse("notify_setup",false);
             
			}
			$t->sparse("section_two",false);
				
				$t->set_var("eta_dow",$eta_dow);
				$t->set_var("eta_month",$eta_month);
				$t->set_var("dd",$eta_day);
                        $t->sparse("not_delivered",false);
                         
	}else{
                $t->sparse("delivered",false);
            
             if(!check_promo_exist($order_id)){
            $t->sparse('discount_section', false);
             
        }
            
            
        }
		$t->set_var("section_one_card_size",$section_one_card_size);
		$t->sparse('section_one',false);
		//parse and send html output
		$t->pparse("main");


function determine_carrier_from_tracking($tracking_number){
          
            $return = is_abf($tracking_number);
                if($return){
                return "abf";
            }

		  $return = is_rnl($tracking_number);
		  if($return){
			  return "rlcarriers";
		  }
		  $return = is_ups($tracking_number);
		   if($return){
			  return "ups";
		  }
		  $return = is_fedex($tracking_number);
		   if($return){
			  return "fedex";
		  }
		  $return = is_usps($tracking_number);
		   if($return){
			  return "usps";
		  }
		  return false;
}
	 
function is_usps($tracking_number){
		
		if(strlen($tracking_number) > 20 && is_numeric($tracking_number)){
			return true;
		}
		return false;
		
}
	  
function check_promo_exist($order_id){
      global $db;
     
      $email = get_email($order_id);
      if(!strlen($email)){
          return true;
      }
      $sql = "SELECT email FROM " . PROMO_TABLE . " WHERE email='$email'";
      $db->query($sql);
      if($db->next_record()){
            return true;
      }
     return false;
}
 
function get_email($order_id){
     global $db;
     $sql = "SELECT email FROM " . ORDERS_TABLE . " WHERE order_id=$order_id";
     $db->query($sql);
     if($db->next_record()){
         return $db->f('email');
         
     }
     return false;
}
 
function var_to_word($var){
            $new_var = "";
			if($var == "unknown" || $var == "pre_transit"){
				$new_var = "awaiting next event";
			}else{
					$new_var = str_replace("_", " ", $var);
			}
            return ucwords($new_var);
            
}
              
function get_eta(\EasyPost\Tracker $ep_tracker){
            
            $d = strtotime($ep_tracker->est_delivery_date);
           
            if($d){
               return array(
                'dow' => ''. date('l', $d) . '',
                'month' => ''. date('F', $d) . '',
                'day' => ''. date('d', $d) . '',  
            );
            }
            return false;
}
        
function is_sms_active($order_id){
               global $db;
        $sql = "SELECT sms_delivery_active FROM va_trackers WHERE order_id=" . $db->tosql($order_id, INTEGER). ";";        
       $db->query($sql);
      
       if($db->next_record()){
               return $db->f('sms_delivery_active') == 1 ? true : false;
        }
            
        return 0;
      
            
}
              
function getOrderShippingTracking($order_id, $format=true){
    global $db;
    
    $sql = "SELECT tracking_id FROM va_orders_shipments WHERE order_id=" . $db->tosql($order_id, INTEGER). " AND NOT ISNULL(tracking_id);";        
    $db->query($sql);

    if($db->next_record()){
       $tn = $db->f('tracking_id');
        if($format){
            return remove_spaces($tn);
        }else{
            return $tn;
        }
    }
    
    return false;
    
    
}

function remove_spaces($string){
    $spaces = array(" ","  ");
    return str_replace($spaces,"",$string);
    
    
}

		
function is_rnl($tracking_id){
    
    if(is_ups($tracking_id)){
        return false;
    }
    
    return startsWith("rl", strtolower($tracking_id));
   
    
}

function parse_rnl_tracking_number($tracking_id){
    $temp_track = strtolower($tracking_id);
    return trim(preg_replace("/rl/","",$temp_track));
}


function clean_tracking_number($tracking_id){
		return preg_replace('/[^a-zA-Z0-9]/', '', $tracking_id);
}
       
function is_fedex($tracking_num){
	    $re = '/(\b96\d{20}\b)|(\b\d{15}\b)|(\b\d{12}\b)/';

		if (preg_match($re, $tracking_num)){
			return true;
		}
        
        return false;
}
	
function is_ups($tracking_number){

    
    $re = '/\b(1Z ?[0-9A-Z]{3} ?[0-9A-Z]{3} ?[0-9A-Z]{2} ?[0-9A-Z]{4} ?[0-9A-Z]{3} ?[0-9A-Z]|[\dT]\d\d\d ?\d\d\d\d ?\d\d\d)\b/';
        
    if(preg_match($re, $tracking_number)){
            
        return true;
        
    }
        
    return false;
    
}
   
function make_tracker($order_id){
        
    global $db;
            
        
    \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
        
    $error_message = array("error"=>false,"message"=>"","script"=>"");
       
    try{
        
        $sqll = "SELECT id FROM va_trackers WHERE order_id=" . $db->tosql($order_id, INTEGER);
       
        $db->query($sqll);
       
        if($db->next_record()){
          
            return \EasyPost\Tracker::retrieve($db->f('id'));
       
        }
            
       $sql = "Select tracking_id, shipping_id from va_orders_shipments where order_id=" . $db->tosql($order_id, INTEGER). ";";        
       
       $db->query($sql);
        
       $tracker = null;
        
       $data = array(
       'shipping_id' => 0,
       'tracking_code' => "",
       );
	  
        
       if($db->next_record()){
        
        $ti = $db->f('tracking_id');

        $tracking_id = remove_spaces($ti);
            
        $tracker = null;
            
            
        if(is_abf($tracking_id)){
            
            $tracking_id = parse_abf_tracking_number($tracking_id);
            
            $tracker = \EasyPost\Tracker::create(array(
                "tracking_code" => $tracking_id,
                "carrier" => "ABF"
            ));
            
        }elseif(is_rnl($tracking_id)){
            $tracking_id = parse_rnl_tracking_number($tracking_id);
            
            $tracker = \EasyPost\Tracker::create(array(
                    "tracking_code" => $tracking_id,
				    "carrier" => "RLCarriers"
                    ));
				
		}elseif(is_fedex($tracking_id)){
            $t_i_fedex = remove_spaces($tracking_id);
            $tracker = \EasyPost\Tracker::create(array(
                        "tracking_code" => $t_i_fedex,
                "carrier" => "FedEx"
                 ));
        }else{
				$tracker = \EasyPost\Tracker::create(array(
                            "tracking_code" => $tracking_id
                    ));
				
			}
                
            if(is_null($tracker)){
                $error_message["error"] = true;
                $error_message["message"] = "Bad Tracking ID"; 
                return $error_message;
            }
				
			
                $data['shipping_id'] = $db->f('shipping_id');
                $data['tracking_code'] = '"'. $tracking_id .'"';
                $data['id'] = '"'.$tracker->id.'"';
                $data['carrier'] = '"'.$tracker->carrier.'"';
                $data['order_id'] = $order_id;
                $data['is_active'] = 1;
                $data['sms_delivery_active'] = 0;
               
        }else{
            $error_message["error"] = true;
           $error_message["message"] = $tracker->response; 
           return $error_message;
        }
       //echo "<br><br>" . print_r($data, true);
       if($data){
          $col = "";$vals="";
          foreach($data as $key => $value){
              $col .= $key.",";
              $vals .= $value . ",";
          }
         $col = trim($col,",");
         $vals = trim($vals,",");
           $sql = "INSERT INTO va_trackers (".$col.") values (".$vals.")";        
           $db->query($sql);
           return $tracker;
       }
           return $error_message;   
       }catch(Exception $e){
           $error["error"] = true; 
            $error_message["message"] = $e->getMessage();
            return $error_message;
       }
       
        return $error_message;
 }
	

 function get_ep_tracker($id){
     
            \EasyPost\EasyPost::setApiKey(EASY_POST_KEY_TEST);
            return \EasyPost\Tracker::retrieve($id);
    
}

function is_abf($tracking_id){
    
    return startsWith(strtolower($tracking_id),"abf");
}

function parse_abf_tracking_number($tracking_id){
     $temp_track = strtolower($tracking_id);
     return trim(preg_replace("/abf/","",$temp_track));
}

function is_tracker_created($order_id){
  
    global $db;
     
    $sql = "SELECT tracker_id FROM va_trackers WHERE order_id=" . $db->tosql($order_id, INTEGER). ";";        
       
    $db->query($sql);
      
       
    if($db->next_record()){
          
        return true;
        
    }
            
        
    return false;
            
  
}
             
function get_tracker_id($order_id){
      
    global $db;
      
    $sql = "SELECT id FROM va_trackers WHERE order_id=" . $db->tosql($order_id, INTEGER). ";";        
       
    $db->query($sql);
      
       
    if($db->next_record()){
               
        return $db->f('id');
        
    }
            
        
    return false;
      
  
}
  

function startsWith ($string, $startString) 
{ 
    $len = strlen($startString); 
    return (substr($string, 0, $len) === $startString); 
} 


function create_review_section(){
    return false;
      
      
  
}

function set_item_tracking_id($order_item_id, $tracking_id){
      
    global $db;
      
      
    if(isset($order_item_id) && isset($tracking_id)){
          
      
        $sql = "UPDATE va_orders_shipments SET shipping_tracking_id='" . $tracking_id . "' WHERE order_item_id=" . $db->tosql($order_item_id, INTEGER). ";";        
      
        $db->query($sql);
      
          
        return true;
      
      
    }
      
    return false;
  
}
  
function get_order_shipping_id($order_id){
      
    global $db;
      
    $sql = "Select order_shipping_id from va_orders_shipments where order_id=" . $db->tosql($order_id, INTEGER). ";";        
      
    $db->query($sql);
      
    if($db->next_record()){
       
        return $db->f('order_shipping_id');
      
    }
      
    return false;
  
}
     
function get_order_item_id($order_id, $item_id){
      
       
    global $db;
      
    $sql = "Select order_item_id from va_orders_items where order_id=" . $db->tosql($order_id, INTEGER). " AND item_id=" . $db->tosql($item_id, INTEGER) . ";";        
      
    $db->query($sql);
      
    if($db->next_record()){
         
        return $db->f('order_item_id');
      
    }
      
    return false;
      
  
}

   
function create_recommended_products($item_ids_recom){

	global $db,$site_host,$t;
	
	$table_prefix = "va_";
	$sql_params = array();
	$sql_params["join"][]   = " LEFT JOIN " . $table_prefix . "items_related ir ON i.item_id=ir.related_id ";
	$sql_params["where"][] = " ir.item_id IN (" . $db->tosql($item_ids_recom, INTEGERS_LIST) . ")";
	$sql_params["where"][] = " ir.related_id NOT IN (" . $db->tosql($item_ids_recom, INTEGERS_LIST) . ")";

	$recom_products_ids = VA_Products::find_all_ids($sql_params, VIEW_CATEGORIES_ITEMS_PERM);	
	
	$sql_params = array();
	$sql_params["join"][]   = " LEFT JOIN " . $table_prefix . "items_accessories ia ON i.item_id=ia.accessory_id ";	
	$sql_params["where"][]  = " ia.item_id IN (" . $db->tosql($item_ids_recom, INTEGERS_LIST) . ")";
	$sql_params["where"][]  = " ia.accessory_id NOT IN (" . $db->tosql($item_ids_recom, INTEGERS_LIST) . ")";
		
	$recom_accessories_ids = VA_Products::find_all_ids($sql_params, VIEW_CATEGORIES_ITEMS_PERM);	
	
	$recom_ids = array_merge($recom_products_ids, $recom_accessories_ids);
        if (!$recom_ids){ 
            return "";
            
        }
	array_unique($recom_ids);

	
	
    
    $sqll = "SELECT item_id, item_name, price, manufacturer_code, small_image, friendly_url FROM va_items where item_id IN (" . $db->tosql($recom_ids, INTEGERS_LIST) . ")";
      
    
    $db->query($sqll);
                
    $items_rec = array();
                
    $html_r = "";
       
                
    while($db->next_record()){
             
                    
        $items_rec[] = array(
                 
            "id" => "".$db->f('item_id')."" ,
                 
            "name" => "".$db->f('item_name')."",
                 
            "code" => "".$db->f('manufacturer_code')."",
                 
            "img" => "".$db->f('small_image')."",
                 
            "friendly" => "".$db->f('friendly_url')."",
                 
            "price" => $db->f('price'),
                 
        );

        
    }
		$max_col = 4;
		$max_item = 4;
		$number_of_items = count($items_rec);
		$collection_box = array();
        
        foreach ($items_rec as $id) {
             
            $html_r = "";
			$url_r = $site_host  . '/' . 'product_details.php?item_id='.$id['id'];
            $img = $site_host  . '/' . $id['img'];
            $link = $site_host  . '/' . $id['friendly'];
            $name = $id['name'];
		    $price_n = $id['price'];
		    setlocale(LC_MONETARY, 'en_US.UTF-8');
            $price = money_format('%.2n', $price_n );
            $mpn = $id['code'];
            $html_r .= '<table class="producttable"><tr><td colspan="2" class="producttitle center"><a style="text-decoration:none;" href="'.$url_r.'" target="blank">'.$name.'</a></td></tr> <tr><td class="half">';
            $html_r .= '<a href="'.$url_r.'" target="blank"><img src="'.$img.'" border="0" /></a></td><td class="price center"><a class="btn waves-effect waves-light green" href="'.$url_r.'" target="blank">BUY NOW</a><br><font style="font-weight:500;">'.$price.'</font></td></tr><tr><td colspan="2" class="recompn blue"><b>MPN:'.$mpn.'</b></td></tr></table>';
            $collection_box[]= $html_r;
    
          
        }
                
        $html_r = "";
              
        $width=25;
              
              
        if($number_of_items < 5){
                  
            $width = (132/$number_of_items) ;
                  
            if($width < 50 ){
                      
                $width * 2;
                  
            }
              
        }
            
        $col = 0;
              
        for($col = 0;$col < $number_of_items;$col++){
                    
            if($col < $max_col){
                    
                $html_r .= '<div class="product s3">'.$collection_box[$col].'</div>';	
                    
            }else{
                        
                break;
                    
            }
					
              
        }
            
        
        $col_size = $col+8;
		  //$t->set_var("recommended_col",$col_size);
		  
          
          return $html_r;

 
        }