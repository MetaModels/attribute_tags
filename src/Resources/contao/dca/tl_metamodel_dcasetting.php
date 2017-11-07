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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['tags'] = array
(
    'presentation' => array
    (
        'tl_class',
        'submitOnChange',
        'tag_as_wizard'
    ),
    'functions'    => array
    (
        'mandatory'
    ),
    'overview'     => array
    (
        'filterable',
        'searchable',
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['palettes']['__selector__'][] = 'tag_as_wizard';

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['tag_as_wizard'][2] = array(
    'presentation after tag_as_wizard' => array(
        'tag_minLevel', 'tag_maxLevel'
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_as_wizard'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_as_wizard'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => array(0, 1, 2, 3),
    'reference' => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_as_wizard_reference'],
    'eval'      => array
    (
        'tl_class' => 'clr',
        'submitOnChange'     => true,
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_minLevel'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_minLevel'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => array
    (
        'tl_class' => 'clr w50'
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['fields']['tag_maxLevel'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_dcasetting']['tag_maxLevel'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => array
    (
        'tl_class' => 'w50'
    )
);
