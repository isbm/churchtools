(function (a)
{
    function b()
    {
        StandardTableView.call(this);
        this.name = "MaintainView"
    }
    Temp.prototype = MaintainStandardView.prototype;
    b.prototype = new Temp;
    maintainView = new b;
    b.prototype.getData = function ()
    {
        masterData.resourcetype = masterData.resTypes;
        return masterData.masterDataTables
    };
    b.prototype.renderMenu = function ()
    {
        this_object = this;
        masterData.resourcetype = masterData.resTypes;
        menu = new CC_Menu("Men&uuml;");
        menu.addEntry("Zur&uuml;ck zur Liste", "apersonview", "arrow-left");
        menu.addEntry("Hilfe", "ahelp", "question-sign");
        menu.renderDiv("cdb_menu") ? a("#cdb_menu a").click(function ()
        {
            if (a(this).attr("id") == "apersonview") churchInterface.setCurrentView(weekView, false);
            else a(this).attr("id") == "ahelp" && churchcore_openNewWindow("http://intern.churchtools.de/?q=help&doc=ChurchResource-Stammdaten");
            return false
        }) : a("#cdb_menu").hide()
    }
})(jQuery);

