<?php

//--------------------------------------------------------------------------------------------------
// MySQL
require_once(dirname(dirname(__FILE__)).'/adodb5/adodb.inc.php');

$db = NewADOConnection('mysql');
$db->Connect(
	"localhost", 	# machine hosting the database, e.g. localhost
	'root', 		# MySQL user name
	'', 			# password
	'phyloinformatics'	# database
	);
	
// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

require_once (dirname(dirname(__FILE__)) . '/lib.php');

//--------------------------------------------------------------------------------------------------
// from http://www.ajaxray.com/blog/2008/02/06/php-uuid-generator-function/
/**
  * Generates an UUID
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


define('RSS1', 'RSS 1.0', true);
define('RSS2', 'RSS 2.0', true);
define('ATOM', 'ATOM', true);

//--------------------------------------------------------------------------------------------------
class FeedMaker
{
	var $id;
	var $url;
	var $title;
	var $harvest_interval;
	var $items;
	var $version;
	var $etag;
	var $last_modified;
	
	//----------------------------------------------------------------------------------------------
	function __construct($url, $title, $harvest_interval = 1, $version = ATOM)
	{
		$this->url 				= $url;
		$this->title 			= $title;
		$this->harvest_interval = $harvest_interval;
		$this->StoreFeedSource();
		$this->version			= $version;
	}
	
	//----------------------------------------------------------------------------------------------
	// For moany feeds the URL will be unique, but for others (especially services) this may not be
	// the case, so make this a method we can override.
	function FeedId ()
	{
		$this->id = md5($this->url);
	}
	
	//----------------------------------------------------------------------------------------------
	function StoreFeedSource()
	{
		global $db;
		
		$this->FeedId();
		
		$sql = 'SELECT * FROM `feed_source` WHERE (id = ' . $db->qstr($this->id) . ') LIMIT  1';
		
		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
		if ($result->NumRows() == 0)
		{
		
		
			$sql = 'INSERT INTO `feed_source`(`id`, `url`, `last_accessed`, `harvest_interval`) VALUES ('
			 . $db->qstr($this->id)
			 . ', ' . $db->qstr($this->url)
			 . ', ' . $db->qstr('2000-01-01 00:00:00')
			 . ', ' . $this->harvest_interval
			 . ')';
			 
			 
			 
			$result = $db->Execute($sql);
			if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
			
		}
	}	
	
	//----------------------------------------------------------------------------------------------
	// Store the time we harvested the feed from it's remote source, and set the Etag and 
	// last modified fields to a new value and the present time, respectively
	function StoreFeedHarvestTime()
	{
		global $db;
			
		$sql = 'SELECT * FROM `feed_source` WHERE (id = ' . $db->qstr($this->id) . ') LIMIT  1';
		
		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
		if ($result->NumRows() == 1)
		{
			$this->etag = '"' . md5(uniqid()) . '"';
			$this->last_modified = date(DATE_RFC822);
		
			$sql = 'UPDATE feed_source SET last_accessed=NOW()'
				. ', etag=' . $db->qstr($this->etag)
				. ', last_modified=' . $db->qstr($this->last_modified)
				. ' WHERE (id=' . $db->qstr($this->id) . ')';
			
			$result = $db->Execute($sql);
			if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
			
		}
	}		
	
	//----------------------------------------------------------------------------------------------
	// Has feed expired (i.e., is the last time we harvested the feed older than the harvest interval?)
	// At the same time we retrieve the ETag and last modified settings
	function SourceExpired($id)
	{
		global $db;
		
		$expired = 0;
		
		$sql = 'SELECT * FROM `feed_source` WHERE (id = ' . $db->qstr($this->id) . ') LIMIT  1';
				
		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
		if ($result->NumRows() == 1)
		{
			$this->etag = $result->fields['etag'];
			$this->last_modified = $result->fields['last_modified'];
			
			$sql = 'SELECT (DATE_ADD(' . $db->qstr($result->fields['last_accessed']) 
				. ', INTERVAL ' . $db->qstr($result->fields['harvest_interval']) . ' DAY)) < NOW() AS expired';
				
			$result = $db->Execute($sql);
			if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
			
			$expired = $result->fields['expired'];
		}
		
		return $expired;
	}	
	
	//----------------------------------------------------------------------------------------------
	// Get last modified and etag
	function GetETag()
	{
		global $db;

		$sql = 'SELECT * FROM `feed_source` WHERE (id = ' . $db->qstr($this->id) . ') LIMIT  1';
				
		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
		if ($result->NumRows() == 1)
		{
			$this->etag = $result->fields['etag'];
			$this->last_modified = $result->fields['last_modified'];
		}
	}		

	//----------------------------------------------------------------------------------------------
	function RetrieveFeedItems ($num_items = 100)
	{
		global $db;
		
		$this->items = array();
	
		$sql = 'SELECT * FROM feed_item 
		WHERE (feed_id = ' . $db->qstr($this->id) . ')
		ORDER BY added DESC
		LIMIT ' . $num_items;
		
		//echo $sql;
		
		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
		while (!$result->EOF) 
		{
			$item = new stdclass;
			
			$item->link = $result->fields['link'];
			$item->title = $result->fields['title'];
			$item->description = $result->fields['description'];

			// dates
			$item->updated = $result->fields['updated'];
			if ($result->fields['created'] != '')
			{
				$item->created = $result->fields['created'];
			}
			
			if ($result->fields['latitude'] != '')
			{
				$item->latitude = $result->fields['latitude'];
			}
			if ($result->fields['longitude'] != '')
			{
				$item->longitude = $result->fields['longitude'];
			}
			if ($result->fields['links'] != '')
			{
				$item->links = json_decode($result->fields['links']);
			}
			else
			{
				$item->links = array();
			}
			if ($result->fields['payload'] != '')
			{
				$item->payload = json_decode($result->fields['payload']);
			}
			
			array_push($this->items, $item);
			$result->MoveNext();				
		}
		
		//print_r($this->items);
	}	

	//----------------------------------------------------------------------------------------------
	function StoreFeedItem ($item)
	{
		global $db;
	
		// Don't overwite
		
		$sql = 'SELECT * FROM `feed_item` WHERE (id = ' . $db->qstr($item->id) . ') LIMIT  1';
		
		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
		if ($result->NumRows() == 0)
		{
			// we don't have this one
			$sql = 'INSERT INTO feed_item(';
			$columns = '';
			$values = ') VALUES (';
			
			$columns .= 'id';
			$values .= $db->qstr($item->id);
	
			$columns .= ',feed_id';
			$values .= ',' . $db->qstr($this->id);
			
			$columns .= ',title';
			$values .= ',' . $db->qstr($item->title);
	
			$columns .= ',link';
			$values .= ',' . $db->qstr($item->link);
	
			$columns .= ',description';
			$values .= ',' . $db->qstr($item->description);
			
			// dates
			if (isset($item->updated))
			{
				$columns .= ',updated';
				$values .= ',' . $db->qstr($item->updated);
			}
			else
			{
				$columns .= ',updated';
				$values .= ', NOW()';
			}

			if (isset($item->created))
			{
				$columns .= ',created';
				$values .= ',' . $db->qstr($item->created);
			}
			else
			{
				$columns .= ',created';
				$values .= ', NOW()';
			}
			
	
			if (isset($item->latitude))
			{
				$columns .= ',latitude';
				$values .= ',' . $item->latitude;
			}
			if (isset($item->longitude))
			{
				$columns .= ',longitude';
				$values .= ',' . $item->longitude;
			}
			
			if (isset($item->links))
			{
				$j = json_encode($item->links);
				$columns .= ',links';
				$values .= ',' . $db->qstr($j);
			}
				
			if (isset($item->payload))
			{
				$j = json_encode($item->payload);
				$columns .= ',payload';
				$values .= ',' . $db->qstr($j);
			}
			
			
			$sql .= $columns . $values . ');';
			
			//echo $sql;
			
			// Store
			$result = $db->Execute($sql);
			if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
		}
	}
		
	//----------------------------------------------------------------------------------------------
	function GetRss()
	{
		if (1)
		{
			$this->Harvest();
			$this->StoreFeedHarvestTime();

			// Get cached content
			$this->RetrieveFeedItems();
			
			$rss = $this->ItemsToFeed();
			return $rss;		
		}
		else
		{
			if ($this->SourceExpired($this->id))
			{
				$this->Harvest();
				$this->StoreFeedHarvestTime();
			}
			
			//echo "Line: " . __LINE__ . " " . $this->etag;
			
			// Get cached content
			$this->RetrieveFeedItems();
			
			$rss = $this->ItemsToFeed();
			return $rss;
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function ItemsToFeed()
	{
		$feed = new DomDocument('1.0');
		
		switch ($this->version)
		{
			case RSS1:
				$rss = $feed->createElement('rdf:RDF');
				$rss->setAttribute('xmlns', 'http://purl.org/rss/1.0/');
				$rss->setAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
				$rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
				$rss->setAttribute('xmlns:prism', 'http://prismstandard.org/namespaces/1.2/basic/');
				$rss->setAttribute('xmlns:geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#');
				$rss->setAttribute('xmlns:georss', 'http://www.georss.org/georss');
				$rss = $feed->appendChild($rss);

				// channel
				$channel = $feed->createElement('channel');
				$channel->setAttribute('rdf:about', $this->url);
				$channel = $rss->appendChild($channel);
				
				// title
				$title = $channel->appendChild($feed->createElement('title'));
				$title->appendChild($feed->createTextNode($this->title));

				// link
				$link = $channel->appendChild($feed->createElement('link'));
				$link->appendChild($feed->createTextNode($this->url));

				// description
				$description = $channel->appendChild($feed->createElement('description'));
				$description->appendChild($feed->createTextNode($this->title));

				// items
				$items = $channel->appendChild($feed->createElement('items'));
				$seq = $items->appendChild($feed->createElement('rdf:Seq'));

				// list them
				foreach ($this->items as $item)
				{
					$li = $seq->appendChild($feed->createElement('rdf:li'));
					$li->setAttribute('rdf:resource', $item->link	);
				}			
				
				//print_r($this->items);
				
				// items
				foreach ($this->items as $item)
				{
					$i = $rss->appendChild($feed->createElement('item'));
					$i->setAttribute('rdf:about', $item->link);
					
					// title
					$title = $i->appendChild($feed->createElement('title'));
					$title->appendChild($feed->createTextNode($item->title));

					// link
					$link = $i->appendChild($feed->createElement('link'));
					$link->appendChild($feed->createTextNode($item->link));

					// description
					$description = $i->appendChild($feed->createElement('description'));
					$description->appendChild($feed->createTextNode($item->description));
					
					
					if (isset($item->payload))
					{
						if (isset($item->payload->atitle))
						{
							$element = $i->appendChild($feed->createElement('dc:title'));
							$element->appendChild($feed->createTextNode($item->payload->atitle));	
						}
						if (isset($item->payload->date))
						{
							$element = $i->appendChild($feed->createElement('dc:date'));
							$element->appendChild($feed->createTextNode($item->payload->date));	
						}
						if (isset($item->payload->title))
						{
							$element = $i->appendChild($feed->createElement('prism:publicationName'));
							$element->appendChild($feed->createTextNode($item->payload->title));	
						}
						if (isset($item->payload->issn))
						{
							$element = $i->appendChild($feed->createElement('prism:issn'));
							$element->appendChild($feed->createTextNode($item->payload->issn));	
						}
						if (isset($item->payload->volume))
						{
							$element = $i->appendChild($feed->createElement('prism:volume'));
							$element->appendChild($feed->createTextNode($item->payload->volume));	
						}
						if (isset($item->payload->issue))
						{
							$element = $i->appendChild($feed->createElement('prism:number'));
							$element->appendChild($feed->createTextNode($item->payload->issue));	
						}
						if (isset($item->payload->spage))
						{
							$element = $i->appendChild($feed->createElement('prism:startingPage'));
							$element->appendChild($feed->createTextNode($item->payload->spage));	
						}
						if (isset($item->payload->epage))
						{
							$element = $i->appendChild($feed->createElement('prism:endingPage'));
							$element->appendChild($feed->createTextNode($item->payload->epage));	
						}
						if (isset($item->payload->authors))
						{
							foreach($item->payload->authors as $author)
							{
								$str = $author->forename . ' ' . $author->lastname;
								if (isset($author->suffix))
								{
									$str .= ' ' . $author->suffix;
								}
								
								$element = $i->appendChild($feed->createElement('dc:creator'));
								$element->appendChild($feed->createTextNode($str));								
							}
						
						}
						
						// Literal tags (e.g., keywords)
						if (isset($item->payload->tags))
						{
							foreach($item->payload->tags as $tag)
							{
								$element = $i->appendChild($feed->createElement('dc:subject'));
								$element->appendChild($feed->createTextNode($tag));								
							}
						}
						// Tag URIs (should this be foaf:topic ?)
						if (isset($item->payload->tagids))
						{
							foreach($item->payload->tagids as $tag)
							{
								$element = $i->appendChild($feed->createElement('dc:subject'));
								$element->setAttribute('rdf:resource', $tag	);
							}
						}
							
					
					}
					
				}	
/*				

				
<item rdf:about="http://scx.sagepub.com/cgi/reprint/30/3/299?rss=1">
<title><![CDATA[No More "Business as Usual": Addressing Climate Change Through Constructive Engagement]]></title>
<link>http://scx.sagepub.com/cgi/reprint/30/3/299?rss=1</link>
<description><![CDATA[]]></description>
<dc:creator><![CDATA[Maibach, E., Hornig Priest, S.]]></dc:creator>
<dc:date>2009-02-06</dc:date>
<dc:identifier>info:doi/10.1177/1075547008329202</dc:identifier>
<dc:title><![CDATA[No More "Business as Usual": Addressing Climate Change Through Constructive Engagement]]></dc:title>
<prism:number>3</prism:number>
<prism:volume>30</prism:volume>
<prism:endingPage>304</prism:endingPage>

<prism:publicationDate>2009-03-01</prism:publicationDate>
<prism:startingPage>299</prism:startingPage>
<prism:section>Article</prism:section>
</item>
				
*/					
				break;
				
			case RSS2:
				break;
				
			case ATOM:
				// header
				$rss = $feed->createElement('feed');
				$rss->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
				$rss->setAttribute('xmlns:geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#');
				$rss->setAttribute('xmlns:georss', 'http://www.georss.org/georss');		
				$rss->setAttribute('xmlns:dcterms','http://purl.org/dc/terms/');
				$rss = $feed->appendChild($rss);
				
				// feed
				
				// title
				$title = $feed->createElement('title');
				$title = $rss->appendChild($title);
				$value = $feed->createTextNode($this->title);
				$value = $title->appendChild($value);
				
				// link
				$link = $feed->createElement('link');
				$link->setAttribute('href', $this->url);
				$link = $rss->appendChild($link);
				
				// updated
				$updated = $feed->createElement('updated');
				$updated = $rss->appendChild($updated);
				$value = $feed->createTextNode(date(DATE_ATOM));
				$value = $updated->appendChild($value);
				
				// id
				$id = $feed->createElement('id');
				$id = $rss->appendChild($id);
				$value = $feed->createTextNode('urn:uuid:' . uuid());
				$value = $id->appendChild($value);
				
				// author
				$author = $feed->createElement('author');
				$author = $rss->appendChild($author);
				
				$name = $feed->createElement('name');
				$name = $author->appendChild($name);
				
				$value = $feed->createTextNode('Rod Page');
				$value = $name->appendChild($value);
				
				foreach ($this->items as $item)
				{
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
					
					// Payload
					if (isset($item->payload))
					{
						
						if (isset($item->payload->tags))
						{
							// tags as ATOM categories, e.g.
							// category term="botanicgardens" scheme="http://www.flickr.com/photos/tags/" />
							
							// ideally we would have a term and the scheme would be a URI that we 
							// could append the tag to to resolve it. This won't always be possible,
							// so for now we don't use scheme
							
							// see http://edward.oconnor.cx/2007/02/representing-tags-in-atom

							foreach($item->payload->tags as $tag)
							{
								$category = $entry->appendChild($feed->createElement('category'));
								$category->setAttribute('term', $tag);
								//$category->setAttribute('scheme', '<some URI>');
							}
						}
						
						
						if (isset($item->payload->bibliography))
						{
							foreach($item->payload->bibliography as $bibo)
							{
								$bibliographicCitation = $entry->appendChild($feed->createElement('dcterms:bibliographicCitation'));
								$bibliographicCitation->appendChild($feed->createTextNode($bibo));
							}
						}
						
						
						/*
						if (isset($item->payload->tagids))
						{
							foreach($item->payload->tagids as $tag)
							{
								$element = $i->appendChild($feed->createElement('dc:subject'));
								$element->setAttribute('rdf:resource', $tag	);
							}
						}
						*/
							
					
					}					
				}
				break;
				
			default:
				break;
		}
			
		return $feed->saveXML();
	}
	
	//----------------------------------------------------------------------------------------------
	function Harvest()
	{
	}
	
	//----------------------------------------------------------------------------------------------
	function WriteHeader($changed = true)
	{
		if ($changed)
		{
			header("Content-type: text/xml");
			header("ETag: " . $this->etag);	
			header("Last-Modified: " . $this->last_modified);	
		}
		else
		{
			header("HTTP/1.1 304 Not Modified");
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function WriteFeed()
	{
		if ($this->CheckHeader())
		{
			$rss = $this->GetRss();
			$this->WriteHeader();
			echo $rss;	
		}
		else
		{
			$this->WriteHeader(false);
		}
		
	}
	
	//----------------------------------------------------------------------------------------------
	function CheckHeader()
	{
		$send = true;
		
		if (1)
		{
			return $send;
		}
		else
		{
			$this->GetETag();
			
			$headers = getallheaders();
			foreach ($headers as $k => $v)
			{
				switch (strtolower($k))
				{					
					case 'if-modified-since':
						if (strcasecmp($this->last_modified, $v) == 0)
						{
							$send = false;
						}
						break;
						
					case 'if-none-match':
						if (strcasecmp($this->etag, $v) == 0)
						{
							$send = false;
						}
						break;
						
					default:
						break;
				}
			}
		}
		
		return $send;
	}
					
		
	
}



/*$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&term=barcode[keyword]';
$f = new FeedMaker($url, 'Barcode Sequences');
echo $f->GetRss();*/
	
//		$this->source_html = get($this->source_url);
//		$this->ExtractItems();
		


?>