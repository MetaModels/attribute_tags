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
 * @package    MetaModels
 * @subpackage AttributeTags
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

/**
 * Table tl_metamodel_tag_relation
 */
$GLOBALS['TL_DCA']['tl_metamodel_tag_relation'] = array
(
    // Config
    'config' => array
    (
        'sql' => array
        (
            'keys' => array
            (
                'id'                        => 'primary',
                'att_id,item_id,value_id'   => 'index'
            )
        )
    ),
    // Fields
    'fields' => array
    (
        'id' => array
        (
            'sql'                     => 'int(11) unsigned NOT NULL auto_increment'
        ),
        'att_id' => array
        (
            'sql'                     => 'int(11) unsigned NOT NULL default \'0\''
        ),
        'item_id' => array
        (
            'sql'                     => 'int(11) unsigned NOT NULL default \'0\''
        ),
        'value_sorting' => array
        (
            'sql'                     => 'int(11) unsigned NOT NULL default \'0\''
        ),
        'value_id' => array
        (
            'sql'                     => 'int(11) unsigned NOT NULL default \'0\''
        )
    )
);
