/*
Copyright 2025 Lloyd Miles M. Bersabe

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

(function() {
    var window = (typeof window !== "undefined") ? window : this || {};

    function Util() {}
    Util.htmlEncode = function(text) {
        return text.replace(Util.htmlEncode.regex, function(s) {
            return Util.htmlEncode.map[s];
        });
    };
    Util.htmlEncode.map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "\"": "&quot;",
        "'": "&#39;"
    };
    Util.htmlEncode.regex = /[&<>"']/g;
    Util.htmlDecode = function(text) {
        return text.replace(Util.htmlDecode.regex, function(s) {
            return Util.htmlDecode.map[s];
        });
    };
    Util.htmlDecode.map = {
        "&amp;": "&",
        "&lt;": "<",
        "&gt;": ">",
        "&quot;": "\"",
        "&#39;": "'"
    };
    Util.htmlDecode.regex = /&(amp|lt|gt|quot|#0*39);/g;
    Util.trim = function(text) {
        var start = 0;
        var end = text.length - 1;
        var filter = Util.trim.filter;
        while (start <= end && filter[text.charCodeAt(start)]) {
            start++;
        }
        while (end >= start && filter[text.charCodeAt(end)]) {
            end--;
        }
        return text.substring(start, end + 1);
    };
    Util.trim.filter = {
        32: 1,
        9: 1,
        10: 1,
        13: 1,
        11: 1,
        12: 1
    };
    Util.strReplace = function(text, map) {
        var keys = [];
        for (var k in map) {
            if (!Object.prototype.hasOwnProperty.call(map, k)) {
                continue;
            }
            keys.push(k.replace(Util.strReplace.escape, "\\$&"));
        }
        if (keys.length === 0) {
            return text;
        }
        keys.sort(Util.strReplace.sort);
        return text.replace(new RegExp(keys.join("|"), "g"), function(match) {
            return map[match];
        });
    };
    Util.strReplace.escape = /[.*+?^${}()|[\]\s\\]/g;
    Util.strReplace.sort = function(a, b) {
        return b.length - a.length;
    };
    Util.strSizeOf = function(text) {
        var size = 0;
        for (var i = 0, ilen = text.length; i < ilen; i++) {
            var code = text.charCodeAt(i);
            if (code >= 0xD800 && code <= 0xDBFF) {
                var next = text.charCodeAt(i + 1);
                if (next >= 0xDC00 && next <= 0xDFFF) {
                    size += 4;
                    i++;
                } else {
                    size += 3;
                }
                continue;
            }
            size += (code <= 0x7F) ? 1 : (code <= 0x7FF ? 2 : 3);
        }
        return size;
    };
    Util.debounce = function(func, time) {
        var timer;

        function debounced() {
            var self = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                func.apply(self, args);
            }, time);
        }
        debounced.cancel = function() {
            clearTimeout(timer);
        };
        return debounced;
    };
    Util.throttle = function(func, time) {
        var last = 0;
        var timeout = null;

        function throttled() {
            var self = this;
            var args = arguments;
            var now = +new Date();
            var remaining = time - (now - last);
            if (remaining <= 0) {
                last = now;
                func.apply(self, args);
            } else if (!timeout) {
                timeout = setTimeout(function() {
                    last = +new Date();
                    timeout = null;
                    func.apply(self, args);
                }, remaining);
            }
        }
        throttled.cancel = function() {
            clearTimeout(timeout);
            timeout = null;
        };
        return throttled;
    };
    Util.queryBuild = function(map) {
        var query = [];
        Util.queryBuild.build(map, null, query);
        return query.join("&");
    };
    Util.queryBuild.build = function(map, prefix, query) {
        for (var key in map) {
            if (!Object.prototype.hasOwnProperty.call(map, key)) {
                continue;
            }
            var value = map[key],
                k = prefix ? prefix + "[" + key + "]" : key,
                type = Object.prototype.toString.call(value);
            if (value === null || typeof value === "undefined") {
                continue;
            }
            if (type === "[object Array]") {
                for (var i = 0, len = value.length; i < len; i++) {
                    var v = value[i];
                    if (v !== null && typeof v === "object") {
                        Util.queryBuild.build(v, k + "[" + i + "]", query);
                    } else {
                        query.push(k + "[]=" + encodeURIComponent(v));
                    }
                }
            } else if (typeof value === "object") {
                Util.queryBuild.build(value, k, query);
            } else {
                query.push(k + "=" + encodeURIComponent(value));
            }
        }
    };
    Util.queryParse = function(text) {
        var result = {};
        if (!text) {
            return result;
        }
        if (text.charAt(0) === "?") {
            text = text.substring(1);
        }
        var pairs = text.split("&");
        for (var i = 0; i < pairs.length; i++) {
            var pair = pairs[i].split("=");
            if (pair[0] === "") {
                continue;
            }
            var key = decodeURIComponent(pair[0].replace(/\+/g, " "));
            var val = pair.length > 1 ? decodeURIComponent(pair[1].replace(/\+/g, " ")) : "";
            var parts = key.replace(/\]/g, "").split("[");
            var current = result;
            for (var j = 0; j < parts.length; j++) {
                var part = parts[j];
                var isLast = (j === parts.length - 1);
                if (isLast) {
                    if (part === "") {
                        if (Object.prototype.toString.call(current) !== "[object Array]") {
                            current = [];
                        }
                        current.push(val);
                    } else {
                        if (typeof current[part] === "undefined") {
                            current[part] = val;
                        } else {
                            if (Object.prototype.toString.call(current[part]) !== "[object Array]") {
                                current[part] = [current[part]];
                            }
                            current[part].push(val);
                        }
                    }
                } else {
                    var nextPart = parts[j + 1];
                    if (typeof current[part] === "undefined" || typeof current[part] !== "object") {
                        current[part] = (nextPart === "") ? [] : {};
                    }
                    current = current[part];
                }
            }
        }
        return result;
    };
    Util.poll = function(probe, func, options) {
        options = options || {};
        var startTime = +new Date();
        var interval = options.interval || 100;
        var timeout = options.timeout || 30000;
        var ontimeout = options.ontimeout || function() {};
        var intervalId = setInterval(function() {
            if (probe()) {
                clearInterval(intervalId);
                func();
            } else if (+new Date() - startTime >= timeout) {
                clearInterval(intervalId);
                ontimeout();
            }
        }, interval);
    };
    Util.script = function(urls, options) {
        options = options || {};
        var onload = options.onload || function() {};
        var onerror = options.onerror || function() {};
        var timeout = options.timeout || 10000;
        var count = 0;
        var errs = [];
        var len = urls.length;
        var cache = Util.script.cache;
        var head = window.document.getElementsByTagName("head")[0] || window.document.documentElement;

        function next(idx) {
            if (idx >= len) {
                return;
            }

            var raw = urls[idx];
            var isAsync = raw.substring(0, 7) === "async::";
            var url = isAsync ? raw.substring(7) : raw;
            var entry = cache[url] = cache[url] || {
                state: 0,
                listeners: []
            };
            var el;
            var accountFor = function(failed, msg) {
                if (failed) {
                    errs.push(raw + msg);
                }

                if (++count === len) {
                    if (errs.length > 0) {
                        onerror({
                            content: "Util.script error: " + errs.join(", "),
                            retry: function() {
                                Util.script(urls, options);
                            }
                        });
                    } else {
                        onload();
                    }
                }
            };
            var finalize = function(failed, msg) {
                if (el) {
                    el.onload = el.onreadystatechange = el.onerror = null;
                    clearTimeout(el.tid);
                }

                entry.state = failed ? 0 : 1;

                while (entry.listeners.length) {
                    entry.listeners.shift()(failed, raw + msg + " (wait)");
                }

                accountFor(failed, msg);
            };

            if (entry.state === 1) {
                setTimeout(function() {
                    accountFor(0);
                    next(idx + 1);
                }, 0);
            } else if (entry.state === 2) {
                entry.listeners.push(function(failed, msg) {
                    accountFor(failed, msg);
                    next(idx + 1);
                });
            } else {
                entry.state = 2;
                el = window.document.createElement("script");
                el.type = "text/javascript";

                el.tid = setTimeout(function() {
                    finalize(1, " (timeout)");
                }, timeout);

                el.onload = el.onreadystatechange = function() {
                    var rs = el.readyState;
                    if (!rs || rs === "loaded" || rs === "complete") {
                        finalize(0);
                        if (!isAsync) {
                            next(idx + 1);
                        }
                    }
                };

                el.onerror = function() {
                    finalize(1, " (failed)");
                };
                el.src = url;
                head.appendChild(el);

                if (isAsync) {
                    setTimeout(function() {
                        next(idx + 1);
                    }, 0);
                }
            }
        }

        next(0);
    };
    Util.script.cache = {};
    Util.path = function(obj, keys, value) {
        var len = keys.length;
        var current = obj;
        for (var i = 0; i < len - 1; i++) {
            current = current[keys[i]];
            if (current == null) {
                return;
            }
        }
        var lastKey = keys[len - 1];
        if (value === undefined) {
            return current[lastKey];
        }
        if (current[lastKey] !== value) {
            current[lastKey] = value;
        }
    };

    function Url(url) {
        this.url = url || window.location.href || "";
        var parts = this.url.split("#");
        this.hash = "";
        if (parts[1]) {
            this.hash = parts[1];
        }
        parts = parts[0].split("?");
        this.base = parts[0];
        this.query = parts[1] ? Util.queryParse(parts[1]) : {};
    }
    Url.prototype.setHash = function(value) {
        this.hash = value;
        return this;
    };
    Url.prototype.setQuery = function(key, value) {
        this.query[key] = value;
        return this;
    };
    Url.prototype.removeQuery = function(key) {
        delete this.query[key];
        return this;
    };
    Url.prototype.getQuery = function(key) {
        return this.query[key];
    };
    Url.prototype.toString = function() {
        var q = Util.queryBuild(this.query);
        return (q !== "" ? this.base + "?" + q : this.base) + (this.hash ? "#" + this.hash : "");
    };
    Url.prototype.sync = function(replace) {
        var url = this.toString();
        if (history && history.pushState) {
            history[replace || false ? "replaceState" : "pushState"]({}, "", url);
        } else {
            location.href = url;
        }
    };

    function Step() {
        this.reset();
    }
    Step.prototype.reset = function() {
        this.steps = [];
        this.index = 0;
    };
    Step.prototype.add = function(func) {
        return this.steps.push(func) - 1;
    };
    Step.prototype.run = function(data) {
        if (this.index < this.steps.length) {
            this.steps[this.index](this, data);
        }
    };
    Step.prototype.next = function() {
        this.index++;
        return this;
    };
    Step.prototype.at = function() {
        return this.index;
    };
    Step.prototype.to = function(index) {
        this.index = index;
        return this;
    };

    function Xhr(xhr) {
        this.xhr = xhr;
    }
    Xhr.prototype.send = function(url, option) {
        option = option || {};
        var method = option.method || "GET";
        var header = option.header || {};
        var content = option.content || "";
        var timeout = option.timeout || -1;
        var onload = option.onload || function() {};
        var timeoutId;
        var self = this;
        this.xhr.open(method, url, true);
        for (var key in header) {
            if (!Object.prototype.hasOwnProperty.call(header, key)) {
                continue;
            }
            var value = header[key];
            if (Object.prototype.toString.call(value) === "[object Array]") {
                for (var i = 0; i < value.length; i = i + 1) {
                    this.xhr.setRequestHeader(key, value[i]);
                }
            } else {
                this.xhr.setRequestHeader(key, value);
            }
        }
        if (timeout !== -1) {
            timeoutId = setTimeout(function() {
                self.xhr.abort();
                onload(self.response({
                    "status": 408,
                    "responseText": "",
                    "getAllResponseHeaders": function() {
                        return "X-Timeout: true";
                    }
                }));
            }, timeout * 1000);
        }
        this.xhr.onreadystatechange = function() {
            if (self.xhr.readyState === 4) {
                if (typeof timeoutId !== "undefined") {
                    clearTimeout(timeoutId);
                }
                onload(self.response(self.xhr));
            }
        };
        this.xhr.send(content);
    };
    Xhr.prototype.abort = function() {
        if (this.xhr && this.xhr.readyState !== 4) {
            this.xhr.abort();
        }
    };
    Xhr.prototype.response = function(xhr) {
        var result = {
            header: {},
            code: xhr.status,
            content: xhr.responseText
        };

        var headerStr = xhr.getAllResponseHeaders();

        if (headerStr) {
            var lines = headerStr.split("\n");
            for (var i = 0, ilen = lines.length; i < ilen; i++) {
                var line = lines[i];
                if (line === "") {
                    continue;
                }
                var colonPos = -1;
                for (var j = 0, jlen = line.length; j < jlen; j++) {
                    if (line.charAt(j) === ":") {
                        colonPos = j;
                        break;
                    }
                }
                if (colonPos === -1) {
                    continue;
                }

                var key = Util.trim(line.substring(0, colonPos)).toLowerCase();
                if (key) {
                    result.header[key] = Util.trim(line.substring(colonPos + 1));
                }
            }
        }

        return result;
    };

    function El(input) {
        this.el = typeof input === 'string' ? window.document.getElementById(input) : input;
        this.lastContent = "";
        this.isSaved = false;
    }
    El.prototype.store = function() {
        this.lastContent = this.el.innerHTML;
        this.isSaved = true;
        return this;
    };
    El.prototype.restore = function() {
        if (this.isSaved) {
            if (this.el.innerHTML !== this.lastContent) {
                this.el.innerHTML = this.lastContent;
            }
            this.isSaved = false;
        }
        return this;
    };
    El.prototype.prepend = function(html) {
        this.el.insertAdjacentHTML("afterbegin", html);
    };
    El.prototype.append = function(html) {
        this.el.insertAdjacentHTML("beforeend", html);
    };
    El.prototype.before = function(html) {
        this.el.insertAdjacentHTML("beforebegin", html);
    };
    El.prototype.after = function(html) {
        this.el.insertAdjacentHTML("afterend", html);
    };
    El.prototype.remove = function() {
        if (this.el && this.el.parentNode) {
            this.el.parentNode.removeChild(this.el);
        }
    };

    function ElX() {}
    ElX.refs = {};
    ElX.refsWin = {};
    ElX.refsTap = {};
    ElX.vals = {};
    ElX.taps = {};
    ElX.uses = {};
    ElX.mutationDepth = 0;
    ElX.events = [];
    ElX.bitEK = {
        "ctrl": 1,
        "alt": 2,
        "shift": 4,
        "left": 8,
        "wheel": 16,
        "right": 32
    };
    ElX.bitEB = {
        "stop": 1,
        "prevent": 2,
        "window": 4
    };
    ElX.init = function(el) {
        ElX.mutationDepth++;

        ElX.processElement(el);
        var elements = el.getElementsByTagName("*");
        for (var i = 0; i < elements.length; i++) {
            ElX.processElement(elements[i]);
        }

        ElX.mutationDepth--;
    };
    ElX.processElement = function(el) {
        var xArr = [];
        var xObj = {};
        var paths = {
            window: window,
            this: el,
            vals: ElX.vals
        };

        for (var i = 0; i < el.attributes.length; i++) {
            var attr = el.attributes[i];
            var attrName = attr.name;
            var attrValue = attr.value;
            var prefix = attrName.substring(0, 6);
            var keyAttrArr = attrName.substring(6).split(".");
            var key = keyAttrArr[0];

            if (prefix === "x-use-" && ElX.uses[key]) {
                ElX.uses[key](el);
            } else if (prefix === "x-ref-") {
                if (!ElX.refs[key]) {
                    ElX.refs[key] = [];
                }
                var isDuplicate = false;
                for (var j = 0, jlen = ElX.refs[key].length; j < jlen; j++) {
                    if (ElX.refs[key][j] === el) {
                        isDuplicate = true;
                        break;
                    }
                }
                if (!isDuplicate) {
                    ElX.refs[key].push(el);
                }
            } else if (prefix === "x-evt-") {
                var parts = keyAttrArr.slice(1);
                var event = key;
                var eventKey = "";
                var eventMask = 0;
                var mask = 0;
                for (var j = 0, jlen = parts.length; j < jlen; j++) {
                    var p = parts[j];
                    if (ElX.bitEB[p]) {
                        mask |= ElX.bitEB[p];
                    } else if (ElX.bitEK[p]) {
                        eventMask |= ElX.bitEK[p];
                    } else {
                        eventKey = p;
                    }
                }
                var object = null;
                var signature = event + "_" + eventKey + "_" + eventMask;
                el["_x_mask_" + signature] = mask;
                el["_x_rule_" + signature] = attrName;
                if (mask & ElX.bitEB.window) {
                    object = ElX.refsWin;
                    window["on" + event] = ElX.event;
                } else {
                    if (event === "tap") {
                        object = ElX.refsTap;
                    }
                    el["on" + event] = ElX.event;
                }
                if (object) {
                    if (!object[signature]) {
                        object[signature] = [];
                    }
                    object[signature].push(el);
                }
            }

            if (prefix === "x-css-" || prefix === "x-dom-" || prefix === "x-set-" || prefix === "x-val-" || prefix === "x-sig-" || prefix === "x-lit-") {
                if (prefix === "x-val-" && ElX.vals[key] === undefined) {
                    ElX.vals[key] = attrValue !== "" && attrValue.charAt(0) === "$" ? Util.path(paths, attrValue.substring(1).split(".")) : attrValue;
                }
                var parts = null;
                if (prefix === "x-dom-") {
                    parts = keyAttrArr.slice(1);
                    for (var j = 0, jlen = parts.length; j < jlen; j++) {
                        var words = parts[j].split("-");
                        parts[j] = words[0];
                        for (var k = 1, klen = words.length; k < klen; k++) {
                            parts[j] += words[k].charAt(0).toUpperCase() + words[k].substring(1);
                        }
                    }
                }
                xArr.push([attrName, Util.trim(attrValue), prefix, key, keyAttrArr.slice(1).join("."), parts]);
            }

            if (prefix === "x-evt-" || prefix === "x-alt-") {
                xObj[attrName] = Util.trim(attrValue);
            }
        }
        el._x_arr = xArr;
        el._x_obj = xObj;
    };
    ElX.prune = function(el) {
        ElX.mutationDepth++;
        el = el || window.document.documentElement;
        var objects = [ElX.refs, ElX.refsWin, ElX.refsTap];
        for (var i = 0, ilen = objects.length; i < ilen; i++) {
            var object = objects[i];
            for (var key in object) {
                if (!Object.prototype.hasOwnProperty.call(object, key)) {
                    continue;
                }
                var els = object[key];
                var writeIndex = 0;
                for (var readIndex = 0; readIndex < els.length; readIndex++) {
                    var node = els[readIndex];
                    while (node && node !== el) {
                        node = node.parentNode;
                    }
                    if (node === el) {
                        els[writeIndex] = els[readIndex];
                        writeIndex++;
                    }
                }
                if (writeIndex === 0) {
                    delete object[key];
                } else {
                    els.length = writeIndex;
                }
            }
        }
        ElX.mutationDepth--;
    };
    ElX.clear = function(key) {
        delete ElX.vals[key];
        delete ElX.taps[key];
    };
    ElX.use = function(key, func) {
        ElX.uses[key] = func;
    };
    ElX.x = function(key, value) {
        return new X(ElX, key, value);
    };
    ElX.ref = function(key) {
        return ElX.refs[key] || [];
    };
    ElX.tap = function(key, func, context) {
        if (!ElX.taps[key]) {
            ElX.taps[key] = [];
        }
        return ElX.taps[key].push([func, context]) - 1;
    };
    ElX.untap = function(key, index) {
        ElX.taps[key][index] = null;
    };
    ElX.css = function(key, attr, value, el) {
        var els = (key == "this") ? [el] : (ElX.refs[key] || []);
        for (var i = 0, ilen = els.length; i < ilen; i++) {
            var refEl = els[i];
            var classList = Util.trim(refEl.className).split(" ");
            var current = -1;
            for (var j = 0, jlen = classList.length; j < jlen; j++) {
                if (attr === classList[j]) {
                    current = j;
                    break;
                }
            }

            if (current !== -1 && (value === "toggle" || value === "remove")) {
                classList.splice(current, 1);
                refEl.className = classList.join(" ");
            } else if (current === -1 && (value === "toggle" || value === "" || value === "add")) {
                classList.push(attr);
                refEl.className = classList.join(" ");
            }
        }
    };
    ElX.set = function(key, attr, states, el) {
        var prefix = attr.substring(0, 6);
        var isArr = prefix === "x-css-" || prefix === "x-dom-" || prefix === "x-set-" || prefix === "x-val-" || prefix === "x-sig-" || prefix === "x-lit-";
        var isObj = prefix === "x-evt-" || prefix === "x-alt-";
        var keyAttrArr = null;

        if (isArr) {
            keyAttrArr = attr.substring(6).split(".");
        }

        var els = (key == "this") ? [el] : (ElX.refs[key] || []);
        for (var i = 0, ilen = els.length; i < ilen; i++) {
            var refEl = els[i];
            var xArrIdx = -1;
            var current = null;
            if (isArr) {
                for (var j = 0, jlen = refEl._x_arr.length; j < jlen; j++) {
                    if (refEl._x_arr[j][0] === attr) {
                        xArrIdx = j;
                        current = refEl._x_arr[j][1];
                        break;
                    }
                }
            } else if (isObj) {
                current = refEl._x_obj[attr];
            } else {
                current = refEl.getAttribute(attr);
            }

            if (current == null) {
                current = "null";
            }

            var newState = Util.trim(states[current === states[0] ? 1 : 0] || "");
            if (current !== newState && newState !== "null") {
                if (isArr) {
                    if (current === "null") {
                        var parts = null;
                        if (prefix === "x-dom-") {
                            parts = keyAttrArr.slice(1);
                            for (var j = 0, jlen = parts.length; j < jlen; j++) {
                                var words = parts[j].split("-");
                                parts[j] = words[0];
                                for (var k = 1, klen = words.length; k < klen; k++) {
                                    parts[j] += words[k].charAt(0).toUpperCase() + words[k].substring(1);
                                }
                            }
                        }
                        refEl._x_arr.push([attr, newState, prefix, keyAttrArr[0], keyAttrArr.slice(1).join("."), parts]);
                    } else {
                        refEl._x_arr[xArrIdx][1] = newState;
                    }
                } else if (isObj) {
                    refEl._x_obj[attr] = newState;
                }

                refEl.setAttribute(attr, newState);
            } else if (current !== "null" && newState === "null") {
                if (isArr) {
                    refEl._x_arr.splice(xArrIdx, 1);
                } else if (isObj) {
                    delete refEl._x_obj[attr];
                }
                refEl.removeAttribute(attr);
            }
        }
    };
    ElX.dom = function(key, keys, value, el) {
        var els = (key == "this") ? [el] : (key == "window" ? [window] : (ElX.refs[key] || []));
        for (var i = 0, ilen = els.length; i < ilen; i++) {
            Util.path(els[i], keys, value);
        }
    };
    ElX.val = function(key, value, event) {
        event = event || {
            type: "sys"
        };
        var old = ElX.vals[key];
        ElX.vals[key] = value;
        for (var i = 0, keyTemp = key; i < 2; i++, keyTemp = "*") {
            if (ElX.taps[keyTemp]) {
                for (var j = 0, jlen = ElX.taps[keyTemp].length; j < jlen; j++) {
                    var tap = ElX.taps[keyTemp][j];
                    if (tap) {
                        tap[0].call(tap[1], value, old, event, key);
                    }
                }
            }
        }
        var signature = "tap_" + key + "_0";
        if (ElX.refsTap[signature]) {
            event = {
                type: "tap",
                key: key
            };
            for (var i = 0, ilen = ElX.refsTap[signature].length; i < ilen; i++) {
                ElX.refsTap[signature][i].ontap(event);
            }
        }
    };
    ElX.sig = function(key, triggers, el) {
        var els = (key == "this") ? [el] : (key == "window" ? [window] : (ElX.refs[key] || []));
        triggers = triggers.split(" ");
        for (var i = 0, ilen = triggers.length; i < ilen; i++) {
            var parts = triggers[i].split(".");
            var e = {
                type: parts[0]
            };
            for (var j = 1, jlen = parts.length; j < jlen; j++) {
                switch (parts[j]) {
                    case "ctrl":
                        e.ctrlKey = 1;
                        break;
                    case "alt":
                        e.altKey = 1;
                        break;
                    case "shift":
                        e.shiftKey = 1;
                        break;
                    case "left":
                        e.button = 0;
                        break;
                    case "wheel":
                        e.button = 1;
                        break;
                    case "right":
                        e.button = 2;
                        break;
                    default:
                        e.key = parts[j];
                        break;
                }
            }
            for (var j = 0, jlen = els.length; j < jlen; j++) {
                ElX.event.call(els[j], e);
            }
        }
    };
    ElX.event = function(e) {
        e = e || window.event;
        if (ElX.mutationDepth < 1 && !e._x_stop) {
            var mask = 0;
            var els = null;
            var key = (e.key || String.fromCharCode(e.keyCode || e.which) || "").toLowerCase();
            var bitEK = (e.ctrlKey * ElX.bitEK.ctrl) | (e.altKey * ElX.bitEK.alt) | (e.shiftKey * ElX.bitEK.shift) | ((e.button === 0) * ElX.bitEK.left) | ((e.button === 1) * ElX.bitEK.wheel) | ((e.button === 2) * ElX.bitEK.right);
            var signature = e.type + "_" + key + "_" + bitEK;

            if (this === window) {
                if (!ElX.refsWin[signature]) {
                    signature = e.type + "__" + bitEK;
                }

                if (ElX.refsWin[signature]) {
                    els = ElX.refsWin[signature];
                }
            } else {
                if (this["_x_mask_" + signature] === undefined) {
                    signature = e.type + "__" + bitEK;
                }

                if (this["_x_mask_" + signature] !== undefined) {
                    els = [this];
                    mask = this["_x_mask_" + signature];
                    e._x_stop = !!(mask & ElX.bitEB.stop);
                }
            }

            if (els) {
                var clientX = 0;
                var clientY = 0;
                if (e.type.substring(0, 5) === "mouse") {
                    clientX = e.clientX;
                    clientY = e.clientY;
                }

                var scrollX = 0;
                var scrollY = 0;
                if (e.type === "scroll") {
                    scrollX = this !== window ? this.scrollLeft : (window.document.documentElement.scrollLeft || (window.document.body ? window.document.body.scrollLeft : 0) || 0);
                    scrollY = this !== window ? this.scrollTop : (window.document.documentElement.scrollTop || (window.document.body ? window.document.body.scrollTop : 0) || 0);
                }

                for (var i = 0, ilen = els.length; i < ilen; i++) {
                    ElX.events.push({
                        type: e.type,
                        key: key,
                        mod: bitEK,
                        clientX: clientX,
                        clientY: clientY,
                        scrollX: scrollX,
                        scrollY: scrollY,
                        element: els[i],
                        signature: signature
                    });
                }
            }

            if (!ElX.eventsTimer && ElX.events.length) {
                ElX.eventsTimer = setTimeout(function() {
                    while (ElX.events.length) {
                        ElX.processEvent(ElX.events.shift());
                    }
                    ElX.eventsTimer = null;
                }, 0);
            }

            return !(mask & ElX.bitEB.prevent);
        }
    };
    ElX.processEvent = function(event) {
        var el = event.element;
        var mode = "";
        var rules = [];
        var rulesObj = {};
        var ruleStr = el._x_obj[el["_x_rule_" + event.signature]];

        if (ruleStr === "") {
            mode = "*";
        } else if (ruleStr.charAt(0) === "!") {
            mode = "!";
            rules = ruleStr.substring(1).split(" ");
        } else {
            rules = ruleStr.split(" ");
        }

        for (var i = 0, ilen = rules.length; i < ilen; i++) {
            rulesObj[rules[i]] = true;
        }

        ElX.elThis = {};
        var paths = {
            window: window,
            this: el,
            vals: ElX.vals
        };
        for (var i = 0; i < el._x_arr.length; i++) {
            var attr = el._x_arr[i];
            var attrName = attr[0];
            var attrValue = attr[1];
            var prefix = attr[2];
            var key = attr[3];
            if (!(mode === "*" || (mode === "!" && !(rulesObj[key] || rulesObj[attrName])) || (mode === "" && (rulesObj[key] || rulesObj[attrName])))) {
                continue;
            }

            if (attrValue !== "" && attrValue.charAt(0) === "$") {
                var attrValue2 = attrValue.substring(1);
                if (!ElX.elThis[attrValue2]) {
                    ElX.elThis[attrValue2] = Util.path(paths, attrValue2.split("."));
                }
                attrValue = ElX.elThis[attrValue2];
            }

            if (prefix === "x-css-") {
                ElX.css(key, attr[4], attrValue, el);
            } else if (prefix === "x-set-") {
                var alt = el._x_obj["x-alt-" + key + "." + attr[4]];
                ElX.set(key, attr[4], [attrValue, (alt && alt.charAt(0) === "$" ? Util.path(paths, alt.substring(1).split(".")) : (alt !== undefined ? alt : attrValue))], el);
            } else if (prefix === "x-dom-") {
                ElX.dom(key, attr[5], attrValue, el);
            } else if (prefix === "x-val-") {
                ElX.val(key, attrValue, event);
            } else if (prefix === "x-sig-") {
                ElX.sig(key, attrValue, el);
            } else if (prefix === "x-lit-" && ElX.refs[key]) {
                if (ElX.isFocusing) {
                    clearTimeout(ElX.isFocusing);
                }
                (function(refEl, window, ElX) {
                    var attempts = 0;
                    var tryFocus = function() {
                        if (!refEl || !refEl.focus || refEl.disabled) {
                            return;
                        }
                        refEl.focus();
                        if (window.document.activeElement !== refEl && ++attempts < 60) {
                            ElX.isFocusing = setTimeout(tryFocus, 16);
                        }
                    };
                    tryFocus();
                })(ElX.refs[key][0], window, ElX);
            }
        }
    };

    function X(elx, key, value) {
        this.elx = elx;
        this.key = key;
        this.elx.vals[key] = value;
    }
    X.prototype.value = function() {
        return this.elx.vals[this.key];
    };
    X.prototype.ref = function() {
        return this.elx.refs[this.key] || [];
    };
    X.prototype.tap = function(func, context) {
        return this.elx.tap(this.key, func, context);
    };
    X.prototype.untap = function(index) {
        this.elx.untap(this.key, index);
    };
    X.prototype.css = function(attr, value) {
        this.elx.css(this.key, attr, value);
    };
    X.prototype.set = function(attr, states) {
        this.elx.set(this.key, attr, states);
    };
    X.prototype.dom = function(keys, value) {
        this.elx.dom(this.key, keys, value);
    };
    X.prototype.val = function(value, event) {
        this.elx.val(this.key, value, event);
    };
    X.prototype.sig = function(triggers) {
        this.elx.sig(this.key, triggers);
    };
    X.prototype.clear = function() {
        this.elx.clear(this.key);
    };

    window.Util = Util;
    window.Url = Url;
    window.Step = Step;
    window.Xhr = Xhr;
    window.El = El;
    window.ElX = ElX;

    var init = window.init || [];
    window.init = {
        push: function(fn) {
            fn();
        }
    };
    for (var i = 0, ilen = init.length; i < ilen; i++) {
        init[i]();
    }
})();