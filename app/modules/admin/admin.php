<?php

# ==================================================================================
#
#	VIZU CMS
#	Module: Admin
#
# ==================================================================================

define('IN_ADMIN', true); // Security constant. Needs to be checked in all included files

$tpl->set_theme('admin');
$tpl->assign(array(
	'app_path'			=> Config::APP_DIR,
	'site_path' 		=> $router->site_path,
	'theme_path'		=> 'themes/admin',
	'script_version'	=> VERSION,
	'phpversion'		=> phpversion(),
));

require_once(Config::APP_DIR . 'libs/user.php');
$user = new User($db);


# ==================================================================================
# PAGE LOADED WITH AJAX
#
# Check if request was done asynchronously.
# If true change the behavior of page to always return JSON data.
# ==================================================================================

if (Core::$ajax_loaded === true) {

	# ------------------------------------------------------------------------------
	# BYPASS DEFAULT PHP ERRORS BY OWN FUNCTION TO SEND THEM AS JSON

	function error_handler($errno, $errstr, $errfile, $errline) {
		echo json_encode(
			array('error' => array(
				'number'	=> $errno,
				'str'		=> $errstr,
				'file'		=> $errfile,
				'line'		=> $errline
			))
		);
		die();
	}

	$old_error_handler = set_error_handler("error_handler");


	# ------------------------------------------------------------------------------
	# START AJAX CLASS AND SET SOME VARIABLES

	require_once(Config::APP_DIR . 'libs/ajax.php');
	$ajax = new Ajax();

	$display = true; // Is there anything that needs to be shown?

	if (!isset($router->request[1])) $request = 'home';
	else $request = $router->request[1];


	# ------------------------------------------------------------------------------
	# HANDLE POST REQUESTS

	if (count($_POST) > 0) {
		switch($request) {

			// LOGIN

			case 'login':
				$auth = $user->login($_POST['email'], $_POST['pass']);
				if ($auth === true) $ajax->set('loggedin', true);
				else {
					$ajax->set('error', array(
						'str' => $auth,
						'file' => __FILE__,
						'line' => __LINE__
					));
					$display = false;
				}
				break;
		}
	}

	# ------------------------------------------------------------------------------
	# DISPLAY RESULTS

	if (($user->get_access() > 0) && ($display == true)) {
		$ajax->set('loggedin', true);

		switch($request) {

			// ADMIN HOME PAGE

			case 'home':
			case 'login':
				$template_content	= $tpl->get_content('home');
				$template_fields	= $tpl->get_fields($template_content);

				$ajax->set('html', $tpl->parse($template_content, $template_fields));
				break;


			// LOG OUT USER

			case 'logout':
				$user->logout();
				$ajax->set('loggedin', false);
				break;


			// CONTENT ADMINISTRATION

			case 'edit':
				require_once('admin-edit.php');
				break;


			// CHANGES HISTORY

			case 'history':
				require_once('admin-history.php');
				break;


			// USER FUNCTIONS

			case 'user':
				require_once('admin-user.php');
				break;


			// BACKUP OPERATIONS

			case 'backup':
				require_once('admin-backup.php');
				break;


			// UNKNOWN REQUEST

			default:
				$ajax->set('error', array(
					'str' => "Nieznana funkcja: " . $request,
					'file' => __FILE__,
					'line' => __LINE__
				));
		}
	}

	$ajax->send();
}


# ==================================================================================
# PAGE LOADED NORMALLY
#
# If page was not loaded asynchronously display admin template.
# ==================================================================================

else {
	if ($user->get_access() > 0) {

		$template_content	= $tpl->get_content('home');
		$template_fields	= $tpl->get_fields($template_content);

		$tpl->assign(array(
			'loggedin'	=> 'loggedin',
			'page'		=> $tpl->parse($template_content, $template_fields),
		));
	}

	else {
		$tpl->assign(array(
			'loggedin'	=> '',
			'page'		=> '',
		));
	}

	$template_content	= $tpl->get_content('index');
	$template_fields	= $tpl->get_fields($template_content);
	echo $tpl->parse($template_content, $template_fields);
}

?>