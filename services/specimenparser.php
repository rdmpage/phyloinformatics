<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');
require_once (dirname(__FILE__) . '/extract_specimens.php');

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
<title>Specimen parser</title>
</head>
<body>
<a href="index.html">Services</a>
<h1>Specimen parser</h1>
<p>This is a simple tool to extract museum specimen codes from text. Proof of concept, so your mileage may vary.</p>
<p>Paste in text containing specimen codes</p>
<form method="post" action="specimenparser.php">
	<textarea id="text" name="text" rows="30" cols="60"></textarea><br />
	<select name="format">
		<option value="html">HTML</option>
		<option value="json">JSON</option>
	</select><br />
	<input type="submit" value="Go"></input>
</form>

<p>For more details on this service see <a href="http://iphylo.blogspot.com/2012/01/extracting-museum-specimen-codes-from.html">Extracting museum specimen codes from text</a>.</p>

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


function find_specimens($text, $format = 'json')
{
	$obj = new stdclass;
	$obj->text = $text;
	$obj->codes = extract_specimen_codes($text);
		
	switch ($format)
	{
		case 'json':
			$obj->text = html_entity_decode($obj->text, ENT_QUOTES, 'UTF-8');
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
<a href="specimenparser.php">Home</a>
<title>Specimen parser</title>
</head>
<body>
<h1>Specimen parser results</h1>
<h2>Specimen codes</h2>
<ul>';

		foreach ($obj->codes as $code)
		{
			echo '<li>' . $code . '</li>';
		}
		echo '</ul>
<h2>Input</h2>
<p>' . htmlentities($obj->text, ENT_QUOTES, 'UTF-8') . '</p>
</body>
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

		
		find_specimens($text, $format);
	}
	else
	{
		display_form();
	}
}
	


main();

?>	