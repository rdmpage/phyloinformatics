<?php

//--------------------------------------------------------------------------------------------------
// MySQL
require_once(dirname(dirname(__FILE__)).'/adodb5/adodb.inc.php');

$db = NewADOConnection('mysql');
$db->Connect(
	"localhost", 	# machine hosting the database, e.g. localhost
	'root', 		# MySQL user name
	'', 			# password
	'col_3JAN2011'	# database
	);
	
// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//--------------------------------------------------------------------------------------------------

// Helper functions

//--------------------------------------------------------------------------------------------------
// Word wrapping
// http://www.xtremevbtalk.com/showthread.php?t=289709
function findStrWidth($str, $width, $low, $hi)
{
	$txtWidth = strlen($str);
	
	if (($txtWidth < $width) || ($hi == 1))
	{
		// string fits, or is one character long
		return $hi;
	}
	else
	{
		if ($hi - $low <= 1)
		{
			// we have at last character
			$txtWidth = $low;
			return $low;
		}
		else
		{
			$mid = $low + floor(($hi - $low)/2.0);
			
			$txtWidth = strlen(substr($str, 0, $mid));
			if ($txtWidth < $width)
			{
				// too short
				$low = $mid;
				return findStrWidth($str, $width, $low, $hi);
			}
			else
			{
				// too long
				$hi = $mid;
				return findStrWidth($str, $width, $low, $hi);
			}
		}
	}
}

//--------------------------------------------------------------------------------------------------
// http://www.herethere.net/~samson/php/color_gradient/
// Return the interpolated value between pBegin and pEnd
function interpolate($pBegin, $pEnd, $pStep, $pMax) 
{
	if ($pBegin < $pEnd) 
	{
  		return (($pEnd - $pBegin) * ($pStep / $pMax)) + $pBegin;
	} 
	else 
	{
  		return (($pBegin - $pEnd) * (1 - ($pStep / $pMax))) + $pEnd;
	}
}


