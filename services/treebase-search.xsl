<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rss="http://purl.org/rss/1.0/" xmlns:tb="http://purl.org/phylo/treebase/2.0/terms#" xmlns:dc="http://purl.org/dc/elements/1.1/"
>

<!-- 
  Note use of normalize-space() to strip white space from XML
  see http://stackoverflow.com/questions/1468984/xslt-remove-whitespace-from-template
-->

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
	<xsl:text>&#x09;"kind":"</xsl:text><xsl:value-of select="normalize-space(tb:kind.tree)"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"type":"</xsl:text><xsl:value-of select="normalize-space(tb:type.tree)"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"quality":"</xsl:text><xsl:value-of select="normalize-space(tb:quality.tree)"/><xsl:text>",&#x0D;</xsl:text>
	<xsl:text>&#x09;"ntax":</xsl:text><xsl:value-of select="normalize-space(tb:ntax.tree)"/><xsl:text>&#x0D;</xsl:text>
	<xsl:text>}</xsl:text>
</xsl:template>
</xsl:stylesheet>