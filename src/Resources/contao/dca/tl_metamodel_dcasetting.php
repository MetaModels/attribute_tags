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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

use Contao\System;
use MetaModels\ContaoFrontendEditingBundle\MetaModelsContaoFrontendEditingBundle;

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['tags'] = [
    'presentation' => [
        'tl_class',
        'be_template',
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
        'tag_minLevel',
        'tag_maxLevel'
    ]
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_as_wizard'] = [
    'label'       => 'tag_as_wizard.label',
    'description' => 'tag_as_wizard.description',
    'exclude'     => true,
    'inputType'   => 'select',
    'options'     => [0, 1, 2, 3],
    'reference'   => [
        '0' => 'tag_as_wizard_reference.0',
        '1' => 'tag_as_wizard_reference.1',
        '2' => 'tag_as_wizard_reference.2',
        '3' => 'tag_as_wizard_reference.3',
    ],
    'eval'        => [
        'tl_class'       => 'clr w50',
        'submitOnChange' => true,
    ],
    'sql'         => 'varchar(1) NOT NULL default \'0\''
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_minLevel'] = [
    'label'       => 'tag_minLevel.label',
    'description' => 'tag_minLevel.description',
    'exclude'     => true,
    'inputType'   => 'text',
    'eval'        => [
        'tl_class' => 'clr w50'
    ],
    'sql'         => 'int(11) NOT NULL default \'0\''
];

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_maxLevel'] = [
    'label'       => 'tag_maxLevel.label',
    'description' => 'tag_maxLevel.description',
    'exclude'     => true,
    'inputType'   => 'text',
    'eval'        => [
        'tl_class' => 'w50'
    ],
    'sql'         => 'int(11) NOT NULL default \'0\''
];

// Load configuration for the frontend editing.
if (\in_array(
    MetaModelsContaoFrontendEditingBundle::class,
    System::getContainer()->getParameter('kernel.bundles'),
    true
)) {
    $GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['tags']['presentation'][] =
        'fe_template';
}
