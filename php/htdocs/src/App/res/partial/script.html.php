<?php 

$app = $data['app'];

$flash = $data['flash'];

?>

    <span style="display: none;" data-ref="flash" x-src-modal-open></span>

    <!-- Flash Messages Modal -->
    <div class="_ modal" data-ref="flash" x-src-modal>
        <div class="modal-content" data-ref="flash" x-src-modal-content>
            <h3 data-ref="flash" x-src-modal-label>Notification</h3>
            <ul style="overflow: auto;" data-ref="flash" x-src-modal-description></ul>
            <input type="button" style="float: right; margin-left: 1em;" value="Close" data-ref="flash" x-src-modal-close x-src-modal-tab-start x-src-modal-tab-end />
        </div>
    </div>
<div id="test"></div>
    <script>
        (window.init = window.init || []).push(function () {
            Util.poll(function () {
                return window.ElXInit;
            }, function () {
                var flash = <?php echo json_encode($flash); ?>;

                if (flash) {
                    flashTpl(flash);
                    ElX.run("modal-open-flash", "click");
                }
            });

            function flashTpl(flash) {
                var flashHtml = "";
                for (var i = 0, ilen = flash.length; i < ilen; i++) {
                    flashHtml += `<li><b>${flash[i].type}: </b>${JSON.stringify(flash[i].data)}</li>`;
                }
                document.getElementById("modal-description-flash").innerHTML = flashHtml;                
            }
        })();
    </script>

    <script>
        (window.init = window.init || []).push(function () {
            Util.script([
                "async::<?php echo $app->urlWeb("asset/js/src/modal.js"); ?>"
            ], {
                onload: function () {
                    ElX.init(document.documentElement);
                    window.ElXInit = true;
                }
            });
        })();
    </script>
