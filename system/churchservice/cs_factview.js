(function(a){function f(){ListView.call(this);this.name="FactView";this.currentDate=new Date;this.currentDate=this.currentDate.toStringDe(false).toDateDe(false);this.allDataLoaded=this.factLoaded=false}function j(){var b=a("#inputFact").parents("td.editable").attr("event_id"),d=a("#inputFact").parents("td.editable").attr("fact_id");if(b!=null&&a("#inputFact").val()!=null){if(!(a("#inputFact").val().replace(",",".")>=0)){alert("Wert muss >=0 sein!");a("#inputFact").focus();return false}if(allEvents[b].facts==
null)allEvents[b].facts={};var c={};c.fact_id=d;c.value=a("#inputFact").val().replace(",",".");allEvents[b].facts[d]=c;a("#inputFact").parent().html(c.value);churchInterface.jsendWrite({func:"saveFact",event_id:b,fact_id:d,value:c.value})}return true}Temp.prototype=ListView.prototype;f.prototype=new Temp;factView=new f;f.prototype.renderMenu=function(){this_object=this;menu=new CC_Menu("Men&uuml;");masterData.auth.write&&menu.addEntry("Neues Event anlegen","anewentry","star");menu.addEntry("Fakten exportieren",
"aexport","share");menu.addEntry("Hilfe","ahelp","question-sign");menu.renderDiv("cdb_menu",churchcore_handyformat())?a("#cdb_menu a").click(function(){if(a(this).attr("id")=="anewentry")this_object.renderAddEntry();else if(a(this).attr("id")=="aexport")churchcore_openNewWindow("?q=churchservice/exportfacts");else a(this).attr("id")=="ahelp"&&churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchService");return false}):a("#cdb_menu").hide()};f.prototype.getCountCols=function(){return 2};
f.prototype.groupingFunction=function(b){var d=b.startdate.toDateEn(false).toStringDe(),c={};a.each(allEvents,function(e,g){g.startdate.toDateEn(false).toStringDe()==d&&g.facts!=null&&a.each(g.facts,function(i,k){if(c[i]==null)c[i]=0;c[i]+=k.value*1})});var h=b.startdate.toDateEn(false).getDayInText()+", "+b.startdate.toDateEn(false).toStringDe();a.each(churchcore_sortMasterData(masterData.fact),function(e,g){h+='<td class="grouping">';if(c[g.id]!=null)h+=c[g.id]});return h};f.prototype.addFurtherListCallbacks=
function(){a("td.editable").hover(function(){a(this).addClass("active")},function(){a(this).removeClass("active")});a("td.editable").click(function(){if(j(b,d)){var b=a(this).attr("event_id"),d=a(this).attr("fact_id"),c="";if(b!=null&&allEvents[b].facts!=null&&allEvents[b].facts[d]!=null)c=allEvents[b].facts[d].value;a(this).html(form_renderInput({value:c,type:"mini",cssid:"inputFact"}));a("#inputFact").focus();a("#inputFact").keyup(function(h){if(h.keyCode==13)j(e,g);else if(h.keyCode==27){var e=
a("#inputFact").parents("td.editable").attr("event_id"),g=a("#inputFact").parents("td.editable").attr("fact_id");allEvents[e].facts!=null&&allEvents[e].facts[g]!=null?a("#inputFact").parent().html(allEvents[e].facts[g].value):a("#inputFact").remove()}})}})};f.prototype.getListHeader=function(){var b=this;if(masterData.settings.filterCategory==""||masterData.settings.filterCategory==null)delete masterData.settings.filterCategory;if(this.filter.filterKategorien==null){b.makeFilterCategories(masterData.settings.filterCategory);
this.filter.filterKategorien.setSelectedAsArrayString(masterData.settings.filterCategory)}this.filter.filterKategorien.render2Div("filterKategorien",{label:"Kategorien"});if(!this.factLoaded&&this.allDataLoaded){var d=this.showDialog("Lade Faktendaten","Lade Faktendaten...",300,300);cs_loadFacts(function(){b.factLoaded=true;d.dialog("close");b.renderList()})}var c=[];factView.listViewTableHeight=masterData.settings.listViewTableHeight==0?null:665;c.push("<th>Events");a.each(churchcore_sortData(masterData.fact,
"sortkey"),function(h,e){c.push("<th>"+e.bezeichnung)});return c.join("")};f.prototype.renderListEntry=function(b){var d=[],c=100/(1+churchcore_countObjectElements(masterData.fact));d.push('<td width="'+c+'%">'+b.startdate.toDateEn(true).toStringDeTime(true)+" "+b.bezeichnung);a.each(churchcore_sortData(masterData.fact,"sortkey"),function(h,e){d.push('<td width="'+c+'%" class="editable" event_id="'+b.id+'" fact_id="'+e.id+'">');b.facts!=null&&b.facts[e.id]!=null?d.push(b.facts[e.id].value):d.push("")});
return d.join("")};f.prototype.messageReceiver=function(b){if(b=="allDataLoaded")this.allDataLoaded=true;this==churchInterface.getCurrentView()&&b=="allDataLoaded"&&this.renderList()};f.prototype.addSecondMenu=function(){return""}})(jQuery);
