<?php

require_once (dirname(__FILE__) . '/tree.php');

// Create SVG of tree

/**
 * @begin Draw tree and labels in SVG
 *
 * @param width Width (pixels) to draw tree + labels in
 * @param height
 * @param label_space Width (pixels) of space to draw leaf labels in
 * @param font_height Height of font to use to draw labels
 * @param default_labels Name of group of labels to show by default
 *
 */
function tree2svg($obj, $width=400, $height=400, $label_space = 150, $font_height=10, $force_height = false, $default_labels='taxa')
{
	//----------------------------------------------------------------------------------------------
	$t = new Tree();
	$t->Parse($obj->tree->newick);
	$t->BuildWeights($t->GetRoot());
	
	$tree_width 	= $width - $label_space;
	
	if (!$force_height)
	{
		// adjust height to accomodate tree
		$height = $t->GetNumLeaves() * ($font_height + $font_height/3);
		
		$inset = $font_height;
	}
	else
	{
		$inset = 0;
	}
	
	// Drawing properties
	$attr = array();
	$attr['inset']			= $inset;
	$attr['width'] 			= $tree_width;
	$attr['height'] 		= $height;
	$attr['font_height'] 	= $font_height;
	$attr['line_width'] 	= 1;
	
	// Don't draw labels (we do this afterwards)
	$attr['draw_leaf_labels'] = false;
	$attr['draw_internal_labels'] = false;
	
	$td = NULL;
	if ($t->HasBranchLengths())
	{
		$td = new PhylogramTreeDrawer($t, $attr);
	}
	else
	{
		$td = new RectangleTreeDrawer($t, $attr);
	}
	$td->CalcCoordinates();	
	
	if (!$force_height)
	{
		$port = new SVGPort('', $width, $td->max_height + $attr['font_height'] );
	}
	else
	{
		$port = new SVGPort('', $width, $height + 2);
	}
	
	
	$port->StartGroup('tree');
	$td->Draw($port);
	$port->EndGroup();
	
	// labels
	
	if ($label_space > 0)
	{
		
		$ni = new NodeIterator ($t->getRoot());
				
		// raw labels (OTUs)
		$port->StartGroup('otu', (('otu' == $default_labels) || !isset($obj->translations)) );
		
		$q = $ni->Begin();
		while ($q != NULL)
		{	
			if ($q->IsLeaf ())
			{
				$p0 = $q->GetAttribute('xy');
				$p0['x'] += $font_height/3;
				
				$text = $q->Getlabel();
				$text = str_replace("_", " ", $text);
				$action = 'onclick="node_info(\'' . htmlentities($text) .  '\');"';			
				$port->DrawText($p0, $text, $action); 
			}			
			$q = $ni->Next();
		}
		$port->EndGroup();		
		
		if ($obj->translations)
		{
			// Tree has a one or more translation tables
			foreach ($obj->translations as $k => $v)
			{			
				// Draw labels as a separate group
				$port->StartGroup($k, ($k == $default_labels ? true : false));
				
				$q = $ni->Begin();
				while ($q != NULL)
				{	
					if ($q->IsLeaf ())
					{
						$p0 = $q->GetAttribute('xy');
						$p0['x'] += $font_height/3;
										
						$label = $q->Getlabel();
						
						if (is_array($v))
						{
							if (isset($v[$label]))
							{
								$label = $v[$label];					
							}
							else
							{
								// No translation for this OTU
								$label = '[' . $label . ']';
							}				
						}
						else
						{
							if (isset($v->{$label}))
							{
								$label = $v->{$label};					
							}
							else
							{
								// No translation for this OTU
								$label = '[' . $label . ']';
							}
						}
						
						$action = 'onclick="node_info(\'' . $q->Getlabel() .  '\');"';
						
						$port->DrawText($p0, $label, $action); 
					}			
					$q = $ni->Next();
				}
				$port->EndGroup();	
			}
		}
	}
	
	$svg = $port->GetOutput();
	
	return $svg;
}

