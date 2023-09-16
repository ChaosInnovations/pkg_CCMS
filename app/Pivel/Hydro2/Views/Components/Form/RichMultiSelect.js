// TODO should this extend H.DOMNodes ?
class RichMultiSelect {
    select = null;
    feedback = null;
    constructor(id) {
        this._e = H.Nodes(id).Parent();

        this.select = this._e.Nodes("select")._nodeList[0];
        this.feedback = this._e.Nodes(".feedback");
    }

    SetValidation(valid, feedback="") {
        this.feedback.HTML(feedback);
        this._e.RemoveClass("valid");
        this._e.RemoveClass("invalid");
        this._e.AddClass(valid?"valid":"invalid");
        this._e.AddClass("show-feedback");
    }

    HideValidation() {
        this._e.RemoveClass("show-feedback");
        this._e.RemoveClass("valid");
        this._e.RemoveClass("invalid");
        this.feedback.HTML("");
    }

    SetOptions(options) {
        for (const o in this.select.options) {
            this.select.options.remove(0);
        }

        options.forEach(element => {
            var o = new Option(element["text"], element["value"], element["selected"])
            this.select.options.add(o);
        });
    }

    Value(newValue=null) {
        var v = [];
        for (const o of this.select.options) {
            if (o.selected) {
                v.push(o.value);
            }

            if (newValue != null) {
                o.selected = newValue.includes(o.value);
            }
        }

        return v;
    }

    Clear() {
        for (const o of this.select.options) {
            o.selected = false;
        };
    }
}