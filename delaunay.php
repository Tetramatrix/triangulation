<?php
/*****************************************************************
 * Delaunay triangulation
 * 
 * Copyright (c) 2013-2015 Chi Hoang (info@chihoang.de)
 * All rights reserved
 ****************************************************************/
require_once("hilbert.php");

define("EPSILON",0.000001);
define("SUPER_TRIANGLE",(float)1000000000);

class Triangle {
   var $x,$y,$z;
   function __construct($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4,$x5,$y5,$x6,$y6) {
      $this->x=new Point(new Edge($x1,$y1),new Edge($x2,$y2));
      $this->y=new Point(new Edge($x3,$y3),new Edge($x4,$y4));
      $this->z=new Point(new Edge($x5,$y5),new Edge($x6,$y6));
   }
}

class Indices {
   var $x,$y,$z;
   function __construct($x,$y,$z) {
      $this->x=$x;
      $this->y=$y;
      $this->z=$z;
   }
}

class Edge
{
   var $e;
   function __construct($x,$y) {
      $this->e=new Point($x,$y);
   }
   
   public function __get($field) {
      if($field == 'x')
      {
	return $this->e->x;
      } else if($field == 'y')
      {
	 return $this->e->y;
      }
   }
}

class Point
{
   var $x,$y;
   function __construct($x,$y) {
      $this->x=$x;
      $this->y=$y;
   }
}

  // circumcircle
class Circle
{
   var $x, $y, $r, $r2, $colinear;
   function __construct($x, $y, $r, $r2, $colinear)
   {
      $this->x = $x;
      $this->y = $y;
      $this->r = $r;
      $this->r2 = $r2;
      $this->colinear=$colinear;
   }
}

class Image
{
   var $path, $stageWidth, $stageHeight, $triangle;
   
   function __construct($path,$pObj)
   {
      $this->path=$path;
      $this->stageWidth=$pObj->stageWidth;
      $this->stageHeight=$pObj->stageHeight;
      $this->triangle=$pObj->triangle;
   }
   
   function erropen()
   {
      print "Cannot open file";
      exit;
   }
   
   function errwrite()
   {
      print "Cannot write file";
      exit;
   }
   
   function create()
   {
         // Generate the image variables
      $im = imagecreate($this->stageWidth,$this->stageHeight);
      $white = imagecolorallocate ($im,0xff,0xff,0xff);
      $black = imagecolorallocate($im,0x00,0x00,0x00);
      $gray_lite = imagecolorallocate ($im,0xee,0xee,0xee);
      $gray_dark = imagecolorallocate ($im,0x7f,0x7f,0x7f);
     
      // Fill in the background of the image
      imagefilledrectangle($im, 0, 0, $this->stageWidth+100, $this->stageHeight+100, $white);
            
      foreach ($this->triangle as $key => $arr)
      {
         foreach ($arr as $ikey => $iarr)
         {
            list($x1,$y1,$x2,$y2) = array($iarr->x->x,$iarr->x->y,$iarr->y->x,$iarr->y->y);
            imageline($im,$x1+5,$y1+5,$x2+5,$y2+5,$gray_dark);
	 }
      }
         
      ob_start();
      imagepng($im);
      $imagevariable = ob_get_contents();
      ob_clean();

         // write to file
      $filename = $this->path."tri_". rand(0,1000).".png";
      $fp = fopen($filename, "w");
      fwrite($fp, $imagevariable);
      if(!$fp)
      {
         $this->errwrite();   
      }
      fclose($fp);
   }
   
   function import()
   {
      if (!$handle = fopen($this->path."tri.csv", "w"))
      {
         $this->erropen();  
      }
      rewind($handle);	
      $c=0;
      foreach ($this->triangle as $key => $arr)
      {
         foreach ($arr as $ikey => $iarr)
         {
            if ( !fwrite ( $handle, $iarr[0].",".$iarr[1]."\n" ) )
            {
               $this->errwrite();  
            }
         }
      }
      fclose($handle);   
   }
   
