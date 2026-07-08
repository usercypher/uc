<!-- IF ELSE -->
<?php if ($condition) : ?>
    <!-- Code to run if condition is true -->
<?php elseif ($condition) : ?>
    <!-- Code to run if condition is true -->
<?php else : ?>
    <!-- Code to run if condition is false -->
<?php endif; ?>



<!-- FOREACH LOOP -->
<?php foreach ($array as $item) : ?>
    <!-- Code to run for each item in array -->
<?php endforeach; ?>



<!-- WHILE LOOP -->
<?php while ($condition) : ?>
    <!-- Code to run while condition is true -->
<?php endwhile; ?>



<!-- FOR LOOP -->
<?php for ($i = 0; $i < 10; $i++) : ?>
    <!-- Code to run for each iteration -->
<?php endfor; ?>



<!-- SWITCH -->
<?php switch ($value) : ?>
    <?php case 'option1' : ?>
        <!-- Code to run for option1 -->
        <?php break; ?>
    <?php case 'option2' : ?>
        <!-- Code to run for option2 -->
        <?php break; ?>
    <?php default : ?>
        <!-- Code to run for default case -->
<?php endswitch; ?>


