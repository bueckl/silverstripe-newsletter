<!DOCTYPE html>
<html lang="$ContentLocale" class="no-js pace">
<head>
	<meta charset="utf-8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
		<% base_tag %>
	<title><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> &raquo; $SiteConfig.Title</title>
		$MetaTags(false)
	<!-- Place favicon.ico and apple-touch-icon.png in the root of your domain and delete these references -->
	<link rel="apple-touch-icon" href="{$ThemeDir}/icons/apple-touch-icon.png">
	<link rel="shortcut icon" href="{$ThemeDir}/icons/favicon.ico">
	<link rel="apple-touch-icon-precomposed" sizes="57x57" href="{$ThemeDir}/icons/apple-touch-icon-57x57.png" />
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="{$ThemeDir}/icons/apple-touch-icon-114x114.png" />
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="{$ThemeDir}/icons/apple-touch-icon-72x72.png" />
	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="{$ThemeDir}/icons/apple-touch-icon-144x144.png" />
	<link rel="apple-touch-icon-precomposed" sizes="60x60" href="{$ThemeDir}/icons/apple-touch-icon-60x60.png" />
	<link rel="apple-touch-icon-precomposed" sizes="120x120" href="{$ThemeDir}/icons/apple-touch-icon-120x120.png" />
	<link rel="apple-touch-icon-precomposed" sizes="76x76" href="{$ThemeDir}/icons/apple-touch-icon-76x76.png" />
	<link rel="apple-touch-icon-precomposed" sizes="152x152" href="{$ThemeDir}/icons/apple-touch-icon-152x152.png" />
	<link rel="icon" type="image/png" href="{$ThemeDir}/icons/favicon-196x196.png" sizes="196x196" />
	<link rel="icon" type="image/png" href="{$ThemeDir}/icons/favicon-96x96.png" sizes="96x96" />
	<link rel="icon" type="image/png" href="{$ThemeDir}/icons/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="{$ThemeDir}/icons/favicon-16x16.png" sizes="16x16" />
	<link rel="icon" type="image/png" href="{$ThemeDir}/icons/favicon-128.png" sizes="128x128" />
	<meta name="msapplication-TileColor" content="#FFFFFF" />
	<meta name="msapplication-TileImage" content="mstile-144x144.png" />
	<meta name="msapplication-square70x70logo" content="mstile-70x70.png" />
	<meta name="msapplication-square150x150logo" content="mstile-150x150.png" />
	<meta name="msapplication-wide310x150logo" content="mstile-310x150.png" />
	<meta name="msapplication-square310x310logo" content="mstile-310x310.png" />
<script data-pace-options='{ "ajax": false }' src='{$ThemeDir}/javascript/pace.min.js'></script>
<script>
window.addEventListener("load", function(){
window.cookieconsent.initialise({
  "palette": {
    "popup": {
      "background": "#1d1f20"
    },
    "button": {
      "background": "#f10519"
    }
  },
  "showLink": false,
  "position": "top",
  "static": true,
  "container": document.getElementById("PageContent"),
  "content": {
    "message": '<%t Cookie.Text "Wir setzen Cookies (eigene und von Drittanbietern) ein, um Ihnen die Nutzung unserer Webseiten zu erleichtern und Ihnen Werbemitteilungen im Einklang mit Ihren Browser-Einstellungen anzuzeigen. Mit der weiteren Nutzung unserer Webseiten sind Sie mit dem Einsatz der Cookies einverstanden. Weitere Informationen zu Cookies und Hinweise, wie Sie die Cookie-Einstellungen Ihres Browsers ändern können, entnehmen Sie bitte unserer <a style=\"vertical-align: middle\" href=\"{CookieLink}\">Cookie-Richtlinie.</a>" CookieLink=$CookieLink %>',
    "dismiss": "x"
  }
})});
</script>



</head>
<body class="$ClassName" data-locale="$Locale">

    <% include Header %>
    <div id="PageContent">
        $Form
    </div>

    <div id="NDAModal" class="modal modal-full fade"></div><!-- /.modal -->
    <div id="ComplianceTermsModal" class="modal modal-full fade"></div><!-- /.modal -->

</body>

</html>
