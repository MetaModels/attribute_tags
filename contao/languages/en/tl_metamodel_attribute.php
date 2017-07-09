<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

// Legends.
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['display_legend'] = 'Display settings';

// Fields.
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['typeOptions']['tags'] = 'Tags';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table'][0]        = 'Database table';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table'][1]        = 'Please select the database table.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_column'][0]       = 'Table column';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_column'][1]       = 'Please select the column.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_id'][0]           = 'Tag ID';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_id'][1]           = 'Please select a entry for the tag id.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_alias'][0]        = 'Tag alias';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_alias'][1]        = 'Please select a entry for the tag alias.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_sorting'][0]      = 'Tag sorting';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_sorting'][1]      = 'Please select a entry for the tag sorting.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_where'][0]        = 'SQL';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_where'][1]        =
    'The list of options can be limited by using SQL.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_filter'][0]       = 'Filter';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_filter'][1]       = 'Here you can choose the filter to use.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_filterparams'][0] = 'Filter parameters';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_filterparams'][1] = 'Here you can choose a default value for the filter.';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_sort'][0]         = 'Sorting direction';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_sort'][1]         = 'The sorting direction.';

/**
 * Misc.
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error']             = 'The SQL query causes an error.';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table_type']['translated']   = 'Translated MetaModels';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table_type']['untranslated'] = 'Untranslated MetaModels';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table_type']['sql-table']    = 'SQL Table';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_column_type']['attribute'] = 'MetaModel attributes';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_column_type']['sql']       = 'SQL table column';

$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_sort_directions']['asc']  = 'Ascending';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_sort_directions']['desc'] = 'Descending';
