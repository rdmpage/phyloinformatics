<?php

// Parse NEXML and export JSON

require_once (dirname(dirname(__FILE__)) . '/lib.php');
require_once (dirname(__FILE__) . '/nameparse.php');
require_once (dirname(__FILE__) . '/ncbi.php');
require_once (dirname(dirname(__FILE__)) . '/treeviewer/tree.php');

//--------------------------------------------------------------------------------------------------
// Get span with respect to reference classification, as well as majority-rule
// taxon, and LCA
function get_span(&$tree)
{
	if (!isset($tree->translations->tax_id))
	{
		return;
	}
	$tree->classification->span = array();
	foreach ($tree->translations->tax_id as $k => $v)
	{
		$tree->classification->span[] = (Integer)$v;
	}
	
	// What is tree about?
	// Get majority rule taxon
	$c = array();
	$stack = array();

	foreach ($tree->classification->span as $tax_id)
	{
		$ancestors = ncbi_ancestors($tax_id);
		
		if (count($ancestors) != 0)
		{
			$ancestors = array_reverse($ancestors);
	
			foreach ($ancestors as $anc)
			{
				if (!isset($c[$anc]))
				{
					$c[$anc] = 0;
				}
				$c[$anc]++;
								
				// Store nodes in stack. This is really a partial order (i.e., a tree), but because we
				// visit nodes from root to tip, we preserve the order that matters
				if (!in_array($anc, $stack))
				{
					$stack[] = $anc;
				}
			}
		}
	}
	
	// Compute threshold for majority of taxa
	$num_taxa = $c[$stack[0]];
	$threshold = round($num_taxa/2);
	if ($num_taxa % 2 == 0)
	{
		$threshold++;
	}
	
	
	// Go down the stack until we hit a node that is more frequent than the majority rule theshold,
	// this is what the study is "about"
	$majority = array_pop($stack);
	while ($c[$majority] < $threshold)
	{
		$majority = array_pop($stack);
	}
		
	// 
	$tree->classification->majority_taxon = get_ncbi_taxon($majority);

	// Path from majority to root
	$tree->classification->majority_path = array_reverse(ncbi_ancestors($majority));
	
	// LCA of all taxa in tree
	$lca = $majority;
	while ($c[$lca] < $num_taxa)
	{
		$lca = array_pop($stack);
	}
	
	// Now make span comprise just unique taxa. We don't do this above because we want to take
	// the relavtive frequencies of each tax_id into account when computing majority_taxon
	$tree->classification->span = array_values(array_unique($tree->classification->span));

	
		
	$tree->classification->lca = $lca;
}



