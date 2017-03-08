<?php

class Template {

	// Assignement storage. This values will be parsed in to the template
	// eg.: {{ text id='foo'}} will be changed with 'foo' => 'Bar'
	public $vars = array();

	// Stores theme name (folder)
	private $theme;

	private $tpl_dir = 'templates/';


	# ==============================================================================
	# SETTING UP TEMPLATE FOLDER
	# ==============================================================================

	public function set_theme($theme_name) {
		$this->theme = $theme_name;
	}


	# ==============================================================================
	# PARSING TEMPLATE
	# ==============================================================================

	public function get_content($file) {
		$file_path = Config::THEMES_DIR . $this->theme . '/' . $this->tpl_dir . $file . '.html';

		if (empty($this->theme)) {
			Core::error('Nie zdefiniowano szablonu', __FILE__, __LINE__, debug_backtrace());
		}
		elseif (!file_exists($file_path)) {
			Core::error('Plik szablonu nie istnieje: ' . $file_path, __FILE__, __LINE__, debug_backtrace());
		}
		else return file_get_contents($file_path);
	}


	# ==============================================================================
	# ASSIGN VARS TO PARSE
	# ==============================================================================

	public function assign($array) {
		if (!is_array($array)) {
			Core::error('Nie przypisano żadnej tablicy do parsowania', __FILE__, __LINE__, debug_backtrace());
		}
		foreach($array as $key => $val) {
			$this->vars[$key] = $val;
		}
	}


	# ==============================================================================
	# PREPARE ARRAY OF FIELDS FOUND IN CONTENT
	# ==============================================================================

	public function get_fields($content) {
		$num_matches = preg_match_all('/{{(.*?)}}/', $content, $matches);
		$fields = array();

		foreach($matches[1] as $key => $val) {
			$val	= trim($val); // Remove spaces from beggining and the end
			$chunks	= array_filter(explode(' ', $val));
			$field	= array();

			if (in_array($chunks[0], Config::$_FIELD_CATEGORIES['content']) or in_array($chunks[0], Config::$_FIELD_CATEGORIES['other'])) {
				$field['category'] = $chunks[0];

				// Get params of the field

				$num_params = preg_match_all("/([a-z]+)='([^']*)'/", $val, $params);
				if (is_array($params)) {
					foreach ($params[1] as $p => $param) {
						$field[$params[1][$p]] = $params[2][$p];
					}
				}

				// Add field to array if has ID and there is no existing entry with this ID

				if (!empty($field['id']) && !isset($fields[$field['id']])) {
					$fields[$field['id']] = $field;
					unset($fields[$field['id']]['id']); // Remove additional ID from params array
				}
			}
		}

		return $fields;
	}


	# ==============================================================================
	# PARSING TEMPLATE
	#
	# $content - string containing html code with template codes: {{ something }}
	# ==============================================================================

	public function parse($content, $fields, $translations = array()) {

		// Prepare all fields
		// @TODO: needed to be replaced
		// http://stackoverflow.com/questions/5017582/php-looping-template-engine-from-scratch

		$patterns		= array();
		$replacements	= array();
		$n = 0;

		foreach($fields as $id => $field) {

			// Match anything like {{ category something='something' id='id' somethin='something' }}
			// (*UTF8)	- selves problem with non latin characters in values
			// \p{L}	- searches any character from unicode.
			// /u		- allow searching for all unicode characters

			$patterns[$n] = '/{{ ' . $field['category'] . '[\p{L}0-9\'=\-_\:\.\(\)\s]+id=\'' . $id . '\'[\p{L}0-9\'=\-_\:\.\(\)\s]+}}/u';

			switch($field['category']) {

				case 'lang':
					if (isset($translations[$id])) $replacements[$n] = $translations[$id];
					else $replacements[$n] = $id;
					break;

				default:
					if (isset($this->vars[$id])) $replacements[$n] = $this->vars[$id];
					else $replacements[$n] = strtoupper($field['category'] . ':' . $id);
					break;
			}
			$n++;
		}

		if (is_array($this->vars)) {
			foreach($this->vars as $key => $var) {
				if (!empty($key)) {
					$patterns[] = '/{{ ' . $key . ' }}/';
					$replacements[] = $var;
				}
			}
		}

		$parsed = preg_replace($patterns, $replacements, $content);
		$preg_status = preg_last_error();

		// If regular expression was performed without errors
		if (!$preg_status) return $parsed;

		// If error eccured show error status
		else {
			if (is_numeric($preg_status)) {
				$preg_status_list = array(
					1 => 'PREG_INTERNAL_ERROR',
					2 => 'PREG_BACKTRACK_LIMIT_ERROR',
					3 => 'PREG_RECURSION_LIMIT_ERROR',
					4 => 'PREG_BAD_UTF8_ERROR',
					5 => 'PREG_BAD_UTF8_OFFSET_ERROR',
				);
				if (isset($preg_status_list[$preg_status])) $preg_status_text = $preg_status_list[$preg_status] . ' [' . $preg_status . ']';
				else $preg_status_text = 'Unknown error' . ' [' . $preg_status . ']';
			}
			else $preg_status_text = $preg_status;

			Core::error('Nie udało się wyświetlić szablonu strony ponieważ w funkcji parsującej wystąpił błąd o następującym kodzie:<br>' . $preg_status_text, __FILE__, __LINE__, debug_backtrace());
		}
	}
}