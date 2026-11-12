<?php 

foreach (array(
    'route',
    'flash'
) as $v) {
    $$v = $data[$v];
}

?>

<?php if ($flash && $route === (isset($flash[0]['data']['route']) ? $flash[0]['data']['route'] : '')) : ?>
<div id="flash" style="border: 1px solid ButtonFace; padding: 1em;">
    <script type="text/javascript">
        document.write('<div style="float: right; cursor: pointer; font-weight: bold; padding: 0 4px;" onclick="document.getElementById(\'flash\').style.display=\'none\';">&#215;</div>');
    </script>
    <h6>Messages</h6>
    <ul>
        <?php foreach ($flash as $item) : ?>
            <li>
                <b><?php echo isset($item['data']['field']) ? htmlspecialchars($item['data']['field']) . ':': ''; ?></b>
                <?php echo isset($item['data']['content']) ? htmlspecialchars($item['data']['content']) : ''; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>