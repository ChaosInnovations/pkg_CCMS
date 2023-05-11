class RichTable extends SortableTable {
    _searchField;
    _searchButton;
    _contextMenu;

    _query;
    constructor(selector, apiEndpoint, apiResponseKey, renderer=null) {
        super(selector + ".rich-table", apiEndpoint, apiResponseKey, renderer);

        this._searchField = this._e.Nodes(selector + '_search_form_q');
        this._searchButton = this._e.Nodes(selector + '_search_form_submit');
        this._contextMenu = this._e.Nodes(selector + '_context');

        // add search event handler
        this._searchButton.AddEventHandler("click", this._searchClick.bind(this));
    }

    _searchClick(event) {
        event.preventDefault(); // we are in a form, don't let the form be submitted.
        this._query = this._searchField.Value();
        this.Load();
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
}