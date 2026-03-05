/**************************************************************
 * 📦 Util – Usage Examples
 **************************************************************/

const htmlEncoded = Util.htmlEncode("<p>hello</p>");

const htmlDecoded = Util.htmlDecode(htmlEncoded);

const trimmed = Util.trim("  hello  ");

const replaced = Util.strReplace("hello {name}", {
    "{name}": "john"
});

const size = Util.strSizeOf("Hello 🌍"); 

const queryStr = Util.queryBuild({ name: "John", els: ["js", "dev"] });

const obj = Util.queryParse("name=John&els[]=js&els[]=dev");

const debouncedFn = Util.debounce(() => console.log("Run"), 300);
debouncedFn();
debouncedFn.cancel();

const throttledFn = Util.throttle(() => console.log("Run"), 300);
throttledFn();
throttledFn.cancel();

Util.poll(function () {
    // usefull to check dependency like return lib1 && lib2;
    return true;
}, function {
    // code
}, {
    interval: 100,
    timeout: 5000,
    ontimeout: function () {
        // run on timeout
    }
});


var retryCount = 0;
function loadFeature() {
    // 'async::' loads the script without blocking urls
    Util.script(['async::lib.js', 'async::feature.js'], {
        onload: function () {
            initFeature();
        },
        onerror: function (error) {
            // error.content
            // error.retry()
            console.log(error.content);
            if (retryCount < 3) {
                error.retry();
            }
        },
        timeout: 10000
    });
};

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
 * 🔗 Callstep – Usage Examples
 **************************************************************/

const step = new Step();

var index = step.add(function (self, data) {
    self.next().run(data); // next() increment index, then run next 
    self.run(data); // it's run current function, since it not move index
    // .at() // return current index
    // .to(index) // move index specifically
    self.to(self.at()).run(data);
});

step.run();


/**************************************************************
 * 📡 Request – Usage Examples
 **************************************************************/

const req = new Xhr(new XMLHttpRequest());

// Send GET request
req.send("https://api.example.com/info", {
  method: "GET",
  header: {
    "Accept": "application/json"
  },
  timeout: 5,
  onload: responseProcessor
});

// req.abort();

/**************************************************************
 * 📥 Response – Usage Example
 **************************************************************/

function responseProcessor(response) {
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

var resolve = {
  api: new Xhr(new XMLHttpRequest()),
  apiUrl: new Url("https://api.example.com/data"),
  maxRetry: 3,
  output: new El("output")
};

function urlWrapper(url) {
  return api
  .setQuery("category", "books")
  .setQuery("limit", 5)
  .setHash("results");
}

urlWrapper(resolve.apiUrl);

// Update browser's URL bar
urlWrapper(new Url()).sync(true);

// Output element
resolve.output.html("<p>Loading...</p>");

// Create and send a request
const newStep = new Step();

newStep.add(function (self, data) {
  data.api.send(data.apiUrl.toString(), {
    method: "GET",
    header: { "Accept": "application/json" },
    onload: function (res) {
      if (res.code === 200) {
        data.output.html(`<pre>${res.content}</pre>`);
      } else if (0 !== data.maxRetry--) {
        data.output.html(`<p style="color:red;">Error ${res.code}</p>`);
        self.run(data);
      }
    }
  });
});

newStep.run(resolve);
