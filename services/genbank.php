<?php

// Fetch sequence(s) from GenBank

require_once (dirname(dirname(__FILE__)) . '/lib.php');

//--------------------------------------------------------------------------------------------------
/**
 * @brief Format an arbitrary date as YYYY-MM-DD
 *
 * @param date A string representation of a date
 *
 * @return Date in YYYY-MM-DD format
 */
function format_date($date)
{
	$formatted_date = '';
	
	// Dates like 2006-8-7T15:47:36.000Z break PHP strtotime, so
	// replace the T with a space.
	$date = preg_replace('/-([0-9]{1,2})T([0-9]{1,2}):/', '-$1 $2:', $date);
	
	if (PHP_VERSION < 5.0)
	{
		if (-1 != strtotime($date))
		{
			$formatted_date = date("Y-m-d", strtotime($date));
		}		
	}
	else
	{
		if (false != strtotime($date))
		{
			$formatted_date = date("Y-m-d", strtotime($date));
		}
	}
	return $formatted_date;
}


//--------------------------------------------------------------------------------------------------
function process_lat_lon(&$sequence)
{
	if (!isset($sequence->source->lat_lon))
	{
		return;
	}

	$matched = false;
	
	$lat_lon = $sequence->source->lat_lon;

	if (preg_match ("/(N|S)[;|,] /", $lat_lon))
	{
		// it's a literal string description, not a pair of decimal coordinates.
		if (!$matched)
		{
			//  35deg12'07'' N; 83deg05'2'' W, e.g. DQ995039
			if (preg_match("/([0-9]{1,2})deg([0-9]{1,2})'(([0-9]{1,2})'')?\s*([S|N])[;|,]\s*([0-9]{1,3})deg([0-9]{1,2})'(([0-9]{1,2})'')?\s*([W|E])/", $lat_lon, $matches))
			{
				//print_r($matches);
			
				$degrees = $matches[1];
				$minutes = $matches[2];
				$seconds = $matches[4];
				$hemisphere = $matches[5];
				$lat = $degrees + ($minutes/60.0) + ($seconds/3600);
				if ($hemisphere == 'S') { $lat *= -1.0; };

				$sequence->source->latitude = $lat;

				$degrees = $matches[6];
				$minutes = $matches[7];
				$seconds = $matches[9];
				$hemisphere = $matches[10];
				$long = $degrees + ($minutes/60.0) + ($seconds/3600);
				if ($hemisphere == 'W') { $long *= -1.0; };
				
				$sequence->source->longitude = $long;
				
				$matched = true;
			}
		}
		if (!$matched)
		{
			
			list ($lat, $long) = explode ("; ", $lat_lon);

			list ($degrees, $rest) = explode (" ", $lat);
			list ($minutes, $rest) = explode ('.', $rest);

			list ($decimal_minutes, $hemisphere) = explode ("'", $rest);

			$lat = $degrees + ($minutes/60.0) + ($decimal_minutes/6000);
			if ($hemisphere == 'S') { $lat *= -1.0; };

			$sequence->source->latitude = $lat;

			list ($degrees, $rest) = explode (" ", $long);
			list ($minutes, $rest) = explode ('.', $rest);

			list ($decimal_minutes, $hemisphere) = explode ("'", $rest);

			$long = $degrees + ($minutes/60.0) + ($decimal_minutes/6000);
			if ($hemisphere == 'W') { $long *= -1.0; };
			
			$sequence->source->longitude = $long;
			
			$matched = true;
		}

	}
	
	if (!$matched)
	{			
		// N19.49048, W155.91167 [EF219364]
		if (preg_match ("/(?<lat_hemisphere>(N|S))(?<latitude>(\d+(\.\d+))), (?<long_hemisphere>(W|E))(?<longitude>(\d+(\.\d+)))/", $lat_lon, $matches))
		{
			$lat = $matches['latitude'];
			if ($matches['lat_hemisphere'] == 'S') { $lat *= -1.0; };
			
			$sequence->source->latitude = $lat;
			
			$long = $matches['longitude'];
			if ($matches['long_hemisphere'] == 'W') { $long *= -1.0; };
			
			$sequence->source->longitude = $long;
			
			$matched = true;

		}
	}
	
	if (!$matched)		
	{
		//13.2633 S 49.6033 E
		if (preg_match("/([0-9]+(\.[0-9]+)*) ([S|N]) ([0-9]+(\.[0-9]+)*) ([W|E])/", $lat_lon, $matches))
		{
			//print_r ($matches);
			
			$lat = $matches[1];
			if ($matches[3] == 'S') { $lat *= -1.0; };
			
			$sequence->source->latitude = $lat;

			$long = $matches[4];
			if ($matches[6] == 'W') { $long *= -1.0; };
			
			$sequence->source->longitude = $long;
			
			$matched = true;
		}
	}
	
	
	// AY249471 Palmer Archipelago 64deg51.0'S, 63deg34.0'W 
	if (!$matched)		
	{
		if (preg_match("/([0-9]{1,2})deg([0-9]{1,2}(\.\d+)?)'\s*([S|N]),\s*([0-9]{1,3})deg([0-9]{1,2}(\.\d+)?)'\s*([W|E])/", $lat_lon, $matches))
		{
			//print_r ($matches);
			
			$lat = $matches[1];
			if ($matches[3] == 'S') { $lat *= -1.0; };
			$sequence->source->latitude = $lat;

			$long = $matches[4];
			if ($matches[6] == 'W') { $long *= -1.0; };
			
			$sequence->source->longitude = $long;
			
			$matched = true;
		}
	}
	
	if (!$matched)
	{
		
		if (preg_match("/(?<latitude>\-?\d+(\.\d+)?),?\s*(?<longitude>\-?\d+(\.\d+)?)/", $lat_lon, $matches))
		{
			//print_r($matches);
			
			$sequence->source->latitude  = $matches['latitude'];
			$sequence->source->longitude = $matches['longitude'];
		
			$matched = true;
		}
	}
	
	
}

