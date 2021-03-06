<?php

include_once('./'. drupal_get_path('module', 'churchresource') .'/../churchcore/churchcore_db.inc');

/**
 * 
 * @param $subject
 * @param $message
 * @param $to
 */
function churchresource_send_mail ($subject, $message, $to) { 
  churchcore_systemmail($to, $subject, $message, true);
}

function churchresource_createBooking($params) {
  global $base_url, $user;


  $i=new CTInterface();
  $i->setParam("resource_id");
  $i->addTypicalDateFields();
  $i->setParam("person_id");
  $i->setParam("status_id");
  $i->setParam("text");
  $i->setParam("location");
  $i->setParam("note");
  $i->setParam("cc_cal_id", false);
  $id=db_insert("cr_booking")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->execute(false);
  $res=db_query("SELECT * from {cr_booking} where id=$id")->fetch();
  $res->ok=true; 

           
  $exc_txt="";         
  if (isset($params["exceptions"])) {
    foreach ($params["exceptions"] as $exception) {
      addException($res->id, $exception["except_date_start"], $exception["except_date_end"], $user->id);
      $exc_txt.=churchcore_stringToDateDe($exception["except_date_start"], false)+" &nbsp;";
    }
  }  
  $add_txt="";
  if (isset($params["additions"])) {
    $days=array();
    foreach ($params["additions"] as $addition) {
      addAddition($res->id, $addition["add_date"], $addition["with_repeat_yn"], $user->id);
      $add_txt.=churchcore_stringToDateDe($addition["add_date"], false)+" ";
      if ($addition["with_repeat_yn"]==1)
        $add_txt.="{R} ";
      $add_txt.="&nbsp;";  
    }
  }             
           
  $info=churchcore_getTableData("cr_resource");
  $txt="Hier sind alle Buchungsinformationen zusammengefasst:<p><small>";
  $txt.='<table class="table table-condensed">';
  $txt.="<tr><td>Bezeichnung<td>$res->text";
  $txt.="<tr><td>Ressource<td>".$info[$params["resource_id"]]->bezeichnung;
  $txt.="<tr><td>Start<td>".churchcore_stringToDateDe($res->startdate);
  $txt.="<tr><td>Ende<td>".churchcore_stringToDateDe($res->enddate);
  
  $status=churchcore_getTableData("cr_status");
  $txt.="<tr><td>Status<td>".$status[$res->status_id]->bezeichnung;
  
  if ($res->location!="")
    $txt.="<tr><td>Ort<td>$res->location";
  if ($res->repeat_id!="0") {    
    $repeats=churchcore_getTableData("cc_repeat");
    $txt.="<tr><td>Wiederholungstyp<td>".$repeats[$res->repeat_id]->bezeichnung;
    if ($res->repeat_id!=999)
      $txt.="<tr><td>Wiederholung bis<td>".churchcore_stringToDateDe($res->repeat_until, false);
    if ($exc_txt!="")  
      $txt.="<tr><td>Ausnahmen<td>$exc_txt";
  }
  if ($add_txt!="")  
    $txt.="<tr><td>Weitere Termine<td>$add_txt";
  if ($res->note!="")
    $txt.="<tr><td>Notiz<td>$res->note";
  if ((isset($params["conflicts"])) && ($params["conflicts"]!=null))
    $txt.="<tr><td>Konflikte<td>".$params["conflicts"];
    
  $txt.="</table>";
  
  $txt.='</small><p><a class="btn" href="'.$base_url."?q=churchresource&id=".$res->id.'">Zur Buchungsanfrage &raquo;</a>';
  
  if ($params["status_id"]==1) {
  	$txt_user="<h3>Hallo ".$user->vorname."!</h3><p>Deine Buchungsanfrage '".$params["text"]. 
  	      "' ist bei uns eingegangen und wird bearbeitet. Sobald es einen neuen Status gibt, wirst Du informiert.<p>".$txt;
  	$txt_admin="<h3>Hallo Admin!</h3><p>Eine neue Buchungsanfrage von <i>$user->vorname $user->name</i> wartet auf Genehmigung.<p>".$txt;    
  } 
  else { 
    $txt_user="<h3>Hallo ".$user->vorname."!</h3><p>Deine Buchung  '".$params["text"]."' war erfolgreich.<p>".$txt;        
    $txt_admin="<h3>Hallo Admin!</h3><p>Eine neue Buchung von <i>$user->vorname $user->name</i> wurde erstellt und automatisch genehmigt.<p>".$txt;    
  }          
  $istUserAdmin=false;
  if ($info[$params["resource_id"]]->admin_person_ids!=-1) {
    $adminids=explode(',',$info[$params["resource_id"]]->admin_person_ids);
    foreach($adminids as $adminid) {
      // Nur, wenn ich nicht selber der Admin bin
      if ($user->id!=$adminid) {
        $p=churchcore_getPersonById($adminid);
        if (($p!=null) && ($p->email!=""))
          churchresource_send_mail("[".variable_get('site_name', 'drupal')."] Neue Buchungsanfrage: ".$params["text"], $txt_admin, $p->email);
      }
      else $istUserAdmin=true;      
    }
  }  
  if (!$istUserAdmin)       
    churchresource_send_mail("[".variable_get('site_name', 'drupal')."] Neue Buchungsanfrage: ".$params["text"], $txt_user, $user->email);
  $txt=churchcore_getFieldChanges(getBookingFields(),null,$res);
  cr_log("CREATE BOOKING\n".$txt,3,$res->id);  
  return jsend()->success($res);
}

