<?php

# ==================================================================================
#
#	VIZU CMS
#	Module: 404 error page
#
# ==================================================================================

header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

$tpl = new libs\Template();
$tpl->setTheme(Config::$THEME_NAME);

if ($tpl->getTemplatePath('404')) {
	$template_content = $tpl->getContent('404');
	$template_fields  = $tpl->getFields($template_content);

	$tpl->assign([
		'site_path'   => $router->site_path . '/',
		'theme_path'  => 'themes/' . Config::$THEME_NAME . '/',
		'app_path'    => Config::$APP_DIR
	]);

	echo $tpl->parse($template_content, $template_fields, $lang->translations);
}

else {
	echo '<h1>404 Not Found</h1>';
	echo '<p>The page that you have requested could not be found.</p>';
}