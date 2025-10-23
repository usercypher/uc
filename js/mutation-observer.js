new MutationObserver(function(mutationsList) {
    for (var i = 0; i < mutationsList.length; i++) {
        var mutation = mutationsList[i];
        if (mutation.type === 'childList') {
            for (var j = 0; j < mutation.addedNodes.length; j++) {
                var node = mutation.addedNodes[j];
                if (node.nodeType === 1) {
                    var els = (node.parentNode && node.parentNode.getElementsByTagName) ? node.parentNode.getElementsByTagName("*") : [node];
                    window.tag.init(els);
                    if (window.elx !== undefined) {
                        window.elx.init(els);
                        window.elx.clean();
                    }
                }
            }
        }
    }
}).observe(document, {
    childList: true,
    subtree: true
});