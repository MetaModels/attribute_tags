<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_tags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['tags'] = [
    'presentation' => [
        'tl_class',
        'submitOnChange',
        'tag_as_wizard'
    ],
    'functions'    => [
        'mandatory'
    ],
    'overview'     => [
        'filterable',
        'searchable',
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['palettes']['__selector__'][] = 'tag_as_wizard';

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['tag_as_wizard'][2] = [
    'presentation after tag_as_wizard' => [
        'tag_minLevel', 'tag_maxLevel'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_as_wizard'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_as_wizard'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => [0, 1, 2, 3],
    'reference' => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_as_wizard_reference'],
    'eval'      => [
        'tl_class' => 'clr',
        'submitOnChange'     => true,
    ],
    'sql'       => 'varchar(1) NOT NULL default \'0\''
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_minLevel'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_minLevel'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class' => 'clr w50'
    ],
    'sql'       => 'int(11) NOT NULL default \'0\''
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_maxLevel'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_maxLevel'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class' => 'w50'
    ],
    'sql'       => 'int(11) NOT NULL default \'0\''
];
