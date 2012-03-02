<?php

// Need mapping between codes, collections, and DiGIR

// Extract museum specimen code
function extract_specimen_codes($t)
{

	// Standard acronyms that have simple [Acronym] [number] specimen codes
	// (allowing for a prefix before [number]
	$acronyms = array(
		'ABTC','AMCC','AMNH','ANSP','ANWC','AMS','ANSP','ASIZB','ASU',
		'BM', 'BMNHE','BNHS','BPBM',
		'CAS','CASENT','CFBH','CMK','CWM',
		'DHMECN',
		'FMNH',
		'HKU',
		'ICN','ILPLA','INHS','IZUA',
		'JAC','JCV',
		'KFBG','KU','KUHE',
		'LACM','LSUMZ',		
		'MACN','MACN-Ict','MCP','MCNU', 'MCZ','MFA-ZV-I','MHNCI','MNCN','MHNUC','MNRJ','MPEG','MRAC','MRT','MUJ','MVUP','MVZ','MZUFV','MZUSP',
		'NHMV','NMB','NRM','NSV','NT','NTM',
		'OMNH',
		'PNGNM',
		'QCAZ','QM','QMJ',
		'RAN','RMNH','ROM',
		'SAMA','SIUC',
		'TNHC','THNHM',
		'UCR','UFMG','UMFS','UMMZ','UNT','USNM','USNMENT','USNM\sENT','UTA','UWBM',
		'WAM','WHT',
		'ZFMK','ZMA','ZMB','ZMH','ZRC','ZSI F','ZUFRJ');

	$specimens = array();
	$ids = array();
	
	// Try and match typical code [A-Z] \d+, allowing for some quirks such as
	// letter prefixes for number, and support ranges
	if (preg_match_all(
		'/
		(?<code>
		'
		. join("|", $acronyms)
		. '
		)
		\s*
		(:|_|\-)?
		(?<number>((?<prefix>(J|R|A[\.|\s]?|A\-))?[0-9]{3,}))
		
		(
			(\-|–|­|—)
			(?<end>[0-9]{2,})
		)?		
		
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->prefix = $out['prefix'][$i];
			$s->number = $out['number'][$i];
			$s->end = $out['end'][$i];
			array_push($specimens, $s);
		}
	}
	
	// Special cases -------------------------------------------------------------------------------
	// Acronyms separated by dots
	$dots = array();
	foreach ($acronyms as $a)
	{
		$s = str_split($a);
		$dots[] = join(".", $s) . '.';		
	}
	
	//print_r($dots);
	
	if (preg_match_all(
		'/
		(?<code>
		'
		. join("|", $dots)
		. '
		)
		\s*
		(:|_|\-)?
		(?<number>((?<prefix>(J|R|A[\.|\s]?|A\-))?[0-9]{3,}))
		
		(
			(\-|–|­|—)
			(?<end>[0-9]{2,})
		)?		
		
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->prefix = $out['prefix'][$i];
			$s->number = $out['number'][$i];
			$s->end = $out['end'][$i];
			array_push($specimens, $s);
		}
	}
	

	// ---------------------------------------------------------------------------------------------
	// BMNH, e.g. BMNH1947.2.26.89
	if (preg_match_all(
		'/
		(?<code>(BMNH|B\.M\.))
		\s*
		(?<number>([0-9]{2,4}(\.[0-9]+)+) )
		
		(
			(\-|–|­|—)
			(?<end>[0-9]+)
		)?		
		
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->prefix = '';
			$s->number = $out['number'][$i];
			$s->end = $out['end'][$i];
			array_push($specimens, $s);
		}
		
		//print_r($specimens);
		
	}	
	
	// ---------------------------------------------------------------------------------------------
	// BM(NH) 50.767 e.g. http://biostor.org/reference/31
	if (preg_match_all(
		'/
		(?<code>BM(\(NH\))?)
		\s*
		(?<number>([0-9]+(\.\s*[0-9]+)+) )
		
		(
			(\-|–|­|—)
			(?<end>[0-9]+)
		)?		
		
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->prefix = '';
			$s->number = preg_replace('/\s/', '', $out['number'][$i]);
			$s->end = $out['end'][$i];
			array_push($specimens, $s);
		}
		
		//print_r($specimens);
		
	}	
	
	// ---------------------------------------------------------------------------------------------
	// HZM e.g. http://biostor.org/reference/31
	if (preg_match_all(
		'/
		(?<code>HZM)
		\s*
		(?<number>([0-9]+(\.[0-9]+)) )
		
		(
			(\-|–|­|—)
			(?<end>[0-9]+)
		)?		
		
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->prefix = '';
			$s->number = preg_replace('/\s/', '', $out['number'][$i]);
			$s->end = $out['end'][$i];
			array_push($specimens, $s);
		}
		
		//print_r($specimens);
		
	}	
	
	
	// ---------------------------------------------------------------------------------------------
	// MNHN
	if (preg_match_all(
		'/
		(?<code>MNHN)
		\s*
		(?<number>([0-9]{4}\.[0-9]+) )

		(
			(\-|–|­|—)
			(?<end>[0-9]+)
		)?		

		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->prefix = '';
			$s->number = $out['number'][$i];
			$s->end = $out['end'][$i];
			array_push($specimens, $s);
		}
		
		//print_r($specimens);
		
	}	
	
	// ---------------------------------------------------------------------------------------------
	if (preg_match_all(
		'/
		(?<code>NCA|QVM|ZSM)
		\s*
		(?<number>([0-9]+(:|\/)[0-9]+))
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->number = $out['number'][$i];
			array_push($specimens, $s);
		}
		
		//print_r($specimens);
		
	}
	
	// ---------------------------------------------------------------------------------------------
	if (preg_match_all(
		'/
		(?<code>NHM)
		\s+
		(?<number>(R\.?[0-9]+))
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->number = $out['number'][$i];
			array_push($specimens, $s);
		}
		
		//print_r($specimens);
		
	}
	
	// ---------------------------------------------------------------------------------------------
	if (preg_match_all(
		'/
		(?<code>(MT|QMOR))
		[\-|:]
		(?<number>([0-9]{3,}))
		
		(
			(\-|–|­|—)
			(?<end>[0-9]+)
		)?		
		
		/x',  
		
		$t, $out, PREG_PATTERN_ORDER))
	{
		//print_r($out);
		$found = true;
		
		for ($i = 0; $i < count($out[0]); $i++)
		{
			$s = new stdClass;
			$s->code = $out['code'][$i];
			$s->prefix = '';
			$s->number = $out['number'][$i];
			$s->end = $out['end'][$i];
			array_push($specimens, $s);
		}
		
		//print_r($specimens);
		
	}	
	
	
	
	// ---------------------------------------------------------------------------------------------
	// Post process to handle lists of specimens
	foreach ($specimens as $z)
	{
		// Fix any codes that seem broken
		if ($z->code == 'USNM ENT')
		{
			$z->code = 'USNMENT';
		}
		
		$z->code = preg_replace('/([A-Z])\./', '$1', $z->code);
	
		if ($z->end == '')
		{
					
			switch ($z->code)
			{
				case 'CASENT':
					array_push($ids, $z->code . $z->number);
					break;
					
				default:
					array_push($ids, $z->code . ' ' . $z->number);
					break;
			}
		}
		else
		{
			// we've a range		
			$prefix = $z->prefix;
			$start = preg_replace("/$prefix/", "", $z->number);
						
			if ($z->code == 'BMNH' || $z->code == 'MNHN')
			{
				//
				
				$pos = strrpos($start, ".");
				
				$leading = substr($start, 0, $pos+1);
				$start = substr($start, $pos+1);
				
				$len = strlen($z->end);			
				$part = substr($start, 0, strlen($start) - $len);
				$end = $part . $z->end;
				
				//echo $start . "\n";
				//echo $leading . "\n";
		
				for ($i = $start; $i <= $end; $i++)
				{
					array_push($ids, $z->code . ' ' . $leading . $i);
				}
			}
			else
			{	
				$len = strlen($z->end);
					
				$part = substr($start, 0, strlen($start) - $len);
				$end = $part . $z->end;
				
				for ($i = $start; $i <= $end; $i++)
				{
					
					switch ($z->code)
					{
						case 'CASENT':
							array_push($ids, $z->code . $z->prefix . $i);
							break;
							
						default:
							array_push($ids, $z->code . ' ' . $z->prefix . $i);
							break;
					}
				}
			}
		}
	}
	
	$ids = array_unique($ids);
	sort($ids);
	
	return $ids;
}

