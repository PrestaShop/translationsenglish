<?php
/*
* 2007-2011 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 1.4 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

include _PS_MODULE_DIR_.'translationsenglish/translationsenglish.php';

class AdminReverseTranslations extends AdminTab
{
	private $html = '';

	/** @var int count success replace */
	public $count_sucess = 0;

	/** @var int count errors replace */
	public $count_errors = 0;

	/** @var array : List of languages */
	public $languages = array();

	/** @var array all files for front office */
	public $files_front = array();

	/** @var array all files for back office */
	public $files_admin = array();

	/** @var array all files for modules */
	public $files_modules = array();

	/** @var array all files for errors*/
	public $files_errors = array();

	/** @var array all files for pdf */
	public $files_pdf = array();

	public $new_translation = array();

	public $module;

	public static $ignore_dir = array('.', '..', '.svn', '.htaccess', 'index.php');

	/** @var string name of theme by defaul in Prestashop */
	public $name_theme;

	/**
	 * List of files which are in folder "admin"
	 * @var array
	 */
	public static $admin_files_as_index =	array(
		'header.inc.php',
		'footer.inc.php',
		'index.php',
		'login.php',
		'password.php',
		'functions.php'
	);

	/**
	 * List of types of translations
	 * @var array
	 */
	public $var_types =	array();

	public function __construct()
	{
		$this->module = new translationsenglish();
		$this->var_types = array(
			'front' => array(
				'var_name' => '_LANG',
				'language' => $this->l('Front Office files'),
				'file' => str_replace('/', DIRECTORY_SEPARATOR, _PS_THEME_DIR_.'lang'.DIRECTORY_SEPARATOR.'ISO_LANG_CODE.php')
			),
			'admin' => array(
				'var_name' => '_LANGADM',
				'language' => $this->l('Back Office Files'),
				'file' => str_replace('/', DIRECTORY_SEPARATOR, _PS_TRANSLATIONS_DIR_.'ISO_LANG_CODE'.DIRECTORY_SEPARATOR.'admin.php')
			),
			'modules' => array(
				'var_name' => '_MODULE',
				'language' => $this->l('Modules Files'),
				'file' => array()
			),
			'pdf' => array(
				'var_name' => '_LANGPDF',
				'language' => $this->l('PDF Files'),
				'file' => str_replace('/', DIRECTORY_SEPARATOR, _PS_TRANSLATIONS_DIR_.'ISO_LANG_CODE'.DIRECTORY_SEPARATOR.'pdf.php')
			),
			'errors' => array(
				'var_name' => '_ERRORS',
				'language' => $this->l('Errors Files'),
				'file' => str_replace('/', DIRECTORY_SEPARATOR, _PS_TRANSLATIONS_DIR_.'ISO_LANG_CODE'.DIRECTORY_SEPARATOR.'errors.php')
			)
		);

		if (_PS_VERSION_ < '1.5')
			$this->name_theme = 'prestashop';
		else
			$this->name_theme = 'default';

		// Get all languages which are active or not
		$this->languages = Language::getLanguages(false);
		parent::__construct();
	}

	public function display()
	{
		$this->html .= '<div id="translationseditor">';

		$this->html .= '
			<script type="text/javascript">
				var params = new Array();
				params["link"] 		= "ajax-tab.php";
				params["action"] 	= "ChangeTranslationKeys";
				params["configure"]	= "translationsenglish";
				params["module_name"]	= "translationsenglish";
				params["ajax"]	 	= "1";
				params["token"] 	= "'.Tools::getAdminTokenLite('AdminReverseTranslations').'";
				params["controller"] 	= "AdminReverseTranslations";
			</script>';

		$this->html .= '<style type="text/css" media="all">@import "'._MODULE_DIR_.'translationsenglish/translationsenglish.css" </style>';
		$this->html .= '<script type="text/javascript" src="'._MODULE_DIR_.'translationsenglish/translationsenglish.js"></script>';
		$this->html .= '<h2>'.$this->l('Prestashop Translation English').'</h2>';
		$this->html .= '<p>'.$this->l('Search all translation which are english and change key in all files').'</p>';

		$this->html .= '
			<form method="post" id="formTranslation">
				<fieldset class="tabWidth">
					<p>'.$this->l('Select type of file:').'</p>';

		foreach ($this->var_types as $key => $type)
			$this->html .= '<p>
								<input type="checkbox" name="'.$key.'" value="1" checked id="type_'.$key.'"/>
								<label for="type_'.$key.'">'.$type['language'].'</label>
							</p>';

		$this->html .= '
					<p class="margin-form"><div id="SumitChangeTranslationKey">'.$this->l('Update translation key here').'</div></p>

					<div id="details"><ul></ul></div>

				</fieldset>
			</form>
		';

		$this->html .= '</div>';

		echo $this->html;
	}

	public function postProcess()
	{
		if (Tools::getValue('action') == 'ChangeTranslationKeys' && Tools::getValue('ajax') == 1)
			$this->ajaxProcessChangeTranslationKeys();
	}

	public function getAllTypeChecked()
	{
		$types_checked = explode(',', trim(Tools::getValue('type'), ','));

		foreach ($this->var_types as $type => $array_type)
			if (!in_array($type, $types_checked) && isset($this->var_types[$type]))
				unset($this->var_types[$type]);
	}

	public function ajaxProcessChangeTranslationKeys()
	{
		// 1 - Remove type which is not checked
		$this->getAllTypeChecked();

		// 2 - Get all files
		if ($array_files = $this->getAllFiles())
			echo '<li class="success">'.$this->l('Get all files which can be translated:').' <span>OK</span></li>';
		else
			echo '<li class="errors">'.$this->l('Get all files which can be translated:').' <span>ERROR</span></li>';
		// 3 - Get all files which are translated in english
		if ($english_files = $this->getAllEnglishFiles())
			echo '<li class="success">'.$this->l('Get the list of english translations files:').' <span>OK</span></li>';
		else
			echo '<li class="errors">'.$this->l('Get the list of english translations files:').' <span>ERROR</span></li>';

		// 4 - Find all the new key with the key which must be searched by translation type
		if ($this->findAllNewTranslation($english_files))
			echo '<li class="success">'.$this->l('Find all translations which are new in english:').' <span>OK</span></li>';
		else
			echo '<li class="errors">'.$this->l('Find all translations which are new in english:').' <span>ERROR</span></li>';

		// 5- Find in all files and replace string
		foreach ($array_files as $type => $files)
			if (isset($this->new_translation[$type]) && !empty($this->new_translation[$type]))
				foreach ($files as $file)
					$this->findAndFillTranslations($file, $type);
			else
			{
				echo '<li class="errors">'.$this->l('No translations for this type:').' <span>'.$type.'</span></li>';
				$this->count_errors++;
			}

		// 6 - Reset all translation files in english
		$this->resetAllEnglishFiles($english_files);

		// 7 - Change all keys which are translated for all languages
		$this->updateKeysForAllLanguages();

		echo '<li>'.$this->l('Count error:').' <span>'.$this->count_errors.'</span></li>';
		echo '<li>'.$this->l('Count success:').' <span>'.$this->count_sucess.'</span></li>';
	}

	/**
	 * Update all key in new translation array for all languages
	 */
	public function updateKeysForAllLanguages()
	{
		// Clean of array new translation
		$this->cleanNewTranslation();

		foreach ($this->new_translation as $type => $translation_type)
			foreach ($translation_type as $translation)
				if ($type == 'modules')
				{
					foreach ($translation as $value)
					{
						foreach ($this->languages as $lang)
						{
							$file = $this->getNameLangFile($value['file_lang'], $lang['iso_code']);

							if (file_exists($file))
							{
								$content = file_get_contents($file);

								$replace = '\1\''.$value['new_md5_key'].'\'';
								$pattern = '`\''.$value['old_md5_key'].'\'`';

								if (preg_match($pattern, $content, $matches_check))
								{
									// Replace old sentence by the new sentence
									$file_content = preg_replace($pattern, $replace, $content, 1, $count);

									if (file_put_contents($file, $file_content) && $count == 1)
									{
										echo '<li class="success">'.sprintf(
											$this->l('This key has been update %1$s by %2$s in this file: %3$s'),
											'<span>"'.$value['old_md5_key'].'"</span>',
											'<span>"'.$value['new_md5_key'].'"</span>',
											$file
										).'</li>';
										$this->count_sucess++;
									}
									else
									{
										echo '<li class="errors">'.sprintf(
											$this->l('This key has not been update %1$s by %2$s in this file: %3$s (count: %4$d)'),
											'<span>"'.$value['old_md5_key'].'"</span>',
											'<span>"'.$value['new_md5_key'].'"</span>',
											'<span>'.$file.'</span>',
											$count
										).'</li>';
										$this->count_errors++;
									}
								}
							}
						}
					}
				}
				else
				{
					foreach ($this->languages as $lang)
					{
						$file = $this->getNameLangFile($translation['file_lang'], $lang['iso_code']);

						if (file_exists($file))
						{
							$content = file_get_contents($file);

							$replace = '\1\''.$translation['new_md5_key'].'\'';
							$pattern = '`\''.$translation['old_md5_key'].'\'`';

							if (preg_match($pattern, $content, $matches_check))
							{
								// Replace old sentence by the new sentence
								$file_content = preg_replace($pattern, $replace, $content, 1, $count);

								if (file_put_contents($file, $file_content) && $count == 1)
								{
									echo '<li class="success">'.sprintf(
										$this->l('This key has been update %1$s by %2$s in this file: %3$s'),
										'<span>"'.$translation['old_md5_key'].'"</span>',
										'<span>"'.$translation['new_md5_key'].'"</span>',
										'<span>'.$file.'</span>'
									).'</li>';
									$this->count_sucess++;
								}
								else
								{
									echo '<li class="errors">'.sprintf(
										$this->l('This key has not been update %1$s by %2$s in this file: %3$s (count: %4$d)'),
										'<span>"'.$translation['old_md5_key'].'"</span>',
										'<span>"'.$translation['new_md5_key'].'"</span>',
										'<span>'.$file.'</span>',
										$count
									).'</li>';
									$this->count_errors++;
								}
							}
						}
					}
				}
	}

	/**
	 * Clean the array of new translation
	 * Remove translation which are not translated
	 */
	public function cleanNewTranslation()
	{
		foreach ($this->new_translation as $type => $translation_type)
			foreach ($translation_type as $key => $translation)
				if ($type == 'modules')
				{
					foreach ($translation as $module_name => $value)
						if (!isset($translation['old_string']))
							unset($this->new_translation[$type][$module_name][$key]);
				}
				else
					if (!isset($translation['old_string']))
						unset($this->new_translation[$type][$key]);
	}

	/**
	 * Reset all english files
	 *
	 * @param $english_files : list of english files
	 * @return bool
	 */
	public function resetAllEnglishFiles($english_files)
	{
		foreach ($english_files as $type => $files)
			foreach ($files as $filename)
			{
				$file = $this->getNameLangFile($filename, 'en');

				$name_var = $this->var_types[$type]['var_name'];

				if (file_exists($file))
					if (!file_put_contents($file, "<?php\n\nglobal \$".$name_var.";\n\$".$name_var.' = array();'))
					{
						echo '<li class="errors">'.$this->l('Reset is not possible for this file:').' <span>'.$file.'</span></li>';
						$this->count_errors++;
					}
			}

		return true;
	}

	/**
	 * Replace sentence "ISO_LANG_CODE" by iso code of language in file and get a good name for lang file
	 *
	 * @param $file
	 * @param $iso_code : iso_code of language
	 * @return string : name of file
	 */
	public function getNameLangFile($file, $iso_code)
	{
		return str_replace('ISO_LANG_CODE', $iso_code, $file);
	}

	/**
	 * Find and fill all translations
	 *
	 * @param $file
	 * @param $type : type of translation
	 */
	public function findAndFillTranslations($file, $type)
	{
		if (file_exists($file))
		{
			// 5.1 - Get module name if is module
			if ($type == 'modules')
			{
				// Get the name of module
				$module_name = str_replace(_PS_ROOT_DIR_.'/modules/', '', $file);

				// Update name of module if is a path
				if (preg_match('/\//i', $module_name))
					$module_name = substr($module_name, 0, strpos($module_name, '/'));
			}
			else
				$module_name = false;

			// 5.2 - Get all content in this file
			$content = file_get_contents($file);

			// 5.3 - Get params for an sentence to search
			$params = $this->getParamsBytype($type, $file, $module_name);

			// 5.4 - Get all sentence which could be translated
			preg_match_all($params['regex'], $content, $matches);

			foreach ($matches[1] as $key)
			{
				// 5.5 - Get a new content of this file
				$content = file_get_contents($file);

				// 5.6 - Get old key MD5
				$old_md5 = $params['prefix_key'].md5($key);

				if (isset($this->new_translation[$type][$module_name]) && $type == 'modules')
					$new_translation = $this->new_translation[$type][$module_name];
				else if (isset($this->new_translation[$type]))
					$new_translation = $this->new_translation[$type];

				if (isset($new_translation[$old_md5]))
				{
					// 5.7 - Get new key MD5
					$new_md5_key = $params['prefix_key'].md5(str_replace("'", "\'", $new_translation[$old_md5]['new_string']));

					// 5.8 - Add new informations
					if (isset($this->new_translation[$type][$module_name][$old_md5]) && $type == 'modules')
						$this->new_translation[$type][$module_name][$old_md5] = array_merge($this->new_translation[$type][$module_name][$old_md5], array(
							'old_string' => $key,
							'new_md5_key' => $new_md5_key,
							'old_md5_key' => $old_md5,
							'module_name' => $module_name
						));
					else
						$this->new_translation[$type][$old_md5] = array_merge($this->new_translation[$type][$old_md5], array(
							'old_string' => $key,
							'new_md5_key' => $new_md5_key,
							'old_md5_key' => $old_md5
						));

					// 5.9 - Prepare the replace of new sentence
					$params_replace = $this->getPatternsAndReplace($type, $file, $new_translation[$old_md5]['new_string'], $key);

					$replace = $params_replace['replace'];
					$pattern = $params_replace['pattern'];

					if (preg_match($pattern, $content, $matches_check))
					{
						// 5.10 - Replace old sentence by the new sentence
						$file_content = preg_replace($pattern, $replace, $content, 1, $count);

						if (file_put_contents($file, $file_content) && $count == 1)
						{
							echo '<li class="success">'.sprintf(
								$this->l('This string has been replace %1$s by %2$s in this file: %3$s'),
								'<span>"'.$key.'"</span>',
								'<span>"'.$new_translation[$old_md5]['new_string'].'"</span>',
								'<span>'.$file.'</span>'
							).'</li>';
							$this->count_sucess++;
						}
						else
						{
							echo '<li class="errors">'.sprintf(
								$this->l('This string has not been replace %1$s by %2$s in this file: %3$s (count: %4$d)'),
								'<span>"'.$key.'"</span>',
								'<span>"'.$new_translation[$old_md5]['new_string'].'"</span>',
								'<span>'.$file.'</span>',
								$count
							).'</li>';
							$this->count_errors++;
						}
					}
					else
					{
						echo '<li class="errors">'.sprintf(
							$this->l('This string has not been find: %1$s in this file: %2$s'),
							'<span>"'.$key.'"</span>',
							'<span>'.$file.'</span>'
						).'</li>';
						$this->count_errors++;
					}
				}
			}
		}
		else
		{
			echo '<li class="errors">'.sprintf(
				$this->l('This file not exists: %s'),
				'<span>'.$file.'</span>'
			).'</li>';
			$this->count_errors++;
		}
	}

	/**
	 * Get params by type of translation
	 *
	 * @param $type : type of translation
	 * @param $file
	 * @param $module_name : name of module if type is modules
	 * @return array $params
	 */
	public function getParamsBytype($type, $file, $module_name)
	{
		$params = array();

		switch ($type)
		{
			case 'front':
				$params['regex'] = '/\{l s=\''._PS_TRANS_PATTERN_.'\'( js=1)?\}/U';
				$params['prefix_key'] = substr(basename($file), 0, -4).'_';
				break;

			case 'admin':
				if (preg_match('/^(.*)\.php$/', $file))
					$params['regex'] = '/this->l\(\''._PS_TRANS_PATTERN_.'\'[\)|\,]/U';
				else
					$params['regex'] = '/\{l s=\''._PS_TRANS_PATTERN_.'\'( js=1)?( slashes=1)?\}/U';

				$params['prefix_key'] = _PS_VERSION_ < '1.5' ? basename(substr($file, 0, -4)) : $this->getPrefixKeyForBack($file);
				break;

			case 'modules':
				if (preg_match('/^(.*)\.php$/', $file))
					$params['regex'] = '/->l\(\''._PS_TRANS_PATTERN_.'\'(, ?\'(.+)\')?(, ?(.+))?\)/Ui';
				else
					$params['regex'] = '/\{l s=\''._PS_TRANS_PATTERN_.'\'( mod=\'.+\')?( js=1)?\}/Ui';

				// Get file name without extension
				$name_file = substr(basename($file), 0, -4);

				$params['prefix_key'] = '<{'.Tools::strtolower($module_name).'}prestashop>'.Tools::strtolower($name_file).'_';
				break;

			case 'errors':
				$params['regex'] = '/Tools::displayError\(\''._PS_TRANS_PATTERN_.'\'(, ?(true|false))?\)/U';
				$params['prefix_key'] = '';
				break;

			case 'pdf':
				if (_PS_VERSION_ < '1.5')
				{
					$params['regex'] = '/self::l\(\''._PS_TRANS_PATTERN_.'\'[\)|\,]/U';
					$params['prefix_key'] = 'PDF_invoice';
				}
				else
				{
					$params['regex'] = '/HTMLTemplate.*::l\(\''._PS_TRANS_PATTERN_.'\'[\)|\,]/U';
					$params['prefix_key'] = 'PDF';
				}
				break;
		}

		return $params;
	}

	public function getPatternsAndReplace($type, $file, $new_string, $old_string)
	{
		$params = array();

		switch ($type)
		{
			case 'front':
			case 'admin':
			case 'modules':
				if (preg_match('/^(.*)\.php$/', $file))
				{
					$params['replace'] = '\1->l(\''.str_replace("'", "\'", $new_string).'\'';
					$params['pattern'] = '`->l\(\''.Tools::pRegexp($old_string, '\\').'\'`';
				}
				else
				{
					$params['replace'] = '\1{l s=\''.str_replace("'", "\'", $new_string).'\'';
					$params['pattern'] = '`\{l s=\''.Tools::pRegexp($old_string, '\\').'\'`';
				}
				break;
			case 'errors':
				$params['replace'] = '\1Tools::displayError(\''.str_replace("'", "\'", $new_string).'\'';
				$params['pattern'] = '`Tools::displayError\(\''.Tools::pRegexp($old_string, '\\').'\'`';
				break;
			case 'pdf':
				if (_PS_VERSION_ < '1.5')
				{
					$params['replace'] = '\1self::l(\''.str_replace("'", "\'", $new_string).'\'';
					$params['pattern'] = '`self::l\(\''.Tools::pRegexp($old_string, '\\').'\'`';
				}
				else
				{
					$params['replace'] = '\1::l(\''.str_replace("'", "\'", $new_string).'\'';
					$params['pattern'] = '`::l\(\''.Tools::pRegexp($old_string, '\\').'\'`';
				}
				break;
		}

		return $params;
	}

	/**
	 * Get name of prefix key for admin file
	 *
	 * @param $file
	 * @return string $prefix_key
	 */
	public function getPrefixKeyForBack($file)
	{
		if (preg_match('/^(.*)\.php$/', $file))
		{
			$prefix_key = basename($file);

			// -4 becomes -14 to remove the ending "Controller.php" from the filename
			if (strpos($file, 'Controller.php') !== false)
				$prefix_key = basename(substr($file, 0, -14));
			else if (strpos($file, 'Helper') !== false)
				$prefix_key = 'Helper';

			if ($prefix_key == 'Admin')
				$prefix_key = 'AdminController';
		}
		else
		{
			// get controller name instead of file name
			$prefix_key = Tools::toCamelCase(str_replace(_PS_ADMIN_DIR_.DIRECTORY_SEPARATOR.'themes', '', $file), true);
			$pos = strrpos($prefix_key, DIRECTORY_SEPARATOR);
			$tmp = substr($prefix_key, 0, $pos);

			if (preg_match('#admin#', $tmp))
			{
				$parent_class = explode(DIRECTORY_SEPARATOR, $file);
				$key = array_search('admin', $parent_class);
				$prefix_key = str_replace('Controller.php', '', ucfirst($parent_class[$key + 1]));
			}
			else if (preg_match('#controllers#', $tmp))
			{
				$parent_class = explode(DIRECTORY_SEPARATOR, $tmp);
				$key = array_search('controllers', $parent_class);
				$prefix_key = 'Admin'.ucfirst($parent_class[$key + 1]);
			}
			else if (preg_match('#helpers#', $tmp))
			{
				$parent_class = explode(DIRECTORY_SEPARATOR, $tmp);
				$key = array_search('helpers', $parent_class);
				if (isset($parent_class[$key + 1]))
					$prefix_key = 'Admin'.ucfirst($parent_class[$key + 1]);
				else
					$prefix_key = 'Admin'.ucfirst($parent_class[$key]);
			}
			else
				$prefix_key = basename(substr($file, 0, -4));

			// Adding list, form, option in Helper Translations
			$list_prefix_key = array('AdminHelpers', 'AdminList', 'AdminView', 'AdminOptions', 'AdminForm', 'AdminHelpAccess');
			if (in_array($prefix_key, $list_prefix_key))
				$prefix_key = 'Helper';

			// Adding the folder backup/download/ in AdminBackup Translations
			if ($prefix_key == 'AdminDownload')
				$prefix_key = 'AdminBackup';

			// use the prefix "AdminController" (like old php files 'header', 'footer.inc', 'index', 'login', 'password', 'functions'
			if ($prefix_key == 'Admin' || $prefix_key == 'AdminTemplate')
				$prefix_key = 'AdminController';
		}

		return $prefix_key;
	}

	/**
	 * Find all new translation in english file
	 *
	 * @param $english_files : List of english files
	 * @return bool
	 */
	public function findAllNewTranslation($english_files)
	{
		foreach ($english_files as $type => $files)
		{
			foreach ($files as $module_name => $file)
			{
				$translation = $this->findAllNewTranslationByFile($file, $type);

				if (!empty($translation))
					if ($type == 'modules')
						$this->new_translation[$type][$module_name] = $translation;
					else
						$this->new_translation[$type] = $translation;
			}
		}

		return true;
	}

	/**
	 * Find all new translation in english file
	 *
	 * @param $filename : Name of file
	 * @param $type : type of translation
	 * @return array list of Translation or bool false
	 */
	public function findAllNewTranslationByFile($filename, $type)
	{
		$array_translation = array();

		$file = $this->getNameLangFile($filename, 'en');

		if (file_exists($file))
		{
			// 4.1 - Add translation file in english
			require($file);

			// 4.2 - Get array of translsations
			if (isset(${$this->var_types[$type]['var_name']}))
				$array_tmp = ${$this->var_types[$type]['var_name']};
			else
			{
				echo '<li class="errors">'.sprintf(
					$this->l('This property %1$s isn\'t in this file: %2$s'),
					'<span>$'.$this->var_types[$type]['var_name'].'</span>',
					'<span>'.$file.'</span>'
				).'</li>';
				$this->count_errors++;
				return false;
			}

			if (!empty($array_tmp))
			{
				foreach ($array_tmp as $old_key_md5 => $row)
				{
					// 4.3 - Get new MD5
					// Warning : It must add the prefix before the key
					$new_md5 = md5(stripslashes($row));

					// 4.4 - Check if the new md5 exists or not in the old key
					preg_match('/'.$new_md5.'/', $old_key_md5, $matches);

					if (empty($matches))
						$array_translation[$old_key_md5] = array(
							'file_lang' => $filename,
							'new_string' => stripslashes($row)
						);
				}

				return $array_translation;
			}
			else
				return false;
		}
		else
		{
			echo '<li class="errors">'.sprintf($this->l('This file not exists: %s'), '<span>'.$file.'</span>').'</li>';
			return false;
		}
	}

	/**
	 * Get all translation files in English
	 *
	 * @return array
	 */
	public function getAllEnglishFiles()
	{
		$files = array();

		foreach ($this->var_types as $type => $values)
			if ($type == 'modules')
				$files[$type] = $this->getAllEnglishFilesForModules();
			else
				$files[$type] = array($values['file']);

		return $files;
	}

	/**
	 * Get all translation files in English for "module" type
	 *
	 * @return array
	 */
	public function getAllEnglishFilesForModules()
	{
		$files = array();

		foreach ($this->files_modules as $module_file)
		{
			// moduleFile is the absolute path
			$module = dirname($module_file);

			// Get the name of module
			$module_name = str_replace(_PS_ROOT_DIR_.'/modules/', '', $module);

			// Update name of module if is a path
			if (preg_match('/\//i', $module_name))
				$module_name = substr($module_name, 0, strpos($module_name, '/'));

			// Loading all lang files
			if (file_exists($module.DIRECTORY_SEPARATOR.'translations'.DIRECTORY_SEPARATOR.'en.php'))
				$files[$module_name] = str_replace('/', DIRECTORY_SEPARATOR, $module.DIRECTORY_SEPARATOR.'translations'.DIRECTORY_SEPARATOR.'ISO_LANG_CODE.php');
			else if (file_exists($module.DIRECTORY_SEPARATOR.'en.php'))
				$files[$module_name] = str_replace('/', DIRECTORY_SEPARATOR, $module.DIRECTORY_SEPARATOR.'ISO_LANG_CODE.php');
		}

		return $files;
	}

	/**
	 * This method call all function for each type of translation and it will always started by "getAllFilesFor...()"
	 *
	 * @return array
	 */
	public function getAllFiles()
	{
		$files = array();

		foreach ($this->var_types as $type => $values)
		{
			if (method_exists($this, 'getAllFilesFor'.Tools::toCamelCase($type, true)))
			{
				// Call the method
				call_user_func(array($this, 'getAllFilesFor'.Tools::toCamelCase($type, true)));

				$files[$type] = $this->{'files_'.$type};
			}
		}

		return $files;
	}

	public function myscandir($path, $ext = 'php', $dir = '', $recursive = false)
	{
		$path = rtrim(rtrim($path, '\\'), '/').'/';
		$real_path = rtrim(rtrim($path.$dir, '\\'), '/').'/';

		$files = scandir($real_path);
		if (!$files)
			return array();

		$filtered_files = array();

		$real_ext = '';
		if (!empty($ext))
			$real_ext = '.'.$ext;
		$real_ext_length = strlen($real_ext);

		$subdir = ($dir) ? $dir.'/' : '';
		foreach ($files as $file)
		{
			if (strpos($file, $real_ext) && strpos($file, $real_ext) == (strlen($file) - $real_ext_length))
				$filtered_files[] = $subdir.$file;

			if ($recursive && $file[0] != '.' && is_dir($real_path.$file))
				foreach ($this->myscandir($path, $ext, $subdir.$file, $recursive) as $subfile)
					$filtered_files[] = $subfile;
		}
		return $filtered_files;
	}

	/**
	 * Get a list of all Admin files
	 */
	public function getAllFilesForAdmin()
	{
		if (_PS_VERSION_ < '1.5')
		{
			$admin_controller_dir = str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_).DIRECTORY_SEPARATOR.'tabs';
			$admin_tab_file = DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'AdminTab.php';
		}
		else
		{
			$admin_controller_dir = DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.'admin';
			$admin_tab_file = DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'AdminController.php';
		}

		// Get all controller files php in directory "controller/admin/"
		if (file_exists(_PS_ROOT_DIR_.$admin_controller_dir))
			$this->files_admin = $this->myscandir(_PS_ROOT_DIR_, 'php', $admin_controller_dir);

		// Add parent AdminController
		if (file_exists(_PS_ROOT_DIR_.$admin_tab_file))
			$this->files_admin[] = $admin_tab_file;

		// Get all helpers files php in directory "classes/helper/"
		if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'helper'))
			$this->files_admin = array_merge(
				$this->files_admin,
				$this->myscandir(_PS_ROOT_DIR_, 'php', DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'helper')
			);

		// Add PHP file in admin directory
		$this->files_admin = array_merge(
			$this->files_admin,
			$this->myscandir(_PS_ROOT_DIR_, 'php', DIRECTORY_SEPARATOR.str_replace(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR, '', _PS_ADMIN_DIR_))
		);

		// Create a good path
		$this->files_admin = array_map(array($this, 'returnTheGoodPath'), $this->files_admin);

		// Add template files
		$this->files_admin = array_merge($this->files_admin, $this->listFiles(_PS_ADMIN_DIR_.DIRECTORY_SEPARATOR.'themes'));
	}

	/**
	 * Get a list of all Front files
	 */
	public function getAllFilesForFront()
	{
		if (_PS_VERSION_ < '1.5')
			$front_controller_dir = DIRECTORY_SEPARATOR.'controllers';
		else
			$front_controller_dir = DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.'front';

		// Get all files php in directory "themes/"
		if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->name_theme))
			$this->files_front = $this->myscandir(_PS_ROOT_DIR_, 'php', DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->name_theme);

		// Get all files tpl in directory "themes/"
		if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->name_theme))
			$this->files_front = array_merge(
				$this->files_front,
				$this->myscandir(_PS_ROOT_DIR_, 'tpl', DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->name_theme)
			);

		// Get all controller files php in directory "controller/front/"
		if (file_exists(_PS_ROOT_DIR_.$front_controller_dir))
			$this->files_front = array_merge($this->files_front, $this->myscandir(_PS_ROOT_DIR_, 'php', $front_controller_dir));

		// Create a good path
		$this->files_front = array_map(array($this, 'returnTheGoodPath'), $this->files_front);
	}

	/**
	 * Get a list of all Modules files
	 *
	 * @param bool $only_php_files
	 * @return array
	 */
	public function getAllFilesForModules()
	{
		$modules = scandir(_PS_MODULE_DIR_);

		foreach ($modules as $module)
			if (!in_array($module, self::$ignore_dir) && file_exists(_PS_MODULE_DIR_.$module))
				$this->recursiveGetModuleFiles(_PS_MODULE_DIR_.$module.'/', $module, _PS_MODULE_DIR_.$module, true);
	}

	/**
	 * Get a list of all Errors files
	 */
	public function getAllFilesForErrors()
	{
		// List of type
		$type_for_errors_files = array('admin', 'front', 'modules', 'pdf');

		// Get all controller files php in directory "classes/"
		if (file_exists(DIRECTORY_SEPARATOR.'classes'))
			$this->files_errors = $this->myscandir(_PS_ROOT_DIR_, 'php', DIRECTORY_SEPARATOR.'classes');

		// Create a good path
		$this->files_errors = array_map(array($this, 'returnTheGoodPath'), $this->files_errors);

		// Get all files errors by type
		foreach ($type_for_errors_files as $type)
			if (isset($this->var_types[$type]))
				$this->files_errors = array_merge($this->files_errors, $this->{'files_'.$type});
			else
			{
				if (method_exists($this, 'getAllFilesFor'.$type))
					call_user_func(array($this, 'getAllFilesFor'.$type));

				$this->files_errors = array_merge($this->files_errors, $this->{'files_'.$type});
			}

		// Take all PHP files only
		$this->files_errors = $this->takeAllPhpFiles();
	}

	/**
	 * Get a list of all PDF files
	 */
	public function getAllFilesForPdf()
	{
		if (_PS_VERSION_ < '1.5')
		{
			// Add parent AdminController
			if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'PDF.php'))
				$this->files_pdf[] = DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'PDF.php';
		}
		else
		{
			// Get all files php in directory "classes/pdf/"
			if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'pdf'))
				$this->files_pdf = $this->myscandir(_PS_ROOT_DIR_, 'php', DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'pdf');

			// Get all files tpl in directory "themes/"
			if (file_exists(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->name_theme.DIRECTORY_SEPARATOR.'pdf'))
				$this->files_pdf = array_merge(
					$this->files_pdf,
					$this->myscandir(_PS_ROOT_DIR_, 'tpl', DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->name_theme.DIRECTORY_SEPARATOR.'pdf')
				);
		}

		// Create a good path
		$this->files_pdf = array_map(array($this, 'returnTheGoodPath'), $this->files_pdf);
	}

	/**
	 * @param string $item
	 * @return string
	 */
	public function returnTheGoodPath($item)
	{
		return str_replace('/', DIRECTORY_SEPARATOR, _PS_ROOT_DIR_.$item);
	}

	/**
	 * Get all PHP file in an array
	 *
	 * @return array $files
	 */
	public function takeAllPhpFiles()
	{
		$files = array();

		foreach ($this->files_errors as $file)
			if (preg_match('/.php$/', $file))
				$files[] = $file;

		return $files;
	}

	/**
	 * Return a filtered list of files or folders
	 *
	 * @param array $files
	 * @param string $type_clear can be 'file' or 'directory'
	 * @param string $path
	 * @return array of filtered files or folders
	 */
	public function clearModuleFiles($files, $type_clear = 'file', $path = '')
	{
		$return = array();

		$arr_exclude = array('img', 'js', 'mails');

		$arr_good_ext = array('.tpl', '.php');

		foreach ($this->languages as $lang)
			$array_iso[] = $lang['iso_code'];

		foreach ($files as $file)
		{
			if (in_array($file[0], self::$ignore_dir))
				continue;

			if (!in_array(substr($file, 0, strrpos($file, '.')), $array_iso) && ($type_clear == 'file' && in_array(substr($file, strrpos($file, '.')), $arr_good_ext)))
				$return[] = $path.$file;
			else if ($type_clear == 'directory' && is_dir($path.$file) && !in_array($file, $arr_exclude))
				$return[] = $file;
		}

		return $return;
	}

	/**
	 * Get list of modules files in recursive method
	 *
	 * @param $path
	 * @param $array_files
	 * @param $module_name
	 * @param $lang_file
	 * @param bool $is_default
	 */
	protected function recursiveGetModuleFiles($path, $module_name, $lang_file, $is_default = false)
	{
		$files_module = scandir($path);

		$files_for_module = $this->clearModuleFiles($files_module, 'file', $path);

		if (!empty($files_for_module))
			$this->files_modules = array_merge($this->files_modules, $files_for_module);

		$dir_module = $this->clearModuleFiles($files_module, 'directory', $path);

		if (!empty($dir_module))
			foreach ($dir_module as $folder)
				$this->recursiveGetModuleFiles($path.$folder.'/', $module_name, $lang_file, $is_default);
	}

	/**
	 * Recursively list files in directory $dir
	 *
	 * @param $dir
	 * @param array $list
	 * @return array
	 */
	public function listFiles($dir, $list = array())
	{
		$file_ext = 'tpl';
		$dir = rtrim($dir, '/').DIRECTORY_SEPARATOR;

		$to_parse = scandir($dir);
		// copied (and kind of) adapted from AdminImages.php
		foreach ($to_parse as $file)
		{
			if (!in_array($file, self::$ignore_dir))
			{
				if (preg_match('#'.preg_quote($file_ext, '#').'$#i', $file))
					$list[] = $dir.$file;
				else if (is_dir($dir.$file))
					$list = $this->listFiles($dir.$file, $list);
			}
		}
		return $list;
	}
}