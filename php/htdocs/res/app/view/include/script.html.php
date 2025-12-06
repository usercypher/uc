
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
    <div class="modal hidden" x-ref--flash x-on-key="escape" x-on-click x-run--flash-close="x-on-click">
        <div class="form-container modal-content small" x-ref--flash-content x-on-click x-stop>
            <span class="modal-close" x-ref--flash-close x-on-click x-rot--flash="hidden" x-rot--flash-content="small">&times;</span>
            <h2>Notification</h2>
            <ul>
            <?php foreach ($flash as $f) : ?>
                <li><b><?= $f['type'] . ': ' ?></b> <?= $f['message'] ?></li>
            <?php endforeach ?>
            </ul>
            <button type="button" class="button" x-ref--flash-tab-last x-on-click x-run--flash-close="x-on-click">Ok</button>
        </div>
    </div>

    <script>
        (window.init = window.init || []).push(function () {
            window.onload = function() {
                var isFlash = <?= empty($flash) ? 'false' : 'true' ?>;
                ElX.init(document.getElementsByTagName("*"));
                if (isFlash) {
                    ElX.run("-flash-open", "x-on-click");
                }
            };
        })();
    </script>

    <?php require($app->dirRoot('res/uc/view/include/performance.html.php')); ?>
