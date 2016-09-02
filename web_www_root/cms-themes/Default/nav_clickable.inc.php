<?php
$classes = array();
if ($children)
    $classes[] = 'parent';

if ($menu_class)
    $classes[] = $menu_class;

if ($first)
    $classes[] = 'first';

if ($last)
    $classes[] = 'last';

$class = ' class="'.implode(' ', $classes).'"';

$target = '';
if ($open_new_window)
    $target = ' target="_blank"';

?>
<li<?=$class?>><a href="<?=$permalink?>"<?=$target?>><?=$name?></a><?=$children?></li>
