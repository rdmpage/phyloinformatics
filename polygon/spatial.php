<?php

define('EPSILON', 0.000001);

global $svg;

// Lots of code from http://martin-thoma.com/how-to-check-if-two-line-segments-intersect/

//--------------------------------------------------------------------------------------------------
class Point
{
	var $x;
	var $y;
	
	//----------------------------------------------------------------------------------------------
	function __construct($x=0, $y=0)
	{
		$this->x = $x;
		$this->y = $y;
	}
	
	//----------------------------------------------------------------------------------------------
	function toSvg()
	{
		$svg = '<circle cx="' . $this->x . '" cy="' . $this->y . '" r="5" style="fill:white;stroke:black"/>';
		return $svg;
	}
	
}


//--------------------------------------------------------------------------------------------------
function crossProduct($a, $b)
{
	return $a->x * $b->y - $b->x * $a->y;
}

//--------------------------------------------------------------------------------------------------
class Line
{
	var $x0;
	var $y0;
	var $x1;
	var $y1;
	
	//----------------------------------------------------------------------------------------------
	function __construct($x0=0, $y0=0, $x1=0, $y1=0)
	{
		$this->x0 = $x0;
		$this->y0 = $y0;
		$this->x1 = $x1;
		$this->y1 = $y1;
	}

	//----------------------------------------------------------------------------------------------
	function fromPoints($pt1, $pt2)
	{
		$this->x0 = $pt1->x;
		$this->y0 = $pt1->y;
		$this->x1 = $pt2->x;
		$this->y1 = $pt2->y;
	}

	//----------------------------------------------------------------------------------------------
	function toSvg()
	{
		$svg = '<circle cx="' . $this->x0 . '" cy="' . $this->y0 . '" r="5" />';
		$svg .= '<line x1="' . $this->x0 . '" y1="' . $this->y0 . '" x2="' . $this->x1 . '" y2="' . $this->y1 . '" stroke="black" />';
		$svg .= '<circle cx="' . $this->x1 . '" cy="' . $this->y1 . '" r="5" />';
		return $svg;
	}
		
	//----------------------------------------------------------------------------------------------
	function isPointOnLine ($pt)
	{
		$a = new Point($this->x1 - $this->x0, $this->y1 - $this->y0);
		$b = new Point($pt->x - $this->x0, $pt->y - $this->y0);
		$r = crossProduct($a, $b);
		return abs($r) < EPSILON;
	}	

	//----------------------------------------------------------------------------------------------
	function isPointRightOfLine ($pt)
	{
		$a = new Point($this->x1 - $this->x0, $this->y1 - $this->y0);
		$b = new Point($pt->x - $this->x0, $pt->y - $this->y0);
		$r = crossProduct($a, $b);
		return $r < 0;
	}	

	//----------------------------------------------------------------------------------------------
	function lineSegmentTouchesOrCrossesLine ($otherLine)
	{
		$pt_b_first = new Point($otherLine->x0, $otherLine->y0);
		$pt_b_second = new Point($otherLine->x1, $otherLine->y1);
		
		return $this->isPointOnLine($pt_b_first)
			|| ($this->isPointRightOfLine($pt_b_first) xor $this->isPointRightOfLine($pt_b_second));
	}	

}

//--------------------------------------------------------------------------------------------------
class Rectangle
{
	var $x;
	var $y;
	var $w;
	var $h;
	var $id;
	
	//----------------------------------------------------------------------------------------------
	function __construct($x=0, $y=0, $w=0, $h=0, $id=0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->w = $w;
		$this->h = $h;
		$this->id = $id;
	}
	
	//----------------------------------------------------------------------------------------------
	function getCentre()
	{
		$centre = new Point($this->x + $this->w/2.0, $this->y + $this->h/2.0);
		return $centre;
	}
	
	//----------------------------------------------------------------------------------------------
	function lineBbox($line)
	{
		$this->x = min($line->x0, $line->x1);
		$this->y = min($line->y0, $line->y1);
		$this->w = abs($line->x1 - $line->x0);
		$this->h = abs($line->y1 - $line->y0);
	}
	
	//----------------------------------------------------------------------------------------------
	function intersectsLine($line)
	{
		global $svg;
		
		$intersects = false;
		
		$rect2 = new Rectangle();
		$rect2->lineBbox($line);

		if ($this->intersectsRect($rect2))
		{
			if (!$intersects)
			{
				// top
				$side = new Line($this->x, $this->y, $this->x + $this->w, $this->y);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);
				//if ($intersects) { echo "top\n"; $svg .= $side->toSvg(); }
			}
			if (!$intersects)
			{
				// right
				$side = new Line($this->x + $this->w, $this->y, $this->x + $this->w, $this->y + $this->h);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);		
				//if ($intersects) { echo "right\n";  $svg .= $side->toSvg(); }
			}
			if (!$intersects)
			{
				// bottom
				$side = new Line($this->x, $this->y + $this->h, $this->x + $this->w, $this->y + $this->h);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);	
				//if ($intersects) { echo "bottom\n";  $svg .= $side->toSvg(); }
			}
			if (!$intersects)
			{
				// left
				$side = new Line($this->x, $this->y, $this->x, $this->y + $this->h);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);	
				//if ($intersects) { echo "left\n";  $svg .= $side->toSvg(); }
	
			}
		}			
		return $intersects;
	}
	
	
	//----------------------------------------------------------------------------------------------
	function intersectsRect($otherRect)
	{
		$intersects = 
			($this->x <= ($otherRect->x + $otherRect->w))
			&& (($this->x + $this->w) >= $otherRect->x)
			&& ($this->y <= ($otherRect->y + $otherRect->h))
			&& (($this->y + $this->h) >= $otherRect->y);
			
		return $intersects;
	}

	//----------------------------------------------------------------------------------------------
	function toSvg()
	{
		$svg = '<rect x="' . $this->x . '" y="' . $this->y . '" width="' . $this->w . '" height="' . $this->h . '"
  style="fill:blue;fill-opacity:0.1;" />';
		return $svg;
	}
}

//--------------------------------------------------------------------------------------------------
function doLinesIntersect($line1, $line2)
{
	$rect1 = new Rectangle();
	$rect1->lineBbox($line1);
	
	$rect2 = new Rectangle();
	$rect2->lineBbox($line2);

	return $rect1->intersectsRect($rect2)
		&& 
		$line1->lineSegmentTouchesOrCrossesLine($line2)
		&& $line2->lineSegmentTouchesOrCrossesLine($line1);
}

?>