function getBookingFields() {
  $res["id"]=churchcore_getTextField("Booking-Id","Id","id");
  $res["resource_id"]=churchcore_getTextField("Resource","Res","resource_id");
  $res["person_id"]=churchcore_getTextField("UserId","User","person_id");
  $res["startdate"]=churchcore_getDateField("Startdatum","Start","startdate");
  $res["enddate"]=churchcore_getDateField("Enddatum","Ende","enddate");
  $res["repeat_id"]=churchcore_getTextField("Wiederholungs-Id","Wdh.","repeat_id");
  $res["repeat_frequence"]=churchcore_getTextField("Wiederholungsfrequenz","Wdh.-Freq.","repeat_frequence");
  $res["repeat_until"]=churchcore_getDateField("Wiederholungs-Ende","Wdh.-Ende","repeat_until");
  $res["status_id"]=churchcore_getTextField("Status","Status","status_id");
  $res["text"]=churchcore_getTextField("Text","Text","text");
  $res["location"]=churchcore_getTextField("Ort","Ort","location");
  $res["note"]=churchcore_getTextField("Notiz","Notiz","note");
  return $res;
}


function churchresource_updateBooking($params) {
  global $base_url, $user;
  
  $bigchange=isset($params["repeat_id"]);
  
  $old_arr=getBooking($params["id"]);
  $buser=churchcore_getPersonById($old_arr->person_id);
  if ($buser==null) {
    $res=jsend()->error("Error by loading user!");
  }
  else {  
    $ressources=churchcore_getTableDataSorted("cr_resource","resourcetype_id,sortkey,bezeichnung");
    
    $i=new CTInterface();
    $i->setParam("resource_id");
      $i->setParam("status_id");
    if ($bigchange) {
      $i->addTypicalDateFields();
      $i->setParam("text");
      $i->setParam("location");
      $i->setParam("note");
    }
    else {  
      $i->setParam("startdate");
      $i->setParam("enddate");
      $res=db_query('select * from {cr_booking} where id=:id', array(":id"=>$params["id"]))->fetch();
      $params["text"]=$res->text;      
    }
    $i->setParam("person_id");
    $id=db_update("cr_booking")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute(false);
    
    // Hole alle Exceptions aus der DB
    $exc=churchcore_getTableData("cr_exception",null, "booking_id=".$params["id"]);
    $res_exceptions=array();
    // Vergleiche erst mal welche schon in der DB sind oder noch nicht in der DB sind.         
    if (isset($params["exceptions"])) {
      $days=array();
      foreach ($params["exceptions"] as $exception) {
        if (($exception["id"]>0) && (isset($exc[$exception["id"]]))) 
          $exc[$exception["id"]]->vorhanden=true;
        else {
          $res_exceptions[$exception["id"]]=addException($params["id"], $exception["except_date_start"], $exception["except_date_end"], $user->id);
          $days[]=$exception["except_date_start"];
        }
      }
      if (count($days)>0) {
        $txt="<h3>Hallo ".$buser->vorname."!</h3><p>Bei Deiner Serien-Buchungsanfrage '".
               $params["text"]."' fuer ".$ressources[$params["resource_id"]]->bezeichnung.
               " mussten leider von ".$user->vorname." ".$user->name." folgende Tage abgelehnt werden: <b>".
               implode(", ",$days)."</b><p>";
        churchresource_send_mail("[".variable_get('site_name', 'drupal')."] Aktualisierung der Buchungsanfrage: ".$params["text"], $txt, $buser->email);
      }
    }  
    // L�sche nun alle, die in der DB sind, aber nicht mehr vorhanden sind.
    if (($exc!=false) && ($bigchange)) {
      foreach ($exc as $e) {
        if (!isset($e->vorhanden))
          churchresource_delException($e->id);
      }
    }

    
    // Hole alle Additions aus der DB
    $add=churchcore_getTableData("cr_addition",null, "booking_id=".$params["id"]);
    $res_additions=array();
    // Vergleiche erst mal welche schon in der DB sind oder noch nicht in der DB sind.         
    if (isset($params["additions"])) {
      $days=array();
      foreach ($params["additions"] as $addition) {
        if ($addition["id"]>0) 
          $add[$addition["id"]]->vorhanden=true;
        else {
          $res_additions[$addition["id"]]=addAddition($params["id"], $addition["add_date"], $addition["with_repeat_yn"], $user->id);
          $days[]=$addition["add_date"];
        }
      }
    }  
    // L�sche nun alle, die in der DB sind, aber nicht mehr vorhanden sind.
    if (($add!=false) && ($bigchange)) {
      foreach ($add as $a) {
        if (!isset($a->vorhanden))
          churchresource_delAddition($a->id);
      }
    }
    
    $txt="";
    $info="'".$params["text"]."' fuer ".$ressources[$params["resource_id"]]->bezeichnung." (".$params["startdate"]."h";
    if ($params["location"]!="")
      $info=$info." in ".$params["location"];
    $info=$info.")";  
      
    if ($params["status_id"]==1) {
      $txt="<h3>Hallo ".$buser->vorname."!</h3><p>Deine Buchungsanfrage ".$info." wurde aktualisiert und wartet auf Genehmigung.";        
    $txt.='<p><a class="btn" href="'.$base_url."?q=churchresource&id=".$params["id"].'">Zur Buchungsanfrage &raquo;</a>';
    } 
    else if (($params["status_id"]==2) && ($old_arr->status_id!=2)) {
      $txt="<h3>Hallo ".$buser->vorname."!</h3><p>Deine Buchungsanfrage ".$info." wurde nun von $user->vorname $user->name genehmigt!<p>";        
    $txt.='<p><a class="btn" href="'.$base_url."?q=churchresource&id=".$params["id"].'">Zur Buchungsanfrage &raquo;</a>';
    } 
    else if ($params["status_id"]==3) {
      $txt="<h3>Hallo ".$buser->vorname."!</h3><p>Deine Buchungsanfrage ".$info." wurde leider abgelehnt, bitte suche Dir einen anderen Termin.<p>";        
    }      	               
    else if ($params["status_id"]==99) {
      $txt="<h3>Hallo ".$buser->vorname."!</h3><p>Deine Buchungsanfrage ".$info." wurde geloescht, bei Fragen dazu melde Dich bitte bei: ".variable_get('site_mail', 'Gemeinde-Buero unter info@elim-hamburg.de oder 040-2271970')."<p>";        
    }                     
    if ($txt!="") {
      $adminmails=explode(",",$ressources[$params["resource_id"]]->admin_person_ids);
      // Wenn der aktuelle User nicht Admin ist ODER wenn der Benutzer nicht der ist, der die Buchung erstellt hat.
      if ((!in_array($user->id, $adminmails)) || ($user->id!=$buser->id))      
        churchresource_send_mail("[".variable_get('site_name', 'drupal')."] Aktualisierung der Buchungsanfrage: ".$params["text"], $txt, $buser->email);
    }

    $arr=getBooking($params["id"]);
    $txt=churchcore_getFieldChanges(getBookingFields(),$old_arr,$arr,false);
    if ($txt!=null)
      cr_log("UPDATE BOOKING\n".$txt,3,$arr->id);
    $res=jsend()->success(array("exceptions"=>$res_exceptions));
  }
  return $res;
}