//--------------------------------------------------------------------------------------------------
function process_locality(&$sequence)
{
	$debug = false;
		
	if (isset($sequence->source->country))
	{
		$country = $sequence->source->country;

		$matches = array();	
		$parts = explode (":", $country);	
		$sequence->source->country = $parts[0];
		
		if (count($parts) > 1)
		{
			$sequence->source->locality = trim($parts[1]);
			// Clean up
			$sequence->source->locality = preg_replace('/\(?GPS/', '', $sequence->source->locality);				
		}	
		
		if ($debug)
		{
			echo "Trying line " . __LINE__ . "\n";
		}

		// Handle AMNH stuff
		if (preg_match('/(?<latitude_degrees>[0-9]+)deg(?<latitude_minutes>[0-9]{1,2})\'\s*(?<latitude_hemisphere>[N|S])/i', $country, $matches))
		{
			if ($debug) { print_r($matches); }	

			$degrees = $matches['latitude_degrees'];
			$minutes = $matches['latitude_minutes'];
			$hemisphere = $matches['latitude_hemisphere'];
			$lat = $degrees + ($minutes/60.0);
			if ($hemisphere == 'S') { $lat *= -1.0; };

			$sequence->source->latitude  = $lat;
		}

		if ($debug)
		{
			echo "Trying line " . __LINE__ . "\n";
		}
		if (preg_match('/(?<longitude_degrees>[0-9]+)deg(,\s*)?(?<longitude_minutes>[0-9]{1,2})\'\s*(?<longitude_hemisphere>[W|E])/i', $country, $matches))
		{
		
			if ($debug) { print_r($matches); }	
			
			$degrees = $matches['longitude_degrees'];
			$minutes = $matches['longitude_minutes'];
			$hemisphere = $matches['longitude_hemisphere'];
			$long = $degrees + ($minutes/60.0);
			if ($hemisphere == 'W') { $long *= -1.0; };
			
			$sequence->source->longitude  = $long;
		}
	
		if ($debug)
		{
			echo "Trying line " . __LINE__ . "\n";
		}

		if (isset($sequence->source->locality))
		{
			// AY249471 Palmer Archipelago 64deg51.0'S, 63deg34.0'W 
			if (preg_match("/(?<latitude_degrees>[0-9]{1,2})deg(?<latitude_minutes>[0-9]{1,2}(\.\d+)?)'\s*(?<latitude_hemisphere>[S|N]),\s*(?<longitude_degrees>[0-9]{1,3})deg(?<longitude_minutes>[0-9]{1,2}(\.\d+)?)'\s*(?<longitude_hemisphere>[W|E])/", $sequence->source->locality, $matches))
			{	
			
				if ($debug) { print_r($matches); }	

				$degrees = $matches['latitude_degrees'];
				$minutes = $matches['latitude_minutes'];
				$hemisphere = $matches['latitude_hemisphere'];
				$lat = $degrees + ($minutes/60.0);
				if ($hemisphere == 'S') { $lat *= -1.0; };

				$sequence->source->latitude = $lat;

				$degrees = $matches['longitude_degrees'];
				$minutes = $matches['longitude_minutes'];
				$hemisphere = $matches['longitude_hemisphere'];
				$long = $degrees + ($minutes/60.0);
				if ($hemisphere == 'W') { $long *= -1.0; };
				
				$sequence->source->longitude  = $long;
				
				$matched = true;
			}
			
			if (!$matched)
			{
				
				//26'11'24N 81'48'16W
				
				//echo $seq['source']['locality'] . "\n";
				
				if (preg_match("/
				(?<latitude_degrees>[0-9]{1,2})
				'
				(?<latitude_minutes>[0-9]{1,2})
				'
				((?<latitude_seconds>[0-9]{1,2})
				'?)?
				(?<latitude_hemisphere>[S|N])
				\s+
				(?<longitude_degrees>[0-9]{1,3})
				'
				(?<longitude_minutes>[0-9]{1,2})
				'
				((?<longtitude_seconds>[0-9]{1,2})
				'?)?
				(?<longitude_hemisphere>[W|E])
				/x", $sequence->source->locality, $matches))
				{
					if ($debug) { print_r($matches); }	
						
					$degrees = $matches['latitude_degrees'];
					$minutes = $matches['latitude_minutes'];
					$seconds = $matches['latitude_seconds'];
					$hemisphere = $matches['latitude_hemisphere'];
					$lat = $degrees + ($minutes/60.0) + ($seconds/3600);
					if ($hemisphere == 'S') { $lat *= -1.0; };
	
					$sequence->source->latitude = $lat;
	
					$degrees = $matches['longitude_degrees'];
					$minutes = $matches['longitude_minutes'];
					$seconds = $matches['longtitude_seconds'];
					$hemisphere = $matches['longitude_hemisphere'];
					$long = $degrees + ($minutes/60.0) + ($seconds/3600);
					if ($hemisphere == 'W') { $long *= -1.0; };
					
					$sequence->source->longitude = $long;
					
					//print_r($seq);
					
					//exit();
					
					$matched = true;
				}
			}
			//exit();

			
		}
		
		if ($debug)
		{
			echo "Trying line " . __LINE__ . "\n";
		}
		
		
		//(GPS: 33 38' 07'', 146 33' 12'') e.g. AY281244
		if (preg_match("/\(GPS:\s*([0-9]{1,2})\s*([0-9]{1,2})'\s*([0-9]{1,2})'',\s*([0-9]{1,3})\s*([0-9]{1,2})'\s*([0-9]{1,2})''\)/", $country, $matches))
		{
			if ($debug) { print_r($matches); }	
			
			$lat = $matches[1] + $matches[2]/60 + $matches[3]/3600;
			
			// OMG
			if ($seq['source']['country'] == 'Australia')
			{
				$lat *= -1.0;
			}
			$long = $matches[4] + $matches[5]/60 + $matches[6]/3600;

			$sequence->source->latitude  = $lat;
			$sequence->source->longitude  = $long;
			
		}
		
		
	}
	
	if ($debug)
	{
		echo "Trying line " . __LINE__ . "\n";
	}
	
	// Some records have lat and lon in isolation_source, e.g. AY922971
	if (isset($sequence->source->isolation_source))
	{
		$isolation_source = $sequence->source->isolation_source;
		$matches = array();
		if (preg_match('/([0-9]+\.[0-9]+) (N|S), ([0-9]+\.[0-9]+) (W|E)/i', $isolation_source, $matches))
		{
			if ($debug) { print_r($matches); }	
			
			$sequence->source->latitude = $matches[1];
			if ($matches[2] == 'S')
			{
				$seq['source']['latitude'] *= -1;
			}
			$sequence->source->longitude = $matches[3];
			if ($matches[4] == 'W')
			{
				$seq['source']['longitude'] *= -1;
			}
			
		}
		if  (!isset($sequence->source->locality))
		{
			$sequence->source->locality = $sequence->source->isolation_source;
		}
	}
}	


//--------------------------------------------------------------------------------------------------
function fetch_sequences($ids)
{
	$hits = new stdclass;
	$hits->ids = $ids;
	$hits->sequences = array();	
	$hits->geometry = new stdclass;	
	$hits->geometry->type = "MultiPoint";
	$hits->geometry->coordinates = array();

	// Query URL	
	$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=nucleotide&id='
		. join(",", $ids)
		. '&rettype=gb&retmode=xml';
	
	$xml = get($url);

	if ($xml != '')
	{
		$xp = new XsltProcessor();
		$xsl = new DomDocument;
		$xsl->load('xml2json.xslt');
		$xp->importStylesheet($xsl);
		
		$dom = new DOMDocument;
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
	
		$json = $xp->transformToXML($dom);
		
	
		// fix "-" in variable names
		$json = str_replace('"GBSeq_feature-table"', 		'"GBSeq_feature_table"', $json);
		$json = str_replace('"GBSeq_primary-accession"', 	'"GBSeq_primary_accession"', $json);
		$json = str_replace('"GBSeq_other-seqids"', 		'"GBSeq_other_seqids"', $json);
		$json = str_replace('"GBSeq_create-date"', 			'"GBSeq_create_date"', $json);
		$json = str_replace('"GBSeq_update-date"', 			'"GBSeq_update_date"', $json);

			
		$sequences = json_decode($json);
	
		foreach ($sequences->GBSet as $GBSet)
		{
			$genbank_sequence = new stdclass;
			$genbank_sequence->accession = $GBSet->GBSeq_primary_accession;
			$genbank_sequence->organism = $GBSet->GBSeq_organism;


			$genbank_sequence->created 	= format_date($GBSet->GBSeq_create_date);
			$genbank_sequence->updated = format_date($GBSet->GBSeq_update_date);
						
			foreach ($GBSet->GBSeq_other_seqids as $seqids)
			{
				if (preg_match('/gi\|(?<gi>\d+)$/', $seqids, $m))
				{
					$genbank_sequence->gi = $m['gi'];
				}
			}
			
			$genbank_sequence->references = array();
			foreach ($GBSet->GBSeq_references as $GBReference)
			{
				$reference = new stdclass;
				$reference->title = $GBReference->GBReference_title;
				$reference->citation = $GBReference->GBReference_journal;
				$reference->authors = array();
				if (isset($GBReference->GBReference_authors))
				{
					foreach ($GBReference->GBReference_authors as $author)
					{
						$reference->authors[] = $author;
					}
				}				
				if (isset($GBReference->GBReference_pubmed))
				{
					if (!isset($reference->identifiers))
					{
						$reference->identifiers = new stdclass;
					}
					$reference->identifiers->pmid = $GBReference->GBReference_pubmed;
				}
				
				if (isset($GBReference->GBReference_xref))
				{
					if ($GBReference->GBReference_xref->GBXref->GBXref_dbname == 'doi')
					{
						if (!isset($reference->identifiers))
						{
							$reference->identifiers = new stdclass;
						}
						$reference->identifiers->doi = $GBReference->GBReference_xref->GBXref->GBXref_id;
					}
				}
				
				$genbank_sequence->references[] = $reference;
			}	
				
			
			
			foreach ($GBSet->GBSeq_feature_table as $feature_table)
			{
				switch ($feature_table->GBFeature_key)
				{
					case 'source':
						foreach ($feature_table->GBFeature_quals as $feature_quals)
						{
							switch ($feature_quals->GBQualifier_name)
							{
								case 'db_xref':
									$genbank_sequence->source->tax_id = str_replace("taxon:", '', $feature_quals->GBQualifier_value);
									break;
									
								case 'country':
									$genbank_sequence->source->country = $feature_quals->GBQualifier_value;
									break;
									
								case 'host':
									$genbank_sequence->source->host = $feature_quals->GBQualifier_value;
									break;								
	
								case 'locality':
									$genbank_sequence->source->locality = $feature_quals->GBQualifier_value;
									break;
	
								case 'isolation_source':
									$genbank_sequence->source->isolation_source = $feature_quals->GBQualifier_value;
									break;
									
								case 'isolate':
									$genbank_sequence->source->isolate = $feature_quals->GBQualifier_value;
									break;
									
								case 'lat_lon':
									$genbank_sequence->source->lat_lon = $feature_quals->GBQualifier_value;
									break;
	
								case 'specimen_voucher':
									$genbank_sequence->source->specimen_voucher = $feature_quals->GBQualifier_value;
									break;
										
								default:
									break;
							}					
						}
						
						
						if (isset($genbank_sequence->source->lat_lon))
						{
							process_lat_lon($genbank_sequence);
						}
						process_locality($genbank_sequence);
						break;
						
					default:
						break;
				}
			
			}
			
			if (isset($genbank_sequence->source->latitude))
			{
				$hits->geometry->coordinates[] = array($genbank_sequence->source->longitude, $genbank_sequence->source->latitude);
			}			
			$hits->sequences[] = $genbank_sequence;
	
		}
		
	}
	
	return $hits;
}

// test
if (0)
{
	$ids = array('DQ502910');
	
	$ids=array('JQ430674','JQ430671','JQ430665','JQ430664','JQ430666','JQ430660','JQ430672','JQ430669','JQ430668','JQ430667','JQ430673','JQ430670','JQ430659','GU339146','GU339145','GU339129','GU339128','GU339127','AF293969','GU339144','GU339143','GU339142','GU339141','GU339140','GU339134','GU339133','GU339132','GU339131','GU386314','GU339130');

	$hits = fetch_sequences($ids);

	print_r($hits);
}
?>