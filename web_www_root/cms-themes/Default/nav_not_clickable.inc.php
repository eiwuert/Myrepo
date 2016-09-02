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
?>
<li<?=$class?>><a href="#" class="no_click"><?=$name?></a><?php if ($level > SUBMENUS):?></li><?php endif; ?><?=$children?><?php if ($level <= SUBMENUS):?></li><?php endif; ?>
