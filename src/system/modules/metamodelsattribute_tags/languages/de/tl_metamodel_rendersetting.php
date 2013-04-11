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
	= 'Anzeigeeinstellungen';
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_display']
	= array('Anzeigemodus', '"Verlinkung" um nur die Tags zu verlinken. "Anzeigeeinstellungen" um eine Voreinstellung des MetaModels der Tag-Tabelle zu verwenden.');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_displayOptions'] = array(
	'jumpTo' => 'Verlinkung',
	'rendersettings' => 'Anzeigeeinstellungen',
);
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_rendersettings']
	= array('Anzeigeeinstellungen', 'Die zu verwendenden Anzeigeeinstellungen.');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo']
	= array('Weiterleitungsseiten', 'Eine Zielseite für jede Sprache.');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_language']
	= array('Sprache');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_allLanguages']
	= 'Alle Sprachen';
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_page']
	= array('Zielseite');
$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['tags_jumpTo_filter']
	= array('Filter');
