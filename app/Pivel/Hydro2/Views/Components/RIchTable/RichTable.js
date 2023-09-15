class RichTable extends SortableTable {
    _searchField;
    _searchButton;
    _contextMenu;

    _query;
    constructor(selector, apiEndpoint, apiResponseKey, renderer=null) {
        super(selector + ".rich-table", apiEndpoint, apiResponseKey, renderer);

        this._searchField = this._e.Nodes(selector + "_search_form_q");
        this._searchButton = this._e.Nodes(selector + "_search_form_submit");
        this._createButton = this._e.Nodes(selector + "_create");
        this._createOverlay = this._e.Nodes(selector + "_create_overlay");
        this._contextMenu = this._e.Nodes(selector + "_context");
        this._toast = this._e.Nodes(selector + "_toast");
        this._overlayCloseButtons = this._e.Nodes(".close-overlay");

        // add search event handler
        this._searchButton.AddEventHandler("click", this._searchClick.bind(this));
        // add create event handler
        this._createButton.AddEventHandler("click", this._createClick.bind(this));
        // add overlay close event handler(s)
        this._overlayCloseButtons.AddEventHandler("click", this._overlayCloseClick.bind(this));
    }

    _searchClick(event) {
        event.preventDefault(); // we are in a form, don't let the form be submitted.
        this._query = this._searchField.Value();
        this.Load();
        return false;
    }

    _createClick(event) {
        event.preventDefault();
        this.ShowCreateOverlay();
        return false;
    }

    _overlayCloseClick(event) {
        event.preventDefault();
        this.HideOverlays();
        return false;
    }

    Load() {
        this._showSpinner();
        var request = new H.AjaxRequest("GET", this._apiEndpoint);
        var qd = {
            "sort_by": this._sortedBy,
            "sort_dir": this._sortDir === 0 ? "asc" : "desc"
        };
        if (this._query != undefined) {
            qd["q"] = this._query;
        }
        request.SetQueryData(qd);
        request.Send(this._dataLoadedCallback.bind(this));
    }

    ShowCreateOverlay() {
        this._createOverlay.AddClass("active");
    }

    HideOverlays() {
        this._createOverlay.RemoveClass("active");
    }

    ShowToast(message, long=false, statusClass="default") {
        var t = this._toast;
        t.Nodes("span").Text(message);
        t._nodeList[0].style.width = "" + t._nodeList[0].scrollWidth + "px";
        t.AddClass("popped");
        t.AddClass(statusClass);

        setTimeout(function() {
            t.RemoveClass("popped");
            t._nodeList[0].style.width = 0;
        }, long ? 30000 : 5000);

        setTimeout(function() {
            t.RemoveClass(statusClass);
        }, long ? 30000 + 1000 : 5000 + 1000);
    }
}