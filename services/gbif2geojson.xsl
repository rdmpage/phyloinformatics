<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:gbif="http://portal.gbif.org/ws/response/gbif">

<xsl:output method='text' version='1.0' encoding='utf-8' indent='no'/>

<xsl:template match="/">
	<xsl:text>{"type":"MultiPolygon",&#x0D;</xsl:text>
	<xsl:text>"coordinates":[&#x0D;</xsl:text>
	<xsl:apply-templates select="//gbif:densityRecord"/>
	<xsl:text>&#x0D;]}</xsl:text>
</xsl:template>

<xsl:template match="//gbif:densityRecord">
	<xsl:if test="position() != 1">
		<xsl:text>,&#x0D;</xsl:text>
	</xsl:if>
	<xsl:text>[&#x0D;</xsl:text>
	<xsl:text>&#x09;[&#x0D;</xsl:text>

	<xsl:text>&#x09;[</xsl:text><xsl:value-of select="gbif:maxLongitude" /><xsl:text>,</xsl:text><xsl:value-of select="gbif:minLatitude" /><xsl:text>],&#x0D;</xsl:text>

	<xsl:text>&#x09;[</xsl:text><xsl:value-of select="gbif:minLongitude" /><xsl:text>,</xsl:text><xsl:value-of select="gbif:minLatitude" /><xsl:text>],&#x0D;</xsl:text>

	<xsl:text>&#x09;[</xsl:text><xsl:value-of select="gbif:minLongitude" /><xsl:text>,</xsl:text><xsl:value-of select="gbif:maxLatitude" /><xsl:text>],&#x0D;</xsl:text>

	<xsl:text>&#x09;[</xsl:text><xsl:value-of select="gbif:maxLongitude" /><xsl:text>,</xsl:text><xsl:value-of select="gbif:maxLatitude" /><xsl:text>],&#x0D;</xsl:text>

	<xsl:text>&#x09;[</xsl:text><xsl:value-of select="gbif:maxLongitude" /><xsl:text>,</xsl:text><xsl:value-of select="gbif:minLatitude" /><xsl:text>]&#x0D;</xsl:text>


	<xsl:text>&#x09;]&#x0D;</xsl:text>
<xsl:text>]</xsl:text>
</xsl:template>

</xsl:stylesheet>