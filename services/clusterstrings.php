<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');
require_once (dirname(__FILE__) . '/cluster.php');

function display_form()
{
	echo 
'<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" /><style type="text/css" title="text/css">
	body {
		font-family: sans-serif;
		margin:20px;
		}
</style>
<title>Cluster strings</title>
</head>
<body>
<a href="index.html">Services</a>
<h1>Cluster strings</h1>
<p>This is a simple tool to group strings into sets of similar clusters</p>
<p>Paste strings, one per line</p>
<form method="post" action="clusterstrings.php">
	<textarea id="text" name="text" rows="30" cols="60"></textarea><br />
	<select name="format">
		<option value="html">HTML</option>
		<option value="json">JSON</option>
	</select><br />
	<input type="submit" value="Go"></input>
</form>


<div id="disqus_thread"></div>
<script type="text/javascript">
    /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
    var disqus_shortname = \'phyloinformatics\'; // required: replace example with your forum shortname

    /* * * DON\'T EDIT BELOW THIS LINE * * */
    (function() {
        var dsq = document.createElement(\'script\'); dsq.type = \'text/javascript\'; dsq.async = true;
        dsq.src = \'http://\' + disqus_shortname + \'.disqus.com/embed.js\';
        (document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(dsq);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>



</body>
</html>';


}


function find_clusters($text, $format = 'json')
{
	$obj = new stdclass;
	$obj->text = $text;
	
	$strings = explode("\n", trim($text));
	
	$obj->result = cluster($strings);
		
	switch ($format)
	{
		case 'json':
			echo json_format(json_encode($obj));			
			break;
			
		case 'html':
		default:
	echo 
'<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<style type="text/css" title="text/css">
	body {
		font-family: sans-serif;
		margin:20px;
		}
</style>
<a href="clusterstrings.php">Home</a>
<title>Cluster strings</title>
</head>
<body>
<h1>Cluster strings results</h1>';

echo '<pre>';
echo $obj->result->graph;
echo '</pre>';

//echo str_replace("\n", '', $obj->result->graph);

$s = urlencode(str_replace("\n", '', $obj->result->graph));

echo '<img src="http://chart.googleapis.com/chart?cht=gv&chl=' . $s . '" />';

echo '<pre>';
print_r($obj->result->clusters);
echo '</pre>';

echo '</body>
</html>';
			break;
	}

}


function main()
{
	$text = '';
	$format = '';
	if (isset($_POST['text']) && ($_POST['text'] != ''))
	{
		$text = $_POST['text'];
		$format = $_POST['format'];

		
		find_clusters($text, $format);
	}
	else
	{
		display_form();
	}
}
	


main();

?>	