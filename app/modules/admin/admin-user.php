<?php

# ==================================================================================
#
#	VIZU CMS
#	Module: Admin / User
#
# ==================================================================================

if (IN_ADMIN !== true) {
	die('This file can be loaded only in admin module');
}


switch($router->request[count($router->request) - 1]) {

	/**
	 * Password change operation
	 */

	case 'change_password';

		// Form validation

		$error_msg = null;

		if (empty($_POST['password_actual']))                          $error_msg = 'Nie podano aktualnego hasła';
		elseif (empty($_POST['password_new1']))                        $error_msg = 'Nie podano nowego hasła';
		elseif ($_POST['password_new1'] !== $_POST['password_new2'])   $error_msg = 'Nowe hasło nie zgadza się z jego powtórzeniem';
		elseif ($_POST['password_actual'] === $_POST['password_new1']) $error_msg = 'Nowe hasło musi różnić się od starego aby zostało zmienione';
		elseif (strlen($_POST['password_new1']) < 6)                   $error_msg = 'Nowe hasło powinno mieć conajmniej 5 znaków';

		if ($error_msg) {
			$ajax->set('message', $error_msg);
			return;
		}


		// Check if entered actual password is correct

		$result = $db->query("SELECT `password` FROM `users` WHERE `id` = '" . $user->getId() . "' LIMIT 1");
		$user_data = $db->fetch($result);

		if ($user_data[0]['password'] && $user_data[0]['password'] !== $user->passwordEncode($_POST['password_actual'])) {
			$ajax->set('message', 'Podane aktualne hasło nie jest poprawne');
			return;
		}


		// Save new password

		$new_password = $user->passwordEncode($_POST['password_new1']);
		$result = $db->query("UPDATE `users` SET `password` = '" . $new_password . "' WHERE `id` = '" . $user->getId() . "' LIMIT 1");

		if ($result) {
			$ajax->set('message', 'Pomyślnie zmieniono hasło');
			$_SESSION['password'] = $new_password;
		}
		else $ajax->set('message', 'Nie udało się zmienić hasła');

		break;


	/**
	 * Add user
	 */

	case 'user_add':
		$mailer = new libs\Mailer();

		// Validate entered email address
		if (empty($_POST['email']) || !$user->verifyUsername($_POST['email'])) {
			$ajax->set('message', 'Nie podano poprawnego adresu e-mail');
			break;
		}

		// Check if email address already exists
		$result = $db->query('SELECT * FROM `users` WHERE `email` = "' . $_POST['email'] . '"');
		$user_found = $db->fetch($result);
		if ($user_found) {
			$ajax->set('message', 'Podany adres e-mail istnieje już w bazie danych');
			break;
		}

		// Set sender email address as theme contact form main recipient
		if ($theme_config['contact']['default_recipient']) {
			$user_id = $theme_config['contact']['default_recipient'];

			// Get email addres of contact user that was set in configuration
			$result  = $db->query('SELECT `email` FROM `users` WHERE `id` = "' . $user_id . '"');
			$fetched = $db->fetch($result);

			if (!$fetched) {
				$ajax->set('message', 'Skonfigurowany domyślny odbiorca/nadawca [' . $user_id . '] nie istnieje w bazie danych. Nie udało się utworzyć konta administratora.');
				break;
			}
			$contact_user_email = $fetched[0]['email'];
			$mailer->setFrom($contact_user_email);
		}

		$generated_password = $user->generatePassword();

		try {
			$notify_result = $mailer
				->setTopic('Rejestracja konta')
				->addRecipient($_POST['email'])
				->addContent('Message', 'Twoje konto administratora zostało utworzone. Dane logowania znajdziesz powyżej. Wskazane jest aby po zalogowaniu się zmienić swoje hasło.')
				->addContent('Adres strony WWW', $router->site_path)
				->addContent('Login', $_POST['email'])
				->addContent('Hasło', $generated_password)
				->send();
		}
		catch (\Exception $e) {
			$ajax->set('message', 'Nie udało się wysłać powiadomienia o utworzeniu konta administratora. Treść błędu: "' . $e->getMessage() . '". Konto administratora nie zostało założone.');
			break;
		}

		// Add user to database
		$query = $db->query('INSERT INTO `users` VALUES ("", "' . $_POST['email'] . '", "' . $user->passwordEncode($generated_password) . '")');

		$ajax->set('message', 'Konto użytkownika o adresie e-mail ' . $_POST['email'] . ' zostało założone.');
		break;


	/**
	 * Display page
	 */

	default:
		$query = $db->query('SELECT * FROM `users`');
		$result = $db->fetch($query);
		$users_list = '';

		foreach($result as $user_data) {
			$users_list .= '<li>' . $user_data['email'] . '</li>';
		}

		$tpl->assign([
			'user_email' => $user->getEmail(),
			'users_list' => $users_list,
		]);

		$template_content = $tpl->getContent('user');
		$template_fields  = $tpl->getFields($template_content);

		$ajax->set('html', $tpl->parse($template_content, $template_fields));
}