   function export($path)
   {
      if (!$handle = fopen($this->path."points.csv", "w"))
      {
         $this->erropen();  
      }
      rewind($handle);	
      $c=0;
      foreach ($this->points as $key => $arr)
      {
         if ( !fwrite ($handle, $arr[0].",".$arr[1]."\n" ) )
         {
            $this->errwrite(); 
         }
      }
      fclose($handle);   
   }
}

class DelaunayTriangulation
{
   var $stageWidth = 400;
   var $stageHeight = 400;
   var $triangle = array();
   var $points = array();
   var $indices = array();
   
   function GetCircumCenter($Ax, $Ay, $Bx, $By, $Cx, $Cy)
   {  
      //$Ax = 5;
      //$Ay = 7;
      //$Bx = 6;
      //$By = 6;
      //$Cx = 2;
      //$Cy = -2;
      
      //$Ax = 5;
      //$Ay = 1;
      //$Bx = -2;
      //$By = 0;
      //$Cx = 4;
      //$Cy = 8;

      $MidSideAx = (($Bx + $Ax)/2.0);
      $MidSideAy = (($By + $Ay)/2.0);
      
      $MidSideBx = (($Bx + $Cx)/2.0);
      $MidSideBy = (($By + $Cy)/2.0);
     
      $MidSideCx = (($Cx + $Ax)/2.0);
      $MidSideCy = (($Cy + $Ay)/2.0);
      
      //Inverted Slopes of two Perpendicular lines of the Triangle y = mx + c
      $SlopeAB = (-(($Bx - $Ax)/($By - $Ay)));
      $SlopeBC = (-(($Cx - $Bx)/($Cy - $By)));
      $SlopeCA = (-(($Cx - $Ax)/($Cy - $Ay)));
      
      //Cab
      $Cab = -1 * ($SlopeAB * $MidSideAx - $MidSideAy);
      
      //Cba
      $Cbc = -1 * ($SlopeBC * $MidSideBx - $MidSideBy);
      
      //Cac
      $Cac = -1 * ($SlopeCA * $MidSideCx - $MidSideCy);
      
      //intersection    
      $CircumCenterX = ($Cab - $Cbc) / ($SlopeBC - $SlopeAB);
      $CircumCenterY = $SlopeCA * $CircumCenterX + $Cac;
      
      return array(round($CircumCenterX), round($CircumCenterY));
   }

   function dotproduct($x1,$y1,$x2,$y2,$px,$py)
   {
      $dx1 = $x2 - $x1;
      $dy1 = $y2 - $y1;
      $dx2 = $px - $x1;
      $dy2 = $py - $y1;
      $o = ($dx1*$dy2)-($dy1*$dx2);

      //if ($o > 0.0) return(0);
      //if ($o < 0.0) return(1);
      //return(-1);
      return $o;
   }
   
   //LEFT_SIDE = true, RIGHT_SIDE = false, 2 = COLINEAR
   function side($x1,$y1,$x2,$y2,$px,$py)
   {
      $dx1 = $x2 - $x1;
      $dy1 = $y2 - $y1;
      $dx2 = $px - $x1;
      $dy2 = $py - $y1;
      $o = ($dx1*$dy2)-($dy1*$dx2);
      if ($o > 0.0) return(0);
      if ($o < 0.0) return(1);
      return(-1);
   }

