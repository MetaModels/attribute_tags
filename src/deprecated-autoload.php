<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_tags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

use MetaModels\AttributeTagsBundle\Attribute\AbstractTags;
use MetaModels\AttributeTagsBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTagsBundle\Attribute\MetaModelTags;
use MetaModels\AttributeTagsBundle\Attribute\Tags;
use MetaModels\AttributeTagsBundle\FilterRule\FilterRuleTags;

// This hack is to load the "old locations" of the classes.
spl_autoload_register(
    function ($class) {
        static $classes = [
            'MetaModels\Attribute\Tags\AbstractTags' => AbstractTags::class,
            'MetaModels\Attribute\Tags\AttributeTypeFactory' => AttributeTypeFactory::class,
            'MetaModels\Attribute\Tags\Tags'               => Tags::class,
            'MetaModels\Attribute\Tags\MetaModelTags'         => MetaModelTags::class,
            'MetaModels\Filter\Rules\FilterRuleTags'         => FilterRuleTags::class,
        ];

        if (isset($classes[$class])) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @trigger_error('Class "' . $class . '" has been renamed to "' . $classes[$class] . '"', E_USER_DEPRECATED);
            // @codingStandardsIgnoreEnd

            if (!class_exists($classes[$class])) {
                spl_autoload_call($class);
            }

            class_alias($classes[$class], $class);
        }
    }
);
