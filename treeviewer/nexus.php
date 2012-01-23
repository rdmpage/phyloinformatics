<?php

// Very basic NEXUS parser

define(NEXUSPunctuation, "()[]{}/\\,;:=*'\"`+-");
define(NEXUSWhiteSpace, "\n\r\t ");

//--------------------------------------------------------------------------------------------------
class TokenTypes
{
	const None 			= 0;
	const String 		= 1;
	const Hash 			= 2;
	const Number 		= 3;
	const SemiColon 	= 4;
	const OpenPar		= 5;
	const ClosePar 		= 6;
	const Equals 		= 7;
	const Space 		= 8;
	const Comma  		= 9;
	const Asterix 		= 10;
	const Colon 		= 11;
	const Other 		= 12;
	const Bad 			= 13;
	const Minus 		= 14;
	const DoubleQuote 	= 15;
	const Period 		= 16;
	const Backslash 	= 17;
	const QuotedString	= 18;
}

//--------------------------------------------------------------------------------------------------
class NumberTokens
{
	const start 		= 0;
	const sign 			= 1;
	const digit 		= 2;
	const fraction 		= 3;
	const expsymbol 	= 4;
	const expsign 		= 5;
	const exponent 		= 6;
	const bad 			= 7;
	const done 			= 8;
}

//--------------------------------------------------------------------------------------------------
class NexusError
{
	const ok 			= 0;
	const nobegin 		= 1;
	const noend 		= 2;
	const syntax 		= 3;
	const badcommand 	= 4;
	const noblockname 	= 5;
	const badblock	 	= 6;
	const nosemicolon	= 7;
}

//--------------------------------------------------------------------------------------------------
class Scanner
{
	public $error = 0;
	public $comment = '';
	public $pos = 0;
	public $str = '';
	public $token = TokenTypes::None;
	public $buffer = '';
	
	//----------------------------------------------------------------------------------------------
	function __construct($str)
	{
		$this->str = $str;
	}

	//----------------------------------------------------------------------------------------------
	function GetToken($returnspace = false)
	{		
		$this->token = TokenTypes::None;
		while (($this->token == TokenTypes::None) && ($this->pos < strlen($this->str)))
		{
			//echo "+" . $this->str{$this->pos} . "\n";
			if (strchr(NEXUSWhiteSpace, $this->str{$this->pos}))
			{
				if ($returnspace && ($this->str{$this->pos} == ' '))
				{
					$this->token = TokenTypes::Space;
				}
			}
			else
			{
				if (strchr (NEXUSPunctuation, $this->str{$this->pos}))
				{
					$this->buffer = $this->str{$this->pos};
					//echo "-" . $this->str{$this->pos} . "\n";
 					switch ($this->str{$this->pos})
 					{
 						case '[':
 							$this->ParseComment();
 							break;
 						case "'":
 							if ($this->ParseString())
 							{
 								$this->token = TokenTypes::QuotedString;
 							}
 							else
 							{
 								$this->token = TokenTypes::Bad;
 							}
 							break;
						case '(':
							$this->token = TokenTypes::OpenPar;
							break;
						case ')':
							$this->token = TokenTypes::ClosePar;
							break;
						case '=':
							$this->token = TokenTypes::Equals;
							break;
						case ';':
							$this->token = TokenTypes::SemiColon;
							break;
						case ',':
							$this->token = TokenTypes::Comma;
							break;
						case '*':
							$this->token = TokenTypes::Asterix;
							break;
						case ':':
							$this->token = TokenTypes::Colon;
							break;
						case '-':
							$this->token = TokenTypes::Minus;
							break;
						case '"':
							$this->token = TokenTypes::DoubleQuote;
							break;
					   	case '/':
							$this->token = TokenTypes::BackSlash;
							break;
						default:
							$this->token = TokenTypes::Other;
							break;
					}
				}
				else
				{
					if ($this->str{$this->pos} == '#')
					{
						$this->token = TokenTypes::Hash;

					}
					else if ($this->str{$this->pos} == '.')
					{
						$this->token = TokenTypes::Period;
					}
					else
					{
						if (is_numeric($this->str{$this->pos}))
						{
							if ($this->ParseNumber())
							{
								$this->token = TokenTypes::Number;
							}
							else
							{
								$this->token = TokenTypes::Bad;
							}
						}
						else
						{
							if ($this->ParseToken())
							{
								$this->token = TokenTypes::String;
							}
							else
							{
								$this->token = TokenTypes::Bad;
							}
						}
					}
				}
			}
			$this->pos++;			

		}
		return $this->token;
	}
	
	
	//----------------------------------------------------------------------------------------------
	function ParseComment()
	{
		$this->buffer = '';
		
		while (($this->str{$this->pos} != ']') && ($this->pos < strlen($this->str)))
		{
			$this->buffer .= $this->str{$this->pos};
			$this->pos++;
		}
		$this->buffer .= $this->str{$this->pos};
		$this->pos++;
	}

	//----------------------------------------------------------------------------------------------
	function ParseNumber()
	{
		$this->buffer = '';
		$state = NumberTokens::start;
		
		while (
			($this->pos < strlen($this->str))
			&& (!strchr (NEXUSWhiteSpace, $this->str{$this->pos}))
			&& (!strchr (NEXUSPunctuation, $this->str{$this->pos}))
			&& ($this->str{$this->pos} != '-')
			&& ($state != NumberTokens::bad)
			&& ($state != NumberTokens::done)
			)
		{
			if (is_numeric($this->str{$this->pos}))
			{
				switch ($state)
				{
					case NumberTokens::start:
					case NumberTokens::sign:
						$state =  NumberTokens::digit;
						break;
					case NumberTokens::expsymbol:
					case NumberTokens::expsign:
						$state =  NumberTokens::exponent;
						break;
					default:
						break;
				}
			}
			else if (($this->str{$this->pos} == '-') && ($this->str{$this->pos} == '+'))
			{
				switch ($state)
				{
					case NumberTokens::start:
						$state = NumberTokens::sign;
						break;
					case NumberTokens::digit:
						$state = NumberTokens::done;
						break;
					case NumberTokens::expsymbol:
						$state = NumberTokens::expsign;
						break;
					default:
						$state = NumberTokens::bad;
						break;
				}
			}
			else if (($this->str{$this->pos} == '.') && ($state == NumberTokens::digit))
			{
				$state = NumberTokens::fraction;
			}
			else if ((($this->str{$this->pos} == 'E') || ($this->str{$this->pos} == 'e')) && (($state == NumberTokens::digit) || ($state == NumberTokens::fraction)))			
			{
				$state = NumberTokens::expsymbol;
			}
			else
			{
				$state = NumberTokens::bad;
			}
			
			if (($state != NumberTokens::bad) && ($state != NumberTokens::done))
			{
				$this->buffer .= $this->str{$this->pos};
				$this->pos++;
			}
		}
		$this->pos--;
		return true; 		
	}
	
	//----------------------------------------------------------------------------------------------
	function ParseString()
	{
		//echo "ParseString\n";
		$this->buffer = '';
		
		$this->pos++;
		$parsing = true;
		
		while (
			($this->pos < strlen($this->str))
			&& $parsing
			)
		{
			//echo "--" . $this->str{$this->pos} . "\n";
			if ($this->str{$this->pos} == "'")
			{
				// ..  check if next character is "'"
				if ($this->pos < strlen($this->str) - 1)
				{
					$parsing = ($this->str{($this->pos + 1)} == "'");
				}
				
			}
			if ($parsing)
			{
				$this->buffer .= $this->str{$this->pos};
				$this->pos++;
			}
		}
		//echo "ParseString done: " . $this->buffer . "\n";
		//exit();
		return true;
	}
	

	//----------------------------------------------------------------------------------------------
	function ParseToken()
	{
		$this->buffer = '';
		
		while (
			($this->pos < strlen($this->str))
			&& (!strchr (NEXUSWhiteSpace, $this->str{$this->pos}))
			&& (!strchr (NEXUSPunctuation, $this->str{$this->pos}))
			)
		{
			$this->buffer .= $this->str{$this->pos};
			$this->pos++;
		}
		$this->pos--;
		return true;
	}
	
}

//--------------------------------------------------------------------------------------------------
class NexusReader extends Scanner
{
	public $nexusCommands = array('begin', 'dimensions', 'end', 'endblock', 'link', 'taxa', 'taxlabels', 'title', 'translate', 'tree');
	public $nexusBlocks = array('taxa', 'trees');
	
	//----------------------------------------------------------------------------------------------
	function GetBlock()
	{
		$blockname = '';
		
		//echo __LINE__ . " get block\n";
		
		$command =  $this->GetCommand();
		if ($command != 'begin')
		{
			$this->error = NexusError::nobegin;
		}
		else
		{
			// get block name
			$t = $this->GetToken();
			if ($t == TokenTypes::String)
			{
				$blockname = strtolower($this->buffer);
				$t = $this->GetToken();
				if ($t != TokenTypes::SemiColon)
				{
					$this->error = NexusError::noblockname;
				}
			}
			else
			{
				$this->error = NexusError::noblockname;
			}
			
		}
		return $blockname;
	}
	
	//----------------------------------------------------------------------------------------------
	function GetCommand()
	{
		$command = '';
		
		//echo __LINE__ . " get command\n";
		
		
		$t = $this->GetToken();
		if ($t == TokenTypes::String)
		{
			if (in_array(strtolower($this->buffer), $this->nexusCommands))
			{
				$command = strtolower($this->buffer);
			}
			else
			{
				$this->error = NexusError::badcommand;
			}
		}
		else
		{
			$this->error = NexusError::syntax;
		}
		return $command;
	}
		
	//----------------------------------------------------------------------------------------------
	function IsNexusFile()
	{
		$this->error = NexusError::ok;
		
		$nexus = false;
		$t = $this->GetToken();
		if ($t == TokenTypes::Hash)
		{
			$t = $this->GetToken();
			if ($t == TokenTypes::String)
			{
				$nexus = (strcasecmp('NEXUS', $this->buffer) == 0);
			}
		}
		return $nexus;
	}
	
	//----------------------------------------------------------------------------------------------
	function SkipCommand()
	{	
		do {
			$t = $this->GetToken();
		} while (($this->error == NexusError::ok) && ($t != TokenTypes::SemiColon));
		return $this->error;
	}

}

//--------------------------------------------------------------------------------------------------
function parse_nexus($str)
{
	$nx = new NexusReader($str);

	if ($nx->IsNexusFile()) {}; // echo "Is NEXUS file\n";
	
	
	$blockname = $nx->GetBlock();
	
	//echo "$blockname\n";
	
	$treeblock = new stdclass;
	
	
	if ($blockname == 'taxa')
	{
		$command = $nx->GetCommand();
		
		while ( 
			(($command != 'end') && ($command != 'endblock'))
			&& ($nx->error == NexusError::ok)
			)
		{		
			switch ($command)
			{
				case 'taxlabels':
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;
	
					
				default:
					//echo "Command to skip: $command\n";
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;
			}
			
			// If end command eat the semicolon
			if (($command == 'end') || ($command == 'endblock'))
			{
				$nx->GetToken();
			}
		}
		
		//echo __LINE__ . "'" . $nx->buffer . "'\n";
		$blockname = $nx->GetBlock();
					
	}
	
	//exit();
	//echo __LINE__ . "\n";
	
	if ($blockname == 'trees')
	{
		$command = $nx->GetCommand();
		
		while ( 
			(($command != 'end') && ($command != 'endblock'))
			&& ($nx->error == NexusError::ok)
			)
		{
			//echo "Command=$command\n";
			
			switch ($command)
			{
				case 'translate':
					if (!$treeblock->translations)
					{
						$treeblock->translations = new stdclass;
					}
					$treeblock->translations->translate = array();
					$curnode = 0;
					while ($nx->token != TokenTypes::SemiColon)
					{
						$t = $nx->GetToken();
						switch ($t)
						{
							case TokenTypes::Number:
								$curnode = $nx->buffer;
								break;
							case TokenTypes::String:
								$treeblock->translations->translate[$curnode] = $nx->buffer;
								break;
							default:
								break;
						}
					}		
					$command = $nx->GetCommand();
					break;
					
				case 'tree':	
					if ($command == 'tree')
					{
						$treeblock->tree = new stdclass;
						
						$t = $nx->GetToken();
						if ($t == TokenTypes::String)
						{
							$treeblock->tree->label = $nx->buffer;
						}
						$t = $nx->GetToken();
						if ($t == TokenTypes::Equals)
						{
							$treeblock->tree->newick = '';
							$t = $nx->GetToken();
							while ($t != TokenTypes::SemiColon)
							{
								if ($t == TokenTypes::QuotedString)
								{
									$s = $nx->buffer;
									$s = str_replace("'", "''", $s);
									$s = "'" . $s . "'";
									$treeblock->tree->newick .= $s;
								}
								else
								{
									$treeblock->tree->newick .= $nx->buffer;
								}
								$t = $nx->GetToken();
							}
							$treeblock->tree->newick .= ';';
						}
						
					}				
					$command = $nx->GetCommand();
					break;
	
				default:
					//echo "Command to skip: $command\n";
					$nx->SkipCommand();
					$command = $nx->GetCommand();
					break;
			}
			
			// If end command eat the semicolon
			if (($command == 'end') || ($command == 'endblock'))
			{
				$nx->GetToken();
			}
			
			
		}
	
	}
	
	//echo "Error=" . $nx->error . "\n";
	return $treeblock;
}

if (0)
{
	
$str = "#NEXUS

[!This data set was downloaded from TreeBASE, a relational database of phylogenetic knowledge. TreeBASE has been supported by the NSF, Harvard University, Yale University, SDSC and UC Davis. Please do not remove this acknowledgment from the Nexus file.


Generated on January 12, 2012; 16:54 GMT

TreeBASE (cc) 1994-2008

Study reference:
Pyron R.A., & Wiens J.J. 2011. A large-scale phylogeny of Amphibia including over
2800 species, and a revised classification of extant frogs, salamanders, and caecilians.
Molecular Phylogenetics Evolution, .

TreeBASE Study URI:  http://purl.org/phylo/treebase/phylows/study/TB2:S11742]

BEGIN TAXA;
        TITLE  Taxa;
        DIMENSIONS NTAX=2872;
        TAXLABELS
            Acanthixalus_sonjae
            Acanthixalus_spinosus
            Acris_blanchardi
            Acris_crepitans
            Acris_gryllus
            Adelophryne_gutturosa
            Adelotus_brevis
            Adenomera_andreae
            Adenomera_heyeri
            Adenomera_hylaedactyla
            Adenomus_kelaartii
            Afrixalus_delicatus
            Afrixalus_dorsalis
            Afrixalus_fornasini
            Afrixalus_knysnae
            Afrixalus_laevis
            Afrixalus_paradorsalis
            Afrixalus_stuhlmanni
            Agalychnis_annae
            Agalychnis_callidryas
            Agalychnis_litodryas
            Agalychnis_moreletii
            Agalychnis_saltator
            Agalychnis_spurrelli
            Aglyptodactylus_laticeps
            Aglyptodactylus_madagascariensis
            Albericus_laurini
            Alexteroon_obstetricans
            Allobates_brunneus
            Allobates_caeruleodactylus
            Allobates_conspicuus
            Allobates_femoralis
            Allobates_gasconi
            Allobates_juanii
            Allobates_nidicola
            Allobates_talamancae
            Allobates_trilineatus
            Allobates_undulatus
            Allobates_zaparo
            Allophryne_ruthveni
            Alsodes_australis
            Alsodes_barrioi
            Alsodes_gargola
            Alsodes_kaweshkari
            Alsodes_monticola
            Alsodes_nodosus
            Alsodes_tumultuosus
            Alsodes_vanzolinii
            Alytes_cisternasii
            Alytes_dickhilleni
            Alytes_maurus
            Alytes_muletensis
            Alytes_obstetricans
            Ambystoma_andersoni
            Ambystoma_barbouri
            Ambystoma_californiense
            Ambystoma_cingulatum
            Ambystoma_dumerilii
            Ambystoma_gracile
            Ambystoma_jeffersonianum
            Ambystoma_laterale
            Ambystoma_mabeei
            Ambystoma_macrodactylum
            Ambystoma_maculatum
            Ambystoma_mexicanum
            Ambystoma_opacum
            Ambystoma_ordinarium
            Ambystoma_talpoideum
            Ambystoma_texanum
            Ambystoma_tigrinum
            Ameerega_altamazonica
            Ameerega_bassleri
            Ameerega_bilinguis
            Ameerega_braccata
            Ameerega_cainarachi
            Ameerega_flavopicta
            Ameerega_hahneli
            Ameerega_macero
            Ameerega_parvula
            Ameerega_petersi
            Ameerega_picta
            Ameerega_pongoensis
            Ameerega_pulchripecta
            Ameerega_rubriventris
            Ameerega_silverstonei
            Ameerega_simulans
            Ameerega_smaragdina
            Ameerega_trivittata
            Amietia_angolensis
            Amietia_fuscigula
            Amietia_vertebralis
            Amnirana_albolabris
            Amnirana_galamensis
            Amnirana_lepus
            Amolops_bellulus
            Amolops_chunganensis
            Amolops_cremnobatus
            Amolops_daiyunensis
            Amolops_granulosus
            Amolops_hainanensis
            Amolops_hongkongensis
            Amolops_jinjiangensis
            Amolops_kangtingensis
            Amolops_larutensis
            Amolops_liangshanensis
            Amolops_lifanensis
            Amolops_loloensis
            Amolops_mantzorum
            Amolops_marmoratus
            Amolops_panhai
            Amolops_ricketti
            Amolops_spinapectoralis
            Amolops_torrentis
            Amolops_viridimaculatus
            Amolops_wuyiensis
            Amphiuma_means
            Amphiuma_pholeter
            Amphiuma_tridactylum
            Andrias_davidianus
            Andrias_japonicus
            Aneides_aeneus
            Aneides_ferreus
            Aneides_flavipunctatus
            Aneides_hardii
            Aneides_lugubris
            Aneides_vagrans
            Anhydrophryne_rattrayi
            Anodonthyla_boulengerii
            Anodonthyla_hutchisoni
            Anodonthyla_montana
            Anodonthyla_moramora
            Anodonthyla_nigrigularis
            Anodonthyla_rouxae
            Anomaloglossus_baeobatrachus
            Anomaloglossus_beebei
            Anomaloglossus_degranvillei
            Anomaloglossus_praderioi
            Anomaloglossus_roraima
            Anomaloglossus_stepheni
            Anomaloglossus_tepuyensis
            Anotheca_spinosa
            Ansonia_fuliginea
            Ansonia_hanitschi
            Ansonia_leptopus
            Ansonia_longidigita
            Ansonia_malayana
            Ansonia_minuta
            Ansonia_muelleri
            Ansonia_ornata
            Ansonia_platysoma
            Ansonia_spinulifer
            Aparasphenodon_brunoi
            Aphantophryne_pansa
            Aplastodiscus_albofrenatus
            Aplastodiscus_albosignatus
            Aplastodiscus_arildae
            Aplastodiscus_callipygius
            Aplastodiscus_cavicola
            Aplastodiscus_cochranae
            Aplastodiscus_eugenioi
            Aplastodiscus_leucopygius
            Aplastodiscus_perviridis
            Aplastodiscus_weygoldti
            Argenteohyla_siemersi
            Aromobates_nocturnus
            Aromobates_saltuensis
            Arthroleptella_bicolor
            Arthroleptella_drewesii
            Arthroleptella_landdrosia
            Arthroleptella_lightfooti
            Arthroleptella_subvoce
            Arthroleptella_villiersi
            Arthroleptis_adelphus
            Arthroleptis_affinis
            Arthroleptis_aureoli
            Arthroleptis_francei
            Arthroleptis_krokosua
            Arthroleptis_nikeae
            Arthroleptis_poecilonotus
            Arthroleptis_reichei
            Arthroleptis_schubotzi
            Arthroleptis_stenodactylus
            Arthroleptis_sylvaticus
            Arthroleptis_taeniatus
            Arthroleptis_tanneri
            Arthroleptis_variabilis
            Arthroleptis_wahlbergii
            Arthroleptis_xenodactyloides
            Arthroleptis_xenodactylus
            Ascaphus_montanus
            Ascaphus_truei
            Assa_darlingtoni
            Asterophrys_turpicola
            Astylosternus_batesi
            Astylosternus_diadematus
            Astylosternus_schioetzi
            Atelognathus_jeinimenensis
            Atelognathus_patagonicus
            Atelopus_bomolochos
            Atelopus_chiriquiensis
            Atelopus_flavescens
            Atelopus_franciscus
            Atelopus_halihelos
            Atelopus_ignescens
            Atelopus_longirostris
            Atelopus_peruensis
            Atelopus_pulcher
            Atelopus_seminiferus
            Atelopus_senex
            Atelopus_spumarius
            Atelopus_spurrelli
            Atelopus_varius
            Atelopus_zeteki
            Aubria_subsigillata
            Austrochaperina_derongo
            Barbourula_busuangensis
            Barbourula_kalimantanensis
            Barycholos_pulcher
            Barycholos_ternetzi
            Barygenys_exsul
            Barygenys_flavigularis
            Batrachoseps_attenuatus
            Batrachoseps_campi
            Batrachoseps_diabolicus
            Batrachoseps_gabrieli
            Batrachoseps_gavilanensis
            Batrachoseps_gregarius
            Batrachoseps_kawia
            Batrachoseps_major
            Batrachoseps_nigriventris
            Batrachoseps_pacificus
            Batrachoseps_regius
            Batrachoseps_relictus
            Batrachoseps_simatus
            Batrachoseps_wrighti
            Batrachuperus_karlschmidti
            Batrachuperus_londongensis
            Batrachuperus_pinchonii
            Batrachuperus_tibetanus
            Batrachuperus_yenyuanensis
            Batrachyla_antartandica
            Batrachyla_leptopus
            Batrachyla_taeniata
            Batrachylodes_vertebralis
            Blommersia_blommersae
            Blommersia_domerguei
            Blommersia_grandisonae
            Blommersia_kely
            Blommersia_sarotra
            Blommersia_wittei
            Boehmantis_microtympanum
            Bokermannohyla_astartea
            Bokermannohyla_circumdata
            Bokermannohyla_hylax
            Bokermannohyla_martinsi
            Bolitoglossa_adspersa
            Bolitoglossa_altamazonica
            Bolitoglossa_alvaradoi
            Bolitoglossa_biseriata
            Bolitoglossa_carri
            Bolitoglossa_celaque
            Bolitoglossa_cerroensis
            Bolitoglossa_colonnea
            Bolitoglossa_conanti
            Bolitoglossa_decora
            Bolitoglossa_diaphora
            Bolitoglossa_dofleini
            Bolitoglossa_dunni
            Bolitoglossa_engelhardti
            Bolitoglossa_epimela
            Bolitoglossa_equatoriana
            Bolitoglossa_flavimembris
            Bolitoglossa_flaviventris
            Bolitoglossa_franklini
            Bolitoglossa_gracilis
            Bolitoglossa_hartwegi
            Bolitoglossa_helmrichi
            Bolitoglossa_hermosa
            Bolitoglossa_lignicolor
            Bolitoglossa_lincolni
            Bolitoglossa_longissima
            Bolitoglossa_macrinii
            Bolitoglossa_marmorea
            Bolitoglossa_medemi
            Bolitoglossa_mexicana
            Bolitoglossa_minutula
            Bolitoglossa_mombachoensis
            Bolitoglossa_morio
            Bolitoglossa_oaxacensis
            Bolitoglossa_occidentalis
            Bolitoglossa_odonnelli
            Bolitoglossa_palmata
            Bolitoglossa_paraensis
            Bolitoglossa_peruviana
            Bolitoglossa_pesrubra
            Bolitoglossa_platydactyla
            Bolitoglossa_porrasorum
            Bolitoglossa_riletti
            Bolitoglossa_robusta
            Bolitoglossa_rostrata
            Bolitoglossa_rufescens
            Bolitoglossa_schizodactyla
            Bolitoglossa_sima
            Bolitoglossa_sooyorum
            Bolitoglossa_striatula
            Bolitoglossa_subpalmata
            Bolitoglossa_synoria
            Bolitoglossa_yucatana
            Bolitoglossa_zapoteca
            Bombina_bombina
            Bombina_fortinuptialis
            Bombina_lichuanensis
            Bombina_maxima
            Bombina_microdeladigitora
            Bombina_orientalis
            Bombina_pachypus
            Bombina_variegata
            Boophis_albilabris
            Boophis_boehmei
            Boophis_doulioti
            Boophis_goudotii
            Boophis_idae
            Boophis_luteus
            Boophis_madagascariensis
            Boophis_marojezensis
            Boophis_microtympanum
            Boophis_occidentalis
            Boophis_pauliani
            Boophis_rappiodes
            Boophis_sibilans
            Boophis_tephraeomystax
            Boophis_viridis
            Boophis_vittatus
            Boophis_xerophilus
            Boulengerula_boulengeri
            Boulengerula_taitana
            Boulengerula_uluguruensis
            Brachycephalus_ephippium
            Brachytarsophrys_feae
            Brachytarsophrys_platyparietus
            Bradytriton_silus
            Breviceps_fichus
            Breviceps_fuscus
            Breviceps_mossambicus
            Bromeliohyla_bromeliacia
            Bryophryne_cophites
            Buergeria_buergeri
            Buergeria_japonica
            Buergeria_oxycephlus
            Buergeria_robusta
            Bufo_achavali
            Bufo_alvarius
            Bufo_amatolicus
            Bufo_amboroensis
            Bufo_americanus
            Bufo_angusticeps
            Bufo_arenarum
            Bufo_arequipensis
            Bufo_arunco
            Bufo_asper
            Bufo_aspinius
            Bufo_atacamensis
            Bufo_atukoralei
            Bufo_balearicus
            Bufo_bankorensis
            Bufo_baxteri
            Bufo_beebei
            Bufo_biporcatus
            Bufo_bocourti
            Bufo_boreas
            Bufo_boulengeri
            Bufo_brauni
            Bufo_brevirostris
            Bufo_brongersmai
            Bufo_bufo
            Bufo_calamita
            Bufo_californicus
            Bufo_camerunensis
            Bufo_campbelli
            Bufo_canaliferus
            Bufo_canorus
            Bufo_castaneoticus
            Bufo_celebensis
            Bufo_chavin
            Bufo_coccifer
            Bufo_cognatus
            Bufo_coniferus
            Bufo_cophotis
            Bufo_crocus
            Bufo_crucifer
            Bufo_cryptotympanicus
            Bufo_cycladen
            Bufo_damaranus
            Bufo_dapsilis
            Bufo_debilis
            Bufo_dhufarensis
            Bufo_divergens
            Bufo_dombensis
            Bufo_empusus
            Bufo_exsul
            Bufo_fastidiosus
            Bufo_fenoulheti
            Bufo_fowleri
            Bufo_fustiger
            Bufo_galeatus
            Bufo_gargarizans
            Bufo_gariepensis
            Bufo_garmani
            Bufo_glaberrimus
            Bufo_gracilipes
            Bufo_granulosus
            Bufo_guentheri
            Bufo_gundlachi
            Bufo_guttatus
            Bufo_gutturalis
            Bufo_haematiticus
            Bufo_hemiophrys
            Bufo_himalayanus
            Bufo_hololius
            Bufo_houstonensis
            Bufo_ibarrai
            Bufo_ictericus
            Bufo_inyangae
            Bufo_japonicus
            Bufo_juxtasper
            Bufo_kisoloensis
            Bufo_koynayensis
            Bufo_latifrons
            Bufo_lemairii
            Bufo_lemur
            Bufo_limensis
            Bufo_lindneri
            Bufo_longinasus
            Bufo_luetkenii
            Bufo_macrocristatus
            Bufo_macrotis
            Bufo_maculatus
            Bufo_manu
            Bufo_margaritifer
            Bufo_marinus
            Bufo_marmoreus
            Bufo_mauritanicus
            Bufo_mazatlanensis
            Bufo_melanochlorus
            Bufo_melanostictus
            Bufo_microscaphus
            Bufo_nasicus
            Bufo_nebulifer
            Bufo_nelsoni
            Bufo_nesiotes
            Bufo_oblongus
            Bufo_occidentalis
            Bufo_ocellatus
            Bufo_pantherinus
            Bufo_pardalis
            Bufo_parietalis
            Bufo_peltocephalus
            Bufo_pewzowi
            Bufo_philippinicus
            Bufo_poeppigii
            Bufo_poweri
            Bufo_punctatus
            Bufo_quercicus
            Bufo_raddei
            Bufo_regularis
            Bufo_retiformis
            Bufo_robinsoni
            Bufo_scaber
            Bufo_schneideri
            Bufo_siculus
            Bufo_speciosus
            Bufo_spinulosus
            Bufo_steindachneri
            Bufo_stejnegeri
            Bufo_stomaticus
            Bufo_stuarti
            Bufo_tacanensis
            Bufo_taitanus
            Bufo_taladai
            Bufo_terrestris
            Bufo_tibetanus
            Bufo_torrenticola
            Bufo_tuberculatus
            Bufo_tuberospinius
            Bufo_tuberosus
            Bufo_uzunguensis
            Bufo_valliceps
            Bufo_variabilis
            Bufo_variegatus
            Bufo_vellardi
            Bufo_veraguensis
            Bufo_verrucosissimus
            Bufo_vertebralis
            Bufo_viridis
            Bufo_woodhousii
            Bufo_xeros
            Cacosternum_boettgeri
            Cacosternum_capense
            Cacosternum_nanum
            Cacosternum_platys
            Caecilia_tentaculata
            Caecilia_volcani
            Calluella_guttulata
            Callulina_kisiwamsitu
            Callulina_kreffti
            Callulops_eurydactylus
            Callulops_pullifer
            Callulops_robustus
            Callulops_slateri
            Calotriton_arnoldi
            Calotriton_asper
            Calyptocephallela_gayi
            Capensibufo_rosei
            Capensibufo_tradouwi
            Cardioglossa_elegans
            Cardioglossa_gracilis
            Cardioglossa_gratiosa
            Cardioglossa_leucomystax
            Cardioglossa_manengouba
            Cardioglossa_occidentalis
            Cardioglossa_oreas
            Cardioglossa_pulchra
            Cardioglossa_schioetzi
            Caudacaecilia_asplenia
            Celsiella_revocata
            Celsiella_vozmedianoi
            Centrolene_altitudinale
            Centrolene_antioquiense
            Centrolene_bacatum
            Centrolene_buckleyi
            Centrolene_daidaleum
            Centrolene_geckoideum
            Centrolene_grandisonae
            Centrolene_hesperium
            Centrolene_hybrida
            Centrolene_notostictum
            Centrolene_peristictum
            Centrolene_pipilatum
            Centrolene_savagei
            Centrolene_venezuelense
            Ceratobatrachus_guentheri
            Ceratophrys_cornuta
            Ceratophrys_cranwelli
            Ceratophrys_ornata
            Ceuthomantis_smaragdinus
            Chacophrys_pierottii
            Chaparana_aenea
            Chaparana_delacouri
            Chaparana_fansipani
            Chaparana_quadranus
            Chaparana_unculuanus
            Chaperina_fusca
            Charadrahyla_nephila
            Charadrahyla_taeniopus
            Chiasmocleis_hudsoni
            Chiasmocleis_shudikarensis
            Chimerella_mariaelenae
            Chioglossa_lusitanica
            Chiromantis_doriae
            Chiromantis_rufescens
            Chiromantis_vittatus
            Chiromantis_xerampelina
            Chiropterotriton_arboreus
            Chiropterotriton_chondrostega
            Chiropterotriton_cracens
            Chiropterotriton_dimidiatus
            Chiropterotriton_lavae
            Chiropterotriton_magnipes
            Chiropterotriton_multidentatus
            Chiropterotriton_orculus
            Chiropterotriton_priscus
            Chiropterotriton_terrestris
            Choerophryne_rostellifer
            Chthonerpeton_indistinctum
            Churamiti_maridadi
            Cochranella_euknemos
            Cochranella_granulosa
            Cochranella_litoralis
            Cochranella_mache
            Cochranella_nola
            Colostethus_argyrogaster
            Colostethus_fraterdanieli
            Colostethus_fugax
            Colostethus_imbricolus
            Colostethus_inguinalis
            Colostethus_latinasus
            Colostethus_panamansis
            Colostethus_pratti
            Conraua_crassipes
            Conraua_goliath
            Conraua_robusta
            Cophixalus_balbus
            Cophixalus_humicola
            Cophixalus_sphagnicola
            Cophixalus_tridactylus
            Cophyla_berara
            Cophyla_phyllodactyla
            Copiula_major
            Copiula_obsti
            Copiula_pipiens
            Corythomantis_greeningi
            Craugastor_alfredi
            Craugastor_andi
            Craugastor_angelicus
            Craugastor_augusti
            Craugastor_bocourti
            Craugastor_bransfordii
            Craugastor_crassidigitus
            Craugastor_cuaquero
            Craugastor_daryi
            Craugastor_emcelae
            Craugastor_fitzingeri
            Craugastor_fleischmanni
            Craugastor_laticeps
            Craugastor_lineatus
            Craugastor_loki
            Craugastor_longirostris
            Craugastor_megacephalus
            Craugastor_melanostictus
            Craugastor_mexicanus
            Craugastor_montanus
            Craugastor_obesus
            Craugastor_podiciferus
            Craugastor_punctariolus
            Craugastor_pygmaeus
            Craugastor_raniformis
            Craugastor_ranoides
            Craugastor_rhodopis
            Craugastor_rugulosus
            Craugastor_rupinius
            Craugastor_sandersoni
            Craugastor_sartori
            Craugastor_spatulatus
            Craugastor_stuarti
            Craugastor_tabasarae
            Craugastor_talamancae
            Craugastor_tarahumaraensis
            Craugastor_uno
            Crinia_deserticola
            Crinia_nimbus
            Crinia_parinsignifera
            Crinia_riparia
            Crinia_signifera
            Crinia_tinnula
            Crossodactylus_caramaschii
            Crossodactylus_schmidti
            Crotaphatrema_tchabalmbaboensis
            Cruziohyla_calcarifer
            Cryptobranchus_alleganiensis
            Cryptothylax_greshoffii
            Cryptotriton_alvarezdeltoroi
            Cryptotriton_nasalis
            Cryptotriton_veraepacis
            Ctenophryne_geayi
            Cycloramphus_acangatan
            Cycloramphus_boraceiensis
            Cyclorana_alboguttata
            Cyclorana_australis
            Cyclorana_brevipes
            Cyclorana_cryptotis
            Cyclorana_cultripes
            Cyclorana_longipes
            Cyclorana_maculosa
            Cyclorana_maini
            Cyclorana_manya
            Cyclorana_novaehollandiae
            Cyclorana_platycephala
            Cyclorana_vagitus
            Cyclorana_verrucosa
            Cynops_cyanurus
            Cynops_ensicauda
            Cynops_orientalis
            Cynops_orphicus
            Cynops_pyrrhogaster
            Dasypops_schirchi
            Dendrobates_amazonicus
            Dendrobates_arboreus
            Dendrobates_auratus
            Dendrobates_biolat
            Dendrobates_bombetes
            Dendrobates_captivus
            Dendrobates_castaneoticus
            Dendrobates_claudiae
            Dendrobates_duellmani
            Dendrobates_fantasticus
            Dendrobates_flavovittatus
            Dendrobates_fulguritus
            Dendrobates_galactonotus
            Dendrobates_granuliferus
            Dendrobates_histrionicus
            Dendrobates_imitator
            Dendrobates_lamasi
            Dendrobates_lehmanni
            Dendrobates_leucomelas
            Dendrobates_minutus
            Dendrobates_mysteriosus
            Dendrobates_pumilio
            Dendrobates_quinquevittatus
            Dendrobates_reticulatus
            Dendrobates_speciosus
            Dendrobates_steyermarki
            Dendrobates_sylvaticus
            Dendrobates_tinctorius
            Dendrobates_truncatus
            Dendrobates_uakarii
            Dendrobates_vanzolinii
            Dendrobates_variabilis
            Dendrobates_ventrimaculatus
            Dendrobates_vicentei
            Dendrobates_virolinensis
            Dendrophryniscus_minutus
            Dendropsophus_allenorum
            Dendropsophus_anceps
            Dendropsophus_aperomeus
            Dendropsophus_berthalutzae
            Dendropsophus_bifurcus
            Dendropsophus_bipunctatus
            Dendropsophus_branneri
            Dendropsophus_brevifrons
            Dendropsophus_carnifex
            Dendropsophus_ebraccatus
            Dendropsophus_elegans
            Dendropsophus_giesleri
            Dendropsophus_koechlini
            Dendropsophus_labialis
            Dendropsophus_leali
            Dendropsophus_leucophyllatus
            Dendropsophus_marmoratus
            Dendropsophus_microcephalus
            Dendropsophus_minusculus
            Dendropsophus_minutus
            Dendropsophus_miyatai
            Dendropsophus_nanus
            Dendropsophus_parviceps
            Dendropsophus_pelidna
            Dendropsophus_rhodopeplus
            Dendropsophus_riveroi
            Dendropsophus_robertmertensi
            Dendropsophus_rubicundulus
            Dendropsophus_sanborni
            Dendropsophus_sarayacuensis
            Dendropsophus_sartori
            Dendropsophus_schubarti
            Dendropsophus_seniculus
            Dendropsophus_triangulum
            Dendropsophus_walfordi
            Dendrotriton_rabbi
            Dermatonotus_muelleri
            Dermophis_mexicanus
            Dermophis_oaxacae
            Dermophis_parviceps
            Desmognathus_aeneus
            Desmognathus_apalachicolae
            Desmognathus_auriculatus
            Desmognathus_brimleyorum
            Desmognathus_carolinensis
            Desmognathus_conanti
            Desmognathus_folkertsi
            Desmognathus_fuscus
            Desmognathus_imitator
            Desmognathus_marmoratus
            Desmognathus_monticola
            Desmognathus_ochrophaeus
            Desmognathus_ocoee
            Desmognathus_orestes
            Desmognathus_planiceps
            Desmognathus_quadramaculatus
            Desmognathus_santeetlah
            Desmognathus_welteri
            Desmognathus_wrighti
            Diasporus_diastema
            Dicamptodon_aterrimus
            Dicamptodon_copei
            Dicamptodon_ensatus
            Dicamptodon_tenebrosus
            Didynamipus_sjostedti
            Discodeles_guppyi
            Discoglossus_galganoi
            Discoglossus_jeanneae
            Discoglossus_montalentii
            Discoglossus_pictus
            Discoglossus_sardus
            Duellmanohyla_rufioculis
            Duellmanohyla_soralia
            Dyscophus_antongilii
            Dyscophus_guineti
            Dyscophus_insularis
            Echinotriton_andersoni
            Echinotriton_chinhaiensis
            Ecnomiohyla_miliaria
            Ecnomiohyla_minera
            Ecnomiohyla_miotympanum
            Edalorhina_perezi
            Elachistocleis_ovalis
            Eleutherodactylus_abbotti
            Eleutherodactylus_acmonis
            Eleutherodactylus_albipes
            Eleutherodactylus_alcoae
            Eleutherodactylus_alticola
            Eleutherodactylus_amadeus
            Eleutherodactylus_amplinympha
            Eleutherodactylus_andrewsi
            Eleutherodactylus_antillensis
            Eleutherodactylus_apostates
            Eleutherodactylus_armstrongi
            Eleutherodactylus_atkinsi
            Eleutherodactylus_audanti
            Eleutherodactylus_auriculatoides
            Eleutherodactylus_auriculatus
            Eleutherodactylus_bakeri
            Eleutherodactylus_barlagnei
            Eleutherodactylus_bartonsmithi
            Eleutherodactylus_blairhedgesi
            Eleutherodactylus_bothroboans
            Eleutherodactylus_bresslerae
            Eleutherodactylus_brevirostris
            Eleutherodactylus_brittoni
            Eleutherodactylus_caribe
            Eleutherodactylus_casparii
            Eleutherodactylus_cavernicola
            Eleutherodactylus_chlorophenax
            Eleutherodactylus_cochranae
            Eleutherodactylus_cooki
            Eleutherodactylus_coqui
            Eleutherodactylus_corona
            Eleutherodactylus_counouspeus
            Eleutherodactylus_cubanus
            Eleutherodactylus_cundalli
            Eleutherodactylus_cuneatus
            Eleutherodactylus_darlingtoni
            Eleutherodactylus_dimidiatus
            Eleutherodactylus_dolomedes
            Eleutherodactylus_eileenae
            Eleutherodactylus_emiliae
            Eleutherodactylus_eneidae
            Eleutherodactylus_etheridgei
            Eleutherodactylus_eunaster
            Eleutherodactylus_flavescens
            Eleutherodactylus_fowleri
            Eleutherodactylus_furcyensis
            Eleutherodactylus_fuscus
            Eleutherodactylus_glamyrus
            Eleutherodactylus_glandulifer
            Eleutherodactylus_glanduliferoides
            Eleutherodactylus_glaphycompus
            Eleutherodactylus_glaucoreius
            Eleutherodactylus_goini
            Eleutherodactylus_gossei
            Eleutherodactylus_grabhami
            Eleutherodactylus_grahami
            Eleutherodactylus_greyi
            Eleutherodactylus_griphus
            Eleutherodactylus_gryllus
            Eleutherodactylus_guanahacabibes
            Eleutherodactylus_guantanamera
            Eleutherodactylus_gundlachi
            Eleutherodactylus_haitianus
            Eleutherodactylus_hedricki
            Eleutherodactylus_heminota
            Eleutherodactylus_hypostenor
            Eleutherodactylus_iberia
            Eleutherodactylus_inoptatus
            Eleutherodactylus_intermedius
            Eleutherodactylus_ionthus
            Eleutherodactylus_jamaicensis
            Eleutherodactylus_jaumei
            Eleutherodactylus_johnstonei
            Eleutherodactylus_jugans
            Eleutherodactylus_junori
            Eleutherodactylus_klinikowskii
            Eleutherodactylus_lamprotes
            Eleutherodactylus_leberi
            Eleutherodactylus_lentus
            Eleutherodactylus_leoncei
            Eleutherodactylus_limbatus
            Eleutherodactylus_locustus
            Eleutherodactylus_luteolus
            Eleutherodactylus_maestrensis
            Eleutherodactylus_mariposa
            Eleutherodactylus_marnockii
            Eleutherodactylus_martinicensis
            Eleutherodactylus_melacara
            Eleutherodactylus_minutus
            Eleutherodactylus_monensis
            Eleutherodactylus_nitidus
            Eleutherodactylus_nortoni
            Eleutherodactylus_nubicola
            Eleutherodactylus_orcutti
            Eleutherodactylus_orientalis
            Eleutherodactylus_oxyrhyncus
            Eleutherodactylus_pantoni
            Eleutherodactylus_parabates
            Eleutherodactylus_parapelates
            Eleutherodactylus_patriciae
            Eleutherodactylus_paulsoni
            Eleutherodactylus_pentasyringos
            Eleutherodactylus_pezopetrus
            Eleutherodactylus_pictissimus
            Eleutherodactylus_pinarensis
            Eleutherodactylus_pinchoni
            Eleutherodactylus_pipilans
            Eleutherodactylus_pituinus
            Eleutherodactylus_planirostris
            Eleutherodactylus_poolei
            Eleutherodactylus_portoricensis
            Eleutherodactylus_principalis
            Eleutherodactylus_probolaeus
            Eleutherodactylus_rhodesi
            Eleutherodactylus_richmondi
            Eleutherodactylus_ricordii
            Eleutherodactylus_riparius
            Eleutherodactylus_rivularis
            Eleutherodactylus_rogersi
            Eleutherodactylus_ronaldi
            Eleutherodactylus_rufifemoralis
            Eleutherodactylus_ruthae
            Eleutherodactylus_schmidti
            Eleutherodactylus_schwartzi
            Eleutherodactylus_sciagraphus
            Eleutherodactylus_sisyphodemus
            Eleutherodactylus_sommeri
            Eleutherodactylus_symingtoni
            Eleutherodactylus_thomasi
            Eleutherodactylus_thorectes
            Eleutherodactylus_toa
            Eleutherodactylus_tonyi
            Eleutherodactylus_turquinensis
            Eleutherodactylus_unicolor
            Eleutherodactylus_varians
            Eleutherodactylus_varleyi
            Eleutherodactylus_ventrilineatus
            Eleutherodactylus_weinlandi
            Eleutherodactylus_wetmorei
            Eleutherodactylus_wightmanae
            Eleutherodactylus_zeus
            Eleutherodactylus_zugi
            Engystomops_coloradorum
            Engystomops_freibergi
            Engystomops_guayaco
            Engystomops_montubio
            Engystomops_petersi
            Engystomops_pustulatus
            Engystomops_pustulosus
            Engystomops_randi
            Ensatina_eschscholtzii
            Epicrionops_marmoratus
            Epicrionops_niger
            Epipedobates_anthonyi
            Epipedobates_boulengeri
            Epipedobates_espinosai
            Epipedobates_machalilla
            Epipedobates_tricolor
            Espadarana_andina
            Espadarana_callistomma
            Espadarana_prosoblepon
            Eupemphix_nattereri
            Euphlyctis_cyanophlyctis
            Euphlyctis_ehrenbergii
            Euphlyctis_hexadactylus
            Euproctus_montanus
            Euproctus_platycephalus
            Eupsophus_calcaratus
            Eupsophus_contulmoensis
            Eupsophus_emiliopugini
            Eupsophus_insularis
            Eupsophus_migueli
            Eupsophus_nahuelbutensis
            Eupsophus_roseus
            Eupsophus_vertebralis
            Eurycea_aquatica
            Eurycea_bislineata
            Eurycea_chisholmensis
            Eurycea_cirrigera
            Eurycea_junaluska
            Eurycea_latitans
            Eurycea_longicauda
            Eurycea_lucifuga
            Eurycea_multiplicata
            Eurycea_nana
            Eurycea_naufragia
            Eurycea_neotenes
            Eurycea_pterophila
            Eurycea_quadridigitata
            Eurycea_rathbuni
            Eurycea_sosorum
            Eurycea_spelaea
            Eurycea_tonkawae
            Eurycea_tridentifera
            Eurycea_troglodytes
            Eurycea_tynerensis
            Eurycea_waterlooensis
            Eurycea_wilderae
            Exerodonta_abdivita
            Exerodonta_chimalapa
            Exerodonta_melanomma
            Exerodonta_perkinsi
            Exerodonta_smaragdina
            Exerodonta_sumichrasti
            Exerodonta_xera
            Feihyla_palpebralis
            Fejervarya_cancrivora
            Fejervarya_caperata
            Fejervarya_granosa
            Fejervarya_greenii
            Fejervarya_iskandari
            Fejervarya_kirtisinghei
            Fejervarya_kudremukhensis
            Fejervarya_limnocharis
            Fejervarya_mudduraja
            Fejervarya_orissaensis
            Fejervarya_pierrei
            Fejervarya_rufescens
            Fejervarya_sakishimensis
            Fejervarya_syhadrensis
            Fejervarya_triora
            Fejervarya_vittigera
            Flectonotus_fitzgeraldi
            Flectonotus_pygmaeus
            Gastrophryne_carolinensis
            Gastrophryne_elegans
            Gastrophryne_olivacea
            Gastrotheca_argenteovirens
            Gastrotheca_atympana
            Gastrotheca_aureomaculata
            Gastrotheca_christiani
            Gastrotheca_chrysosticta
            Gastrotheca_cornuta
            Gastrotheca_dendronastes
            Gastrotheca_dunni
            Gastrotheca_excubitor
            Gastrotheca_fissipes
            Gastrotheca_galeata
            Gastrotheca_gracilis
            Gastrotheca_griswoldi
            Gastrotheca_guentheri
            Gastrotheca_helenae
            Gastrotheca_litonedis
            Gastrotheca_longipes
            Gastrotheca_marsupiata
            Gastrotheca_monticola
            Gastrotheca_nicefori
            Gastrotheca_ochoai
            Gastrotheca_orophylax
            Gastrotheca_peruana
            Gastrotheca_plumbea
            Gastrotheca_pseustes
            Gastrotheca_psychrophila
            Gastrotheca_riobambae
            Gastrotheca_ruizi
            Gastrotheca_stictopleura
            Gastrotheca_trachyceps
            Gastrotheca_walkeri
            Gastrotheca_weinlandii
            Gastrotheca_zeugocystis
            Gegeneophis_ramaswamii
            Gegeneophis_seshachari
            Genyophryne_thomsoni
            Geocrinia_victoriana
            Geotrypetes_seraphini
            Gephyromantis_ambohitra
            Gephyromantis_asper
            Gephyromantis_azzurrae
            Gephyromantis_blanci
            Gephyromantis_boulengeri
            Gephyromantis_cornutus
            Gephyromantis_corvus
            Gephyromantis_decaryi
            Gephyromantis_eiselti
            Gephyromantis_enki
            Gephyromantis_granulatus
            Gephyromantis_horridus
            Gephyromantis_klemmeri
            Gephyromantis_leucocephalus
            Gephyromantis_leucomaculatus
            Gephyromantis_luteus
            Gephyromantis_malagasius
            Gephyromantis_moseri
            Gephyromantis_plicifer
            Gephyromantis_pseudoasper
            Gephyromantis_redimitus
            Gephyromantis_rivicola
            Gephyromantis_salegy
            Gephyromantis_sculpturatus
            Gephyromantis_silvanus
            Gephyromantis_striatus
            Gephyromantis_tandroka
            Gephyromantis_tschenki
            Gephyromantis_ventrimaculatus
            Gephyromantis_webbi
            Gephyromantis_zavona
            Ghatixalus_variabilis
            Glyphoglossus_molossus
            Gracixalus_gracilipes
            Grandisonia_alternans
            Grandisonia_brevis
            Grandisonia_larvata
            Grandisonia_sechellensis
            Guibemantis_albolineatus
            Guibemantis_bicalcaratus
            Guibemantis_depressiceps
            Guibemantis_liber
            Guibemantis_tornieri
            Gymnopis_multiplicata
            Gyrinophilus_gulolineatus
            Gyrinophilus_palleucus
            Gyrinophilus_porphyriticus
            Haddadus_binotatus
            Hadromophryne_natalensis
            Haideotriton_wallacei
            Hamptophryne_boliviana
            Heleioporus_australiacus
            Heleophryne_purcelli
            Heleophryne_regis
            Hemidactylium_scutatum
            Hemiphractus_bubalus
            Hemiphractus_helioi
            Hemiphractus_proboscideus
            Hemiphractus_scutatus
            Hemisus_marmoratus
            Herpele_squalostoma
            Heterixalus_alboguttatus
            Heterixalus_andrakata
            Heterixalus_betsileo
            Heterixalus_boettgeri
            Heterixalus_carbonei
            Heterixalus_luteostriatus
            Heterixalus_madagascariensis
            Heterixalus_punctatus
            Heterixalus_rutenbergi
            Heterixalus_tricolor
            Heterixalus_variabilis
            Hildebrandtia_ornata
            Holoaden_bradei
            Holoaden_luederwaldti
            Homo_sapiens
            Hoplobatrachus_crassus
            Hoplobatrachus_occipitalis
            Hoplobatrachus_rugulosus
            Hoplobatrachus_tigerinus
            Hoplophryne_rogersi
            Hoplophryne_uluguruensis
            Huia_cavitympanum
            Huia_masonii
            Huia_melasma
            Huia_sumatrana
            Hyalinobatrachium_aureoguttatum
            Hyalinobatrachium_bergeri
            Hyalinobatrachium_carlesvilai
            Hyalinobatrachium_chirripoi
            Hyalinobatrachium_colymbiphyllum
            Hyalinobatrachium_crurifasciatum
            Hyalinobatrachium_duranti
            Hyalinobatrachium_eccentricum
            Hyalinobatrachium_fleischmanni
            Hyalinobatrachium_fragile
            Hyalinobatrachium_iaspidiense
            Hyalinobatrachium_ibama
            Hyalinobatrachium_ignioculus
            Hyalinobatrachium_mondolfii
            Hyalinobatrachium_orientale
            Hyalinobatrachium_orocostale
            Hyalinobatrachium_pallidum
            Hyalinobatrachium_pellucidum
            Hyalinobatrachium_talamancae
            Hyalinobatrachium_tatayoi
            Hyalinobatrachium_taylori
            Hyalinobatrachium_valerioi
            Hydromantes_ambrosii
            Hydromantes_brunus
            Hydromantes_flavus
            Hydromantes_genei
            Hydromantes_imperialis
            Hydromantes_italicus
            Hydromantes_platycephalus
            Hydromantes_shastae
            Hydromantes_strinatii
            Hydromantes_supramontis
            Hyla_andersonii
            Hyla_annectans
            Hyla_arborea
            Hyla_arenicolor
            Hyla_avivoca
            Hyla_chinensis
            Hyla_chrysoscelis
            Hyla_cinerea
            Hyla_euphorbiacea
            Hyla_eximia
            Hyla_femoralis
            Hyla_gratiosa
            Hyla_immaculata
            Hyla_intermedia
            Hyla_japonica
            Hyla_meridionalis
            Hyla_molleri
            Hyla_orientalis
            Hyla_plicata
            Hyla_sarda
            Hyla_savignyi
            Hyla_squirella
            Hyla_tsinlingensis
            Hyla_versicolor
            Hyla_walkeri
            Hyla_wrightorum
            Hylarana_nicobariensis
            Hylodes_dactylocinus
            Hylodes_meridionalis
            Hylodes_ornatus
            Hylodes_perplicatus
            Hylodes_phyllodes
            Hylodes_sazimai
            Hylomantis_aspera
            Hylomantis_granulosa
            Hylomantis_hulli
            Hylomantis_lemur
            Hylophorbus_nigrinus
            Hylophorbus_picoides
            Hylophorbus_rufescens
            Hylophorbus_tetraphonus
            Hylophorbus_wondiwoi
            Hylorina_sylvatica
            Hyloscirtus_armatus
            Hyloscirtus_charazani
            Hyloscirtus_colymba
            Hyloscirtus_lascinius
            Hyloscirtus_lindae
            Hyloscirtus_pacha
            Hyloscirtus_palmeri
            Hyloscirtus_pantostictus
            Hyloscirtus_phyllognathus
            Hyloscirtus_simmonsi
            Hyloscirtus_tapichalaca
            Hyloxalus_anthracinus
            Hyloxalus_awa
            Hyloxalus_azureiventris
            Hyloxalus_bocagei
            Hyloxalus_chlorocraspedus
            Hyloxalus_delatorreae
            Hyloxalus_elachyhistus
            Hyloxalus_idiomelus
            Hyloxalus_infraguttatus
            Hyloxalus_insulatus
            Hyloxalus_leucophaeus
            Hyloxalus_maculosus
            Hyloxalus_nexipus
            Hyloxalus_pulchellus
            Hyloxalus_pulcherrimus
            Hyloxalus_sauli
            Hyloxalus_sordidatus
            Hyloxalus_subpunctatus
            Hyloxalus_sylvaticus
            Hyloxalus_toachi
            Hyloxalus_vertebralis
            Hymenochirus_boettgeri
            Hynobius_abei
            Hynobius_amjiensis
            Hynobius_arisanensis
            Hynobius_boulengeri
            Hynobius_chinensis
            Hynobius_dunni
            Hynobius_formosanus
            Hynobius_fuca
            Hynobius_glacialis
            Hynobius_guabangshanensis
            Hynobius_hidamontanus
            Hynobius_katoi
            Hynobius_kimurae
            Hynobius_leechii
            Hynobius_lichenatus
            Hynobius_maoershanensis
            Hynobius_naevius
            Hynobius_nebulosus
            Hynobius_nigrescens
            Hynobius_okiensis
            Hynobius_quelpaertensis
            Hynobius_retardatus
            Hynobius_sonani
            Hynobius_stejnegeri
            Hynobius_takedai
            Hynobius_tokyoensis
            Hynobius_tsuensis
            Hynobius_yangi
            Hynobius_yiwuensis
            Hyperolius_acuticeps
            Hyperolius_alticola
            Hyperolius_angolensis
            Hyperolius_argus
            Hyperolius_baumanni
            Hyperolius_castaneus
            Hyperolius_chlorosteus
            Hyperolius_cinnamomeoventris
            Hyperolius_concolor
            Hyperolius_cystocandicans
            Hyperolius_frontalis
            Hyperolius_fusciventris
            Hyperolius_glandicolor
            Hyperolius_guttulatus
            Hyperolius_horstockii
            Hyperolius_kivuensis
            Hyperolius_lateralis
            Hyperolius_marmoratus
            Hyperolius_molleri
            Hyperolius_montanus
            Hyperolius_mosaicus
            Hyperolius_nasutus
            Hyperolius_ocellatus
            Hyperolius_pardalis
            Hyperolius_phantasticus
            Hyperolius_picturatus
            Hyperolius_puncticulatus
            Hyperolius_pusillus
            Hyperolius_semidiscus
            Hyperolius_thomensis
            Hyperolius_torrentis
            Hyperolius_tuberculatus
            Hyperolius_tuberilinguis
            Hyperolius_viridiflavus
            Hyperolius_zonatus
            Hypodactylus_brunneus
            Hypodactylus_dolops
            Hypodactylus_elassodiscus
            Hypodactylus_peraccai
            Hypogeophis_rostratus
            Hypopachus_variolosus
            Hypsiboas_albomarginatus
            Hypsiboas_albopunctatus
            Hypsiboas_andinus
            Hypsiboas_balzani
            Hypsiboas_benitezi
            Hypsiboas_bischoffi
            Hypsiboas_boans
            Hypsiboas_caingua
            Hypsiboas_calcaratus
            Hypsiboas_cinerascens
            Hypsiboas_cordobae
            Hypsiboas_crepitans
            Hypsiboas_dentei
            Hypsiboas_ericae
            Hypsiboas_faber
            Hypsiboas_fasciatus
            Hypsiboas_geographicus
            Hypsiboas_guentheri
            Hypsiboas_heilprini
            Hypsiboas_joaquini
            Hypsiboas_lanciformis
            Hypsiboas_latistriatus
            Hypsiboas_lemai
            Hypsiboas_leptolineatus
            Hypsiboas_lundii
            Hypsiboas_marginatus
            Hypsiboas_marianitae
            Hypsiboas_microderma
            Hypsiboas_multifasciatus
            Hypsiboas_nympha
            Hypsiboas_ornatissimus
            Hypsiboas_pardalis
            Hypsiboas_pellucens
            Hypsiboas_picturatus
            Hypsiboas_polytaenius
            Hypsiboas_prasinus
            Hypsiboas_pulchellus
            Hypsiboas_raniceps
            Hypsiboas_riojanus
            Hypsiboas_roraima
            Hypsiboas_rosenbergi
            Hypsiboas_rufitelus
            Hypsiboas_semiguttatus
            Hypsiboas_semilineatus
            Hypsiboas_sibleszi
            Ichthyophis_bannanicus
            Ichthyophis_bombayensis
            Ichthyophis_glutinosus
            Ichthyophis_orthoplicatus
            Ichthyophis_tricolor
            Ichthyosaura_alpestris
            Ikakogi_tayrona
            Indirana_beddomii
            Indirana_semipalmata
            Ingerana_baluensis
            Ingerana_tenasserimensis
            Insuetophrynus_acarpicus
            Ischnocnema_guentheri
            Ischnocnema_hoehnei
            Ischnocnema_holti
            Ischnocnema_juipoca
            Ischnocnema_parva
            Isthmohyla_pseudopuma
            Isthmohyla_rivularis
            Isthmohyla_tica
            Isthmohyla_zeteki
            Itapotihyla_langsdorffii
            Ixalotriton_niger
            Ixalotriton_parvus
            Kalophrynus_baluensis
            Kalophrynus_intermedius
            Kalophrynus_pleurostigma
            Kaloula_conjuncta
            Kaloula_pulchra
            Kaloula_taprobanica
            Karsenia_koreana
            Kassina_maculata
            Kassina_senegalensis
            Kurixalus_eiffingeri
            Kurixalus_hainanus
            Kurixalus_idiootocus
            Kurixalus_jinxiuensis
            Kurixalus_odontotarsus
            Laliostoma_labrosum
            Lankanectes_corrugatus
            Laotriton_laoensis
            Lechriodus_fletcheri
            Leiopelma_archeyi
            Leiopelma_hamiltoni
            Leiopelma_hochstetteri
            Leiopelma_pakeka
            Lepidobatrachus_laevis
            Leptobrachium_ailaonicum
            Leptobrachium_banae
            Leptobrachium_boringii
            Leptobrachium_chapaense
            Leptobrachium_gunungense
            Leptobrachium_hainanense
            Leptobrachium_hasseltii
            Leptobrachium_huashen
            Leptobrachium_leishanense
            Leptobrachium_liui
            Leptobrachium_montanum
            Leptobrachium_mouhoti
            Leptobrachium_ngoclinhense
            Leptobrachium_promustache
            Leptobrachium_smithi
            Leptobrachium_xanthospilum
            Leptodactylodon_bicolor
            Leptodactylus_albilabris
            Leptodactylus_bufonius
            Leptodactylus_chaquensis
            Leptodactylus_didymus
            Leptodactylus_diedrus
            Leptodactylus_discodactylus
            Leptodactylus_elenae
            Leptodactylus_fallax
            Leptodactylus_fuscus
            Leptodactylus_gracilis
            Leptodactylus_griseigularis
            Leptodactylus_knudseni
            Leptodactylus_labyrinthicus
            Leptodactylus_leptodactyloides
            Leptodactylus_longirostris
            Leptodactylus_melanonotus
            Leptodactylus_mystaceus
            Leptodactylus_mystacinus
            Leptodactylus_notoaktites
            Leptodactylus_ocellatus
            Leptodactylus_pallidirostris
            Leptodactylus_pentadactylus
            Leptodactylus_plaumanni
            Leptodactylus_podicipinus
            Leptodactylus_rhodomystax
            Leptodactylus_rhodonotus
            Leptodactylus_riveroi
            Leptodactylus_silvanimbus
            Leptodactylus_spixi
            Leptodactylus_validus
            Leptodactylus_vastus
            Leptodactylus_wagneri
            Leptolalax_arayai
            Leptolalax_bourreti
            Leptolalax_liui
            Leptolalax_oshanensis
            Leptolalax_pelodytoides
            Leptolalax_pictus
            Leptopelis_argenteus
            Leptopelis_bocagii
            Leptopelis_brevirostris
            Leptopelis_concolor
            Leptopelis_kivuensis
            Leptopelis_modestus
            Leptopelis_natalensis
            Leptopelis_palmatus
            Leptopelis_vermiculatus
            Leptophryne_borbonica
            Limnodynastes_convexiusculus
            Limnodynastes_depressus
            Limnodynastes_dorsalis
            Limnodynastes_dumerilii
            Limnodynastes_fletcheri
            Limnodynastes_interioris
            Limnodynastes_lignarius
            Limnodynastes_peronii
            Limnodynastes_salmini
            Limnodynastes_tasmaniensis
            Limnodynastes_terraereginae
            Limnomedusa_macroglossa
            Limnonectes_acanthi
            Limnonectes_arathooni
            Limnonectes_asperatus
            Limnonectes_bannaensis
            Limnonectes_blythii
            Limnonectes_dabanus
            Limnonectes_finchi
            Limnonectes_fragilis
            Limnonectes_fujianensis
            Limnonectes_grunniens
            Limnonectes_gyldenstolpei
            Limnonectes_hascheanus
            Limnonectes_heinrichi
            Limnonectes_ibanorum
            Limnonectes_ingeri
            Limnonectes_kadarsani
            Limnonectes_kuhlii
            Limnonectes_laticeps
            Limnonectes_leporinus
            Limnonectes_leytensis
            Limnonectes_limborgi
            Limnonectes_macrocephalus
            Limnonectes_macrodon
            Limnonectes_magnus
            Limnonectes_malesianus
            Limnonectes_microdiscus
            Limnonectes_microtympanum
            Limnonectes_modestus
            Limnonectes_palavanensis
            Limnonectes_paramacrodon
            Limnonectes_parvus
            Limnonectes_poilani
            Limnonectes_shompenorum
            Limnonectes_visayanus
            Limnonectes_woodworthi
            Lineatriton_lineolus
            Lineatriton_orchileucos
            Liophryne_dentata
            Liophryne_rhododactyla
            Liophryne_schlaginhaufeni
            Lissotriton_boscai
            Lissotriton_helveticus
            Lissotriton_italicus
            Lissotriton_montandoni
            Lissotriton_vulgaris
            Lithodytes_lineatus
            Litoria_adelaidensis
            Litoria_amboinensis
            Litoria_andiirrmalin
            Litoria_angiana
            Litoria_arfakiana
            Litoria_aurea
            Litoria_barringtonensis
            Litoria_bicolor
            Litoria_booroolongensis
            Litoria_brevipalmata
            Litoria_burrowsi
            Litoria_caerulea
            Litoria_cavernicola
            Litoria_chloris
            Litoria_citropa
            Litoria_congenita
            Litoria_coplandi
            Litoria_cyclorhyncha
            Litoria_dahlii
            Litoria_darlingtoni
            Litoria_daviesae
            Litoria_dentata
            Litoria_dorsalis
            Litoria_dux
            Litoria_electrica
            Litoria_eucnemis
            Litoria_ewingii
            Litoria_exophthalmia
            Litoria_fallax
            Litoria_freycineti
            Litoria_genimaculata
            Litoria_gilleni
            Litoria_gracilenta
            Litoria_havina
            Litoria_impura
            Litoria_inermis
            Litoria_infrafrenata
            Litoria_iris
            Litoria_jervisiensis
            Litoria_jungguy
            Litoria_kumae
            Litoria_latopalmata
            Litoria_lesueurii
            Litoria_leucova
            Litoria_littlejohni
            Litoria_longirostris
            Litoria_majikthise
            Litoria_meiriana
            Litoria_microbelos
            Litoria_micromembrana
            Litoria_modica
            Litoria_moorei
            Litoria_multiplica
            Litoria_nannotis
            Litoria_nasuta
            Litoria_nigrofrenata
            Litoria_nigropunctata
            Litoria_nudidigita
            Litoria_nyakalensis
            Litoria_olongburensis
            Litoria_pallida
            Litoria_paraewingi
            Litoria_pearsoniana
            Litoria_peronii
            Litoria_personata
            Litoria_phyllochroa
            Litoria_pronimia
            Litoria_prora
            Litoria_raniformis
            Litoria_revelata
            Litoria_rheocola
            Litoria_rothii
            Litoria_rubella
            Litoria_spartacus
            Litoria_spenceri
            Litoria_splendida
            Litoria_subglandulosa
            Litoria_thesaurensis
            Litoria_tornieri
            Litoria_tyleri
            Litoria_verreauxii
            Litoria_watjulumensis
            Litoria_wilcoxii
            Litoria_wollastoni
            Litoria_xanthomera
            Liua_shihi
            Liua_tsinpaensis
            Liuixalus_romeri
            Luetkenotyphlus_brasiliensis
            Lyciasalamandra_antalyana
            Lyciasalamandra_atifi
            Lyciasalamandra_billae
            Lyciasalamandra_fazilae
            Lyciasalamandra_flavimembris
            Lyciasalamandra_helverseni
            Lyciasalamandra_luschani
            Lynchius_flavomaculatus
            Lynchius_nebulanastes
            Lynchius_parkeri
            Macrogenioglottus_alipioi
            Mannophryne_herminae
            Mannophryne_trinitatis
            Mannophryne_venezuelensis
            Mantella_aurantiaca
            Mantella_baroni
            Mantella_bernhardi
            Mantella_betsileo
            Mantella_cowanii
            Mantella_crocea
            Mantella_ebenaui
            Mantella_expectata
            Mantella_haraldmeieri
            Mantella_laevigata
            Mantella_madagascariensis
            Mantella_manery
            Mantella_milotympanum
            Mantella_nigricans
            Mantella_pulchra
            Mantella_viridis
            Mantidactylus_ambreensis
            Mantidactylus_argenteus
            Mantidactylus_biporus
            Mantidactylus_charlotteae
            Mantidactylus_femoralis
            Mantidactylus_grandidieri
            Mantidactylus_lugubris
            Mantidactylus_mocquardi
            Mantidactylus_opiparis
            Mantidactylus_ulcerosus
            Megaelosia_goeldii
            Megastomatohyla_mixe
            Megophrys_lekaguli
            Megophrys_nasuta
            Melanobatrachus_indicus
            Melanophryniscus_klappenbachi
            Melanophryniscus_rubriventris
            Melanophryniscus_stelzneri
            Meristogenys_jerboa
            Meristogenys_kinabaluensis
            Meristogenys_orphnocnemis
            Meristogenys_phaeomerus
            Meristogenys_poecilus
            Meristogenys_whiteheadi
            Mertensiella_caucasica
            Mertensophryne_micranotis
            Metacrinia_nichollsi
            Metaphrynella_sundana
            Micrixalus_fuscus
            Micrixalus_kottigeharensis
            Microbatrachella_capensis
            Microhyla_borneensis
            Microhyla_butleri
            Microhyla_fissipes
            Microhyla_heymonsi
            Microhyla_okinavensis
            Microhyla_ornata
            Microhyla_pulchra
            Microhyla_rubra
            Micryletta_inornata
            Mixophyes_balbus
            Mixophyes_carbinensis
            Mixophyes_coggeri
            Mixophyes_fasciolatus
            Mixophyes_schevilli
            Morerella_cyanophthalma
            Myersiohyla_inparquesi
            Myersiohyla_kanaima
            Myobatrachus_gouldii
            Nannophrys_ceylonensis
            Nannophrys_marmorata
            Nanorana_parkeri
            Nanorana_pleskei
            Nanorana_ventripunctata
            Nasikabatrachus_sahyadrensis
            Natalobatrachus_bonebergi
            Nectophryne_afra
            Nectophryne_batesii
            Nectophrynoides_minutus
            Nectophrynoides_tornieri
            Nectophrynoides_viviparus
            Necturus_alabamensis
            Necturus_beyeri
            Necturus_lewisi
            Necturus_maculosus
            Necturus_punctatus
            Nelsonophryne_aequatorialis
            Neobatrachus_pelobatoides
            Neobatrachus_pictus
            Neobatrachus_sudelli
            Neurergus_crocatus
            Neurergus_kaiseri
            Neurergus_microspilotus
            Neurergus_strauchii
            Noblella_lochites
            Noblella_peruviana
            Notaden_bennettii
            Notaden_melanoscaphus
            Notophthalmus_meridionalis
            Notophthalmus_perstriatus
            Notophthalmus_viridescens
            Nototriton_abscondens
            Nototriton_barbouri
            Nototriton_brodiei
            Nototriton_gamezi
            Nototriton_guanacaste
            Nototriton_lignicola
            Nototriton_limnospectator
            Nototriton_picadoi
            Nototriton_richardi
            Nyctanolis_pernix
            Nyctibates_corrugatus
            Nyctibatrachus_aliciae
            Nyctibatrachus_major
            Nyctimantis_rugiceps
            Nyctimystes_cheesmani
            Nyctimystes_dayi
            Nyctimystes_foricula
            Nyctimystes_humeralis
            Nyctimystes_kubori
            Nyctimystes_narinosus
            Nyctimystes_papua
            Nyctimystes_pulcher
            Nyctimystes_semipalmatus
            Nyctimystes_zweifeli
            Nyctixalus_pictus
            Nyctixalus_spinosus
            Nymphargus_bejaranoi
            Nymphargus_cochranae
            Nymphargus_griffithsi
            Nymphargus_megacheirus
            Nymphargus_mixomaculatus
            Nymphargus_pluvialis
            Nymphargus_posadae
            Nymphargus_puyoensis
            Nymphargus_rosadus
            Nymphargus_siren
            Nymphargus_wileyi
            Occidozyga_baluensis
            Occidozyga_borealis
            Occidozyga_laevis
            Occidozyga_lima
            Occidozyga_magnapustulosa
            Occidozyga_martensii
            Odontophrynus_achalensis
            Odontophrynus_americanus
            Odontophrynus_carvalhoi
            Odontophrynus_cultripes
            Odontophrynus_moratoi
            Odontophrynus_occidentalis
            Odorrana_absita
            Odorrana_aureola
            Odorrana_chapaensis
            Odorrana_jingdongensis
            Odorrana_junlianensis
            Odorrana_nasica
            Odorrana_schmackeri
            Odorrana_tormota
            Oedipina_alleni
            Oedipina_carablanca
            Oedipina_collaris
            Oedipina_complex
            Oedipina_cyclocauda
            Oedipina_elongata
            Oedipina_gephyra
            Oedipina_gracilis
            Oedipina_grandis
            Oedipina_kasios
            Oedipina_leptopoda
            Oedipina_maritima
            Oedipina_pacificensis
            Oedipina_parvipes
            Oedipina_poelzi
            Oedipina_pseudouniformis
            Oedipina_quadra
            Oedipina_savagei
            Oedipina_stenopodia
            Oedipina_uniformis
            Ommatotriton_ophryticus
            Ommatotriton_vittatus
            Onychodactylus_fischeri
            Onychodactylus_japonicus
            Ophryophryne_hansi
            Ophryophryne_microstoma
            Opisthothylax_immaculatus
            Oreobates_choristolemma
            Oreobates_cruralis
            Oreobates_discoidalis
            Oreobates_granulosus
            Oreobates_heterodactylus
            Oreobates_ibischi
            Oreobates_lehri
            Oreobates_madidi
            Oreobates_quixensis
            Oreobates_sanctaecrucis
            Oreobates_sanderi
            Oreobates_saxatilis
            Oreolalax_chuanbeiensis
            Oreolalax_jingdongensis
            Oreolalax_liangbeiensis
            Oreolalax_lichuanensis
            Oreolalax_major
            Oreolalax_multipunctatus
            Oreolalax_nanjiangensis
            Oreolalax_omeimontis
            Oreolalax_pingii
            Oreolalax_popei
            Oreolalax_rhodostigmatus
            Oreolalax_rugosus
            Oreolalax_schmidti
            Oreolalax_xiangchengensis
            Oreophryne_asplenicola
            Oreophryne_atrigularis
            Oreophryne_brachypus
            Oreophryne_clamata
            Oreophryne_pseudasplenicola
            Oreophryne_sibilans
            Oreophryne_unicolor
            Oreophryne_waira
            Oreophryne_wapoga
            Oscaecilia_ochrocephala
            Osornophryne_antisana
            Osornophryne_bufoniformis
            Osornophryne_guacamayo
            Osornophryne_puruanta
            Osornophryne_sumacoensis
            Osteocephalus_alboguttatus
            Osteocephalus_buckleyi
            Osteocephalus_cabrerai
            Osteocephalus_leprieurii
            Osteocephalus_mutabor
            Osteocephalus_oophagus
            Osteocephalus_planiceps
            Osteocephalus_taurinus
            Osteocephalus_verruciger
            Osteopilus_brunneus
            Osteopilus_crucialis
            Osteopilus_dominicensis
            Osteopilus_marianae
            Osteopilus_pulchrilineatus
            Osteopilus_septentrionalis
            Osteopilus_vastus
            Osteopilus_wilderi
            Otophryne_pyburni
            Oxydactyla_crassa
            Paa_arnoldi
            Paa_boulengeri
            Paa_bourreti
            Paa_chayuensis
            Paa_conaensis
            Paa_exilispinosa
            Paa_fasciculispina
            Paa_jiulongensis
            Paa_liebigii
            Paa_liui
            Paa_maculosa
            Paa_medogensis
            Paa_robertingeri
            Paa_shini
            Paa_spinosa
            Paa_taihangnicus
            Paa_verrucospinosa
            Paa_yei
            Paa_yunnanensis
            Pachyhynobius_shangchengensis
            Pachymedusa_dacnicolor
            Pachytriton_brevipes
            Pachytriton_labiatus
            Paracrinia_haswelli
            Paradactylodon_gorganensis
            Paradactylodon_mustersi
            Paradactylodon_persicus
            Paradoxophyla_palmata
            Paradoxophyla_tiarano
            Paramesotriton_caudopunctatus
            Paramesotriton_chinensis
            Paramesotriton_deloustali
            Paramesotriton_fuzhongensis
            Paramesotriton_guangxiensis
            Paramesotriton_hongkongensis
            Paramesotriton_zhijinensis
            Paratelmatobius_cardosoi
            Paratelmatobius_gaigeae
            Paratelmatobius_poecilogaster
            Parvimolge_townsendi
            Pedostibes_hosii
            Pedostibes_rugosus
            Pedostibes_tuberculosus
            Pelobates_cultripes
            Pelobates_fuscus
            Pelobates_syriacus
            Pelobates_varaldii
            Pelodytes_caucasicus
            Pelodytes_ibericus
            Pelodytes_punctatus
            Pelophryne_brevipes
            Pelophryne_misera
            Pelophryne_signata
            Petropedetes_cameronensis
            Petropedetes_martiensseni
            Petropedetes_newtoni
            Petropedetes_palmipes
            Petropedetes_parkeri
            Petropedetes_yakusini
            Phaeognathus_hubrichti
            Phasmahyla_cochranae
            Phasmahyla_cruzi
            Phasmahyla_exilis
            Phasmahyla_guttata
            Phasmahyla_jandaia
            Philautus_abditus
            Philautus_acutirostris
            Philautus_anili
            Philautus_asankai
            Philautus_aurifasciatus
            Philautus_banaensis
            Philautus_beddomii
            Philautus_bobingeri
            Philautus_bombayensis
            Philautus_carinensis
            Philautus_cavirostris
            Philautus_charius
            Philautus_decoris
            Philautus_femoralis
            Philautus_glandulosus
            Philautus_graminirupes
            Philautus_griet
            Philautus_gryllus
            Philautus_hainanus
            Philautus_hoffmanni
            Philautus_ingeri
            Philautus_leucorhinus
            Philautus_longchuanensis
            Philautus_lunatus
            Philautus_menglaensis
            Philautus_microtympanum
            Philautus_mittermeieri
            Philautus_mjobergi
            Philautus_mooreorum
            Philautus_neelanethrus
            Philautus_nerostagona
            Philautus_ocellatus
            Philautus_ocularis
            Philautus_papillosus
            Philautus_petersi
            Philautus_pleurotaenia
            Philautus_ponmudi
            Philautus_poppiae
            Philautus_popularis
            Philautus_quyeti
            Philautus_schmarda
            Philautus_signatus
            Philautus_simba
            Philautus_steineri
            Philautus_stuarti
            Philautus_surdus
            Philautus_tanu
            Philautus_tinniens
            Philautus_travancoricus
            Philautus_tuberohumerus
            Philautus_wynaadensis
            Philautus_zorro
            Philoria_sphagnicolus
            Phlyctimantis_leonardi
            Phlyctimantis_verrucosus
            Phrynobatrachus_acridoides
            Phrynobatrachus_africanus
            Phrynobatrachus_auritus
            Phrynobatrachus_calcaratus
            Phrynobatrachus_cricogaster
            Phrynobatrachus_dendrobates
            Phrynobatrachus_dispar
            Phrynobatrachus_krefftii
            Phrynobatrachus_leveleve
            Phrynobatrachus_mababiensis
            Phrynobatrachus_natalensis
            Phrynobatrachus_sandersoni
            Phrynomantis_annectens
            Phrynomantis_bifasciatus
            Phrynomantis_microps
            Phrynomedusa_marginata
            Phrynopus_barthlenae
            Phrynopus_bracki
            Phrynopus_bufoides
            Phrynopus_horstpauli
            Phrynopus_juninensis
            Phrynopus_kauneorum
            Phrynopus_pesantesi
            Phrynopus_tautzorum
            Phyllobates_aurotaenia
            Phyllobates_bicolor
            Phyllobates_lugubris
            Phyllobates_terribilis
            Phyllobates_vittatus
            Phyllodytes_auratus
            Phyllodytes_luteolus
            Phyllomedusa_araguari
            Phyllomedusa_atelopoides
            Phyllomedusa_ayeaye
            Phyllomedusa_azurea
            Phyllomedusa_bahiana
            Phyllomedusa_baltea
            Phyllomedusa_bicolor
            Phyllomedusa_boliviana
            Phyllomedusa_burmeisteri
            Phyllomedusa_camba
            Phyllomedusa_centralis
            Phyllomedusa_distincta
            Phyllomedusa_duellmani
            Phyllomedusa_hypochondrialis
            Phyllomedusa_iheringii
            Phyllomedusa_itacolomi
            Phyllomedusa_megacephala
            Phyllomedusa_neildi
            Phyllomedusa_nordestina
            Phyllomedusa_oreades
            Phyllomedusa_palliata
            Phyllomedusa_perinesos
            Phyllomedusa_rohdei
            Phyllomedusa_sauvagii
            Phyllomedusa_tarsius
            Phyllomedusa_tetraploidea
            Phyllomedusa_tomopterna
            Phyllomedusa_trinitatis
            Phyllomedusa_vaillantii
            Physalaemus_albonotatus
            Physalaemus_barrioi
            Physalaemus_biligonigerus
            Physalaemus_cuvieri
            Physalaemus_ephippifer
            Physalaemus_gracilis
            Physalaemus_riograndensis
            Physalaemus_signifer
            Phyzelaphryne_miriamae
            Pipa_carvalhoi
            Pipa_parva
            Pipa_pipa
            Platymantis_bimaculatus
            Platymantis_corrugatus
            Platymantis_cryptotis
            Platymantis_dorsalis
            Platymantis_hazelae
            Platymantis_mimulus
            Platymantis_montanus
            Platymantis_naomii
            Platymantis_papuensis
            Platymantis_pelewensis
            Platymantis_punctatus
            Platymantis_vitiensis
            Platymantis_weberi
            Platymantis_wuenscheorum
            Platypelis_barbouri
            Platypelis_grandis
            Platypelis_mavomavo
            Platypelis_milloti
            Platypelis_pollicaris
            Platypelis_tuberifera
            Platyplectrum_ornatum
            Platyplectrum_spenceri
            Plectrohyla_ameibothalame
            Plectrohyla_arborescandens
            Plectrohyla_bistincta
            Plectrohyla_calthula
            Plectrohyla_chrysopleura
            Plectrohyla_cyclada
            Plectrohyla_glandulosa
            Plectrohyla_guatemalensis
            Plectrohyla_matudai
            Plectrohyla_pentheter
            Plectrohyla_siopela
            Plethodon_albagula
            Plethodon_amplus
            Plethodon_angusticlavius
            Plethodon_asupak
            Plethodon_aureolus
            Plethodon_caddoensis
            Plethodon_chattahoochee
            Plethodon_cheoah
            Plethodon_chlorobryonis
            Plethodon_cinereus
            Plethodon_cylindraceus
            Plethodon_dorsalis
            Plethodon_dunni
            Plethodon_electromorphus
            Plethodon_elongatus
            Plethodon_fourchensis
            Plethodon_glutinosus
            Plethodon_grobmani
            Plethodon_hoffmani
            Plethodon_hubrichti
            Plethodon_idahoensis
            Plethodon_jordani
            Plethodon_kentucki
            Plethodon_kiamichi
            Plethodon_kisatchie
            Plethodon_larselli
            Plethodon_meridianus
            Plethodon_metcalfi
            Plethodon_mississippi
            Plethodon_montanus
            Plethodon_neomexicanus
            Plethodon_nettingi
            Plethodon_ocmulgee
            Plethodon_ouachitae
            Plethodon_petraeus
            Plethodon_punctatus
            Plethodon_richmondi
            Plethodon_savannah
            Plethodon_sequoyah
            Plethodon_serratus
            Plethodon_shenandoah
            Plethodon_shermani
            Plethodon_stormi
            Plethodon_teyahalee
            Plethodon_vandykei
            Plethodon_variolatus
            Plethodon_vehiculum
            Plethodon_ventralis
            Plethodon_virginia
            Plethodon_websteri
            Plethodon_wehrlei
            Plethodon_welleri
            Plethodon_yonahlossee
            Plethodontohyla_bipunctata
            Plethodontohyla_brevipes
            Plethodontohyla_fonetana
            Plethodontohyla_guentheri
            Plethodontohyla_inguinalis
            Plethodontohyla_mihanika
            Plethodontohyla_notosticta
            Plethodontohyla_ocellata
            Plethodontohyla_tuberata
            Pleurodeles_nebulosus
            Pleurodeles_poireti
            Pleurodeles_waltl
            Pleurodema_bibroni
            Pleurodema_brachyops
            Pleurodema_bufoninum
            Pleurodema_marmoratum
            Pleurodema_thaul
            Polypedates_colletti
            Polypedates_cruciger
            Polypedates_eques
            Polypedates_fastigo
            Polypedates_leucomystax
            Polypedates_maculatus
            Polypedates_megacephalus
            Polypedates_mutus
            Poyntonia_paludicola
            Praslinia_cooperi
            Pristimantis_acerus
            Pristimantis_achatinus
            Pristimantis_actites
            Pristimantis_acuminatus
            Pristimantis_altamazonicus
            Pristimantis_aniptopalmatus
            Pristimantis_appendiculatus
            Pristimantis_ardalonychus
            Pristimantis_ashkapara
            Pristimantis_bipunctatus
            Pristimantis_bisignatus
            Pristimantis_bromeliaceus
            Pristimantis_buccinator
            Pristimantis_buckleyi
            Pristimantis_cajamarcensis
            Pristimantis_calcarulatus
            Pristimantis_caprifer
            Pristimantis_caryophyllaceus
            Pristimantis_celator
            Pristimantis_ceuthospilus
            Pristimantis_chalceus
            Pristimantis_chiastonotus
            Pristimantis_chloronotus
            Pristimantis_citriogaster
            Pristimantis_colomai
            Pristimantis_condor
            Pristimantis_conspicillatus
            Pristimantis_cremnobates
            Pristimantis_crenunguis
            Pristimantis_croceoinguinis
            Pristimantis_crucifer
            Pristimantis_cruentus
            Pristimantis_cryophilius
            Pristimantis_curtipes
            Pristimantis_danae
            Pristimantis_devillei
            Pristimantis_diadematus
            Pristimantis_dissimulatus
            Pristimantis_duellmani
            Pristimantis_eriphus
            Pristimantis_euphronides
            Pristimantis_fenestratus
            Pristimantis_fraudator
            Pristimantis_galdi
            Pristimantis_gentryi
            Pristimantis_glandulosus
            Pristimantis_imitatrix
            Pristimantis_inguinalis
            Pristimantis_inusitatus
            Pristimantis_koehleri
            Pristimantis_labiosus
            Pristimantis_lanthanites
            Pristimantis_latidiscus
            Pristimantis_leoni
            Pristimantis_lirellus
            Pristimantis_llojsintuta
            Pristimantis_luteolateralis
            Pristimantis_lymani
            Pristimantis_malkini
            Pristimantis_marmoratus
            Pristimantis_melanogaster
            Pristimantis_mercedesae
            Pristimantis_nyctophylax
            Pristimantis_ockendeni
            Pristimantis_ocreatus
            Pristimantis_orcesi
            Pristimantis_orestes
            Pristimantis_parvillus
            Pristimantis_peruvianus
            Pristimantis_petrobardus
            Pristimantis_phoxocephalus
            Pristimantis_platydactylus
            Pristimantis_pluvicanorus
            Pristimantis_prolatus
            Pristimantis_pulvinatus
            Pristimantis_pycnodermis
            Pristimantis_pyrrhomerus
            Pristimantis_quaquaversus
            Pristimantis_quinquagesimus
            Pristimantis_reichlei
            Pristimantis_rhabdocnemus
            Pristimantis_rhabdolaemus
            Pristimantis_rhodoplichus
            Pristimantis_ridens
            Pristimantis_riveti
            Pristimantis_rozei
            Pristimantis_sagittulus
            Pristimantis_samaipatae
            Pristimantis_schultei
            Pristimantis_shrevei
            Pristimantis_simonbolivari
            Pristimantis_simonsii
            Pristimantis_skydmainos
            Pristimantis_spinosus
            Pristimantis_stictogaster
            Pristimantis_subsigillatus
            Pristimantis_supernatis
            Pristimantis_surdus
            Pristimantis_terraebolivaris
            Pristimantis_thymalopsoides
            Pristimantis_thymelensis
            Pristimantis_toftae
            Pristimantis_truebae
            Pristimantis_unistrigatus
            Pristimantis_urichi
            Pristimantis_verecundus
            Pristimantis_versicolor
            Pristimantis_vertebralis
            'Pristimantis w-nigrum'
            Pristimantis_walkeri
            Pristimantis_wiensi
            Pristimantis_zeuctotylus
            Probreviceps_durirostris
            Probreviceps_macrodactylus
            Probreviceps_uluguruensis
            Proceratophrys_appendiculata
            Proceratophrys_avelinoi
            Proceratophrys_bigibbosa
            Proceratophrys_boiei
            Proceratophrys_concavitympanum
            Proceratophrys_cristiceps
            Proceratophrys_cururu
            Proceratophrys_goyana
            Proceratophrys_laticeps
            Proceratophrys_melanopogon
            Proceratophrys_renalis
            Proceratophrys_schirchi
            Proteus_anguinus
            Pseudacris_brachyphona
            Pseudacris_brimleyi
            Pseudacris_cadaverina
            Pseudacris_clarkii
            Pseudacris_crucifer
            Pseudacris_feriarum
            Pseudacris_fouquettei
            Pseudacris_illinoensis
            Pseudacris_kalmi
            Pseudacris_maculata
            Pseudacris_nigrita
            Pseudacris_ocularis
            Pseudacris_ornata
            Pseudacris_regilla
            Pseudacris_streckeri
            Pseudacris_triseriata
            Pseudis_bolbodactyla
            Pseudis_caraya
            Pseudis_cardosoi
            Pseudis_fusca
            Pseudis_laevis
            Pseudis_limellum
            Pseudis_minuta
            Pseudis_paradoxa
            Pseudis_tocantins
            Pseudoamolops_sauteri
            Pseudobranchus_axanthus
            Pseudobranchus_striatus
            Pseudoeurycea_altamontana
            Pseudoeurycea_anitae
            Pseudoeurycea_bellii
            Pseudoeurycea_boneti
            Pseudoeurycea_brunnata
            Pseudoeurycea_cephalica
            Pseudoeurycea_cochranae
            Pseudoeurycea_conanti
            Pseudoeurycea_exspectata
            Pseudoeurycea_firscheini
            Pseudoeurycea_gadovii
            Pseudoeurycea_galeanae
            Pseudoeurycea_gigantea
            Pseudoeurycea_goebeli
            Pseudoeurycea_juarezi
            Pseudoeurycea_leprosa
            Pseudoeurycea_longicauda
            Pseudoeurycea_lynchi
            Pseudoeurycea_maxima
            Pseudoeurycea_melanomolga
            Pseudoeurycea_mystax
            Pseudoeurycea_naucampatepetl
            Pseudoeurycea_nigromaculata
            Pseudoeurycea_obesa
            Pseudoeurycea_papenfussi
            Pseudoeurycea_rex
            Pseudoeurycea_robertsi
            Pseudoeurycea_ruficauda
            Pseudoeurycea_saltator
            Pseudoeurycea_scandens
            Pseudoeurycea_smithi
            Pseudoeurycea_tenchalli
            Pseudoeurycea_unguidentis
            Pseudoeurycea_werleri
            Pseudohynobius_flavomaculatus
            Pseudohynobius_shuichengensis
            Pseudopaludicola_falcipes
            Pseudophryne_bibronii
            Pseudophryne_coriacea
            Pseudotriton_montanus
            Pseudotriton_ruber
            Psychrophrynella_iatamasi
            Psychrophrynella_wettsteini
            Ptychadena_aequiplicata
            Ptychadena_anchietae
            Ptychadena_bibroni
            Ptychadena_cooperi
            Ptychadena_longirostris
            Ptychadena_mahnerti
            Ptychadena_mascareniensis
            Ptychadena_newtoni
            Ptychadena_oxyrhynchus
            Ptychadena_porosissima
            Ptychadena_pumilio
            Ptychadena_subpunctata
            Ptychadena_taenioscelis
            Ptychadena_tellinii
            Ptychohyla_dendrophasma
            Ptychohyla_euthysanota
            Ptychohyla_hypomykter
            Ptychohyla_leonhardschultzei
            Ptychohyla_spinipollex
            Ptychohyla_zophodes
            Pyxicephalus_adspersus
            Pyxicephalus_edulis
            Ramanella_obscura
            Ramanella_variegata
            Rana_adenopleura
            Rana_alticola
            Rana_amamiensis
            Rana_amurensis
            Rana_andersonii
            Rana_archotaphus
            Rana_areolata
            Rana_arfaki
            Rana_arvalis
            Rana_asiatica
            Rana_aurantiaca
            Rana_aurora
            Rana_bacboensis
            Rana_banaorum
            Rana_banjarana
            Rana_baramica
            Rana_bedriagae
            Rana_bergeri
            Rana_berlandieri
            Rana_blairi
            Rana_boylii
            Rana_brownorum
            Rana_bwana
            Rana_capito
            Rana_cascadae
            Rana_catesbeiana
            Rana_cerigensis
            Rana_chalconota
            Rana_chaochiaoensis
            Rana_chapaensis
            Rana_chensinensis
            Rana_chiricahuensis
            Rana_chloronota
            Rana_clamitans
            Rana_compotrix
            Rana_cretensis
            Rana_cubitalis
            Rana_cucae
            Rana_curtipes
            Rana_daemeli
            Rana_dalmatina
            Rana_daorum
            Rana_dunni
            Rana_dybowskii
            Rana_emeljanovi
            Rana_epeirotica
            Rana_erythraea
            Rana_eschatia
            Rana_esculenta
            Rana_faber
            Rana_forreri
            Rana_fukienensis
            Rana_glandulosa
            Rana_gracilis
            Rana_graeca
            Rana_grahami
            Rana_grylio
            Rana_guentheri
            Rana_heckscheri
            Rana_hejiangensis
            Rana_hmongorum
            Rana_holsti
            Rana_hosii
            Rana_huanrensis
            Rana_hubeiensis
            Rana_iberica
            Rana_igorota
            Rana_iriodes
            Rana_ishikawae
            Rana_italica
            Rana_japonica
            Rana_jimiensis
            Rana_johnsi
            Rana_juliani
            Rana_khalam
            Rana_kukunoris
            Rana_kunyuensis
            Rana_kurtmuelleri
            Rana_labialis
            Rana_latastei
            Rana_lateralis
            Rana_laterimaculata
            Rana_latouchii
            Rana_lessonae
            Rana_livida
            Rana_longicrus
            Rana_luctuosa
            Rana_luteiventris
            Rana_luzonensis
            Rana_macrocnemis
            Rana_macrodactyla
            Rana_macroglossa
            Rana_maculata
            Rana_magnaocularis
            Rana_malabarica
            Rana_maosonensis
            Rana_margaretae
            Rana_megalonesa
            Rana_megatympanum
            Rana_milleti
            Rana_minima
            Rana_miopus
            Rana_mocquardii
            Rana_montezumae
            Rana_morafkai
            Rana_muscosa
            Rana_narina
            Rana_neovolcanica
            Rana_nigromaculata
            Rana_nigrovittata
            Rana_okaloosae
            Rana_okinavana
            Rana_omeimontis
            Rana_omiltemana
            Rana_onca
            Rana_ornativentris
            Rana_palmipes
            Rana_palustris
            Rana_parvaccola
            Rana_perezi
            Rana_picturata
            Rana_pipiens
            Rana_pirica
            Rana_plancyi
            Rana_pleuraden
            Rana_porosa
            Rana_pretiosa
            Rana_psilonota
            Rana_pustulosa
            Rana_pyrenaica
            Rana_raniceps
            Rana_ridibunda
            Rana_rugosa
            Rana_saharica
            Rana_sanguinea
            Rana_septentrionalis
            Rana_sevosa
            Rana_shqiperica
            Rana_shuchinae
            Rana_siberu
            Rana_sierramadrensis
            Rana_signata
            Rana_spectabilis
            Rana_sphenocephala
            Rana_spinulosa
            Rana_supranarina
            Rana_swinhoana
            Rana_sylvatica
            Rana_tagoi
            Rana_taipehensis
            Rana_tarahumarae
            Rana_taylori
            Rana_temporalis
            Rana_temporaria
            Rana_tiannanensis
            Rana_tientaiensis
            Rana_tlaloci
            Rana_tsushimensis
            Rana_utsunomiyaorum
            Rana_vaillanti
            Rana_versabilis
            Rana_vibicaria
            Rana_virgatipes
            Rana_vitrea
            Rana_warszewitschii
            Rana_weiningensis
            Rana_yavapaiensis
            Rana_zhengi
            Rana_zhenhaiensis
            Rana_zweifeli
            Ranodon_sibiricus
            Rhacophorus_annamensis
            Rhacophorus_arboreus
            Rhacophorus_bipunctatus
            Rhacophorus_calcaneus
            Rhacophorus_chenfui
            Rhacophorus_dennysi
            Rhacophorus_dugritei
            Rhacophorus_feae
            Rhacophorus_hui
            Rhacophorus_hungfuensis
            Rhacophorus_kio
            Rhacophorus_lateralis
            Rhacophorus_malabaricus
            Rhacophorus_maximus
            Rhacophorus_minimus
            Rhacophorus_moltrechti
            Rhacophorus_nigropunctatus
            Rhacophorus_omeimontis
            Rhacophorus_orlovi
            Rhacophorus_puerensis
            Rhacophorus_reinwardtii
            Rhacophorus_rhodopus
            Rhacophorus_schlegelii
            Rhacophorus_taronensis
            Rhamphophryne_festae
            Rhamphophryne_macrorhina
            Rhamphophryne_rostrata
            Rheobates_palmatus
            Rheobatrachus_silus
            Rhinatrema_bivittatum
            Rhinoderma_darwinii
            Rhinophrynus_dorsalis
            Rhombophryne_alluaudi
            Rhombophryne_coronata
            Rhombophryne_coudreaui
            Rhombophryne_laevipes
            Rhombophryne_minuta
            Rhombophryne_serratopalpebrosa
            Rhombophryne_testudo
            Rhyacotriton_cascadae
            Rhyacotriton_kezeri
            Rhyacotriton_olympicus
            Rhyacotriton_variegatus
            Rulyrana_adiazeta
            Rulyrana_flavopunctata
            Rulyrana_spiculata
            Rulyrana_susatamai
            Sabahphrynus_maculatus
            Sachatamia_albomaculata
            Sachatamia_ilex
            Sachatamia_punctulata
            Salamandra_algira
            Salamandra_atra
            Salamandra_corsica
            Salamandra_infraimmaculata
            Salamandra_lanzai
            Salamandra_salamandra
            Salamandrella_keyserlingii
            Salamandrina_perspicillata
            Salamandrina_terdigitata
            Scaphiophryne_boribory
            Scaphiophryne_brevis
            Scaphiophryne_calcarata
            Scaphiophryne_gottlebei
            Scaphiophryne_madagascariensis
            Scaphiophryne_marmorata
            Scaphiophryne_menabensis
            Scaphiophryne_spinosa
            Scaphiopus_couchii
            Scaphiopus_holbrookii
            Scaphiopus_hurterii
            Scarthyla_goinorum
            Schismaderma_carens
            Schistometopum_gregorii
            Schistometopum_thomense
            Scinax_acuminatus
            Scinax_berthae
            Scinax_boesemani
            Scinax_boulengeri
            Scinax_catharinae
            Scinax_crospedospilus
            Scinax_cruentommus
            Scinax_elaeochroa
            Scinax_fuscovarius
            Scinax_garbei
            Scinax_jolyi
            Scinax_nasicus
            Scinax_nebulosus
            Scinax_proboscideus
            Scinax_rostratus
            Scinax_ruber
            Scinax_squalirostris
            Scinax_staufferi
            Scinax_sugillatus
            Scinax_uruguayus
            'Scinax x-signatus'
            Scolecomorphus_uluguruensis
            Scolecomorphus_vittatus
            Scotobleps_gabonicus
            Scutiger_boulengeri
            Scutiger_chintingensis
            Scutiger_glandulatus
            Scutiger_mammatus
            Scutiger_muliensis
            Scutiger_tuberculatus
            Scythrophrys_sawayae
            Sechellophryne_gardineri
            Sechellophryne_pipilodryas
            Semnodactylus_wealii
            Silurana_epitropicalis
            Silurana_tropicalis
            Silverstoneia_flotator
            Silverstoneia_nubicola
            Siphonops_annulatus
            Siphonops_hardyi
            Siphonops_paulensis
            Siren_intermedia
            Siren_lacertina
            Smilisca_baudinii
            Smilisca_cyanosticta
            Smilisca_fodiens
            Smilisca_phaeota
            Smilisca_puma
            Smilisca_sila
            Smilisca_sordida
            Sooglossus_sechellensis
            Sooglossus_thomasseti
            Spea_bombifrons
            Spea_hammondii
            Spea_intermontana
            Spea_multiplicata
            Spelaeophryne_methneri
            Sphaenorhynchus_dorisae
            Sphaenorhynchus_lacteus
            Sphaenorhynchus_orophilus
            Sphaerotheca_breviceps
            Sphaerotheca_dobsonii
            Sphenophryne_cornuta
            Spicospina_flammocaerulea
            Spinomantis_aglavei
            Spinomantis_elegans
            Spinomantis_peraccae
            Staurois_latopalmatus
            Staurois_natator
            Staurois_parvus
            Staurois_tuberilinguis
            Stefania_coxi
            Stefania_evansi
            Stefania_ginesi
            Stefania_scalae
            Stefania_schuberti
            Stephopaedes_anotis
            Stephopaedes_loveridgei
            Stereochilus_marginatus
            Strabomantis_anomalus
            Strabomantis_biporcatus
            Strabomantis_bufoniformis
            Strabomantis_necerus
            Strabomantis_sulcatus
            Strongylopus_bonaespei
            Strongylopus_fasciatus
            Strongylopus_grayii
            Stumpffia_gimmeli
            Stumpffia_grandis
            Stumpffia_helenae
            Stumpffia_psologlossa
            Stumpffia_pygmaea
            Stumpffia_roseifemoralis
            Stumpffia_tetradactyla
            Stumpffia_tridactyla
            Synapturanus_mirandaribeiroi
            Tachycnemis_seychellensis
            Taricha_granulosa
            Taricha_rivularis
            Taricha_torosa
            Taudactylus_acutirostris
            Telmatobius_bolivianus
            Telmatobius_culeus
            Telmatobius_espadai
            Telmatobius_gigas
            Telmatobius_hintoni
            Telmatobius_huayra
            Telmatobius_marmoratus
            Telmatobius_niger
            Telmatobius_sanborni
            Telmatobius_sibiricus
            Telmatobius_simonsi
            Telmatobius_truebae
            Telmatobius_vellardi
            Telmatobius_verrucosus
            Telmatobius_vilamensis
            Telmatobius_yuracare
            Telmatobius_zapahuirensis
            Telmatobufo_bullocki
            Telmatobufo_venustus
            Tepuihyla_edelcae
            Teratohyla_midas
            Teratohyla_pulverata
            Teratohyla_spinosa
            Theloderma_asperum
            Theloderma_bicolor
            Theloderma_corticale
            Theloderma_moloch
            Theloderma_rhododiscus
            Thorius_dubitus
            Thorius_minutissimus
            Thorius_troglodytes
            Thoropa_miliaris
            Thoropa_taophora
            Tlalocohyla_godmani
            Tlalocohyla_loquax
            Tlalocohyla_picta
            Tlalocohyla_smithii
            Tomopterna_cryptotis
            Tomopterna_damarensis
            Tomopterna_delalandii
            Tomopterna_krugerensis
            Tomopterna_luganga
            Tomopterna_marmorata
            Tomopterna_natalensis
            Tomopterna_tandyi
            Tomopterna_tuberculosa
            Trachycephalus_coriaceus
            Trachycephalus_hadroceps
            Trachycephalus_imitatrix
            Trachycephalus_jordani
            Trachycephalus_mesophaeus
            Trachycephalus_nigromaculatus
            Trachycephalus_resinifictrix
            Trachycephalus_venulosus
            Trichobatrachus_robustus
            Triprion_petasatus
            Triprion_spatulatus
            Triturus_carnifex
            Triturus_cristatus
            Triturus_dobrogicus
            Triturus_karelinii
            Triturus_marmoratus
            Triturus_pygmaeus
            Tylototriton_asperrimus
            Tylototriton_kweichowensis
            Tylototriton_shanjing
            Tylototriton_taliangensis
            Tylototriton_verrucosus
            Tylototriton_wenxianensis
            Typhlonectes_natans
            Uperodon_systoma
            Uperoleia_laevigata
            Uperoleia_littlejohni
            Uraeotyphlus_narayani
            Urspelerpes_brucei
            Vibrissaphora_echinata
            Vitreorana_antisthenesi
            Vitreorana_castroviejoi
            Vitreorana_eurygnatha
            Vitreorana_gorzulae
            Vitreorana_helenae
            Vitreorana_oyampiensis
            Wakea_madinika
            Werneria_mertensiana
            Wolterstorffina_parvipalmata
            Xenohyla_truncata
            Xenophrys_baluensis
            Xenophrys_major
            Xenophrys_minor
            Xenophrys_nankiangensis
            Xenophrys_omeimontis
            Xenophrys_shapingensis
            Xenophrys_spinata
            Xenopus_amieti
            Xenopus_andrei
            Xenopus_borealis
            Xenopus_boumbaensis
            Xenopus_clivii
            Xenopus_fraseri
            Xenopus_gilli
            Xenopus_laevis
            Xenopus_largeni
            Xenopus_longipes
            Xenopus_muelleri
            Xenopus_petersii
            Xenopus_pygmaeus
            Xenopus_ruwenzoriensis
            Xenopus_vestitus
            Xenopus_victorianus
            Xenopus_wittei
            Xenorhina_bouwensi
            Xenorhina_lanthanites
            Xenorhina_obesa
            Xenorhina_oxycephala
            Xenorhina_varia
            ;
END;

BEGIN TREES;
      TITLE RAxML_bestTree;
      LINK TAXA = Taxa;
      TREE Fig._2 = [&R] ((((((Ichthyophis_tricolor:0.15512818931855865,((Ichthyophis_bannanicus:0.05114548253830877,Caudacaecilia_asplenia:0.04349273500666728):0.030227617545139546,(Ichthyophis_glutinosus:0.025577686831715636,Ichthyophis_orthoplicatus:0.024680936988547383):0.03707580419285977):0.013307325553815313):0.08726062928050775,(Ichthyophis_bombayensis:0.11658213411373387,Uraeotyphlus_narayani:0.22642274719006317):0.04600074112639517):0.12105222907078213,((((((Luetkenotyphlus_brasiliensis:0.07754051625197583,(Siphonops_hardyi:0.270200834490591,(Siphonops_annulatus:0.06292601270940154,Siphonops_paulensis:0.021072817179252246):0.03551861929893312):0.032643968629368345):0.0742579432670324,(Geotrypetes_seraphini:0.2575230067522903,((Schistometopum_thomense:0.0942845606216579,Schistometopum_gregorii:0.12193229359020712):0.061006818636490205,(Gymnopis_multiplicata:0.26382349942611233,(Dermophis_parviceps:0.10461619503887794,(Dermophis_mexicanus:0.06942028902604636,Dermophis_oaxacae:0.07184797143824209):0.006324019079513551):0.03751912356882525):0.08519680288996473):0.03649069341801962):0.019074603025975714):0.025469653482547917,((Gegeneophis_seshachari:0.11315981821732159,Gegeneophis_ramaswamii:0.09227955081994238):0.07882579127204424,(Hypogeophis_rostratus:0.0656009023529858,((Grandisonia_alternans:0.0970216626247433,(Grandisonia_brevis:0.13046362785867577,(Grandisonia_sechellensis:0.07707907694012701,Grandisonia_larvata:0.11576547884455306):0.023687025190225273):0.017660030623206136):0.03231253282130345,Praslinia_cooperi:0.09453541184595882):0.02556554506499458):0.05109189729978144):0.04220831051182831):0.026761675881140104,(Chthonerpeton_indistinctum:0.19762658145813863,(((Oscaecilia_ochrocephala:0.16981804500479208,Caecilia_volcani:0.085849870858732):0.03776605784257829,Caecilia_tentaculata:0.12169151990335754):0.1340772608161132,Typhlonectes_natans:0.1685929206164274):0.013161291718547942):0.08168214040214405):0.031560566598592486,(Herpele_squalostoma:0.1745128926390318,((Boulengerula_taitana:0.06602198378092844,Boulengerula_uluguruensis:0.09801668419978112):0.09817035385271168,Boulengerula_boulengeri:0.19264575607662227):0.028911904024182995):0.08013020596893246):0.015619218454964896,((Scolecomorphus_vittatus:0.07012153358942647,Scolecomorphus_uluguruensis:0.06543467178170749):0.17117055907904044,Crotaphatrema_tchabalmbaboensis:0.3198329004176958):0.04630793566387266):0.04508165191276247):0.05180401791540392,(Epicrionops_marmoratus:0.19568736636047715,(Rhinatrema_bivittatum:0.22235237730339946,Epicrionops_niger:0.1614244806243516):0.044840998548945084):0.029224862971408524):0.2727543092940001,(((((((Hadromophryne_natalensis:0.029293323574437577,(Heleophryne_purcelli:0.02052744131955876,Heleophryne_regis:0.020754222518019182):0.09303724463934189):0.17596154513523252,((Nasikabatrachus_sahyadrensis:0.2635564740566661,((Sooglossus_thomasseti:0.04250365242656698,Sooglossus_sechellensis:0.04588919955517448):0.048989875692312355,(Sechellophryne_pipilodryas:0.10427058338342313,Sechellophryne_gardineri:0.07912265393885108):0.06714674717049832):0.26355446504096314):0.17270386287690148,(((((Telmatobufo_bullocki:0.026159789394025194,Telmatobufo_venustus:0.026150880033604832):0.022503443612756288,Calyptocephallela_gayi:0.05874478119391234):0.1429538366913903,((((Platyplectrum_spenceri:0.02465279359588217,(Lechriodus_fletcheri:0.06412604242218539,Platyplectrum_ornatum:0.07098215284272492):0.03112223419887004):0.050769702687405625,(((Philoria_sphagnicolus:0.1854969533280328,(((Limnodynastes_convexiusculus:0.013646429349349608,Limnodynastes_lignarius:0.05269278546002557):0.008191656982826798,Limnodynastes_salmini:0.04800851040791374):0.035035656515019026,((Limnodynastes_terraereginae:3.51422724235035E-6,((Limnodynastes_dumerilii:0.0020152300467420514,Limnodynastes_interioris:3.51422724235035E-6):0.004526577942866743,Limnodynastes_dorsalis:0.012901493879538154):0.0040564169715803676):0.04327655958417585,((Limnodynastes_fletcheri:0.025350772049438013,Limnodynastes_depressus:0.06785651968180288):0.012584530098238069,(Limnodynastes_tasmaniensis:0.031468593641165596,Limnodynastes_peronii:0.03964943850136758):0.019074615681290784):0.024244370633566922):0.010857870597111682):0.030179545098032032):0.010355279284222293,Adelotus_brevis:0.13051100980694194):0.01281738274913602,Heleioporus_australiacus:0.1466865291594109):0.009496184251691492):0.009541227256364212,((Notaden_bennettii:0.04056669848511425,Notaden_melanoscaphus:0.01835972155673522):0.09003087164003981,(Neobatrachus_pelobatoides:0.03482788614467177,(Neobatrachus_sudelli:0.01690896072158227,Neobatrachus_pictus:0.017321702471059835):0.02167359472533365):0.07316994980130526):0.013078694736219674):0.11283468724511068,(Rheobatrachus_silus:0.2778071976471774,((((Mixophyes_fasciolatus:0.026714108322199448,Mixophyes_schevilli:0.004917073736082525):0.006373494964978543,(Mixophyes_coggeri:0.021973646743516193,Mixophyes_carbinensis:3.51422724235035E-6):0.0030272982670658563):0.01875996898527427,Mixophyes_balbus:0.02345844004642947):0.11504490434319212,(Taudactylus_acutirostris:0.19079980460213897,(((Paracrinia_haswelli:0.11547691586973663,(Assa_darlingtoni:0.1004917004170296,Geocrinia_victoriana:0.04823639577943305):0.0473629777887023):0.04381399283559031,(Crinia_nimbus:0.14213748907747215,((Crinia_signifera:0.03796955580967866,Crinia_riparia:0.03684701211608149):0.02072647508196699,((Crinia_tinnula:0.03587141714702705,Crinia_parinsignifera:0.03812980846477936):0.023390813082613402,Crinia_deserticola:0.1060629757783124):0.007755090673167179):0.06783636706521619):0.0507504225804863):0.016451759569532265,((Spicospina_flammocaerulea:0.10553786014729451,(Uperoleia_laevigata:0.046179131201695624,Uperoleia_littlejohni:0.06793333208984909):0.07257283212603843):0.018997201754440675,((Metacrinia_nichollsi:0.06070645643693599,Myobatrachus_gouldii:0.053238563095757016):0.05705729347084828,(Pseudophryne_bibronii:0.03395469313171839,Pseudophryne_coriacea:0.04351116251488284):0.044449530658175924):0.04552248130643274):0.02152973421686504):0.06432452591452581):0.058970178878688925):0.015268360659133041):0.01513825108369909):0.04451953876281484):0.044816923485470304,((((Flectonotus_pygmaeus:0.06818710003410915,Flectonotus_fitzgeraldi:0.05670460716216311):0.20098575928420834,(((Stefania_ginesi:0.04752937201626349,(Stefania_schuberti:0.08804321921230417,((Stefania_evansi:0.028206054598563693,Stefania_scalae:0.022865420854657953):0.04320924085648965,Stefania_coxi:0.08541558397035885):0.015254346729432735):0.030201349791915938):0.0709251983470627,(Gastrotheca_fissipes:0.1544515522529424,((Gastrotheca_walkeri:0.1818947725508174,((Gastrotheca_weinlandii:0.047853990400006215,Gastrotheca_guentheri:0.057401685840728564):0.043810302422339464,((Gastrotheca_helenae:0.0453298197994057,Gastrotheca_longipes:0.05825292104347645):0.030097527618081016,(Gastrotheca_cornuta:0.04275350261324936,Gastrotheca_dendronastes:0.04335440390085311):0.031727461961265295):0.0172853622788192):0.020596074741275137):0.018630926614417176,(Gastrotheca_zeugocystis:0.07861667119838517,((Gastrotheca_psychrophila:0.049475781085582045,(((Gastrotheca_marsupiata:0.025948664394222366,(Gastrotheca_griswoldi:0.03874466897052366,(Gastrotheca_gracilis:0.018976928103969063,(Gastrotheca_chrysosticta:0.010723180858048917,Gastrotheca_christiani:0.008844104046468074):0.0048015840637312904):0.009450885979058387):0.0046357345791194415):0.003957909850080148,(Gastrotheca_pseustes:0.022909795891093612,Gastrotheca_peruana:0.02529357677528453):0.01892734056526497):0.005641439036844435,((Gastrotheca_stictopleura:0.04337062324598738,Gastrotheca_atympana:0.05369819385049636):0.022299443901318907,(Gastrotheca_ochoai:0.015470187953896798,Gastrotheca_excubitor:0.018124706820927487):0.023196696072278592):0.004125309700933757):0.0027247390301405627):0.006506263391305517,((Gastrotheca_galeata:0.06695698648611924,(Gastrotheca_monticola:0.053857658211470705,(Gastrotheca_litonedis:0.026765292207954088,(Gastrotheca_plumbea:0.018795254328174794,Gastrotheca_orophylax:0.008733385335434357):0.020013583739494432):0.01033125736456889):0.012705054606372945):0.007743683816394949,((Gastrotheca_nicefori:0.05772204727769121,(Gastrotheca_dunni:0.019072565062378737,((Gastrotheca_argenteovirens:0.0028367421248856914,Gastrotheca_trachyceps:0.0023689187211205598):0.020349266066434724,(Gastrotheca_aureomaculata:0.009069187138339844,Gastrotheca_ruizi:0.0067031819601149865):0.01127341251450033):0.013565330138048568):0.004976301296132533):0.00521287773782518,Gastrotheca_riobambae:0.04036311985431352):0.015698427370543156):0.00487846368526542):0.008543111380203028):0.03235885813625151):0.021296272356809347):0.05471001772162827):0.006807973278035055,((Hemiphractus_bubalus:0.04371822508839736,Hemiphractus_proboscideus:0.056880858480218406):0.06113010499581426,(Hemiphractus_helioi:0.09407261076650304,Hemiphractus_scutatus:0.12866328576972463):0.02485767606266772):0.09776592132504053):0.00932763899567129):0.022243896921328835,((((Myersiohyla_inparquesi:0.1916699555830184,(((((Hypsiboas_cinerascens:0.10331399691629342,(((Hypsiboas_picturatus:0.129048748507292,(Hypsiboas_heilprini:0.10566017507866196,(((Hypsiboas_dentei:0.1029670420156654,Hypsiboas_fasciatus:0.04689939261744679):0.025452473961343248,Hypsiboas_raniceps:0.04685645804558534):0.012422896716896614,(((Hypsiboas_albopunctatus:0.017947376626851825,Hypsiboas_multifasciatus:0.030448903553436456):0.019714790469617802,Hypsiboas_lanciformis:0.053059007626302716):0.013176196224630515,Hypsiboas_calcaratus:0.08131107884271373):0.014054615622855952):0.039654169935532495):0.013063786448110721):0.0071779635308199434,((Hypsiboas_rufitelus:0.022727120181614137,Hypsiboas_pellucens:0.026374613280254155):0.09226113361315585,((Hypsiboas_ericae:0.05366863692306379,(((Hypsiboas_joaquini:0.022426022228937137,Hypsiboas_semiguttatus:0.015176997329867563):0.015181091622960899,(Hypsiboas_leptolineatus:0.03221864756912586,(Hypsiboas_latistriatus:3.534630144071727E-4,Hypsiboas_polytaenius:0.00263651918213505):0.025847843401642336):0.010059906057040751):0.00543768167254387,((((Hypsiboas_prasinus:0.0280160593799342,(Hypsiboas_pulchellus:0.02022858192854508,Hypsiboas_cordobae:0.01448855040907502):0.011943172252516076):0.0173199101494137,Hypsiboas_caingua:0.03423839562548107):0.003895106116781549,(Hypsiboas_guentheri:0.03292629684106205,(Hypsiboas_bischoffi:0.016788407514825573,Hypsiboas_marginatus:0.01963036729152492):0.009051716060713314):0.004600211760050983):0.004854979094530035,((Hypsiboas_andinus:0.005913270202323614,Hypsiboas_riojanus:0.015117951837711007):0.009365786667485778,(Hypsiboas_marianitae:0.015773826050896708,Hypsiboas_balzani:0.012153019513732181):0.010745036104807456):0.0053875597244119715):0.016612422036721322):0.015237568128579517):0.03206926715790917,(Hypsiboas_albomarginatus:0.0806362682252256,((Hypsiboas_crepitans:0.05135296038561596,Hypsiboas_rosenbergi:0.05701241236526646):0.039650691934599386,((Hypsiboas_lundii:0.040390842523321646,Hypsiboas_pardalis:0.04256923612449412):0.020619630515715755,Hypsiboas_faber:0.04600469654232623):0.013761589687456022):0.010610447536238247):0.01590383319535416):0.017544167346611735):0.012376887199064003):0.010809008202331431,((Hypsiboas_sibleszi:0.09097574526746818,((Hypsiboas_ornatissimus:0.1312765845623414,(Hypsiboas_lemai:0.027742967882179455,Hypsiboas_benitezi:0.027181770287243046):0.041625538190457695):0.03499966015354184,(Hypsiboas_roraima:0.07903188640788503,(Hypsiboas_nympha:0.05059310442804219,Hypsiboas_microderma:0.05951306966813316):0.03671138176212556):0.013644479196622934):0.016287515583210743):0.00613939514653591,(Hypsiboas_boans:0.042189750543904544,(Hypsiboas_geographicus:0.01827922988069556,Hypsiboas_semilineatus:0.012726389317249365):0.05280955800922917):0.07706037293053618):0.005148637635262385):0.005814443838607742):0.008137798960987224,(((Aplastodiscus_cochranae:0.016379568361717115,Aplastodiscus_perviridis:0.03130188008471425):0.0316912136038748,(((Aplastodiscus_leucopygius:0.02142725459972106,Aplastodiscus_cavicola:0.016265676206638744):0.01827259264326265,Aplastodiscus_callipygius:0.030393710174322356):0.004999767687260793,Aplastodiscus_albosignatus:0.03103003032633658):0.019970618031554088):0.05630970583992509,(Aplastodiscus_albofrenatus:0.06980549700559893,((Aplastodiscus_weygoldti:0.014145731705618277,Aplastodiscus_arildae:0.012938132621993635):0.035160981277580446,Aplastodiscus_eugenioi:0.051262833040350994):0.009634012001190873):0.058437477378297124):0.04955393026379):0.019705933770769526,(Bokermannohyla_martinsi:0.05137955800415404,(Bokermannohyla_astartea:0.03799153409111611,(Bokermannohyla_circumdata:0.009530891124088985,Bokermannohyla_hylax:0.011197489293560656):0.020909337539083604):0.026497436032480046):0.07610903202512173):0.030887648977350367,(((Hyloscirtus_colymba:0.11896171724560499,Hyloscirtus_simmonsi:0.09910445643467858):0.03246281807057168,((Hyloscirtus_lascinius:0.06422219398878638,Hyloscirtus_palmeri:0.10892404256583546):0.017341185640659708,Hyloscirtus_phyllognathus:0.11742327210888723):0.006710508504379973):0.05284767844483115,((Hyloscirtus_charazani:0.02310428591924345,Hyloscirtus_armatus:0.02370718768543255):0.06743310837183863,((Hyloscirtus_pacha:0.044259347664714795,Hyloscirtus_pantostictus:0.04333576273563205):0.05859588969442001,(Hyloscirtus_lindae:0.030006741142078765,Hyloscirtus_tapichalaca:0.0169228221575519):0.06933965657484702):0.017299812363933454):0.018609542647817338):0.03851218746630512):0.02550703868907273,Myersiohyla_kanaima:0.14627094817262967):0.007222838177971184):0.027704751464216953,((((Scinax_catharinae:0.07202346280484707,Scinax_berthae:0.060028772878566505):0.05715262536109205,(Scinax_uruguayus:0.12684347713659427,((Scinax_acuminatus:0.10704797576548304,((((Scinax_garbei:0.05164594753163333,Scinax_jolyi:0.022621092765092714):0.008683492378295023,Scinax_proboscideus:0.026907493799784375):0.014152934413298507,(Scinax_nebulosus:0.09172844177308774,Scinax_rostratus:0.07239562227119194):0.02241076778007981):0.019087723710446657,(Scinax_sugillatus:0.01741392743916493,Scinax_boulengeri:0.02942329574651338):0.054499891228184966):0.03387636721661196):0.04088535631391975,(Scinax_crospedospilus:0.11463670653690755,(((Scinax_cruentommus:0.11932945940023525,Scinax_boesemani:0.09933110231475588):0.019441975070748664,(Scinax_fuscovarius:0.09243524415629431,(Scinax_nasicus:0.07096867276905895,(Scinax_ruber:0.023107642046277074,'Scinax x-signatus':0.029704383860518914):0.04770387331999749):0.0328478920689383):0.02228068918087736):0.023080834523123733,((Scinax_staufferi:0.09078267978844616,Scinax_elaeochroa:0.07248533452161472):0.022500201243530374,Scinax_squalirostris:0.06712862121645605):0.010565860015842476):0.022460028624350505):0.016764157337873085):0.02881437158245868):0.023414819364859087):0.05666277358097713,(Sphaenorhynchus_orophilus:0.05298619001590326,(Sphaenorhynchus_dorisae:0.06392143465979008,Sphaenorhynchus_lacteus:0.058790800215941005):0.03162032001305173):0.08827618675123457):0.01139082671259959,(((Scarthyla_goinorum:0.1314750685634851,((Pseudis_laevis:0.07355928945119408,(Pseudis_caraya:0.020103085270082855,Pseudis_limellum:0.029784406292136516):0.04864016673736638):0.01420751793408382,(((Pseudis_paradoxa:0.033123016662728995,Pseudis_bolbodactyla:0.03326007326398996):0.008720119929470851,(Pseudis_tocantins:0.04190973111293032,Pseudis_fusca:0.03761162073359612):0.0372467973351855):0.03134110513245241,(Pseudis_minuta:0.010084475004252769,Pseudis_cardosoi:0.011973835522258017):0.07928250992419668):0.01425504839596116):0.05818909568602705):0.051155639794601845,(Xenohyla_truncata:0.07463399206406053,(((Dendropsophus_seniculus:0.06438211403815773,Dendropsophus_marmoratus:0.07183769309492978):0.049494587754844124,(((Dendropsophus_koechlini:0.09163579054816269,(Dendropsophus_brevifrons:0.07362713834865095,Dendropsophus_parviceps:0.06665978011718937):0.011029874147775976):0.04187395751390245,(((Dendropsophus_pelidna:0.015222120172527356,Dendropsophus_labialis:0.007770589564241685):0.02176804141655346,Dendropsophus_carnifex:0.04720976170173009):0.04861306473856256,Dendropsophus_giesleri:0.09341352267143871):0.008242149760948815):0.012317757989132882,((Dendropsophus_minutus:0.1393234064439916,(((Dendropsophus_riveroi:0.057322170156504426,(Dendropsophus_nanus:0.009612117035336102,Dendropsophus_walfordi:0.02819075014501515):0.02079939187872018):0.025825977053346928,((Dendropsophus_berthalutzae:0.06940038377722031,Dendropsophus_bipunctatus:0.06371493695652918):0.008130466478422305,(Dendropsophus_branneri:0.07121831492821079,(Dendropsophus_minusculus:0.02956017783131708,(Dendropsophus_rubicundulus:0.0371318397287897,Dendropsophus_sanborni:0.02605033562865445):0.017926794444016535):0.003636762195110127):0.01567000252209191):0.008742354239969117):0.03301063542423189,(Dendropsophus_leali:0.08082894726569356,(Dendropsophus_rhodopeplus:0.05054326989167012,(Dendropsophus_microcephalus:0.056050293607648516,(Dendropsophus_sartori:0.016611146380807715,Dendropsophus_robertmertensi:0.04491192936936934):0.030710977415724226):0.010491346881867267):0.014272666171011417):0.01876904166461618):0.01621662358855775):0.010111995029627141,(Dendropsophus_anceps:0.07701791418811513,(Dendropsophus_aperomeus:0.10824348441137917,((Dendropsophus_miyatai:0.13656962853688195,Dendropsophus_schubarti:0.0691738385422116):0.016091452508096085,(Dendropsophus_elegans:0.11300149868476436,(Dendropsophus_ebraccatus:0.07351979032031108,((Dendropsophus_bifurcus:0.09547934028011258,Dendropsophus_sarayacuensis:0.07452014915178197):0.01612301770800049,(Dendropsophus_leucophyllatus:0.03802361329213774,Dendropsophus_triangulum:0.0474116015040207):0.03620450874222584):0.00983344304871975):0.02076562046451006):0.01862826923310839):0.009302809784779377):3.51422724235035E-6):0.008344569844560267):0.012861154883865447):0.010058017083028286):0.013207432080127052,Dendropsophus_allenorum:0.0738598744566743):0.014417253801054809):0.053170941302919064):0.015782984667323696,(((((Corythomantis_greeningi:0.06690103761107055,((Argenteohyla_siemersi:0.03498098885707089,Nyctimantis_rugiceps:0.08295676506301534):0.013304256214129125,Aparasphenodon_brunoi:0.047349822368376336):0.028863134729552025):0.007917915593260674,(Trachycephalus_jordani:0.05726723906510311,((((Trachycephalus_nigromaculatus:0.026469205959021587,Trachycephalus_imitatrix:0.018855210023219028):0.0029930998024070192,((Trachycephalus_hadroceps:0.02254636599134189,Trachycephalus_venulosus:0.008284876342481148):0.006270096430706478,Trachycephalus_resinifictrix:0.012522206529985239):0.017455468418187424):0.00442758748392084,Trachycephalus_mesophaeus:0.02704040245144021):0.005484048437649863,Trachycephalus_coriaceus:0.03584753466378477):0.011069256052256152):0.017304479863210855):0.008859823485834667,(Phyllodytes_luteolus:0.13495888096033878,((Tepuihyla_edelcae:0.04989459632951296,(((Osteocephalus_cabrerai:0.012416856350191234,(Osteocephalus_verruciger:0.015250099275480312,Osteocephalus_buckleyi:0.018793421093810585):0.004896729866048731):0.012772001949676053,Osteocephalus_mutabor:0.023698546692980205):0.008448638600172215,(Osteocephalus_alboguttatus:0.04425815749983307,((Osteocephalus_oophagus:0.02240750359684367,Osteocephalus_taurinus:0.01011020427382609):0.01862903717758698,(Osteocephalus_leprieurii:0.025018499389578962,Osteocephalus_planiceps:0.006320631386308616):0.012348915398163284):0.004743623462180909):0.0029161567712150203):0.028539958930737522):0.011385857516090192,(Osteopilus_vastus:0.09131600759831518,(((Osteopilus_pulchrilineatus:0.04805217290853981,Osteopilus_dominicensis:0.0364732990233173):0.00595430187401293,((Osteopilus_wilderi:0.07795721051575401,Osteopilus_marianae:0.07180219272808286):0.02451772423558093,(Osteopilus_crucialis:0.06852199092940382,Osteopilus_brunneus:0.0344350237689433):0.020070620709858482):0.016387770563182167):0.006332479736572655,Osteopilus_septentrionalis:0.05362054400739048):0.005519993896381874):0.03487997469543429):0.004622919840860835):0.008611975062236875):0.006825803189902338,(Phyllodytes_auratus:0.09885959016782403,Itapotihyla_langsdorffii:0.05318152947750459):0.007522613517877108):0.05277355279323275,(((((((((Isthmohyla_rivularis:0.015583836761989177,Isthmohyla_tica:0.021156435043130386):0.06629318529699259,(Isthmohyla_zeteki:0.1051942563946694,Isthmohyla_pseudopuma:0.04878072070238278):0.03984045733707456):0.011444359856784332,((Smilisca_baudinii:0.05197781867991817,((Smilisca_sordida:0.05553394782616671,Smilisca_sila:0.08783061939391472):0.02164163641912325,((Smilisca_cyanosticta:0.033470234442922235,(Smilisca_phaeota:0.04710605189423664,Smilisca_puma:0.04053794852524606):0.012026478415261456):0.013213506988886913,Smilisca_fodiens:0.03599711627825228):0.011073163341158336):0.012338425504002297):0.015597351518659002,((Anotheca_spinosa:0.09933059953948903,Triprion_petasatus:0.04281945863259758):0.014731062345424694,Triprion_spatulatus:0.06138518402629339):0.012735841579996805):0.017286867961552946):0.007540459479298788,((Tlalocohyla_loquax:0.05943098540742401,Tlalocohyla_godmani:0.05889653528888395):0.04070564089012377,(Tlalocohyla_picta:0.10650369250362725,Tlalocohyla_smithii:0.10429474746659316):0.024963423343155867):0.05926052836964715):0.010258419401307646,(((Hyla_chinensis:0.032123868987454805,(Hyla_annectans:0.004676096380999446,Hyla_tsinlingensis:0.012065724341749615):0.01926857208558372):0.026322853773479697,(Hyla_meridionalis:0.05039715298231055,(Hyla_sarda:0.04868766885234035,(Hyla_savignyi:0.061984855018942485,((Hyla_molleri:0.045216494191280786,Hyla_intermedia:0.037395321974481234):0.018295018797413694,(Hyla_orientalis:0.041187185679864124,Hyla_arborea:0.030717641729713176):0.009796144348257792):0.0021023363868735807):0.0031525839176354668):0.023114285323217736):0.009973416375753818):0.019042795362863376,((Hyla_squirella:0.11512257790816918,(Hyla_cinerea:0.0572566844481563,Hyla_gratiosa:0.04926749528498019):0.02024768888445194):0.010015994796807613,(((Hyla_immaculata:0.0589296894392763,Hyla_japonica:0.02980184901690818):0.007288691347492736,(Hyla_arenicolor:0.03059011868641421,(Hyla_wrightorum:0.010564091166549591,(((Hyla_plicata:0.012709896587732574,Hyla_euphorbiacea:0.010863017376252249):0.003868986703085239,Hyla_eximia:0.007031562193754681):0.0074200066572460005,Hyla_walkeri:0.015175814739912483):0.01519285140069749):0.01222516030288469):0.010086811177536766):0.004627999137633894,(Hyla_andersonii:0.03812578389481024,((Hyla_versicolor:0.00231943145035348,(Hyla_avivoca:0.005289200991129247,Hyla_chrysoscelis:0.00514648183191685):0.004719783403007144):0.025248132012219465,Hyla_femoralis:0.05047468592355871):0.004109991651623919):0.004004181318728218):0.010461486841801076):0.040140458727894145):0.006058218570529266):0.04215176169470081,(Megastomatohyla_mixe:0.11566681470748129,(Charadrahyla_nephila:0.051395027708108575,Charadrahyla_taeniopus:0.047782237224252996):0.035636627635555466):0.015241313717358405):0.010882136730825488,((Ecnomiohyla_miliaria:0.004223727478230915,Ecnomiohyla_minera:0.001117167197358359):0.12141300384535603,(((Ptychohyla_spinipollex:0.0504280973192003,(Bromeliohyla_bromeliacia:0.06766391909175055,(Duellmanohyla_rufioculis:0.0943529301163044,Duellmanohyla_soralia:0.06710551435042657):0.013400284835679652):0.008734203643818777):0.001941190488262434,(Ptychohyla_dendrophasma:0.04397986624717933,((Ptychohyla_euthysanota:0.024028561670567668,(Ptychohyla_zophodes:0.007179722417409263,Ptychohyla_leonhardschultzei:0.007884734892003548):0.017911594497129684):0.005975330534180401,Ptychohyla_hypomykter:0.024234201791621796):0.018416442077854443):0.016037289513704266):0.02935181376910516,Ecnomiohyla_miotympanum:0.16761119093740073):0.008267079649863457):0.023434619142929828):0.006892373581450525,(((Exerodonta_abdivita:0.002089628152855378,Exerodonta_perkinsi:3.51422724235035E-6):0.04122409197939409,((Exerodonta_melanomma:0.01700022010052438,Exerodonta_sumichrasti:0.027693032249483787):0.0325998197533361,(Exerodonta_chimalapa:0.003550357862803284,(Exerodonta_xera:0.006073535527607524,Exerodonta_smaragdina:0.0020903617550465823):5.272250479217169E-4):0.030487967988744116):0.006607761742482994):0.04796350259051482,((((Plectrohyla_matudai:0.04110682235940342,Plectrohyla_guatemalensis:0.02457742195308873):0.0043261226071495225,Plectrohyla_chrysopleura:0.023424883906097038):0.0019754569093940622,Plectrohyla_glandulosa:0.021705875368068162):0.012615789646340166,((Plectrohyla_siopela:0.027655178963460033,(Plectrohyla_cyclada:0.012214434803753102,Plectrohyla_arborescandens:0.01234542236119781):0.024925769397304728):3.51422724235035E-6,(Plectrohyla_ameibothalame:0.026760772675661272,(Plectrohyla_bistincta:0.019565530628865006,(Plectrohyla_calthula:0.0024794557560306354,Plectrohyla_pentheter:0.004778236791455386):0.015391467938772113):0.010501538084419882):0.010300456624218144):0.00692445260385303):0.02089836685915877):0.012718760333844127):0.008804634753530341,((Acris_gryllus:0.053801848512398,(Acris_crepitans:0.0249445436753068,Acris_blanchardi:0.033664468803454195):0.01958762959124142):0.08145011146022473,((Pseudacris_regilla:0.024497978519199947,Pseudacris_cadaverina:0.03635911772307517):0.037233925255935725,(((Pseudacris_ornata:0.05506472607211143,(Pseudacris_streckeri:0.01353626216646528,Pseudacris_illinoensis:0.01539819506134295):0.027971880784123566):0.04174078490729063,((Pseudacris_fouquettei:0.016707991670453556,(((Pseudacris_nigrita:0.0020366516291389024,Pseudacris_kalmi:9.044237165908218E-4):0.022416916301824664,(Pseudacris_triseriata:0.011890368542720775,Pseudacris_feriarum:0.014809767589267404):0.004929348797753799):0.00530903361189601,(Pseudacris_maculata:5.233816407099565E-4,Pseudacris_clarkii:0.0013389817152437726):0.013381426561452804):0.004422890175469863):0.007071780949795678,(Pseudacris_brimleyi:0.007908987722079031,Pseudacris_brachyphona:0.01800500169688794):0.014729383285199883):0.02915872961838748):0.006913621988770431,(Pseudacris_crucifer:0.05710683362277458,Pseudacris_ocularis:0.04480483402311943):0.031370488235249176):0.011804663729701573):0.014465764653730773):0.018571692014171554):0.061162737109931996):0.009648271981605108):0.007735637033836134):0.009095092547632992):0.030676345893313396,((((((Litoria_adelaidensis:0.05282777193459525,Litoria_burrowsi:0.037414531525088106):0.008323417809390912,((Litoria_rothii:0.07500132483047178,(Litoria_tyleri:0.02248734036980002,((Litoria_amboinensis:3.51422724235035E-6,Litoria_darlingtoni:0.002398387902344068):0.012840900300317695,Litoria_peronii:0.011438611995947713):0.01662043044666214):0.015808064470614166):0.012479681399215944,(((Litoria_dentata:0.02746860015907917,(Litoria_electrica:0.059780547894458295,Litoria_rubella:0.03772336886427846):0.010199865621002743):0.00447979189619973,Litoria_congenita:0.06122538106576059):0.019519538101366772,(Litoria_jervisiensis:0.027996941927548043,((((Litoria_paraewingi:0.01215599272441749,Litoria_verreauxii:0.007389162328921283):0.004641955535919955,Litoria_revelata:0.020722088054381204):0.0011594575754041224,Litoria_littlejohni:0.01186884698390143):0.009480144130316661,Litoria_ewingii:0.027224032701075776):0.007720294706476694):0.04213710542489844):0.010456832795670497):0.014197985555527912):0.012833253768393395,((((Litoria_microbelos:0.07423040897263582,Litoria_dorsalis:0.044271900591172066):0.01204710710352975,Litoria_longirostris:0.06443272108571267):0.007790556094443486,Litoria_meiriana:0.08728727161947436):0.030822335323383285,(Litoria_personata:0.07795436919476689,(((Litoria_tornieri:0.02540955865662706,(Litoria_inermis:0.021694082893935342,Litoria_pallida:0.014040752783461697):0.012789822933548061):0.0043232082761879755,(Litoria_freycineti:0.0038650477236482593,Litoria_latopalmata:1.432472329968647E-5):0.0316151419442123):0.004826680170423154,((Litoria_coplandi:0.05386035960478554,Litoria_watjulumensis:0.0813532834866376):0.015132884511665917,(Litoria_nasuta:0.037586983476184554,Litoria_nigrofrenata:0.05831166561805655):0.006892429926288797):0.002392059921383026):0.02341440598642318):0.03074207394892594):0.007832608242004303):0.008262531491935506,((Litoria_fallax:0.029767315500436438,Litoria_olongburensis:0.0318690254005535):0.04206955966035147,(Litoria_bicolor:0.060688750355203276,(((((Litoria_majikthise:0.03288504994825968,Litoria_iris:0.0523701826713703):0.021426281792119466,Litoria_havina:0.09850066732742335):0.009813569321676093,Litoria_multiplica:0.07223034150028855):0.006799037342479455,Litoria_pronimia:0.05609113539419293):0.010412334324262602,(((((Litoria_angiana:0.07403814526909785,Litoria_micromembrana:0.024326691611118584):0.014191207254049373,(Litoria_spartacus:0.06127912909043599,(Litoria_arfakiana:0.022885232016762284,Litoria_wollastoni:0.032864608476187954):0.01988237379802083):0.01053408500396332):0.0054993940831493315,Litoria_modica:0.0272407973915969):0.0032072905332072186,Litoria_leucova:0.07859998740487813):0.012899989501957898,(Litoria_prora:0.05668866121578826,Litoria_nigropunctata:0.06581565645858944):0.019001697888847306):0.006256037793298847):0.010054169202931501):0.009828187461898945):0.030224004398395194):0.027353935660997277,(((Litoria_infrafrenata:0.0689771743769351,Litoria_dux:0.16147175191366198):0.033854397962245826,(Litoria_brevipalmata:0.07355000112270481,((Nyctimystes_narinosus:0.02724522175005688,Nyctimystes_kubori:0.042745156000051646):0.026736290123174026,((Nyctimystes_humeralis:0.021858842694853994,Nyctimystes_zweifeli:0.01987103821131217):0.01485677737024531,((Nyctimystes_foricula:0.011745911695180609,Nyctimystes_semipalmatus:0.032226327186010616):0.028906856835919,(Nyctimystes_cheesmani:0.04446966171294944,(Nyctimystes_pulcher:0.026430466551392726,Nyctimystes_papua:0.04028975448851791):0.009470715995733734):0.011061607627860985):0.008856997109417447):0.007090592742658684):0.04504115900972419):0.008637579116746055):0.028304689309655736,(((((Litoria_kumae:0.03323176611823457,(Litoria_gracilenta:0.018480714852120512,(Litoria_xanthomera:0.01660182851459004,Litoria_chloris:0.00785386984451041):0.012344053823379662):0.02113701424159689):0.01827094751445247,((Litoria_splendida:0.015700880093257274,(Litoria_gilleni:0.01088341134649128,Litoria_caerulea:0.011030969291246058):0.006998199160928989):0.004835355797502743,Litoria_cavernicola:0.03783828061461305):0.0335552467109116):0.01788256231291284,((Litoria_raniformis:0.023369423245196454,((Litoria_moorei:7.96130714859618E-4,Litoria_cyclorhyncha:0.0023828808789906355):0.014066258770297331,Litoria_aurea:0.04131096388967536):0.008374924722717097):0.011679914989471008,(Litoria_dahlii:0.05245962566371663,((Cyclorana_novaehollandiae:0.002424991740503426,Cyclorana_australis:3.51422724235035E-6):0.04476775162293372,((((Cyclorana_alboguttata:0.03379337325249785,Cyclorana_brevipes:0.019265373216797478):0.006821934034331524,(Cyclorana_cryptotis:0.028203950471009816,(Cyclorana_manya:0.03492981697466287,((Cyclorana_longipes:0.00813599117146493,(Cyclorana_maini:0.0027972993444458083,Cyclorana_maculosa:0.00578238737360237):0.004237869996370579):0.006519132515228173,(Cyclorana_vagitus:0.012752219056321306,Cyclorana_cultripes:0.011451277329891983):0.013147236406761994):0.004039227813848053):0.008501008564837627):0.001583098338931389):0.0013773531378405942,Cyclorana_verrucosa:0.020661751333999348):0.0046713484858082,Cyclorana_platycephala:0.013289545678467629):0.0013502550889857125):0.012097868263164087):0.012191103519235781):0.018797938461973675):0.0035437180276023804,(((Litoria_exophthalmia:0.14458942593490307,(Litoria_genimaculata:0.05744983607417445,Litoria_eucnemis:0.08220669544164354):0.01968871887291415):0.012375149539293556,(Litoria_andiirrmalin:0.08853179204089255,(Litoria_lesueurii:0.012597353557267203,(Litoria_booroolongensis:0.011711540242097205,(Litoria_wilcoxii:0.01158317296249063,Litoria_jungguy:0.004807420635711331):0.005588300253211283):0.007342331855047599):0.04791093697547003):0.009838771944697696):0.007476049244651745,(Litoria_spenceri:0.03274088780458463,((Litoria_citropa:0.024058190337131462,((Litoria_pearsoniana:0.03649108023060613,Litoria_barringtonensis:0.034457726269805755):0.018007681950015227,(Litoria_nudidigita:0.025604555273375684,Litoria_phyllochroa:0.015548872820454456):0.014280383692102955):0.01890052859012551):0.003330853108152096,(Litoria_daviesae:0.009037660614550433,Litoria_subglandulosa:0.010509495677140537):0.01310330935934104):0.014409623041220935):0.009900062677657313):0.002118685549775179):0.012093850784220733,((Nyctimystes_dayi:0.13780883059263938,(Litoria_nannotis:0.05970633991007581,(Litoria_rheocola:0.07163135497074624,Litoria_nyakalensis:0.06785004077392402):0.03636160846090388):0.07105358021970347):0.029243060418526094,(Litoria_impura:0.02267950117003708,Litoria_thesaurensis:0.012092124818657964):0.034048914851252304):0.0071628291544205335):0.030124660633556878):0.011056230486204363):0.029501073490921285,((Cruziohyla_calcarifer:0.09815555873950378,(((Hylomantis_granulosa:0.02608492375289091,Hylomantis_aspera:0.03857206317328182):0.050783944241702135,((Pachymedusa_dacnicolor:0.07441551387995494,((Agalychnis_callidryas:0.04505362098418303,(Agalychnis_saltator:0.024821041035196542,(Agalychnis_moreletii:0.024178224331582097,Agalychnis_annae:0.02488808743929365):0.004319660501354235):0.01273012724529098):0.007401647124112275,(Agalychnis_spurrelli:0.0047006610512522065,Agalychnis_litodryas:7.658974032307748E-4):0.03447120703470747):0.018362602157508595):0.037620547348523034,(Hylomantis_hulli:0.0796776341775924,Hylomantis_lemur:0.07907405015016095):0.004453638069185125):0.009680163598446725):0.021806546637112744,((((Phyllomedusa_vaillantii:0.06455843580365957,Phyllomedusa_bicolor:0.04675860294151334):0.03571300049200244,((Phyllomedusa_camba:0.026016573586426502,((Phyllomedusa_neildi:0.002404251004134906,Phyllomedusa_trinitatis:0.004614815296160126):0.00703694218629372,Phyllomedusa_tarsius:0.012791629341905954):0.016707316882484013):0.02517714445731443,(Phyllomedusa_boliviana:0.053644763494760696,(Phyllomedusa_sauvagii:0.030316871721105094,((Phyllomedusa_iheringii:0.009784135306974712,(Phyllomedusa_distincta:0.00747421652363506,Phyllomedusa_tetraploidea:0.01549691485473276):0.001717503502735943):0.0045934727043037355,(Phyllomedusa_burmeisteri:0.0236311324980803,Phyllomedusa_bahiana:0.015139575318658316):0.0027145346836948054):0.007177179974106005):0.030019551066715333):0.01389212872957288):0.013346152649642808):0.019475270530839226,(((Phyllomedusa_megacephala:0.02922231565153066,(((Phyllomedusa_ayeaye:0.003088447609331768,Phyllomedusa_itacolomi:0.001970236975876284):0.008150221635403784,((Phyllomedusa_oreades:0.002292986346492128,Phyllomedusa_araguari:8.185562508573022E-4):0.0012243796410143684,Phyllomedusa_centralis:0.0029328251296258006):0.006325354151660177):0.023154840739395326,Phyllomedusa_rohdei:0.030195848134738763):0.00892717539548421):0.0218658227739426,(Phyllomedusa_palliata:0.056576090697729946,(Phyllomedusa_nordestina:0.05182490629586304,(Phyllomedusa_azurea:0.012638994584114997,Phyllomedusa_hypochondrialis:0.008394168548140585):0.021648882770542423):0.023122201738099642):0.008647917888463294):0.03258116599575732,(((Phyllomedusa_perinesos:0.04137459687100714,(Phyllomedusa_duellmani:0.011313336050355978,Phyllomedusa_baltea:0.020673359001655203):0.030414711615710115):0.007721434618847831,Phyllomedusa_atelopoides:0.052730041878104504):0.012050576806240871,Phyllomedusa_tomopterna:0.05613331759467005):0.01097668729921957):0.02500477156458158):0.02898100998535745,(Phasmahyla_jandaia:0.03129100372074662,(Phasmahyla_exilis:0.028510988535461374,(Phasmahyla_guttata:0.030095795510006445,(Phasmahyla_cruzi:0.011357621482992684,Phasmahyla_cochranae:0.016712057933195388):0.00814210557108122):0.003481889351695168):0.00753755753970558):0.06399717616774465):0.011172816953440131):0.017934280329618822):0.012769546922376205,Phrynomedusa_marginata:0.11225137354177234):0.06617749867796215):0.041838162787890126):0.012613131206122888,(((Ceratophrys_cornuta:0.03613806271575429,((Ceratophrys_ornata:0.009077893234250099,Ceratophrys_cranwelli:0.008948671727907058):0.04372044746543944,(Lepidobatrachus_laevis:0.026139570963471026,Chacophrys_pierottii:0.030326796774333243):0.016137271830716962):0.009667956581231879):0.06718516589802385,(((Macrogenioglottus_alipioi:0.02818164390492381,(Odontophrynus_carvalhoi:0.03869501214687563,((Odontophrynus_achalensis:3.51422724235035E-6,Odontophrynus_occidentalis:0.0012554442072002158):0.008936357761508126,(Odontophrynus_americanus:0.06420222627793869,Odontophrynus_cultripes:0.03718991890874187):0.011899854874400944):0.009735403875029542):0.015061030874895501):0.04084402026720538,((Proceratophrys_schirchi:0.0720847986420875,Proceratophrys_cristiceps:0.06996229866157981):0.024137068260095266,((((Odontophrynus_moratoi:0.03603495630775375,Proceratophrys_concavitympanum:0.04672282036622399):0.019398631482460262,Proceratophrys_goyana:0.04005485305169007):0.01033026233449211,(Proceratophrys_cururu:0.03319275248316402,((Proceratophrys_renalis:0.009705556244618644,Proceratophrys_boiei:0.02039911024312959):0.029521453183275398,Proceratophrys_laticeps:0.03136735075965229):0.00616319495659699):0.011501175192073826):0.013111551582966417,((Proceratophrys_avelinoi:0.02964941394257297,Proceratophrys_bigibbosa:0.04499104401951081):0.0034690697781631047,(Proceratophrys_appendiculata:0.04667028551459356,Proceratophrys_melanopogon:0.03885152844300143):0.015617827881519313):0.005947849323943252):0.010650983382607773):0.03333464884526228):0.11108871244548173,((((((Telmatobius_marmoratus:0.0013851664440669965,Telmatobius_vilamensis:0.0012699378466683917):3.51422724235035E-6,(((Telmatobius_huayra:4.787580496314803E-4,Telmatobius_hintoni:0.0022167169036956167):0.0030200919372118094,(Telmatobius_gigas:0.0032938391522944507,Telmatobius_culeus:0.0037435065616118588):6.924828559606176E-4):0.009767930049706141,(((Telmatobius_bolivianus:0.009382433532274784,Telmatobius_yuracare:0.004299493683881205):3.51422724235035E-6,Telmatobius_sibiricus:3.51422724235035E-6):0.003420916934207061,Telmatobius_simonsi:0.005480520878066534):0.0030231718725980032):0.009201290255399192):3.51422724235035E-6,(Telmatobius_vellardi:0.006512900251834433,(Telmatobius_truebae:0.006269094691594775,(Telmatobius_espadai:0.010790688694319138,(Telmatobius_sanborni:0.015268041855620192,Telmatobius_verrucosus:0.006296271681006605):0.004262871042092278):0.00610693609237514):0.007148163083502051):0.0031655913235285335):0.004765145510111567,(Telmatobius_niger:0.007364629748102269,Telmatobius_zapahuirensis:0.002309313064028779):0.001648877306637931):0.07385980769161064,(((Atelognathus_patagonicus:3.51422724235035E-6,Atelognathus_jeinimenensis:0.0038545498709412224):0.06134127680948582,Batrachyla_leptopus:0.07079061149891001):0.028601364868466272,(Rhinoderma_darwinii:0.13457079722711204,Insuetophrynus_acarpicus:0.08660945588091974):0.043957870849416145):0.01235127091865454):0.011727877661703794,(((Thoropa_taophora:0.07454798367018965,Thoropa_miliaris:3.51422724235035E-6):0.12869220011360527,(Cycloramphus_acangatan:0.08010238935596282,Cycloramphus_boraceiensis:0.13624005901888522):0.03835587559134866):0.02424530120079612,(((Crossodactylus_schmidti:0.0758690302185362,Crossodactylus_caramaschii:0.14606398986416746):0.06191127429726985,(((Hylodes_phyllodes:0.08119164082464317,(Hylodes_ornatus:0.040012705742772214,Hylodes_sazimai:0.10549256722170872):4.1933504884486685E-4):0.026868581489096585,((Hylodes_perplicatus:0.06657494774962242,Hylodes_meridionalis:0.13415371092013242):0.012655609670331152,Megaelosia_goeldii:0.11804826517259605):0.006489129894963895):0.017251638641262924,Hylodes_dactylocinus:0.06905156353313281):0.02950199893850203):0.04574189587267483,(((Alsodes_vanzolinii:0.05259760330437458,((((Alsodes_gargola:0.0035432972004097807,((Alsodes_barrioi:3.51422724235035E-6,Alsodes_monticola:0.04134023505294098):0.00899625800260791,Alsodes_kaweshkari:0.002852472163012146):1.6455004301288523E-4):0.0015505032792154565,Alsodes_tumultuosus:0.008275152474787087):0.015333385147203194,Alsodes_australis:0.024850813473693306):0.013128205415688108,Alsodes_nodosus:0.03589325075776888):0.007046397152146476):0.021065813786167755,((Eupsophus_vertebralis:0.04529272141443695,(((Batrachyla_antartandica:0.018274483319260633,Batrachyla_taeniata:0.010024872308791982):0.00915343037228995,Hylorina_sylvatica:0.05475511901692668):0.046603269582611594,(Eupsophus_migueli:0.009606253627597637,((Eupsophus_insularis:0.039914413338250444,(Eupsophus_contulmoensis:7.661435204728503E-4,Eupsophus_roseus:0.024616371439993612):7.476613497917772E-4):0.005939718490800906,Eupsophus_nahuelbutensis:0.011320044569096289):5.386748131368859E-4):0.04788335270500874):0.02715489978290897):0.020069253566808345,(Eupsophus_calcaratus:0.07664775937621637,Eupsophus_emiliopugini:0.03719022497968215):0.015349259427558389):0.01287313509918609):0.04174018397768186,Limnomedusa_macroglossa:0.17086937699716856):0.019995226096830673):0.007618183824771823):0.0092833418721629):0.006923600181919994):0.007093996021758073):0.010407750498207578,((((Melanophryniscus_rubriventris:0.032692512507585245,(Melanophryniscus_klappenbachi:4.5279378458206886E-4,Melanophryniscus_stelzneri:0.0018820397875231702):0.019833518866927212):0.13025748266429532,((((Atelopus_pulcher:0.016031108899557854,Atelopus_seminiferus:0.015424592594026933):0.002239809022146863,(Atelopus_spumarius:0.006462204042810626,(Atelopus_flavescens:0.0017528494791394836,Atelopus_franciscus:0.004610279158898515):0.00557585656607087):0.02434483051245446):0.0898325360196078,(((Atelopus_halihelos:0.04239654984254127,(Atelopus_bomolochos:3.51422724235035E-6,Atelopus_ignescens:3.51422724235035E-6):0.005075189889002372):0.06432513122656398,Atelopus_peruensis:0.015112866701627418):0.007699434689127103,(Atelopus_longirostris:0.054050431384153275,(Atelopus_spurrelli:0.021705696267357934,((Atelopus_zeteki:0.006154978029352232,(Atelopus_senex:0.001863928401202361,Atelopus_varius:0.00137062184936457):0.014478587393909772):0.0022256524467235772,Atelopus_chiriquiensis:0.027200091263327154):0.010443084304517703):0.016545078816202555):0.012127097284366424):0.048977608817929875):0.07203952542535942,((((Bufo_cophotis:0.05722072483166054,Bufo_variegatus:0.06093551009786204):0.01418331184824967,(((Bufo_nasicus:0.11539097913871182,(Bufo_haematiticus:0.04429800976985988,(Bufo_guttatus:0.04931653357095753,Bufo_glaberrimus:0.050954081255525675):0.008376995921398983):0.028084850668405614):0.016832646799778264,((((Bufo_bocourti:0.02886336563338535,((Bufo_alvarius:0.03716856970392498,(Bufo_tacanensis:0.039114501488882326,Bufo_occidentalis:0.041527655128804276):0.01636450199966099):0.006272305215552939,(((((Bufo_macrocristatus:0.003494885343496044,Bufo_campbelli:0.008816122192571326):0.01687795072301057,Bufo_valliceps:0.01803265204447068):0.024462660914225182,Bufo_nebulifer:0.023729719195384146):0.0016443323502171267,((Bufo_mazatlanensis:0.014806847241062003,Bufo_luetkenii:0.02053979975845164):0.011372232577721209,Bufo_melanochlorus:0.042228009751398225):0.003322569922349261):0.026790073597188078,((Bufo_marmoreus:0.0016452715291759962,Bufo_canaliferus:9.70705205328033E-4):0.043549431538884,((Bufo_coniferus:0.033916801770037516,Bufo_fastidiosus:0.03389694630725212):0.009346894492699142,(Bufo_coccifer:0.0093828764543821,(Bufo_ibarrai:0.0027304259539068913,Bufo_cycladen:0.0244796141484704):0.004528582140581156):0.016837135583771923):0.0174776483481383):0.008076235311602205):0.005108290002252816):0.010200676027970514):0.00494032586175612,(((Bufo_canorus:0.005050303711254472,Bufo_exsul:0.0031279270518507693):0.002074432589120455,(Bufo_boreas:8.697721467069019E-4,Bufo_nelsoni:3.51422724235035E-6):0.0035328103033443778):0.044815410473806334,(Bufo_punctatus:0.05613660965058804,(Bufo_quercicus:0.062350480093696925,((Bufo_cognatus:0.021695448801152272,(Bufo_retiformis:0.03394852125991759,Bufo_debilis:0.01517223659600371):0.03498527485989144):0.026141554092446357,(Bufo_speciosus:0.040239788641984825,(Bufo_californicus:0.01702955281887372,(Bufo_microscaphus:0.0151077019474192,((Bufo_fowleri:0.004102706663824389,Bufo_terrestris:0.004781286272119375):0.003002573350290433,(((Bufo_hemiophrys:0.0026655300130648558,(Bufo_houstonensis:0.0031524842322289146,Bufo_americanus:0.002871562560062652):0.001749034817318444):0.010079394818471005,Bufo_baxteri:3.51422724235035E-6):0.002866934124259839,Bufo_woodhousii:0.004207232145697754):0.0037507398823559282):0.008708745054880897):0.006272132478439544):0.012670513970129473):0.0051660525038307184):0.011225363753515):0.012030085650514142):0.024760322680522646):0.012245955000451674):0.007625717319138981,(((Bufo_arequipensis:0.002850603186812518,Bufo_spinulosus:0.002618970820606951):0.023828036592305896,(Bufo_vellardi:0.01804811523891159,Bufo_limensis:0.029720698343184287):0.026121569182632524):0.010827423776578053,((Bufo_arunco:0.012076151490106971,Bufo_atacamensis:0.011119795235555535):0.03148012350038279,(((Bufo_amboroensis:0.007504043751086878,Bufo_veraguensis:0.030522326413154825):0.039956836254298696,(((Bufo_castaneoticus:0.016339302950800657,(Bufo_margaritifer:0.02705561057005578,Bufo_dapsilis:0.01866644885519367):0.005139359274706951):0.008445716112829973,Bufo_ocellatus:0.05268764302747828):0.04304110740494754,((Bufo_chavin:0.08679596912554306,(Bufo_manu:0.03539022866726009,Bufo_nesiotes:0.017256301475292782):0.031927364438248916):0.00813974349507255,((Rhamphophryne_rostrata:0.011243775308956483,Rhamphophryne_macrorhina:0.04891019355767394):0.04610064787743091,Rhamphophryne_festae:0.031843036827119905):0.018648166086762723):0.030369737526588447):0.024901278809507932):0.017042214486054245,((Bufo_beebei:0.0140315691508308,Bufo_granulosus:0.05484403705398744):0.037696688978936396,((Bufo_poeppigii:0.01727489854788973,(Bufo_schneideri:0.010338715927045527,Bufo_marinus:0.013454582636028601):0.007757841574199154):0.006474539088091969,(Bufo_achavali:0.019132293377386332,(Bufo_crucifer:0.037944862558633954,(Bufo_ictericus:0.011094851513558622,Bufo_arenarum:0.008226624183109264):0.0043708847760716854):0.007875469933921684):0.002364638641584057):0.02849114263224881):0.01150441210998491):0.00446545856872111):0.004313676586897462):0.014560467425272795):0.010806377117800527,((((Bufo_angusticeps:0.010511552181734378,((Bufo_gariepensis:0.005034307953950188,Bufo_robinsoni:0.004424876229418803):0.002337366416487367,(Bufo_amatolicus:0.013145500409985559,Bufo_inyangae:0.02622927985663118):0.0028335664381897107):0.0023303147303946063):0.05368869049046118,((Bufo_fenoulheti:0.032124959364181256,(Bufo_damaranus:0.009585574334024485,Bufo_dombensis:0.006041547531228596):0.0445792328461243):0.02225480183675282,((Bufo_taitanus:0.06098696081915432,Bufo_uzunguensis:0.02741733456420775):0.020236671497847518,(Mertensophryne_micranotis:0.08101571334713926,(Bufo_lindneri:0.035666814500864584,(Stephopaedes_loveridgei:0.011877866733842583,Stephopaedes_anotis:0.009240705418612752):0.024331715832353586):0.027962484752983532):0.010998430427610994):0.03106867432914418):0.015814667241840826):0.017869449846398443,((Capensibufo_rosei:0.021599348916693364,Capensibufo_tradouwi:0.021922214749252454):0.025543313449905677,(Bufo_mauritanicus:0.04670864873266676,((Bufo_steindachneri:0.046867048247811585,((Bufo_poweri:0.03140222603935601,(Bufo_lemairii:0.07029759761786457,Bufo_brauni:0.030029946218440885):0.005594675465191006):0.007857267412673702,(Bufo_pantherinus:0.008010158015172502,Bufo_pardalis:0.004466111953613089):0.0588380322127675):0.010530759261036839):0.008519071167103345,((((Bufo_latifrons:0.10671165434114795,Bufo_vertebralis:0.13674066518457):0.02517249397554653,Bufo_maculatus:0.04131516318313236):0.017242287310696793,Bufo_regularis:0.03343868301179742):0.014722401848479106,((((Bufo_xeros:0.0034668501354189157,Bufo_garmani:0.003555917037548212):0.013262578876285413,Bufo_gutturalis:0.019673665022177197):0.041109256763319994,(Bufo_camerunensis:0.02559239540043749,(Bufo_gracilipes:0.02597712715671881,Bufo_kisoloensis:0.024891279837972218):0.005434929248926394):0.014280427060678783):0.004887825000113892,Bufo_tuberosus:0.07122987496745166):0.006347779776705535):0.01333620311132869):0.010354867839743124):0.024089142738012825):0.004498685488187115):0.011323475882021577,((((Bufo_calamita:0.056770126363139835,Leptophryne_borbonica:0.09043555255245754):0.007187276239014535,(((Bufo_bufo:0.011526975560122993,Bufo_verrucosissimus:0.0028641532061210754):0.03572725758196636,((Bufo_tuberospinius:0.031901339670982284,(Bufo_aspinius:0.013879541198943392,Bufo_cryptotympanicus:0.03142783088046694):0.010533562491968506):0.016483659823049373,((Bufo_bankorensis:0.001767316345398933,Bufo_gargarizans:0.001466153036705784):0.02320623741520447,(((Bufo_tibetanus:8.381419469504079E-4,Bufo_tuberculatus:0.0031423222461151807):0.007981917953226303,Bufo_stejnegeri:0.01567565040449508):3.51422724235035E-6,(Bufo_japonicus:0.015465503854860979,Bufo_torrenticola:0.016086063305685412):0.014860161455565365):0.004773935730047963):0.013671546744012138):0.0174416189616552):0.03214971070145065,Bufo_raddei:0.06062043566595881):0.006022532795766231):0.0020704899913979893,(((((Schismaderma_carens:0.11513164651111465,(((Bufo_pewzowi:3.51422724235035E-6,Bufo_oblongus:3.51422724235035E-6):0.007029694324231469,(Bufo_balearicus:0.01616476781058988,(Bufo_variabilis:0.0628644695912647,Bufo_viridis:3.51422724235035E-6):0.0032976785449541804):0.004063281797366015):0.010242423861759498,(Bufo_siculus:0.011698235559480064,Bufo_boulengeri:0.006551802211670881):0.008661718088888768):0.02420217821446468):0.0041602517828711345,((Churamiti_maridadi:0.10074769260210611,(Nectophrynoides_viviparus:0.02910280597360838,(Nectophrynoides_minutus:0.019270468403189803,Nectophrynoides_tornieri:0.021473000796487327):0.01682306097959018):0.02754390768878825):0.03527324806883293,((Adenomus_kelaartii:0.06626598167819472,((Bufo_crocus:0.0368298705002286,(Bufo_stuarti:0.03583311970340836,(Bufo_himalayanus:0.030622016117490228,((Bufo_melanostictus:0.022247290587932858,(Bufo_parietalis:0.020241613941745142,Bufo_brevirostris:0.022731969687523394):0.011382004660885297):0.014786631292561444,(Bufo_atukoralei:0.012665565108421031,Bufo_scaber:0.014559373937517099):0.05020655549149093):0.006211657337535103):0.01251746237630834):0.004371057951931543):0.0127006655258339,(Bufo_koynayensis:0.04824122863552666,(Bufo_dhufarensis:0.022978597273744572,(Bufo_stomaticus:0.02162368519017542,Bufo_hololius:0.0271065523435876):0.009834455188160998):0.024259447756919343):0.007167943664184754):0.005182739255605597):0.013619818570342576,Bufo_brongersmai:0.07800725857768395):0.005628226119415525):0.011207981650810665):0.0026225746801750633,Pedostibes_tuberculosus:0.07675436999252466):0.002759754687061663,((Sabahphrynus_maculatus:0.07689922577553512,((Bufo_divergens:0.0459558626102961,Bufo_biporcatus:0.04986484371368735):0.014825553534774277,(Bufo_celebensis:0.07000587016290774,(Bufo_macrotis:0.03953329047224673,(Bufo_galeatus:0.0233523486832157,Bufo_philippinicus:0.057797537636926195):3.51422724235035E-6):0.014503707755401904):3.51422724235035E-6):0.02814982558675198):0.010290456219345462,((Didynamipus_sjostedti:0.1805257543244311,Ansonia_ornata:0.07316775021178822):0.014874011299774479,((Pelophryne_misera:0.04777732621942599,(Pelophryne_signata:0.027711182835588825,Pelophryne_brevipes:0.050628879860963795):0.029063690515064262):0.06279798444979241,(((Ansonia_spinulifer:0.07460008747087658,(Ansonia_hanitschi:0.04124408648296938,(Ansonia_minuta:0.07008149200511128,Ansonia_platysoma:0.04739392000363228):0.01397623035828886):0.00805646404182949):0.017129354882160636,Ansonia_malayana:0.06933882235629303):0.011967721584486142,((Ansonia_longidigita:0.03986302387662909,Ansonia_leptopus:0.026173799819281196):0.030844697075349523,(Ansonia_muelleri:0.09206019558935007,Ansonia_fuliginea:0.041010531525225595):0.01527471755436923):0.014786996966508268):0.01623374092247194):0.007144659257876144):0.005426343183304365):0.002650847832740423):0.0034605394360525434,((Pedostibes_hosii:0.05874308419346553,Pedostibes_rugosus:0.06030164300396269):0.012917210782720064,(Bufo_juxtasper:0.04079783589567688,Bufo_asper:0.018289582998953165):0.03532290119901755):0.015550609385830232):0.002500796631886784):0.005600139324883039,((Nectophryne_batesii:0.07542741664250335,Nectophryne_afra:0.07116787368322364):0.09475922728396335,(Wolterstorffina_parvipalmata:0.07779405796876845,Werneria_mertensiana:0.0466870699324515):0.009758705642554):0.017196063430996163):0.0025957860840157233):0.005230498861744707):0.013649183485875663):0.0061641100536997855,(Bufo_lemur:0.060117313049854,(Bufo_guentheri:0.03792154708948311,((Bufo_taladai:0.02430495767212748,(Bufo_empusus:0.02329846725353938,(Bufo_peltocephalus:0.016902401402183784,Bufo_fustiger:0.016976075542969603):0.0055336332385465615):0.007988001024518097):0.0024917434107672417,(Bufo_gundlachi:0.035704290698983406,Bufo_longinasus:0.041375822894154736):0.007229480362105909):0.010006649292177958):0.013262029385925455):0.03964049593594135):0.017128990984820604):0.023688606375698383,Dendrophryniscus_minutus:0.2134125068872137):0.015203012505430434,(Osornophryne_sumacoensis:0.04652231418125524,((Osornophryne_puruanta:0.019782552573324605,(Osornophryne_bufoniformis:0.02376004114267259,Osornophryne_antisana:9.074925738563241E-4):0.01070722867133915):0.02473986420142023,Osornophryne_guacamayo:0.03953935634383084):0.01781520939303277):0.09227984905011206):0.014230860075943486):0.035769860484872566):0.036890620340665425,(((((Silverstoneia_flotator:0.055303654370186854,Silverstoneia_nubicola:0.06932872421084643):0.01286178815401919,((((Epipedobates_tricolor:0.004615786025432817,Epipedobates_anthonyi:0.005158562387866989):0.0028518241301736676,Epipedobates_machalilla:0.006588996599739428):0.005002747915479419,Epipedobates_espinosai:0.011221889280646198):0.003146127934906297,Epipedobates_boulengeri:0.007694711109451382):0.044839377541384874):0.03779302875494361,(((Colostethus_pratti:0.019425276839611372,Colostethus_latinasus:0.02076524929287546):0.04398603904250736,(Colostethus_imbricolus:0.024636729970494917,(Colostethus_panamansis:0.01826108849436824,Colostethus_inguinalis:0.027584918681676472):0.013079239012766286):0.028054613310406613):0.036108434728461716,(((Colostethus_argyrogaster:0.03255026858862315,Colostethus_fugax:0.025550961806889143):0.050149745757403184,Colostethus_fraterdanieli:0.06525085355937725):0.01741887966553313,(Ameerega_altamazonica:0.022028410218298006,(Ameerega_simulans:0.020854381842686467,(Ameerega_silverstonei:0.02401601964526445,((((((Ameerega_picta:0.02286183398202294,Ameerega_hahneli:0.02579844095909239):0.008705546009389322,Ameerega_pulchripecta:0.03302761252386399):0.00337693050724309,((Ameerega_smaragdina:0.01685688859703034,(Ameerega_petersi:0.004128777685219303,Ameerega_cainarachi:0.0068764072748653295):0.003789969131901042):0.015619515661063501,(Ameerega_rubriventris:0.023862685082138655,Ameerega_macero:0.024790607438423602):0.00552042083204409):8.061332465179214E-4):0.0018361192042004337,Ameerega_trivittata:0.038992016991518094):0.0023178182887200307,((Ameerega_pongoensis:0.017391663095354018,Ameerega_parvula:0.01907270270037936):0.009678703466681671,(Ameerega_bilinguis:0.024024992702560376,Ameerega_bassleri:0.02181361631629867):0.0037570978264921382):0.006139675966544381):0.0049774387568020445,(Ameerega_braccata:0.02703694131975898,Ameerega_flavopicta:0.01764989642222455):0.014722010512862174):0.0038219674011778022):0.01512955163274436):0.00522339030937326):0.0435543758507863):0.014024921064198349):0.013952989935557521):0.051935517165322506,((((Hyloxalus_maculosus:0.05631564972178448,(Hyloxalus_sauli:0.033403884930405095,Hyloxalus_bocagei:0.03793027436418336):0.023502843810224935):0.058624719505031696,(Hyloxalus_subpunctatus:0.08627692319280575,(Hyloxalus_sordidatus:0.04066967353708531,Hyloxalus_leucophaeus:0.03821972401839939):0.05608449061805898):0.01555932795505781):0.012807430938676648,((((Hyloxalus_sylvaticus:0.026168558795987555,Hyloxalus_pulcherrimus:0.03145609223150635):0.03993580103141442,Hyloxalus_anthracinus:0.06739466298662343):0.01635559373950928,(Hyloxalus_vertebralis:0.03665411799636095,(Hyloxalus_delatorreae:0.02896787009352828,Hyloxalus_pulchellus:0.024383782885566946):0.02486300411144401):0.04598927224778948):0.008203985743032815,(((Hyloxalus_chlorocraspedus:0.04120170854375162,Hyloxalus_azureiventris:0.042809150225327774):0.07717378397385447,(Hyloxalus_idiomelus:0.0371095642658887,((Hyloxalus_elachyhistus:0.02862254802338681,((Hyloxalus_awa:0.010443847333193703,Hyloxalus_toachi:0.004942016410494292):0.03359183902478802,Hyloxalus_infraguttatus:0.028230843886944176):0.017557797452433247):0.008885540084620131,Hyloxalus_insulatus:0.038513072639815626):0.008800812957381268):0.02473536480813142):0.014912581368384824,Hyloxalus_nexipus:0.09981758605258262):0.007417519185640861):0.024694784225579625):0.022135177097644964,(((Phyllobates_lugubris:0.012526064939148738,Phyllobates_vittatus:0.010737668426680943):0.031070886117725714,((Phyllobates_terribilis:0.01409093613627416,Phyllobates_aurotaenia:0.01118637636090046):0.007414956315180807,Phyllobates_bicolor:0.007043103743700595):0.045257055763667925):0.06166685178776979,((((Dendrobates_histrionicus:0.010669302382608136,((Dendrobates_lehmanni:0.00674810184496799,Dendrobates_sylvaticus:0.0062922636552138365):0.004773542565880907,(Dendrobates_arboreus:0.009557639716580742,((Dendrobates_pumilio:0.007624352302532114,Dendrobates_vicentei:0.00616555899120694):0.002945360163749692,Dendrobates_speciosus:0.015723231808026202):0.0036069183758904296):7.593565695821145E-4):0.001986923234911104):0.0170914976401243,Dendrobates_granuliferus:0.03852322374082942):0.06441981230375768,(((Dendrobates_leucomelas:0.035294006366071225,Dendrobates_tinctorius:0.036713347591506684):0.009053563801969809,(Dendrobates_auratus:0.00934522697558239,Dendrobates_truncatus:0.008151720307380988):0.02509660158544239):0.04379587683529684,(Dendrobates_steyermarki:0.0817658140902585,(Dendrobates_quinquevittatus:0.060677967166014674,(Dendrobates_galactonotus:0.10920065502715741,Dendrobates_castaneoticus:0.06116234354054787):0.018800000768934757):0.04386017427758397):0.014947237748613377):0.005675963211465776):0.019835822636516225,((Dendrobates_mysteriosus:0.10785185959800003,Dendrobates_captivus:0.07973529502073967):0.02588626028985613,(((Dendrobates_bombetes:0.006029364933369441,Dendrobates_virolinensis:0.00898768014691295):0.026507272579288765,(Dendrobates_fulguritus:0.06998041257826623,(Dendrobates_claudiae:0.024922178126946207,Dendrobates_minutus:0.025259738555372894):0.018090577686241095):0.00938453576416118):0.021497964025289883,(((Dendrobates_flavovittatus:0.08168902769825494,(Dendrobates_vanzolinii:0.01423775525510062,Dendrobates_imitator:0.011438508750893736):0.012283569440985494):0.021062025675748587,(Dendrobates_biolat:0.045570200026272434,Dendrobates_lamasi:0.041580020519626885):0.032585112354792334):0.013086372408656025,((Dendrobates_fantasticus:0.022566769595468206,(Dendrobates_reticulatus:0.009953038996422501,Dendrobates_duellmani:0.02345615074631477):0.013293217580619784):0.006058386735409437,((Dendrobates_ventrimaculatus:0.02535165031172395,Dendrobates_variabilis:0.045585792249575326):0.009917957954615695,(Dendrobates_amazonicus:0.036799499056233136,Dendrobates_uakarii:0.13619103143432096):0.0026503472653926666):3.51422724235035E-6):0.024082985467513276):0.024087524305598334):0.014474242659245663):0.0398217516675515):0.02527861772284386):0.024742458674670727):0.00887477635239191):0.013559343031416047,((((Anomaloglossus_baeobatrachus:0.0859222634904571,Anomaloglossus_stepheni:0.08534747460189027):0.01758217104787297,((Anomaloglossus_degranvillei:0.02622158191060663,(Anomaloglossus_praderioi:0.023281718440772436,(Anomaloglossus_beebei:0.014778849406614407,Anomaloglossus_roraima:0.012687094409217595):0.020087244689496114):0.008116784620919847):0.04893241597616057,Anomaloglossus_tepuyensis:0.06586008793880392):0.01295013315710014):0.04725450250231378,(((Aromobates_nocturnus:0.04937769775345741,Aromobates_saltuensis:0.071028227085726):0.048194413449635,(Mannophryne_herminae:0.05677879995701413,(Mannophryne_venezuelensis:0.017218954355876333,Mannophryne_trinitatis:0.003646674153883777):0.05091407708135315):0.05165384129227876):0.026924479241169887,(Allobates_undulatus:0.07269107855286412,(Allobates_talamancae:0.09104118110282529,(((Allobates_femoralis:0.03471848731470027,Allobates_zaparo:0.04718883413224317):0.07008936274296504,(Allobates_juanii:0.09070765423930233,(Allobates_gasconi:0.07929897247149258,(Allobates_caeruleodactylus:0.08107144856144705,(Allobates_conspicuus:0.07048860019693877,Allobates_trilineatus:0.07860895328846014):0.01673852442681091):0.00210629120725903):0.014656201279038444):0.013521370496367615):0.011729436173093912,(Allobates_nidicola:0.09947493388040214,Allobates_brunneus:0.0666092363579752):0.013131402886282234):0.02005493419788982):0.018138448469391152):0.0375093202969811):0.008795364532609049):0.008468660364432275,Rheobates_palmatus:0.11329892913808905):0.03273761463675823):0.10596716661374998):0.012365485494094969,((Allophryne_ruthveni:0.11152868856652456,(((Celsiella_revocata:0.01609303573368861,Celsiella_vozmedianoi:0.030593406476930336):0.011662686550691144,(((Hyalinobatrachium_aureoguttatum:0.013222588628962527,Hyalinobatrachium_valerioi:0.017705708181814238):0.02599841603376089,((Hyalinobatrachium_pellucidum:0.024686690800648024,Hyalinobatrachium_bergeri:0.017666013912162257):0.0026107549597451656,((Hyalinobatrachium_colymbiphyllum:0.013425718827453852,Hyalinobatrachium_chirripoi:0.02056928758707436):0.020375096181281666,Hyalinobatrachium_talamancae:0.049538140748270995):0.0047826485057844755):0.020714263163889884):0.0066549245589961285,(((Hyalinobatrachium_taylori:0.015575207932614753,(Hyalinobatrachium_ignioculus:0.008759773895243093,(Hyalinobatrachium_crurifasciatum:0.0024450428831757987,Hyalinobatrachium_eccentricum:5.807877564869934E-4):3.51422724235035E-6):0.011315906221012752):0.03071749691651609,((((Hyalinobatrachium_fleischmanni:0.014017681287784972,Hyalinobatrachium_tatayoi:0.010020202148117267):0.01294962687300945,Hyalinobatrachium_carlesvilai:0.028706676188409507):0.0063937313243865816,Hyalinobatrachium_mondolfii:0.030817186319666352):0.010252090337500037,(((Hyalinobatrachium_ibama:0.007332249226522418,Hyalinobatrachium_pallidum:0.004795937157754465):0.007580165078542383,Hyalinobatrachium_duranti:0.013520263319100683):0.013546724804783944,(Hyalinobatrachium_orientale:0.028007612556128644,(Hyalinobatrachium_orocostale:0.024219360788871002,Hyalinobatrachium_fragile:0.030749786300596254):0.013050622548373062):0.00615705010217134):0.007598694187449246):0.005584848533849559):0.00423829914966018,Hyalinobatrachium_iaspidiense:0.06370098881602808):0.003156603273926271):0.008182055531928507):0.02513401005583242,(Ikakogi_tayrona:0.04526104649540783,((((Centrolene_daidaleum:0.021374353025238074,((Centrolene_antioquiense:0.003247256325682715,Centrolene_peristictum:0.010211271344075713):0.014535151873918432,Centrolene_savagei:0.027427124223716067):0.0030449403391114883):0.024275792863942477,((Centrolene_bacatum:0.010066088285040097,((Centrolene_pipilatum:0.008740295993879576,Centrolene_hybrida:0.013457991785637752):0.007489676740527603,((Centrolene_hesperium:0.016574531825452973,Centrolene_venezuelense:0.00661611465962284):0.004337820774403978,((Centrolene_altitudinale:0.002547342049125784,Centrolene_notostictum:0.00669613256368246):0.004548520758163592,Centrolene_buckleyi:0.015214548226220362):0.004456780960538418):0.0118205283528816):0.004324129242463712):0.018043549119093623,Centrolene_geckoideum:0.04929147647544251):0.002378834726643774):0.013699363049750586,((Centrolene_grandisonae:0.032295343426499284,(Nymphargus_mixomaculatus:0.02709538075437444,(Nymphargus_pluvialis:0.02366174321756368,(Nymphargus_posadae:0.009118501937902873,Nymphargus_bejaranoi:0.016650941691729895):0.008304604090429659):0.016738273863902742):0.011297535151313374):0.0037208460111033455,(Nymphargus_griffithsi:0.019241452258991907,(((Nymphargus_puyoensis:0.02725796531539663,Nymphargus_cochranae:3.51422724235035E-6):0.02321180123767432,((Nymphargus_siren:0.02381016943013128,Nymphargus_megacheirus:0.027451337407359378):0.003327428136555056,Nymphargus_rosadus:0.02451108575385118):0.009382391644132968):0.005099851054883919,Nymphargus_wileyi:0.01541168743205522):0.0017326831945287256):0.012661814436098547):0.02085263393388909):0.013487439877927896,(((Vitreorana_gorzulae:0.06587695921775236,(Vitreorana_helenae:0.017811221767933885,Vitreorana_oyampiensis:0.04084399532940378):0.04680114399326156):0.008477553707087021,((Vitreorana_castroviejoi:0.03032661154766493,Vitreorana_antisthenesi:0.026056935089300206):0.015189866162415468,Vitreorana_eurygnatha:0.044592685331249826):0.004797882417678357):0.016949558875937767,((((Cochranella_nola:0.04477671782626518,Cochranella_litoralis:0.0355134470782018):0.001861988561009625,(Cochranella_granulosa:0.025851855628954517,(Cochranella_euknemos:0.017450110900628837,Cochranella_mache:0.01270764314659158):0.013507207747152824):0.008666259601845536):0.01687155067457123,((Chimerella_mariaelenae:0.04603481313411031,(Espadarana_andina:0.0166914073277476,(Espadarana_prosoblepon:0.013305789716468244,Espadarana_callistomma:0.012983137965557312):0.00965483309546315):0.018842185306211772):0.006535810141132384,(((Sachatamia_albomaculata:0.036248289817457945,Sachatamia_punctulata:0.042798052734449143):0.02006163193586734,Sachatamia_ilex:0.03664278595641977):0.020342782151933074,((Rulyrana_adiazeta:0.004536337650972919,Rulyrana_susatamai:0.004142065497211397):0.014768416263116008,(Rulyrana_spiculata:0.0360960424517822,Rulyrana_flavopunctata:0.015908154843103598):0.007835848321050573):0.022432340729553066):0.006605330328970433):0.0028366172982660816):0.0057733364102071465,(Teratohyla_pulverata:0.0545784808039149,(Teratohyla_spinosa:0.044424843526565845,Teratohyla_midas:0.029883112967295185):0.01549684936201724):0.006313034907289459):0.0048286381262687365):0.01324700054762855):0.00572253683830721):0.006018843256886959):0.047090418901243186):0.04106734955930309,(((Lithodytes_lineatus:0.14238545666905594,(Adenomera_andreae:0.07541462473669305,(Adenomera_heyeri:0.037309801816817006,Adenomera_hylaedactyla:0.09907265936641364):0.006545028807660631):0.04177588345922579):0.031018362652977544,(((Leptodactylus_rhodonotus:0.0703637164754621,((Leptodactylus_vastus:0.059623917052910876,(Leptodactylus_fallax:0.05880075618954103,(Leptodactylus_labyrinthicus:0.03988738559123628,(Leptodactylus_pentadactylus:0.03293190056058129,Leptodactylus_knudseni:0.031086381280264638):0.006884080932044118):0.004090056895623057):0.028136022976751544):0.04305954094152336,(Leptodactylus_mystacinus:0.06697257949726261,((Leptodactylus_spixi:0.051757131479486476,(((Leptodactylus_mystaceus:0.024331620935158272,Leptodactylus_didymus:0.030358910477826368):0.02186424597219784,Leptodactylus_notoaktites:0.017824650353451953):0.016833341199706735,Leptodactylus_elenae:0.039903702141068156):0.0036928480352525195):0.020832261120601794,(((Leptodactylus_plaumanni:0.06304561126928727,Leptodactylus_gracilis:0.027393128843161467):0.03849889778456861,Leptodactylus_albilabris:0.032492262463195185):0.02208191631467067,((Leptodactylus_longirostris:0.0277112647783201,Leptodactylus_bufonius:0.05235698730183425):0.011035348406451557,Leptodactylus_fuscus:0.04741723454734374):0.018219968278712136):0.00886516353412062):0.00662275484894161):0.017531818502784485):0.010088328503630867):0.005147197773882487,(Leptodactylus_silvanimbus:0.09653348076204979,((Leptodactylus_ocellatus:0.03860421154583127,Leptodactylus_chaquensis:0.02296036849019839):0.027411112854985018,((Leptodactylus_riveroi:0.15933634495231636,((((Leptodactylus_griseigularis:0.04671156205189437,Leptodactylus_discodactylus:0.08656041476215345):0.02633486838560379,Leptodactylus_diedrus:0.09157532278514616):0.002182577611906324,Leptodactylus_podicipinus:0.04387095843317276):0.021942872376200528,(Leptodactylus_melanonotus:0.05049506155793228,(Leptodactylus_wagneri:0.03780272698031401,(Leptodactylus_validus:0.00466417657669036,Leptodactylus_pallidirostris:0.0019371379563122568):0.046820785047285714):0.017517106959827267):0.02281582372300136):0.02251442063192455):0.01754844315514898,Leptodactylus_leptodactyloides:0.0685867523616035):0.014384970411316297):0.01414862013323839):0.018612320861256266):0.01085178876845509,Leptodactylus_rhodomystax:0.10896209122124292):0.04779335206713504):0.04736974913420395,((Pseudopaludicola_falcipes:0.2144744285312883,(((Pleurodema_marmoratum:0.04467969573496102,((Pleurodema_bibroni:0.006890057565841716,Pleurodema_thaul:0.003543117268097068):0.015997153640256227,Pleurodema_bufoninum:0.030517741578152412):0.015643621116278063):0.03115075395440104,Pleurodema_brachyops:0.12618698510072543):0.01898993544909032,(Edalorhina_perezi:0.16633314111199857,(((Physalaemus_signifer:0.05119760507247719,Eupemphix_nattereri:0.06895151082130317):0.025889349789372955,(((Physalaemus_ephippifer:0.014425599741156812,Physalaemus_cuvieri:0.0362393532771404):0.0321045168054548,Physalaemus_albonotatus:0.06930367559957314):0.055699588169452946,((Physalaemus_barrioi:0.06561125042303063,Physalaemus_gracilis:0.03779852571551652):0.01628280336938942,(Physalaemus_riograndensis:0.04344982786905663,Physalaemus_biligonigerus:0.060144398346484294):0.009784306910638004):0.02391970492497865):0.02316967405237195):0.02278028004424988,((Engystomops_pustulosus:0.1055639876141047,(Engystomops_freibergi:0.053698327037864514,Engystomops_petersi:0.025222475206454115):0.06813758904068738):0.023484006592728677,(Engystomops_pustulatus:0.056047253894577,((Engystomops_randi:0.01616805680940282,Engystomops_montubio:0.012275608075603685):0.01323841941081588,(Engystomops_coloradorum:0.032642129877113096,Engystomops_guayaco:0.032074140904663734):0.009728020779729814):0.03322276880024754):0.032804221817219974):0.05188386448976904):0.015882982004018095):0.025851453724769966):0.03915926534422007):0.009801444922947874,(Scythrophrys_sawayae:0.06675644766607394,(Paratelmatobius_cardosoi:0.09052555831115587,(Paratelmatobius_gaigeae:0.03571466191495177,Paratelmatobius_poecilogaster:0.04047624006044407):0.017788629221066256):0.031099561351021144):0.0643934233860418):0.011562489161952644):0.01613515502138833):0.008680017037020926):0.004294998495778566):0.004273658807898904):0.01401139494915083):0.006953101744475173,(Ceuthomantis_smaragdinus:0.3200543432485569,((((Ischnocnema_holti:0.19149971785195122,Ischnocnema_juipoca:0.1525677876450708):0.021947536299241364,((Ischnocnema_hoehnei:0.128548097349216,Ischnocnema_guentheri:0.12510673685648538):0.017088073270099254,Ischnocnema_parva:0.14405263804749774):0.028981914391021896):0.06921737658566672,Brachycephalus_ephippium:0.2663210235377419):0.025033237319138757,(((Diasporus_diastema:0.20629472927887713,((((Eleutherodactylus_marnockii:0.0846497621130333,(Eleutherodactylus_pipilans:0.09126225161316191,Eleutherodactylus_nitidus:0.07924668779273264):0.050610360536227604):0.061743096102788574,(Eleutherodactylus_symingtoni:0.045482873591955436,Eleutherodactylus_zeus:0.03505962082578949):0.06452457610849997):0.0176943392971644,(((Eleutherodactylus_zugi:0.004660585752570969,Eleutherodactylus_klinikowskii:0.0028372691261069043):0.09974700478644914,(((Eleutherodactylus_glandulifer:0.006704283893085302,Eleutherodactylus_sciagraphus:0.00194077626314879):0.026793227039020557,(Eleutherodactylus_brevirostris:0.058932577576142786,Eleutherodactylus_ventrilineatus:0.023241411201509212):0.0075232766431814905):0.016646665393278488,(((Eleutherodactylus_rufifemoralis:0.03324110362424683,Eleutherodactylus_furcyensis:0.043841469577980255):0.025526859220705807,((Eleutherodactylus_oxyrhyncus:0.0366804483813783,Eleutherodactylus_apostates:0.027048906100369575):0.025164374067322564,(((Eleutherodactylus_thorectes:0.03170199196005089,((Eleutherodactylus_bakeri:0.03520112375981523,(Eleutherodactylus_glaphycompus:0.045665066562837725,Eleutherodactylus_dolomedes:0.06648602858914136):0.01574971429109184):0.0055959312620808615,(Eleutherodactylus_heminota:0.05874525885869132,(Eleutherodactylus_corona:0.03009913802673726,((Eleutherodactylus_caribe:0.015971087371241068,Eleutherodactylus_eunaster:0.021101600892544627):0.01451802972874306,Eleutherodactylus_amadeus:0.022728899431596186):0.006133965484218174):0.0051786662058995545):0.0044791012511746255):0.006960122178389646):0.0067652036007556145,Eleutherodactylus_glanduliferoides:0.09864728401975087):0.008535132057970095,Eleutherodactylus_jugans:0.06353175793067418):0.0033674776021919996):0.0074490959283837746):0.004161448112297135,Eleutherodactylus_paulsoni:0.12905174272924338):0.005101044655218886):0.03578318516836447):0.015867987309861675,((Eleutherodactylus_schmidti:0.08692112809087198,(Eleutherodactylus_albipes:0.07447163381899363,(Eleutherodactylus_maestrensis:0.0904591100280354,(Eleutherodactylus_emiliae:0.0584790361186568,Eleutherodactylus_dimidiatus:0.05738012318884213):0.030789533637154056):0.010306070054371426):0.029604579663461528):0.027595755300254654,(Eleutherodactylus_greyi:0.08840613611400162,(((((Eleutherodactylus_gundlachi:0.06596275921933621,(Eleutherodactylus_varleyi:3.51422724235035E-6,Eleutherodactylus_intermedius:0.004588091028106378):0.023778277152764857):0.02512451896084527,((Eleutherodactylus_etheridgei:0.04606768757833982,(Eleutherodactylus_iberia:0.02289514854483921,(Eleutherodactylus_limbatus:0.015150647966402376,Eleutherodactylus_jaumei:0.009910971686200667):0.013059467843316065):0.01606476235069261):0.016154403081965207,(Eleutherodactylus_orientalis:0.0429186040634883,Eleutherodactylus_cubanus:0.03346428336857495):0.0033958437715604565):0.012370858165784092):0.006688650527692303,Eleutherodactylus_atkinsi:0.0495985472815036):3.51422724235035E-6,(((Eleutherodactylus_pezopetrus:0.08100943584910883,(Eleutherodactylus_pinarensis:0.04388726641055476,(Eleutherodactylus_thomasi:0.001813264478654204,Eleutherodactylus_blairhedgesi:0.003487052032329943):0.02703359256041654):0.0196790570771442):0.0051795911099786094,((Eleutherodactylus_rogersi:0.05390842799084423,Eleutherodactylus_tonyi:0.03646804675774285):0.00773624884130982,Eleutherodactylus_goini:0.05261082570481505):0.0052691682068118735):0.011959021314107164,(Eleutherodactylus_guanahacabibes:0.030031189479537992,(Eleutherodactylus_casparii:0.021182869746148022,Eleutherodactylus_planirostris:0.0031494047650646667):0.029274386316165326):0.0327831591450891):0.014920189738374001):0.02224243929140297,((((Eleutherodactylus_cuneatus:0.038054587726490736,Eleutherodactylus_turquinensis:0.040969783259542196):0.023405594313187797,(((Eleutherodactylus_luteolus:0.05736951358997151,((((Eleutherodactylus_cavernicola:0.022511869395154496,Eleutherodactylus_grabhami:0.019303388571298895):0.014179160037806305,Eleutherodactylus_sisyphodemus:0.02687956951370629):0.011954797563138662,(Eleutherodactylus_jamaicensis:0.024197458785863404,(Eleutherodactylus_nubicola:0.01566333307319785,(Eleutherodactylus_orcutti:0.04402527472136111,Eleutherodactylus_andrewsi:0.06887665880861657):0.005767823386959339):0.002825210993444294):0.004007870701233049):0.010205228583431314,(Eleutherodactylus_alticola:0.028936265108701297,((Eleutherodactylus_junori:0.028999953323006117,((Eleutherodactylus_griphus:0.04002986373019299,((Eleutherodactylus_pantoni:0.02152784601666403,Eleutherodactylus_pentasyringos:0.013480330448371046):0.004512138462151265,(Eleutherodactylus_cundalli:0.030676174845364564,Eleutherodactylus_glaucoreius:0.03376876902661673):0.01707356329145234):0.002165453677367858):0.0017325142967327231,Eleutherodactylus_gossei:0.03274279323058094):0.0051341836946974485):0.003343101445220158,Eleutherodactylus_fuscus:0.03229567595879668):9.489449233062204E-4):0.005687925081536713):0.0072236370482281435):0.014129388387745552,Eleutherodactylus_toa:0.0735814899155772):0.0062893645159965315,(Eleutherodactylus_rivularis:0.13802176497969842,Eleutherodactylus_riparius:0.10153687270344722):0.01783850488839368):0.011524235297440573):0.010337843481783505,((Eleutherodactylus_bresslerae:0.029852609921710956,(Eleutherodactylus_acmonis:0.030817452248448104,Eleutherodactylus_ricordii:0.022709923646041397):0.016364523089298852):0.03258398469756156,(Eleutherodactylus_probolaeus:0.03974095323024431,((Eleutherodactylus_pictissimus:0.01966275337361081,(Eleutherodactylus_grahami:0.011407697411394246,(Eleutherodactylus_lentus:0.01690479733709959,(Eleutherodactylus_weinlandi:0.007954197085474223,Eleutherodactylus_rhodesi:0.007395641274809001):0.0057336717998367655):0.006261739862063423):0.006690666416308991):0.024456002101424307,Eleutherodactylus_monensis:0.04448186103767861):0.010426779479603325):0.04265378966576893):0.012459068148320901):0.010685650861784966,((Eleutherodactylus_darlingtoni:0.013793850357479884,Eleutherodactylus_leoncei:0.005513346167798844):0.04783687106998427,(Eleutherodactylus_armstrongi:0.014266852396593421,Eleutherodactylus_alcoae:0.036525531966101976):0.011492249321838245):0.018906618193169022):0.005926091672195361):0.026181541589252216):0.01785170765490126):0.011258755691389307):0.020050229106421836):0.051619063824474606,(((Eleutherodactylus_unicolor:0.093985401541976,Eleutherodactylus_richmondi:0.07481345230552154):0.03299637659127877,((((Eleutherodactylus_minutus:0.1020843891748783,Eleutherodactylus_poolei:0.08682560205881341):0.028280462836645922,((Eleutherodactylus_eileenae:0.0960159293260652,(Eleutherodactylus_glamyrus:0.039933113953297926,((Eleutherodactylus_mariposa:0.047747369841321385,Eleutherodactylus_ronaldi:0.04286294966090121):0.025764823560780525,(Eleutherodactylus_bartonsmithi:0.06585899760875669,(Eleutherodactylus_principalis:0.03105617207849697,Eleutherodactylus_auriculatus:0.03904724782603023):0.02620149251665096):0.015760117297387313):0.003599096135638752):0.028887762018942994):0.017768419130256778,(Eleutherodactylus_pituinus:0.06809872020730864,(Eleutherodactylus_parabates:0.05781890748859934,(Eleutherodactylus_abbotti:0.0819710152431781,(Eleutherodactylus_audanti:0.06084683370618783,Eleutherodactylus_haitianus:0.06636338177080083):0.007742138317589103):0.004313137168343702):0.01934975850003422):0.01601825285060254):0.013198009944656157):0.035731211971333575,(((Eleutherodactylus_barlagnei:0.02706115271095175,Eleutherodactylus_pinchoni:0.017866001452237892):0.04087476310457186,(Eleutherodactylus_johnstonei:0.04519938585337231,(Eleutherodactylus_martinicensis:0.03921375491774232,Eleutherodactylus_amplinympha:0.03653861079330608):0.03457678269139407):0.023033649250169864):0.04951992892087079,(Eleutherodactylus_flavescens:0.1444504155222169,(((Eleutherodactylus_locustus:0.014047770771712462,Eleutherodactylus_eneidae:0.033970757074179965):0.019397917063519943,Eleutherodactylus_cooki:0.034953616526258426):0.03311468982224783,((((Eleutherodactylus_schwartzi:0.03571959067216098,Eleutherodactylus_coqui:0.03947441385128695):0.015978262552329807,(Eleutherodactylus_portoricensis:0.047371511456635776,Eleutherodactylus_wightmanae:0.051970836009123125):0.0071058517669309275):0.013466620871877777,Eleutherodactylus_gryllus:0.08728661632784364):0.009773715977104889,(((Eleutherodactylus_cochranae:0.03781523933090701,Eleutherodactylus_hedricki:0.04329268870633566):0.018721961777815595,Eleutherodactylus_brittoni:0.06121249058219736):0.025097199314447732,Eleutherodactylus_antillensis:0.0635438954502729):0.01433643763393386):0.011621144102775864):0.020365319942619364):0.02641989005783543):0.010373109830630963):0.0063485392623674,((Eleutherodactylus_auriculatoides:0.033508714740692475,Eleutherodactylus_patriciae:0.03285779893812763):0.03856789167505605,(((Eleutherodactylus_lamprotes:0.05222065460349165,Eleutherodactylus_fowleri:0.046491198505911234):0.0655486640976047,(Eleutherodactylus_wetmorei:0.05350648572536581,Eleutherodactylus_sommeri:0.02776566855918259):0.0727016790701668):0.013732471174586511,(((Eleutherodactylus_guantanamera:0.011998623545672618,Eleutherodactylus_ionthus:0.006990023245777151):0.016052795340769577,Eleutherodactylus_varians:0.02163536287911473):0.05248976780862308,(Eleutherodactylus_leberi:0.04608557765097465,Eleutherodactylus_melacara:0.027352763966407085):0.039907359309301914):0.02870945061989461):0.013189561753566705):0.02502941835594528):0.02232781389842496):0.008354401265587771,((((Eleutherodactylus_hypostenor:0.04625917810092555,Eleutherodactylus_parapelates:0.05538302028458832):0.023220134459927634,(Eleutherodactylus_ruthae:0.018779210048730482,Eleutherodactylus_bothroboans:0.009191300928598115):0.06016884516745334):0.061455327496717026,(Eleutherodactylus_inoptatus:0.08469988580986197,(Eleutherodactylus_chlorophenax:0.0016274289987174952,Eleutherodactylus_nortoni:3.51422724235035E-6):0.09505015111462019):0.06175376967101758):0.035763344343728416,Eleutherodactylus_counouspeus:0.13363573935550652):0.008802896889456933):0.017588944946287103):0.019228631390327777):0.056023957905158385,(Phyzelaphryne_miriamae:0.2626970040070212,Adelophryne_gutturosa:0.18616535428549805):0.06136840975180572):0.03367267357025298,((((((Barycholos_pulcher:0.14396889794689596,Barycholos_ternetzi:0.156564794367698):0.08497725557064405,Noblella_lochites:0.19212384534496565):0.08652917784991043,(Bryophryne_cophites:0.20037896523191703,(Holoaden_luederwaldti:0.066167101848484,Holoaden_bradei:0.06943637427714167):0.1287227406192725):0.008242935006829042):0.014678432989978564,(Noblella_peruviana:0.251548612586072,(Psychrophrynella_iatamasi:0.07934300591174098,Psychrophrynella_wettsteini:0.05162818673429221):0.07045842891368394):0.04861180686168764):0.017332441681665985,((((Pristimantis_bisignatus:0.04592822176814196,Pristimantis_fraudator:0.009996091353044951):0.031876088993278706,(Pristimantis_mercedesae:0.12667789770749727,Pristimantis_ashkapara:0.08153795863824499):0.014861663701180475):0.15212037962328268,((((Pristimantis_caryophyllaceus:0.15356290701881645,(Pristimantis_cremnobates:0.08572808652798186,(Pristimantis_cruentus:0.08895848596098872,(Pristimantis_latidiscus:0.012232476583669119,Pristimantis_colomai:0.005175996541605278):0.07236499891413392):0.016353940307869713):0.00909963588112849):0.015091525253684904,Pristimantis_ridens:0.15905360958885223):0.015519728964294476,((Pristimantis_labiosus:0.05339062259317683,Pristimantis_crenunguis:0.04480409773364547):0.1244033446772878,(Pristimantis_lanthanites:0.12435165211941535,(Pristimantis_actites:0.02503361357875008,'Pristimantis w-nigrum':0.023455738390454832):0.0440773572153552):0.047952751310276896):0.026689654969404324):0.017205570114734312,(((Pristimantis_caprifer:0.1365850970050503,(Pristimantis_euphronides:0.021645403939082958,Pristimantis_shrevei:0.03039721252146906):0.12544555055720377):0.025696552599290046,((Pristimantis_terraebolivaris:0.12401365371605154,((Pristimantis_samaipatae:0.044198195242505484,((Pristimantis_fenestratus:0.022499598533587153,Pristimantis_koehleri:0.015515275145431794):0.018678695712794527,Pristimantis_chiastonotus:0.14998597655037946):0.0065379949385838635):0.09044311448721688,((Pristimantis_bipunctatus:0.10012294828555866,Pristimantis_skydmainos:0.11518067175427592):0.025927559411606235,((Pristimantis_achatinus:0.04273914020729587,Pristimantis_lymani:0.06140526927170591):0.018963555053215234,(((Pristimantis_buccinator:0.04891591074140416,Pristimantis_citriogaster:0.03300736262796858):0.006340440762239329,Pristimantis_malkini:0.039938668879869306):0.0266946692814396,(Pristimantis_condor:0.037572160157491055,Pristimantis_conspicillatus:0.07106700607799389):0.006402256856076239):0.024907083050249065):0.028364852081604802):0.00921724118007114):0.010036715344162332):0.0360877303117186,((((Pristimantis_danae:0.06841394313606747,Pristimantis_sagittulus:0.04488533723746925):0.010950561487074623,(Pristimantis_pluvicanorus:0.07222619736930877,(Pristimantis_toftae:0.07942996127548496,Pristimantis_rhabdolaemus:0.0678395020700993):0.013827192735930154):0.018592813727474858):0.0158891549727329,(Pristimantis_stictogaster:0.0395986123687888,Pristimantis_aniptopalmatus:0.05685917742528557):0.02782580138617438):0.012043981767337045,(Pristimantis_reichlei:0.012737293395279322,Pristimantis_peruvianus:3.51422724235035E-6):0.1042767588818616):0.07454361790656473):0.010380096771945227):0.011300927892402602,((Pristimantis_prolatus:0.1515690082165631,(Pristimantis_rozei:0.14470279950584589,Pristimantis_urichi:0.17103882154696748):0.01822196531129039):0.0044581477451227395,((((((Pristimantis_pycnodermis:0.06325173807308161,(Pristimantis_appendiculatus:0.07838919442707962,(Pristimantis_dissimulatus:0.049865817972329425,Pristimantis_calcarulatus:0.07228441720537061):0.013314630578832934):0.004627068385804754):0.011809327984239022,Pristimantis_orcesi:0.058745112694901125):0.010597620462685954,((Pristimantis_glandulosus:0.04431837975094693,Pristimantis_inusitatus:0.02042153476373098):0.012495832232071081,Pristimantis_acerus:0.03457547525024126):0.03895129377823442):0.008710169690432007,(((Pristimantis_subsigillatus:0.01219583919029385,Pristimantis_nyctophylax:0.010358048405424123):0.052984092917062575,Pristimantis_crucifer:0.08889920078066538):0.028071853815426772,(Pristimantis_galdi:0.07851257480146592,((Pristimantis_bromeliaceus:0.08742854228798634,(Pristimantis_acuminatus:0.17052806955355848,Pristimantis_schultei:0.06118929698711504):0.0013065437546198187):0.022364464323990935,Pristimantis_zeuctotylus:0.12919791691155733):0.020501862935603087):0.012356577957900738):0.010290809286910039):0.01630312459666508,((((Pristimantis_simonbolivari:0.03574095694463125,Pristimantis_orestes:0.05058612447601888):0.040136978744597214,(((Pristimantis_chalceus:0.09866260918897309,((Pristimantis_walkeri:0.025353809792914517,Pristimantis_luteolateralis:0.02416676118145074):0.05005832023705672,Pristimantis_parvillus:0.059574268522620745):0.04094877048909894):0.021507101835818332,(((Pristimantis_ockendeni:0.09873447485868761,Pristimantis_unistrigatus:0.04960103395404462):0.008905387928395556,Pristimantis_ardalonychus:0.08113704459190194):0.0037834777671597202,(Pristimantis_cajamarcensis:0.06005646960683265,Pristimantis_ceuthospilus:0.07145742059390767):0.018618311671726073):0.009970046829723614):0.02095600363712736,((((Pristimantis_marmoratus:0.05986838328181804,Pristimantis_inguinalis:0.03537203559636511):0.012023078123967431,Pristimantis_pulvinatus:0.0573268867394381):0.01372577341331705,((Pristimantis_llojsintuta:0.02035458543902516,Pristimantis_croceoinguinis:0.08063963056796795):3.51422724235035E-6,(Pristimantis_imitatrix:0.06316526351493315,Pristimantis_lirellus:0.04904978357780009):0.023077861451460187):0.00526466042151648):0.03147656448731368,(Pristimantis_diadematus:0.06390604321344559,(Pristimantis_altamazonicus:0.04517118987913795,Pristimantis_platydactylus:0.040324973501343245):0.026946743888773533):0.01679222485308147):0.0241256738293489):0.013483873989285872):0.01051886699259611,(Pristimantis_cryophilius:0.048007081758721454,(Pristimantis_spinosus:0.05314974968987325,(Pristimantis_phoxocephalus:0.03734724630471983,(Pristimantis_riveti:0.03231011685137991,Pristimantis_versicolor:0.040484427775895906):0.009358634986955359):0.018450575978415883):0.010848952083375836):0.02101811400093097):0.008084232599606783,((Pristimantis_melanogaster:0.08211061582539926,((Pristimantis_wiensi:0.09423245797068655,Pristimantis_petrobardus:0.09462376002266883):0.014739027880393947,(Pristimantis_quaquaversus:0.05561501062223532,Pristimantis_rhabdocnemus:0.082271145912182):0.0412190685858752):0.013538498631660635):0.028138643597748685,(Pristimantis_simonsii:0.0698721015656685,Pristimantis_rhodoplichus:0.07261761632339303):0.01642113543809006):0.005097207174325625):0.01597972819471243):0.012569303152736489,(((Pristimantis_supernatis:0.04999415451688066,Pristimantis_chloronotus:0.0328188962572378):0.03187064356907543,Pristimantis_eriphus:0.06939000398068966):0.05513899955294488,(((Pristimantis_celator:0.06234207995071632,Pristimantis_verecundus:0.06828998183960838):0.029972126504104333,(Pristimantis_leoni:0.05375161199621174,(Pristimantis_pyrrhomerus:0.027329638598872438,(Pristimantis_ocreatus:0.0016062225524272403,Pristimantis_thymelensis:7.356694538179277E-4):0.03129226714243349):0.01468970477476476):0.018066340759770295):0.01575887010493342,(((((Pristimantis_truebae:0.0025197115170822997,Pristimantis_gentryi:0.001611762078387158):0.012045872534196598,Pristimantis_curtipes:0.02247565133462041):0.012732169792668066,((Pristimantis_buckleyi:0.023979602267224998,Pristimantis_vertebralis:0.037721848891624395):0.007356365479968842,Pristimantis_devillei:0.03578108547523754):0.0028643967546574355):0.0024779035396380444,Pristimantis_surdus:0.02616077273239429):0.012535004397316892,((Pristimantis_quinquagesimus:0.04268384581093592,Pristimantis_duellmani:0.03329872183745385):0.009529163916271746,Pristimantis_thymalopsoides:0.04569274794923554):0.010754222036261295):0.02891012043048569):0.02507224167934785):0.0058816114842242286):0.010504788299871745):0.008931847165992582):0.005913628420249355):0.0512935966040581):0.027421787787167497,((Phrynopus_bracki:0.03664172071351548,(Phrynopus_bufoides:0.02343029381689311,((Phrynopus_tautzorum:0.03921375142902055,(Phrynopus_juninensis:0.024781031545528482,Phrynopus_kauneorum:0.039891225415764474):0.02060882348825351):0.008844166432657079,(Phrynopus_pesantesi:0.016135470726806215,(Phrynopus_barthlenae:0.03070332290665268,Phrynopus_horstpauli:0.02762746714834709):0.009254387833757536):0.005926148105084998):0.012121069809142475):0.0468628911566262):0.11555610852644184,(((Lynchius_nebulanastes:0.057907524201622054,Lynchius_parkeri:0.04379316793349986):0.013141310829504907,Lynchius_flavomaculatus:0.02996444765684037):0.1542265686365759,(((Oreobates_saxatilis:0.03573491533412051,Oreobates_quixensis:0.018647244442207556):0.1626876538410304,(((Oreobates_cruralis:0.06769178842221196,(Oreobates_madidi:0.09611433394981096,Oreobates_heterodactylus:0.12576431316100758):0.018606251621441847):0.02240728690457594,(Oreobates_ibischi:0.04838507489999232,Oreobates_discoidalis:0.04229587418452339):0.045081298988823786):0.021942092067919146,((Oreobates_sanctaecrucis:0.01782316610626467,(Oreobates_sanderi:0.02025947685045741,Oreobates_granulosus:0.02276073967763518):0.014104303864176531):0.028830739258791187,Oreobates_choristolemma:0.044455896618561794):0.05824852651236008):0.016580363079623815):0.005095544241794674,Oreobates_lehri:0.1088755696655215):0.04247672432295313):0.016689528233274794):0.02223186647847705):0.013829071344793751):0.003641501881443738,((Hypodactylus_dolops:0.19365613792901976,(Hypodactylus_peraccai:0.0708264578689752,(Hypodactylus_brunneus:0.045244110938798314,Hypodactylus_elassodiscus:0.073910795654959):0.007836848574721722):0.11156115126212032):0.021190231898499678,((((Strabomantis_anomalus:0.06199486076385595,Strabomantis_bufoniformis:0.0545048124694153):0.04286745754163488,Strabomantis_necerus:0.09977040307628846):0.036232752812975375,(Strabomantis_biporcatus:0.07551552909320706,Strabomantis_sulcatus:0.11780807887465058):0.028721260145821):0.10926045015745856,(Haddadus_binotatus:0.26260728767890806,(Craugastor_daryi:0.15178088063310088,(((Craugastor_montanus:0.1996382999454045,(Craugastor_sartori:0.112269532325603,Craugastor_pygmaeus:0.0992611746514):0.030642299473845634):0.05241023452017656,(((Craugastor_sandersoni:0.05898342395561328,(((((Craugastor_ranoides:0.01421083330441709,Craugastor_rugulosus:0.013481536334967986):0.030776296452399574,Craugastor_fleischmanni:0.05160848884586483):0.007461201091633009,Craugastor_rupinius:0.07079406408065932):0.010718014817844176,(Craugastor_megacephalus:0.07447049215758311,Craugastor_angelicus:0.03213509457110762):0.006647400022552097):0.005908111085204446,(Craugastor_punctariolus:0.018850348053259844,Craugastor_obesus:0.02293372017279647):0.026248174274754087):0.017093552376735772):0.07540190959795928,(((Craugastor_tabasarae:0.0983779103653251,Craugastor_longirostris:0.05655661255812662):0.03716097081488238,(((Craugastor_talamancae:0.10992920249206702,Craugastor_crassidigitus:0.03854173636572111):0.02151804933632248,Craugastor_fitzingeri:0.08158587373257367):0.007944971978915989,Craugastor_raniformis:0.07737851456660332):0.01888666734180332):0.03128375102578164,(Craugastor_emcelae:0.13434657828857416,((Craugastor_cuaquero:0.033096410756538894,Craugastor_andi:0.007497201345303428):0.09874247965584357,Craugastor_melanostictus:0.09431260850401328):0.017031691165303696):0.03141696357167946):0.06762968004647567):0.04314922125689441,(((Craugastor_bransfordii:0.14153060572371925,Craugastor_podiciferus:0.0901290953645141):0.0507377803552086,(Craugastor_mexicanus:0.0670964987986107,(Craugastor_rhodopis:0.07870470533752161,Craugastor_loki:0.07077542981801167):0.01985229545248604):0.06905596424039488):0.05007840649898789,(Craugastor_lineatus:0.0659352702049914,Craugastor_laticeps:0.08015392869161689):0.12965278134459543):0.018103487742545894):0.011061476326915861):0.024348810036715927,((Craugastor_tarahumaraensis:0.04400542577598442,Craugastor_augusti:0.04092159927563167):0.08134228820968432,(Craugastor_alfredi:0.07606613594027745,(Craugastor_uno:0.08456061455480048,((Craugastor_spatulatus:0.06648496108179212,Craugastor_bocourti:0.1125666599126582):0.016021473969911902,Craugastor_stuarti:0.0858143918232064):0.012550333914418043):0.014855157095242882):0.04072164513106052):0.06304009547837616):0.016937353400684436):0.04204536537596243):0.01823604205745893):0.006804425731538875):0.009117436645756254):0.017100099803173497):0.00606997121120396):0.02524516141383102):0.019701472482038435):0.20888041229816465):0.031459423293471914,((((Hemisus_marmoratus:0.31649302970731363,(((Callulina_kreffti:0.009767231935801573,Callulina_kisiwamsitu:0.007334456228684841):0.11138534874264865,(Spelaeophryne_methneri:0.17388945361562047,(Probreviceps_durirostris:0.06058073514541161,(Probreviceps_macrodactylus:0.0533175243039436,Probreviceps_uluguruensis:0.039314406734049685):0.03627375826846615):0.0636195213371591):0.01768780084392321):0.06270197714607655,(Breviceps_fuscus:0.07799551851474598,(Breviceps_fichus:0.10177995415992219,Breviceps_mossambicus:0.029640120552229834):0.05162735729567):0.0848824310381513):0.06701771936350572):0.09339282640844292,((Cryptothylax_greshoffii:0.17801948258168246,(((Phlyctimantis_leonardi:0.04734709828731503,(Kassina_maculata:0.06053968105242286,Phlyctimantis_verrucosus:0.048988516251558124):0.012535715899438972):0.03880398642502227,(Semnodactylus_wealii:0.07475983050844014,Kassina_senegalensis:0.06738152267267565):0.00937528675852648):0.06764876444744738,((Acanthixalus_spinosus:0.00997733145065307,Acanthixalus_sonjae:3.51422724235035E-6):0.20038027867791322,(((((Hyperolius_horstockii:0.13305027080076876,Hyperolius_semidiscus:0.0715122602680812):0.03104123469520503,(Hyperolius_pusillus:0.0871381307515843,(Hyperolius_nasutus:0.08468532387213772,Hyperolius_acuticeps:0.10453557653533736):0.017788880237858278):0.08686504685400026):0.012355038562575919,(((((Hyperolius_phantasticus:0.004492635198615602,Hyperolius_glandicolor:0.00238187924946521):0.017010397008283263,(Hyperolius_tuberculatus:0.031191912142210513,((Hyperolius_marmoratus:3.51422724235035E-6,Hyperolius_viridiflavus:0.0017893655086800174):0.01776716246305473,Hyperolius_angolensis:0.008746439014716986):0.015129042040748072):0.009812552295038291):0.025896719900840474,Hyperolius_argus:0.0489799431801102):0.017757913237200114,((Hyperolius_fusciventris:0.060815054928743464,Hyperolius_guttulatus:0.09248707044558996):0.02699007249116386,Hyperolius_pardalis:0.09522840303977362):0.00741818101501971):0.06608909768974564,(Hyperolius_tuberilinguis:0.1254120811710064,(Alexteroon_obstetricans:0.13313531553909755,(Hyperolius_kivuensis:0.17111487255318528,((((Hyperolius_castaneus:0.0365057707336828,(Hyperolius_cystocandicans:0.0618770400920116,Hyperolius_frontalis:0.04905582360256994):0.007428109458678053):0.0073119744240628245,(Hyperolius_lateralis:0.05981191590017168,Hyperolius_alticola:0.030992292454357515):0.019906758019105495):0.03563725121064865,(Hyperolius_ocellatus:0.015444153199763729,Hyperolius_mosaicus:0.022475902823285515):0.06867308968600899):0.016435824642235045,((((Hyperolius_molleri:0.003945309787581538,Hyperolius_thomensis:0.009269477519233293):0.033691951062209256,Hyperolius_cinnamomeoventris:0.04759504208858714):0.04948936698245398,((Hyperolius_puncticulatus:0.06738639251828381,Hyperolius_montanus:0.032158824241370336):0.01926076253398997,(Hyperolius_zonatus:0.044586139286211836,Hyperolius_concolor:0.037013108800231106):0.020424824452864716):0.011504945166906373):0.01510317530652092,((Hyperolius_torrentis:0.043468983204292565,Hyperolius_chlorosteus:0.06470935236766935):0.027380254293312874,(Hyperolius_baumanni:0.04491272075299735,Hyperolius_picturatus:0.05607488229541196):0.034924019602587994):0.01676415690944129):0.014688206511413308):0.0064309684137216876):0.010377362075859821):0.01423933291257686):0.044729584550172743):0.03532065552321609):0.035457071098357565,(Morerella_cyanophthalma:0.14932285723876304,((Tachycnemis_seychellensis:0.0615330678709685,((Heterixalus_punctatus:0.06190836641070411,((Heterixalus_rutenbergi:0.05775031533606187,Heterixalus_luteostriatus:0.03953866644572742):0.009165152672450343,((Heterixalus_carbonei:0.02670964146241898,Heterixalus_betsileo:0.023985078553122728):0.01263694781304087,(Heterixalus_andrakata:0.016313340280234866,(Heterixalus_variabilis:0.001316779943980547,Heterixalus_tricolor:0.004685282111689229):0.004991991451192654):0.03999317209452683):0.009377047968286156):0.00849719548764904):0.00933080061626554,(Heterixalus_madagascariensis:0.026222755376536677,(Heterixalus_alboguttatus:0.00957250911103238,Heterixalus_boettgeri:0.015499989829609638):0.023338937995971597):0.038044569254894134):0.016290095793335627):0.06527901674411335,((Afrixalus_fornasini:0.08717634487759107,(Afrixalus_laevis:0.08814787697250907,(Afrixalus_paradorsalis:0.022605796151082613,Afrixalus_dorsalis:0.014902601657612619):0.09483962084899908):0.012421654644929322):0.031140437775379313,((Afrixalus_stuhlmanni:0.0034464904960338563,Afrixalus_delicatus:0.025638008813980033):0.04360082946347706,Afrixalus_knysnae:0.04673889975696773):0.03717379730711843):0.03104446835588926):0.01893373459691679):0.008523990849959454):0.017544679273657313,Opisthothylax_immaculatus:0.19331334444614282):0.017524970081514345):0.02471712404528343):0.04050686020058848):0.08578183718974747,((((Cardioglossa_gratiosa:0.0535351491659008,((Cardioglossa_occidentalis:0.033987423286967286,Cardioglossa_leucomystax:0.06270417940139353):0.035971883067615194,(Cardioglossa_pulchra:0.03564154087855466,(Cardioglossa_manengouba:0.005142572597513988,Cardioglossa_oreas:0.009870696027569173):0.022101967372075):0.016366182748464644):0.010258712277479027):0.007649673858544078,Cardioglossa_elegans:0.10031797645630536):0.06199238842900457,((Cardioglossa_gracilis:0.07137732160659974,Cardioglossa_schioetzi:0.06529824932739177):0.023878082805565574,((Arthroleptis_xenodactylus:0.10328413636951729,(Arthroleptis_schubotzi:0.08845151572054172,Arthroleptis_xenodactyloides:0.07315820493379818):0.015424549966527706):0.04214914220355963,(((Arthroleptis_francei:0.0829513401076117,Arthroleptis_wahlbergii:0.16002558985897397):0.014601703131262049,((((Arthroleptis_affinis:0.06340412990593682,Arthroleptis_nikeae:0.0689235142498329):0.01556561717215826,Arthroleptis_reichei:0.08446332418189045):0.036785185636266156,Arthroleptis_tanneri:0.08059269685498989):0.013074249262091504,(Arthroleptis_stenodactylus:0.10431342054132711,((Arthroleptis_poecilonotus:0.032431256065356004,Arthroleptis_adelphus:0.0525487459198253):0.0821535136898747,(Arthroleptis_krokosua:0.032213556997553745,Arthroleptis_variabilis:0.031357002439719596):0.02549353604183644):0.020918201930579174):0.017028149806628298):0.00890085290135446):0.02086623335668369,(Arthroleptis_taeniatus:0.10000512942023729,(Arthroleptis_aureoli:0.23084230524420427,Arthroleptis_sylvaticus:0.13763162177641086):0.02993181491642898):0.013833007782785579):0.01553165441915672):0.03943292558248758):0.008477845900493684):0.1280911574275325,((Leptodactylodon_bicolor:0.16731081016764535,((Trichobatrachus_robustus:0.10408678806625861,(Astylosternus_diadematus:0.044296209122298306,(Astylosternus_batesi:0.015076895756229725,Astylosternus_schioetzi:0.011840682897577453):0.014674693791841808):0.04302742146912781):0.04708908200492301,(Nyctibates_corrugatus:0.1409555981462386,Scotobleps_gabonicus:0.2322064842477615):0.01324192500975251):0.015967495048624214):0.016448876086161742,(((Leptopelis_vermiculatus:0.0219619321429452,(((Leptopelis_concolor:0.017826555012905126,Leptopelis_bocagii:0.007598287147622363):0.0326861279069208,(Leptopelis_palmatus:0.03256430740817748,Leptopelis_kivuensis:0.04136835218783521):0.01939234227629102):0.011752134130004496,Leptopelis_natalensis:0.03616875332922136):0.012714830935618942):0.008527568486334229,Leptopelis_modestus:0.07525273039541638):0.015012028941202014,(Leptopelis_argenteus:0.05112013499779118,Leptopelis_brevirostris:0.12470210036149718):0.032121924212778055):0.16344196964631805):0.012993995530905335):0.0511647008055738):0.05315975286543589):0.03259642137123931,(((Phrynomantis_microps:0.012958575010139708,Phrynomantis_bifasciatus:3.51422724235035E-6):0.16556949351930733,Phrynomantis_annectens:0.10508234057464554):0.04452755035449042,(((((Chiasmocleis_shudikarensis:0.10491936345549896,Chiasmocleis_hudsoni:0.07164770836236463):0.14966402344818577,((Dasypops_schirchi:0.12317789425127663,(Hamptophryne_boliviana:0.11922727411172854,(Dermatonotus_muelleri:0.12683339623350232,(Elachistocleis_ovalis:0.12139123822467078,((Gastrophryne_elegans:0.09226401369560916,(Gastrophryne_olivacea:0.0416708742395666,Gastrophryne_carolinensis:0.029531854873670948):0.0343675643078271):0.015504892991789455,Hypopachus_variolosus:0.13256707571113588):0.024642951731627533):0.010406756523022436):0.02038706717023971):0.04548570317650754):0.0363059829599104,(Ctenophryne_geayi:0.08784011485200265,Nelsonophryne_aequatorialis:0.05025659730933181):0.06490152555483435):0.0027309428473180626):0.03414379681163214,((Hoplophryne_uluguruensis:0.04361342499146708,Hoplophryne_rogersi:0.09043132283255588):0.1628697909033466,((((Anodonthyla_hutchisoni:0.06243756485290712,Anodonthyla_boulengerii:0.06471109176610113):0.027178293345590108,(Anodonthyla_nigrigularis:0.02300175123854439,Anodonthyla_moramora:0.02649072891253013):0.07715161657293652):0.01991792121005589,(Anodonthyla_montana:0.07836014738769163,Anodonthyla_rouxae:0.0992095576545697):0.013069644678481948):0.04589276797602929,((((Plethodontohyla_ocellata:0.03275293467966662,Plethodontohyla_brevipes:0.026269728493522854):0.033424532241884145,(Plethodontohyla_bipunctata:0.04219450479017377,Plethodontohyla_tuberata:0.029881187542680033):0.04061745627722365):0.010148953892301249,Plethodontohyla_inguinalis:0.06529941154658023):0.019550430343232506,((Platypelis_grandis:0.07362086980370704,(Platypelis_milloti:0.09950221448708202,(Platypelis_mavomavo:0.09087969841116751,(Platypelis_tuberifera:0.06620099818482256,(Platypelis_barbouri:0.08268008910934632,Platypelis_pollicaris:0.05984095996720797):0.02530541476020168):0.006903735050927585):3.51422724235035E-6):0.03305355497271636):0.029421741672974303,((((((Rhombophryne_laevipes:0.06073963107973386,Rhombophryne_alluaudi:0.05973750870080946):0.022887591490611234,(Rhombophryne_testudo:0.09156613765865712,Rhombophryne_minuta:0.09916178466818365):0.008536788677423031):0.010494860355562931,((Rhombophryne_serratopalpebrosa:0.050507095634109725,Rhombophryne_coronata:0.07360194844190675):0.0330973102067606,Rhombophryne_coudreaui:0.11734209021228702):0.014788723988629359):0.00803050885831381,Stumpffia_helenae:0.19255907218782262):0.03374942080586929,((Plethodontohyla_mihanika:0.07758901478156299,(Plethodontohyla_fonetana:0.0792165996550691,(Plethodontohyla_notosticta:0.08715164888641568,Plethodontohyla_guentheri:0.06822512408003228):0.01267613468733452):0.014341739621625254):0.01259461751501062,(Cophyla_berara:0.06545824553758181,Cophyla_phyllodactyla:0.04691187135716327):0.12103903338602061):0.0030125123813723393):0.03573674096547616,(Stumpffia_tridactyla:0.1459608393042075,((Stumpffia_tetradactyla:0.07443205509115465,(Stumpffia_roseifemoralis:0.07137480920964819,Stumpffia_grandis:0.07921706148501483):0.012348334333642087):0.036865345438593156,(Stumpffia_pygmaea:0.09146817897866429,(Stumpffia_psologlossa:0.0440621881217157,Stumpffia_gimmeli:0.09759817423721949):0.02739133088179031):0.028124136596558495):0.012989583972458738):0.057965968842753096):0.00787556508636932):0.005876631185446535):0.007007909820081245):0.09447124967971239):0.01931394883011402):0.010213243048986654,(((Paradoxophyla_tiarano:0.07635432158172857,Paradoxophyla_palmata:0.08349920840866328):0.06974792490892615,(Scaphiophryne_brevis:0.04030526376750783,((Scaphiophryne_marmorata:0.028449049128228144,(Scaphiophryne_boribory:0.01394200658275257,(Scaphiophryne_menabensis:0.0020625344129811592,Scaphiophryne_madagascariensis:0.0036155673162391057):0.011299300199303636):0.006274564736791586):0.019764118498146264,((Scaphiophryne_gottlebei:0.022644964528817518,Scaphiophryne_calcarata:0.05794292156293766):0.006426273280980524,Scaphiophryne_spinosa:0.003119326385011839):0.008944765330461318):0.02482382564682648):0.04680444843380368):0.02634779363154417,(((((Chaperina_fusca:0.25133728517019843,((Microhyla_butleri:0.11953923297919403,(((Microhyla_borneensis:0.09081651844479108,Microhyla_heymonsi:0.052324933312530277):0.019597880852569388,((Microhyla_ornata:0.05687776124304801,Microhyla_fissipes:0.02276894083544128):0.024973641788061156,Microhyla_okinavensis:0.06628657236677542):0.019871327814148014):0.016835808912421594,(Microhyla_rubra:0.07651509586338918,Microhyla_pulchra:0.08237093199209013):0.013225509872806981):0.016806458174703238):0.09647265843086156,(Calluella_guttulata:0.09199121599273959,Glyphoglossus_molossus:0.07427630945054066):0.07046286891248629):0.04911883615753126):0.009587642301593596,Micryletta_inornata:0.26486292412372436):0.009502832748492831,((((Uperodon_systoma:0.055716273695337534,(Ramanella_obscura:0.07022264088798759,Ramanella_variegata:0.05509309945578596):0.043450386673065194):0.020153669213813372,Kaloula_taprobanica:0.0654886154237366):0.013995109635788977,(Kaloula_conjuncta:0.042160841189748395,Kaloula_pulchra:0.07903314442881271):0.020816353855553416):0.0074397380967941885,Metaphrynella_sundana:0.21348617582277465):0.055501907544891045):0.013704921869003402,(Dyscophus_insularis:0.04270025319273739,(Dyscophus_antongilii:0.0010807534544395633,Dyscophus_guineti:0.0017848005725741162):0.0790293674941389):0.09628668647356714):0.015943217442063954,((Kalophrynus_baluensis:0.0369521894297887,(Kalophrynus_pleurostigma:0.05268922851665413,Kalophrynus_intermedius:0.037987655196672625):0.010676170591773686):0.1946451286058724,(Melanobatrachus_indicus:0.20395323480052546,(((((Albericus_laurini:0.10879869171704869,Choerophryne_rostellifer:0.16214552633655643):0.029770689751347414,(Barygenys_exsul:0.2646934856859031,Cophixalus_sphagnicola:0.11395338290573835):0.022138069728271977):0.02747188611474401,(((Cophixalus_tridactylus:0.05985656359209199,Cophixalus_humicola:0.08001743552909843):0.05619392219154561,((((Oreophryne_atrigularis:0.06236963264854908,Oreophryne_wapoga:0.06260870000272473):0.09061459926463342,(Oreophryne_brachypus:0.0879091595229819,Oreophryne_unicolor:0.08995087060759825):0.038080363113317875):0.017924556264689716,((Cophixalus_balbus:0.16053148188957628,(Oreophryne_pseudasplenicola:0.04756795699184053,Oreophryne_asplenicola:0.022129284141241264):0.09082562834233093):0.016085403185948068,Oreophryne_sibilans:0.0919444568149742):0.009222895868012544):0.007417571838344542,(Oreophryne_waira:0.023662863469299304,Oreophryne_clamata:0.03969595846797701):0.07774899091538694):0.003961258201480869):0.014349001827532966,Aphantophryne_pansa:0.07136864641700366):0.01299807071215337):0.012680430031072238,(((Oxydactyla_crassa:0.11261690873428352,Liophryne_rhododactyla:0.04890432034517879):0.019316596840284955,((Liophryne_dentata:0.12770017110731696,(Hylophorbus_rufescens:0.06253973917540487,Sphenophryne_cornuta:0.09908946821086717):0.007358213631760409):3.51422724235035E-6,Xenorhina_obesa:0.07643281785765765):0.01557493445267109):0.014304398865628036,((Barygenys_flavigularis:0.09303711631835224,(((Callulops_pullifer:0.02260990402110985,Callulops_eurydactylus:0.01084565834387533):0.09760449787199686,(Callulops_slateri:0.07643433527337473,Asterophrys_turpicola:0.0731343207839186):0.018955560916072357):0.029400487830316464,((Xenorhina_oxycephala:0.03700685724799739,Xenorhina_varia:0.051908303727268776):0.0687947023411289,(Xenorhina_bouwensi:0.1119973179187195,Xenorhina_lanthanites:0.12082839071737234):0.018839338225540354):0.04052531824980829):0.009991961792447317):0.013975918659937409,(Callulops_robustus:0.07245758366454673,(Hylophorbus_nigrinus:0.08776893755344983,((Hylophorbus_picoides:0.06163216577468324,Hylophorbus_tetraphonus:0.062181354845306264):0.013880519924628661,Hylophorbus_wondiwoi:0.07657011381398263):0.020618880025394477):0.036059188751108):0.014367201841847825):0.013811570973180844):0.010269427608453273):0.0042197628562168354,((Copiula_major:0.08838253892210128,(Austrochaperina_derongo:0.07664051218840276,(Copiula_obsti:0.09184143194232981,Copiula_pipiens:0.0871102776707642):0.07361323779343176):0.04884818256163565):0.01904224006248139,(Liophryne_schlaginhaufeni:0.10948204104653225,Genyophryne_thomsoni:0.1190520595218908):0.02753337427350191):0.0016913660717387353):0.10943450568070408):0.011660093039403338):0.010606158655649421):0.004839801897434639):0.007660345502578853):0.007107467243314485,(Synapturanus_mirandaribeiroi:0.20241291833023267,Otophryne_pyburni:0.16718439223357198):0.05925180026887092):0.007597567051885242):0.13216683072844243):0.018977376055421945,(((((Phrynobatrachus_sandersoni:0.15967486186668467,(Phrynobatrachus_dendrobates:0.12071023001637841,Phrynobatrachus_krefftii:0.09654214204944418):0.02682613435419646):0.033574370266666584,((((Phrynobatrachus_dispar:0.04312408095350109,Phrynobatrachus_leveleve:0.04122263926117896):0.04515749761984198,Phrynobatrachus_calcaratus:0.08530140373394636):0.013143254309615032,Phrynobatrachus_mababiensis:0.09285408558944472):0.09163481972613444,((Phrynobatrachus_auritus:0.17057658504282933,Phrynobatrachus_natalensis:0.11542086681932862):0.0348539718873949,((Phrynobatrachus_africanus:0.092883951920816,Phrynobatrachus_cricogaster:0.13228466956324691):0.01602083981969161,Phrynobatrachus_acridoides:0.19543694995036384):0.01964974336173751):0.10289826810018694):0.10073863232885627):0.06492262028580856,(((Conraua_crassipes:0.08650393461643997,Conraua_goliath:0.03436002289249391):0.033755312623435764,Conraua_robusta:0.06777717077499508):0.09747512616239662,((((Aubria_subsigillata:0.18163673148443807,(Pyxicephalus_edulis:0.04041083876996451,Pyxicephalus_adspersus:0.06437516048710691):0.04238715291728079):0.12403700538965817,(Anhydrophryne_rattrayi:0.11611489792389686,((Tomopterna_natalensis:0.056789980343925864,(Tomopterna_krugerensis:0.03201166644585016,((Tomopterna_tuberculosa:0.04192573636437682,Tomopterna_delalandii:0.010360926695385115):0.012586593010357262,(((Tomopterna_tandyi:0.02455298593863416,Tomopterna_cryptotis:0.0057064735799268905):0.016726719796781815,Tomopterna_damarensis:0.0011101649751385045):0.00563193877158779,(Tomopterna_marmorata:0.024063541743338888,Tomopterna_luganga:0.026920062024956587):0.01864562325623444):3.51422724235035E-6):0.03054334028233262):0.017450343428157118):0.06998308367051005,(((Natalobatrachus_bonebergi:0.09903598919150869,((Arthroleptella_villiersi:0.004132365823633657,Arthroleptella_lightfooti:0.008610914035211635):0.03847984141304231,((Arthroleptella_subvoce:0.014277355363631637,Arthroleptella_bicolor:0.017872363613447708):0.005072122201254288,(Arthroleptella_landdrosia:0.01576382029046417,Arthroleptella_drewesii:0.013796564211685982):0.011233960243307499):0.006395300390661342):0.05142087231635133):0.03647635872082496,(Amietia_fuscigula:0.0871383704723144,(Strongylopus_grayii:0.0711507294435188,(Amietia_vertebralis:0.023801738153764424,Amietia_angolensis:0.03903451279989068):0.010392078716912225):0.02053303286988349):0.02419994385973035):0.006625500414209578,((Strongylopus_fasciatus:0.1291267255765178,Strongylopus_bonaespei:0.043257037957701236):0.06583965277995975,(((Cacosternum_nanum:0.025805797607016134,(Cacosternum_boettgeri:0.05368346583944682,Cacosternum_capense:0.05313946469614122):0.02349114100231704):0.019395135678575218,(Cacosternum_platys:0.007630787017100105,Microbatrachella_capensis:0.006530513260606815):0.07829091168309953):0.03871040864409095,Poyntonia_paludicola:0.11358632231571064):0.02677766007161403):0.020933450822974795):0.005496057144484663):0.0247227803394513):0.012303672759106767):0.028080048284431343,((Petropedetes_martiensseni:0.017570802161490293,Petropedetes_yakusini:0.01994739212103281):0.14487823735038116,(((Petropedetes_newtoni:3.51422724235035E-6,Petropedetes_parkeri:0.001356984537612692):0.06468708988305069,Petropedetes_cameronensis:0.025370804553394834):0.05666002644743063,Petropedetes_palmipes:0.07352609402466355):0.04594944664526872):0.13279299741233425):0.021035983482928313,((((Indirana_semipalmata:0.07692321590145436,Indirana_beddomii:0.09441760162688262):0.15727154982782465,(((Occidozyga_lima:0.19866716845661758,(Occidozyga_baluensis:0.16787834256285333,(Occidozyga_laevis:0.1349018831336204,(Occidozyga_magnapustulosa:0.07114385482675202,Occidozyga_martensii:3.51422724235035E-6):0.1474841254105712):0.018135186775504273):0.012182250177281171):0.028000617178139658,(Occidozyga_borealis:3.51422724235035E-6,Ingerana_tenasserimensis:3.51422724235035E-6):0.19779608463837556):0.06192864902833021,((((Sphaerotheca_breviceps:0.06805314598095911,Sphaerotheca_dobsonii:0.08418159821833822):0.08342296895434874,((Fejervarya_mudduraja:0.09509301345857568,((((Fejervarya_granosa:0.014311745278609532,Fejervarya_syhadrensis:0.02939062625084238):0.034099172820789725,Fejervarya_caperata:0.043474365389655725):0.018636694527744253,(Fejervarya_rufescens:0.06227388732822408,Fejervarya_kudremukhensis:0.05814218172810066):0.013220914893883466):0.00848081020604562,(Fejervarya_pierrei:0.045727955213713686,(Fejervarya_kirtisinghei:0.026355658243655152,Fejervarya_greenii:0.016999428093486254):0.03075010366653641):0.0030290046846399342):0.0301388570898483):0.06969285039102552,((Fejervarya_vittigera:0.06639705190606597,Fejervarya_cancrivora:0.09279002314526484):0.04832248083587929,(((Fejervarya_limnocharis:0.0721017546049879,Fejervarya_sakishimensis:0.02178194290150706):0.022704324676004327,(Fejervarya_orissaensis:0.037266245781426614,Fejervarya_iskandari:0.04462284410232109):0.03054192907242319):0.06949574222449999,Fejervarya_triora:0.06882589613717296):0.013889006086278998):0.03332571140247153):0.017227194585311866):0.026079090362714438,((Nannophrys_marmorata:0.04660682807950223,Nannophrys_ceylonensis:0.041785897740611685):0.0699647482148753,((Hoplobatrachus_occipitalis:0.1116056777639218,(Hoplobatrachus_crassus:0.05336474262802545,(Hoplobatrachus_rugulosus:0.0377792019687381,Hoplobatrachus_tigerinus:0.044190479801824264):0.01710895412678589):0.03408794268798899):0.017561485777973573,(Euphlyctis_hexadactylus:0.1299527210572976,(Euphlyctis_ehrenbergii:0.05062415493534764,Euphlyctis_cyanophlyctis:0.030760367439283863):0.06907802767030388):0.01719642181443587):0.022836402489471073):0.029542657101985243):0.06108850609694466,(((((Limnonectes_leytensis:0.07022989693977842,((((Limnonectes_woodworthi:0.05364603546100809,(Limnonectes_visayanus:0.039122820919818814,Limnonectes_macrocephalus:0.039118012843325285):0.0040690598892999625):0.01016770175617313,((Limnonectes_modestus:0.06399549002443865,Limnonectes_heinrichi:0.04830113491695682):0.002973214989408817,Limnonectes_magnus:0.03657216962904713):0.003541711640296227):0.01285301831528728,(Limnonectes_arathooni:0.049237372032794345,Limnonectes_microtympanum:0.05166673865512617):0.00791725994644855):0.0034099033876346363,Limnonectes_acanthi:0.06649377136324007):0.011218879415459786):0.04103856647375832,((((((Limnonectes_shompenorum:0.025325305922250314,Limnonectes_macrodon:0.06795361844279307):0.0404531752207139,(Limnonectes_malesianus:0.03337641166170834,(Limnonectes_finchi:3.51422724235035E-6,Limnonectes_ingeri:0.0028068355619800837):0.04862809367450021):0.060612257529585):0.011570584373661931,Limnonectes_paramacrodon:0.09372541138660018):0.01862686321002418,(Limnonectes_poilani:0.0034683898829642574,Limnonectes_blythii:0.026099461734733397):0.07671296452862819):0.009426972316152507,(Limnonectes_grunniens:0.056637041670280226,Limnonectes_ibanorum:0.09796374331372491):0.016485390676007076):0.009408408557129488,(Limnonectes_leporinus:0.10022616183335631,(Limnonectes_palavanensis:0.12599389245990486,Limnonectes_parvus:0.05912238573586734):0.05635851578309423):0.010099912734545233):0.011727708076562152):0.03556733308117808,(((Limnonectes_kuhlii:0.0404950912628947,Limnonectes_bannaensis:0.04198735735825579):0.024268263450126445,Limnonectes_fujianensis:0.09004836040736663):0.04512800636498468,(Limnonectes_fragilis:0.07702023983160931,Limnonectes_asperatus:0.09174976944479808):0.007663551473035736):0.014407437825568319):0.007076599499525699,((Limnonectes_kadarsani:0.06029322972167184,Limnonectes_microdiscus:0.13614643585547095):0.052279974105330494,(Limnonectes_laticeps:0.12212086154247999,((Limnonectes_gyldenstolpei:0.06781657517253437,Limnonectes_dabanus:0.037174787999602575):0.0694890129129256,(Limnonectes_hascheanus:0.0195777795999792,Limnonectes_limborgi:0.01172485298805743):0.10460593684866067):0.027873542965906835):0.006601306422976045):0.018947860777178322):0.06220869171977756,(((Paa_fasciculispina:0.051020926994748195,Chaparana_delacouri:0.055868803546356226):0.01595864481936964,((Paa_yei:0.06123826796091295,((Paa_spinosa:0.040523095881948974,Paa_exilispinosa:0.02410382524767448):0.016931133467817126,Paa_jiulongensis:0.03552571627845341):0.02338810788505022):0.006219164059451537,((Paa_boulengeri:0.002171008445825413,Paa_verrucospinosa:0.005990353363292723):0.03921442586368561,(Paa_shini:0.07479702771363426,Paa_robertingeri:0.050977005555657935):0.0020129532150237033):0.013837785517202592):0.01455371612343928):0.015732083073104936,((((Nanorana_ventripunctata:0.008179007462750545,(Nanorana_parkeri:0.0453352800424607,Nanorana_pleskei:0.030725860528664155):0.006273042791818256):0.028202992870939456,(((Paa_maculosa:0.007393732965740943,(Paa_arnoldi:0.0027614058674225246,Paa_chayuensis:6.781423722330621E-4):0.00524781770257679):0.005813930767337131,Paa_medogensis:0.027229014767315887):0.005238513596308828,Paa_conaensis:0.025323755209626504):0.009756878172965677):0.006280852385979492,(Paa_taihangnicus:0.03364385215913648,(Chaparana_quadranus:0.03674773382413536,(((Paa_liui:0.0056918756869541965,Paa_yunnanensis:0.002041150285809114):0.011429857030995177,Paa_bourreti:0.020449228069286274):0.019558138174049364,(Chaparana_unculuanus:0.045481802499624954,(Chaparana_fansipani:0.029225446524332222,Chaparana_aenea:3.51422724235035E-6):0.05151882970544836):0.011890981151084529):0.0058505743627072365):0.0043736455314128215):0.009906021382317302):0.007327180834793165,Paa_liebigii:0.04222492890842812):0.04466176940300272):0.03823025290682112):0.022537651568407112):0.017577701013021134):0.02521581376925567):0.010200483289878853,((((Buergeria_oxycephlus:0.14733031687196835,((Buergeria_robusta:0.09279756401018038,Buergeria_buergeri:0.059428364292312054):0.030391709808763934,Buergeria_japonica:0.08579732070141453):0.01494216464283355):0.05402461395008456,((((Ghatixalus_variabilis:0.2302507876173849,((Chiromantis_vittatus:0.23931529608948865,((Chiromantis_xerampelina:0.0942996598392109,Chiromantis_rufescens:0.09362843741329853):0.024174583686235922,Chiromantis_doriae:0.10132611996231108):0.05544257835600968):0.03184733978922795,((Feihyla_palpebralis:0.14776867336979205,((Polypedates_fastigo:0.03725618500669031,Polypedates_eques:0.017637598378002967):0.11917817028934953,(((Polypedates_leucomystax:0.05908252726515238,(Polypedates_megacephalus:0.03217298727742093,Polypedates_mutus:0.02347919510006767):0.02209853678053448):0.015233263540630891,(Polypedates_cruciger:0.0444118220874027,Polypedates_maculatus:0.042095621512113456):0.03247589066114442):0.011569706032440525,Polypedates_colletti:0.07396410570439596):0.07754461217128232):0.045492465753767454):0.020174687592213414,((((Rhacophorus_maximus:0.06280321807895307,(((Rhacophorus_moltrechti:0.037144890754723596,((Rhacophorus_omeimontis:0.026893860997424603,Rhacophorus_taronensis:0.015194371040735542):0.014009160591933429,((Rhacophorus_minimus:0.042565881444775584,Rhacophorus_hui:0.015916309983635864):0.011875499444469896,((Rhacophorus_puerensis:0.008642722495434548,Rhacophorus_dugritei:0.013450603043358687):0.010595068461153224,Rhacophorus_hungfuensis:0.025995669984662413):5.317150420227457E-4):0.007434580356568759):0.002647970494797497):0.002724071257970153,(Rhacophorus_arboreus:0.04319138732589707,Rhacophorus_schlegelii:0.04090823288260053):0.004287824374481933):0.008519469583794513,(Rhacophorus_nigropunctatus:0.032238833696112794,Rhacophorus_chenfui:0.049446018228861854):0.02188858414598462):0.006612172385594863):0.004439225663909923,Rhacophorus_dennysi:0.07719865557647196):0.004623344364358125,Rhacophorus_feae:0.057789205854714656):0.05328863514462501,((Rhacophorus_rhodopus:0.0530984308497703,(Rhacophorus_bipunctatus:0.04413781752143678,((Rhacophorus_lateralis:0.14512084992896793,Rhacophorus_reinwardtii:0.039041879413005846):0.024492981780633624,Rhacophorus_kio:0.034395336078787274):0.01829385718937674):0.009126032711285991):0.030044647786162417,((Rhacophorus_calcaneus:0.107882363175999,Rhacophorus_malabaricus:0.10327497917980691):0.004181617371843435,(Rhacophorus_orlovi:0.0839949826471847,Rhacophorus_annamensis:0.13236000432177936):0.02978069952612547):0.005780047736830641):0.03338619028635103):0.015393628710472163):0.004713324787444068):0.0037349934052689285):0.012318842051895313,((Theloderma_moloch:0.12591990622038945,(((Philautus_carinensis:0.025205666918361725,(Kurixalus_hainanus:0.030431032160448065,Kurixalus_odontotarsus:0.013375748640751207):0.009263192608850251):0.07181272806322193,((Kurixalus_idiootocus:0.004179818672230727,Kurixalus_eiffingeri:0.04836488210585982):0.025891574824946677,Philautus_banaensis:0.07944217572261837):0.02303591202400114):0.043966737352676845,(((((Philautus_tinniens:0.02826320950825592,Philautus_signatus:0.024560345468796384):0.027876437570068635,(Philautus_charius:0.030918815184899032,Philautus_griet:0.04881477558241401):0.05375896638062847):0.03599713191861304,(Philautus_ponmudi:0.04854401268615183,((Philautus_gryllus:0.0480738874347269,((Philautus_tuberohumerus:0.040208256261654314,Philautus_bombayensis:0.03657194482600098):0.042824091582194745,Philautus_menglaensis:0.056781748639684264):0.00519315156419744):0.012973020054696227,Philautus_longchuanensis:0.04303709929545903):0.04653019140663531):0.0170122628191586):0.018301380018490677,(((Philautus_bobingeri:0.06646415576315355,Philautus_anili:0.06436914404471178):0.014169003485240979,((Philautus_nerostagona:0.08295673975544299,(Philautus_travancoricus:0.037368301121311336,Philautus_neelanethrus:0.06550451985285345):0.04386600182106352):0.022233784661429633,Philautus_graminirupes:0.03234092945639179):0.016142105844638693):0.008964881052639838,(Philautus_glandulosus:0.05244179023177748,Philautus_beddomii:0.04144491750266117):0.013307021271456264):0.012970583205677915):0.02769138472329835,(((Philautus_leucorhinus:0.010910042429603316,Philautus_wynaadensis:0.0038198914749589387):0.07049365388755573,Philautus_zorro:0.12786828645076023):0.021492521730250565,(Philautus_simba:0.12372388077083227,(((Philautus_tanu:0.07192490941051223,((Philautus_pleurotaenia:0.08741125857618441,(Philautus_hoffmanni:0.01394312234828371,Philautus_asankai:0.010416849434843596):0.033049010285124886):0.026865645152412323,Philautus_ocularis:0.05815777724843236):0.01698463958169791):0.0038818300238763754,(Philautus_mittermeieri:0.01811627220884601,Philautus_decoris:0.025913885155816756):0.10128758877107824):0.026811238888097687,((Philautus_papillosus:0.04992202258925763,(Philautus_steineri:0.03582560632152969,Philautus_microtympanum:0.03382156683322269):0.015143685536127416):0.012219120970718289,(((Philautus_stuarti:0.06285112313988077,Philautus_popularis:0.028959385917075392):0.024738290882888522,((Philautus_schmarda:0.0705845383140541,Philautus_cavirostris:0.08819792923713492):0.00721966479039789,Philautus_lunatus:0.045887802060632574):0.009668868578616001):0.014320553427348717,(Philautus_mooreorum:0.03516745214552825,(Philautus_femoralis:3.51422724235035E-6,Philautus_poppiae:0.0064889688762172285):0.02289615396447227):0.0331367751638452):0.014734035224069951):0.00805215044972048):0.007072687570235846):0.01267050050455756):0.05270256889448274):0.0522043460771777):0.010647795131537293):0.01099046383957769,((Kurixalus_jinxiuensis:0.042440863331213934,(Philautus_quyeti:0.07258875511272936,Gracixalus_gracilipes:0.07019586493673285):0.027606756064719823):0.05214679504147278,(Philautus_ingeri:0.13315045627638128,(((Philautus_petersi:0.06814826809310351,Philautus_mjobergi:0.0994069526891823):0.022752024999343368,(Philautus_surdus:0.0038051263128363795,Philautus_acutirostris:0.0011129333142972395):0.09169681448767789):0.03291669914624818,(Philautus_aurifasciatus:0.12331070378615888,Philautus_abditus:0.10819408555139828):0.024799799284178987):0.023786807800162145):0.04610669695647408):0.006029607099939422):0.005807044985079551):0.011540467034199878,((Nyctixalus_pictus:0.0761076523939794,Nyctixalus_spinosus:0.1886574723964793):0.028123872762387682,((Theloderma_rhododiscus:0.007324888623053517,(Theloderma_asperum:0.009016368906584441,Theloderma_bicolor:0.031094830620540175):7.318858189264799E-5):0.025489587514125077,Theloderma_corticale:0.09632184798877648):0.03676821243856158):0.03757794885727697):0.01386371882776441,(Philautus_hainanus:0.027436585511059843,(Liuixalus_romeri:0.020735973356129176,Philautus_ocellatus:0.0212333261956055):0.009307416076954702):0.16205182987069208):0.04393381595305598):0.04602999271434721,(((Aglyptodactylus_laticeps:0.10215015171937292,Aglyptodactylus_madagascariensis:0.057054987957471416):0.07652000768830011,Laliostoma_labrosum:0.1054454489653598):0.04312658503627853,(((((Boophis_boehmei:0.06648364937547446,Boophis_goudotii:0.0740409241303529):0.028938672509408955,(Boophis_madagascariensis:0.08814966822902587,Boophis_luteus:0.083449969541097):0.04150868888222694):0.035847560480475466,(((Boophis_occidentalis:0.02954881003047728,Boophis_albilabris:0.030653114217844626):0.0652757487805942,((Boophis_viridis:0.10786610427739314,Boophis_rappiodes:0.08104824004702532):0.047957439179964895,(Boophis_sibilans:0.12166485316743043,Boophis_microtympanum:0.09589889605644471):0.012639433396217389):0.012944176703067186):0.004730320215369582,(Boophis_vittatus:0.0761117664414639,Boophis_marojezensis:0.061818860214519405):0.05413440108433062):0.01374117123849278):0.06766605455202114,(((Boophis_xerophilus:0.06224439376603649,(Boophis_doulioti:0.030293812056038847,Boophis_tephraeomystax:0.02824782231050339):0.051877849921731514):0.017171348776577503,Boophis_idae:0.10059345527755952):0.021595941547362902,Boophis_pauliani:0.11317851241727833):0.010859034028596152):0.050783548356493055,((((((Guibemantis_liber:0.05335314066922926,Guibemantis_bicalcaratus:0.058182814517929254):0.010639071752109612,Guibemantis_albolineatus:0.04871381000593294):0.04070056646736493,(Guibemantis_depressiceps:0.04599168682843341,Guibemantis_tornieri:0.03505104192051969):0.033728042175094244):0.03946208045977631,(((Blommersia_domerguei:0.05042476660853011,Blommersia_blommersae:0.05867616513858844):0.02788204799459333,(Blommersia_grandisonae:0.04386087177441577,(Blommersia_kely:0.030677244786833893,Blommersia_sarotra:0.04265025395905418):0.038138009519499645):0.02705226398181645):0.019786385085178576,Blommersia_wittei:0.07519972656328351):0.02184480078824391):0.02799637673736004,(Wakea_madinika:0.1257520475308968,(Mantella_bernhardi:0.058257721837322314,((Mantella_cowanii:0.01641800663753103,((Mantella_nigricans:7.952147662988242E-4,Mantella_haraldmeieri:0.010245911217108574):9.299731460325339E-4,Mantella_baroni:0.002510478812978812):0.007853084748529488):0.047627613728534426,(((Mantella_manery:0.023837642470317087,Mantella_laevigata:0.033141910185759356):0.021730042871337032,((Mantella_betsileo:0.02250063067857076,Mantella_viridis:0.007353439911209487):0.004957997508609896,(Mantella_expectata:0.02073587068119353,Mantella_ebenaui:0.0037514424728406857):0.0054836367401189644):0.021106531577181408):0.026115683694544103,(Mantella_madagascariensis:0.017047303036191147,((Mantella_aurantiaca:0.0020590434825478432,(Mantella_milotympanum:0.001891514558999741,Mantella_crocea:0.003094963799632443):0.0029798785252249846):0.00544416439009896,Mantella_pulchra:0.01741086963394032):0.01212435516417992):0.026418220655701517):0.014688135000972529):0.011332406892617056):0.026055618688499045):0.03014921519434747):0.03078283184206239,((Boehmantis_microtympanum:0.13994166403448458,(((((Gephyromantis_boulengeri:0.14950918680806918,(((Gephyromantis_ambohitra:0.1186614239280493,(Gephyromantis_klemmeri:0.09984700249340764,((Gephyromantis_striatus:0.016129351083992643,Gephyromantis_malagasius:0.032765279751400755):0.07369031926590101,(Gephyromantis_ventrimaculatus:0.09519209594263126,Gephyromantis_horridus:0.06712028321682588):0.0030116870717009346):0.01983090067346115):0.013631889390420518):0.0146739147756688,(Gephyromantis_webbi:0.12383351649735583,(Gephyromantis_rivicola:0.07283186334911645,Gephyromantis_silvanus:0.0593212833076704):0.010159581993761562):0.014635787425764395):0.00939362503044889,(((Gephyromantis_eiselti:0.051934738405588245,(Gephyromantis_decaryi:0.035895956677030494,Gephyromantis_leucocephalus:0.05454217393865153):0.015496895706630008):0.019580992196449844,(Gephyromantis_enki:0.02562344050647241,Gephyromantis_blanci:0.05393585694906208):0.05787849316159521):0.057725751297477326,Gephyromantis_asper:0.10061085950263104):0.018246182271585008):0.008460221900254571):0.007504244241205511,((Gephyromantis_luteus:3.51422724235035E-6,Gephyromantis_plicifer:3.51422724235035E-6):0.12818882048487626,(Gephyromantis_pseudoasper:0.1505426276503342,((((Gephyromantis_leucomaculatus:0.11124307979976476,Gephyromantis_zavona:0.10633378393666423):0.020402027626175833,(Gephyromantis_salegy:0.05220167208607043,Gephyromantis_tandroka:0.0766993796984654):3.51422724235035E-6):0.02094667097487188,Gephyromantis_moseri:0.13621328206347866):0.005201631497026534,(Gephyromantis_granulatus:0.10569832509350816,(Gephyromantis_tschenki:0.06312984537360099,(Gephyromantis_redimitus:0.03436689043397156,Gephyromantis_cornutus:0.049587887388661774):0.00709785886062028):0.061812787147175106):0.002859195708134177):0.01575916962807059):0.011256775537661749):0.01205083896826724):0.0040678120180990315,Gephyromantis_sculpturatus:0.1437877942387062):0.017772323553842485,(Gephyromantis_azzurrae:0.09016145205473965,Gephyromantis_corvus:0.056767301283697974):0.043007273109697264):0.010820613187834326,((Mantidactylus_argenteus:0.0995334998609215,(((Mantidactylus_biporus:0.07913939427973,(Mantidactylus_opiparis:0.05334858042685462,Mantidactylus_charlotteae:0.05403186010334796):0.030349564737623198):0.034140164130915536,(Mantidactylus_ulcerosus:3.51422724235035E-6,((Mantidactylus_femoralis:0.03767314477884185,Mantidactylus_mocquardi:0.027203094377291813):0.023427141816891026,Mantidactylus_ambreensis:0.08029819753880892):0.0028933720051385133):0.02876473153535759):0.006781417249077803,Mantidactylus_lugubris:0.11347259631190285):0.00826530265331061):0.014874465828896866,Mantidactylus_grandidieri:0.06605541094862243):0.02023057260913889):0.026742613074065703):0.012291338034384772,((Spinomantis_peraccae:0.050884118841864195,Spinomantis_elegans:0.06882841067639697):0.01652070888890121,Spinomantis_aglavei:0.0879267024574338):0.05537641493958672):0.01083099452476991):0.040742487571837065):0.008957527688402016):0.03929694165466337):0.018777705217700853,((Staurois_latopalmatus:0.06302249947374754,(Staurois_natator:0.08924041540224469,(Staurois_tuberilinguis:0.0034075530364964437,Staurois_parvus:0.029091856837587186):0.06156956850129555):0.0328188898028289):0.1000732709021494,((Huia_melasma:0.08464990024664369,(((Huia_sumatrana:0.08253307854458562,Huia_masonii:0.052659973657240906):0.05295548697842138,(Rana_alticola:0.07121903935395205,Rana_curtipes:0.05722568133540912):0.06784492374831098):0.012311653354200279,((Meristogenys_kinabaluensis:0.04336465201326388,((Meristogenys_orphnocnemis:0.021570370627436546,Meristogenys_whiteheadi:0.0110594153639118):0.006275039395613164,(Meristogenys_poecilus:0.017227622992810675,(Meristogenys_phaeomerus:0.014369708933881963,Meristogenys_jerboa:0.03401693680761545):0.001252689095724512):0.008170531915654785):0.026467194553910205):0.03713681807577577,Huia_cavitympanum:0.1304539807901242):0.010457848560580322):0.0028508262326094134):0.033092605477438586,(((Amolops_spinapectoralis:0.10392459391397775,((Amolops_torrentis:0.07061594808277227,Amolops_hainanensis:0.06363325944563042):0.06347763225961162,((Amolops_ricketti:0.06345685473024973,Amolops_wuyiensis:0.03655784711549774):0.02455120508836048,(Amolops_daiyunensis:0.06282036799167048,Amolops_hongkongensis:0.049519417659640116):0.042622715117668644):0.010605462892799579):0.019605595230342138):0.016674863194132375,((Amolops_larutensis:0.17312980539570544,Amolops_cremnobatus:0.10484278605540641):0.028364385159055195,((Amolops_marmoratus:0.13566350110030814,Amolops_panhai:0.11697912512580928):0.048640791412258556,((((Amolops_granulosus:0.050764130056939756,Amolops_lifanensis:0.002020390538021148):0.04167306625756356,((Amolops_jinjiangensis:0.00903265940531416,(Amolops_liangshanensis:6.720339417358261E-4,Amolops_loloensis:0.005164589463113121):0.006281894263733544):0.0033284944300575104,(Amolops_mantzorum:0.041589237241043776,Amolops_kangtingensis:0.005780116874490021):0.006904503725404898):0.01190069330177945):0.007732885644710654,Amolops_viridimaculatus:0.051021679753431):0.00837240996196086,(Amolops_chunganensis:0.08149050007388413,Amolops_bellulus:0.04591681364288745):0.01491341538902088):0.02770227616589162):0.014172294881444156):0.01543918910611245):0.016506339001478246,(((Rana_fukienensis:0.04406243510074927,(Rana_porosa:0.02470726527854227,((Rana_nigromaculata:0.0017758556166154805,Rana_plancyi:0.006987665044016193):0.0031831155972957288,Rana_hubeiensis:0.009791341349926784):0.019397591022460706):0.005237659284714032):0.03635681304222574,(((Rana_cretensis:0.02686396040910847,(((Rana_bergeri:0.004021561800345787,Rana_shqiperica:0.012004182952807956):0.01420615304692531,(Rana_cerigensis:3.51422724235035E-6,Rana_bedriagae:0.013754615345798107):0.003049087239576181):0.009839463233196129,(Rana_epeirotica:0.020457568183064993,(Rana_ridibunda:0.004442733665769551,Rana_kurtmuelleri:0.005280257809686617):0.0154831438022899):0.00434063914371197):0.0045351334478031715):0.01865594852550567,(Rana_lessonae:0.005174454606974176,Rana_esculenta:0.0022278821931353836):0.053852934617458076):0.019671305596810632,(Rana_perezi:0.05368368830941028,Rana_saharica:0.05851585732863204):0.014731752620887063):0.023884531299746676):0.017644345536311133,(((Rana_luctuosa:0.16449799009806257,((((Rana_temporalis:0.04303844907747062,Rana_aurantiaca:0.10697671621876673):0.11880995738933946,((((Rana_arfaki:0.061674142273635484,(Rana_daemeli:0.035031624517020896,Rana_jimiensis:0.045633453116832555):0.003532831032345309):0.04152775024762762,(Rana_milleti:0.12534811624675002,Rana_gracilis:0.040063274533863436):0.015923690609975196):0.016445573726921375,(((Rana_latouchii:0.03200173331781947,(Rana_spinulosa:0.024024990614276043,Rana_maosonensis:0.01893379022954202):0.023911932059160555):0.002582783982353692,Rana_cubitalis:0.04384580810771868):0.026419800231504045,(Rana_nigrovittata:0.03487963544135764,Rana_faber:0.0339148452332411):0.034084900459065634):0.023537635286228806):0.0022055778404736904,Rana_malabarica:0.09435171361974615):0.00783897781123117):0.003741828932778496,(((Hylarana_nicobariensis:0.1412843264661007,((Amnirana_lepus:0.09261890046894626,Amnirana_albolabris:0.05584209801060765):0.04798841419747356,Amnirana_galamensis:0.12218014637889614):0.016965072421447958):0.013192513698828453,((Rana_banjarana:0.14519100915205377,(Rana_glandulosa:0.027670876935503547,(Rana_laterimaculata:0.02856508247683548,Rana_baramica:0.021279016037686834):0.012035872899423851):0.043829483352663956):0.01141632009327573,((Rana_signata:0.03564294056479104,Rana_picturata:0.06363508040331305):0.023295431549058093,Rana_siberu:0.09547754203003397):0.05988086042334578):0.022580013572149234):0.0025887798717740963,(Rana_mocquardii:0.052529789505237734,((Rana_labialis:0.008183294454236047,(Rana_eschatia:0.015830553868027352,Rana_parvaccola:0.031050441917603512):0.01504324016574443):0.010385203682790771,(Rana_chalconota:0.015216380568670343,(Rana_raniceps:0.06117812243114806,Rana_megalonesa:3.51422724235035E-6):0.01125783594831206):0.00865727578566211):0.061233333993022956):0.08034928424611049):0.007670658383409402):0.007825924488622028,((Rana_guentheri:0.0604243930387786,(Rana_miopus:0.07228556052637498,Rana_lateralis:0.06743928129772411):0.021456691339079906):0.025628841629959574,((Rana_macrodactyla:0.07922072148426236,Rana_taipehensis:0.0568789423734109):0.03032929223128862,Rana_erythraea:0.05820288831932492):0.07101392628748882):0.033080534853294694):0.011167179612049705):0.013499548195664521,((Rana_sanguinea:0.07942904628076462,(Rana_luzonensis:0.011276995075322661,Rana_igorota:0.009275544122749679):0.03957498666087492):0.05889203366231869,(Rana_minima:0.08970564292123062,((Rana_rugosa:0.027408312402167543,Rana_emeljanovi:0.040981300421232526):0.015785902150823424,Rana_tientaiensis:0.04160260167499577):0.04978670846866963):0.00565087764343201):0.023628059874374552):0.006599389419029077,(((Rana_pleuraden:0.039821550838713686,(Rana_adenopleura:0.01870733309957287,Rana_chapaensis:0.08032671875353035):0.023567147315248945):0.03911465472701262,((Rana_khalam:0.015578608377890974,Odorrana_absita:0.05623960792950121):0.025757016990900876,((Rana_ishikawae:0.0811334313844035,(((Odorrana_schmackeri:0.04702163043021501,Rana_hejiangensis:0.02920429503856153):0.01356766286706741,Rana_bacboensis:0.05599676229645224):0.021292528684784345,((Rana_megatympanum:0.02622292518561897,(Rana_tiannanensis:0.08758741600551252,((Rana_morafkai:0.006582450255325103,Rana_banaorum:0.014620012952913623):0.058474256738480945,((Odorrana_aureola:0.03651262650592577,Rana_livida:0.014854296794342695):0.02526330638962758,(Rana_chloronota:0.0364270422344613,Rana_hosii:0.06441840135527792):0.008944183168216635):0.016265289306894117):0.009723146262768689):0.004787215171432445):0.007788608792587099,((Odorrana_tormota:0.057052693681503036,(Rana_versabilis:0.04561718539197723,Odorrana_nasica:0.050040197429894746):0.01090578290847852):0.004794805838189409,((Rana_utsunomiyaorum:0.028445761524132782,Rana_swinhoana:0.021013482013082573):0.04760952421395159,(Rana_supranarina:0.02791932878867899,(Rana_narina:0.03801382271434115,Rana_amamiensis:0.011780991229239876):0.013870166246531519):0.03342333413750072):0.011720679771087011):0.011660750219115425):0.01835247669997818):0.005111528606467057):0.009747177181277506,(Odorrana_chapaensis:0.05756560159656864,((((Rana_vitrea:0.006882033683098186,(Rana_cucae:0.004321138816795841,Rana_compotrix:0.0021547140526836307):0.0039405285081074):0.011814264227517435,(Rana_iriodes:0.006848504769958634,(Rana_archotaphus:0.005573010335977759,Rana_daorum:0.0015665617011035315):0.0017464011768230819):0.0027693285697097305):0.01295550288468953,Odorrana_jingdongensis:0.005130236473062277):0.021458934673084434,((Rana_andersonii:0.0032483605184784993,(Odorrana_junlianensis:0.008059521485798466,(Rana_margaretae:0.018882373831612526,Rana_grahami:0.00537989007132799):0.0010852143811652078):0.002762071822020463):0.00279733134778585,Rana_hmongorum:0.0013057170407221495):0.0056751625602163205):0.024977928980954862):0.009082612987641228):0.0065106871644441955):0.05631388345776597):0.010629060428606101,(Rana_weiningensis:0.11341398116186337,((Rana_shuchinae:0.04759465373847546,(((Rana_pretiosa:0.011178445089172727,Rana_luteiventris:0.011484374180568906):0.024278847388002636,(Rana_boylii:0.03940347418030743,(Rana_aurora:0.03525231263696711,(Rana_cascadae:0.024377478851736807,Rana_muscosa:0.027320313732727624):0.007049210334599251):0.013225050020629146):0.009973270615605016):0.014130055864737225,(((Rana_zhengi:0.014317431512058897,Rana_johnsi:0.011649249712210465):0.04468104285507999,(((Rana_asiatica:0.02140460122250773,Pseudoamolops_sauteri:0.023260407548292596):0.010324655018097477,((Rana_latastei:0.03255383324350909,(Rana_graeca:0.034023442547965625,Rana_dalmatina:0.03724228904290032):0.015227762556207652):0.004945696508304834,(((Rana_tsushimensis:0.06326471120327085,Rana_okinavana:0.04100082863477588):0.02545339989365681,((Rana_iberica:0.02773384688512287,Rana_italica:0.03955902857718143):0.003680149803009822,(Rana_temporaria:0.024461513105149147,Rana_pyrenaica:0.017816700241980395):0.00963833132109964):0.003885685562381727):0.003321280058319299,(Rana_holsti:6.861352422513298E-4,Rana_macrocnemis:0.016173088361824194):0.029158961945660925):0.0025865331150898476):0.00669787367667106):0.004500502467400461,((Rana_amurensis:0.029915019959623097,Rana_kunyuensis:0.04386288688296749):0.026130505628373102,((Rana_arvalis:0.02258881162171222,(Rana_japonica:0.020067319372307855,(Rana_chaochiaoensis:0.03406458556838564,(Rana_omeimontis:0.020697284984497157,(Rana_longicrus:0.006418146219257719,Rana_zhenhaiensis:0.017147818999779036):0.006317093582399178):0.022324239397981192):0.008618706890623365):0.023186460904668322):0.0012967539903095092,(((Rana_huanrensis:0.02673850673379958,(Rana_chensinensis:0.01577619108184726,Rana_kukunoris:0.0018504409536392104):0.0026425482124678734):0.011543932507036694,(Rana_pirica:0.01787547713475213,Rana_dybowskii:0.03998645428750354):0.004306754732763865):0.006737381761853483,Rana_ornativentris:0.026964627224477077):0.01830035248591128):0.005136477686143453):0.0061627991665105545):0.0074256023613661955):0.006617948876617825,Rana_tagoi:0.12244996122733084):0.017327056474018637):0.0076573354594718195):0.015051262997551756,(((((Rana_maculata:0.050221537314202,(Rana_warszewitschii:0.0962335689173649,Rana_vibicaria:0.03708796604232067):0.05512141573915081):0.013778314295653644,((Rana_vaillanti:0.04944880183764994,Rana_juliani:0.061374717079919254):0.01714741807204423,(Rana_bwana:0.022836631151903056,Rana_palmipes:0.03148014426601184):0.011859388652529364):0.03519091600736271):0.0176965385276332,(((Rana_psilonota:0.051935453220555594,Rana_zweifeli:0.0419594589269163):0.0145731643343052,(Rana_tarahumarae:0.033882889578279986,Rana_pustulosa:0.06389120630369215):0.009844274363726908):0.02453579035764094,Rana_sierramadrensis:0.07911059235189666):0.0190301286841732):0.005015274767670722,(((Rana_magnaocularis:0.07141045439255539,(Rana_onca:0.0035734103186528445,Rana_yavapaiensis:0.010842647048479315):0.01103500054429762):0.010397621206233512,(((Rana_forreri:0.024228965072778125,Rana_omiltemana:0.056094635937525054):0.003538720394763976,(Rana_spectabilis:0.040517875838006756,(Rana_brownorum:0.01954578882018084,(Rana_macroglossa:0.01396440508501386,Rana_taylori:0.03978144535103517):0.004073149087822009):0.007729982037184089):0.0022800841291136962):0.003789286165947334,(((Rana_berlandieri:0.014139009296848776,(Rana_tlaloci:0.0021855418246213565,Rana_neovolcanica:0.001470798329219931):0.002542471542490019):0.005971203671231683,Rana_blairi:0.011568655962296157):0.010532716540777243,Rana_sphenocephala:0.025755603271525884):0.008283303304192781):0.005348922921533601):0.016324456835953655,((Rana_pipiens:0.022480773050150515,(Rana_chiricahuensis:0.017711058724524234,(Rana_montezumae:0.013184945116307615,Rana_dunni:0.010112077439988909):0.012191952867058863):0.016407488084099888):0.02341387611870183,((Rana_capito:0.007817231349365007,Rana_sevosa:0.0013073300170960353):0.019864864723730034,(Rana_areolata:0.02417000377354416,Rana_palustris:0.017281104113648717):0.003154113643951716):0.013885650267033544):0.008570154069767197):0.05018742426232068):0.020854877376947363,((Rana_catesbeiana:0.02026651774909701,((Rana_grylio:0.04364852833584274,Rana_septentrionalis:0.03226524783920748):0.0068180476025132795,(Rana_virgatipes:0.04239207008993619,(Rana_heckscheri:0.020724375701007384,(Rana_clamitans:0.0018319151173938914,Rana_okaloosae:0.0064731909132441316):0.010340520849428108):0.007197890559733265):0.0047145328919001896):0.009309394842348257):0.0342176549205389,Rana_sylvatica:0.06277738631699288):0.0077185945098427414):0.01964629772068223):0.009676704395673743):0.012008227288596655):0.009623236134737352):0.0060841452511267895):0.0074153500756528904):0.013240202953851539):0.02154737232861123):0.07255713311129935):0.012868950591463167):0.005292591340164026,((Lankanectes_corrugatus:0.12493229475912412,(Nyctibatrachus_major:0.04491213115045423,Nyctibatrachus_aliciae:0.0476203744536436):0.14461506799928625):0.033707849577893904,(Ingerana_baluensis:0.1904480378565742,(((Platymantis_hazelae:0.041679310684237816,Platymantis_montanus:0.040612609456875365):0.044523017042592185,(Platymantis_corrugatus:0.0728690547477232,((Platymantis_naomii:3.51422724235035E-6,Platymantis_mimulus:0.02559674874241205):0.030813986434689158,Platymantis_dorsalis:0.05257698806801976):0.05006938942926147):0.05804356280492249):0.07214569154299487,(((Ceratobatrachus_guentheri:0.09132012948174961,(Platymantis_punctatus:0.17215702127405735,(Platymantis_vitiensis:0.16299635841174873,Discodeles_guppyi:0.07595482612740873):0.018595427768908823):0.027752833195579037):0.016461797211844258,((Platymantis_wuenscheorum:0.21036640332403742,Platymantis_bimaculatus:0.13839950112354868):0.0888496273437796,(Platymantis_weberi:0.11764426969897833,(Platymantis_papuensis:0.01976725202343783,(Platymantis_cryptotis:0.022124915900258987,Platymantis_pelewensis:0.024405043455589095):0.010914425756354722):0.0534120226297621):0.05594041709100482):0.014355565883565314):0.009158368370868742,Batrachylodes_vertebralis:0.20651437480342583):0.041221679088963604):0.07348856418588408):0.08209684794219882):0.019030114287854272):0.006787351147547254):0.006142206021984638):0.0058615703853905484):0.008045210276618156,(Micrixalus_fuscus:0.1049524328631414,Micrixalus_kottigeharensis:0.07280537913723813):0.21803703732265986):0.014268790724219878,(Hildebrandtia_ornata:0.158873308284323,(((Ptychadena_taenioscelis:0.0881276193423027,Ptychadena_pumilio:0.1004168880922404):0.012468202458090383,(Ptychadena_newtoni:0.06620001390462044,Ptychadena_mascareniensis:0.07722482840808483):0.06542850898168838):0.06129110225967346,(((Ptychadena_longirostris:0.11684273220370935,(Ptychadena_anchietae:0.05490227893957198,(Ptychadena_oxyrhynchus:0.04674422516057717,Ptychadena_tellinii:0.023909442195499713):0.04009282155932016):0.018465194969942315):0.039143610774483195,(((Ptychadena_mahnerti:0.06335662305983897,Ptychadena_porosissima:0.02753718590386438):0.05235374821893078,(Ptychadena_bibroni:0.0918008191214947,Ptychadena_aequiplicata:0.16070844772477866):0.0211256650711783):0.051003570921497744,Ptychadena_cooperi:0.12260395156131357):0.013054299729922359):0.025399270050053442,Ptychadena_subpunctata:0.13397772983164646):0.02081193770692045):0.05602007460767652):0.08107975155843303):0.08407173816656903):0.16008864895669617):0.013568082843847286):0.030193393113446293):0.2034733643365477,(((Scaphiopus_couchii:0.03271566574813081,(Scaphiopus_holbrookii:0.02577217542005343,Scaphiopus_hurterii:0.03537246778869653):0.01776767451734391):0.09749515225312969,(Spea_multiplicata:0.06615323103113746,((Spea_intermontana:0.012714899982533546,Spea_bombifrons:7.562301506955574E-4):0.006446019834085123,Spea_hammondii:0.013293154680574626):0.017024838377916667):0.05678023520914898):0.19964527073584404,(((Pelobates_syriacus:0.019224527879006477,(Pelobates_fuscus:0.03134771006335052,(Pelobates_varaldii:0.018958743775974524,Pelobates_cultripes:0.026986546823587993):0.04037373258089995):0.027418987410968466):0.18549501205860186,((((Leptolalax_arayai:0.0870037958625464,Leptolalax_pictus:0.09664235027437006):0.15103190132407054,((Leptolalax_bourreti:0.04288870020690991,Leptolalax_pelodytoides:0.055666266549975814):0.029828553184002675,(Leptolalax_liui:0.07715075507522359,Leptolalax_oshanensis:0.06791122876143525):0.01660623091212283):0.043647523955287024):0.11835832425085047,(((Scutiger_chintingensis:0.032568596504263005,(Scutiger_glandulatus:0.013303824018972888,((Scutiger_mammatus:0.01879768946080866,(Scutiger_tuberculatus:0.012019684429878542,Scutiger_muliensis:0.01245564179264718):0.004896375217296469):0.010702324458015096,Scutiger_boulengeri:0.013395824816551157):0.0024224055090360085):0.0110783396453439):0.07936277151210507,(Oreolalax_rhodostigmatus:0.06749848364343576,((Oreolalax_lichuanensis:0.02227898738746576,(Oreolalax_schmidti:0.015882336439456454,Oreolalax_pingii:0.008433756301954656):0.014571095258539717):0.0397794880852933,(((Oreolalax_omeimontis:0.017235477583835493,(Oreolalax_nanjiangensis:0.010295386413828288,Oreolalax_popei:0.0027289231859991604):0.010842098611292946):0.029821081248779756,(Oreolalax_chuanbeiensis:0.02824915825512472,Oreolalax_multipunctatus:0.02274445391619315):0.021765821170491233):0.030128922444184948,(Oreolalax_rugosus:0.026515885714608774,(Oreolalax_jingdongensis:0.03255947066005231,((Oreolalax_liangbeiensis:0.006617795819651155,Oreolalax_major:0.007514646338259883):0.006454741461204142,Oreolalax_xiangchengensis:0.0115509636697753):0.002209268041597386):0.011261423996929474):0.016504259301904137):0.0091874278488061):0.012302972240847433):0.05194339966959845):0.018897422616554162,(((Leptobrachium_montanum:0.03568108886274149,Leptobrachium_gunungense:0.02436848969588325):0.06496279233053928,(Leptobrachium_smithi:3.51422724235035E-6,Leptobrachium_hasseltii:3.51422724235035E-6):0.12783481280961337):0.053228820293991896,(((((Leptobrachium_leishanense:0.02789233680126063,Leptobrachium_liui:0.01871675708543069):0.009703995493569668,((Leptobrachium_chapaense:0.01147743468556177,Leptobrachium_huashen:0.012662374757983067):0.030277905154050915,(Leptobrachium_ailaonicum:0.0140367153120465,Vibrissaphora_echinata:0.006518245125877532):0.015296403869089377):0.004384491358274107):0.003541266365693655,Leptobrachium_boringii:0.032876123873109425):0.018105434171081035,Leptobrachium_promustache:0.03583596460382915):0.0720654282144993,(Leptobrachium_banae:0.06608442088437479,(Leptobrachium_xanthospilum:0.0808439809555968,(Leptobrachium_hainanense:0.07954364242499462,(Leptobrachium_mouhoti:0.03107683196372655,Leptobrachium_ngoclinhense:0.024501452835515712):0.023563735145399594):0.009854777666346792):0.013404377384776064):0.05017997837940303):0.037634322890768374):0.020845026133704786):0.054140180997829544):0.0728655255570591,((Ophryophryne_hansi:0.053066125459931564,Ophryophryne_microstoma:0.14971207152766194):0.06809886008596808,(((Xenophrys_major:0.05434952841240809,Megophrys_lekaguli:0.051808974139271616):0.044278490737494854,((Xenophrys_minor:0.07124455822214087,(Xenophrys_omeimontis:0.014943153808258187,Xenophrys_spinata:0.03200376078859856):0.0371407663376705):0.024022475204298212,((Brachytarsophrys_platyparietus:0.0020714105299906136,Brachytarsophrys_feae:0.0183427424464781):0.057334611948281974,(Xenophrys_shapingensis:0.024134833082402253,Xenophrys_nankiangensis:0.020638977631814517):0.0376526001469135):0.019576528376802873):0.012036529653604057):0.017067816427775,(Megophrys_nasuta:0.05157093888632729,Xenophrys_baluensis:0.048725587831565435):0.09658755949893466):0.020624164600971603):0.14874061620902146):0.1255874261695627):0.0522247759442755,((Pelodytes_punctatus:0.01518482983591855,Pelodytes_ibericus:0.004730857457114724):0.10000293121302398,Pelodytes_caucasicus:0.03672786522261239):0.22790011974363683):0.045815961895696734):0.1436018660223735):0.058168555168530374,(Rhinophrynus_dorsalis:0.2826092111300013,((Pipa_carvalhoi:0.1319476998197759,(Pipa_parva:0.11668124473955786,Pipa_pipa:0.09462659039670993):0.09611304781600698):0.12919516320208033,(Hymenochirus_boettgeri:0.3112512032952951,((Silurana_tropicalis:0.034405372796553216,Silurana_epitropicalis:0.052849700612531324):0.0903674426860804,((Xenopus_muelleri:0.09257779392535499,Xenopus_borealis:0.05207540449181775):0.03531700765757319,(Xenopus_clivii:0.06958108274679246,((Xenopus_vestitus:0.045243945893605024,((Xenopus_laevis:0.051360371386752195,(Xenopus_victorianus:0.004256382233026562,Xenopus_petersii:0.008187778412045486):0.013801775187252292):0.006504464415233474,Xenopus_gilli:0.019866849411277494):0.011248609179108781):0.009677620578079355,(((Xenopus_fraseri:0.026631898350236063,Xenopus_pygmaeus:0.010014825310184937):0.023502069641693737,(Xenopus_andrei:0.022707770807429677,Xenopus_boumbaensis:0.01976975348102825):0.014894538447569793):0.00216795571155761,(Xenopus_wittei:0.016408279712189822,((Xenopus_ruwenzoriensis:0.012659758180364265,(Xenopus_amieti:0.009500628853122929,Xenopus_longipes:0.01355318477503281):0.003588364487608698):0.013042852845630204,Xenopus_largeni:0.07317212648458622):0.024061086908931287):0.013271547325567757):0.014264796560900593):0.02738941537862179):0.010821231988834293):0.06391740965482814):0.12267693165356061):0.054566221943245986):0.10169883538748742):0.0728326024286219):0.024646174934455874,(((Discoglossus_montalentii:0.055215544589440106,(Discoglossus_sardus:0.02575753313166643,((Discoglossus_jeanneae:0.025920630537799635,Discoglossus_galganoi:0.017452648959484146):0.015469664547602527,Discoglossus_pictus:0.03876091595764626):0.011423025153994244):0.05145716611921924):0.16766467750858824,(Alytes_cisternasii:0.05542807864099237,(((Alytes_muletensis:0.010812024342000256,Alytes_dickhilleni:0.016172654024650936):0.006443284380259113,Alytes_maurus:0.012535298848035822):0.007576385357113548,Alytes_obstetricans:0.013610475006564145):0.08036026100818126):0.14685998364546762):0.08250524876256576,(((Bombina_orientalis:0.04447338355102943,(Bombina_bombina:0.050258039878646264,(Bombina_variegata:0.008008759340586835,Bombina_pachypus:0.014076690599106883):0.01810796175074006):0.014664143702271534):0.02747590816560664,(Bombina_maxima:0.01539004930835119,(Bombina_lichuanensis:0.00562208145163925,(Bombina_fortinuptialis:0.005633766949896051,Bombina_microdeladigitora:0.007969414900987157):0.0050726356816070475):0.012517995319435296):0.019629994056065143):0.12409623508204838,(Barbourula_busuangensis:0.05107769190650476,Barbourula_kalimantanensis:0.07372439834345082):0.07417975659800047):0.141783902607317):0.07244728012713521):0.06609021850130688,((Leiopelma_hochstetteri:0.10431750080894076,(Leiopelma_archeyi:0.029112052670614577,(Leiopelma_pakeka:3.51422724235035E-6,Leiopelma_hamiltoni:0.003232938799455851):0.020224026480490966):0.05092180424177688):0.13049924550498732,(Ascaphus_truei:0.012664759909149855,Ascaphus_montanus:0.019251528763225466):0.25163121038785485):0.03665880250055297):0.3195020873841806,(((Cryptobranchus_alleganiensis:0.06482749424754401,(Andrias_davidianus:0.05297920987453793,Andrias_japonicus:0.01722680931429081):0.06223946269290178):0.1321872993331671,((Onychodactylus_japonicus:0.04011563051964715,Onychodactylus_fischeri:0.029874609312296307):0.08319709703196171,((Salamandrella_keyserlingii:0.07454901615791439,Pachyhynobius_shangchengensis:0.08093693811976056):0.007147612160543992,((((Batrachuperus_yenyuanensis:0.03991494593868354,((Batrachuperus_karlschmidti:0.015043233953685602,Batrachuperus_tibetanus:0.021010339303712926):0.009384646246807085,(Batrachuperus_londongensis:0.018630286249533317,Batrachuperus_pinchonii:0.01092515222035968):0.009236879239849375):0.010256987212122821):0.011467932742830656,((Liua_shihi:0.03607151620708848,Liua_tsinpaensis:0.03966970781269397):0.044560893684735675,(Pseudohynobius_shuichengensis:0.030330087094855827,Pseudohynobius_flavomaculatus:0.05280531018330869):0.04130968637239452):0.00736225770722938):0.007241926229433512,(((Paradactylodon_persicus:0.0035067069925983785,Paradactylodon_gorganensis:0.034895318514273334):0.041589648640612896,Paradactylodon_mustersi:0.08271525109554491):0.00894123674466313,Ranodon_sibiricus:0.032916448168151737):0.00530418562874314):0.01661721132916436,(Hynobius_retardatus:0.040849173741091066,(((((Hynobius_hidamontanus:0.02876565815045999,(Hynobius_yiwuensis:0.034197391070751225,(Hynobius_guabangshanensis:0.013605973866659299,(Hynobius_maoershanensis:0.011241747669228176,Hynobius_chinensis:0.008707611796736056):0.0023094023041047747):0.019525178177658788):0.004793801216734018):0.0032383051750547592,((Hynobius_nebulosus:0.029114630233942226,(Hynobius_stejnegeri:0.023596789170843745,(((Hynobius_takedai:0.013688447884325225,Hynobius_nigrescens:0.009913739648582207):0.011562644616634608,(Hynobius_tokyoensis:0.034067423402159215,Hynobius_abei:0.023397920825385103):0.0015051095952671744):0.0015682590526651435,Hynobius_lichenatus:0.031094042670952823):0.008903072188727434):0.005302790306212618):0.012441578159977703,((Hynobius_yangi:0.007048909564384192,Hynobius_leechii:0.040545679408385514):0.005394010024351316,(Hynobius_quelpaertensis:0.034572844657716525,(Hynobius_dunni:0.02429226644572465,(Hynobius_tsuensis:0.029348442583625747,Hynobius_okiensis:0.02424406286735768):0.010166966407611175):0.00850449778659257):0.003850263489450768):0.011929304416185458):0.0052828947612297075):0.006656803744843263,Hynobius_katoi:0.03639538787569757):0.0020252897220249533,(Hynobius_amjiensis:0.037732591250189546,Hynobius_naevius:0.02399984888267131):0.01275234604387768):0.013076097910152022,((Hynobius_kimurae:0.0310878896609115,Hynobius_boulengeri:0.030002073365619407):0.022343503909616258,(Hynobius_fuca:0.03135509694895143,(Hynobius_glacialis:0.01318795743832665,((Hynobius_sonani:0.009875074312838175,Hynobius_arisanensis:0.012521244145641703):0.007546567237867016,Hynobius_formosanus:0.020807778596591955):0.0049702616356224645):0.013868295381534742):0.010570930341500355):0.0074630093219751):0.010830866582560227):0.02233200755523605):0.011794724275445652):0.08469841980376537):0.05163414666540317):0.06929068223608785,(((Siren_lacertina:0.03582500852772953,Siren_intermedia:0.04284742530088724):0.09330826739709129,(Pseudobranchus_axanthus:0.05087196743872939,Pseudobranchus_striatus:0.0438888659575596):0.048871707416057376):0.29782244323157026,((((Ambystoma_cingulatum:0.04755986480935362,(((Ambystoma_opacum:0.06264752307660917,(Ambystoma_californiense:0.026730109922170297,(Ambystoma_tigrinum:0.01292049017390565,(Ambystoma_dumerilii:0.012006096352897037,((Ambystoma_ordinarium:0.02001347608763426,Ambystoma_andersoni:0.002262676704493864):3.51422724235035E-6,Ambystoma_mexicanum:0.007661139022734216):0.010135584447592752):0.004865113559824681):0.01101814408184553):0.01899271940784327):0.006247294952921808,(((Ambystoma_talpoideum:0.10120512075107288,Ambystoma_macrodactylum:0.03239377609767271):0.015464145446496097,((Ambystoma_jeffersonianum:0.047829468235517765,Ambystoma_laterale:0.02325922710271703):0.00712005087342033,Ambystoma_maculatum:0.07738318404288845):0.007486299731689042):0.00529893734914556,(Ambystoma_mabeei:0.026534470410370773,(Ambystoma_barbouri:0.01700128933679807,Ambystoma_texanum:0.02156918771210017):0.02514958166191709):0.011321780470276497):0.004387805279666236):0.007282946053571765,Ambystoma_gracile:0.05156129508857251):0.016163356172710683):0.16695994147263973,(Dicamptodon_tenebrosus:0.016083076354613327,((Dicamptodon_copei:0.011328627926808508,Dicamptodon_aterrimus:0.007876099315425725):0.0021955458593796223,Dicamptodon_ensatus:0.0015099765045769795):0.008008041748970459):0.20158738143602184):0.08202631026536938,((Salamandrina_perspicillata:0.04520251023171163,Salamandrina_terdigitata:0.04083094383899003):0.07815247784719219,(((((Notophthalmus_meridionalis:0.03569536560702414,(Notophthalmus_viridescens:0.02051929026961001,Notophthalmus_perstriatus:0.04060330778613368):0.00949957555126297):0.04560210947094672,(Taricha_rivularis:0.03319761752021458,(Taricha_granulosa:0.004168679932624281,Taricha_torosa:0.031172702024850545):0.025193676373652427):0.06333606620056291):0.01664289892517825,(((((Ommatotriton_ophryticus:0.020320014857824323,Ommatotriton_vittatus:0.038079552937639274):0.0463447916420271,((Neurergus_kaiseri:0.023199945856041196,(Neurergus_microspilotus:0.012163674338543528,Neurergus_crocatus:0.010759244727434331):0.017617962389023592):0.013063257153050286,Neurergus_strauchii:0.03381152580773073):0.0358998738963561):0.018414567751864397,((Calotriton_asper:0.017166374273328705,Calotriton_arnoldi:0.03946466880427221):0.04050696077169857,(((Triturus_carnifex:0.019321719768786133,(Triturus_cristatus:0.03719175825007374,Triturus_karelinii:0.015008064864878247):0.004806973591934871):0.011748375924067652,Triturus_dobrogicus:0.015108969494711045):0.029111660810166026,(Triturus_pygmaeus:0.019856355359253595,Triturus_marmoratus:0.011588247094699878):0.0380212423004364):0.03045983611363332):0.013575145608300344):0.020193436242433388,((Ichthyosaura_alpestris:0.14313468362681694,(Laotriton_laoensis:0.05067447545673734,((Pachytriton_brevipes:0.01951651618714201,Pachytriton_labiatus:0.01961029832824607):0.02569946207749435,(((Paramesotriton_chinensis:0.04251887591468648,(Paramesotriton_hongkongensis:0.04161638872968608,((Paramesotriton_fuzhongensis:0.012612868739570052,Paramesotriton_guangxiensis:0.011025940966546377):0.007025156452001444,Paramesotriton_deloustali:0.021938853977457474):0.005396595806239017):0.0056540371075273965):0.012055189143965682,(Paramesotriton_zhijinensis:0.02408331442072267,Paramesotriton_caudopunctatus:0.039310429714434374):0.015437685425212374):0.019026058059419714,((Cynops_ensicauda:0.04740894608019164,Cynops_pyrrhogaster:0.03497185541875888):0.009439226590401556,((Cynops_orientalis:0.03498920658205913,Cynops_orphicus:0.04802466231073344):0.024569789625303946,Cynops_cyanurus:0.04882760829369551):0.0038208921230953037):0.009183712479001026):0.008068850254734288):0.006945244432752097):0.023050177935665588):0.028518052526854448,(Euproctus_montanus:0.064930582975601,Euproctus_platycephalus:0.04688600009535361):0.05608811746937193):0.011156019319431578):0.020375894379828015,(((Lissotriton_montandoni:0.006625599255405903,Lissotriton_vulgaris:0.012501043179911029):0.06025915682872377,(Lissotriton_helveticus:0.09625471481457948,Lissotriton_italicus:0.032869180818382415):0.007390770156469512):0.009215337851116221,Lissotriton_boscai:0.06792524846054884):0.0433697986814785):0.021933622038051707):0.016773588356486544,(((Pleurodeles_nebulosus:0.021469366878047357,Pleurodeles_poireti:0.01929055645961485):0.009766646397179276,Pleurodeles_waltl:0.0388131968739298):0.05538962491087751,((Echinotriton_chinhaiensis:0.0343977705487183,Echinotriton_andersoni:0.03617529435810767):0.020151202701086116,(((Tylototriton_taliangensis:0.021355451683722996,(Tylototriton_shanjing:0.019813589437403394,(Tylototriton_kweichowensis:0.018736708892812634,Tylototriton_verrucosus:0.017279011361414975):0.00884289879251076):0.0038640234599713126):0.007873576600309995,Tylototriton_wenxianensis:0.020196673138094326):0.005669408460393959,Tylototriton_asperrimus:0.03587991163870559):0.011293802226073093):0.02224997195031664):0.0400505057191802):0.05451464759890507,(((Lyciasalamandra_luschani:0.01986239119173575,((Lyciasalamandra_helverseni:0.037192381291540365,((Lyciasalamandra_atifi:0.025117698034664276,(Lyciasalamandra_billae:0.018968290376548354,Lyciasalamandra_antalyana:0.051133640407020814):0.026074615064059926):0.009726260000035144,Lyciasalamandra_flavimembris:0.0443450470897955):0.010933949798464921):3.51422724235035E-6,Lyciasalamandra_fazilae:0.03389609357584312):0.012916606153702664):0.02803924794727258,((Salamandra_infraimmaculata:0.009153370869271708,(Salamandra_algira:0.049601595345181916,Salamandra_salamandra:0.008674674291939513):0.007926967893735395):0.0069524148226779445,(Salamandra_atra:0.020222489581288295,(Salamandra_lanzai:0.007458427913609389,Salamandra_corsica:0.021741745539362513):0.006952185741509651):0.0024449050075179735):0.029561895030413638):0.06278649107045871,(Mertensiella_caucasica:0.11500166417860742,Chioglossa_lusitanica:0.1752898810816301):0.03406740459245218):0.019380139804351677):0.04907721532536579):0.1299120193082103):0.03151469740220646,((((Rhyacotriton_variegatus:0.007461115432026732,Rhyacotriton_olympicus:0.011530970643825146):0.02724633163606887,(Rhyacotriton_cascadae:0.028695941365860927,Rhyacotriton_kezeri:0.0371856972767738):0.006157483529021992):0.22499755896640317,(((Hemidactylium_scutatum:0.22832153050663634,((((Batrachoseps_campi:0.03290199780991439,Batrachoseps_wrighti:0.024257586864093232):0.09559336992869777,(((Batrachoseps_relictus:0.017246774061440092,Batrachoseps_kawia:0.028701740249359103):0.021579919652406875,(Batrachoseps_regius:0.02583584671576959,Batrachoseps_diabolicus:0.03906966431838943):0.004025828643832533):0.02183544539212255,(((Batrachoseps_gregarius:0.019343449548979468,(Batrachoseps_nigriventris:0.01370158566958388,Batrachoseps_simatus:0.006937892234108772):0.012791777638436776):0.03535874730594275,Batrachoseps_attenuatus:0.06473194698010042):0.01703528783651234,(Batrachoseps_gabrieli:0.04436795849204853,((Batrachoseps_pacificus:0.0055426988801491545,Batrachoseps_major:0.029407267605359094):0.03644592231223824,Batrachoseps_gavilanensis:0.02906815711520803):0.018763907459873067):0.016067285273508217):0.0073801804470862865):0.0802471143981961):0.024204628777370132,((Thorius_minutissimus:0.04850367019314343,(Thorius_dubitus:0.04751218810020316,Thorius_troglodytes:0.02947991024534076):0.0485652098766931):0.17150092920191956,(((Chiropterotriton_arboreus:0.05654888953483959,((((Chiropterotriton_chondrostega:0.15090701067220433,Chiropterotriton_terrestris:0.09492489662847418):0.02718814536744623,Chiropterotriton_priscus:0.10633316076440784):0.027665665386491452,Chiropterotriton_magnipes:0.19460671127217286):0.024371069736035263,(Chiropterotriton_cracens:0.008426784830373903,Chiropterotriton_multidentatus:0.012985124532523373):0.07613586149592043):0.033588249518809794):0.057575620895559926,(Chiropterotriton_dimidiatus:0.13369472603473812,(Chiropterotriton_lavae:0.07898160707270703,Chiropterotriton_orculus:0.05205104611857581):0.05596828395615811):0.03467146611523153):0.11917666909164902,((((Bolitoglossa_hartwegi:0.06638330748800828,((Bolitoglossa_platydactyla:0.026419014328052028,(Bolitoglossa_flaviventris:0.07570724438916673,((Bolitoglossa_mexicana:0.008463773630920218,Bolitoglossa_odonnelli:9.809697378658398E-4):0.031868810130442626,(Bolitoglossa_lignicolor:0.07118723527056678,(Bolitoglossa_yucatana:0.021839074247839037,(Bolitoglossa_mombachoensis:0.009312434808543645,Bolitoglossa_striatula:0.01068903611274549):0.025924118092158308):0.0063522917065969775):0.0068074060378673605):0.01646668062728836):0.007899339811428034):0.027442339912827116,(Bolitoglossa_occidentalis:0.07878475143976146,Bolitoglossa_rufescens:0.09724587266713976):0.06777880210449026):0.005451755340542039):0.018749726959330644,((((Bolitoglossa_robusta:0.04551066616031681,(Bolitoglossa_colonnea:0.07358669553913726,Bolitoglossa_schizodactyla:0.03477261992209574):0.011696231254772737):0.014688759559113434,(((Bolitoglossa_sima:0.033587309146092606,Bolitoglossa_biseriata:0.032696842516148406):0.028844203454864,((Bolitoglossa_medemi:0.07371121799237694,Bolitoglossa_adspersa:0.02283551639361646):0.0074890897218606485,((Bolitoglossa_palmata:0.01664539571536447,Bolitoglossa_equatoriana:0.05634262593600112):0.008358850110404442,(Bolitoglossa_peruviana:0.014809922785635692,Bolitoglossa_altamazonica:0.022667801051217306):0.020231441064181503):0.006312029408525174):0.005762814696972339):0.006538990591047757,Bolitoglossa_paraensis:0.05985443344347674):0.01796384353568651):0.027678578756839375,((Bolitoglossa_subpalmata:0.019538041238113063,(Bolitoglossa_pesrubra:0.02346645477514421,Bolitoglossa_gracilis:0.01972801167601426):0.007808620843998598):0.04304500675745861,((Bolitoglossa_epimela:0.06127110544656582,(Bolitoglossa_marmorea:0.020616778242898256,(Bolitoglossa_minutula:0.030868055534120115,Bolitoglossa_sooyorum:0.013575896087374594):0.003767086709126201):0.005221515348117764):0.01680976035484069,Bolitoglossa_cerroensis:0.03391807286807647):0.028732403892944604):0.019960331910408576):0.05265297816055828,(((Bolitoglossa_lincolni:0.02645036777963853,Bolitoglossa_franklini:0.017149957443912348):0.02906158887650378,(((((Bolitoglossa_carri:0.032782333207814485,Bolitoglossa_conanti:0.030005097309428123):0.009335397145683265,(Bolitoglossa_diaphora:0.035228040414650164,Bolitoglossa_dunni:0.03196589148571105):0.007023746695628443):0.006870876357270288,(Bolitoglossa_flavimembris:0.01977332349055549,Bolitoglossa_morio:0.007245255104290605):0.048389424696106256):0.006100831576266559,(Bolitoglossa_synoria:0.01256542204848068,Bolitoglossa_celaque:0.007874272471430587):0.02554823482745403):0.0065075347354676995,(Bolitoglossa_decora:0.037539562811459044,(Bolitoglossa_longissima:0.05225408534640393,Bolitoglossa_porrasorum:0.03997493840273424):0.006837973696821607):0.0036682190343976036):0.014476605906916832):0.024789553617355478,((((Bolitoglossa_helmrichi:0.009699838781755432,Bolitoglossa_rostrata:0.03309667696702606):0.015359800927877438,Bolitoglossa_engelhardti:0.03217336152243108):0.038265436908275,((Bolitoglossa_macrinii:0.024474566987605143,Bolitoglossa_oaxacensis:0.015545587963874183):0.041637632173985514,((Bolitoglossa_riletti:0.021662152153564984,Bolitoglossa_hermosa:0.031877693231283644):0.03707161838136899,Bolitoglossa_zapoteca:0.03400330946329835):0.011671392818244407):0.034806627038497316):0.00650352506019876,(Bolitoglossa_dofleini:0.09126007669703089,Bolitoglossa_alvaradoi:0.07525733865477463):0.04352970751755557):0.005941459492428757):0.013430890713501722):0.010522162137817119):0.06745279365094906,(((((Lineatriton_orchileucos:0.047814563224100264,(Lineatriton_lineolus:0.048227459932015146,(Pseudoeurycea_lynchi:0.028459300508774526,(Pseudoeurycea_leprosa:0.040814505580274364,Pseudoeurycea_firscheini:0.03163885171529217):0.013617282920230275):0.01401012685496319):0.01611521022806804):0.010802605906328332,((((Pseudoeurycea_werleri:0.04418753310249815,(Pseudoeurycea_conanti:0.06486131745902986,Pseudoeurycea_obesa:0.031132668196807007):3.51422724235035E-6):0.0075912663184765205,Pseudoeurycea_mystax:0.04743188031903151):0.008482153068692896,Pseudoeurycea_nigromaculata:0.054024671028298776):0.014220353555284183,(Pseudoeurycea_unguidentis:0.05254563805825455,((Pseudoeurycea_saltator:0.0013325922355037602,Pseudoeurycea_juarezi:0.0010437355510986093):0.049973463914345775,Pseudoeurycea_ruficauda:0.031127111518373822):0.016085554790052702):0.01661514358914385):0.00969936583525725):0.012579242072193523,((((((Pseudoeurycea_robertsi:0.007670335082420623,Pseudoeurycea_altamontana:0.003191088456030655):0.005281479411628122,Pseudoeurycea_longicauda:0.012156401502979385):0.0016054121975864395,Pseudoeurycea_tenchalli:0.02464909763702179):0.010225314799296692,((Pseudoeurycea_melanomolga:0.009058831799112219,Pseudoeurycea_gadovii:0.0022453014413188893):0.010826231574132488,(((Pseudoeurycea_brunnata:0.03797624250477546,Pseudoeurycea_goebeli:0.038541647854295535):0.028255562837193576,Pseudoeurycea_exspectata:0.022645580468869365):0.043117191032382145,(Pseudoeurycea_anitae:0.04656392772810081,Pseudoeurycea_cochranae:3.51422724235035E-6):0.0069333875795307585):0.008847843918569936):0.0018409016716365869):0.02031434570102111,(Pseudoeurycea_smithi:0.012092039149030235,Pseudoeurycea_papenfussi:0.013496373853605886):0.0065766159089784134):0.007226487045045808,((Ixalotriton_parvus:0.035707011386663476,Ixalotriton_niger:0.03166628812154043):0.06847942084175673,Pseudoeurycea_rex:0.01757047574686014):0.0076507474084439855):0.0250904301444308):0.015459413293319492,((Pseudoeurycea_cephalica:0.038986323971855225,(Pseudoeurycea_scandens:0.058066907080922464,Pseudoeurycea_galeanae:0.0629586415993838):0.009417359070780867):0.03958020339837444,((Pseudoeurycea_maxima:0.02539184533227555,Pseudoeurycea_boneti:0.02089936389111307):0.02075821940411733,(Pseudoeurycea_bellii:0.026633538666616646,(Pseudoeurycea_gigantea:3.51422724235035E-6,Pseudoeurycea_naucampatepetl:0.026929822416298172):0.03972936898403057):0.020723820842984467):0.019087143720708816):0.020069013942022026):0.015892430362058,Parvimolge_townsendi:0.1582928639260128):0.01645934905347636):0.059892721696117424,((Nyctanolis_pernix:0.1322770139646475,Dendrotriton_rabbi:0.17815592627527424):0.02501354712970488,((Cryptotriton_alvarezdeltoroi:0.084519986001703,(Cryptotriton_veraepacis:0.03621586873148408,Cryptotriton_nasalis:0.04459596797523407):0.009607105844047333):0.15773817177024266,((Bradytriton_silus:0.13983463657159187,((Oedipina_gephyra:0.07033448550425489,((Oedipina_elongata:0.06634953155108626,Oedipina_carablanca:0.06984661631571251):0.018877165803938056,((((((Oedipina_poelzi:0.04457722342012769,(Oedipina_leptopoda:0.067123642727446,((Oedipina_uniformis:0.009601079890804434,Oedipina_pacificensis:0.01864783558554588):0.02132463765667971,Oedipina_gracilis:0.027347024151889634):0.03298226668922528):0.007837903025033897):0.01651769231445592,(Oedipina_collaris:0.08045085464360316,(Oedipina_cyclocauda:0.023017649481986095,Oedipina_pseudouniformis:0.006684072275464486):0.04524903214779259):0.014951266376391952):0.006498614482300863,Oedipina_grandis:0.03804692322197058):0.02017835720157669,Oedipina_stenopodia:0.05139325049892773):0.024290005843469718,(Oedipina_alleni:0.014588153802671271,Oedipina_savagei:0.0408739302088212):0.03513611960682353):0.013028600310766598,((Oedipina_complex:0.04890673513813067,Oedipina_parvipes:0.01857823362507106):0.01998017528824308,Oedipina_maritima:0.06178163892149627):0.0359464425913007):0.006671660874441953):0.014458358428276979):0.040785632691695425,(Oedipina_kasios:0.045577129154693655,Oedipina_quadra:0.042325451797985886):0.08277605158201674):0.02968277383365546):0.02592081831887697,(((Nototriton_lignicola:0.03871701654116341,Nototriton_limnospectator:0.036799139296145734):0.0026502597192723794,(Nototriton_barbouri:0.021564638065967104,Nototriton_brodiei:0.012093266886459984):0.028718211924905812):0.020308155593079757,(Nototriton_richardi:0.049154414979818054,((Nototriton_picadoi:0.003542530425896571,(Nototriton_abscondens:0.007602193710271877,Nototriton_gamezi:0.004406051032312586):0.004782043005670078):0.005986707120755813,Nototriton_guanacaste:0.008053272525111892):0.024341046696363666):0.008702496572623834):0.08768259350319166):0.03240189441990238):0.03249201307275824):0.025990203046754208):0.019395197768770904):0.006648982947844538):0.02920228304467515):0.024053748359687425,(((Stereochilus_marginatus:0.08708197594023871,(Pseudotriton_montanus:0.014709205825137808,Pseudotriton_ruber:0.01568734971847331):0.04673751756442568):0.011644810434606502,(Gyrinophilus_porphyriticus:0.01871420091910824,(Gyrinophilus_palleucus:0.00534246307493022,Gyrinophilus_gulolineatus:0.00609133791306903):0.021128185375537012):0.056134751231156355):0.04059092201022025,(Urspelerpes_brucei:0.1229903578780156,((((Haideotriton_wallacei:0.043894008067303286,(Eurycea_longicauda:0.034659474865221064,Eurycea_lucifuga:0.035437012376937464):0.02420354536975269):0.0042370300589705725,(Eurycea_cirrigera:0.030676349572138013,(Eurycea_wilderae:0.0379731912341853,(Eurycea_bislineata:0.019401928041227198,(Eurycea_aquatica:0.007118808458700517,Eurycea_junaluska:3.51422724235035E-6):0.03393894008652718):0.007986939648165386):0.002520831976038186):0.04096528212162):0.011965613459049862,((((Eurycea_rathbuni:0.0070978596524263375,Eurycea_waterlooensis:0.008779150596972353):0.017143737967668934,(Eurycea_troglodytes:0.017386859256624275,(Eurycea_sosorum:0.0030690014197104665,((Eurycea_neotenes:0.0027586210862599264,((Eurycea_latitans:7.932531202403054E-4,Eurycea_tridentifera:3.9595417938943447E-4):3.51422724235035E-6,Eurycea_pterophila:0.0011970188004837594):4.168503996836662E-4):0.001138916788771944,Eurycea_nana:0.00763753819923012):2.1882632305188525E-4):0.017389808983564427):0.005488035526621201):0.02386110951779808,(Eurycea_naufragia:0.007676799829192948,(Eurycea_chisholmensis:0.0034694233476346265,Eurycea_tonkawae:0.003724557942713148):0.003246106324734327):0.0605086622324159):0.014117847609077356,Eurycea_quadridigitata:0.06855249750237098):0.01480388744932893):0.012277200780855733,(Eurycea_multiplicata:0.0627807885821139,(Eurycea_tynerensis:0.04112986073413401,Eurycea_spelaea:0.04104320937510008):0.011479916994605169):0.008097836738714559):0.05302683833429867):0.030304726759609817):0.05618291771165584):0.014134658180198982):0.020463654151175067,((((Plethodon_larselli:0.029789775821322508,(Plethodon_idahoensis:0.020157503592494356,Plethodon_vandykei:0.020091732423929445):0.04852817383216303):0.014309139641153466,(((Plethodon_dunni:0.07267149529824457,Plethodon_vehiculum:0.0350765319122873):0.03958827093842944,(Plethodon_asupak:0.05613992642319034,(Plethodon_elongatus:0.025416088639661678,Plethodon_stormi:0.029651919105521448):0.00855811112687897):0.032114322558911):0.011412650421934886,Plethodon_neomexicanus:0.054196413412068885):0.002677921425016851):0.017177543563086154,(((Plethodon_websteri:0.039770238921202565,((Plethodon_wehrlei:0.005141910473577737,Plethodon_punctatus:0.023015463562243616):0.04598341420566125,(((Plethodon_ventralis:0.01438930444816133,Plethodon_dorsalis:0.019830477390753255):0.0031047285493086205,Plethodon_angusticlavius:0.03342667542370055):0.016746237545939346,Plethodon_welleri:0.04243555969611503):0.0161291631818505):0.007771030832420106):0.004112723822377494,(Plethodon_petraeus:0.015548316001169593,((((((Plethodon_cheoah:0.030362335917285082,Plethodon_aureolus:0.002311951667182125):0.006135984917652505,(Plethodon_teyahalee:3.51422724235035E-6,Plethodon_cylindraceus:0.008405076261295009):2.896894975495844E-4):0.003862362679590755,Plethodon_glutinosus:0.007389784532805823):0.003335565167397398,(Plethodon_chattahoochee:0.01567131433720817,(Plethodon_variolatus:0.0034735433225563743,Plethodon_chlorobryonis:3.51422724235035E-6):0.005553365253693751):0.0030386020279483477):0.011636692378197725,((Plethodon_meridianus:0.006320823784055166,Plethodon_amplus:0.004820888779779959):0.009325164729226879,(Plethodon_montanus:0.0051877897204165,Plethodon_metcalfi:0.0032380105977852978):0.007213467170481851):0.010220131147303763):0.009474108616495705,((Plethodon_kentucki:0.02710552271959496,(((Plethodon_shermani:0.015997502986065986,((Plethodon_kiamichi:0.027321024055588613,Plethodon_mississippi:0.010821089214263866):0.0065274856563188105,((Plethodon_kisatchie:0.007572282383590577,(Plethodon_grobmani:0.010641216083973008,(Plethodon_savannah:0.0016787760634747097,Plethodon_ocmulgee:0.0021984473074753027):0.0019427315228470723):0.001222731489823314):0.00366542792991989,(Plethodon_albagula:0.008581701282311819,Plethodon_sequoyah:0.00928836441427106):0.002317478130433112):0.0037355448457033953):0.004098849371839801):0.010187929544639396,Plethodon_yonahlossee:0.07443918317382278):0.004603244030589513,Plethodon_jordani:0.024185252912473528):0.00404251148957688):0.004774723250244468,(Plethodon_caddoensis:0.02868530712369922,(Plethodon_fourchensis:0.03206320600657406,Plethodon_ouachitae:0.024565649301716022):0.00513877519589439):0.006494472411022512):0.0036810098381106766):0.008785049006620041):0.043433860342716624):0.012859838881651358,(Plethodon_serratus:0.04147935980236959,((Plethodon_virginia:0.017122509622992246,(Plethodon_cinereus:0.019799695244376372,Plethodon_shenandoah:0.02581515357798322):0.009728455650413931):0.0044067313192308515,((((Plethodon_richmondi:0.01622240829245836,Plethodon_electromorphus:0.009554707168265517):0.008389447843233318,Plethodon_hubrichti:0.01680545539996551):0.006184726550555476,Plethodon_nettingi:0.016512386071123895):0.0039013587119026455,Plethodon_hoffmani:0.009834022317883443):0.00806252683122783):0.020596907922673255):0.02288255511694773):0.0783269033900747):0.02765255812557099,(((Hydromantes_genei:0.05666070127514604,((Hydromantes_strinatii:0.01789561545624845,(Hydromantes_ambrosii:0.014201466741382403,Hydromantes_italicus:0.018879621153556465):0.008552737907271497):0.02462362087043404,(Hydromantes_imperialis:0.025243920306246385,(Hydromantes_supramontis:0.024573076961963782,Hydromantes_flavus:0.03754060155060141):0.004866214793651474):0.034671067598187486):0.006131966628863629):0.04533858909597034,(Hydromantes_shastae:0.012708143560786327,(Hydromantes_platycephalus:0.013428110644708405,Hydromantes_brunus:0.020320738405759334):0.013731335929266914):0.08481147037201162):0.07259427982538595,(((Karsenia_koreana:0.1277538629487996,((Desmognathus_wrighti:0.09635423827422904,(((((Desmognathus_carolinensis:0.03610249495086411,Desmognathus_monticola:0.031335951763051616):0.008757817161988209,(Desmognathus_brimleyorum:0.041150168285617766,((((Desmognathus_santeetlah:0.03490141019376119,Desmognathus_conanti:0.02375772335998637):0.019906384189405945,Desmognathus_apalachicolae:0.03974125261670264):0.00423500023209535,(Desmognathus_ocoee:0.053849220472195966,(Desmognathus_orestes:0.02526368332610574,Desmognathus_ochrophaeus:0.02483523744813494):0.02185761288302131):0.004180786263952547):0.003952309847804795,(Desmognathus_planiceps:0.04423920603630307,(Desmognathus_welteri:0.032369678267644204,(Desmognathus_auriculatus:0.02671886490893002,Desmognathus_fuscus:0.014980338292613637):0.0067358579439033475):3.51422724235035E-6):0.014652185232043676):0.0013891099476047103):0.003382748578895076):0.00451839250136603,(Desmognathus_imitator:0.03517842537967915,Desmognathus_aeneus:0.06485945128957):0.003801552236335149):0.012539748107307028,(Desmognathus_marmoratus:0.03113172913763076,Desmognathus_quadramaculatus:0.039690315830844855):0.005875406129701906):0.010946254999405953,Desmognathus_folkertsi:0.050217296109593745):0.02707505016808712):0.035204785446732674,Phaeognathus_hubrichti:0.08030423677156762):0.014479681177238711):3.51422724235035E-6,((Aneides_hardii:0.05729944024261182,(Aneides_lugubris:0.08568602273644758,(Aneides_flavipunctatus:0.06531794444913123,(Aneides_ferreus:0.047765163411121976,Aneides_vagrans:0.020587598881605124):0.038994959349898994):0.02076225185713483):0.03476016381340312):0.031058990205553267,Aneides_aeneus:0.10797817669463243):0.041780976968360424):0.009443138679463671,Ensatina_eschscholtzii:0.1319547496709821):0.004808484399761322):0.016727409082953806):0.043815168855395194):0.14861858730342395,(Amphiuma_tridactylum:0.023461357557260594,(Amphiuma_means:0.03601778513350728,Amphiuma_pholeter:0.02163561343271888):0.022713854457677436):0.27591139181084834):0.036954557253876474):0.04381784018116853,(Proteus_anguinus:0.33305724800879877,(Necturus_lewisi:0.050873994354114396,((Necturus_beyeri:0.012413762587158029,(Necturus_maculosus:0.005733239879848899,Necturus_alabamensis:0.0036567012275411726):0.010749823212934266):0.01666250802559586,Necturus_punctatus:0.04774789596811974):0.01682217279877847):0.15923376098504552):0.09187817114929174):0.028010655385510702):0.03720978144676138):0.03959700966766624):0.287239501178131):0.07294836253817946):0.5723662330983965,Homo_sapiens:0.5723662330983965);
[!  TreeBASE tree URI: http://purl.org/phylo/treebase/phylows/tree/TB2:Tr48025]


END;
";

	$obj = parse_nexus($str);
	print_r($obj);
}


?>