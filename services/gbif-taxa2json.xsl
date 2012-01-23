<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:gbif="http://portal.gbif.org/ws/response/gbif" xmlns:tc="http://rs.tdwg.org/ontology/voc/TaxonConcept#" xmlns:tn="http://rs.tdwg.org/ontology/voc/TaxonName#">

<xsl:output method='text' version='1.0' encoding='utf-8' indent='no'/>

<xsl:template match="/">
	<xsl:text>{"taxonConcepts":[&#x0D;</xsl:text>
	<xsl:apply-templates select="//tc:TaxonConcept"/>
	<xsl:text>&#x0D;]}</xsl:text>
</xsl:template>

<xsl:template match="//tc:TaxonConcept">
	<xsl:if test="position() != 1">
		<xsl:text>,&#x0D;</xsl:text>
	</xsl:if>
	<xsl:text>{&#x0D;</xsl:text>
	
	<xsl:text>"gbifKey":</xsl:text><xsl:value-of select="@gbifKey" /><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>"nameComplete":"</xsl:text><xsl:value-of select="tc:hasName/tn:TaxonName/tn:nameComplete" /><xsl:text>"&#x0D;</xsl:text>

	<xsl:text>}</xsl:text>
</xsl:template>


</xsl:stylesheet>