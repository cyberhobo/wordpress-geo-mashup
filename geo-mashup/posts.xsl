<?xml version="1.0" encoding="utf-8" ?> 
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" />
	<xsl:template match="/rss/channel">
		<div class="locationinfo">
			<xsl:apply-templates select="item" />
		</div>
	</xsl:template>
	<xsl:template match="item">
		<h2 class="storytitle">
			<a href="{link}" onclick="GeoMashup.setBackCookies()"><xsl:value-of select="title" /></a>
		</h2>
		<p class="meta">
				<xsl:value-of select="category" />, 
				<xsl:value-of select="substring(pubDate,1,16)" />
		</p>
		<hr />
	</xsl:template>
</xsl:stylesheet>
