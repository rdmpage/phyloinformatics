<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rss="http://purl.org/rss/1.0/" xmlns:tb="http://treebase.org/terms#" xmlns:dc="http://purl.org/dc/elements/1.1/"
>

<xsl:output method='text' version='1.0' encoding='utf-8' indent='no'/>

<xsl:template match="/">
	<xsl:text>{"trees":[&#x0D;</xsl:text>
	<xsl:apply-templates select="//rss:item"/>
	<xsl:text>&#x0D;]}</xsl:text>
</xsl:template>

<xsl:template match="//rss:item">
	<xsl:if test="position() != 1">
		<xsl:text>,&#x0D;</xsl:text>
	</xsl:if>
	<xsl:text>{&#x0D;</xsl:text>
	<xsl:text>&#x09;"link":"</xsl:text><xsl:value-of select="rss:link"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"identifier":"</xsl:text><xsl:value-of select="substring-after(rss:link,'http://purl.org/phylo/treebase/phylows/tree/')"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"kind":"</xsl:text><xsl:value-of select="tb:kind.tree"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"type":"</xsl:text><xsl:value-of select="tb:type.tree"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"type":"</xsl:text><xsl:value-of select="tb:quality.tree"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"ntax":</xsl:text><xsl:value-of select="tb:ntax.tree"/><xsl:text>,&#x0D;</xsl:text>
	<xsl:text>}</xsl:text>
</xsl:template>
</xsl:stylesheet>