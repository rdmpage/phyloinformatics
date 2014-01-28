<?php

// crude RSS until I figure out how to support conditional HTTP queries properly

require_once (dirname(__FILE__) . '/genbank_fetch.php');




//--------------------------------------------------------------------------------------------------
/**
 * @brief Generate UUID
 *
 * From http://www.ajaxray.com/blog/2008/02/06/php-uuid-generator-function/
 *
  * @author     Anis uddin Ahmad <admin@ajaxray.com>
  * @param      string  an optional prefix
  * @return     string  the formatted uuid
  */
function uuid($prefix = '')
{
	$chars = md5(uniqid(mt_rand(), true));
	$uuid  = substr($chars,0,8) . '-';
	$uuid .= substr($chars,8,4) . '-';
	$uuid .= substr($chars,12,4) . '-';
	$uuid .= substr($chars,16,4) . '-';
	$uuid .= substr($chars,20,12);
	
	return $prefix . $uuid;
} 




$taxon_id = 2759;
if (isset($_GET['taxon_id']))
{
	$taxon_id = $_GET['taxon_id'];
}

$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&term=barcode[keyword]+txid' . $taxon_id . '[Organism:exp]&retmax=10'; //&reldate=365&datetype=mdat';


$xml = get($url);

//echo $xml;

$ids = array();

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath_query = "//eSearchResult/IdList/Id";
$nodeCollection = $xpath->query ($xpath_query);
foreach($nodeCollection as $node)
{
	$id = $node->firstChild->nodeValue;
	array_push ($ids, $id);
}

//print_r($ids);

// Get GenBank records


// RSS
$feed = new DomDocument('1.0', 'UTF-8');

$feed->preserveWhiteSpace = false;
$feed->formatOutput = true;

$rss = $feed->createElement('feed');
$rss->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
$rss->setAttribute('xmlns:geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#');
$rss->setAttribute('xmlns:georss', 'http://www.georss.org/georss');
$rss = $feed->appendChild($rss);
	
// feed

// title
$title = $feed->createElement('title');
$title = $rss->appendChild($title);
$value = $feed->createTextNode('NCBI Sequences for taxon ' . $taxon_id);
$value = $title->appendChild($value);
	
// link
$link = $feed->createElement('link');
$link->setAttribute('href', 'http://iphylo.org/~rpage/phyloinformatics/rss');
$link = $rss->appendChild($link);
	
$link = $feed->createElement('link');
$link->setAttribute('rel', 'self');
$link->setAttribute('type', 'application/atom+xml');
$link->setAttribute('href', 'http://iphylo.org/~rpage/phyloinformatics/rss/barcode.php?taxon_id=' . $taxon_id);
$link = $rss->appendChild($link);
			
// updated
$updated = $feed->createElement('updated');
$updated = $rss->appendChild($updated);
$value = $feed->createTextNode(date(DATE_ATOM));
$value = $updated->appendChild($value);
	
// id
$id = $feed->createElement('id');
$id = $rss->appendChild($id);
$id->appendChild($feed->createTextNode('urn:uuid:' . uuid()));

// author
$author = $feed->createElement('author');
$author = $rss->appendChild($author);

$name = $feed->createElement('name');
$name = $author->appendChild($name);
$name->appendChild($feed->createTextNode('Phyloinformatics'));


// Cache, store metadata, and make into RSS feed