//--------------------------------------------------------------------------------------------------
function parse_nexml($xml)
{	
	//$xml = str_replace('xmlns="http://www.nexml.org/1.0"', '', $xml);
	$xml = str_replace('xmlns="http://www.nexml.org/2009"', '', $xml);
	
	
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath->registerNamespace('DwC', 'http://rs.tdwg.org/dwc/terms/');
	$xpath->registerNamespace('phylows', 'http://purl.org/phylo/treebase/phylows/');
	$xpath->registerNamespace('xsi', 'http://www.w3.org/1999/XMLSchema-instance');
	
	$nx = new stdclass;
	
	$otu_list = array();
	
	$taxids = array();
	
	//----------------------------------------------------------------------------------------------
	// Get publication details
	$nx->publication = new stdclass;
	
	$nc2 = $xpath->query ('meta');
	foreach ($nc2 as $n2)
	{
		if ($n2->hasAttributes()) 
		{ 
			$attributes2 = array();
			$attrs = $n2->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes2[$attr->name] = $attr->value; 
			}
			
			//print_r($attributes2);
			
			switch ($attributes2['property'])
			{
				case 'dc:title':
					$nx->publication->title = $attributes2['content'];
					$nx->publication->title = preg_replace('/\.$/Uu', '', $attributes2['content']);
					break;
					
				case 'prism:doi':
					$nx->publication->identifiers->doi = $attributes2['content'];
					break;
	
				case 'tb:identifier.study.tb1':
					if (isset($attributes2['content']))
					{
						$nx->publication->identifiers->treebase1 = $attributes2['content'];
					}
					break;
	
				case 'tb:identifier.study':
					$nx->publication->identifiers->treebase2 = 'S' . $attributes2['content'];
					break;
				
				case 'prism:startingPage':
					if (isset($nx->publication->pages))
					{
						$nx->publication->pages = $attributes2['content'] . '-' . $nx->publication->pages;
					}
					else
					{
						$nx->publication->pages = $attributes2['content'];
					}
					break;
	
				case 'prism:endingPage':
					if (isset($nx->publication->pages))
					{
						$nx->publication->pages .= '-' . $attributes2['content'];
					}
					else
					{
						$nx->publication->pages = $attributes2['content'];
					}
					break;
				
				/*
				case 'prism:pageRange':
					if (!isset($nx->publication->pages))
					{
						$nx->publication->pages = $attributes2['content'];
					}
					break;
				*/
			
				case 'prism:publicationName':
					$nx->publication->publication_outlet = $attributes2['content'];
					break;
					
				case 'prism:publicationDate':
					$nx->publication->year = $attributes2['content'];
					break;
					
				case 'prism:volume':
					$nx->publication->volume = $attributes2['content'];
					break;
					
				case 'prism:number':
					$nx->publication->issue = $attributes2['content'];
					break;
	
				case 'dc:subject':
					if ($attributes2['content'] != '')
					{
						$nx->publication->tags[] = $attributes2['content'];
					}
					break;
					
				case 'dc:contributor':
					$authorstring = $attributes2['content'];
					if (strpos($authorstring, ',') === false)
					{
						$authorstring = preg_replace('/^(\w+)\s/Uu', "$1,", $authorstring);					
					}
				
					// Get parts of name
					$parts = parse_name($authorstring);
					
					$author = new stdClass();
					
					if (isset($parts['last']))
					{
						$author->surname = $parts['last'];
					}
					if (isset($parts['suffix']))
					{
						$author->suffix = $parts['suffix'];
					}
					if (isset($parts['first']))
					{
						$author->forename = $parts['first'];
						$author->forename = preg_replace('/\./', ' ', $author->forename);
						$author->forename = preg_replace('/\s\s+/', ' ', $author->forename);
		
						if (array_key_exists('middle', $parts))
						{
							$author->forename .= ' ' . $parts['middle'];
						}
						
						$author->forename = trim($author->forename);
					}
					$nx->publication->authors[] = $author;
					break;	
	
					
					
				default:
					break;
			}
		}			
	}
		
	//----------------------------------------------------------------------------------------------
	// Node labels.
	// Store OTU arrays, extracting label, alternative labels, external identifiers (e.g., NCBI and uBio)
	//
	$nodeCollection = $xpath->query ('//otus');
	foreach($nodeCollection as $node)
	{
		$otus = array();
		
		if ($node->hasAttributes()) 
		{ 
			$attributes = array();
			$attrs = $node->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes[$attr->name] = $attr->value; 
			}
		}
		
		$otuid = '';
		if (isset($attributes['id']))
		{
			$otuid = $attributes['id'];
		}
		
		$nc = $xpath->query ('otu', $node);
		foreach ($nc as $n)
		{
			$otu = new stdclass;
			
			if ($n->hasAttributes()) 
			{ 
				$attributes = array();
				$attrs = $n->attributes; 
				
				foreach ($attrs as $i => $attr)
				{
					$attributes[$attr->name] = $attr->value; 
				}

				$otu->id = $attributes['id'];
				$otu->label = $attributes['label'];
				
				// External databases
				$nc2 = $xpath->query ('meta[@rel="skos:closeMatch"]', $n);
				foreach ($nc2 as $n2)
				{
					if ($n2->hasAttributes()) 
					{ 
						$attributes2 = array();
						$attrs = $n2->attributes; 
						
						foreach ($attrs as $i => $attr)
						{
							$attributes2[$attr->name] = $attr->value; 
						}
						
						if (isset($attributes2['href']))
						{
							if (preg_match('/http:\/\/purl.uniprot.org\/taxonomy\/(?<id>\d+)/', $attributes2['href'], $m))
							{
								if ($m['id'] != 0)
								{
									$otu->taxIds[] = $m['id'];
								
									$taxids[] = $m['id'];
								}
							}
						}
						if (isset($attributes2['href']))
						{
							if (preg_match('/urn:lsid:ubio.org:namebank:(?<id>\d+)$/', $attributes2['href'], $m))
							{
								$otu->namebankIDs[] = $m['id'];
							}
						}
					}
				
				}
	
				// Labels
				$nc2 = $xpath->query ('meta[@property="skos:altLabel"]|meta[@property="skos:prefLabel"]', $n);
				foreach ($nc2 as $n2)
				{
					if ($n2->hasAttributes()) 
					{ 
						$attributes2 = array();
						$attrs = $n2->attributes; 
						
						foreach ($attrs as $i => $attr)
						{
							$attributes2[$attr->name] = $attr->value; 
						}

						$otu->altLabels[] = $attributes2['content'];
					}			
				}
				
				// Geotagging
				$nc2 = $xpath->query ('meta[@property="DwC:DecimalLongitude"]', $n);
				foreach ($nc2 as $n2)
				{
					if ($n2->hasAttributes()) 
					{ 
						$attributes2 = array();
						$attrs = $n2->attributes; 
						
						foreach ($attrs as $i => $attr)
						{
							$attributes2[$attr->name] = $attr->value; 
						}
						$otu->coordinates = array();
						$otu->coordinates[0] = (double)$attributes2['content'];
					}			
				}
				$nc2 = $xpath->query ('meta[@property="DwC:DecimalLatitude"]', $n);
				foreach ($nc2 as $n2)
				{
					if ($n2->hasAttributes()) 
					{ 
						$attributes2 = array();
						$attrs = $n2->attributes; 
						
						foreach ($attrs as $i => $attr)
						{
							$attributes2[$attr->name] = $attr->value; 
						}
						$otu->coordinates[1] = (double)$attributes2['content'];
					}			
				}
				
			}
			
			$otus[] = $otu;
		}
	
		$otu_list[$otuid] = $otus;
	}
	
	//----------------------------------------------------------------------------------------------
	// Trees
	$nodeCollection = $xpath->query ('//trees');
	foreach($nodeCollection as $node)
	{
		// Label for set of OTUs for this tree
		$otu_label = '';
	
		// Get OTU list
		if ($node->hasAttributes()) 
		{ 
			$attributes2 = array();
			$attrs = $node->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes2[$attr->name] = $attr->value; 
			}
				
			// Store label for OTU set
			if (isset($attributes2['otus']))
			{
				$otu_label = $attributes2['otus'];
			}	
		}
			
		// Get the tree 
		$nc = $xpath->query ('tree', $node);
		foreach ($nc as $n)
		{
			$tree = new stdClass;
			$tree->nodes = $otu_list[$otu_label];
	
			// Tree object (Newick)
			$t = new Tree();
			
			// Tree attributes
			if ($n->hasAttributes()) 
			{ 
				$attributes2 = array();
				$attrs = $n->attributes; 
				
				foreach ($attrs as $i => $attr)
				{
					$attributes2[$attr->name] = $attr->value; 
				}
				
				if (isset($attributes2['label']))
				{
					$tree->label = $attributes2['label'];
				}
				if (isset($attributes2['id']))
				{
					$tree->id = $attributes2['id'];
					
					// For fuck's sake can we not standardise where we put the external tree identifier?!
					if (preg_match('/^Tr\d+$/', $attributes2['id']))
					{
						$tree->identifiers->treebase2 = $attributes2['id'];
					}
				}
			}
	
			//--------------------------------------------------------------------------------------
			// Meta		
			$nc2 = $xpath->query ('meta', $n);
			foreach ($nc2 as $n2)
			{
				if ($n2->hasAttributes()) 
				{ 
					$attributes2 = array();
					$attrs = $n2->attributes; 
					
					foreach ($attrs as $i => $attr)
					{
						$attributes2[$attr->name] = $attr->value; 
					}
					
					// TreeBASE 2 properties
					switch ($attributes2['property'])
					{
						case 'tb:kind.tree':
							$tree->kind = $attributes2['content'];
							break;	
	
						case 'tb:type.tree':
							$tree->type = $attributes2['content'];
							break;	
	
						case 'tb:ntax.tree':
							$tree->ntax = $attributes2['content'];
							break;	
	
						case 'tb:quality.tree':
							$tree->quality = $attributes2['content'];
							break;	
						
						default:
							break;						
					}
	
					// TreeBASE 2 identifier
					switch ($attributes2['rel'])
					{
						case 'owl:sameAs':
							$tree->identifiers->treebase2  = str_replace('tree/TB2:', '', $attributes2['href']);
							break;	
						default:
							break;						
					}
				}
			}		
			
			//--------------------------------------------------------------------------------------
			// Temporary array to store nodes, we use this when building the tree
			$nodes = array();
			
			// List of possible roots, at end of processing <edge> tag this should have just a single
			// node, the root
			$roots = array();
			
			//------------------------------------------------------------------------------------------
			// Get nodes, store them in $nodes
			$nc2 = $xpath->query ('node', $n);
			foreach ($nc2 as $n2)
			{
				if ($n2->hasAttributes()) 
				{ 
					$attributes2 = array();
					$attrs = $n2->attributes; 
					
					foreach ($attrs as $i => $attr)
					{
						$attributes2[$attr->name] = $attr->value; 
					}
					
					$node = $t->NewNode();
					
					
					if (isset($attributes2['otu']))
					{
						$node->SetLabel($attributes2['otu']);
					}
					else
					{
						// debugging
						//$node->SetLabel($attributes2['id']);
					}


					$nodes[$attributes2['id']] = $node;				
					
					$roots[$attributes2['id']] = $attributes2['id'];
				}		
			}
			
			//--------------------------------------------------------------------------------------
			// Get edges
			$nc2 = $xpath->query ('edge', $n);
			foreach ($nc2 as $n2)
			{
				if ($n2->hasAttributes()) 
				{ 
					$attributes2 = array();
					$attrs = $n2->attributes; 
					
					foreach ($attrs as $i => $attr)
					{
						$attributes2[$attr->name] = $attr->value; 
					}
					
					if ($nodes[$attributes2['source']]->GetChild() == NULL)
					{
						$nodes[$attributes2['source']]->SetChild($nodes[$attributes2['target']]);
					}
					else
					{
						$q = $nodes[$attributes2['source']]->GetChild()->GetRightMostSibling();
						$q->SetSibling($nodes[$attributes2['target']]);
					}
					$nodes[$attributes2['target']]->SetAncestor($nodes[$attributes2['source']]);
					
					// Edge lengths
					if (isset($attributes2['length']))
					{
						$nodes[$attributes2['target']]->SetAttribute('edge_length', $attributes2['length']);
					}
					
					// target node has "ancestor" hence can't be root, so delete from list of potential
					// roots
					unset($roots[$attributes2['target']]);
				}
			}
			
			// We now know the root, we store tree description and the tree object
			$t->SetRoot($nodes[array_pop($roots)]);
			$tree->profile[] = $t->WriteNewick();
			$nx->trees[] = $tree;
		}
	}
	
	//print_r($nx);
	//exit();
	
	return $nx;
}

