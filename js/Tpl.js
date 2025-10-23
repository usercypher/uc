(function() {
    var global = (typeof window !== "undefined") ? window: this;

    function Tpl() {
        this.tpl = {};
    }
    Tpl.prototype.register = function(tpl, template, data, callback) {
        this.tpl[tpl] = [template, data || {}, callback || function () {}];
    };
    Tpl.prototype.init = function(elements) {
        var els = [];
        for (var i = 0, ilen = elements.length; i < ilen; i++) { els.push(elements[i]); }
        for (var i = els.length - 1; i > -1; i--) {
            var el = els[i];
            var tpl = el.tpl.getAttribute("__tpl");
            if (!this.tpl.hasOwnProperty(tpl)) continue;
            var data = {"__slot" : el.innerHTML};
            var source = this.tpl[tpl][1];
            for (var key in source) {
                if (source.hasOwnProperty(key)) { data[key] = source[key]; }
            }
            var attr = el.attributes;
            var inherit = "";
            for (var j = 0; j < attr.length; j++) {
                if (attr[j].name.slice(0, 2) === "__") {
                    data[attr[j].name] = attr[j].value;
                } else {
                    inherit += ' ' + attr[j].name + '="' + attr[j].value.split('"').join('&quot;') + '"';
                }
            }
            data["__inherit"] = inherit;
            var output = this.tpl[tpl][0];
            for (var key in data) {
                if (!data.hasOwnProperty(key)) continue;
                output = output.split(key).join(data[key]);
            }
            el.insertAdjacentHTML('beforebegin', output);
            el.parentNode.removeChild(el);
            this.tpl[tpl][2](data);
        }
    };
    global.Tpl = Tpl;
})();