<?php

/**
 *@file lib.php
 *
 * Utility functions
 *
 */
 
require_once(dirname(__FILE__) . '/config.inc.php');

//--------------------------------------------------------------------------------------------------
// safe file name, based on http://snipplr.com/view/5256/filename-safe/
function filename_safe($filename) 
{
	$temp = $filename;
	// Lower case
	$temp = strtolower($temp);

	// Replace spaces with a '_'
	$temp = str_replace(" ", "_", $temp);

	// Loop through string
	$result = '';
	for ($i=0; $i<strlen($temp); $i++) 
	{
		if (preg_match('([0-9]|[a-z]|_)', $temp[$i])) 
		{
			$result = $result . $temp[$i];
		}    
	}
	// Return filename
	return $result;
}


//--------------------------------------------------------------------------------------------------
/**
 * @brief Format JSON nicely
 *
 * From umbrae at gmail dot com posted 10-Jan-2008 06:21 to http://uk3.php.net/json_encode
 *
 * @param json Original JSON
 *
 * @result Formatted JSON
 */
function json_format($json)
{
    $tab = "  ";
    $new_json = "";
    $indent_level = 0;
    $in_string = false;

/*    $json_obj = json_decode($json);

    if($json_obj === false)
        return false;

    $json = json_encode($json_obj); */
    $len = strlen($json);

    for($c = 0; $c < $len; $c++)
    {
        $char = $json[$c];
        switch($char)
        {
            case '{':
            case '[':
                if(!$in_string)
                {
                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
                    $indent_level++;
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case '}':
            case ']':
                if(!$in_string)
                {
                    $indent_level--;
                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case ',':
                if(!$in_string)
                {
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case ':':
                if(!$in_string)
                {
                    $new_json .= ": ";
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case '"':
                if($c > 0 && $json[$c-1] != '\\')
                {
                    $in_string = !$in_string;
                }
            default:
                $new_json .= $char;
                break;                    
        }
    }

    return $new_json;
}



//--------------------------------------------------------------------------
/**
 * @brief Test whether HTTP code is valid
 *
 * HTTP codes 200 and 302 are OK.
 *
 * For JSTOR we also accept 403
 *
 * @param HTTP code
 *
 * @result True if HTTP code is valid
 */
function HttpCodeValid($http_code)
{
	if ( ($http_code == '200') || ($http_code == '302') || ($http_code == '403'))
	{
		return true;
	}
	else{
		return false;
	}
}


//--------------------------------------------------------------------------
/**
 * @brief GET a resource
 *
 * Make the HTTP GET call to retrieve the record pointed to by the URL. 
 *
 * @param url URL of resource
 *
 * @result Contents of resource
 */
function get($url, $userAgent = '')
{
	global $config;
	
	$data = '';
	
	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	//curl_setopt ($ch, CURLOPT_HEADER,		  1);  

	curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	
	if ($userAgent != '')
	{
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	}	
	
	if ($config['proxy_name'] != '')
	{
		curl_setopt ($ch, CURLOPT_PROXY, $config['proxy_name'] . ':' . $config['proxy_port']);
	}
			
	$curl_result = curl_exec ($ch); 
	
	//echo $curl_result;
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		 //$header = substr($curl_result, 0, $info['header_size']);
		//echo $header;
		
		
		$http_code = $info['http_code'];
		
		//echo "<p><b>HTTP code=$http_code</b></p>";
		
		if (HttpCodeValid ($http_code))
		{
			$data = $curl_result;
		}
	}
	return $data;
}

//--------------------------------------------------------------------------------------------------
/**
 *
 * @brief Checking whether a HTTP source has been modified.
 *
 * We use HTTP conditional GET to check whether source has been updated, see 
 * http://fishbowl.pastiche.org/2002/10/21/http_conditional_get_for_rss_hackers .
 * ETag and Last Modified header values are stored in a MySQL database table 'feed'.
 * ETag is a double-quoted string sent by the HTTP server, e.g. "2f4511-8b92-44717fa6"
 * (note the string includes the enclosing double quotes). Last Modified is date,
 * written in the form Mon, 22 May 2006 09:08:54 GMT.
 *
 * @param url Feed URL
 *
 * @return 0 if source exists and is modified, otherwise an HTTP code or an error
 * code.
 *
 */
 function has_source_changed($url)
{
	global $config;
	global $ADODB_FETCH_MODE;

	$debug_headers = 0;
	
	$result = 0;

	// 1. Get details of ETag and LastModified from database
		
	$db = NewADOConnection('mysql');
	$db->Connect("localhost", 
		$config['db_user'] , $config['db_passwd'] , $config['db_name']);
	
	// Ensure fields are (only) indexed by column name
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$sql = 'SELECT last_modified, etag FROM feed WHERE (url = "' . $url . '")';

	$sql_result = $db->Execute($sql);
	if ($sql_result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
	$ETag = '';
	$LastModified = '';
	if ($sql_result->RecordCount() == 0)
	{
		// We don't have this source
		$sql = 'INSERT feed (url) VALUES(' . $db->qstr($url) . ')';
		$sql_result = $db->Execute($sql);
		if ($sql_result == false) die("failed [" . __LINE__ . "]: " . $sql);
	}
	else
	{
		$ETag = trim($sql_result->fields['etag']);
		$LastModified = trim($sql_result->fields['last_modified']);
	}
	
	// Construct conditional GET header
	$if_header = array();
	
	if ($LastModified != "''")
	{
		array_push ($if_header, 'If-Modified-Since: ' . $LastModified);
	}
	
	// Only add this header if server returned an ETag value, otherwise
	// Connotea doesn't play nice.
	if ($ETag != "''")
	{
		array_push ($if_header,'If-None-Match: ' . $ETag);
	}
	
	if ($debug_headers)
	{
		print_r($if_header);
	}
	 

	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_HEADER,		  1); 
//	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	
	if ($check)
	{
		curl_setopt ($ch, CURLOPT_HTTPHEADER,	  $if_header); 
	}
	
	if ($config['proxy_name'] != '')
	{
		curl_setopt ($ch, CURLOPT_PROXY, $config['proxy_name'] . ":" . $config['proxy_port']);
	}
			
	$curl_result = curl_exec ($ch); 
		
	if(curl_errno ($ch) != 0 )
	{
		// Problems with CURL
		$result = curl_errno ($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		
		$header = substr($curl_result, 0, $info['header_size']);
		
		$result = $info['http_code'];
		
		if ($debug_headers)
		{
			echo $header;
		}

		if ($result == 200)
		{
			// HTTP 200 means the feed exists and has been modified since we 
			// last visited (or this is the first time we've looked at it)
			// so we grab it, remembering to trim off the header. We store
			// details of the feed in our database.
			$result = 0;
			
			$rss = substr ($curl_result, $info['header_size']);
			
			// Retrieve ETag and LastModified
			$rows = split ("\n", $header);
			foreach ($rows as $row)
			{
				$parts = split (":", $row, 2);
				if (count($parts) == 2)
				{
					if (preg_match("/ETag/", $parts[0]))
					{
						$ETag = trim($parts[1]);
					}
					
					if (preg_match("/Last-Modified/", $parts[0]))
					{
						$LastModified = trim($parts[1]);
					}
					
				}
			}

			// Store in database conditional headers in database
			$sql = 'UPDATE feed SET last_modified=' . $db->qstr($LastModified) 
				. ', etag=' . $db->qstr($ETag) 
				. ', last_accessed = NOW()'
				. ' WHERE (url = "' . $url . '")';
			$sql_result = $db->Execute($sql);
			
			if ($sql_result == false) die("failed [" . __LINE__ . "]: " . $sql);
		}
	}
	return $result;
}


?>