   function CircumCircle($x1,$y1,$x2,$y2,$x3,$y3)
   {
      //list($x1,$y1)=array(1,3);
      //list($x2,$y2)=array(6,5);
      //list($x3,$y3)=array(4,7);
      
      $absy1y2 = abs($y1-$y2);
      $absy2y3 = abs($y2-$y3);

      if ($absy1y2 < EPSILON)
      {
         $m2 = - ($x3-$x2) / ($y3-$y2);
         $mx2 = ($x2 + $x3) / 2.0;
         $my2 = ($y2 + $y3) / 2.0;
         $xc = ($x2 + $x1) / 2.0;
         $yc = $m2 * ($xc - $mx2) + $my2;
      }
      else if ($absy2y3 < EPSILON)
      {
         $m1 = - ($x2-$x1) / ($y2-$y1);
         $mx1 = ($x1 + $x2) / 2.0;
         $my1 = ($y1 + $y2) / 2.0;
         $xc = ($x3 + $x2) / 2.0;
         $yc = $m1 * ($xc - $mx1) + $my1;	
      }
      else
      {
         $m1 = - ($x2-$x1) / ($y2-$y1);
         $m2 = - ($x3-$x2) / ($y3-$y2);
         $mx1 = ($x1 + $x2) / 2.0;
         $mx2 = ($x2 + $x3) / 2.0;
         $my1 = ($y1 + $y2) / 2.0;
         $my2 = ($y2 + $y3) / 2.0;
         $xc = ($m1 * $mx1 - $m2 * $mx2 + $my2 - $my1) / ($m1 - $m2);
         if ($absy1y2 > $absy2y3)
         {
            $yc = $m1 * ($xc - $mx1) + $my1;   
         } else
         {
            $yc = $m2 * ($xc - $mx2) + $my2;   
         }
      }
      
      $dx = $x2 - $xc;
      $dy = $y2 - $yc;
      $rsqr = $dx*$dx + $dy*$dy;
      $r = sqrt($rsqr);
     
      /* Check for coincident points */
      //if($absy1y2 < EPSILON && $absy2y3 < EPSILON)
      //{
      //   $colinear=false; 
      //} else
      //{
      //   $colinear=true;
      //}
      return new Circle($xc, $yc, $r, $rsqr, $colinear);
   }

   function inside(Circle $c, $x, $y)
   {
      $dx = $x - $c->x;
      $dy = $y - $c->y;
      $drsqr = $dx * $dx + $dy * $dy;
      //$inside = ($drsqr <= $c->r2) ? true : false;
      $inside = (($drsqr-$c->r2) <= EPSILON) ? true : false;
      //$inside = $inside & $c->colinear;
      //$inside = $inside & ($c->r > EPSILON) ? true : false; 
      return $inside;
   }
   
