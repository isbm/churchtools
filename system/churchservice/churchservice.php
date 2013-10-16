<?php 

function ical_main() {
  include_once(drupal_get_path('module', 'churchservice').'/churchservice_ajax.inc');
  call_user_func("churchservice_ical");
}

function churchservice__ajax() {
  if (!user_access("view","churchservice")) {
    addInfoMessage("Keine Berechtigung f&uuml;r ChurchService");
    return " ";
  }
  include_once(drupal_get_path('module', 'churchservice').'/churchservice_ajax.inc');
  call_user_func("churchservice_ajax");
}


function churchservice__filedownload() {
  include_once("system/churchcore/churchcore.php");
  churchcore__filedownload();  
}

function churchservice__exportfacts() {
  if (!user_access("edit facts","churchservice")) {
    addInfoMessage("Keine Berechtigung zum Exportieren von Faktendaten (edit facts)");
    return " ";
  }
  drupal_add_http_header('Content-type', 'application/csv; charset=ISO-8859-1; encoding=ISO-8859-1',true);
  drupal_add_http_header('Content-Disposition', 'attachment; filename="churchservice_fact_export.csv"',true);
  
  $events=churchcore_getTableData("cs_event", "datum");
  $category=churchcore_getTableData("cs_category");
  $facts=churchcore_getTableData("cs_fact", "sortkey");
  $res=db_query("select * from cs_event_fact");  
  
  $result=array();
  foreach($res as $d) {
    $result[$d->event_id]->facts[$d->fact_id]=$d->value;    
  }

  echo '"Datum";"Bezeichnung";"Kategorie";';
  foreach ($facts as $fact) {
    echo mb_convert_encoding('"'.$fact->bezeichnung.'";', 'ISO-8859-1', 'UTF-8');
  }
  echo "\n";
  foreach ($events as $key=>$event) {
    if (isset($result[$key])) {
      echo "$event->datum;";
      echo mb_convert_encoding('"'.$event->bezeichnung.'";', 'ISO-8859-1', 'UTF-8');
      echo mb_convert_encoding('"'.$category[$event->category_id]->bezeichnung.'";', 'ISO-8859-1', 'UTF-8');
      foreach ($facts as $fact) {
        if (isset($result[$key]->facts[$fact->id])) {
          echo $result[$key]->facts[$fact->id];
        }
        echo ";";
      }       
      echo "\n";
    }    
  }
  return null;
}

function churchservice_getAuth() {
  return "view churchservice";
}

function churchservice_getName() {
  global $config;
  return $config["churchservice_name"];
}

