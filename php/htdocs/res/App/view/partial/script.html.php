<?php 

$flash = $data['flash'];

?>
    <span
        x-ref-window
        x-on-key-window="escape"        
    ></span>

    <span
        x-ref--flash-open
        x-on-click
        x-rot--flash=""
        x-rot--flash-content=""
        x-focus="-flash-tab-last"
        x-tab="-flash-tab-last:-flash-tab-last"
        x-set-window.x-on-key-window-escape="-flash-close"
        x-set-window.x-run--flash-close="x-on-click"
    >
    </span>

    <!-- Flash Messages Modal -->
    <div class="modal hidden" x-ref--flash x-on-click x-run--flash-close="x-on-click">
        <div class="form-container modal-content small" x-ref--flash-content x-on-click x-stop>
            <span class="modal-close" x-ref--flash-close x-on-click x-rot--flash="hidden" x-rot--flash-content="small">&times;</span>
            <h2>Notification</h2>
            <ul id="flash"></ul>
            <button type="button" class="button" x-ref--flash-tab-last x-on-click x-run--flash-close="x-on-click">Ok</button>
        </div>
    </div>
    <script>
        (window.init = window.init || []).push(function () {
            Utils.run(function () {
                return window.ElXInit;
            },
            function () {
                var flash = <?php echo json_encode($flash); ?>;

                if (flash) {
                    flashTpl(flash);
                    ElX.run("-flash-open", "x-on-click");
                }
            });

            function flashTpl(flash) {
                var flashHtml = "";
                for (var i = 0, ilen = flash.length; i < ilen; i++) {
                    flashHtml += `<li><b>${flash[i].type}: </b>${flash[i].message} : ${JSON.stringify(flash[i].meta)}</li>`;
                }
                document.getElementById("flash").innerHTML = flashHtml;
            }
        })();
    </script>

    <script>
        (window.init = window.init || []).push(function () {
            ElX.init(document.getElementsByTagName("*"));
            window.ElXInit = true;
        })();
    </script>
