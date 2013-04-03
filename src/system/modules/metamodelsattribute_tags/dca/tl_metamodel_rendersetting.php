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

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['config']['onload_callback'][]
	= array('TableMetaModelRenderSettingTags', 'initializeTagsJumpToLanguageOptions');

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metapalettes']['tags extends default'] = array(
	'+display' => array('tags_display'),
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metasubselectpalettes']['tags_display']['jumpTo']
	= array('tags_jumpTo');
$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metasubselectpalettes']['tags_display']['rendersettings']
	= array('tags_rendersettings');

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['tags_display'] = array(
	'label'				=> &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_display'],
	'inputType'			=> 'select',
	'options'			=> array(/*'jumpTo',*/ 'rendersettings'),
	'reference'			=> &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_displayOptions'],
	'eval'				=> array('submitOnChange'=>true, 'includeBlankOption'=>true, 'tl_class'=>'clr')
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['tags_rendersettings'] = array(
	'label'				=> &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_rendersettings'],
	'inputType'			=> 'select',
	'options_callback'	=> array('TableMetaModelRenderSettingTags', 'getTagsTableRenderSettings'),
	'eval'				=> array('mandatory'=>true, 'tl_class'=>'clr')
);

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['tags_jumpTo'] = array(
	'label'				=> &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo'],
	'exclude'			=> true,
	'minCount'			=> 1,
	'maxCount'			=> 1,
	'disableSorting'	=> '1',
	'inputType'			=> 'multiColumnWizard',
	'load_callback'		=> array(array('TableMetaModelRenderSettingTags', 'loadTagsJumpTo')),
	'save_callback'		=> array(array('TableMetaModelRenderSettingTags', 'saveTagsJumpTo')),
	'eval' => array(
		'style'				=> 'width:100%;',
		'columnFields' => array(
			'langcode' => array(
				'label'			=> &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_language'],
				'exclude'		=> true,
				'inputType'		=> 'justtextoption',
				'options'		=> array('xx' => $GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_allLanguages']),
				'eval'			=> array('valign'=>'center')
			),
			'value' => array(
				'label'			=> &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_page'],
				'exclude'		=> true,
				'inputType'		=> 'text',
				'wizard'		=> array(array('TableMetaModelRenderSettingTags', 'pagePicker')),
				'eval'			=> array('style'=>'width:200px;')
			),
			'filter' => array(
				'label'			=> &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_filter'],
				'exclude'		=> true,
				'inputType'		=> 'select',
				'options_callback'=> array('TableMetaModelRenderSettingTags', 'getTagsTableFilterSettings'),
				'eval'			=> array('includeBlankOption'=>true, 'style'=>'width:200px;'),
			),
		),
		'buttons' => array('copy' => false, 'delete' => false, 'up' => false, 'down' => false)
	)
);
