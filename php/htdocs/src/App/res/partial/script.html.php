<?php 

$app = $data['app'];

$flash = empty($data['flash']) ? null : $data['flash'];

?>

    <span style="display: none;" data-ref="flash" x-use-modal-open></span>

    <!-- Flash Messages Modal -->
    <div class="modal" data-ref="flash" x-use-modal>
        <div class="modal-content" data-ref="flash" x-use-modal-content>
            <div style="padding: 1em;">
                <h3 data-ref="flash" x-use-modal-label>Notification</h3>

                <ul style="overflow: auto;" data-ref="flash" x-use-modal-description></ul>
                <input type="button" style="float: right; margin-bottom: 1em;" value="Close" data-ref="flash" x-use-modal-close x-use-modal-tab-start x-use-modal-tab-end />
            </div>
        </div>
    </div>

    <script>
        (window.init = window.init || []).push(function () {
            Util.poll(function () {
                return window.ElXInit;
            }, function () {
                var flash = <?php echo json_encode($flash); ?>;

                if (flash) {
                    flashTpl(flash);
                    ElX.sig("modal-open-flash", "click");
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
                "async::<?php echo $app->urlWeb("asset/js/use/modal.js"); ?>"
            ], {
                onload: function () {
                    ElX.init(window.document.documentElement);
                    window.ElXInit = true;
                }
            });
        })();
    </script>
