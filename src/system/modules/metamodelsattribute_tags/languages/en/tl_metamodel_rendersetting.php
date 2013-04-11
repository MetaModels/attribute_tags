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

$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['display_legend']
	= 'Display settings';
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_display']
	= array('Display mode', '"Link tags" to just link the tags. "Render settings" to use a preset of the tag tables MetaModel.');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_displayOptions'] = array(
	'jumpTo' => 'Link tags',
	'rendersettings' => 'Render settings',
);
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_rendersettings']
	= array('Render settings', 'The render settings to be used.');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo']
	= array('Jump to pages', 'A target page for each language.');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_language']
	= array('Language');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_allLanguages']
	= 'All languages';
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_page']
	= array('Target page');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_filter']
	= array('Filter');