foreach ($ids as $id)
{
	$obj = fetch_sequence($id);
	
	//print_r($obj);
	
	if (isset($obj->accession))
	{
	
		$item = new stdclass;
		$item->links = array();
		
		if (isset($obj->created))
		{
			$item->created = $obj->created;
		}
		if (isset($obj->updated))
		{
			$item->updated = $obj->updated;
		}
		if (isset($obj->source->latitude))
		{
			$item->latitude = $obj->source->latitude;
		}
		if (isset($obj->source->longitude))
		{
			$item->longitude = $obj->source->longitude;
		}
		
		$item->title = $obj->accession;
		$item->description = $obj->definition;
		$item->link = 'http://www.ncbi.nlm.nih.gov/nuccore/' . $obj->gi;
		$item->id = $item->link;
		
		$item->description .= '<br/><a href="http://www.ncbi.nlm.nih.gov/nuccore/' . $obj->accession . '">' . $obj->accession . '</a>';
		
		// Source taxon
		$txid = $obj->source->tax_id;
		array_push($item->links, array('taxon' =>  $txid));
		$item->description .= '<br/><a href="http://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=' . $txid . '">taxon:' . $txid . '</a>';
		
		
		//print_r($item);
		
		// Store
		//$this->StoreFeedItem($item);
		
		
		$entry = $feed->createElement('entry');
		$entry = $rss->appendChild($entry);
		
		// title
		$title = $entry->appendChild($feed->createElement('title'));
		$title->appendChild($feed->createTextNode($item->title));
	
		// link
		$link = $entry->appendChild($feed->createElement('link'));
		$link->setAttribute('rel', 'alternate');
		$link->setAttribute('type', 'text/html');
		$link->setAttribute('href', $item->link);
	
		// dates
		$updated = $entry->appendChild($feed->createElement('updated'));
		$updated->appendChild($feed->createTextNode(date(DATE_ATOM, strtotime($item->updated))));
		
		if (isset($item->created))
		{
			$created = $entry->appendChild($feed->createElement('published'));
			$created->appendChild($feed->createTextNode(date(DATE_ATOM, strtotime($item->created))));
		}		
		
		
		// id
		$id = $entry->appendChild($feed->createElement('id'));
		$id->appendChild($feed->createTextNode('urn:uuid:' . uuid()));
	
		// content
		$content = $entry->appendChild($feed->createElement('content'));
		$content->setAttribute('type', 'html');
		$content->appendChild($feed->createTextNode($item->description));
	
		// summary (do we need this, it is duplicated in Google Maps? )
		/*$summary = $entry->appendChild($feed->createElement('summary'));
		$summary->setAttribute('type', 'html');
		$summary->appendChild($feed->createTextNode($item->description));*/
	
		// georss
		if (isset($item->latitude))
		{
			/*
			$geo = $entry->appendChild($feed->createElement('georss:point'));
			$geo->appendChild($feed->createTextNode($item->latitude . ' ' . $item->longitude));
			*/
			
			$geo = $entry->appendChild($feed->createElement('geo:lat'));
			$geo->appendChild($feed->createTextNode($item->latitude));
	
			$geo = $entry->appendChild($feed->createElement('geo:long'));
			$geo->appendChild($feed->createTextNode($item->longitude));
			
		}
		
					
		// links
		foreach ($item->links as $link)
		{
			//print_r($link);
		
			foreach ($link as $k => $v)
			{
				switch ($k)
				{
					case 'taxon':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', 'http://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=' . $v);
						$link->setAttribute('title', 'taxon:' . $v);
						break;
	
					case 'doi':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', 'http://dx.doi.org/' . $v);
						$link->setAttribute('title', 'doi:' . $v);
						break;

					case 'hdl':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', 'http://hdl.handle.net/' . $v);
						$link->setAttribute('title', 'hdl:' . $v);
						break;
	
					case 'pmid':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', 'http://www.ncbi.nlm.nih.gov/pubmed/' . $v);
						$link->setAttribute('title', 'pmid:' . $v);
						break;

					case 'lsid':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', 'http://bioguid.info/' . $v);
						$link->setAttribute('title', $v);
						break;

					case 'url':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', $v);
						$link->setAttribute('title', $v);
						break;

					case 'pdf':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'application/pdf');
						$link->setAttribute('href', $v);
						$link->setAttribute('title', 'PDF');
						break;
						
					case 'msw':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', 'http://www.bucknell.edu/msw3/browse.asp?id=' . $v);
						$link->setAttribute('title', 'msw:' . $v);
						break;
						
					case 'itis':
						$link = $entry->appendChild($feed->createElement('link'));
						$link->setAttribute('rel', 'related');
						$link->setAttribute('type', 'text/html');
						$link->setAttribute('href', 'http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=' . $v);
						$link->setAttribute('title', 'tsn:' . $v);
						break;
						
					default:
						break;
				}
			}
		}
		
		
		
	}
}

//$feed->xmlIndent();
echo $feed->saveXML();


?>