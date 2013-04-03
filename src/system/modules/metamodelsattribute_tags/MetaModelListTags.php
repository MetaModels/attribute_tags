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
 * @package    MetaModels
 * @subpackage AttributeTags
 * @author     Oliver Hoff <oliver@hofff.com>
 */
class MetaModelListTags extends MetaModelList {

	protected $objTagAttribute;

	protected $arrTagIDs;

	public function __construct() {
		parent::__construct();
		$this->arrParam = array();
		$this->objFilterSettings = new MetaModelFilterSettings(array());
	}

	public function setTagAttribute($objTagAttribute) {
		$this->objTagAttribute = $objTagAttribute;
		return $this;
	}

	public function setTagIDs($arrTagIDs) {
		$this->arrTagIDs = (array) $arrTagIDs;
		return $this;
	}

	protected function modifyFilter() {
		if(!$this->arrTagIDs || $this->objTagAttribute->get('tag_id') == 'id') {
			$this->objFilter->addFilterRule(new MetaModelFilterRuleStaticIdList((array) $this->arrTagIDs));
			return;
		}

		$strQuery = sprintf('SELECT id FROM %s WHERE %s IN (%s)',
			$this->objMetaModel->getTableName(),
			$this->objTagAttribute->get('tag_id'),
			rtrim(str_repeat('?,', count($this->arrTagIDs)), ',')
		);
		$this->objFilter->addFilterRule(new MetaModelFilterRuleSimpleQuery($strQuery, $this->arrTagIDs));
	}

	public function setFilterParam($intFilter, $arrPresets, $arrValues) {
		// nothing
		return $this;
	}

	protected function getFilter() {
		// nothing
	}

}