//--------------------------------------------------------------------------------------------------
function nex2json($xml)
{
	$nx = parse_nexml($xml);
	
	// post process
	
	// TreeBASE file may have > 1 tree
	foreach ($nx->trees as $tree)
	{
		echo "-- Processing trees\n";
		$j = new stdclass;

		$j->tree = $tree->profile[0];
		if (isset($tree->label))
		{
			$j->label = $tree->label;
		}
		$j->identifier = $tree->identifiers->treebase2;
			
		// Reference that published this tree
		$j->source = $nx->publication;
		
		// translation table(s)
		$j->translations = new stdclass;
		
		// TreeBASE taxon name
		$j->translations->taxa = new stdclass;
		foreach ($tree->nodes as $node)
		{
			$j->translations->taxa->{$node->id} = $node->label;
		}
		
		// NCBI tax_id
		$have = false;
		$j->translations->tax_id = new stdclass;
		foreach ($tree->nodes as $node)
		{
			if (isset($node->taxIds))
			{
				$have = true;
				$j->translations->tax_id->{$node->id} = (Integer)$node->taxIds[0];
			}
		}	
		if (!$have)
		{
			unset($j->translations->tax_id);
		}
	
		// uBio namebankID
		$have = false;
		$j->translations->namebankID = new stdclass;
		foreach ($tree->nodes as $node)
		{
			if (isset($node->namebankIDs))
			{	
				$have = true;
				$j->translations->namebankID->{$node->id} = (Integer)$node->namebankIDs[0];
			}
		}	
		if (!$have)
		{
			unset($j->translations->namebankID);
		}
		
		echo "-- Coordinates\n";
		// Nodes in tree may have coordinates (e.g., study S10423)
		foreach ($tree->nodes as $node)
		{
			if (isset($node->coordinates))
			{
				if (!isset($j->geometry))
				{
					$j->geometry = new stdclass;
					$j->geometry->type='MultiPoint';
					$j->geometry->coordinates=array();
				}
				$j->geometry->coordinates[] = $node->coordinates;
			}
		}
		
		echo "-- Span\n";
		//print_r($j);
		// Locate tree in NCBI taxonomy
		get_span ($j);
		
		//print_r($j);
		
		
		echo "-- NEXUS\n";

		// Tree in NEXUS format...		
		$nexus = "#NEXUS\n";
		$nexus .= "BEGIN TREES;\n";
		$nexus .= "\tTRANSLATE\n";
		
		$count = 0;
		foreach ($j->translations->taxa as $k => $v)
		{
			if ($count != 0)
			{
				$nexus .= ",\n";
			}
			$count++;
			$nexus .= "\t\t" . $k . " '" . $v . "'";
			
		}
		$nexus .= "\t\t;\n";
		$nexus .= "\tTREE '" . $j->label . "' = " . $j->tree . "\n";
		$nexus .= "END;\n";
		
		//echo $nexus;
		
		// To SQL
		
		
		if (isset($j->classification))
		{
			
			$sql = "REPLACE INTO treebase(id,publication,label, majority_taxon_tax_id, majority_taxon_bbox, tree)
			VALUES('TB2:" . $j->identifier . '\''
			. ',' . '\'' . addcslashes(json_encode($j->source), '\'\\') . '\''
			. ',' . '\'' . addcslashes($j->label, "'") . '\''
			. ',' . $j->classification->majority_taxon->tax_id
			. ',' . 'GeomFromText("' . ncbi_bbox($j->classification->majority_taxon->tax_id) . '")'
			. ',' . '\'' . addcslashes($nexus, "'\\") . '\''
			. ');' . "\n";
		
			echo $sql;
		}
	
	}

}


if (1)
{
	
	
	$filename = 'nexml/S12224.xml'; // mantids
	$filename = 'nexml/S2108.xml'; // birds
	//$filename = 'nexml/S12162.xml'; //  frog refugia
	//$filename = 'nexml/S10423.xml'; // Tungara frog with lats,longs
	//$filename = 'nexml/S1252.xml'; // squamates
	//$filename = 'nexml/S10190.xml'; // cetaceans
	//$filename = 'nexml/S12330.xml'; // spiders
	//$filename = 'nexml/S12335.xml'; // spiders
	
	$filename = '/Users/rpage/Sites/nexml/nexml/S10045.xml';
	
	$filename = '/Users/rpage/Sites/nexml/nexml/S10135.xml'; // 0 taxids (sigh)
	$filename = '/Users/rpage/Sites/nexml/nexml/S2108.xml'; //
	$filename = '/Users/rpage/Sites/nexml/nexml/S2014.xml'; //
	$filename = '/Users/rpage/Sites/nexml/nexml/S11742.xml'; //
	$filename = '/Users/rpage/Sites/nexml/nexml/S11988.xml'; // Pristimantis (big)

	
	$xml = file_get_contents($filename);
	
	nex2json($xml);
}
else
{
	$basedir = '/Users/rpage/Sites/nexml/nexml';

	$files = scandir($basedir);
	
	foreach ($files as $filename)
	{
		if (preg_match('/S1[1,2][0-9]{3}\.xml$/', $filename))
		{	
			$xml = file_get_contents($basedir . '/' . $filename);
			
			echo "-- " . $filename . "\n";
			
			
			if ($xml != '')
			{
				nex2json($xml);
			}
		}
	}
}

?>