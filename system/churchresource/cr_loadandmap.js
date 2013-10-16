function cdb_loadMasterData(a)
{
    churchInterface.setStatus("Lade Kennzeichen...");
    timers.startMasterdata = new Date;
    jQuery.getJSON("index.php?q=churchresource/ajax", {
        func: "getMasterData"
    }, function (b)
    {
        timers.endMasterdata = new Date;
        masterData = b;
        b.version != churchresource_js_version && alert("Achtung, Versionen unterscheiden sich, bitte Cache loeschen! php:" + b.version + "/js:" + churchresource_js_version);
        churchInterface.clearStatus();
        a != null && a()
    })
}

function cr_mapJsonBookings(a)
{
    booking = {};
    booking.id = a.id;
    booking.resource_id = a.resource_id;
    booking.person_id = a.person_id;
    booking.person_name = a.person_name;
    booking.startdate = a.startdate.toDateEn();
    booking.enddate = a.enddate.toDateEn();
    booking.repeat_id = a.repeat_id;
    booking.repeat_frequence = a.repeat_frequence;
    booking.repeat_until = a.repeat_until;
    if (a.repeat_until != null) booking.repeat_until = a.repeat_until.toDateEn();
    booking.repeat_option_id = a.repeat_option_id;
    booking.status_id = a.status_id;
    booking.text = a.text;
    booking.location = a.location;
    booking.note = a.note;
    booking.exceptions = a.exceptions;
    booking.additions = a.additions;
    booking.show_in_churchcal_yn = a.show_in_churchcal_yn;
    booking.cc_cal_id = a.cc_cal_id;
    booking.category_id = a.category_id;
    return booking
}

function cr_loadBookings(a)
{
    churchInterface.setStatus("Lade Buchungen...");
    timers.startAllPersons = new Date;
    churchInterface.jsonRead(
    {
        func: "getBookings"
    }, function (b)
    {
        timers.endAllPersons = new Date;
        jQuery.each(b, function (d, c)
        {
            allBookings[c.id] = cr_mapJsonBookings(c)
        });
        churchInterface.clearStatus();
        a != null && a()
    })
};

