<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2015 Chi Hoang (info@chihoang.de)
*  All rights reserved
*
***************************************************************/
require_once("delaunay.php");

// Turn off all error reporting
//error_reporting(0);
 
// example 1
$triangle=new DelaunayTriangulation();
$triangle->main();

//$vis=new visualize("c:\Temp\\",$triangle);
$i=new Image("/tmp/",$triangle);
$i->create();
      
//example2
$set=array(172,31,238,106,233,397,118,206,58,28,268,382,10,380,342,26,67,371,380,14,382,200,24,200,194,190,10,88,276,19);
$triangle->main($set);
$i=new Image("/tmp/",$triangle);
$i->create(); 

//example3
$set=array(172,31,238,106,233,397,118,206,58,28,268,382,10,380,342,26,67,371,380,14,382,200,24,200,194,190,10,88,276,19);
$triangle->main($set);
print_r($triangle->triangle); 
?>