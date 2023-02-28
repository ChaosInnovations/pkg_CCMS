class LoginCard extends MultiPageCard {
    id = '';
    constructor(id) {
        super(id+"_multipagecard");
        this.id = id;

        new LabelledFormPasswordField(id+"_loginform_password");
        new LabelledFormPasswordField(id+"_changepasswordform_current-password");
        new LabelledFormPasswordField(id+"_changepasswordform_new-password");

        this._e.Nodes(id+"_loginform").AddEventHandler("submit", this._onLoginFormSubmit.bind(this));
        this._e.Nodes(id+"_requestresetpasswordform").AddEventHandler("submit", this._onResetFormSubmit.bind(this));
        this._e.Nodes(id+"_changepasswordform").AddEventHandler("submit", this._onChangePasswordFormSubmit.bind(this));
        // check whether we are connected via https. If not, display a warning to use
        //  https
    }

    _onLoginFormSubmit(event) {
        event.preventDefault();
        console.log("login clicked");
        this.SubmitLoginForm();
        return false;
    }

    _onResetFormSubmit(event) {
        event.preventDefault();
        this.SubmitResetForm();
        return false;
    }

    _onChangePasswordFormSubmit(event) {
        event.preventDefault();
        this.SubmitChangePasswordForm();
        return false;
    }

    SubmitLoginForm() {
        // disable submit button and change contents to spinner
        this._e.Nodes(this.id+"_loginform_submit").Disable();
        // submit POST to ~login (this.$loginCallback)
        var request = new H.AjaxRequest("POST", "/api/hydro2/identity/login");
        request.SetJsonData({
            "email": this._e.Nodes(this.id+"_loginform_email").Value(),
            "password": this._e.Nodes(this.id+"_loginform_password").Value()
        });
        request.Send(this._submitLoginCallback.bind(this));
    }

    SubmitResetForm() {
        // disable submit button and change contents to spinner
        this._e.Nodes(this.id+"_requestresetpasswordform_submit").Disable();
        var request = new H.AjaxRequest("POST", "/api/hydro2/identity/users/sendpasswordreset");
        request.SetJsonData({
            "email": this._e.Nodes(this.id+"_requestresetpasswordform_email").Value()
        });
        request.Send(this._submitResetCallback.bind(this));
    }

    SubmitChangePasswordForm() {
        // disable submit button and change contents to spinner
        // session cookie should have already been set.
        this._e.Nodes(this.id+"_changepasswordform_submit").Disable();
        var request = new H.AjaxRequest("POST", "/api/hydro2/identity/users/changepassword");
        request.SetJsonData({
            "password": this._e.Nodes(this.id+"_changepasswordform_currentpassword").Value(),
            "new_password": this._e.Nodes(this.id+"_changepasswordform_newpassword").Value()
        });
        request.Send(this._submitChangePasswordCallback.bind(this));
    }

    /**
     * @param {H.AjaxResponse} response
     */
    _submitLoginCallback(response) {
        if (response.Status == H.StatusCode.OK) {
            // the session token and key should be saved in a cookie (httponly so we can't read it here)
            //  if 2FA challenge is required, display 2FA challenge screen
            // TODO implement 2FA challenge
            //this.NavigateTo("mfachallenge");
            //  if password change is required, display password change screen
            this.NavigateTo("changepassword");
            //  else refresh page
            location.reload();
            this._e.Nodes(this.id+"_loginform_submit").Enable();
            return;
        }

        // display wrong email message, wrong password message, too many tries message, or account locked message
        // handle if already logged in
        
        //  restore submit button
        this._e.Nodes(this.id+"_loginform_submit").Enable();
    }

    /**
     * @param {H.AjaxResponse} response
     */
     _submitResetCallback(response) {
        if (response.Status == H.StatusCode.OK) {
            // switch to #resetlinksent
            this.NavigateTo("resetlinksent");
            this._e.Nodes(this.id+"_requestresetpasswordform_submit").Enable();
            return;
        }
        
        //  restore submit button
        this._e.Nodes(this.id+"_requestresetpasswordform_submit").Enable();
    }

    /**
     * @param {H.AjaxResponse} response
     */
     _submitChangePasswordCallback(response) {
        if (response.Status == H.StatusCode.OK) {
            this.NavigateTo("changepasswordsuccess");
            location.reload();
            this._e.Nodes(this.id+"_changepasswordform_submit").Enable();
            return;
        }
        
        //  restore submit button
        this._e.Nodes(this.id+"_changepasswordform_submit").Enable();
    }
}