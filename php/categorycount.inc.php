<?php

function drawNode($category, $count, $parent, $x, $y)
{
        $width = 100;
        $height = 50;

        $link = new XML_SVG_A(array('href' => 'http://nl.wikipedia.org/wiki/Categorie:' . $category));
        $link->addParent($parent);

        $rect = new XML_SVG_Rect(array('x' => $x, 'y' => $y, 'width' => $width, 'height' => $height, 'rx' => 2, 'ry' => 2, 'style' => 'stroke: black; fill: lightblue;'));
        $rect->addParent($link);

        $text = new XML_SVG_Text(array('x' => $x + $width/2, 'y' => $y + 20, 'style' => '" text-anchor = "middle" dominant-baseline = "mathematical" font-weight = "bold" font-size="12', 'text' => $category));
        $text->addParent($link);

        $text = new XML_SVG_Text(array('x' => $x + $width/2, 'y' => $y + 40, 'style' => '" text-anchor = "middle" dominant-baseline = "mathematical" font-size="12', 'text' => $count));
        $text->addParent($link);
}
?>
