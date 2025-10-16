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
 * 📡 Request – Usage Examples
 **************************************************************/

const req = new Request(new XMLHttpRequest());

// Enable caching with limits
req.setCache(true)
   .setCacheSize(1)  // 1 MB max
   .setCacheTTL(30); // 30 seconds TTL

// Store custom data
req.setData("authToken", "abc123");
const token = req.getData("authToken");

// Add a response handler
req.addCallback((req, res) => {
  console.log("Status:", res.code);
  console.log("Body:", res.content);
});

// Send GET request
req.send("https://api.example.com/info", {
  method: "GET",
  headers: {
    "Accept": "application/json"
  },
  timeout: 5
});

// Abort if needed
// req.abort();

// Retry logic
req.retry();     // Retry last
req.retryAll();  // Retry all previous

/**************************************************************
 * 📥 Response – Usage Example
 **************************************************************/

req.addCallback((request, response) => {
  const status = response.code;
  const content = response.content;
  const headers = response.headers;
  // Do something with response
});

/**************************************************************
 * 🧩 Script – Usage Examples
 **************************************************************/

<button onclick="loadFeature()">Load Feature</button>

<script>
    var script = new Script();

    function loadFeature() {
        // load(srcs, successCallback, failureCallback)
        script.load(['lib.js', 'feature.js'], function () {
            initFeature();
        });
    };
</script>

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
const dataReq = new Request(new XMLHttpRequest());
dataReq
  .setCache(true)
  .setCacheSize(2)    // 2MB max
  .setCacheTTL(60)    // 60s TTL
  .addCallback((req, res) => {
    if (res.code === 200) {
      output.html(`<pre>${res.content}</pre>`);
    } else {
      output.html(`<p style="color:red;">Error ${res.code}</p>`);
      req.retry().send(apiUrl.toString(), {
        method: "GET",
        headers: { "Accept": "application/json" }
      });
    }
  })
  .send(apiUrl.toString(), {
    method: "GET",
    headers: { "Accept": "application/json" }
  });
