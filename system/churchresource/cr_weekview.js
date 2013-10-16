(function (g)
{
    function l()
    {
        StandardTableView.call(this);
        this.name = "WeekView";
        this.currentDate = new Date;
        this.allDataLoaded = this.printview = false;
        this.currentBooking = this.datesIndex = this.renderTimer = null
    }
    function q(c)
    {
        c.sort(function (e, b)
        {
            time_a = e.startdate.getHours() * 60 + e.startdate.getMinutes();
            time_b = b.startdate.getHours() * 60 + b.startdate.getMinutes();
            return time_a > time_b ? 1 : time_a < time_b ? -1 : 0
        });
        return c
    }
    function r(c)
    {
        txt = "";
        g.each(c, function (e, b)
        {
            if (b.category_id != null) txt = txt + '<span title="Kalender: Hauptgottesdienst" style="display:inline-block; background-color:' + masterData.category[b.category_id].color + '; margin-bottom:-2px; margin-right:4px; width:3px; height:11px"></span>';
            starttxt = "";
            if (b.startdate < b.viewing_date) starttxt = "0";
            else
            {
                starttxt = b.startdate.getHours();
                if (b.startdate.getMinutes() > 0) starttxt = starttxt + ":" + b.startdate.getMinutes()
            }
            endtxt = "";
            tomorrow = new Date(b.viewing_date.getFullYear(), b.viewing_date.getMonth(), b.viewing_date.getDate() + 1);
            if (b.enddate >= tomorrow || b.enddate.getHours() == 0 && b.enddate.getMinutes() == 0) endtxt = 24;
            else
            {
                endtxt = b.enddate.getHours();
                if (b.enddate.getMinutes() > 0) endtxt = endtxt + ":" + b.enddate.getMinutes()
            }
            color = b.status_id == 1 ? "color:red" : b.status_id == 3 ? "color:gray;text-decoration:line-through;" : b.status_id == 99 ? masterData.auth.write && b.person_id == masterData.user_pid || masterData.auth.editall ? "color:lightgray;text-decoration:line-through;" : "" : "color:black";
            ort = "";
            text = b.text.substr(0, 15);
            if (text != b.text) text += "..";
            if (color != "")
            {
                txt = !this.printview && masterData.auth.write && b.person_id == masterData.user_pid || masterData.auth.editall ? txt + '<a href="#' + b.viewing_date.toStringEn() + '" id="edit' + b.id + '" tooltip="' + b.id + '" style="font-weight:normal;' + color + '">' + starttxt + "-" + endtxt + "h " + text + ort + "</a>" : txt + '<span style="cursor:default;font-weight:normal;' + color + '" tooltip="' + b.id + '">' + starttxt + "-" + endtxt + "h " + text + ort + "</span>";
                if (b.repeat_id > 0) txt = txt + " " + weekView.renderImage("recurring", 12);
                txt += "<br/>"
            }
        });
        return txt
    }
    function s(c, e)
    {
        var b = {};
        b.resource_id = c;
        d = e == null ? new Date : e.toDateEn();
        d.setHours(12);
        b.startdate = new Date(d);
        b.enddate = new Date(d);
        b.enddate.setHours(13);
        b.enddate.setMinutes(0);
        b.person_id = masterData.user_pid;
        b.person_name = masterData.user_name;
        b.repeat_id = 0;
        b.repeat_frequence = 1;
        e = new Date;
        e.addDays(7);
        b.repeat_until = e;
        b.status_id = c == null || masterData.res[c].autoaccept_yn == 0 ? 1 : 2;
        b.text = "";
        b.neu = true;
        return b
    }
    Temp.prototype = StandardTableView.prototype;
    l.prototype = new Temp;
    weekView = new l;
    l.prototype.getData = function (c)
    {
        if (c)
        {
            var e = [];
            g.each(masterData.res, function (b, f)
            {
                e[b] = f
            });
            e.sort(function (b, f)
            {
                return masterData.resTypes[b.resourcetype_id].sortkey * 1 > masterData.resTypes[f.resourcetype_id].sortkey * 1 ? 1 : masterData.resTypes[b.resourcetype_id].sortkey * 1 < masterData.resTypes[f.resourcetype_id].sortkey * 1 ? -1 : b.sortkey * 1 > f.sortkey * 1 ? 1 : b.sortkey * 1 < f.sortkey * 1 ? -1 : 0
            });
            return e
        }
        else return masterData.res
    };
    l.prototype.renderMenu = function ()
    {
        var c = this;
        if (g("#printview").val() != null) this.printview = true;
        menu = new CC_Menu("Men&uuml;");
        if (!this.printview)
        {
            masterData.auth.write && menu.addEntry("Neue Anfrage erstellen", "anewentry", "star");
            menu.addEntry("Druckansicht", "adruckansicht", "print")
        }
        masterData.auth.admin && menu.addEntry("Stammdatenpflege", "amaintainview", "cog");
        menu.addEntry("Hilfe", "ahelp", "question-sign");
        menu.renderDiv("cdb_menu", churchcore_handyformat()) ? g("#cdb_menu a").click(function ()
        {
            if (g(this).attr("id") == "anewentry") c.showBookingDetails("new");
            else if (g(this).attr("id") == "aaddfilter")
            {
                c.furtherFilterVisible = c.furtherFilterVisible ? false : true;
                c.renderFurtherFilter()
            }
            else if (g(this).attr("id") == "amaintainview")
            {
                menuDepth = "amain";
                churchInterface.setCurrentView(maintainView)
            }
            else if (g(this).attr("id") == "adruckansicht")
            {
                fenster = window.open("?q=churchresource/printview&curdate=" + c.currentDate.toStringEn(), "Druckansicht", "width=900,height=600,resizable=yes");
                fenster.focus();
                return false
            }
            else if (g(this).attr("id") == "amain")
            {
                menuDepth = "amain";
                c.renderMenu()
            }
            else g(this).attr("id") == "ahelp" && churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchResource");
            return false
        }) : g("#cdb_menu").hide()
    };
    l.prototype.renderListMenu = function ()
    {
        var c = this;
        searchEntry = this.getFilter("searchEntry");
        var e = new CC_Navi;
        g.each(masterData.resTypes, function (b, f)
        {
            e.addEntry(c.filter["filterRessourcen-Typ"] == f.id, "ressourcentyp_" + f.id, f.bezeichnung)
        });
        e.addSearch(searchEntry);
        e.renderDiv("cdb_search", churchcore_handyformat());
        this.implantStandardFilterCallbacks(this, "cdb_search");
        g("#cdb_search a").click(function ()
        {
            c.filter["filterRessourcen-Typ"] = g(this).attr("id").substr(14, 99);
            masterData.settings.filterRessourcentyp = c.getFilter("filterRessourcen-Typ");
            churchInterface.jsonWrite(
            {
                func: "saveSetting",
                sub: "filterRessourcentyp",
                val: c.getFilter("filterRessourcen-Typ")
            });
            c.renderView()
        })
    };
    l.prototype.renderFilter = function ()
    {
        var c = [],
            e = new CC_Form;
        e.setHelp("ChurchResource-Filter");
        e.addHtml('<div id="dp_currentdate"></div>');
        c.push('<div id="dp_currentdate" style=""></div><br/>');
        e.addSelect(
        {
            data: masterData.status,
            label: "Status",
            selected: this.filter.filterStatus,
            cssid: "filterStatus",
            freeoption: true,
            type: "medium"
        });
        c.push(e.render(true));
        c.push('<div id="cdb_filtercover"></div>');
        g("#cdb_filter").html(c.join(""));
        g.each(this.filter, function (b, f)
        {
            g("#" + b).val(f)
        });
        this.renderCalender();
        filter = this.filter;
        this.implantStandardFilterCallbacks(this, "cdb_filter")
    };
    l.prototype.renderCalender = function ()
    {
        var c = this;
        g("#dp_currentdate").datepicker(
        {
            dateFormat: "dd.mm.yy",
            showButtonPanel: true,
            dayNamesMin: dayNamesMin,
            monthNames: monthNames,
            currentText: "Heute",
            defaultDate: c.currentDate,
            firstDay: 1,
            onSelect: function (e)
            {
                c.currentDate = e.toDateDe();
                c.renderList()
            },
            onChangeMonthYear: function (e, b)
            {
                var f = new Date;
                if (c.allDataLoaded)
                {
                    c.currentDate = f.getFullYear() == e && f.getMonth() + 1 == b ? f : new Date(e, b - 1);
                    g("#dp_currentdate").datepicker("setDate", c.currentDate);
                    c.renderTimer != null && window.clearTimeout(c.renderTimer);
                    c.renderTimer = window.setTimeout(function ()
                    {
                        c.renderTimer = null;
                        c.renderList()
                    }, 150)
                }
            }
        });
        g("#dp_currentdate").datepicker(g.datepicker.regional.de)
    };
    l.prototype.checkFilter = function (c)
    {
        var e = this.filter;
        if (c == null) return false;
        if (e["filterRessourcen-Typ"] != null && e["filterRessourcen-Typ"] != "" && c.resourcetype_id != e["filterRessourcen-Typ"]) return false;
        return true
    };
    l.prototype.messageReceiver = function (c, e)
    {
        var b = this;
        if (this == churchInterface.getCurrentView()) if (c == "allDataLoaded")
        {
            b.buildDates(allBookings);
            if (g("#filter_id").val() != null && allBookings[g("#filter_id").val()] != null)
            {
                this.currentDate = new Date(allBookings[g("#filter_id").val()].startdate);
                this.showBookingDetails("edit", g("#filter_id").val());
                g("#filter_id").remove()
            }
            if (g("#curdate").val() != null)
            {
                this.currentDate = g("#curdate").val().toDateEn();
                g("#curdate").remove()
            }
            this.renderView();
            this.allDataLoaded = true
        }
        else if (c == "filterChanged")
        {
            if (e[0] == "filterRessourcen-Typ")
            {
                masterData.settings.filterRessourcentyp = this.getFilter("filterRessourcen-Typ");
                churchInterface.jsonWrite(
                {
                    func: "saveSetting",
                    sub: "filterRessourcentyp",
                    val: this.getFilter("filterRessourcen-Typ")
                })
            }
        }
        else if (c == "pollForNews")
        {
            var f = false;
            e != null && g.each(e, function (n, h)
            {
                if (h.id != null) f = true
            });
            if (f)
            {
                var j = form_showCancelDialog("Achtung...", "Lade aktuelle Daten nach!");
                cr_loadBookings(function ()
                {
                    j.dialog("close");
                    b.renderList()
                })
            }
        }
        else alert("Message " + c + " unbekannt!")
    };
    l.prototype.initView = function ()
    {
        if (masterData.settings.filterRessourcentyp == null) masterData.settings.filterRessourcentyp = 1;
        this.filter["filterRessourcen-Typ"] = masterData.settings.filterRessourcentyp
    };
    l.prototype.getListHeader = function ()
    {
        var c = [],
            e = new Date(this.currentDate),
            b = -e.getDay() + 1;
        if (b == 1) b = -6;
        e.addDays(b);
        e = new Date(e.getFullYear(), e.getMonth(), e.getDate() - 1);
        c.push("<th>");
        e.addDays(1);
        c.push("KW" + e.getKW());
        e.addDays(-1);
        b = new Date(e.getFullYear(), e.getMonth(), e.getDate());
        for (i = 0; i < 7; i++)
        {
            b.addDays(1);
            e = "";
            if (b.toStringDe() == this.currentDate.toStringDe()) e = "active";
            c.push('<th class="' + e + '">' + dayNamesMin[b.getDay()] + ", " + b.toStringDe() + "")
        }
        c.push("");
        return c.join("")
    };
    l.prototype.buildDates = function (c)
    {
        var e = this;
        e.datesIndex = {};
        c != null && g.each(c, function (b, f)
        {
            f != null && g.each(churchcore_getAllDatesWithRepeats(f), function (j, n)
            {
                for (j = new Date(n.startdate); j.toStringEn(false) <= n.enddate.toStringEn(false);)
                {
                    var h = e.datesIndex[j.getFullYear()];
                    if (h == null) h = [];
                    var k = h[j.getMonth() + 1];
                    if (k == null) k = [];
                    var m = k[j.getDate()];
                    if (m == null) m = [];
                    n.id = f.id;
                    m.push(n);
                    k[j.getDate()] = m;
                    h[j.getMonth() + 1] = k;
                    e.datesIndex[j.getFullYear()] = h;
                    j.addDays(1)
                }
            })
        })
    };
    l.prototype.getIndexedBookings = function (c)
    {
        if (this.datesIndex == null || this.datesIndex[c.getFullYear()] == null || this.datesIndex[c.getFullYear()][c.getMonth() + 1] == null || this.datesIndex[c.getFullYear()][c.getMonth() + 1][c.getDate()] == null) return [];
        return this.datesIndex[c.getFullYear()][c.getMonth() + 1][c.getDate()]
    };
    l.prototype.getBookings = function (c, e)
    {
        var b = this,
            f = [],
            j = new Date(e.getFullYear(), e.getMonth(), e.getDate() + 1),
            n = new Date(j.getTime() - 1),
            h = this.getFilter("searchEntry").toUpperCase();
        g.each(b.getIndexedBookings(e), function (k, m)
        {
            k = g.extend(
            {}, allBookings[m.id]);
            if (k != null && (!this.printview || k.status_id == 2)) if (churchcore_datesInConflict(m.startdate, m.enddate, e, n))
            {
                filterOk = true;
                if (h != "" && ar.text.toUpperCase().indexOf(h) != 0 && k.person_name.toUpperCase().indexOf(h) != 0 && k.id != h) filterOk = false;
                if (b.filter.filterStatus != null && k.status_id != b.filter.filterStatus) filterOk = false;
                if (k.resource_id == c && filterOk)
                {
                    k.startdate = new Date(m.startdate);
                    k.enddate = new Date(k.enddate);
                    k.viewing_date = e;
                    f.push(k)
                }
            }
        });
        return f
    };
    l.prototype.renderTooltip = function (c)
    {
        c = c.attr("tooltip");
        a = allBookings[c];
        txt = "";
        txt += '<table class="table table-condensed">';
        if (a.category_id != null)
        {
            txt += "<tr><td>Kalender<td>";
            txt = txt + '<span title="Kalender: Hauptgottesdienst" style="display:inline-block; background-color:' + masterData.category[a.category_id].color + '; margin-bottom:-2px; margin-right:4px; width:3px; height:11px"></span>';
            txt = txt + "<b>" + churchcore_getBezeichnung("category", a.category_id) + "</b>"
        }
        txt = txt + "<tr><td>Start<td>" + a.startdate.toStringDe(true);
        txt = txt + "<tr><td>Ende<td>" + a.enddate.toStringDe(true);
        if (a.location != "") txt = txt + "<tr><td>Ort<td>" + a.location;
        if (a.repeat_id != 0)
        {
            txt += "<tr><td>Wiederholung<td>";
            if (a.repeat_frequence > 1) txt = txt + a.repeat_frequence + " ";
            txt += masterData.repeat[a.repeat_id] != null ? masterData.repeat[a.repeat_id].bezeichnung : "id:" + a.repeat_id;
            if (a.repeat_id != 999) txt = txt + "<br/>bis " + a.repeat_until.toStringDe()
        }
        else txt += "<tr><td>Wiederholung<td>-";
        txt = txt + "<tr><td>Status<td><b>" + masterData.status[a.status_id].bezeichnung + "</b>";
        txt = txt + "<tr><td>Ersteller<td>" + a.person_name;
        if (a.note != "") txt = txt + "<tr><td>Notiz<td>" + a.note;
        txt += "</table>";
        title = a.text;
        if (masterData.auth.editall || masterData.auth.write && a.person_id == masterData.user_pid) if (a.status_id == 1 || a.status_id == 2 || a.status_id == 3)
        {
            title += '<span class="pull-right">';
            if (a.status_id == 1)
            {
                title = title + form_renderImage(
                {
                    label: "Best\u00e4tigen",
                    cssid: "confirm",
                    src: "check-64.png",
                    width: 20
                }) + "&nbsp; ";
                title = title + form_renderImage(
                {
                    label: "Ablehnen",
                    cssid: "deny",
                    src: "delete_2.png",
                    width: 20
                }) + "&nbsp; "
            }
            if (a.status_id != 3) title = title + form_renderImage(
            {
                label: "Kopieren",
                cssid: "copy",
                src: "copy.png",
                width: 20
            }) + "&nbsp; ";
            if (a.status_id != 1) title = title + form_renderImage(
            {
                label: "L\u00f6schen",
                cssid: "delete",
                src: "trashbox.png",
                width: 20
            }) + "&nbsp; ";
            title += "</span>"
        }
        return [txt, title]
    };
    l.prototype.updateBookingStatus = function (c, e)
    {
        t.clearTooltip(true);
        var b = allBookings[c].status_id;
        allBookings[c].status_id = e;
        allBookings[c].func = "updateBooking";
        churchInterface.jsendWrite(a, function (f)
        {
            if (!f) allBookings[c].status_id = b;
            t.renderList()
        }, false, false);
        t.renderList()
    };
    l.prototype.tooltipCallback = function (c, e)
    {
        var b = this;
        e.find("#copy").click(function ()
        {
            b.clearTooltip(true);
            b.showBookingDetails("copy", c);
            return false
        });
        e.find("#delete").click(function ()
        {
            b.updateBookingStatus(c, 99);
            return false
        });
        e.find("#confirm").click(function ()
        {
            b.updateBookingStatus(c, 2);
            return false
        });
        e.find("#deny").click(function ()
        {
            b.updateBookingStatus(c, 3);
            return false
        })
    };
    l.prototype.groupingFunction = function (c)
    {
        return masterData.resTypes[c.resourcetype_id].bezeichnung
    };
    l.prototype.getCountCols = function ()
    {
        return 9
    };
    l.prototype.renderListEntry = function (c)
    {
        var e = [];
        e.push("<td><p><b>" + c.bezeichnung + "</b>");
        c.location != null && e.push('<br/><small><font color="grey">' + c.location + "</color></small>");
        var b = new Date(this.currentDate),
            f = -b.getDay() + 1;
        if (f == 1) f = -6;
        b.addDays(f);
        b = new Date(b.getFullYear(), b.getMonth(), b.getDate() - 1);
        for (i = 0; i < 7; i++)
        {
            b.addDays(1);
            f = "";
            if (b.toStringDe() == t.currentDate.toStringDe()) f = "active";
            e.push('<td valign="top" class="' + f + '"><p><small>');
            bookings = this.getBookings(c.id, b);
            bookings = q(bookings);
            e.push(r(bookings));
            e.push("</small>");
            masterData.auth.write && !this.printview && e.push('<a href="#' + b.toStringEn() + '" id="new_' + c.id + '">+</a>')
        }
        return e.join("")
    };
    l.prototype.renderEditBookingFields = function (c)
    {
        var e = [];
        e.push('<div id="cr_fields" data-id="' + c.id + '">');
        e.push('<br/><form class="form-horizontal" >');
        e.push(form_renderInput(
        {
            cssid: "text",
            label: "Dienstbereich",
            value: c.text,
            disabled: c.cc_cal_id != null
        }));
        e.push(form_renderInput(
        {
            cssid: "location",
            label: "Ort",
            value: c.location
        }));
        e.push(form_renderSelect(
        {
            data: masterData.res,
            cssid: "InputRessource",
            label: "Ressource",
            htmlclass: "input-medium",
            selected: c.resource_id,
            disabled: c.cc_cal_id != null
        }));
        e.push('<div id="dates"></div>');
        e.push('<div id="wiederholungen"></div>');
        e.push('<div id="conflicts"></div>');
        e.push(form_renderSelect(
        {
            data: masterData.status,
            cssid: "InputStatus",
            label: "Status",
            selected: c.status_id,
            disabled: !masterData.auth.editall
        }));
        e.push(form_renderTextarea(
        {
            data: c.note,
            label: "Weitere Infos",
            cssid: "inputNote",
            rows: 2,
            cols: 150
        }));
        e.push("</form>");
        c.id != null && e.push("<i>Buchungsanfrage " + c.id + " wurde erstellt von " + c.person_name + "</a></i><br/>");
        e.push("</div>");
        return e.join("")
    };
    l.prototype.implantEditBookingCallbacks = function (c)
    {
        function e()
        {
            var f = g("#InputRessource").val();
            if (f != null && masterData.res[f] != null) if (masterData.res[f].autoaccept_yn == 0 && !masterData.auth.editall) g("#" + c + " select[id=InputStatus]").val(1);
            else masterData.res[f].autoaccept_yn == 1 && g("#" + c + " select[id=InputStatus]").val(2)
        }
        var b = this;
        e();
        g("#" + c + " select").change(function ()
        {
            g(this).attr("id") == "InputRessource" ? e() : b.checkConflicts()
        });
        g("#" + c + " input").keyup(function ()
        {
            b.checkConflicts()
        });
        g("#" + c + " input").click(function ()
        {
            b.checkConflicts()
        })
    };
    l.prototype.checkConflicts = function ()
    {
        var c = this,
            e = g("#cr_fields").attr("data-id"),
            b = e != null && allBookings[e] != null ? g.extend(
            {}, allBookings[e]) : {};
        form_getDatesInToObject(b);
        var f = g("#InputRessource").val(),
            j = [];
        b.enddate.getTime();
        b.startdate.getTime();
        g.each(churchcore_getAllDatesWithRepeats(b), function (n, h)
        {
            g.each(c.getIndexedBookings(h.startdate), function (k, m)
            {
                k = allBookings[m.id];
                if (k != null && k.resource_id == f && b.id != k.id) if (k.status_id == 1 || k.status_id == 2) churchcore_datesInConflict(h.startdate, h.enddate, m.startdate, m.enddate) && j.push("<li>" + h.startdate.toStringDe(true) + " - " + h.enddate.toStringDe(true) + ": " + k.text)
            })
        });
        if (j.join("") != "")
        {
            g("#conflicts").html('<p class="text-error">Achtung, Termin-Konflikte: <p class="text-error"><div id="show_conflicts"><ul>' + j.join("") + "</div>");
            g("#conflicts").addClass("well")
        }
        else
        {
            g("#conflicts").html("");
            g("#conflicts").removeClass("well")
        }
    };
    l.prototype.closeAndSaveBookingDetail = function (c)
    {
        var e = this,
            b = e.currentBooking;
        b.repeat_frequence = 1;
        form_getDatesInToObject(b);
        if (b.enddate < b.startdate)
        {
            alert("Das Enddatum liegt vor dem Startdatum, bitte korrigieren!");
            return null
        }
        b.resource_id = g("select[id=InputRessource]").val();
        b.status_id = g("select[id=InputStatus]").val();
        b.text = g("input[id=text]").val().trim();
        b.location = g("input[id=location]").val().trim();
        b.show_in_churchcal_yn = g("input[id=showinchurchcal]").attr("checked") == "checked" ? 1 : 0;
        b.note = g("#inputNote").val().trim();
        if (g("#show_conflicts").html() != null) b.conflicts = g("#show_conflicts").html();
        b.startdate = b.startdate.toStringEn(true);
        b.enddate = b.enddate.toStringEn(true);
        if (b.repeat_until != null) b.repeat_until = b.repeat_until.toStringEn(false);
        b.neu = false;
        if (b.text == "")
        {
            alert("Bitte eine Bezeichnung angeben!");
            return null
        }
        g("#cr_fields").html("<br/>Daten werden gespeichert..<br/><br/>");
        churchInterface.jsendWrite(b, function (f, j)
        {
            b.startdate = b.startdate.toDateEn();
            b.enddate = b.enddate.toDateEn();
            if (b.repeat_until != null) b.repeat_until = b.repeat_until.toDateEn();
            c.empty().remove();
            if (f)
            {
                if (j.id != null)
                {
                    b.id = j.id;
                    allBookings[j.id] = b
                }
                else if (b.id != null) allBookings[b.id] = b;
                j.exceptions != null && g.each(j.exceptions, function (n, h)
                {
                    allBookings[b.id].exceptions[h] = allBookings[b.id].exceptions[n];
                    allBookings[b.id].exceptions[h].id = h;
                    delete allBookings[b.id].exceptions[n]
                });
                e.buildDates(allBookings);
                e.renderList()
            }
            else alert("Fehler beim Speichern: " + j)
        }, false, false)
    };
    l.prototype.addFurtherListCallbacks = function ()
    {
        var c = this;
        g("#cdb_content a").click(function ()
        {
            id = g(this).attr("id").substr(4, 99);
            date = g(this).attr("href").substr(1, 99);
            if (g(this).attr("id").indexOf("edit") == 0) c.showBookingDetails("edit", id, date);
            else g(this).attr("id").indexOf("new_") == 0 && c.showBookingDetails("new", id, date)
        })
    };
    l.prototype.cloneBooking = function (c)
    {
        var e = g.extend(
        {}, c);
        e.startdate = new Date(c.startdate);
        e.enddate = new Date(c.enddate);
        e.repeat_until = new Date(c.repeat_until);
        e.id = null;
        return e
    };
    l.prototype.showBookingDetails = function (c, e, b)
    {
        var f = this,
            j = "",
            n = "";
        if (c == "edit")
        {
            f.currentBooking = g.extend(
            {}, allBookings[e]);
            if (f.currentBooking.cc_cal_id != null) n = n + '<div class="alert alert-error">Achtung: Die Buchung wurde in <a href="?q=churchcal&date=' + f.currentBooking.startdate.toStringEn(false) + '">' + masterData.churchcal_name + "</a> erstellt. Datum und Bezeichnung bitte im Kalendereintrag bearbeiten. Die Raumbuchung passt sich dementsprechend an.</div>";
            if (!masterData.auth.editall && masterData.auth.write && f.currentBooking.person_id == masterData.user_pid && f.currentBooking.status_id != 1 && masterData.res[f.currentBooking.resource_id].autoaccept_yn == 0)
            {
                n += "<i><div style=\"background:white\"><b>Achtung: Die Anfrage wurde schon vom Administrator bearbeitet!</b><br/>Wenn nun 'Speichern' gww&auml;hlt, muss erneut der Administrator best&auml;tigen.</i></div><br/><br/>";
                f.currentBooking.status_id = 1
            }
            n += f.renderEditBookingFields(f.currentBooking);
            n += '<div id="cr_logs" style=""></div>';
            j = masterData.auth.write && f.currentBooking.person_id == masterData.user_pid || masterData.auth.editall ? "Editiere Buchungsanfrage" : "Anzeige der Buchungsanfrage";
            f.currentBooking.func = "updateBooking";
            if (f.currentBooking.exceptionids == null) f.currentBooking.exceptionids = 0
        }
        else if (c == "new")
        {
            f.currentBooking = s(e, b);
            n = f.renderEditBookingFields(f.currentBooking);
            j = "Buchungsanfrage erstellen";
            f.currentBooking.func = "createBooking";
            f.currentBooking.exceptionids = 0
        }
        else if (c == "copy")
        {
            f.currentBooking = f.cloneBooking(allBookings[e]);
            n = f.renderEditBookingFields(a);
            j = "Buchungsanfrage kopieren";
            f.currentBooking.func = "createBooking";
            f.currentBooking.exceptionids = 0
        }
        else alert("unkown function in showBookingDetails");
        if (j != "")
        {
            var h = this.showDialog(j, n, 600, 640, {});
            masterData.auth.editall ? form_renderDates(
            {
                elem: g("#dates"),
                data: f.currentBooking,
                disabled: f.currentBooking.cc_cal_id != null,
                authexceptions: masterData.auth.editall,
                authadditions: masterData.auth.editall,
                deleteException: function (m)
                {
                    delete f.currentBooking.exceptions[m.id]
                },
                addException: function (m, p)
                {
                    if (f.currentBooking.exceptions == null) f.currentBooking.exceptions = {};
                    f.currentBooking.exceptionids -= 1;
                    f.currentBooking.exceptions[f.currentBooking.exceptionids] = {
                        id: f.currentBooking.exceptionids,
                        except_date_start: p.toDateDe().toStringEn(),
                        except_date_end: p.toDateDe().toStringEn()
                    };
                    return f.currentBooking
                },
                deleteAddition: function (m)
                {
                    delete f.currentBooking.additions[m.id]
                },
                addAddition: function (m, p, o)
                {
                    if (f.currentBooking.additions == null) f.currentBooking.additions = {};
                    f.currentBooking.exceptionids -= 1;
                    f.currentBooking.additions[f.currentBooking.exceptionids] = {
                        id: f.currentBooking.exceptionids,
                        add_date: p.toDateDe().toStringEn(),
                        with_repeat_yn: o
                    };
                    return f.currentBooking
                }
            }) : form_renderDates(
            {
                elem: g("#dates"),
                data: f.currentBooking
            });
            this.implantEditBookingCallbacks("cr_fields", allBookings[e]);
            this.checkConflicts();
            var k = g("#cr_logs");
            k != null && g.getJSON("index.php?q=churchresource/ajax", {
                func: "getLogs",
                id: e
            }, function (m)
            {
                if (m != null)
                {
                    logs = '<small><font style="line-height:100%;"><a href="#" id="toogleLogs">Historie >></a><br/></small>';
                    logs += '<div id="cr_logs_detail" style="display: none; border: 1px solid white; height: 140px; overflow: auto; margin: 2px; padding: 2px;">';
                    logs += "<small><table><tr><td>Historie<td>Beschreibung<td>Erfolgt durch";
                    g.each(m, function (p, o)
                    {
                        logs = logs + '<tr><td width="100px">' + o.datum.toDateEn().toStringDe(true) + "<td>" + o.txt + '<td width="80px">' + o.person_name + " [" + o.person_id + "]<br/>"
                    });
                    logs += "</table>";
                    logs += "</small>";
                    if (masterData.auth.editall) logs += '<small><p align="right"><a href="#" id="del_complete"><i>(Admin: Gesamten Termin l&ouml;schen)</a></small>';
                    logs += "</div>";
                    k.html(logs);
                    g("#cr_logs a").click(function ()
                    {
                        if (g(this).attr("id") == "del_complete" && masterData.auth.editall) confirm("Soll der Termin wirklich entfernt werden? Achtung, man kann es nicht mehr wiederherstellen!") && g.getJSON("index.php?q=churchresource/ajax", {
                            func: "delBooking",
                            id: e
                        }, function ()
                        {
                            allBookings[e] = null;
                            g("#cr_cover").html("");
                            f.renderList();
                            h.dialog("close")
                        });
                        else g("#cr_logs_detail").animate(
                        {
                            height: "toggle"
                        }, "fast");
                        return false
                    })
                }
            });
            if (c == "delete") f.closeAndSaveBookingDetail(h);
            else
            {
                if (masterData.auth.write && f.currentBooking.person_id == masterData.user_pid || masterData.auth.editall || f.currentBooking.neu)
                {
                    h.dialog("addbutton", "Speichern", function ()
                    {
                        f.closeAndSaveBookingDetail(h)
                    });
                    if (f.currentBooking.status_id != 99 && !f.currentBooking.neu) if (f.currentBooking.repeat_id > 0 && b != null && b.toDateEn() > f.currentBooking.startdate)
                    {
                        f.currentBooking.repeat_id != 999 && h.dialog("addbutton", "Nur aktuellen Termin l\u00f6schen", function ()
                        {
                            if (f.currentBooking.exceptions == null) f.currentBooking.exceptions = {};
                            f.currentBooking.exceptionids -= 1;
                            f.currentBooking.exceptions[f.currentBooking.exceptionids] = {
                                id: f.currentBooking.exceptionids,
                                except_date_start: b,
                                except_date_end: b
                            };
                            f.closeAndSaveBookingDetail(h)
                        });
                        f.currentBooking.repeat_id != 999 && h.dialog("addbutton", "Diesen und nachfolgende l\u00f6schen", function ()
                        {
                            d = b.toDateEn();
                            d.addDays(-1);
                            g("#cr_fields input[id=inputRepeatUntil]").val(d.toStringDe());
                            f.closeAndSaveBookingDetail(h)
                        })
                    }
                    else
                    {
                        j = "L\u00f6schen";
                        if (f.currentBooking.repeat_id > 0) j = "Gesamte Serie l\u00f6schen";
                        h.dialog("addbutton", j, function ()
                        {
                            g("select[id=InputStatus]").val(99);
                            f.closeAndSaveBookingDetail(h)
                        })
                    }
                }
                h.dialog("addbutton", "Abbrechen", function ()
                {
                    h.dialog("close")
                })
            }
        }
    };
    l.prototype.renderEntryDetail = function ()
    {}
})(jQuery);

