(function(a){function b(){StandardTableView.call(this);this.name="MaintainView"}Temp.prototype=MaintainStandardView.prototype;b.prototype=new Temp;maintainView=new b;b.prototype.renderMenu=function(){this_object=this;if(masterData.cdb_gruppen==null){var c=form_showCancelDialog("Masterdaten werden geladen...","Bitte warten..");churchInterface.jsonRead({func:"getChurchDBMasterData"},function(d){a.each(d,function(e,f){masterData[e]=f});c.dialog("close");this_object.renderList()})}menu=new CC_Menu("Men&uuml;");
menu.addEntry("Zur&uuml;ck zur Liste","apersonview","arrow-left");menu.addEntry("Hilfe","ahelp","question-sign");menu.renderDiv("cdb_menu")?a("#cdb_menu a").click(function(){if(a(this).attr("id")=="apersonview")churchInterface.setCurrentView(listView,false);else a(this).attr("id")=="ahelp"&&churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService-Stammdaten");return false}):a("#cdb_menu").hide()}})(jQuery);
