class MasterDetail {
    _e;
    _baseEndpoint;
    _pages = [];
    _pageKeys = [];
    _activePageKey = null;
    _pageTriggerNodes = [];
    _nav = null;
    constructor(selector, baseEndpoint) {
        this._e = H.Nodes(selector);
        this._baseEndpoint = baseEndpoint;

        this._nav = this._e.Nodes(".master-nav");
        this._e.Nodes(".master-nav>.collapse-control").AddEventHandler("click", this._toggleNavCollapse.bind(this));
        this._e.Nodes(".expandable>a").AddEventHandler("click", this._toggleNavSectionCollapse.bind(this));

        // find elements with data-masterdetailtarget set. Add onclick event handlers
        this._pageTriggerNodes = this._e.Nodes("[data-masterdetailtarget]");
        this._pageTriggerNodes.AddEventHandler("click", this._multiPageNavEventHandler.bind(this));
        // find elements with data-page
        // First element with .active is the active page
        this._pages = this._e.Nodes("[data-page]");
        this._pageKeys = this._pages.Data("page");

        this._pages._nodeList.forEach(element => {
            if (H.Nodes(element).HasClass("active") && this._activePageKey == null) {
                this._activePageKey = H.Nodes(element).Data("page");
            }
        });
        if (this._activePageKey == null) {
            this._activePageKey = this._pageKeys[0];
        }

        window.addEventListener("popstate", this._historyPopState.bind(this));
    }

    _toggleNavCollapse(event) {
        // if parent.HasClass("collapsed") remove it, and vice-versa
        if (this._nav.HasClass("collapsed")) {
            this._nav.RemoveClass("collapsed");
        } else {
            this._nav.AddClass("collapsed");
        }
    }

    _toggleNavSectionCollapse(event) {
        // if parent.HasClass("expanded") remove it, and vice-versa
        // if becoming expanded, calculate the height of parent>ul scrollHeight when expanded, set style.height, unset after delay
        //  else, unset style.height
        event.preventDefault();
        var li = H.Nodes(event.target).Parent();
        var ul = H.Nodes(li.Nodes("ul")._nodeList[0]);
        var height = ul._nodeList[0].scrollHeight;
        if (li.HasClass("expanded")) {
            ul._nodeList[0].style.height = ""+height+"px";
            li.RemoveClass("expanded");
            setTimeout(function(){
                ul._nodeList[0].style.height = "";
            },10);
        } else {
            li.AddClass("expanded");
            ul._nodeList[0].style.height = ""+height+"px";
            setTimeout(function(){
                ul._nodeList[0].style.height = "";
            },500);
        }
    }

    _multiPageNavEventHandler(event) {
        event.preventDefault();
        var trigger = H.Nodes(event.target);
        var target = trigger.Data("masterdetailtarget");
        this.NavigateTo(target);
    }

    _historyPopState(event) {
        if (event.state == null) {
            return;
        }
        this.NavigateTo(event.state["key"]);
    }

    NavigateTo(pageKey) {
        // check that target key exists. If not, navigate to first page.
        if (!this._pageKeys.includes(pageKey)) {
            pageKey = this._pageKeys[0];
        }
        // removeclass("active") from currently-active page
        this._pages.RemoveClass("active");
        // addClass("active") to newly-active page
        this._e.Nodes("[data-page=\"" + pageKey + "\"]").AddClass("active");
        // set currently-active page
        this._activePageKey = pageKey;
        // removeClass("active") from all trigger nodes
        this._pageTriggerNodes.RemoveClass("active");
        // find trigger node(s) with a matching data-masterdetailtarget and addClass("active")
        this._e.Nodes("[data-masterdetailtarget=\"" + pageKey + "\"]").AddClass("active");
        // expand parent trigger node(s)
        var k = pageKey;
        while (k.includes("/")) {
            // do this first so we don't expand the orginal node.
            k = k.substring(0, k.lastIndexOf("/"));
            this._e.Nodes("[data-masterdetailtarget=\"" + k + "\"]").Parent()
            var li = this._e.Nodes("[data-masterdetailtarget=\"" + k + "\"]").Parent();
            var ul = H.Nodes(li.Nodes("ul")._nodeList[0]);
            var height = ul._nodeList[0].scrollHeight;
            li.AddClass("expanded");
            ul._nodeList[0].style.height = ""+height+"px";
            setTimeout(function(){
                ul._nodeList[0].style.height = "";
            },500);
        }
        
        // add to history/back button
        history.pushState({key:pageKey}, "", "/"+this._baseEndpoint+"/"+pageKey);
    }
}