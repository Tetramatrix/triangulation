<?php

/* * *************************************************************
 * Copyright notice
 *
 * (c) 2013 Chi Hoang (info@chihoang.de)
 *  All rights reserved
 *
 * **************************************************************/

define("EPSILON",0.000001);
define("SUPER_TRIANGLE",(float)1000000000);

  // circum circle
class Circle
{
   var $x, $y, $r, $r2, $colinear;
   function Circle($x, $y, $r, $r2, $colinear)
   {
      $this->x = $x;
      $this->y = $y;
      $this->r = $r;
      $this->r2 = $r2;
      $this->colinear=$colinear;
   }
}

class visualize
{
   var $path;
   var $pObj;
   
   function visualize($path,$pObj)
   {
      $this->path=$path;
      $this->pObj=$pObj;
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
   
   function genimage()
   {
         // Generate the image variables
      $im = imagecreate($this->pObj->stageWidth,$this->pObj->stageHeight);
      $white = imagecolorallocate ($im,0xff,0xff,0xff);
      $black = imagecolorallocate($im,0x00,0x00,0x00);
      $gray_lite = imagecolorallocate ($im,0xee,0xee,0xee);
      $gray_dark = imagecolorallocate ($im,0x7f,0x7f,0x7f);
     
      // Fill in the background of the image
      imagefilledrectangle($im, 0, 0, $this->pObj->stageWidth+100, $this->pObj->stageHeight+100, $white);
            
      foreach ($this->pObj->delaunay as $key => $arr)
      {
         foreach ($arr as $ikey => $iarr)
         {
            list($x1,$y1,$x2,$y2) = $iarr;
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
   
   function tri()
   {
      if (!$handle = fopen($this->path."tri.csv", "w"))
      {
         $this->erropen();  
      }
      rewind($handle);	
      $c=0;
      foreach ($this->pObj->delaunay as $key => $arr)
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
   
   function pset($path)
   {
      if (!$handle = fopen($this->path."pset.csv", "w"))
      {
         $this->erropen();  
      }
      rewind($handle);	
      $c=0;
      foreach ($this->pObj->pointset as $key => $arr)
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
   var $delaunay = array();
   var $pointset = array();
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
      if($absy1y2 < EPSILON && $absy2y3 < EPSILON)
      {
         $colinear=false; 
      } else
      {
         $colinear=true;
      }
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
   
   function getEdges($n, $x, $y, $z)
   {
      /*
         Set up the supertriangle
         This is a triangle which encompasses all the sample points.
         The supertriangle coordinates are added to the end of the
         vertex list. The supertriangle is the first triangle in
         the triangle list.
      */
      
      $x[$n+0] = -SUPER_TRIANGLE;
      $y[$n+0] = SUPER_TRIANGLE;
      $x[$n+1] = 0;
      $y[$n+1] = -SUPER_TRIANGLE;
      $x[$n+2] = SUPER_TRIANGLE;
      $y[$n+2] = SUPER_TRIANGLE;
    
      // indices       
      $v = array(); 
      $v[] = array($n,$n+1,$n+2);
      
      //sort buffer
      $complete = array();
      $complete[] = false;
      
      /*
         Include each point one at a time into the existing mesh
      */
      foreach ($x as $key => $arr)
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
            list($vi,$vj,$vk)=array($v[$vkey][0],$v[$vkey][1],$v[$vkey][2]);
            $c=$this->CircumCircle($x[$vi],$y[$vi],$x[$vj],$y[$vj],$x[$vk],$y[$vk]);
	    if ($c->x + $c->r < $x[$key]) $complete[$vkey]=1;
            if ($c->r > EPSILON && $this->inside($c, $x[$key],$y[$key]))
            {
               if ($this->side($x[$vi],$y[$vi],$x[$vj],$y[$vj],$x[$vk],$y[$vk])==0)
               {
                  $edges[]=array($vi,$vj);
                  $edges[]=array($vj,$vk);
                  $edges[]=array($vk,$vi);
                  
               } elseif($this->side($x[$vk],$y[$vj],$x[$vj],$y[$vi],$x[$vi],$y[$vk])==0)
               {
                  $edges[]=array($vk,$vj);
                  $edges[]=array($vj,$vi);
                  $edges[]=array($vi,$vk);
                  
               } elseif($this->side($x[$vk],$y[$vi],$x[$vi],$y[$vj],$x[$vj],$y[$vk])==0)
               {
                  $edges[]=array($vk,$vi);
                  $edges[]=array($vi,$vj);
                  $edges[]=array($vj,$vk);
                  
               } elseif($this->side($x[$vj],$y[$vi],$x[$vi],$y[$vk],$x[$vk],$y[$vj])==0)
               {  
                  $edges[]=array($vj,$vi);
                  $edges[]=array($vi,$vk);
                  $edges[]=array($vk,$vj);
               
               } elseif($this->side($x[$vj],$y[$vk],$x[$vk],$y[$vi],$x[$vi],$y[$vj])==0)
               {
                  $edges[]=array($vj,$vk);
                  $edges[]=array($vk,$vi);
                  $edges[]=array($vi,$vj);
               
               } elseif($this->side($x[$vi],$y[$vk],$x[$vk],$y[$vj],$x[$vj],$y[$vi])==0)
               {
                  $edges[]=array($vi,$vk);
                  $edges[]=array($vk,$vj);
                  $edges[]=array($vj,$vi);
               
               } elseif($this->side($x[$vk],$y[$vk],$x[$vi],$y[$vi],$x[$vj],$y[$vj])==0)
               {
                  $edges[]=array($vk,$vi);
                  $edges[]=array($vi,$vj);
                  $edges[]=array($vj,$vk);
               
               } elseif($this->side($x[$vj],$y[$vj],$x[$vk],$y[$vk],$x[$vi],$y[$vi])==0)
               {
                  $edges[]=array($vj,$vk);
                  $edges[]=array($vk,$vi);
                  $edges[]=array($vi,$vj);
               } 
               else
               {
                  $edges[]=array($vi,$vj);
                  $edges[]=array($vj,$vk);
                  $edges[]=array($vk,$vi); 
               }
               
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
                  if (($earr[0] == $iarr[1]) && ($earr[1] == $iarr[0]))
                  {
                     unset($edges[$ekey]);
                     unset($edges[$ikey]);
                     
                  } elseif (($earr[0] == $iarr[0]) && ($earr[1] == $iarr[1]))
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
            list($vi,$vj,$vk)=array($edges[$ekey][0],$edges[$ekey][1],$key);
            
            if ($this->side($x[$vi],$y[$vi],$x[$vj],$y[$vj],$x[$vk],$y[$vk])==0)
            {
               $v[] = array($vi,$vj,$vk);
               
            } elseif($this->side($x[$vk],$y[$vj],$x[$vj],$y[$vi],$x[$vi],$y[$vk])==0)
            {
               $v[] = array($vk,$vj,$vi);
               
            } elseif($this->side($x[$vk],$y[$vi],$x[$vi],$y[$vj],$x[$vj],$y[$vk])==0)
            {
               $v[] = array($vk,$vi,$vj);
               
            } elseif($this->side($x[$vj],$y[$vi],$x[$vi],$y[$vk],$x[$vk],$y[$vj])==0)
            {  
               $v[] = array($vj,$vi,$vk);
            
            } elseif($this->side($x[$vj],$y[$vk],$x[$vk],$y[$vi],$x[$vi],$y[$vj])==0)
            {
               $v[] = array($vj,$vk,$vi);
            
            } elseif($this->side($x[$vi],$y[$vk],$x[$vk],$y[$vj],$x[$vj],$y[$vi])==0)
            {
               $v[] = array($vi,$vk,$vj);
               
            }  elseif($this->side($x[$vk],$y[$vk],$x[$vj],$y[$vj],$x[$vj],$y[$vi])==0)
            {
               $v[] = array($vk,$vj,$vi);
               
            }  elseif($this->side($x[$vj],$y[$vj],$x[$vk],$y[$vk],$x[$vi],$y[$vi])==0)
            {
               $v[] = array($vj,$vk,$vi);
            }  
            else
            {
               $v[] = array($vi,$vj,$vk);
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
         if ($v[$key][0] >= $n || $v[$key][1] >= $n || $v[$key][2] >= $n)
         {
            unset($v[$key]);        
         }
      }
      $v=array_values($v);   
      
      
      foreach ($v as $key => $arr)
      {
         $this->indices[]=$arr;
         $this->delaunay[]=array(array($x[$arr[0]],$y[$arr[0]],$x[$arr[1]],$y[$arr[1]]),
                                 array($x[$arr[1]],$y[$arr[1]],$x[$arr[2]],$y[$arr[2]]),
                                 array($x[$arr[2]],$y[$arr[2]],$x[$arr[0]],$y[$arr[0]])                                 
                                 );   
      }
      return $v;
   }
 
   function main($pointset=0,$stageWidth=400,$stageHeight=400)
   {
      $this->stageWidth = $stageWidth;
      $this->stageHeight = $stageHeight;
      $this->delaunay = array();
      $this->pointset = array();
      $this->indices = array();
      
      if ($pointset==0)
      {         
         for ($i=0; $i<15; $i++) 
         {
            list($x,$y)=array((float)rand(1,$this->stageWidth),(float)rand(1,$this->stageHeight));
            $this->pointset[]=array($x,$y);
         }
      } else
      { 
         $this->pointset=$pointset;   
      }

      $x = $y = $sortX = array(); 
      foreach($this->pointset as $key => $arr)
      {
         $sortX[$key] = $arr[0];
      } 
      array_multisort($sortX, SORT_ASC, SORT_NUMERIC, $this->pointset);
         
      foreach ($this->pointset as $key => $arr)
      {
        list($x[],$y[]) = $arr;
      }
      $result=$this->getEdges(count($this->pointset), $x, $y, $z);
      return $result;    
   }
}
 
?>