//--------------------------------------------------------------------------------------------------
/**
 * @brief Encapsulate a rectangle
 *
 */class Rectangle
{
	var $x;
	var $y;
	var $w;
	var $h;
	

	function Rectangle($x=0, $y=0, $w=0, $h=0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->w = $w;
		$this->h = $h;
	}
	
	function Dump()
	{
		echo $this->x . ' ' . $this->y . ' ' . $this->w . ' ' . $this->h . "\n";
	}
	
	
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Encapsulate a cell in the Treemap
 *
 */
class Item
{
	var $bounds;			 // rectangle cell occupies (computed by treemap layout)
	var $size;				 // quantity cell corresponds to
	var $id;				 // id, typically an external id so we can make a link
	var $children = array(); // children of this node, if we are doing > 1 level
	var $label;				 // label for cell
	var $isLeaf;			 // flag for whether cell is a leaf

	/**
	* @brief Constructor
	*
	* @param n Number of items in this cell
	* @param label Label for this cell
	* @param ext External identifier for this cell (used to make a link)
	* @param leaf True if this cell has no children
	*
	*/
	function Item($n = 0, $label = '', $ext = 0, $leaf = false)
	{		
		$this->bounds 	= new Rectangle();
		$this->size 	= $n;
		$this->label 	= $label;
		$this->isLeaf 	= $leaf;
		$this->id		= $ext;
	}
	
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Compute weight of list of items to be placed
 *
 * This is the sum of the quantity represented by each item in the list.
 * @param l Array of items being placed
 *
 * @return Weight of items
 */
function w($l)
{
	$sum = 0.0;
	foreach ($l as $item)
	{
		$sum += $item->size;
	}
	return $sum;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Split layout
 *
 * Implements Björn Engdahl's Split Layout algorithm for treemaps,
 * see http://www.nada.kth.se/utbildning/grukth/exjobb/rapportlistor/2005/rapporter05/engdahl_bjorn_05033.pdf
 *
 * This is a recursive function that lays out the treemap. It tries to satisfy the twin goals of 
 * a good aspect ratio for the rectangles, and minimal changes to the order of the items in the treemap.
 *
 * @param items Array of items to place
 * @param r Current rectangle
 *
 */
function splitLayout($items, &$r)
{
	global $cr;
	
	if (count($items) == 0)
	{
		return;
	}
	
	if (count($items) == 1)
	{
		// Store rectangle dimensions
		$cr[$items[0]->id] = $r;
		
		$items[0]->bounds = $r;
		
		
		// Handle children (if any)		
		if (isset($items[0]->children))
		{
			splitLayout($items[0]->children, $r);
		}
		else
		{
			return;
		}
		
		return;
		
	}
	
	// Split list of items into two roughly equal lists
	$l1 = array();
	$l2 = array();
	
	$halfSize = w($items) / 2.0;
	$w1 		= 0.0;
	$tmp 		= 0.0;
	
	while (count($items) > 0)
	{
		$item = $items[0];
		
		$tmp = $w1 + $item->size;
		
		// Has it gotten worse by picking another item?
		if (abs($halfSize - $tmp) > abs($halfSize - $w1))
		{
			break;
		}
		
		// It was good to pick another
		array_push($l1, array_shift($items));
		$w1 = $tmp;
	}
	
	// The rest of the items go into l2
	foreach ($items as $item)
	{
		array_push($l2, $item);
	}
	
	$wl1 = w($l1);
	$wl2 = w($l2);
	
	
	// Which way do we split current rectangle it?	
	if ($r->w > $r->h)
	{
		// vertically
		$r1 = new Rectangle(
			$r->x, 
			$r->y,
			$r->w * $wl1/($wl1 + $wl2),
			$r->h);
	
		$r2 = new Rectangle(
			$r->x + $r1->w, 
			$r->y,
			$r->w - $r1->w,
			$r->h);
	}
	else
	{
		// horizontally
		$r1 = new Rectangle(
			$r->x, 
			$r->y,
			$r->w,
			$r->h * $wl1/($wl1 + $wl2));
	
		$r2 = new Rectangle(
			$r->x, 
			$r->y + $r1->h,
			$r->w,
			$r->h - $r1->h);	
	}
		
	// Continue recursively
	splitLayout($l1, $r1);
	splitLayout($l2, $r2);
}

// Create some data for us to plot

$items = array();

// 0 = life
$subtree_root = 0;

if (isset($_GET['node']))
{
	$subtree_root = $_GET['node'];
}
$show_text = true;
if (isset($_GET['showtext']))
{
	$show_text = false;
}

// Get children of this node
$sql = 'SELECT * FROM tree 
	INNER JOIN taxa USING(record_id)
	WHERE tree.parent_id = ' . $subtree_root .
	' AND (tree.record_id <> 0)
	ORDER BY taxa.name';
	
$result = $db->Execute($sql);
if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
while (!$result->EOF) 
{
	$i = new Item(
		log10($result->fields['weight'] + 1), 
		$result->fields['name'], 
		$result->fields['record_id'],
		($result->fields['right_id'] - $result->fields['left_id'] == 1)
		);
	array_push($items, $i);
	$result->MoveNext();	
}


// Treemap bounds
$tm_width = 600;
$tm_height = 500;
$r = new Rectangle(0,0,$tm_width,$tm_height);

// Compute the layout
splitLayout($items, $r);

// Use a colour gradient to colour cells
$theColorBegin = 0x006600;
$theColorEnd = 0x000066;


$theR0 = ($theColorBegin & 0xff0000) >> 16;
$theG0 = ($theColorBegin & 0x00ff00) >> 8;
$theB0 = ($theColorBegin & 0x0000ff) >> 0;

$theR1 = ($theColorEnd & 0xff0000) >> 16;
$theG1 = ($theColorEnd & 0x00ff00) >> 8;
$theB1 = ($theColorEnd & 0x0000ff) >> 0;
  

?>
<!DOCTYPE html>
<html>
<head>

<script type="text/javascript">
<!--

    function mouse_over(id) 
    {
    	var e = document.getElementById(id);
    	e.style.opacity= "1.0";
	    e.style.filter = "alpha(opacity=100)";
    }
    
    function mouse_out(id) 
    {
    	var e = document.getElementById(id);
    	e.style.opacity=0.6;
        e.style.filter = "alpha(opacity=60)";
    }    
//-->
</script>
<style type="text/css">
	body {
		font-family: sans-serif;
		margin: 20px;
	}
</style>
</head>
<body>
<?php

if ($show_text)
{
?>
<a href="../">Home</a>
<h1>Simple HTML-based treemap</h1>
<p>Displaying Catalogue of Life 2011 classification. Each taxon is drawn proportional to log<sub>10</sub>(<i>n</i> + 1),
where <i>n</i> is the number of terminal taxa (i.e., species or below) in that taxon (the number of terminals is shown in each cell).
</p>
<?php
}

// Tell user where we are in the classification

$path = '';
$curnode = $subtree_root;
while ($curnode != 0)
{
	$sql = 'SELECT * FROM taxa 
		WHERE record_id = ' . $curnode .
		' LIMIT 1';

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);

	$p = ' &raquo; ';
	
	if ($curnode != $subtree_root)
	{
		$p .= '<a href="?node=' . $result->fields['record_id'];
		if (!$show_text)
		{
			$p .= '&showtext=false';
		}

		$p .= '">';
	}
	$p .= $result->fields['name_with_italics'];
	if ($curnode != $subtree_root)
	{
		$p .= '</a>';
	}
	$path = $p . $path;
	$curnode = $result->fields['parent_id'];
}
$p = '<p><a href="?node=0';
if (!$show_text)
{
	$p .= '&showtext=false';
}
$p .= '">Life</a>';
$path = $p . $path . '</p>';
echo $path;

// Enclose treemap in a DIV that has position:relative. The cells themselves have position:absolute.
// Note also that the enclosing DIV has the same height as the treemap, so that elements that follow
// the treemap appear below the treemap (rather than being obscured).
echo '<div style="color:white;font-family:Arial;position:relative;font-size:10px;height:' . $tm_height . 'px;margin-left:20px">';
$theNumSteps = count($items);
$count = 0;
foreach ($items as $i)
{
	// Note that each treemap cell has position:absolute
	echo '<div id="div' . $i->id . '" class="cell" style="opacity:0.6;filter:alpha(opacity=60);position: absolute; overflow:hidden;text-align:center;';
	echo ' left:' . $i->bounds->x . 'px;';
	echo ' top:' . $i->bounds->y . 'px;';
	echo ' width:' . $i->bounds->w. 'px;';
	echo ' height:' . $i->bounds->h . 'px;';
	echo ' border:1px solid white;';
	
	// Background colour
    $theR = interpolate($theR0, $theR1, $count, $theNumSteps);
    $theG = interpolate($theG0, $theG1, $count, $theNumSteps);
    $theB = interpolate($theB0, $theB1, $count, $theNumSteps);
    $theVal = ((($theR << 8) | $theG) << 8) | $theB;

    printf("background-color: #%06X; ", $theVal);
	echo '" ';
    
    // Mouse activity
    echo ' onMouseOver="mouse_over(\'div' . $i->id . '\');" ';
    echo ' onMouseOut="mouse_out(\'div' . $i->id . '\');" ';

	// Link to drill down
	if (!$i->isLeaf)
	{
	    echo ' onClick="document.location=\'?node=' . $i->id;

		if (!$show_text)
		{
			echo '&showtext=false';
		}

		echo '\';" ';
	}
	echo ' >';
	
				
	// Text is taxon name, plus number of leaf descendants
	// Note that $number[$count] is log (n+1)
	$tag = $i->label . ' ' . number_format(pow(10, $i->size) - 1);
			
			
	// format the tag...
	// 1. Find longest word
	$words = preg_split("/[\s]+/", $tag);
	
	$max_length = 0;
	foreach($words as $word)
	{
		$max_length = max($max_length, strlen($word));
	}
	
	// Font upper bound is proportional to length of longest word
	$font_height = $i->bounds->w / $max_length;
	$font_height *= 1.2;
	if ($font_height < 10)
	{
		$font_height = 10;
	}

	// text			
	echo '<span style="font-size:' . $font_height . 'px;">' . $tag . '</span>';

	echo '</div>';
	echo "\n";

	$count++;
}
echo '</div>';

echo '</body>
</html>';

?>



	
