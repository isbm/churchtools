<?php 


/**
 * Erzeugt eine Uebersicht ueber alle Stammdatentabellen, die per MaintainView gepflegt werden sollen
 * Diese Infos werden per JSON an das JS uebergeben
 */
function churchwiki_getMasterDataTablenames() {
  $res=array();
  $res[1]=churchcore_getMasterDataEntry(1, "Wiki-Kategorien", "wikicategory", "cc_wikicategory","sortkey,bezeichnung");
  
  return $res;
}

function churchwiki_getMasterDataTables() {
  $tables=churchwiki_getMasterDataTablenames();
  foreach ($tables as $value) {
    $res[$value["shortname"]]=churchcore_getTableData($value["tablename"],$value["sql_order"]);
  }
  return $res;
}

function churchwiki_getCurrentNo($doc_id, $wikicategory_id=0) {
  $res=db_query("select max(version_no) c from {cc_wiki} where doc_id=:doc_id and wikicategory_id=:wikicategory_id",
    array(":doc_id"=>$doc_id, ":wikicategory_id"=>$wikicategory_id))->fetch();
  if ($res==false) 
    return -1;
  else 
    return $res->c;  
}

function churchwiki_setShowonstartpage($params) {
  $i=new CTInterface();
  $i->setParam("doc_id");
  $i->setParam("version_no");
  $i->setParam("wikicategory_id");
  $i->setParam("auf_startseite_yn");
  
  try {
    db_update("cc_wiki")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("doc_id", $params["doc_id"], "=")
      ->condition("version_no", $params["version_no"], "=")
      ->condition("wikicategory_id", $params["wikicategory_id"], "=")
      ->execute(false);
  } 
  catch (Exception $e) {
    return jsend()->error($e);      
  }
  return jsend()->success();  
  
  
}

function churchwiki_load($doc_id, $wikicategory_id, $version_no=null) {
  if ($version_no==null)
    $version_no=churchwiki_getCurrentNo($doc_id, $wikicategory_id);

  ct_log("Aufruf Hilfeseite $wikicategory_id:$doc_id ($version_no)",2,"-1", "help");
  
    
  $sql_1="select * from {cc_wiki} where version_no=:version_no and doc_id=:doc_id and wikicategory_id=:wikicategory_id";
  $sql_2="select p.vorname, p.name, doc_id,  version_no, wikicategory_id, aes_decrypt(text, '".churchcore_getEncryptionKey()."') text, 
          modified_date, modified_pid, auf_startseite_yn from {cc_wiki} w LEFT JOIN {cdb_person} p ON (w.modified_pid=p.id) 
          where version_no=:version_no and doc_id=:doc_id and wikicategory_id=:wikicategory_id";
  $data=db_query((churchcore_getEncryptionKey()==null?$sql_1:$sql_2), 
    array(':doc_id'=>$doc_id, ":wikicategory_id"=>$wikicategory_id,
       ':version_no'=>$version_no)
  )->fetch();
  // Kann die Encrption nicht lesen, versuche es nun ohne
  if (($data==null) || ($data->text==null)) {
    $data=db_query($sql_1, array(':doc_id'=>$doc_id, ":wikicategory_id"=>$wikicategory_id,
       ':version_no'=>$version_no)
    )->fetch();
  }        
  if (isset($data->text)) {
    $data->text = preg_replace('/\\\/', "", $data->text);
    $data->text = preg_replace('/===([^===]*)===/', "<h3>$1</h3>", $data->text);
    $data->text = preg_replace('/==([^==]*)==/', "<h2>$1</h2>", $data->text);
    $data->files=churchcore_getFilesAsDomainIdArr("wiki_".$wikicategory_id, $doc_id);
  }
  return $data;
}

