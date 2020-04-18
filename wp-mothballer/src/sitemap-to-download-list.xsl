<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">

	<xsl:output method="xml" indent="yes" />

	<xsl:template match="/">

		<project name="wp-mothballer-download-urls" basedir="." default="all">

			<property name="dir.cache" value="cache" />

			<target name="all" depends="init,downloads" />

			<target name="init">
				<xsl:element name="mkdir">
					<xsl:attribute name="dir">
						<xsl:call-template name="escAntParam">
							<xsl:with-param name="paramName">dir.cache</xsl:with-param>
						</xsl:call-template>
					</xsl:attribute>
				</xsl:element>
			</target>

			<target name="downloads">
				<xsl:for-each select="sitemap:urlset/sitemap:url">
					<antcall target="download">
						<xsl:element name="param">
							<xsl:attribute name="name">url</xsl:attribute>
							<xsl:attribute name="value">
								<xsl:value-of select="sitemap:loc" />
							</xsl:attribute>
						</xsl:element>
					</antcall>
				</xsl:for-each>
			</target>

			<target name="download">
				<xsl:element name="echo">
					<xsl:attribute name="message">
						<xsl:call-template name="escAntParam">
							<xsl:with-param name="paramName">url</xsl:with-param>
						</xsl:call-template>
					</xsl:attribute>
					<xsl:attribute name="file">tmpfile</xsl:attribute>
				</xsl:element>
				<loadfile property="dest" srcFile="tmpfile">
					<filterchain>
				        <tokenfilter>
				        	<replaceregex pattern="http://[^/]+/$" replace="index" flags="g"/>
				        	<replaceregex pattern="http://[^/]+/" replace="" flags="g"/>
				            <replaceregex pattern="(.*)/" replace="\1" flags="g"/>
				            <replaceregex pattern="/" replace="_" flags="g"/>
				        </tokenfilter>
				    </filterchain>
				</loadfile>
				<xsl:element name="get">
					<xsl:attribute name="src">
						<xsl:call-template name="escAntParam">
							<xsl:with-param name="paramName">url</xsl:with-param>
						</xsl:call-template>
					</xsl:attribute>
					<xsl:attribute name="dest">
						<xsl:call-template name="escAntParam">
							<xsl:with-param name="paramName">dir.cache</xsl:with-param>
						</xsl:call-template>
						<xsl:text>/</xsl:text>
						<xsl:call-template name="escAntParam">
							<xsl:with-param name="paramName">dest</xsl:with-param>
						</xsl:call-template>
						<xsl:text>.html</xsl:text>
					</xsl:attribute>
				</xsl:element>
				<delete file="tmpfile" />
			</target>

		</project>

	</xsl:template>

	<xsl:template name="escAntParam">
		<xsl:param name="paramName" />
		<xsl:text>&#36;&#123;</xsl:text>
		<xsl:value-of select="$paramName" />
		<xsl:text>&#125;</xsl:text>
	</xsl:template>

</xsl:stylesheet>