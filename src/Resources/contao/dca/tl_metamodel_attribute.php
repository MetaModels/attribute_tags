<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2024 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_tags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['tags extends _simpleattribute_'] = [
    '+display' => [
        'tag_table after description',
        'tag_column',
        'tag_id',
        'tag_alias',
        'tag_sorting',
        'tag_sort',
        'tag_where',
        'tag_filter',
        'tag_filterparams'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_table'] = [
    'label'       => 'tag_table.label',
    'description' => 'tag_table.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
    'sql'         => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_column'] = [
    'label'       => 'tag_column.label',
    'description' => 'tag_column.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
    'sql'         => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_id'] = [
    'label'       => 'tag_id.label',
    'description' => 'tag_id.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
    'sql'         => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_alias'] = [
    'label'       => 'tag_alias.label',
    'description' => 'tag_alias.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
    'sql'         => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_sorting'] = [
    'label'       => 'tag_sorting.label',
    'description' => 'tag_sorting.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'eval'        => [
        'includeBlankOption' => true,
        'mandatory'          => true,
        'doNotSaveEmpty'     => true,
        'alwaysSave'         => true,
        'tl_class'           => 'w50 clr',
        'chosen'             => 'true'
    ],
    'sql'         => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_where'] = [
    'label'       => 'tag_where.label',
    'description' => 'tag_where.description',
    'exclude'     => true,
    'inputType'   => 'textarea',
    'eval'        => [
        'tl_class'       => 'clr',
        'style'          => 'height: 4em;',
        'decodeEntities' => 'true'
    ],
    'sql'         => 'text NULL'
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_filter'] = [
    'label'       => 'tag_filter.label',
    'description' => 'tag_filter.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'eval'        => [
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'clr w50',
        'chosen'             => 'true'
    ],
    'sql'         => 'int(11) unsigned NOT NULL default \'0\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_filterparams'] = [
    'label'       => 'tag_filterparams.label',
    'description' => 'tag_filterparams.description',
    'exclude'     => true,
    'inputType'   => 'mm_subdca',
    'eval'        => [
        'tl_class' => 'clr m12'
    ],
    'sql'         => 'text NULL'
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['tag_sort'] = [
    'label'       => 'tag_sort.label',
    'description' => 'tag_sort.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'options'     => ['asc', 'desc'],
    'eval'        => [
        'tl_class' => 'w50',
    ],
    'reference'   => [
        'asc'  => 'tag_sort_directions.asc',
        'desc' => 'tag_sort_directions.desc',
    ],
    'sql'         => "varchar(10) NOT NULL default 'asc'"
];
