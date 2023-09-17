var H = {
    AjaxResponse: class {
        Status;
        ResponseText;
        ResponseObject;
        ErrorMessage=null;
        ErrorCode=null;
        Data=null;

        constructor(statusCode, responseText) {
            this.Status = statusCode;
            this.ResponseText = responseText;
            try {
                this.ResponseObject = JSON.parse(responseText);
                if ("message" in this.ResponseObject) {
                    this.ErrorMessage = this.ResponseObject["message"];
                }
                if ("code" in this.ResponseObject) {
                    this.ErrorCode = this.ResponseObject["code"];
                }
                if ("data" in this.ResponseObject) {
                    this.Data = this.ResponseObject["data"];
                }
            } catch {
                this.ResponseObject = null;
            }
        }
    },

    AjaxRequest: class {
        Body = "";
        Headers = {};

        constructor(method, url) {
            this.Method = method;
            this.Url = url;
        }

        // TODO adding files

        SetQueryData(queryData) {
            // for each key in queryData
            // queryPart[] = urlencode(key)=urlencode(queryData[key])
            var queryParts = [];
            for (var key in queryData) {
                queryParts.push(""+encodeURIComponent(key)+"="+encodeURIComponent(queryData[key]));
            }

            // join queryParts with '&'
            var query = queryParts.join("&");

            // remove any query part in this.Url
            this.Url = this.Url.split("?")[0];

            // append new query part to this.Url
            this.Url += "?" + query;
        }

        // TODO implement this
        SetFormData(formData) {

        }

        SetJsonData(object) {
            this.Body = JSON.stringify(object);
            this.SetHeader("Content-Type", "application/json; charset=utf-8");
        }

        SetHeader(name, value) {
            this.Headers[name] = value;
        }

        Send(callback) {
            const req = new XMLHttpRequest();
            req.addEventListener("load", function () {
                // build response
                var response = new H.AjaxResponse(this.status, this.responseText);
                callback(response);
            });
            req.open(this.Method, this.Url);
            Object.keys(this.Headers).forEach(name => {
                req.setRequestHeader(name, this.Headers[name]);
            });
            req.send(this.Body);
        }
    },

    // TODO add remaining status codes
    StatusCode: {
        OK: 200,
        BadRequest: 400,
        NotFound: 404,
        InternalServerError: 500,
    },

    HtmlEncode: function(s) {
        var el = document.createElement('div');
        el.innerText = el.textContent = s;
        return el.innerHTML;
    },
    
    HtmlDecode: function(s) {
        var el = document.createElement('div');
        el.innerHtml = s;
        return el.innerText;
    },

    Nodes: function(selector, parent=null) {
        return new H.DOMNodes(selector, parent);
    },

    DOMNodes: class {
        _nodeList = [];
        constructor(selector, parent=null) {
            // TODO create new element when selector is like <tag>
            if (selector instanceof H.DOMNodes) {
                this._nodeList = selector._nodeList;
                return;
            }
            if (selector instanceof HTMLElement || selector instanceof Document) {
                this._nodeList = [selector];
                return;
            }
            if (parent == null) {
                parent = document;
            }
            this._nodeList = parent.querySelectorAll(selector);
        }

        Enable() {
            this._nodeList.forEach(element => {
                element.disabled = false;
            });
        }

        Disable() {
            this._nodeList.forEach(element => {
                element.disabled = true;
            });
        }

        Value(newValue=null) {
            var values = [];
            this._nodeList.forEach(element => {
                values.push(element.value);
                if (newValue != null) {
                    element.value = newValue;
                }
            });

            if (values.length == 1) {
                return values[0];
            }

            return values;
        }

        Data(key, newValue=null) {
            var values = [];
            this._nodeList.forEach(element => {
                // TODO check if element.dataset contains key
                values.push(element.dataset[key]);
                if (newValue != null) {
                    element.dataset[key] = newValue;
                }
            });

            if (values.length == 1) {
                return values[0];
            }

            return values;
        }

        Attribute(attributeName, newValue=null) {
            var values = [];
            this._nodeList.forEach(element => {
                values.push(element.getAttribute(attributeName));
                if (newValue != null) {
                    element.setAttribute(attributeName, newValue);
                }
            });

            if (values.length == 1) {
                return values[0];
            }

            return values;
        }

        HTML(newHTML=null) {
            var values = [];
            this._nodeList.forEach(element => {
                values.push(element.innerHTML);
                if (newHTML != null) {
                    element.innerHTML = newHTML;
                }
            });

            if (values.length == 1) {
                return values[0];
            }

            return values;
        }

        Text(newText=null) {
            var values = [];
            this._nodeList.forEach(element => {
                values.push(element.innerText);
                if (newText != null) {
                    element.innerText = element.textContent = newText;
                }
            });

            if (values.length == 1) {
                return values[0];
            }

            return values;
        }

        AddClass(className) {
            this._nodeList.forEach(element => {
                element.classList.add(className);
            });
        }

        RemoveClass(className) {
            this._nodeList.forEach(element => {
                element.classList.remove(className);
            });
        }

        HasClass(className) {
            var results = [];
            this._nodeList.forEach(element => {
                results.push(element.classList.contains(className));
            });

            if (results.length == 1) {
                return results[0];
            }

            return results;
        }

        AddEventHandler(event, callback) {
            this._nodeList.forEach(element => {
                element.addEventListener(event, callback);
            });
        }

        RemoveEventHandler(event, callback) {
            this._nodeList.forEach(element => {
                element.removeEventListener(event, callback);
            });
        }

        Nodes(selector) {
            // TODO how to handle when we have multiple nodes?
            return H.Nodes(selector, this._nodeList[0]);
        }

        Parent() {
            // returns next parent of this node
            // TODO how to handle when we have multiple nodes?
            return H.Nodes(this._nodeList[0].parentElement);
        }

        Count() {
            return this._nodeList.length;
        }
    }
}