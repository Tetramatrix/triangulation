<?php

/***************************************************************
* Copyright notice
*
* (c) 2010-2013 Chi Hoang (info@chihoang.de)
*  All rights reserved
*
***************************************************************/
require_once("delaunay.php");

// Turn off all error reporting
error_reporting(0);
 
// example 1
$triangle=new DelaunayTriangulation();
$triangle->main();

$vis=new visualize("c:\Temp\\",$triangle);
$vis->genimage();
      
//example2
$set=array();
$tree=array(172,31,238,106,233,397,118,206,58,28,268,382,10,380,342,26,67,371,380,14,382,200,24,200,194,190,10,88,276);
for ($i=0,$end=count($tree);$i<$end;$i+=2)
{
    $set[]=array($tree[$i],$tree[$i+1]);    
}
$triangle->main($set);
 
$vis=new visualize("/tmp/",$triangle);
$vis->genimage(); 

//example3
$set=array();
$tree=array(172,31,238,106,233,397,118,206,58,28,268,382,10,380,342,26,67,371,380,14,382,200,24,200,194,190,10,88,276);
for ($i=0,$end=count($tree);$i<$end;$i+=2)
{
    $set[]=array($tree[$i],$tree[$i+1]);    
}
$triangle->main($set);
print_r($triangle->delaunay); 
?>