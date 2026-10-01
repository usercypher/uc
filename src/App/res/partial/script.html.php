<?php 

foreach (array(
    'app',
    't',
    'flash'
) as $v) {
    $$v = $data[$v];
}

$flash = empty($data['flash']) ? null : $data['flash'];

?>
<!--
    <span
        x-ref-time-render
        x-evt-click 
        x-val-time-render
        data-time-mode="relative"
        data-time="<?= gmdate('Y-m-d\TH:i:s\Z') ?>" 
        data-time-format=""
    >
        <?= gmdate('Y-m-d\TH:i:s\Z') ?>
    </span>
-->
    

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
        // Define all helper functions BEFORE window.init
        var pad = function (n) {
            return n < 10 ? '0' + n : '' + n;
        };

        var buildDateMap = function (d, useUTC) {
            var u = useUTC ? 'UTC' : '';
            var Y = d['get' + u + 'FullYear']();
            var M = d['get' + u + 'Month']() + 1;
            var D = d['get' + u + 'Date']();
            var H = d['get' + u + 'Hours']();
            var m = d['get' + u + 'Minutes']();
            var s = d['get' + u + 'Seconds']();
            var h = H % 12 || 12;

            return {
                Y: Y,             y: String(Y).slice(-2),
                m: pad(M),        n: M,
                d: pad(D),        j: D,
                H: pad(H),        G: H,
                h: pad(h),        g: h,
                i: pad(m),        s: pad(s),
                a: H < 12 ? 'am' : 'pm',
                A: H < 12 ? 'AM' : 'PM'
            };
        };

        var formatDate = function (fmt, map) {
            return fmt.replace(/[YymndjHGhgisAa]/g, function (c) {
                return map[c] !== undefined ? map[c] : c;
            });
        };

        var formatRelative = function (date, fmt, map) {
            var sec = (new Date().getTime() - date.getTime()) / 1000;
            if (sec < 60)          return 'just now';
            if (sec < 3600)        return Math.floor(sec / 60) + 'm ago';
            if (sec < 86400)       return Math.floor(sec / 3600) + 'h ago';
            if (sec < 604800)      return Math.floor(sec / 86400) + 'd ago';
            return formatDate(fmt, map);
        };

        var renderTimeSpan = function (span) {
            var iso = span.getAttribute('data-time');
            if (!iso) return false;

            var mode = span.getAttribute('data-time-mode') || 'absolute';
            var d    = new Date(iso);
            var fmt  = span.getAttribute('data-time-format') || 'Y-m-d H:i:s';
            var utc  = span.getAttribute('data-time-utc') === 'true';

            var map  = buildDateMap(d, utc);
            var text = mode === 'relative'
                ? formatRelative(d, fmt, map)
                : formatDate(fmt, map);

            span.innerHTML = text;
            return mode;
        };

        (window.init = window.init || []).push(function () {
            Util.script([
                "async::<?php echo $app->url('web', "asset/js/use/modal.js"); ?>"
            ], {
                onload: function () {
                    ElX.init(window.document.documentElement);
                    window.ElXInit = true;
                    ElX.tap("time-render", function (current, old, event) {  
                        renderTimeSpan(event.element);
                    });
                    ElX.sig("time-render", "click");
                    setInterval(function () { ElX.sig("time-render", "click"); }, 60000);
                }
            });
        });
    </script>