function _shiftDate($date, $minutes) {
  $dt=new DateTime($date);
  $dt->modify("+$minutes Minute");
  return $dt->format('Y-m-d H:i:s');
}

function churchresource_updateResourcesFromChurchCal($params, $source) {
  global $user;
  $db=db_query('select * from {cr_booking} where cc_cal_id=:cal_id', 
      array(":cal_id"=>$params["id"]));
      
  $params["location"]="";
  $params["note"]="";      
      
  foreach ($db as $booking) {
    
    if ((isset($params["bookings"])) && (isset($params["bookings"][$booking->resource_id]))) {
      $save = array_merge(array(), $params);      
      $save["cc_cal_id"]=$params["id"];
      if (!isset($params["bookings"][$booking->resource_id]["status_id"]))
        $save["status_id"]=1;
      else $save["status_id"]=$params["bookings"][$booking->resource_id]["status_id"];
      $save["id"]=$booking->id;  
      $save["person_id"]=$user->id;
      $save["resource_id"]=$booking->resource_id;
      // Wenn es ein gro�es Update ist, also nicht nur eine Verschiebung auf dem Kalender
      if (isset($params["repeat_id"])) {
        $save["text"]=$params["bezeichnung"];
      }      
      $save["startdate"]=_shiftDate($save["startdate"], -$params["bookings"][$booking->resource_id]["minpre"]);
      $save["enddate"]=_shiftDate($save["enddate"], $params["bookings"][$booking->resource_id]["minpost"]);
      
      // Wenn es einen Unterschied im Datum gibt, setze den Status wieder auf zu genehmigen!
      if ((strtotime($save["startdate"])!=strtotime($booking->startdate)) ||
            (strtotime($save["enddate"])!=strtotime($booking->enddate)))
        $save["status_id"]=1;
        
      churchresource_updateBooking($save);
        
      $params["bookings"][$booking->resource_id]["updated"]=true;
    }
    else if ((!isset($params["bookings"])) && (isset($params["cal_id"]))) {
    } 
  }

  
  // Gehe nun noch die neuen Bookings durch, die nicht in der DB sind
  if (!isset($params["bookings"])) return;
  foreach ($params["bookings"] as $booking) {
    if (!isset($booking["updated"])) {
      $save = array_merge(array(), $params);      
      $save["cc_cal_id"]=$params["id"];
      $save["status_id"]=1;
      $save["person_id"]=$user->id;
      $save["resource_id"]=$booking["resource_id"];
      $save["text"]=$params["bezeichnung"];
      $save["startdate"]=_shiftDate($save["startdate"], -$params["bookings"][$booking["resource_id"]]["minpre"]);
      $save["enddate"]=_shiftDate($save["enddate"], $params["bookings"][$booking["resource_id"]]["minpost"]);      
      churchresource_createBooking($save); 
    }
  }  
}      