   function getEdges($n, $points)
   {
      /*
         Set up the supertriangle
         This is a triangle which encompasses all the sample points.
         The supertriangle coordinates are added to the end of the
         vertex list. The supertriangle is the first triangle in
         the triangle list.
      */
      
      $points[$n+0] = new Point(-SUPER_TRIANGLE,SUPER_TRIANGLE);
      $points[$n+1] = new Point(0,-SUPER_TRIANGLE);
      $points[$n+2] = new Point(SUPER_TRIANGLE,SUPER_TRIANGLE);
    
      // indices       
      $v = array(); 
      $v[] = new Indices($n,$n+1,$n+2);
      
      //sort buffer
      $complete = array();
      $complete[] = false;
      
      /*
         Include each point one at a time into the existing mesh
      */
      foreach ($points as $key => $arr)
      {        
         /*
            Set up the edge buffer.
            If the point (xp,yp) lies inside the circumcircle then the
            three edges of that triangle are added to the edge buffer
            and that triangle is removed.
         */
         
         $edges=array();
         foreach ($v as $vkey => $varr)
         {  
            if ($complete[$vkey]) continue;
            list($vi,$vj,$vk)=array($v[$vkey]->x,$v[$vkey]->y,$v[$vkey]->z);
            $c=$this->CircumCircle($points[$vi]->x,$points[$vi]->y,
				   $points[$vj]->x,$points[$vj]->y,
				   $points[$vk]->x,$points[$vk]->y);
	    if ($c->x + $c->r < $points[$key]->x) $complete[$vkey]=1;
            if ($c->r > EPSILON && $this->inside($c, $points[$key]->x,$points[$key]->y))
            {
	       $edges[]=new Edge($vi,$vj);
	       $edges[]=new Edge($vj,$vk);
	       $edges[]=new Edge($vk,$vi); 

               unset($v[$vkey]);
               unset($complete[$vkey]);
            }
         }
         
         /*
            Tag multiple edges
            Note: if all triangles are specified anticlockwise then all
            interior edges are opposite pointing in direction.
         */
         $edges=array_values($edges);
         foreach ($edges as $ekey => $earr)
         {   
            foreach ($edges as $ikey => $iarr)
            {
               if ($ekey != $ikey)
               {
                  if (($earr->x == $iarr->y) && ($earr->y == $iarr->x))
                  {
                     unset($edges[$ekey]);
                     unset($edges[$ikey]);
                     
                  } else if (($earr->x == $iarr->x) && ($earr->y == $iarr->y))
                  {
                     unset($edges[$ekey]);
                     unset($edges[$ikey]);
                 
		  }
               }
            }
         }
         
         /*
            Form new triangles for the current point
            Skipping over any tagged edges.
            All edges are arranged in clockwise order.
         */
         $complete=array_values($complete);
         $v=array_values($v);
         $ntri=count($v);
         $edges=array_values($edges);
         foreach ($edges as $ekey => $earr)
         {
	    if ($edges[$ekey]->x != $key && $edges[$ekey]->y != $key)
	    {
	       $v[] = new Indices($edges[$ekey]->x,$edges[$ekey]->y,$key);
	    }
            $complete[$ntri++]=0;
         }
      }
    
      /*
         Remove triangles with supertriangle vertices
         These are triangles which have a vertex number greater than nv
      */
      foreach ($v as $key => $arr)
      {  
         if ($v[$key]->x >= $n || $v[$key]->y >= $n || $v[$key]->z >= $n)
         {
            unset($v[$key]);        
         }
      }
      $v=array_values($v);   
      
      foreach ($v as $key => $arr)
      {
         $this->indices[]=$arr;
         $this->triangle[]=new Triangle($points[$arr->x]->x,$points[$arr->x]->y,
				        $points[$arr->y]->x,$points[$arr->y]->y,
				        $points[$arr->y]->x,$points[$arr->y]->y,
				        $points[$arr->z]->x,$points[$arr->z]->y,
				        $points[$arr->z]->x,$points[$arr->z]->y,
				        $points[$arr->x]->x,$points[$arr->x]->y                                 
                                 );   
      }
      return $v;
   }
 
   function main($points=0,$stageWidth=400,$stageHeight=400)
   {
      $this->stageWidth = $stageWidth;
      $this->stageHeight = $stageHeight;
      $this->triangle= array();
      $this->points = array();
      $this->indices = array();
      
      if ($points==0)
      {         
         for ($i=0; $i<15; $i++) 
         {
            list($x,$y)=array((float)rand(1,$this->stageWidth),(float)rand(1,$this->stageHeight));
            $this->points[]=new Point($x,$y);
         }
      } else
      {
	 for ($i=0,$end=count($points);$i<$end;$i+=2)
	 {
	    $this->points[]=new Point($points[$i],$points[$i+1]);
	 }
      }

      $maxx=$maxy=0;
      foreach ($this->points as $key => $arr)
      {
	 if ($maxx<$arr->x) $maxx=$arr->y;
	 if ($maxy<$arr->y) $maxy=$arr->y;
      }
      
      $hilbert = new hilbert();     
      $powx=$hilbert->power($maxx,2);     
      $powy=$hilbert->power($maxy,2);
      $order= ($powx<$powy) ? $powy : $powx;
 
      foreach($this->points as $key => $arr) {
	 $sort[$key] = $hilbert->point2hilbert($arr->x, $arr->y, $order);
      }
      array_multisort($sort, SORT_ASC, SORT_NUMERIC, $this->points);
      
      $result=$this->getEdges(count($this->points), $this->points);
      return $result;    
   }
}
 
?>