if (0)
{

// test
$json =
'{
  "tree": "((((((((219923430:0.046474,219923429:0.009145):0.037428,219923426:0.038397):0.015434,(219923419:0.022612,219923420:0.015561):0.050529):0.004828,(207366059:0.020922,207366058:0.016958):0.038734):0.003901,219923422:0.072942):0.005414,((219923443:0.038239,219923444:0.025617):0.037592,(219923423:0.056081,219923421:0.055808):0.003788):0.009743):0.001299,(219923469:0.072965,125629132:0.044638):0.012516):0.011647,(((((219923464:0.069894,((((((125628927:0.021470,219923456:0.021406):0.003083,219923455:0.021625):0.029147,219923428:0.042785):0.001234,225685777:0.037478):0.016027,((((56549933:0.003265,219923453:-0.000859):0.015462,150371743:0.009558):0.004969,219923452:0.014401):0.024398,((((((150371732:0.001735,((150371733:0,150371736:0):6.195e-05,150371735:-6.195e-05):7.410e-05):0.000580,150371734:0.001196):0.000767,(150371737:0.001274,(150371738:0,150371740:0):0.000551):0.000498):0.000905,70608555:0.003205):0.004807,150371741:0.010751):8.979e-05,150371739:0.006647):0.022090):0.012809):0.011838,219923427:0.057366):0.009364):0.004238,((219923450:0.022699,125628925:0.012519):0.048088,219923466:0.046514):0.003608):0.007025,((56549930:0.067920,219923440:0.059754):0.002384,((219923438:0.044329,219923439:0.038470):0.014514,(219923442:0.038021,(((207366060:0,207366061:0):0.001859,125628920:0.001806):0.024716,((((125628921:0.005610,207366057:0.003531):0.001354,(207366055:0.003311,207366056:0.002174):0.003225):0.011836,207366062:0.019303):0.003741,((((((207366047:0,207366048:0):0,207366049:0):0.001563,207366050:0.000272):0.002214,(207366051:0.000818,125628919:0.001017):0.000675):0.003916,207366054:0.007924):0.004138,((219923441:0.000975,207366052:-0.000975):0.000494,207366053:-0.000494):0.012373):0.010040):0.003349):0.017594):0.011029):-0.003134):0.011235):0.004149,((((219923435:0.064354,219923424:0.067340):0.002972,219923454:0.045087):0.002092,((219923460:0.027282,219923465:0.025756):0.031269,(219923462:0.017555,219923425:-0.009591):0.047358):0.006198):0.004242,(((219923463:0.031885,(219923459:0.000452,219923458:-0.000452):0.029292):0.005200,225685776:0.024691):0.020131,219923461:0.042563):0.004673):0.009128):0.001452,((56549934:0.088142,56549929:0.066475):0.004212,(219923437:0.048313,219923436:0.044997):0.014553):0.008927):0);",
  "translations": {
    "accession": {
      "56549929": "AY803560",
      "56549930": "AY803561",
      "56549933": "AY803564",
      "56549934": "AY803565",
      "70608555": "DQ062739",
      "125628919": "AM234642",
      "125628920": "AM234643",
      "125628921": "AM234644",
      "125628925": "AM234648",
      "125628927": "AM234650",
      "125629132": "AM234651",
      "150371732": "AB265227",
      "150371733": "AB265228",
      "150371734": "AB265229",
      "150371735": "AB265230",
      "150371736": "AB265231",
      "150371737": "AB265232",
      "150371738": "AB265233",
      "150371739": "AB265234",
      "150371740": "AB265235",
      "150371741": "AB265236",
      "150371743": "AB265238",
      "207366047": "AM292907",
      "207366048": "AM292908",
      "207366049": "AM292909",
      "207366050": "AM292910",
      "207366051": "AM292911",
      "207366052": "AM292912",
      "207366053": "AM292913",
      "207366054": "AM292914",
      "207366055": "AM292915",
      "207366056": "AM292916",
      "207366057": "AM292917",
      "207366058": "AM292918",
      "207366059": "AM292919",
      "207366060": "AM292920",
      "207366061": "AM292921",
      "207366062": "AM292922",
      "219923419": "FM180122",
      "219923420": "FM180123",
      "219923421": "FM180124",
      "219923422": "FM180125",
      "219923423": "FM180126",
      "219923424": "FM180127",
      "219923425": "FM180128",
      "219923426": "FM180129",
      "219923427": "FM180130",
      "219923428": "FM180131",
      "219923429": "FM180132",
      "219923430": "FM180133",
      "219923435": "FM180138",
      "219923436": "FM180139",
      "219923437": "FM180140",
      "219923438": "FM180141",
      "219923439": "FM180142",
      "219923440": "FM180143",
      "219923441": "FM180144",
      "219923442": "FM180145",
      "219923443": "FM180146",
      "219923444": "FM180147",
      "219923450": "FM180153",
      "219923452": "FM180155",
      "219923453": "FM180156",
      "219923454": "FM180157",
      "219923455": "FM180158",
      "219923456": "FM180159",
      "219923458": "FM180161",
      "219923459": "FM180162",
      "219923460": "FM180163",
      "219923461": "FM180164",
      "219923462": "FM180165",
      "219923463": "FM180166",
      "219923464": "FM180167",
      "219923465": "FM180168",
      "219923466": "FM180169",
      "219923469": "FM180172",
      "225685776": "AB428517",
      "225685777": "AB428518"
    },
    "taxa": {
      "56549929": "Ceylonthelphusa rugosa",
      "56549930": "Parathelphusa sp. SAD-2004",
      "56549933": "Sayamia sexpunctata",
      "56549934": "Phricothelphusa limula",
      "70608555": "Somanniathelphusa sinensis",
      "125628919": "Nautilothelphusa zimmeri",
      "125628920": "Syntripsa matannensis",
      "125628921": "Parathelphusa sarasinorum",
      "125628925": "Salangathelphusa brevicarinata",
      "125628927": "Siamthelphusa holthuisi",
      "125629132": "Sundathelphusa minahassae",
      "150371732": "Somanniathelphusa taiwanensis",
      "150371733": "Somanniathelphusa taiwanensis",
      "150371734": "Somanniathelphusa taiwanensis",
      "150371735": "Somanniathelphusa amoyensis",
      "150371736": "Somanniathelphusa zhangpuensis",
      "150371737": "Somanniathelphusa zanklon",
      "150371738": "Somanniathelphusa zanklon",
      "150371739": "Somanniathelphusa zanklon",
      "150371740": "Somanniathelphusa zanklon",
      "150371741": "Somanniathelphusa qiongshanensis",
      "150371743": "Sayamia cf. germaini HS-2006",
      "207366047": "Nautilothelphusa zimmeri",
      "207366048": "Nautilothelphusa zimmeri",
      "207366049": "Parathelphusa ferruginea",
      "207366050": "Parathelphusa ferruginea",
      "207366051": "Parathelphusa ferruginea",
      "207366052": "Parathelphusa pantherina",
      "207366053": "Parathelphusa pantherina",
      "207366054": "Parathelphusa pallida",
      "207366055": "Parathelphusa possoensis",
      "207366056": "Parathelphusa possoensis",
      "207366057": "Migmathelphusa olivacea",
      "207366058": "Sundathelphusa molluscivora",
      "207366059": "Sundathelphusa sp. ZRC 2000.1684",
      "207366060": "Syntripsa flavichela",
      "207366061": "Syntripsa flavichela",
      "207366062": "Parathelphusa celebensis",
      "219923419": "Austrothelphusa transversa",
      "219923420": "Austrothelphusa sp. SK-2008",
      "219923421": "Bakousa sarawakensis",
      "219923422": "Balssiathelphusa natunaensis",
      "219923423": "Balssiathelphusa cursor",
      "219923424": "Ceylonthelphusa kandambyi",
      "219923425": "Currothelphusa asserpes",
      "219923426": "Geelvinkia holthuisi",
      "219923427": "Geithusa pulcher",
      "219923428": "Heterothelphusa fatum",
      "219923429": "Holthuisana biroi",
      "219923430": "Holthuisana festiva",
      "219923435": "Niasathelphusa wirzi",
      "219923436": "Oziothelphusa ceylonensis",
      "219923437": "Oziothelphusa sp. SK-2008",
      "219923438": "Parathelphusa convexa",
      "219923439": "Parathelphusa maculata",
      "219923440": "Parathelphusa oxygona",
      "219923441": "Parathelphusa pantherina",
      "219923442": "Parathelphusa sarawakensis",
      "219923443": "Perithelphusa borneensis",
      "219923444": "Perithelphusa lehi",
      "219923450": "Salangathelphusa brevicarinata",
      "219923452": "Sayamia bangkokensis",
      "219923453": "Sayamia sexpunctata",
      "219923454": "Sendleria gloriosa",
      "219923455": "Siamthelphusa improvisa",
      "219923456": "Siamthelphusa sp. SK-2008",
      "219923458": "Sundathelphusa boex",
      "219923459": "Sundathelphusa cavernicola",
      "219923460": "Sundathelphusa celer",
      "219923461": "Sundathelphusa hades",
      "219923462": "Sundathelphusa halmaherensis",
      "219923463": "Sundathelphusa picta",
      "219923464": "Sundathelphusa rubra",
      "219923465": "Sundathelphusa sutteri",
      "219923466": "Sundathelphusa tenebrosa",
      "219923469": "Terrathelphusa kuhli",
      "225685776": "Sundathelphusa philippina",
      "225685777": "Heterothelphusa cf. beauvoisi HS-2008"
    },
    "tax_id": {
      "56549929": 304502,
      "56549930": 304505,
      "56549933": 304507,
      "56549934": 304509,
      "70608555": 331414,
      "125628919": 375101,
      "125628920": 375102,
      "125628921": 375103,
      "125628925": 375105,
      "125628927": 375107,
      "125629132": 375109,
      "150371732": 393269,
      "150371733": 393269,
      "150371734": 393269,
      "150371735": 393270,
      "150371736": 393271,
      "150371737": 393272,
      "150371738": 393272,
      "150371739": 393272,
      "150371740": 393272,
      "150371741": 393273,
      "150371743": 393275,
      "207366047": 375101,
      "207366048": 375101,
      "207366049": 395363,
      "207366050": 395363,
      "207366051": 395363,
      "207366052": 395364,
      "207366053": 395364,
      "207366054": 395365,
      "207366055": 395366,
      "207366056": 395366,
      "207366057": 395360,
      "207366058": 395367,
      "207366059": 395368,
      "207366060": 395362,
      "207366061": 395362,
      "207366062": 395369,
      "219923419": 341133,
      "219923420": 545680,
      "219923421": 545725,
      "219923422": 547108,
      "219923423": 545727,
      "219923424": 303074,
      "219923425": 545729,
      "219923426": 545723,
      "219923427": 547116,
      "219923428": 545700,
      "219923429": 545701,
      "219923430": 545702,
      "219923435": 545738,
      "219923436": 303047,
      "219923437": 545682,
      "219923438": 545703,
      "219923439": 304504,
      "219923440": 545704,
      "219923441": 395364,
      "219923442": 545705,
      "219923443": 545740,
      "219923444": 545741,
      "219923450": 375105,
      "219923452": 545711,
      "219923453": 304507,
      "219923454": 545743,
      "219923455": 545712,
      "219923456": 545683,
      "219923458": 545713,
      "219923459": 545714,
      "219923460": 547109,
      "219923461": 545715,
      "219923462": 547110,
      "219923463": 545716,
      "219923464": 545717,
      "219923465": 547111,
      "219923466": 545718,
      "219923469": 545745,
      "225685776": 511342,
      "225685777": 511305
    }
  }
}';

