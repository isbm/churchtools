<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<title><?php echo $config["site_name"]." - ".(isset($config[$q."_name"])?$config[$q."_name"]:$q); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta name="description" content="ChurchTools">
    <meta name="author" content="">

	
	<link href="system/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="system/includes/churchtools.css" rel="stylesheet">
    
   <?php if (!$embedded) {?>
    <style>
    body {
      padding-top: 60px;
      padding-bottom: 40px;
    }
    </style>
   <?php } ?>

  <link href="system/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
   <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
<script src="system/assets/js/jquery.js"></script>
 <link rel="shortcut icon" href="system/assets/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="system/assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="system/assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="system/assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="system/assets/ico/apple-touch-icon-57-precomposed.png">
    <?php echo $add_header; ?>
</head>

<body>

   <?php if (!$embedded) {?>

    <div class="navbar navbar-fixed-top <?php if (!isset($_SESSION["simulate"])) echo "navbar-inverse" ?>">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="?q=home"><?php echo $config["site_name"] ?></a>

            <?php if (userLoggedIn()) { ?>
              <div class="btn-group pull-right">
                <?php if (isset($_SESSION["simulate"])) {?>
                  <a class="btn" href="?q=simulate&link=<?php echo $q?>">
                    Simulation beenden
                  </a>
                  <?php } ?>   
                <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                  <i class="icon-user"></i>&nbsp;<span class="hidden-phone"><?php echo $_SESSION["user"]->vorname." ".$_SESSION["user"]->name ?> </span>
                  <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                  <?php //Andere Familienmitglieder mit der gleichen E-Mail-Adresse
                   if (isset($_SESSION["family"])) {
                     echo('<caption>Wechseln zu...</caption>');
                     foreach ($_SESSION["family"] as $family) {
                        echo('<li><a href="?q=login&family_id='.$family->id.'">'.$family->vorname.' '.$family->name.'</a></li>');                        
                      }                       
                      echo('<li class="divider"></li>');
                    }                                    
                  ?>
                  <li><a href="?q=profile">
                  <?php 
                    if (isset($user->password)) echo "Passwort &auml;ndern";
                    else echo "Passwort setzen"; 
                  ?></a></li>
                  <?php if ((user_access("view", "churchdb")) && (isset($user)) && ($user->email!='')) { ?>
                    <li><a href="?q=churchdb/mailviewer">Gesendete Nachrichten</a></li>
                  <?php } ?>  
                  <li class="divider"></li>
                  <?php 
                  
                    if (user_access("administer settings", "churchcore")) {
                      echo '<li><a href="?q=admin">Admin-Einstellungen</a></li>';                      
                    }                  
                    if (user_access("administer persons", "churchcore")) {
                      echo '<li><a href="?q=auth">Rechteverwaltung</a></li>';
                    } 
                    if (user_access("administer settings", "churchcore")) {
                      echo '<li><a href="?q=cron&manual=true">Cron-Job ausf&uuml;hren</a></li>';
                    }                  
                    if (user_access("view logfile", "churchcore")) {
                      echo '<li><a href="?q=churchcore/logviewer">Log-Viewer</a></li>';
                    }
                    if (user_access("administer settings", "churchcore")) {
                      echo '<li class="divider"></li>';
                    }                  
                    ?>   
                  <li><a href="?q=about">&Uuml;ber ChurchTools 2.0</a></li>
                  <li class="divider"></li>
                  <li><a href="?q=logout">Abmelden</a></li>
                </ul>
              </div>
             <div class="pull-right">
               <ul class="nav">
                 <li class="active"><p><div id="cdb_status" style="color:#999"></div></li>
               </ul>
             </div>
            <?php } ?>
              <div class="nav-collapse">
                <ul class="nav">
                  <?php   
                    $arr=churchcore_getModulesSorted();
                    foreach ($arr as $key) {
                      include_once("system/".$mapping[$key]);
                      if ((isset($config[$key."_name"])) && (isset($config[$key."_inmenu"])) && ($config[$key."_inmenu"]=="1") 
                             && ((user_access("view", $key)) || (in_array($key,$mapping["page_with_noauth"])))) {
                        echo "<li ";
                        if ($q==$key) echo 'class="active"';
                        echo '><a href="?q='.$key.'">';
                        echo $config[$key."_name"];
                        echo "</a></li>";
                      }                      
                    }  
                   ?>                  
                </ul>
                  <!--form class="navbar-search pull-right">
                    <input type="text" class="search-query" placeholder="Search">
                  </form-->  
              </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>    
    <div class="container-fluid" id="page">
  <?php 
    } else     
    echo '<div>';
  ?>   

   <?php if ((isset($config["site_offline"]) && ($config["site_offline"]==1))) {   echo '<div class="alert alert-info">Achtung, der Offline-Modus ist aktiv!</div>';} ?>
    