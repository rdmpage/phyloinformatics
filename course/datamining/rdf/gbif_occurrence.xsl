<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' 
xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
xmlns:gbif="http://portal.gbif.org/ws/response/gbif"
xmlns:tcom="http://rs.tdwg.org/ontology/voc/Common#"
xmlns:to="http://rs.tdwg.org/ontology/voc/TaxonOccurrence#" xmlns:tc="http://rs.tdwg.org/ontology/voc/TaxonConcept#" xmlns:tn="http://rs.tdwg.org/ontology/voc/TaxonName#" >

<xsl:output method='xml' version='1.0' encoding='utf-8' indent='yes'/>

<xsl:template match="/">
	<xsl:apply-templates select="//gbif:occurrenceRecords" />
</xsl:template>

<xsl:template match="//gbif:occurrenceRecords">
<rdf:RDF>
<xsl:copy-of select="@*|node()" />
</rdf:RDF>
</xsl:template>

<!--
<xsl:template match="//to:TaxonOccurrence">
 
</xsl:template>
-->

</xsl:stylesheet>