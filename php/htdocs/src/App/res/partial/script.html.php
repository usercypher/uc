<?php 

$app = $data['app'];

$flash = $data['flash'];

?>

    <span x-src="modal-open" data-ref="flash" style="display: none;"></span>

    <!-- Flash Messages Modal -->
    <div class="modal-overlay _" x-src="modal-overlay" data-ref="flash" data-static>
        <div class="modal-container" x-src="modal-container">
            <div class="modal-header">
                <h3 x-src="modal-title" data-ref="flash">Notification</h3>
                <button class="modal-close" x-src="modal-close modal-tab-first" data-ref="flash">&times</button>
            </div>
            <div class="modal-body" x-src="modal-desc" data-ref="flash">
                <ul id="flash"></ul>
            </div>
            <div class="modal-footer">
                <input type="button" x-src="modal-close modal-tab-last" data-ref="flash" value="Close" />
            </div>
        </div>
    </div>

    <script>
        (window.init = window.init || []).push(function () {
            Util.poll(function () {
                return window.ElXInit;
            },
            function () {
                var flash = <?php echo json_encode($flash); ?>;

                if (flash) {
                    flashTpl(flash);
                    ElX.run("modal-open-flash", "x-on-click");
                }
            });

            function flashTpl(flash) {
                var flashHtml = "";
                for (var i = 0, ilen = flash.length; i < ilen; i++) {
                    flashHtml += `<li><b>${flash[i].type}: </b>${JSON.stringify(flash[i].data)}</li>`;
                }
                document.getElementById("flash").innerHTML = flashHtml;
            }
        })();
    </script>

    <script>
        (window.init = window.init || []).push(function () {
            Util.script([
                "<?php echo $app->urlWeb("asset/js/tag/modal.js"); ?>"
            ], {
                onload: function () {
                    ElX.init(document.documentElement);
                    window.ElXInit = true;
                }
            });
        })();
    </script>
