class MultiPageCard {
    _e;
    _pages = [];
    _pageIds = [];
    _activePageId = null;
    _pageTriggerNodes = [];
    constructor(selector) {
        this._e = H.Nodes(selector);

        // find elements with data-multipagecardtarget set. Add onclick event handlers
        this._pageTriggerNodes = this._e.Nodes("[data-multipagecardtarget]");
        this._pageTriggerNodes.AddEventHandler("click", this._multiPageNavEventHandler.bind(this));
        window.addEventListener("hashchange", this._onLocationHashChanged.bind(this));
        // find elements with .card-page.
        // First element with .card-page.active is the active page
        this._pages = this._e.Nodes(".card-page");
        this._pages._nodeList.forEach(element => {
            this._pageIds.push(element.id);
            if (H.Nodes(element).HasClass("active") && this._activePageId == null) {
                this._activePageId = element.id;
            }
        });
        if (this._activePageId == null) {
            this._activePageId = this._pages._nodeList[0].id;
        }
        // if the page's fragment is set and one of our pages has a matching id, then NavigateTo(fragment).
        if (location.hash != "") {
            this.NavigateTo(location.hash.substring(1));
        }
    }

    _onLocationHashChanged(event) {
        this._e._nodeList[0].scrollTop = 0;
        this._e._nodeList[0].scrollLeft = 0;
        if (location.hash != "") {
            this.NavigateTo(location.hash.substring(1));
        }
    }

    _multiPageNavEventHandler(event) {
        this.NavigateTo(H.Nodes(event.target).Data("multipagecardtarget"));
    }

    NavigateTo(targetId) {
        if (!this._pageIds.includes(targetId)) {
            return;
        }
        // remove .active class from _activePage element
        this._pages.RemoveClass("active");
        // find target and add .active class
        this._e.Nodes("#"+targetId).AddClass("active");
        // TODO for some reason this doesn't actually set the hash.
        window.location.hash = "#"+targetId;
        this._activePageId = targetId;
    }
}