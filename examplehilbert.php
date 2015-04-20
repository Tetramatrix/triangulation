<?php
/***************************************************************
*  hilbert curve
*  Version 0.3
*  
*  Copyright (c) 2010-2015 Chi Hoang 
*  All rights reserved
***************************************************************/
require_once("hilbert.php");

$hilbert=new hilbert();
foreach (range(31,0,-1) as $x)
{
    foreach (range(31,0,-1) as $y)
    {
	$sort[] = $points["$x, $y"] = $hilbert->point2moore($x, $y, 4);
    }
}
array_multisort($points, $sort);
foreach ($points as $k => $v)
{
    echo $k."\n";
}
    
foreach (range(7,0,-1) as $x)
{
    foreach (range(7,0,-1) as $y)
    {
	$sort[] = $points["$x, $y"] = $hilbert->point2hilbert($x, $y, 3);
    }
}
array_multisort($points, $sort);
foreach ($points as $k => $v)
{
    echo $k."\n";
}
?>