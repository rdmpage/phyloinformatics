<?php

// Fetch sequence(s) from GenBank

require_once (dirname(dirname(__FILE__)) . '/lib.php');
require_once (dirname(__FILE__) . '/nameparse.php');



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
			
			$sequence->source->latitude = (float)$matches[1];
			if ($matches[2] == 'S')
			{
				$sequence->source->latitude *= -1.0;
			}
			$sequence->source->longitude = (float)$matches[3];
			if ($matches[4] == 'W')
			{
				$sequence->source->longitude *= -1.0;
			}
		}
		if  (!isset($sequence->source->locality))
		{
			$sequence->source->locality = $sequence->source->isolation_source;
		}
	}
}	


//--------------------------------------------------------------------------------------------------
function fetch_sequence($id)
{
	$genbank_sequence = null;

	// Query URL	
	$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=nucleotide&id='
		. $id
		. '&rettype=gb&retmode=xml';
	
	$xml = get($url);
	
	//echo $xml;

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
		
		//echo $json;
	
		// fix "-" in variable names
		$json = str_replace('"GBSeq_feature-table"', 		'"GBSeq_feature_table"', $json);
		$json = str_replace('"GBSeq_primary-accession"', 	'"GBSeq_primary_accession"', $json);
		$json = str_replace('"GBSeq_other-seqids"', 		'"GBSeq_other_seqids"', $json);

		$json = str_replace('"GBSeq_update-date"', 			'"GBSeq_update_date"', $json);
		$json = str_replace('"GBSeq_create-date"', 			'"GBSeq_create_date"', $json);
		$json = str_replace('"GBSeq_accession-version"', 	'"GBSeq_accession_version"', $json);
		
			
		$sequences = json_decode($json);
		
		//print_r($sequences);
	
		foreach ($sequences->GBSet as $GBSet)
		{
			$genbank_sequence = new stdclass;
			$genbank_sequence->accession 			= $GBSet->GBSeq_primary_accession;
			$genbank_sequence->accession_version 	= $GBSet->GBSeq_accession_version;
			$genbank_sequence->organism 			= $GBSet->GBSeq_organism;
			$genbank_sequence->definition 			= $GBSet->GBSeq_definition;
			$genbank_sequence->moltype 				= $GBSet->GBSeq_moltype;
			
			// dates
			if (false != strtotime($GBSet->GBSeq_update_date))
			{
				$genbank_sequence->updated = date("Y-m-d", strtotime($GBSet->GBSeq_update_date));
			}	
			if (false != strtotime($GBSet->GBSeq_create_date))
			{
				$genbank_sequence->created = date("Y-m-d", strtotime($GBSet->GBSeq_create_date));
			}	
			
			// keywords
			$genbank_sequence->barcode = false;
			if ($GBSet->GBSeq_keywords)
			{
				foreach ($GBSet->GBSeq_keywords as $keyword)
				{
					if ($keyword = 'BARCODE')
					{
						$genbank_sequence->barcode = true;
					}
				}
			}
			
			foreach ($GBSet->GBSeq_other_seqids as $seqids)
			{
				if (preg_match('/gi\|(?<gi>\d+)$/', $seqids, $m))
				{
					$genbank_sequence->gi = (Integer)$m['gi'];
				}
			}
			
			$genbank_sequence->references = array();
			foreach ($GBSet->GBSeq_references as $GBReference)
			{
				$reference = new stdclass;
				$reference->title = $GBReference->GBReference_title;
				$reference->citation = $GBReference->GBReference_journal;
				if (isset($GBReference->GBReference_authors))
				{
					foreach ($GBReference->GBReference_authors as $a)
					{
						$parts = parse_name($a);					
						$author = new stdClass();
						$author->name = $a;
						if (isset($parts['last']))
						{
							$author->lastname = $parts['last'];
						}
						if (isset($parts['first']))
						{
							$author->forename = $parts['first'];
							
							if (array_key_exists('middle', $parts))
							{
								$author->forename .= ' ' . $parts['middle'];
							}
						}
						$reference->author[] = $author;					
					}
				}		
				
				if (preg_match('/(?<journal>.*)\s+(?<volume>\d+)(\s+\((?<issue>.*)\))?,\s+(?<spage>\d+)-(?<epage>\d+)\s+\((?<year>[0-9]{4})\)/', $reference->citation, $m))
				{
					$reference->journal = new stdclass;
					$reference->journal->name = $m['journal'];
					
					$reference->journal->volume = $m['volume'];
					if ($m['issue'] != '')
					{
						$reference->journal->issue = $m['issue'];
					}
					$reference->journal->pages = $m['spage'];
					if ($m['epage'] != '')
					{
						$reference->journal->pages .= '--' . $m['epage'];
					}
				}
				
				
				if (isset($GBReference->GBReference_pubmed))
				{
					$identifier = new stdclass;
					$identifier->type = 'pmid';
					$identifier->id = (Integer)$GBReference->GBReference_pubmed;
					$reference->identifier[] = $identifier;
				}
				
				if (isset($GBReference->GBReference_xref))
				{
					if ($GBReference->GBReference_xref->GBXref->GBXref_dbname == 'doi')
					{
						$identifier = new stdclass;
						$identifier->type = 'doi';
						$identifier->id = $GBReference->GBReference_xref->GBXref->GBXref_id;
						$reference->identifier[] = $identifier;
					}
				}
				
				$genbank_sequence->references[] = $reference;
			}	
				
			
			
			foreach ($GBSet->GBSeq_feature_table as $feature_table)
			{
				switch ($feature_table->GBFeature_key)
				{
					case 'source':
					
						if (!isset($genbank_sequence->source))
						{
							$genbank_sequence->source = new stdclass;
						}
					
						foreach ($feature_table->GBFeature_quals as $feature_quals)
						{
							switch ($feature_quals->GBQualifier_name)
							{
								case 'db_xref':
									$genbank_sequence->source->tax_id = (Integer)str_replace("taxon:", '', $feature_quals->GBQualifier_value);
									break;
									
								case 'collection_date':
									$genbank_sequence->source->collection_date = $feature_quals->GBQualifier_value;
									break;

								case 'collected_by':
									$genbank_sequence->source->collected_by = $feature_quals->GBQualifier_value;
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
									
								case 'mol_type':
									$genbank_sequence->source->mol_type = $feature_quals->GBQualifier_value;
									break;
									
								case 'organelle':
									$genbank_sequence->source->organelle = $feature_quals->GBQualifier_value;
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
				$genbank_sequence->source->geometry = new stdclass;
				$genbank_sequence->source->geometry->type = "Point";
				$genbank_sequence->source->geometry->coordinates = array((float)$genbank_sequence->source->longitude, (float)$genbank_sequence->source->latitude);
			}			
	
		}
		
	}
	
	return $genbank_sequence;
}


//--------------------------------------------------------------------------------------------------
function fetch_one($id)
{
	global $config;
		
	$sequence = fetch_sequence($id);
		
	//print_r($sequence);
}

if (0)
{
	$id = 'DQ502910';
	
	$id = 'AY273103';
	$id = 'FJ559180';
	
	//$id = 'DQ502910';
	
	//$id = 'DQ095127';
	//$id = 'HQ918317';
	
	$id = 'AY967993';
	$id = 'EU220392';
	
	$id = 'HM407788';
	
	$id = 'AY014968';
	
	$id=20153277;
	
	$id = 537783611;
	
	$id = 13559894;
	
	$id=574587242;

	fetch_one($id);
}

// test
if (0)
{
	$ids = array('DQ502910');
	
	$ids = array('AY273103');
	$ids = array('FJ559180');

	$hits = fetch_sequences($ids);
	
	

	print_r($hits);
}
?>