$json = '{
	"tree" : "((((GU358614:0.006978,GU358613:0.011286):0.022177,(((GU358611:0.007010,GU358612:0.007067):0.012374,GU358615:0.022095):0.008527,GU358627:0.024621):0.006002):0.033382,((DQ378043:0.006414,DQ378042:0.007678):0.025235,((DQ378044:0.002023,DQ378045:0.002287):0.024879,GU358629:0.030914):0.017516):0.029856):0.022068,((((((GU594648:0.000167,DQ378048:-0.000167):0.000829,JN039368:0.001290):0.000356,DQ378049:0.000426):0.026145,DQ378047:0.052018):0.014832,((GU358617:0,GU969172:0):0.034185,(DQ378052:0.015554,GU358616:0.008152):0.026240):0.013642):0.012873,(((DQ378053:0,AY781424:0):0.016258,GU358621:0.016287):0.027042,(DQ378050:0.030756,GU358622:0.028337):0.002880):0.010477):0);",
	"translations" : {
			"definition" : {
				"GU594648" : "Hyperiidea sp. AC-2010 18S ribosomal RNA gene, partial sequence",
				"DQ378049" : "Parathemisto compressa 18S ribosomal RNA gene, complete sequence",
				"JN039368" : "Themisto libellula isolate TN2 18S ribosomal RNA gene, partial sequence",
				"DQ378048" : "Themisto compressa 18S ribosomal RNA gene, complete sequence",
				"DQ378047" : "Hyperia macrocephala 18S ribosomal RNA gene, partial sequence",
				"GU358617" : "Hyperietta sibaginis isolate HYSI141 18S ribosomal RNA gene, partial sequence",
				"GU969172" : "Hyperioides sibaginis 18S ribosomal RNA gene, partial sequence",
				"DQ378050" : "Primno macropa 18S ribosomal RNA gene, complete sequence",
				"DQ378052" : "Eupronoe minuta 18S ribosomal RNA gene, complete sequence",
				"GU358616" : "Hyperietta vosseleri isolate HYVO027 18S ribosomal RNA gene, partial sequence",
				"GU358622" : "Phrosina semilunata isolate PHSENH 18S ribosomal RNA gene, partial sequence",
				"DQ378053" : "Phronima sp. UE-2006 18S ribosomal RNA gene, complete sequence",
				"AY781424" : "Phronima sp. TS-2005 small subunit ribosomal RNA gene, complete sequence",
				"GU358614" : "Eupronoe intermedia isolate EUIN134 18S ribosomal RNA gene, partial sequence",
				"DQ378043" : "Cyllopus magellanicus 18S ribosomal RNA gene, complete sequence",
				"GU358627" : "Tetrathyrus forcipatus isolate TEFONH 18S ribosomal RNA gene, partial sequence",
				"GU358621" : "Phronimella elongata isolate PHEL023 18S ribosomal RNA gene, partial sequence",
				"DQ378042" : "Cyllopus lucasi 18S ribosomal RNA gene, partial sequence",
				"GU358611" : "Amphithyrus bispinosus isolate AMBI147 18S ribosomal RNA gene, partial sequence",
				"GU358615" : "Hemityphis tenuimanus isolate HETE147 18S ribosomal RNA gene, partial sequence",
				"GU358629" : "Vibilia armata isolate VIAR024 18S ribosomal RNA gene, partial sequence",
				"GU358613" : "Eupronoe intermedia isolate EUIN023 18S ribosomal RNA gene, partial sequence",
				"DQ378044" : "Vibilia antarctica 18S ribosomal RNA gene, complete sequence",
				"DQ378045" : "Vibilia sp. UE-2006 18S ribosomal RNA gene, complete sequence",
				"GU358612" : "Amphithyrus muratus isolate AMMU134 18S ribosomal RNA gene, partial sequence"
			}
		}
	}';
	

$obj = json_decode($json);


$svg = tree2svg($obj, 600, 400, 300, 10, false, 'definition' );
header('Content-type: image/svg+xml');
echo $svg;
}

?>