function churchservice_main() {
 // drupal_add_css(drupal_get_path('module', 'churchcore').'/churchcore.css');
  //drupal_add_css(drupal_get_path('module', 'churchcore').'/churchcore_bootstrap.css');
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  drupal_add_css('system/assets/ui/jquery-ui-1.8.18.custom.css');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/shortcut.js'); 
  drupal_add_js('system/bootstrap/js/bootstrap-multiselect.js'); 
  drupal_add_js('system/assets/fileuploader/fileuploader.js'); 
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js('system/assets/ui/jquery.ui.core.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.datepicker.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.position.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.widget.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.dialog.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.mouse.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.draggable.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.selectable.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js('system/assets/mediaelements/mediaelement-and-player.min.js'); 
  drupal_add_css('system/assets/mediaelements/mediaelementplayer.css');
  
  
  drupal_add_js('system/assets/ckeditor/ckeditor.js');
  drupal_add_js('system/assets/ckeditor/lang/de.js');  
    
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchcore.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchforms.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_interface.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_loadandmap.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_settingsview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_maintainview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_listview.js'); 
  //drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_testview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_calview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_factview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_itemview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_songview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_main.js'); 

  $content="";
  // Übergabe der ID für den Direkteinstieg einer Person
  if (isset($_GET["id"]) && ($_GET["id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"externevent_id\" value=\"".$_GET["id"]."\"/>";
  if (isset($_GET["service_id"]) && ($_GET["service_id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"service_id\" value=\"".$_GET["service_id"]."\"/>";
    
  $content=$content." 
<div class=\"row-fluid\">
  <div class=\"span3\">
    <div id=\"cdb_menu\"></div>
    <div id=\"cdb_filter\"></div>
  </div>  
  <div class=\"span9\">
    <div id=\"cdb_search\"></div> 
    <div id=\"cdb_group\"></div> 
    <div id=\"cdb_content\"></div>
  </div>
</div>";
  return $content;
}



function churchservice_getUserOpenServices($shorty=true) {
  global $user;
  
  if (isset($_GET["eventservice_id"])) {
    include_once('./'. drupal_get_path('module', 'churchservice') .'/churchservice_ajax.inc');
    $reason=null;
    if (isset($_GET["reason"])) $reason=$_GET["reason"];
    if ($_GET["zugesagt_yn"]==1)
      churchservice_updateEventService($_GET["eventservice_id"], $user->vorname." ".$user->name, $user->id, 1, $reason);
    else  
      churchservice_updateEventService($_GET["eventservice_id"], null, null, 0, $reason);
    addInfoMessage("Danke f&uuml;r deine R&uuml;ckmeldung!");
  }
  
  include_once('./'. drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');
  $txt="";
  $pid = $user->id;
  $txt1="";        
  $res = db_query("select cal.bezeichnung event, e.id event_id, es.id eventservice_id, allowtonotebyconfirmation_yn,
                       DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, s.bezeichnung service, 
                       s.id service_id, sg.bezeichnung servicegroup, concat(p.vorname, ' ', p.name) as modifieduser, p.id modified_pid
                   from {cs_eventservice} es, {cs_event} e, {cs_servicegroup} sg, {cs_service} s, {cdb_person} p, {cc_cal} cal 
                    where cal.id=e.cc_cal_id and cdb_person_id=$user->id and e.startdate>=current_date() and es.modified_pid=p.id and 
                    zugesagt_yn=0 and valid_yn=1 and es.event_id=e.id and es.service_id=s.id 
                    and sg.id=s.servicegroup_id order by datum ");
  $nr=0;
  $txt2="";
  foreach($res as $arr) {
    $nr=$nr+1;
    if (($nr<=3) || (!$shorty)) {
      $txt2=$txt2.'<p><a href="?q=churchservice&id=#'.$arr->event_id.'">';
      $txt2.=$arr->datum." - ".$arr->event."</a>: ";
      if (!$shorty) {
        $txt2.="<br/>&nbsp; &nbsp; ".$arr->modifieduser." hat Dich vorgeschlagen f&uuml;r ";
      } 
      $txt2.='<a href="?q=churchservice&id=#'.$arr->event_id.'"><b>'.$arr->service."</b></a> (".$arr->servicegroup.")";

      $files=churchcore_getFilesAsDomainIdArr("service", $arr->event_id);
      $txt.='<span class="pull-right">';
      if ((isset($files)) && (isset($files[$arr->event_id]))) {
        foreach ($files[$arr->event_id] as $file) {
          $txt.=churchcore_renderFile($file)."&nbsp;";
        }
      }
      $txt.="</span>";
      
      if ($shorty) $txt2.="<br/>&nbsp;&nbsp; &nbsp; ";
      if ($arr->allowtonotebyconfirmation_yn==0)
        $txt2.=l("Zusagen",'',array("q"=>"home","eventservice_id"=>$arr->eventservice_id, "zugesagt_yn"=>1)).' | ';
      else  
        $txt2.='<a href="#" id="zusagen" eventserviceid="'.$arr->eventservice_id.'" onclick="askMeYes('.$arr->eventservice_id.')">Zusagen</a> | ';            
      
      $txt2.='<a href="#" id="absagen" eventserviceid="'.$arr->eventservice_id.'" onclick="askMeNo('.$arr->eventservice_id.')">Absagen</a>';            
      if (!$shorty) {
        $q=array("event_id"=>$arr->event_id, "service_id"=>$arr->service_id);            
        $txt2.=" | ".l("Anderen vorschlagen",'churchservice',array("query"=>$q));
      }
      else {
        $txt2.="&nbsp; &nbsp; <small>Anfrage von ".
            (user_access("view","churchdb")?'<a href="?q=churchdb#PersonView/searchEntry:#'.$arr->modified_pid.'">'.$arr->modifieduser.'</a>':$arr->modifieduser).
          "</small>";
      }

      $txt2.='<script>
                    function askMeYes(id) { 
             				var res=prompt("Wirklich verbindlich zusagen? Hier kannst Du noch eine Notiz angeben.","");
             				if (res!=null) window.location.href="?q=home&zugesagt_yn=1&reason="+res+"&eventservice_id="+id;
           			} 
                    function askMeNo(id) { 
             				var res=prompt("Wirklich absagen? Hier kannst Du noch einen Grund angeben.","");
             				if (res!=null) window.location.href="?q=home&zugesagt_yn=0&reason="+res+"&eventservice_id="+id;
           			} 
           			</script>';
    } 
  }           
  if ($txt2!="") $txt=$txt.$txt1.$txt2;
  //if (($shorty) && ($txt!="")) $txt.='<br><p align="right">'.l("Weiter","?q=churchservice");
  return $txt;
}


function churchservice_getCurrentEvents() {
  global $user;
  // Hole Events, wo Dienste sind, wo ich Leiter/Co-Leiter bin.
  $txt="";
  
  
  $mygroups=churchdb_getMyGroups($user->id, true, true);
  
  $events=db_query("select e.id, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, bezeichnung 
      from {cs_event} e, {cc_cal} cal 
      where cal.id=e.cc_cal_id and DATE_ADD(e.startdate, INTERVAL 3 hour) > NOW() and datediff(e.startdate, now())<3 order by e.startdate");
  foreach($events as $event) {
    $firstrow=true;
    $ess=db_query("select es.name, s.cdb_gruppen_ids, s.bezeichnung, es.zugesagt_yn
                  from {cs_eventservice} es, {cs_service} s, {cs_servicegroup} sg 
                  where es.valid_yn=1 and es.service_id=s.id and s.servicegroup_id=sg.id and  
                      es.event_id=$event->id 
                  order by sg.sortkey, s.sortkey");
    foreach($ess as $es) {
      $istdrin=false;
      $service_groups=explode(',',$es->cdb_gruppen_ids);
      foreach ($service_groups as $service_group) {
        if (in_array($service_group, $mygroups))
          $istdrin=true;
      }   
      if ($istdrin) {
        if ($firstrow) {
          $txt.='<li><a href="?q=churchservice&id=#'.$event->id.'">'."$event->datum - $event->bezeichnung</a><p>";
          $firstrow=false;
        }
        $txt.="<small>&nbsp; $es->bezeichnung: ";
        if ($es->zugesagt_yn==1) {
          $txt.=$es->name;
        }
        else {
          $txt.='<font style="color:red">';
          if ($es->name!=null) $txt.=$es->name;
          $txt.="?";  
          $txt.='</font>';
        }
        $txt.="</small><br/>";
      }
    }
  }  
  if ($txt!="") $txt="<ul>$txt</ul>";
  return $txt;  
}


function churchservice_getUserNextServices($shorty=true) {
  global $user;
  
  include_once('./'. drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');
  
  $pid=$user->id;
  $res = db_query("select e.id event_id, cal.bezeichnung event, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, s.bezeichnung service, sg.bezeichnung servicegroup, cdb_person_id, DATE_FORMAT(es.modified_date, '%d.%m.%Y %H:%i') modified_date
   from {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_servicegroup} sg, {cs_service} s where
   cal.id=e.cc_Cal_id and  
     cdb_person_id=$pid and e.startdate>=current_date and zugesagt_yn=1 and valid_yn=1 and es.event_id=e.id and es.service_id=s.id and sg.id=s.servicegroup_id order by e.startdate");
  $nr=0;
  $txt="";
  foreach($res as $arr) {
    $nr=$nr+1;
    if (($nr<=5) || (!$shorty)) {
      $txt.='<p><a href="?q=churchservice&id=#'.$arr->event_id.'">'.$arr->datum." - ".$arr->event."</a>: ";
      $txt.='<a href="?q=churchservice&id=#'.$arr->event_id.'"><b>'.$arr->service."</b></a> (".$arr->servicegroup.")";
      $files=churchcore_getFilesAsDomainIdArr("service", $arr->event_id);
      $txt.='<span class="pull-right">';
      if ((isset($files)) && (isset($files[$arr->event_id]))) {
        foreach ($files[$arr->event_id] as $file) {
          $txt.=churchcore_renderFile($file)."&nbsp;";
        }
      }
      $txt.="</span><small><br>&nbsp; &nbsp; &nbsp; ";
      $txt.="Zugesagt am ".$arr->modified_date."</small>";
    }  
  }
  //if (($shorty) && ($txt!="")) $txt.='<br/><p align="right">'.l("Weiter","?q=churchservice");
  return $txt;  
}


function churchservice_getFactsOfLastDays() {
  $txt='';
  if (user_access("edit facts","churchservice")) {
    $res=db_query("select e.id, cal.bezeichnung eventname, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, f.bezeichnung factname, value 
                from {cs_fact} f, {cs_event_fact} ef, {cs_event} e, {cc_cal} cal
             where cal.id=e.cc_cal_id and ef.fact_id=f.id and ef.event_id=e.id and datediff(now(), e.startdate)<3 and datediff(now(), e.startdate)>=0
            order by e.startdate, factname");
    $event=null;
    foreach($res as $val) {
      if ($val->id!=$event) {
        $event=$val->id;
        $txt.='</small><li><a href="?q=churchservice&id='.$val->id.'#FactView/">'.$val->datum." - ".$val->eventname.'</a><p>';
      } 
      $txt.='<small>'.$val->factname.": ".$val->value."</small><br/>";
    }
    if ($txt!='')
      $txt='<ul>'.$txt.'</ul>';           
  }
  return $txt;
}

function churchservice_getAbsents($year=null) {
  $txt='';
  
  if (user_access("view","churchdb")) {
    $user=$_SESSION["user"];
    include_once(drupal_get_path('module', 'churchdb').'/churchdb_db.inc');
    $groups=churchdb_getMyGroups($user->id, true, true);
    $allPersonIds=churchdb_getAllPeopleIdsFromGroups($groups);
    
    if (count($groups)>0) {
      $sql="select p.id p_id, p.name, p.vorname, DATE_FORMAT(a.startdate, '%d.%m.') startdate_short, DATE_FORMAT(a.startdate, '%d.%m.%Y') startdate, DATE_FORMAT(a.enddate, '%d.%m.%Y') enddate, a.bezeichnung, ar.bezeichnung reason 
                from {cdb_person} p, {cs_absent} a, {cs_absent_reason} ar 
              where a.absent_reason_id=ar.id and p.id=a.person_id and p.id in (".implode(",", $allPersonIds).") ";
      if ($year==null)
        $sql.="and datediff(a.enddate,now())>=-1 and datediff(a.enddate,now())<=31";
      else 
        $sql.="and (DATE_FORMAT(a.startdate, '%Y')=$year or DATE_FORMAT(a.enddate, '%Y')=$year)";
      $sql.=" order by a.startdate";  
        
      $db=db_query($sql);  
      $people=array();    
      foreach ($db as $a) {
        if (isset($people[$a->p_id]))
          $absent=$people[$a->p_id];
        else $absent=array();  
        $absent[]=$a;  
        $people[$a->p_id]=$absent;  
      }
      if (count($people)>0) {
        $txt='<ul>';        
        foreach ($people as $p) {
          $txt.='<li>'.$p[0]->vorname." ".$p[0]->name.": <p>";
          foreach ($p as $abwesend) {
            $reason=$abwesend->reason;
            if ($abwesend->bezeichnung!=null) $reason=$abwesend->bezeichnung." ($reason)";
            if ($abwesend->startdate==$abwesend->enddate)
              $txt.='<small>'.$abwesend->startdate." $reason</small><br/>";
            else          
              $txt.='<small>'.$abwesend->startdate_short."-".$abwesend->enddate." $reason</small><br/>";
          }      
        }
        $txt.='</ul>';
      }
      if (($year==null) && (user_access("view","churchcal")))
        $txt.='<p style="line-height:100%" align="right"><a href="?q=churchcal&viewname=yearView">Weitere</a></p>';
    }
  }
  return $txt;
}

function churchservice_blocks() {
  return (array(
    1=>array(
      "label"=>"Deine offenen Dienstanfragen",
      "col"=>2,
      "sortkey"=>1,
      "html"=>churchservice_getUserOpenServices(),
      "help"=>"Offene Dienstanfragen"
    ),  
    2=>array(
      "label"=>"Deine n&auml;chsten Dienste",
      "col"=>2,
      "sortkey"=>2,
      "html"=>churchservice_getUserNextServices()
    ),  
    3=>array(
      "label"=>"Deine aktuelle Eventbesetzung",
      "col"=>2,
      "sortkey"=>3,
      "html"=>churchservice_getCurrentEvents()
    ),  
    4=>array(
      "label"=>"Abwesenheiten der n&auml;chsten 30 Tage",
      "col"=>2,
      "sortkey"=>4,
      "html"=>churchservice_getAbsents()
    ),  
    5=>array(
      "label"=>"Fakten der letzten Tage",
      "col"=>2,
      "sortkey"=>5,
      "html"=>churchservice_getFactsOfLastDays()
    ),  
    ));
} 


/**
 * Infos f�r noch zu best�tigende Dienste
 */
function churchservice_openservice_rememberdays() {
  global $base_url;
  include_once(drupal_get_path('module', 'churchcore').'/churchcore_db.inc');
  $delay=variable_get('churchservice_openservice_rememberdays');
  $dt = new datetime();
  
  // Checken, ob EIN EventService noch nicht gesendet wurde, bzw. schon so alt ist.
  // Pr�fe dabei, ob die Person eine EMail-Adresse hat und auch gemappt wurde.
  $sql="select es.id, p.id p_id, p.vorname, p.email, es.modified_pid, if (password is null and loginstr is null and lastlogin is null,1,0) as invite  
                    from {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_service} s, {cdb_person} p 
                    where e.cc_cal_id=cal.id and es.valid_yn=1 and es.zugesagt_yn=0 and es.cdb_person_id is not null
                      and es.service_id=s.id and s.sendremindermails_yn=1 
                      and es.event_id=e.id and e.Startdate>=current_date
                      and ((es.mailsenddate is null) or (datediff(current_date,es.mailsenddate)>=$delay))
                      and p.email!='' and p.id=es.cdb_person_id limit 1";
  $res=db_query($sql)->fetch();
  
  $sql2="select es.id id, cal.bezeichnung event, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, e.id event_id,
                 s.bezeichnung service, sg.bezeichnung servicegroup, es.mailsenddate
              from {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_service} s, {cs_servicegroup} sg 
                 where cal.id=e.cc_cal_id and es.valid_yn=1 and es.zugesagt_yn=:zugesagt and es.cdb_person_id=:p_id
                  and s.sendremindermails_yn=1 
                  and es.event_id=e.id and es.service_id=s.id and sg.id=s.servicegroup_id
                  and e.startdate>=current_date
                  order by e.startdate";
  $i=0;
  // Lasse 15 EventServices durch, dann warten bis n�chste Cron, sonst werden es zu viele Mails
  while (($res) && ($i<15)) {
    // Wenn einer vorhanden ist, dann suche nach weiteren offenen Diensten f�r die Person
    $txt="<h3>Hallo ".$res->vorname.",</h3><p>";
    
    $inviter=churchcore_getPersonById($res->modified_pid);
    if ($inviter!=null)        
      $txt.="Du wurdest in dem Dienstplan auf ".variable_get('site_name', 'drupal').
             ' von <i>'.$inviter->vorname." ".$inviter->name."</i> zu Diensten vorgeschlagen. <br/>Bitte sage diese zu oder ab:";
    else      
      $txt.="Du wurdest in dem Dienstplan auf ".variable_get('site_name', 'drupal')." zu Diensten vorgeschlagen.<br/>Bitte sage diese zu oder ab:";

    $loginstr=churchcore_createPersonLoginStr($res->p_id);
      
    $txt.='<p><a href="'.$base_url.'?q=home&id='.$res->p_id.'&loginstr='.$loginstr.'" class="btn btn-primary">%sitename aufrufen</a>';
    
    $txt.="<p><p><b>Folgende Dienst-Termine sind von Dir noch nicht bearbeitet:</b><ul>";        
    $arr=db_query($sql2, array(":p_id"=>$res->p_id, ":zugesagt"=>0));
    foreach ($arr as $res2) {
      $txt.="<li> ".$res2->datum." ".$res2->event.":  ".$res2->service." (".$res2->servicegroup.")";
      db_update("cs_eventservice")
        ->fields(array("mailsenddate"=>$dt->format('Y-m-d H:i:s')))
        ->condition('id',$res2->id,"=")
        ->execute();          
    }
    $txt.='</ul>';
    
    $arr=db_query($sql2, array(":p_id"=>$res->p_id, ":zugesagt"=>1));
    $txt2="";
    foreach ($arr as $res2) {
      $txt2.="<li> ".$res2->datum." - ".$res2->event.":  ".$res2->service." (".$res2->servicegroup.")";
      if ($res2->mailsenddate==null) $txt2.=" NEU!";
      db_update("cs_eventservice")
        ->fields(array("mailsenddate"=>$dt->format('Y-m-d H:i:s')))
        ->condition('id',$res2->id,"=")
        ->execute();          
      }
      if ($txt2!="") {
        $txt.="<p><p><b>Bei folgenden Diensten hast Du schon zugesagt:</b><ul>".$txt2;        
        $txt.="</ul>";
    }  

    // Person wurde noch nicht eingeladen, also schicke gleich eine Einladung mit!
    if ($res->invite==1) {
      include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
      churchdb_invitePersonToSystem($res->p_id);
      $txt.="<p><b>Da Du noch nicht kein Zugriff auf das System hast, bekommst Du noch eine separate E-Mail, mit der Du Dich dann anmelden kannst!.</b>";
    }
    
    churchservice_send_mail("[".variable_get('site_name', 'drupal')."] Es sind noch Dienste offen",$txt,$res->email);
    $i=$i+1;
    $res=db_query($sql)->fetch();
  }
}


function churchservice_remindme() {
  global $base_url;
  $set=db_query("select * from {cc_usersettings} where modulename='churchservice' and attrib='remindMe' and value=1");
  foreach ($set as $p) {
    $res=db_query("SELECT cal.bezeichnung, s.bezeichnung dienst, sg.bezeichnung sg, e.id event_id, 
           DATE_FORMAT(e.Startdate, '%d.%m.%Y %H:%i') datum, es.id eventservice_id
       FROM {cs_eventservice} es, {cs_service} s, {cs_event} e, {cc_cal} cal, {cs_servicegroup} sg where
         cal.id=e.cc_cal_id and  es.cdb_person_id=:person_id AND es.valid_yn=1 AND es.zugesagt_yn=1
       and UNIX_TIMESTAMP(e.startdate)-UNIX_TIMESTAMP(now())<60*60*".variable_get('churchservice_reminderhours')." 
       and UNIX_TIMESTAMP(e.startdate)-UNIX_TIMESTAMP(now())>0
       AND s.id=es.service_id and s.sendremindermails_yn=1   
       AND e.id=es.event_id AND s.servicegroup_id=sg.id",array(":person_id"=>$p->person_id));
    foreach($res as $es) {
      if (churchcore_checkUserMail($p->person_id, "remindService", $es->eventservice_id, variable_get('churchservice_reminderhours'))) {
        $person=db_query("SELECT vorname, name, email FROM {cdb_person} WHERE id=:person_id",
                        array(":person_id"=>$p->person_id))->fetch();
        $txt="<h3>Hallo ".$person->vorname."!</h3>";
        $txt.="<p>Dies ist eine Erinnerung an den Dienst ".$es->dienst." (".$es->sg."): ".$es->bezeichnung." am ".$es->datum.".\n\n";
        $txt.='<p><a href="'.$base_url.'?q=churchservice&id=#'.$es->event_id.'" class="btn btn-primary">Event aufrufen</a>';
        $txt.='&nbsp;<a class="btn" href="'.$base_url.'?q=churchservice#SettingsView">Erinnerungen deaktivieren</a>';
        churchservice_send_mail("[".variable_get('site_name', 'drupal')."] Erinnerung an Deinen Dienst",$txt,$person->email);                            
      }              
    }
  }  
}

function churchcore_checkUserMail($p, $mailtype, $id, $interval) {
   $result = db_query("SELECT letzte_mail FROM {cc_usermails} WHERE person_id=:person_id and mailtype=:mailtype and domain_id=:domain_id",
     array(":person_id"=>$p, ":mailtype"=>$mailtype, ":domain_id"=>$id))->fetch();
  $dt=new DateTime();
  if ($result==null) {
    db_insert("cc_usermails")
      ->fields(array("person_id"=>$p, "mailtype"=>$mailtype, "domain_id"=>$id, "letzte_mail"=>$dt->format('Y-m-d H:i:s')))
      ->execute();
    return true;
  } 
  else {
    $lm=new DateTime($result->letzte_mail);
    $dt=new DateTime(date("Y-m-d",strtotime("-".$interval." hour")));
    if ($lm<$dt) {
      $dt=new DateTime();
      db_query("update {cc_usermails} set letzte_mail=:dt where person_id=:p and mailtype=:mailtype and domain_id=:domain_id",
           array(":dt"=>$dt->format('Y-m-d H:i:s'), ":p"=>$p, ":mailtype"=>$mailtype, ":domain_id"=>$id));
      return true;
    }    
  }
  return false; 
}
function churchservice_inform_leader() {
global $base_url;
  // Hole erst mal die Gruppen_Ids, damit ich gleich nicht alle Personen holen mu�
  $res=db_query("select cdb_gruppen_ids from {cs_service} where cdb_gruppen_ids!='' and cdb_gruppen_ids is not null and sendremindermails_yn=1");
  $arr=array();
  foreach($res as $g) {
    $arr[]=$g->cdb_gruppen_ids;
  }
   
  if (count($arr)==0) return false;
  
  // Hole nun die Person/Gruppen wo die Person Leiter oder Co-Leiter ist
  $res=db_query("select p.id person_id, gpg.gruppe_id, p.email, p.vorname, p.cmsuserid from {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
      where gpg.gemeindeperson_id=gp.id and p.id=gp.person_id and status_no>=1 and status_no<=2
      and gpg.gruppe_id in (".implode(",",$arr).")");

  // Aggregiere nach Person_Id P1[G1,G2,G3],P2[G3]
  $persons=array();
  foreach ($res as $p) {
    $data=churchcore_getUserSettings("churchservice",$p->person_id);
    // Darf er �berhaupt noch, und wenn ja dann schaue ob der Leiter es will.
    // (Wenn noch nicht best�tigt, dann wird davon ausgegangen
    $auth=getUserAuthorization($p->person_id);
    if (isset($auth["churchservice"]["view"]) && 
        ((!isset($data["informLeader"])) || ($data["informLeader"]==1))) {
      if (!isset($data["informLeader"])) {
        $data["informLeader"]=1;
        churchcore_saveUserSetting("churchservice",$p->person_id,"informLeader","1");
      }  
      
      if (isset($persons[$p->person_id]))
        $arr=$persons[$p->person_id]["group"];
      else {
        $persons[$p->person_id]=array();
        $arr=array();
        $persons[$p->person_id]["service"]=array();
        $persons[$p->person_id]["person"]=$p;
      }
      $arr[]=$p->gruppe_id;
      $persons[$p->person_id]["group"]=$arr;
    }
  }
  
  // Gehe nun die Personen durch und schaue wer seit einer Zeit keine Mail mehr bekommen hatte.
  foreach ($persons as $person_id=>$p) {
    if (!churchcore_checkUserMail($person_id, "informLeaderService", -1, 6*24)) {
      $persons[$person_id]=null;
    }
  }
  
  // Suche nun dazu die passenden Services
  $res=db_query("select cdb_gruppen_ids, bezeichnung, id service_id from {cs_service} where cdb_gruppen_ids is not null");
  foreach ($res as $d) {
    $gruppen_ids=explode(",", $d->cdb_gruppen_ids);
    foreach ($persons as $key=>$person) {
      if ($person!=null) {
        foreach($person["group"] as $person_group) {
          if (in_array($person_group,$gruppen_ids))
            $persons[$key]["service"][]=$d->service_id;
        }
      }  
    }    
  }
  
  // Gehe nun die Personen durch und suche nach Events
  foreach ($persons as $person_id=>$person) {
    if ($person!=null) {
      $res=db_query("select es.id, e.bezeichnung event, DATE_FORMAT(e.datum, '%d.%m.%Y %H:%i') datum, es.name, s.bezeichnung service from {cs_event} e, {cs_eventservice} es, {cs_service} s 
         where es.service_id in (".implode(",",$person["service"]).")
          and es.event_id=e.id and es.service_id=s.id and es.valid_yn=1 and zugesagt_yn=0
          and e.datum>current_date order by e.datum");
      $txt='';
      foreach ($res as $es) {
        $txt.="<li>". $es->datum." ".$es->event." - Dienst ".$es->service.": ";
        if ($es->name==null)
          $txt.="?";
        else         
          $txt.=$es->name."";
      }
      if ($txt!='') {    
        $txt="<h3>Hallo ".$person["person"]->vorname."!</h3><p>Hier eine Liste der noch offenen Dienste in Deinem Bereich:<ul>".$txt."</ul>";
        $txt.="<p>Weitere Infos hierzu auf ".
            $base_url."/?q=churchservice \n";
        $txt.="<p>Diese Benachrichtigung deaktivieren auf ".
            $base_url."/?q=churchservice#SettingsView/ \n\n";
        churchservice_send_mail("[".variable_get('site_name', 'drupal')."] Offene Dienste",$txt,$person["person"]->email);
      }
    }                                
  }
}

function churchservice_cron() {
  global $base_url;
  
  include_once('./'. drupal_get_path('module', 'churchservice') .'/churchservice_ajax.inc');
  
  churchservice_openservice_rememberdays();
  churchservice_remindme();
  churchservice_inform_leader();
}


?>
