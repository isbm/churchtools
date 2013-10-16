var churchresource_js_version = "7.x-1.36",
    masterData = null,
    allBookings = {}, timers = [];
jQuery(document).ready(function () {
    churchInterface.registerView("WeekView", weekView);
    churchInterface.registerView("MaintainView", maintainView);
    cdb_loadMasterData(function () {
        churchInterface.setModulename(masterData.modulename);
        churchInterface.setLastLogId(masterData.lastLogId);
        churchInterface.activateHistory("WeekView");
        cr_loadBookings(function () {
            churchInterface.sendMessageToAllViews("allDataLoaded")
        })
    })
});
