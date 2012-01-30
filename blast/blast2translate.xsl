<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>

<xsl:output method='text' version='1.0' encoding='utf-8' indent='yes'/>

<xsl:param name="number" />

<xsl:template name="replace-string">
    <xsl:param name="text"/>
    <xsl:param name="replace"/>
    <xsl:param name="with"/>
    <xsl:choose>
      <xsl:when test="contains($text,$replace)">
        <xsl:value-of select="substring-before($text,$replace)"/>
        <xsl:value-of select="$with"/>
        <xsl:call-template name="replace-string">
          <xsl:with-param name="text"
                          select="substring-after($text,$replace)"/>
          <xsl:with-param name="replace" select="$replace"/>
          <xsl:with-param name="with" select="$with"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$text"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

<xsl:template match="/">
	<xsl:text>Translate&#x0D;</xsl:text>
	<xsl:apply-templates select="//Hit"/>
	<xsl:text>&#x0D;</xsl:text>
	<xsl:text>;&#x0D;</xsl:text>
</xsl:template>

<xsl:template match="//Hit">
	<xsl:if test="position() &lt; $number">

	<xsl:if test="position() != 1">
		<xsl:text>,&#x0D;</xsl:text>
	</xsl:if>

	<xsl:value-of select="position()"/>
	<xsl:text> '</xsl:text>
	<xsl:value-of select="Hit_accession"/>
	<xsl:text> </xsl:text>

	
		<xsl:choose>
			<xsl:when test="contains(Hit_def, '&gt;gi')">
				<xsl:call-template name="replace-string">
					<xsl:with-param name="text" select="substring-before(Hit_def, '&gt;gi')"/>
 					<xsl:with-param name="replace" select='"&apos;"' />
           			<xsl:with-param name="with" select='"&apos;&apos;"'/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="replace-string">
					<xsl:with-param name="text" select="Hit_def"/>
 					<xsl:with-param name="replace" select='"&apos;"' />
           			<xsl:with-param name="with" select='"&apos;&apos;"'/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>

	<xsl:text>'</xsl:text>

	</xsl:if>

</xsl:template>

</xsl:stylesheet>