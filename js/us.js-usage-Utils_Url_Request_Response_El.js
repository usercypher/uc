/**************************************************************
 * 📚 Lightweight JS Utility Library
 * 
 * 📦 Modules:
 * - Utils: string size, debounce/throttle, query object utils
 * - Url: easy query/hash manipulation + history sync
 * - Request: XMLHttpRequest wrapper with caching and retry
 * - Response: wraps XHR into clean object
 * - El: simple DOM content handling
 **************************************************************/

/**************************************************************
 * 📦 Utils – Usage Examples
 **************************************************************/

// Calculate byte size of a string (handles emojis and surrogate pairs)
const size = Utils.strSizeOf("Hello 🌍"); 
// Example result: 12

// Convert object to query string
const queryStr = Utils.objectToQuery({ name: "John", els: ["js", "dev"] });
// "name=John&els[]=js&els[]=dev"

// Parse query string into an object
const obj = Utils.queryToObject("name=John&els[]=js&els[]=dev");
// { name: "John", els: ["js", "dev"] }

// Debounce a function (run after delay if no more calls)
const debouncedFn = Utils.debounce(() => console.log("Run"), 300);
debouncedFn();
debouncedFn.cancel(); // cancel pending call

// Throttle a function (run once per time period)
const throttledFn = Utils.throttle(() => console.log("Run"), 300);
throttledFn();
throttledFn.cancel(); // cancel scheduled call

// run(funcCondition, funcCallback, options)
// options - {interval: 100, timeout: 5000}
Utils.run(function () {
    // usefull to check dependency like return elx && Utils;
    return true;
}, function {
    // code
}, { interval: 100, timeout: 5000 });

// Utils.script
<button onclick="loadFeature()">Load Feature</button>

<script>
    var retryCount = 0;
    function loadFeature() {
        // Utils.script(srcs, successCallback, failureCallback(url, retry))
        // 'async::' loads the script without blocking and calls successCallback immediately if no next script
        Utils.script(['async::lib.js', 'feature.js'], function () {
            initFeature();
        }, function (url, retry) {
            if (retryCount < 3) {
                retry();
            }
        });
    };
</script>
/**************************************************************
 * 🔗 Url – Usage Examples
 **************************************************************/

const url = new Url("https://example.com/page?lang=en#top");

// Modify query string
url.setQuery("page", 2).setQuery("sort", "desc");
const pageVal = url.getQuery("page"); // "2"

// Remove query parameter
url.removeQuery("lang");

// Modify hash
url.setHash("section2");

// Convert back to full URL
const fullUrl = url.toString();
// "https://example.com/page?page=2&sort=desc#section2"

// Push or replace in browser history
url.sync();       // pushState
url.sync(true);   // replaceState

/**************************************************************
 * 🔗 Callstack – Usage Examples
 **************************************************************/

const stack = new Callstack();

stack.add(function (self) {
    self.next(); // call next in stack
});

stack.inject(function (self) {

});

stack.retry();
stack.retryAll();
stack.reset();

/**************************************************************
 * 📡 Request – Usage Examples
 **************************************************************/

const req = new Request(new XMLHttpRequest());

// Send GET request
var response = req.send("https://api.example.com/info", {
  method: "GET",
  header: {
    "Accept": "application/json"
  },
  timeout: 5
}, responseProcessor);

// req.abort();

/**************************************************************
 * 📥 Response – Usage Example
 **************************************************************/

function responseProcessor(response) {
    // run after request
    const status = response.code;
    const content = response.content;
    const header = response.header;
}

/**************************************************************
 * 🧩 El – Usage Examples
 **************************************************************/

const el = new El("output");

// Replace content
el.html("<p>Hello, world!</p>");

// Append new HTML (temporarily backed up content)
el.store().append("<div>New content</div>");

// Restore previous content (when temp was set)
el.restore().append("<span>Updated again</span>");

// Prepend content
el.prepend("<h1>Header</h1>");

// insert html in before of element, store() & restore() will not affect it
el.before("<span>before</span>");

// insert html in after of element, store() & restore() will not affect it
el.after("<span>after</span>");

// Direct DOM access still possible
el.el.addEventListener("click", () => alert("Clicked!"));

// remove an element
new El('element').remove();

/**************************************************************
 * 🚀 Combine All – Practical Flow
 **************************************************************/

const apiUrl = new Url("https://api.example.com/data");
apiUrl.setQuery("category", "books").setQuery("limit", 5).setHash("results");

// Update browser's URL bar
new Url()
  .setQuery("category", "books")
  .setQuery("limit", 5)
  .setHash("results")
  .sync(true);

// Output element
const output = new El("output");
output.html("<p>Loading...</p>", true);

// Create and send a request
const newStack = new Callstack();
newStack.setData('api', new Request(new XMLHttpRequest()));

newStack.add(function (self) {
  self.getData('api').send(apiUrl.toString(), {
    method: "GET",
    header: { "Accept": "application/json" }
  }, function (res) {
    if (res.code === 200) {
      output.html(`<pre>${res.content}</pre>`);
    } else {
      output.html(`<p style="color:red;">Error ${res.code}</p>`);
      self.retry().next();
    }
  });
}).next();

