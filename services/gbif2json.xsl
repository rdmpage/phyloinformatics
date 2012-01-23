<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:gbif="http://portal.gbif.org/ws/response/gbif">

<xsl:output method='text' version='1.0' encoding='utf-8' indent='no'/>

<xsl:template match="/">
	<xsl:text>{"cells":[&#x0D;</xsl:text>
	<xsl:apply-templates select="//gbif:densityRecord"/>
	<xsl:text>&#x0D;]}</xsl:text>
</xsl:template>

<xsl:template match="//gbif:densityRecord">
	<xsl:if test="position() != 1">
		<xsl:text>,&#x0D;</xsl:text>
	</xsl:if>
	<xsl:text>{&#x0D;</xsl:text>
	<xsl:text>"cellid":</xsl:text><xsl:value-of select="@cellid" /><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>"minLatitude":</xsl:text><xsl:value-of select="gbif:minLatitude" /><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>"maxLatitude":</xsl:text><xsl:value-of select="gbif:maxLatitude" /><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>"minLongitude":</xsl:text><xsl:value-of select="gbif:minLongitude" /><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>"maxLongitude":</xsl:text><xsl:value-of select="gbif:maxLongitude" /><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>"count":</xsl:text><xsl:value-of select="gbif:count" /><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>"colour":"</xsl:text>
	<xsl:choose>
		<xsl:when test="gbif:count &lt; 10"><xsl:text>#ffff00</xsl:text></xsl:when>
		<xsl:when test="gbif:count &lt; 100"><xsl:text>#ffcc00</xsl:text></xsl:when>
		<xsl:when test="gbif:count &lt; 1000"><xsl:text>#ff6600</xsl:text></xsl:when>
		<xsl:when test="gbif:count &lt; 10000"><xsl:text>#ff3300</xsl:text></xsl:when>
		<xsl:otherwise><xsl:text>#cc0000</xsl:text></xsl:otherwise>
	</xsl:choose>
	<xsl:text>"&#x0D;</xsl:text>
	<xsl:text>}</xsl:text>
</xsl:template>

</xsl:stylesheet>