// TODO should this extend H.DOMNodes ?
class FormField {
    input = null;
    feedback = null;
    constructor(id) {
        this._e = H.Nodes(id).Parent();

        this.input = this._e.Nodes("input");
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
}