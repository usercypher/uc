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

/*
El(
    tag,         // "#id", "div", {El: arguments}, undefined or null creates fragmenr
    attr,        // [["name", "value"]]
    ...children, // [...children], El(), {El: arguments, replace: bool}, "string"
) : return Element
*/
var pElement = El("p", null, "Hello, world!");

/*
El(...same with El()) : return {El: arguments}
*/
// note: using as child in El would reuse the child node if .replace = true, since it's not define by default it will reuse children 
var pElObject = El.use("p", null, "Hello, world!");

/*
El.insert(
    position,   // "inner", "append", "prepend", "before", "after"
    parentNode, // element
    childNode   // element
) : null
*/
El.insert("append", El("#root"), El("div", null, "New content"));

/*
El.buffer(
    position,   // "inner", "append", "prepend", "before", "after"
    parentNode, // element
    childNode   // element
    milliseconds// time debounced
) : null
*/
// note: changing position will run debounced so to avoid breaking layout
El.buffer("append", El("#root"), El("div", null, "New content"), 100);

// remove element
El.remove(El("#root"));

// clear children nodes / content
El.clear(El("#root"));

/**************************************************************
 * 🚀 Combine All – Practical Flow
 **************************************************************/

var resolve = {
  api: new Xhr(new XMLHttpRequest()),
  apiUrl: new Url("https://api.example.com/data"),
  maxRetry: 3,
  output: El("#output")
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
El.insert("inner", resolve.output, El("p", null, "Loading..."));

// Create and send a request
const newStep = new Step();

newStep.add(function (self, data) {
  data.api.send(data.apiUrl.toString(), {
    method: "GET",
    header: { "Accept": "application/json" },
    onload: function (res) {
      if (res.code === 200) {
        El.insert("inner", data.output, El("pre", null, `${res.content}`));
      } else if (0 !== data.maxRetry--) {
        El.insert("inner", data.output, El("p", [["style", "color:red;"]], `Error ${res.code}`));
        self.run(data);
      }
    }
  });
});

newStep.run(resolve);