function getOpenBookings() {
  $result = db_query("SELECT b.id, b.person_id, concat(p.vorname,' ',p.name) person_name, DATE_FORMAT(startdate, '%d.%m.%Y %H:%i') startdate, enddate, b.text, r.bezeichnung resource 
	        from {cr_booking} b, {cr_resource} r, {cdb_person} p WHERE
	        b.person_id=p.id and  
	      status_id=1 and b.resource_id=r.id order by startdate");
  $arrs=array();
  foreach ($result as $arr) {
    $arrs[$arr->id]=$arr;   
  }
  return $arrs; 
}

function churchresource_getLastLogId() {
  $arr=db_query("select max(id) id from {cr_log}")->fetch();
  return $arr->id;  
}

function churchresource_pollForNews($last_id) {
  global $user;
  $res=db_query("select * from {cr_log} where id > $last_id and person_id!='".$user->id."'");
  $arrs=Array();
  foreach ($res as $arr) {
    $arrs[$arr->id]=$arr;   
  }
  $arr=Array();
  $arr["lastLogId"]=churchresource_getLastLogId();
  $arr["logs"]=$arrs;  
  return $arr;
}

function addException($booking_id, $date_start, $date_end, $pid) {
  $dt=new DateTime();  
  return db_insert("cr_exception")->fields(array(
     "booking_id"=>$booking_id,
     "except_date_start"=>$date_start,
     "except_date_end"=>$date_end,
     "modified_pid"=>$pid,
     "modified_date"=>$dt->format('Y-m-d H:i:s')))->execute();
}
function churchresource_delException($exc_id) {
  $res=db_query("delete from {cr_exception} where id=:id",array(':id'=>$exc_id));
  return jsend()->success();      
}

function addAddition($booking_id, $date_start, $with_repeat, $pid) {
  $dt=new DateTime();  
  return db_insert("cr_addition")->fields(array(
     "booking_id"=>$booking_id,
     "add_date"=>$date_start,
     "with_repeat_yn"=>$with_repeat,
     "modified_pid"=>$pid,
     "modified_date"=>$dt->format('Y-m-d H:i:s')))->execute();
}
function churchresource_delAddition($add_id) {
  $res=db_query("delete from {cr_addition} where id=:id",array(':id'=>$add_id));
  return jsend()->success();      
}

function getBookings($von=null, $bis=null, $status_id_in="") {
  if ($von==null)
    $von=-variable_get('churchresource_entries_last_days', '90');
  if ($bis==null) 
    $bis=999;  
  if ($status_id_in!="") $status_id_in=" and status_id in ($status_id_in)";
  $res = db_query("SELECT b.id , b.cc_cal_id, b.resource_id, b.person_id, b.startdate, b.enddate, 
              b.repeat_id, b.repeat_frequence, b.repeat_until, b.repeat_option_id, b.status_id, b.text,
               b.location, b.note, b.show_in_churchcal_yn, concat(p.vorname, ' ',p.name) person_name 
                   FROM {cr_booking} b, {cdb_person} p
                    WHERE b.person_id=p.id and  
	      ((startdate<=DATE_ADD(now(),INTERVAL $von day) and enddate>DATE_ADD(now(),INTERVAL $von day) )
	         or (enddate>=DATE_ADD(now(),INTERVAL $von day) and enddate<=DATE_ADD(now(),INTERVAL $bis day)) 
	         or (repeat_id>0 and startdate<=DATE_ADD(now(),INTERVAL $bis day) and (repeat_until>=DATE_ADD(now(),INTERVAL $von day) or repeat_id=999))) 
                          $status_id_in");
  $arrs=null;
  foreach ($res as $arr) {    
    $res2 = db_query("SELECT * FROM {cr_exception} WHERE booking_id=".$arr->id." ORDER by except_date_start");
    $arr2g=Array();
    foreach ($res2 as $arr2) {
      $arr2g[$arr2->id]=$arr2;      
    }
    if ($arr2g!=null) 
      $arr->exceptions=$arr2g;

    $res2 = db_query("SELECT * FROM {cr_addition} WHERE booking_id=".$arr->id." ORDER by add_date");
    $arr2g=Array();
    foreach ($res2 as $arr2) {
      $arr2g[$arr2->id]=$arr2;      
    }
    if ($arr2g!=null) 
      $arr->additions=$arr2g;
      
    if (isset($arr->cc_cal_id)) {
      $r=db_query("select category_id from {cc_cal} where id=:cal_id", array(":cal_id"=>$arr->cc_cal_id))->fetch();
      if ($r!=false) $arr->category_id=$r->category_id;
    }  
      
    $arrs[$arr->id]=$arr;   
  }
  if ($arrs==null)
    return "";
  return $arrs; 	
}

function getBooking($id) {
  $res=db_query("SELECT b.*, concat(p.vorname, ' ', p.name) person_name 
                from {cr_booking} b, {cdb_person} p
                where b.person_id=p.id and b.id=".$id)->fetch(); 
  return $res;	
}

function delBooking($id) {
  $res=db_query("DELETE FROM {cr_exception} where booking_id=".$id); 
  $res=db_query("DELETE FROM {cr_addition} where booking_id=".$id); 
  $res=db_query("DELETE FROM {cr_booking} where id=".$id);
  return "ok"; 
}

function churchresource_getLogs($id) {
  $res=db_query("SELECT l.*, concat(p.vorname,' ',p.name) as person_name from {cr_log} l, {cdb_person} p 
             where l.person_id=p.id and booking_id=".$id." order by datum desc");
  $ret=null; 
  foreach ($res as $arr) {
    $ret[]=$arr;
  }
  return $ret; 	 
}

/**
 * 
 * @param unknown_type $txt
 * @param unknown_type $level  3=Unwichtig 2=Erscheint in PersonDetails 1=Wichtig!!
 * @param unknown_type $personid  Wenn Bezug zur PersonId
 */
function cr_log($txt,$level=3,$booking_id=-1) {
	global $user;
	db_query("insert into {cr_log} (person_id, level, datum, booking_id, txt) values ('$user->id', $level, current_timestamp(), $booking_id, '$txt')");
}

