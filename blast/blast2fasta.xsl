<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>

<xsl:output method='text' version='1.0' encoding='utf-8' indent='no'/>

<xsl:param name="number" />

<xsl:template match="/">

<xsl:apply-templates select="//Hit"/>

</xsl:template>

<xsl:template match="//Hit">
	<xsl:if test="position() &lt; $number">
		<xsl:text>></xsl:text>
		<xsl:value-of select="Hit_accession"/>
		<xsl:text>&#x0D;</xsl:text>
		<xsl:value-of select="Hit_hsps/Hsp/Hsp_hseq"/>
		<xsl:text>&#x0D;</xsl:text>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>