(function(e){function c(){ListView.call(this);this.name="CalView";this.currentDate=new Date;this.printview=false}Temp.prototype=ListView.prototype;c.prototype=new Temp;calView=new c;c.prototype.shiftCurrentDate=function(){this.currentDate.addDays(-this.currentDate.getDay()+1);this.currentDate=new Date(this.currentDate.getFullYear(),this.currentDate.getMonth(),this.currentDate.getDate()-1)};var n=new Array("So","Mo","Di","Mi","Do","Fr","Sa");c.prototype.getData=function(){var a=[];e.each(churchcore_sortData(masterData.servicegroup,
"bezeichnung"),function(b,h){var l={};e.each(masterData.service,function(f,j){j.servicegroup_id==h.id&&j.cdb_gruppen_ids!=null&&e.each(j.cdb_gruppen_ids.split(","),function(o,m){groups!=null&&groups[m]!=null&&e.each(groups[m],function(k,g){if(l[g.p_id]==null&&allPersons[g.p_id]!=null){k={};k.servicegroup_id=h.id;k.group=g;k.name=g.name+" "+g.vorname;l[g.p_id]=k}})})});e.each(churchcore_sortData(l,"name"),function(f,j){a.push(j)})});return a};c.prototype.checkFilter=function(a){if(allPersons[a.group.p_id]!=
null&&allPersons[a.group.p_id].absent!=null){if(this.filter.filterDienstgruppen!=null)if(a.servicegroup_id!=this.filter.filterDienstgruppen)return false;return true}return false};c.prototype.groupingFunction=function(a){return masterData.servicegroup[a.servicegroup_id].bezeichnung};c.prototype.getCountCols=function(){return 9};c.prototype.getListHeader=function(){var a=[];this.shiftCurrentDate();var b=this.currentDate;a.push('<th style="min-width:100px">');b.addDays(1);a.push("KW"+b.getKW());b.addDays(-1);
d=new Date(b.getFullYear(),b.getMonth(),b.getDate());for(i=0;i<7;i++){d.addDays(1);a.push("<th>"+n[d.getDay()]+", "+d.toStringDe()+"")}a.push("");return a.join("")};c.prototype.renderListEntry=function(a){var b=[];b.push("<td><b>"+a.group.vorname+" "+a.group.name+"</b>");d=new Date(this.currentDate.getFullYear(),this.currentDate.getMonth(),this.currentDate.getDate());for(i=0;i<7;i++){b.push('<td style="min-width:40px">');d.addDays(1);var h=allPersons[a.group.p_id];h.absent!=null&&e.each(h.absent,
function(l,f){f!=null&&f.startdate<=d&&f.enddate>=d&&b.push(masterData.absent_reason[f.absent_reason_id].bezeichnung+" ")})}return b.join("")};c.prototype.messageReceiver=function(a){this==churchInterface.getCurrentView()&&a=="allDataLoaded"&&this.renderList()}})(jQuery);
