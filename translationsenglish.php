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

class translationsenglish extends Module
{
	public function __construct()
	{
		$this->name = 'translationsenglish';
		$this->tab = 'dev';
		$this->version = '1.0';

		parent::__construct();

		$this->displayName = $this->l('Prestashop Translation English');
		$this->description = $this->l('Search all translation which are english and change key in all files');
	}

	public function install()
	{
		$id_tabs = Tab::getIdFromClassName('AdminReverseTranslations');

		// Then we add AdminReverseTranslations only if not exists
		if (!$id_tabs)
		{
			$tab = new Tab();
			$tab->class_name = 'AdminReverseTranslations';
			$tab->module = 'translationsenglish';
			$tab->id_parent = version_compare(_PS_VERSION_, '1.5', '<') ? 9 : 14;
			$languages = Language::getLanguages(false);
			foreach ($languages as $lang)
				$tab->name[$lang['id_lang']] = 'Reverse Translation';
			$res = $tab->save();
			if (!$res)
				$this->_errors[] = $this->l('New tab "AdminReverseTranslations" cannot be created');
		}
		else
			$tab = new Tab((int)$id_tabs);

		return parent::install() && Configuration::updateValue('PS_TRANSLATIONS_ENGLISH_ID_TAB', $tab->id);
	}

	public function uninstall()
	{
		$id_tab = Configuration::get('PS_TRANSLATIONS_ENGLISH_ID_TAB');
		if ($id_tab)
		{
			$tab = new Tab((int)$id_tab, 1);
			$res = $tab->delete();
		}
		else
			$res = true;

		if (!$res || !parent::uninstall())
			return false;

		return true;
	}
}

