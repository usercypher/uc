<?php 

foreach (array(
    'app',
    't',
    'translation_dir',
    'flash'
) as $v) {
    $$v = $data[$v];
}

$t->load($translation_dir);

$flash = empty($data['flash']) ? null : $data['flash'];

?>

    <span style="display: none;" data-ref="flash" x-use-modal-open></span>

    <div class="uc-modal" data-ref="flash" x-use-modal>
        <div class="uc-modal-content" data-ref="flash" x-use-modal-content>
            <div style="padding: 1em;">
                <h3 data-ref="flash" x-use-modal-label><?php echo $t->t('notification'); ?></h3>

                <ul style="overflow: auto;" data-ref="flash" x-use-modal-description></ul>
                <input type="button" style="float: right; margin-bottom: 1em;" value="<?php echo $t->t('close'); ?>" data-ref="flash" x-use-modal-close x-use-modal-tab-start x-use-modal-tab-end />
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
                    flashHtml += `<li><b>${flash[i].type}: </b><span style="white-space: pre-wrap;">${JSON.stringify(flash[i].data, null, 2)}</span></li>`;
                }
                document.getElementById("modal-description-flash").innerHTML = flashHtml;                
            }
        })();
    </script>

    <script>
        (window.init = window.init || []).push(function () {
            Util.script([
                "async::<?php echo $app->url('WEB', "asset/js/use/modal.js"); ?>"
            ], {
                onload: function () {
                    ElX.init(window.document.documentElement);
                    window.ElXInit = true;
                }
            });
        })();
    </script>