function churchwiki_cron() {
  // Hole mir alle Daten, die �ber 90 Tage alt sind und dann die neuste Version_No
  $db=db_query("SELECT MAX( version_no ) version_no, wikicategory_id, doc_id FROM {cc_wiki}
    WHERE DATE_ADD( modified_date, INTERVAL 90  DAY ) < NOW( )   GROUP BY wikicategory_id, doc_id");
  foreach ($db as $e) {  
    db_query("delete from {cc_wiki} where wikicategory_id=$e->wikicategory_id and doc_id='$e->doc_id'
              and version_no<$e->version_no");
  }
  
}

function churchwiki__filedownload() {
  include_once("system/churchcore/churchcore.php");
  churchcore__filedownload();  
}

function churchwiki__ajax() {
  global $user, $files_dir, $base_url, $mapping, $config;
  
  $auth["view"]=user_access("view category", "churchwiki");
  $auth["edit"]=user_access("edit category", "churchwiki");
  $auth["admin"]=user_access("edit masterdata", "churchwiki");
  
  if ((!user_access("view","churchwiki")) && (!in_array("churchwiki",$mapping["page_with_noauth"]))
        && (!in_array("churchwiki",$config["page_with_noauth"]))) {
    addInfoMessage("Keine Berechtigung f&uuml;r das ChurchWiki");
    $res=jsend()->fail("Keine Berechtigung f&uuml;r das ChurchWiki");
  }
  else {
    if (((isset($mapping["page_with_noauth"])) && (in_array("churchwiki",$mapping["page_with_noauth"]))) 
      || ((isset($config["page_with_noauth"]) && (in_array("churchwiki",$config["page_with_noauth"]))))) {
        if (!isset($auth["view"])) $auth["view"]=array();
        $auth["view"][0]="0";        
      }
  
    $func="";
    if (isset($_GET["func"]))
      $func=$_GET["func"];
    else  
      $func=$_POST["func"];
    if ($func=='save') {
      if (($auth["edit"]==false) || ($auth["edit"][$_POST["wikicategory_id"]]!=$_POST["wikicategory_id"]))
        $res=jsend()->fail("Keine Berechtigung zum Speichern von ".$_POST["wikicategory_id"]);
      else {  
        $dt = new DateTime();
        if (churchcore_getEncryptionKey()==null)
          $sql="insert into {cc_wiki} (doc_id, version_no, wikicategory_id, text, modified_date, modified_pid)
            values (:doc_id, :version_no, :wikicategory_id, :text, :modified_date, :modified_pid)";
        else  
          $sql="insert into {cc_wiki} (doc_id, version_no, wikicategory_id, text, modified_date, modified_pid)
            values (:doc_id, :version_no, :wikicategory_id, aes_encrypt(:text, '".churchcore_getEncryptionKey()."'), :modified_date, :modified_pid)";
        db_query($sql,array(":doc_id"=>$_POST["doc_id"], 
          ":version_no"=>churchwiki_getCurrentNo($_POST["doc_id"],$_POST["wikicategory_id"])+1, 
          ":wikicategory_id"=>$_POST["wikicategory_id"],
          ":text"=>$_POST["val"], 
          ":modified_date"=>$dt->format('Y-m-d H:i:s'), 
          ":modified_pid"=>$user->id));
        
        $res=jsend()->success();
      }      
    }
    else if ($func=='masterData') {
      $data["wikicategory"]=churchcore_getTableData("cc_wikicategory");
      $data["auth"]=$auth;    
      
      $data["settings"]=array();
      $data["masterDataTables"] = churchwiki_getMasterDataTablenames();
      $data["files_url"] = $base_url.$files_dir;
      $data["files_dir"] = $files_dir;
      $data["modulename"] = "churchwiki";
      $data["modulespath"] = drupal_get_path('module', 'churchwiki');
      $data["adminemail"] = variable_get('site_mail', '');
      $data["encrypted"] = churchcore_getEncryptionKey()!=null;
      $res=jsend()->success($data);      
    }
    else if ($func=='load') {
      if (($auth["view"]==false) || ($auth["view"][$_GET["wikicategory_id"]]!=$_GET["wikicategory_id"]))
        $res=jsend()->fail("Keine Berechtigung zum Laden von ".$_GET["wikicategory_id"]);
      else {
        if (!isset($_GET["version_no"]))
          $data=churchwiki_load($_GET["doc_id"], $_GET["wikicategory_id"]);
        else    
          $data=churchwiki_load($_GET["doc_id"], $_GET["wikicategory_id"], $_GET["version_no"]);
        $res=jsend()->success($data);      
      }
    }
    else if ($func=='loadHistory') {
      if (($_GET["wikicategory_id"]!=0) && (($auth["view"]==false) || ($auth["view"][$_GET["wikicategory_id"]]!=$_GET["wikicategory_id"])))
        $res=jsend()->fail("Keine Berechtigung zum Laden von ".$_GET["wikicategory_id"]);
      else {        
        $data=db_query("select version_no id, 
          concat('Version ', version_no,' vom ', modified_date, ' - ',p.vorname, ' ', p.name) as bezeichnung from {cc_wiki} w, {cdb_person} p where w.modified_pid=p.id and doc_id=:doc_id and wikicategory_id=:wikicategory_id order by version_no desc",
              array(':doc_id'=>$_GET["doc_id"], ":wikicategory_id"=>$_GET["wikicategory_id"]));
        $res_data=array();      
        foreach ($data as $d) {
          $res_data[$d->id]=$d;
        }      
        $res=jsend()->success($res_data);
      };
    }
    else if ($func=="showonstartpage") {
      if (($auth["edit"]==false) || ($auth["edit"][$_POST["wikicategory_id"]]!=$_POST["wikicategory_id"]))
        $res=jsend()->fail("Keine Berechtigung zum Editieren von ".$_POST["wikicategory_id"]);
      else {  
        $res=churchwiki_setShowonstartpage($_POST);
      }
    }
    else if ($func=="delFile") {
      $res=churchcore_delFile($_GET["id"]);
    }
    else if ($func=="renameFile") {
      $res=churchcore_renameFile($_GET["id"], $_GET["filename"]);
    }
    else if ($func=="deleteMasterData") {
      if ((user_access("edit masterdata","churchwiki")) && (churchcore_isAllowedMasterData(churchwiki_getMasterDataTablenames(), $_GET["table"]))) {
        churchcore_deleteMasterData($_GET["id"], $_GET["table"]);
        ct_log("f:$func id:".$_GET["id"],2,-1,"masterData",1);
        $res=jsend()->success();
      }
      else $res=jsend()->error("Keine Rechte! ".(!user_access("edit masterdata","churchservice")?"Bitte edit masterdata vergeben.":"Unerlaubte Tabelle!"));
    }
    else if ($func=="saveMasterData") {
      if ((user_access("edit masterdata","churchwiki")) && (churchcore_isAllowedMasterData(churchwiki_getMasterDataTablenames(), $_GET["table"]))) {      
        churchcore_saveMasterData($_GET["id"], $_GET["table"]);
        ct_log("f:$func id:".$_GET["id"],2,-1,"masterData",1);
        $res=jsend()->success();
      } 
      else $res=jsend()->error("Keine Rechte! ".(!user_access("edit masterdata","churchservice")?"Bitte edit masterdata vergeben.":"Unerlaubte Tabelle!"));
    }
    else {
      $res=jsend()->fail("Funktion $func ist nicht bekannt!");      
    }
  }
  drupal_json_output($res);  
}


function churchwiki_getWikiOnStartpage() {
  if (!user_access("view", "churchwiki"))
    return "";
  $ids=user_access("view category", "churchwiki");
  if ($ids==false)
    return "";
  $db=db_query("select w.wikicategory_id, w.doc_id, wc.bezeichnung, version_no, 
             DATE_FORMAT(w.modified_date , '%d.%m.%Y %H:%i') date,
             concat(p.vorname, ' ', p.name) user  
             from {cc_wiki} w, {cc_wikicategory} wc, {cdb_person} p 
         where wikicategory_id in (".implode(",",$ids).")". 
              " and w.wikicategory_id=wc.id and w.modified_pid=p.id ".
                "and w.auf_startseite_yn=1 order by w.wikicategory_id, modified_date Asc");
  $ar=array();
  foreach ($db as $wiki) {
    // Hole nun die max. Version_no
    $w=db_query("select max(version_no) version_no from {cc_wiki} where doc_id=:doc_id and wikicategory_id=:wikicategory_id",
         array(":doc_id"=>$wiki->doc_id, ":wikicategory_id"=>$wiki->wikicategory_id))->fetch();
    if ($w->version_no==$wiki->version_no)     
      $ar[$wiki->bezeichnung][$wiki->doc_id]=$wiki;
   
  }
  $txt="";
  foreach ($ar as $key=>$cat) {
    $txt.='<li><p>Wiki '.$key;
    foreach ($cat as $wiki) {
      $txt.='<br/><small><a href="?q=churchwiki#WikiView/filterWikicategory_id:'.$wiki->wikicategory_id.'/doc:'.$wiki->doc_id.'">'.($wiki->doc_id=="main"?"Hauptseite":$wiki->doc_id)."</a>";
      $txt.=" - $wiki->date $wiki->user</small>";     
    }
  }
  if ($txt!="")
    $txt="<ul>".$txt."</ul>"; 
  return $txt;
}

function churchwiki_getWikiInfos() {
  if (!user_access("view", "churchwiki"))
    return "";
  $ids=user_access("view category", "churchwiki");
  if ($ids==false)
    return "";
  $db=db_query("select w.wikicategory_id, w.doc_id, wc.bezeichnung, 
             DATE_FORMAT(w.modified_date , '%d.%m.%Y %H:%i') date,
             concat(p.vorname, ' ', p.name) user  
             from {cc_wiki} w, {cc_wikicategory} wc, {cdb_person} p 
         where wikicategory_id in (".implode(",",$ids).")". 
              " and w.wikicategory_id=wc.id and w.modified_pid=p.id ".
                "and datediff(now(),modified_date)<2 order by w.wikicategory_id, modified_date Asc");
  $ar=array();
  foreach ($db as $wiki) {
    $ar[$wiki->bezeichnung][$wiki->doc_id]=$wiki;
  }
  $txt="";
  foreach ($ar as $key=>$cat) {
    $txt.='<li><p>Wiki '.$key;
    foreach ($cat as $wiki) {
      $txt.='<br/><small><a href="?q=churchwiki#WikiView/filterWikicategory_id:'.$wiki->wikicategory_id.'/doc:'.$wiki->doc_id.'">'.($wiki->doc_id=="main"?"Hauptseite":$wiki->doc_id)."</a>";
      $txt.=" - $wiki->date $wiki->user</small>";     
    }
  }
  if ($txt!="")
    $txt="<ul>".$txt."</ul>"; 
  return $txt;
}

function churchwiki_blocks() {
  global $config;
  return (array(
    1=>array(
      "label"=>"Wichtiges von ".$config["churchwiki_name"],
      "col"=>2,
      "sortkey"=>1,
      "html"=>churchwiki_getWikiOnStartpage()
      //"help"=>"Offene Dienstanfragen"
    ),
    2=>array(
      "label"=>"Neuigkeiten von ".$config["churchwiki_name"],
      "col"=>2,
      "sortkey"=>8,
      "html"=>churchwiki_getWikiInfos()
      //"help"=>"Offene Dienstanfragen"
    )
  ));
}
      

function churchwiki__create() {
  include_once("system/includes/forms.php");

  $model = new CC_Model("EditHtml", "editHtml");
  $model->setHeader("Editieren eines Hilfeeintrages", "Hier kann die Hilfe editiert werden.");    
  $model->addField("doc_id", "", "INPUT_REQUIRED","Doc-Id");
  $model->addField("text", "", "TEXTAREA","Text");
  if (isset($_GET["doc"])) {
    $model->fields["doc_id"]->setValue($_GET["doc"]);
    $res=db_query("select text from {cc_wiki} where doc_id=:doc_id", array(":doc_id"=>$_GET["doc"]))->fetch();
    if ($res) {
      $res->text = preg_replace('/\\\/', "", $res->text);
      $model->fields["text"]->setValue($res->text);
    }
  }
  $model->addButton("Speichern","ok");
  
  return $model->render();
}


function editHtml($form) {
  global $user;
  $dt=new DateTime();
  db_query("insert into {cc_wiki} (doc_id, text, modified_date, modified_pid) 
            values (:doc_id, :text, :date, :pid) ON DUPLICATE KEY UPDATE text=:text, modified_date=:date, modified_pid=:pid", 
    array(":text"=>$form->fields["text"]->getValue(), 
          ":doc_id"=>$form->fields["doc_id"]->getValue(),
          ":date"=>$dt->format('Y-m-d H:i:s'),
          ":pid"=>$user->id));
  
  ct_log("Aktualisierung Hilfeseite ".$form->fields["doc_id"]->getValue(), 2,"-1", "help");
    
  header("Location: ?q=churchwiki&doc=".$form->fields["doc_id"]->getValue());
}

function help_main() {
  return churchwiki_main();
}

function churchwiki_main() {
  global $files_dir;
  include_once("system/includes/forms.php");

  drupal_add_css('system/assets/ui/jquery-ui-1.8.18.custom.css');
  drupal_add_js('system/assets/ui/jquery.ui.core.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.datepicker.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.position.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.widget.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.dialog.min.js');
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  drupal_add_js('system/assets/fileuploader/fileuploader.js'); 
  
  drupal_add_js('system/assets/tablesorter/jquery.tablesorter.min.js'); 
  drupal_add_js('system/assets/tablesorter/jquery.tablesorter.widgets.min.js'); 
  
  drupal_add_js('system/assets/mediaelements/mediaelement-and-player.min.js'); 
  drupal_add_css('system/assets/mediaelements/mediaelementplayer.css');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/shortcut.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchcore.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchforms.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  
  drupal_add_js('system/churchcore/cc_interface.js');
  drupal_add_js('system/assets/ckeditor/ckeditor.js');
  //drupal_add_js('system/assets/ckeditor/config.js');
  drupal_add_js('system/assets/ckeditor/lang/de.js');
  //drupal_add_css('system/assets/ckeditor/skins/moono/editor.css');
  drupal_add_js('system/churchwiki/wiki_maintainview.js');
  drupal_add_js('system/churchwiki/churchwiki.js');
  
  $doc_id="";
  if (isset($_GET["doc"])) $doc_id=$_GET["doc"];

  $text='<div id="cdb_navi"></div>';
  $text.='<div id="cdb_content"></div>';
  if ($doc_id!="")
    $text.='<input type="hidden" id="doc_id" name="doc_id" value="'.$doc_id.'"/>';    
    
  $page='<div class="row-fluid">';
    $page.='<div class="span3 bs-docs-sidebar">';   
      $page.='<div class="bs-docs-sidebar" id="cdb_menu"></div>';   
      $page.='<div class="bs-docs-sidebar" id="sidebar"></div>';   
    
    $page.='</div>';
    $page.='<div class="span9">'.$text.'</div>';
  $page.='</div>';
 
  return $page;
}

function churchwiki__printview() {
  global $files_dir;
  include_once("system/includes/forms.php");

  drupal_add_css('system/assets/ui/jquery-ui-1.8.18.custom.css');
  drupal_add_js('system/assets/ui/jquery.ui.core.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.datepicker.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.position.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.widget.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.dialog.min.js');
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  drupal_add_js('system/assets/fileuploader/fileuploader.js'); 
  
  drupal_add_js('system/assets/tablesorter/jquery.tablesorter.min.js'); 
  drupal_add_js('system/assets/tablesorter/jquery.tablesorter.widgets.min.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/shortcut.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchcore.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchforms.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  
  drupal_add_js('system/churchcore/cc_interface.js');
  drupal_add_js('system/assets/ckeditor/ckeditor.js');
  //drupal_add_js('system/assets/ckeditor/config.js');
  drupal_add_js('system/assets/ckeditor/lang/de.js');
  //drupal_add_css('system/assets/ckeditor/skins/moono/editor.css');
  drupal_add_js('system/churchwiki/wiki_maintainview.js');
  drupal_add_js('system/churchwiki/churchwiki.js');
  
  $doc_id="main";
  if (isset($_GET["doc"])) $doc_id=$_GET["doc"];

  $text='<div id="cdb_content"></div>';
  $text.='<input type="hidden" id="doc_id" name="doc_id" value="'.$doc_id.'"/>';        
  $text.='<input type="hidden" id="printview" name="doc_id" value="true"/>';        
  $page='<div class="row-fluid">';
    $page.='<div class="span12">'.$text.'</div>';
  $page.='</div>';
 
  return $page;
}
?>
