
//var fc = $.eventCalendar = { version: "1.0.0" };

$.fn.eventCalendar = function(options) {

  // method calling
  if (typeof options == 'string') {
    var args = Array.prototype.slice.call(arguments, 1);
    var res;
    this.each(function() {
      var calendar = $.data(this, 'eventCalendar');
      if (calendar && $.isFunction(calendar[options])) {
        var r = calendar[options].apply(calendar, args);
        if (res === undefined) {
          res = r;
        }
        if (options == 'destroy') {
          $.removeData(this, 'eventCalendar');
        }
      }
      else alert("no function: "+options)
    });
    if (res !== undefined) {
      return res;
    }
    return this;
  }
    
  this.each(function(i, _element) {
    var element = $(_element);
    var calendar = new eventCalendar(element, options);
    element.data('eventCalendar', calendar); // TODO: look into memory leak implications
    //calendar.render();
  });  
  return this;
};


function eventCalendar(element, options, eventSources) {
  var t = this;
  var timer=null;
  t.render=render;
  t.addEventSource=addEventSource;
  t.removeEventSource=removeEventSource;
  t.startdate=new Date();
  t.startdate=t.startdate.withoutTime();
  
  t.eventSources=new Array();
  
  function addEventSource(s) {
    t.eventSources.push(s);
    t.render();
  }
  function removeEventSource(s) {
    $.each(t.eventSources, function(k,a) {
      if (a==s) delete t.eventSources[k];
    });
    t.render();
  }

  function render() {
    if (timer!=null) window.clearTimeout(timer);
    timer=window.setTimeout(function() {
      renderCalendar();
      var d2 = new Date();
      var d = new Date(d2.getFullYear()+"");  
      var year=d.getFullYear();  
      var rows = new Array();  
      
      var allData= new Array();
      $.each(t.eventSources, function(k,s) {
        if (s!=null) {
          $.each(s.events, function(i,event) {
            event.container=s.container;
            event.category_id=s.category_id;
            allData.push(event);
          });
        }
      });
      
      rows.push('<table class="table table-condensed">');
      var _filter=filterName.toUpperCase();
      var count=0;
      $.each(churchcore_sortData(allData, "start"), function(k,a) {
        if (a.start>=t.startdate) {
          if ((filterName=="") || (a.title.toUpperCase().indexOf(_filter)>=0)) {
            rows.push('<tr><td>');
            
            if (!minical) {
              if ((a.notizen!=null) || (a.link!=null)) 
                rows.push('<a href="#" class="event" data-id="'+k+'">'+a.title+'</a>');
              else
                rows.push(a.title);
              rows.push(' ('+a.container.getName(a.category_id)+')<br><p><small>');
              rows.push(a.start.toStringDe(!a.allDay));
              if (a.end!=null) {
                if (a.end.getDate()!=a.start.getDate()) {
                  rows.push(" - "+a.end.toStringDe(!a.allDay));
                }
                else 
                  rows.push(" - "+a.end.toStringDeTime());
              }
              rows.push('</small>');
            }
            else {
              rows.push('<p><small>');
              rows.push(a.start.toStringDe(!a.allDay));
              if (a.end!=null) {
                if (a.end.getDate()!=a.start.getDate()) {
                  rows.push(" - "+a.end.toStringDe(!a.allDay));
                }
                else 
                  rows.push(" - "+a.end.toStringDeTime());
              }
              rows.push('</small><br>');
              if (a.link!=null) 
                rows.push('<a href="'+a.link+'">'+a.title+'</a>');
              else
                rows.push(a.title);
              if (a.notizen!=null) rows.push("<br><small>"+a.notizen.trim(100)+'</small>');
              
            }
            

            var notizen="";
            if ((a.notizen!=null) && (a.notizen!="")) {
              notizen=a.notizen.replace(/(http:\/\/\S*)/g, '<a target="_clean" href="$1">$1<\/a>');
              notizen=notizen.replace(/(https:\/\/\S*)/g, '<a target="_clean" href="$1">$1<\/a>');
              notizen=notizen.replace(/\n/g, '<br/>');
            }
            
            if ((a.link!=null) && (a.link!="")) {
              if (notizen!="") notizen=notizen+'<br><br>';
              notizen=notizen+'<a class="btn" href="'+a.link+'" '+(embedded?"":'target="_clean"')+'>Weitere Informationen &raquo;</a>'; 
            }
            
            rows.push('<div style="display:none" id="entry'+k+'"><div class="well">'+notizen+'</div></div>');
            count++;
            if (count>=max_entries) return false;
          }
        }
      });
      if ((count>max_entries) && (!minical)) rows.push("<tr><td>...");
      rows.push('</table>');
      if (count==0) rows.push("Keine Eintr&auml;ge gefunden.");
      
      $("#calendar").html(rows.join(""));  
      
      $("#calendar a.event").click(function() {
        var id=$(this).attr("data-id");
        var elem=$("#entry"+id);
        elem.animate({ height: 'toggle'}, "fast");
        return false;
      });
      
      
      timer=null;
    },50);
  }

  function renderCalendar() {
    $("#minicalendar").datepicker({
      dateFormat: 'dd.mm.yy',
      showButtonPanel: true,
      dayNamesMin: dayNamesMin,
      monthNames: monthNames, 
      currentText: "Heute",
      firstDay: 1,
      onSelect : function(dateText, inst) {
        t.startdate=dateText.toDateDe();
        if (embedded)  $("#minicalendar").hide();
        render();
      },
      onChangeMonthYear:function(year, month, inst) {
        var dt = new Date();
        // Wenn es der aktuelle Monat ist, dann gehe auf den heutigen Tag
        if ((dt.getFullYear()==year) && (dt.getMonth()+1==month))
          t.startdate=dt;
        else
          t.startdate=new Date(year, month-1);
        render();
      }
      
    });    
    $("#minicalendar").datepicker($.datepicker.regional['de']);
    //$("#minicalendar").datepicker('setDate', t.startDate.toStringDe());
    shortcut.add("esc", function() {
      $("#minicalendar").hide();
    });  

  };

}