if (0)
{
	
	// test code
	$samples = array();
	$failed = array();
	$specimens = array();
	
	/*array_push($samples, 'spinosa: ECUADOR: PICHINCHA: USNM 288443: RÌo Blanco. LOS RÌOS: USNM 286741≠44: RÌo Palenque.');
	
	array_push($samples, 'BMNH1947.2.26.89');
	
	array_push($samples, 'Material examined. ≠ Holotype - male, 30.3 mm SVL, WHT 5862, Hunnasgiriya (Knuckles), elevation 1,100 m (07∫23\' N, 80∫41\' E), coll. 17 Oct.2003. Paratypes - females, 35.0 mm SVL, WHT 2477, Corbett\'s Gap (Knuckles), 1,245 m (07∫ 22\' N, 80∫ 51\' E) coll. 6 Jun.1999; 33.8 mm SVL, WHT 6124, Corbett\'s Gap (Knuckles), 1,245 m (07∫ 22\' N, 80∫ 51\' E) coll. 16 Jun.2004; males, 30.3 mm SVL, WHT 5868, Hunnasgiriya, same data as holotype, coll. 16 Oct.2003; 31.3 mm');
	*/
	/*
	array_push($samples,'Gephyromantis runewsweeki, spec. nov. Figs 1-4, 6
	Types. Holotype: ZSM 49/2005, collected by M. Vences, I. De la Riva, E. and T. Rajearison on 25 January 2004 at the top of Maharira mountain, Ranomafana National Park, south-eastern Madagascar (21∞20.053\' S, 47∞ 24.787\' E), ca. 1350 m above sea level. ≠ Paratype: MNCN 42085, adult male with same collecting data as holotype.');
	
	array_push($samples,'Figure 57. L. orarius sp. nov., male paratype ex QVM 23:17693.
	Mesal (left) and anterior (right) views of right gonopod telopodite.
	Dashed lines indicate course of prostatic groove; scale bar = 0.25 mm.
	Figure 59. L. otwayensis sp. nov., male paratype, NMV K-9619. Mesal');
	
	array_push($samples,'FIGURES 1≠6. Adults and male genitalia. 1, Schinia immaculata, male, Arizona, Coconino Co.
	Colorado River, Grand Canyon, river mile 166.5 L, USNMENT 00229965; 2, S biundulata, female,
	Nevada, Humboldt Co. Sulphur, USNMENT 00220807; 3, S. immaculata, male genitalia; 4, S.
	immaculata, aedoeagus; 5, S. biundulata, male genitalia; 6, S. biundulata, aedoeagus.
	Material Examined. PARATYPES (3∞): U.S.A.: ARIZONA: COCONINO CO. 1∞
	same data as holotype except: USNM ENT 00210120 (NAU); river mile 166.5 L, old high
	water, 36.2542 N, 112.8996 W, 14 Apr. 2003 (1∞), R. J. Delph, USNM ENT 00219965
	(USNM); river mile 202 R, new high water, 36.0526 N, 113.3489 W, 15 May 2001 (1∞), J.
	Rundall, USNM ENT 00210119 (NAU). Paratypes deposited in the National Museum of
	Natural History, Washington, DC (USNM) and Northern Arizona University, Flagstaff,
	AZ (NAU).');
	
	*/
	// exmaples
	
	/*array_push($samples, 'WHT 5868');
	array_push($samples, 'BMNH1947.2.26.89');
	array_push($samples, 'ZSM 49/2005');
	array_push($samples, 'MNCN 42085');
	array_push($samples, 'USNM ENT 00210120');
	array_push($samples, 'MCZ A-119850');
	array_push($samples, 'SAMA R37834');
	array_push($samples, 'NT R.18657');
	array_push($samples, 'QVM 23:16172');
	array_push($samples, 'WAM R166250'); */
	//array_push($samples, 'LSUMZ 81921–7');
	//array_push($samples, 'LSUMZ 81921–7');
	//array_push($samples, 'MNHN 2000.612-23');
	array_push($samples,'BMNH 1933.9.10.9–11');
	array_push($samples, 'AMS R 93465');
	array_push($samples, 'SAMAR20583');
	array_push($samples, 'TNHC63518');
	array_push($samples,'FIGURES 1≠6. Adults and male genitalia. 1, Schinia immaculata, male, Arizona, Coconino Co.
	Colorado River, Grand Canyon, river mile 166.5 L, USNMENT 00229965; 2, S biundulata, female,
	Nevada, Humboldt Co. Sulphur, USNMENT 00220807; 3, S. immaculata, male genitalia; 4, S.
	immaculata, aedoeagus; 5, S. biundulata, male genitalia; 6, S. biundulata, aedoeagus.
	Material Examined. PARATYPES (3∞): U.S.A.: ARIZONA: COCONINO CO. 1∞
	same data as holotype except: USNM ENT 00210120 (NAU); river mile 166.5 L, old high
	water, 36.2542 N, 112.8996 W, 14 Apr. 2003 (1∞), R. J. Delph, USNM ENT 00219965
	(USNM); river mile 202 R, new high water, 36.0526 N, 113.3489 W, 15 May 2001 (1∞), J.
	Rundall, USNM ENT 00210119 (NAU). Paratypes deposited in the National Museum of
	Natural History, Washington, DC (USNM) and Northern Arizona University, Flagstaff,
AZ (NAU).');

//array_push($samples, 'Material examined. ≠ Holotype - male, 30.3 mm SVL, WHT 5862, Hunnasgiriya (Knuckles), elevation 1,100 m (07∫23\' N, 80∫41\' E), coll. 17 Oct.2003. Paratypes - females, 35.0 mm SVL, WHT 2477, Corbett\'s Gap (Knuckles), 1,245 m (07∫ 22\' N, 80∫ 51\' E) coll. 6 Jun.1999; 33.8 mm SVL, WHT 6124, Corbett\'s Gap (Knuckles), 1,245 m (07∫ 22\' N, 80∫ 51\' E) coll. 16 Jun.2004; males, 30.3 mm SVL, WHT 5868, Hunnasgiriya, same data as holotype, coll. 16 Oct.2003; 31.3 mm');

	$samples = array();

	//$samples[] ="SÃO PAULO: 1. Teodoro Sampaio, (−22.52, −52.17), MZUSP 8885, 25819; 2. Estação Biológica de Boracéia, Salesópolis, (−23.65, −45.9), USNM 460569; 3. Parque Estadual da Serra do Mar, Núcleo Santa Virgínia, 10 km NW Ubatuba, (−23.36, −45.13), 850 m, NSV 160599. PARANÁ: 4. Parque Barigüi, Bairro Mercês, Curitiba, (−25.42, −49.30), 861 m, MHNCI 2599. SANTA CATARINA: 5. Ilha de Santa Catarina, (−27.6, −48.5), BMNH 50.7.8.24, 50.7.8.25, 7.1.1.174; 6. Serra do Tabuleiro, (−27.83, −48.78), JCV 28. RIO GRANDE DO SUL: 7. Parque Nacional dos Aparados da Serra, Cambará do Sul, (−29.25, −49.83), 800 m, MCNU 829; 8. Aratiba, (−27.27, −52.32), 420 m, MZUSP 33474 (holotype), MZUSP 33475, MCNU 826, 827, 831, 833–838, 840 (paratypes), MCNU 829. UNKNOWN LOCALITY: probably from the state of Minas Gerais, UFMG 3015.";
	
	$samples[] = "We employed DNA barcoding as a third source of standardized data for species identification. We sequenced two mitochondrial DNA barcode markers for amphibians, the 5’ end of the cytochrome oxidase I (COI) gene and a fragment of the ribosomal 16S gene, using published primers and protocols (Vences et al. 2005; Smith et al. 2008; Crawford et al. 2010). GenBank accession numbers for each gene (COI, 16S) for each Panamanian specimen are as follows: MVUP 2042 (JF769001, JF769004) and AJC 2067 (JF769000, JF769003). We also obtained sequence data from one E. planirostris from Havana, Cuba, deposited in the Museum of Natural History “Felipe Poey”, Havana, with specimen number MFP.11512 (JF769002, JF769005). Gene sequences and metadata were also deposited at Barcode of Life Data Systems (Ratnasingham & Hebert 2007) under project code “BSINV”. Species identification utilized character-based phylogenetic inference and genetic distances (Goldstein & DeSalle 2011), as well as qualitative observations of morphology and advertisement call.
We compared the 16S DNA data with 16 closely related sequences (Frost et al. 2006; Heinicke et al. 2007) from GenBank (Fig. 1). Note, specimen USNM 564984 is currently identified as P. casparii in GenBank EF493599, but was re-identified as P. planirostris in Heinicke et al. (2011). Excluding gapped sites, the alignment contained 518 base pairs (bp), of which 57 were parsimony-informative and 37 were singletons. Phylogenetic analysis of 16S data followed protocols in Crawford et al. (2010). Parsimony inference resulted in 4 shortest trees of 148 steps (not shown), with support measured by 2,000 boostrap pseudoreplicates. A maximum likelihood-based tree (-Ln score = 1520.37670) is shown in Fig 1.";
	$ok = 0;
	foreach ($samples as $str)
	{
		$s = extract_specimen_codes($str);
		$matched = count($s);
		
		if ($matched > 0)
		{
			$specimens = array_merge($specimens, $s);
			$ok++;
		}
		else
		{
			array_push($failed, $str);
		}
	}
	
	// report
	
	echo "--------------------------\n";
	echo count($samples) . ' samples, ' . (count($samples) - $ok) . ' failed' . "\n";
	print_r($failed);
	
	print_r($specimens);
	
	// Post process specimens
}



?>