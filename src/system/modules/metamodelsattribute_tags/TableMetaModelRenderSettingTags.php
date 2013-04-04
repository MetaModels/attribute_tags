<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage AttributeTags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Oliver Hoff <oliver@hofff.com>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

/**
 * Supplementary class for handling render information for tag attributes.
 *
 * @package	   MetaModels
 * @subpackage AttributeTags
 * @author     Oliver Hoff <oliver@hofff.com>
 */
class TableMetaModelRenderSettingTags extends TableMetaModelRenderSetting {

	/** @var TableMetaModelsRenderSettingTags */
	protected static $objInstance = null;

	/** @return TableMetaModelsRenderSettingTags */
	public static function getInstance() {
		isset(self::$objInstance) || self::$objInstance = new self();
		return self::$objInstance;
	}

	public function getTagsTableRenderSettings($objDC) {
		$objRenderSettings = $this->Database->prepare(
			'SELECT rs.id, rs.name
			FROM tl_metamodel_rendersettings AS rs
			JOIN tl_metamodel AS m ON m.id = rs.pid
			JOIN tl_metamodel_attribute AS a ON a.tag_table = m.tableName
			JOIN tl_metamodel_rendersetting AS r ON r.attr_id = a.id
			WHERE r.id = ?
			ORDER BY rs.name'
		)->execute($objDC->id);

		$arrRenderSettings = array();
		while($objRenderSettings->next()) {
			$arrRenderSettings[$objRenderSettings->id] = $objRenderSettings->name;
		}
		return $arrRenderSettings;
	}

	public function getTagsTableFilterSettings($objMCW) {
		$objFilter = $this->Database->prepare(
			'SELECT f.id, f.name
			FROM tl_metamodel_filter AS f
			JOIN tl_metamodel AS m ON m.id = f.pid
			JOIN tl_metamodel_attribute AS a ON a.tag_table = m.tableName
			JOIN tl_metamodel_rendersetting AS r ON r.attr_id = a.id
			WHERE r.id = ?
			ORDER BY f.name'
		)->execute($objMCW->currentRecord);

		$arrFilter = array();
		while($objFilter->next()) {
			$arrFilter[$objFilter->id] = $objFilter->name;
		}
		return $arrFilter;
	}

	public function pagePicker(DataContainer $dc) {
		return ' ' . $this->generateImage('pickpage.gif', $GLOBALS['TL_LANG']['MSC']['pagepicker'], 'style="vertical-align:top;cursor:pointer" onclick="Backend.pickPage(\'ctrl_' . $dc->inputName . '\')"');
	}

	public function loadTagsJumpTo($varValue) {
		$arrRows = $GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['tags_jumpTo']['eval']['columnFields']['langcode']['options'];
		foreach(deserialize($varValue, true) as $arrRow) {
			if(!isset($arrRow['langcode'])) {
				continue;
			}
			strlen($arrRow['value']) && $arrRow['value'] = '{{link_url::' . $arrRow['value'] . '}}';
			$arrRows[$arrRow['langcode']] = $arrRow;
		}
		foreach($arrRows as $strLang => &$arrRow) if(!is_array($arrRow)) {
			$arrRow = array('langcode' => $strLang);
		}
		return array_values($arrRows);
	}

	public function saveTagsJumpTo($varValue) {
		$varValue = deserialize($varValue, true);
		foreach($varValue as &$arrRow) {
			$arrRow['value'] = str_replace(array('{{link_url::', '}}'), array('',''), $arrRow['value']);
		}
		return serialize($varValue);
	}

	public function getMetaModel($objDC) {
		if($this->Input->get('pid')) {
			$intModel = $this->Database->prepare(
				'SELECT	rs.pid
				FROM	tl_metamodel_rendersettings AS rs
				WHERE	rs.id = ?'
			)->execute($this->Input->get('pid'))->pid;

		} elseif($objDC->id) {
			$intModel = $this->Database->prepare(
				'SELECT	rs.pid
				FROM	tl_metamodel_rendersettings AS rs
				JOIN	tl_metamodel_rendersetting AS r ON r.pid = rs.id
				WHERE	r.id = ?'
			)->execute($objDC->id)->pid;
		}

		$objMetaModel = MetaModelFactory::byId($intModel);
		if(!$objMetaModel) {
			throw new Exception('unexpected condition, metamodel unknown', 1);
		}
		return $objMetaModel;
	}

	public function initializeTagsJumpToLanguageOptions($objDC) {
		$strAct = $this->Input->get('act');
		if($strAct != 'create' && $strAct != 'edit') {
			return;
		}

		$objMetaModel = $this->getMetaModel($objDC);
		if(!$objMetaModel->isTranslated()) {
			return;
		}

		$this->loadLanguageFile('languages');
		$arrLanguages = array();
		foreach((array) $objMetaModel->getAvailableLanguages() as $strLangCode) {
			$arrLanguages[$strLangCode] = $GLOBALS['TL_LANG']['LNG'][$strLangCode];
		}
		asort($arrLanguages);

		$arrField = &$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['tags_jumpTo'];
		$arrField['minCount'] = $arrField['maxCount'] = count($arrLanguages);
		$arrField['eval']['columnFields']['langcode']['options'] = $arrLanguages;
	}

}
