
    <span
        x-ref-window
        x-on-key-window="escape"        
    ></span>

    <span
        x-ref--flash-open
        x-on-click
        x-cycle--flash=""
        x-cycle--flash-content=""
        x-focus="-flash-tab-last"
        x-tab="-flash-tab-last:-flash-tab-last"
        x-attr-window_x-on-key-window-escape="-flash-close"
        x-attr-window_x-run--flash-close="x-on-click"
    >
    </span>

    <!-- Flash Messages Modal -->
    <div class="modal hidden" x-ref--flash>
        <span class="modal-content-overlay" tabindex="-1" x-on-key="escape" x-on-click x-run--flash-close="x-on-click"></span>

        <div class="form-container modal-content small" x-ref--flash-content>
            <span class="modal-close" x-ref--flash-close x-on-click x-cycle--flash="hidden" x-cycle--flash-content="small">&times;</span>
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
        (function() {
            window.onload = function() {
                var isFlash = <?= empty($flash) ? 'false' : 'true' ?>;
                var tagx = new TagX();
                tagx.register(document.getElementsByTagName("*"));
                if (isFlash) {
                    tagx.run("-flash-open", "x-on-click");
                }
            };
        })();
    </script>
