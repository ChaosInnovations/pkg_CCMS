class LabelledFormPasswordField {
    id = '';
    input = null;
    toggle = null;
    constructor(id) {
        this.id = id;
        this._e = H.Nodes(id).Parent();

        this.input = this._e.Nodes(id);
        this.toggle = this._e.Nodes(id+"_visibletoggle");

        this.toggle.AddEventHandler("click", this._onVisibleToggleClick.bind(this));
    }

    _onVisibleToggleClick(event) {
        event.preventDefault(); // if we are in a form, don't let the form be submitted.
        // check current state of password field (type==password|text)
        if (this.input.Attribute("type") == "password") {
            this.ShowPassword();
        } else {
            this.HidePassword();
        }
        return false;
    }

    ShowPassword() {
        // set input type to text
        this.input.Attribute("type", "text");
        // change button title & content to indicate that clicking the button will hide the password
        this.toggle.Attribute("title", "Hide Password");
        this.toggle.HTML("Hide");
    }

    HidePassword() {
        // set input type to password
        this.input.Attribute("type", "password");
        // change button title & content to indicate that clicking the button will show the password
        this.toggle.Attribute("title", "Show Password");
        this.toggle.HTML("Show");
    }
}