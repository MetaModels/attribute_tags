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

namespace MetaModels\AttributeTagsBundle\Test;

use MetaModels\AttributeTagsBundle\Attribute\AbstractTags;
use MetaModels\AttributeTagsBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTagsBundle\Attribute\MetaModelTags;
use MetaModels\AttributeTagsBundle\Attribute\Tags;
use MetaModels\AttributeTagsBundle\FilterRule\FilterRuleTags;
use PHPUnit\Framework\TestCase;

/**
 * This class tests if the deprecated autoloader works.
 *
 * @package MetaModels\AttributeSelectBundle\Test
 */
class DeprecatedAutoloaderTest extends TestCase
{
    /**
     * Selectes of old classes to the new one.
     *
     * @var array
     */
    private static $classes = [
        'MetaModels\Attribute\Tags\AbstractTags'         => AbstractTags::class,
        'MetaModels\Attribute\Tags\AttributeTypeFactory' => AttributeTypeFactory::class,
        'MetaModels\Attribute\Tags\Tags'                 => Tags::class,
        'MetaModels\Attribute\Tags\MetaModelTags'        => MetaModelTags::class,
        'MetaModels\Filter\Rules\FilterRuleTags'         => FilterRuleTags::class,
    ];

    /**
     * Provide the alias class map.
     *
     * @return array
     */
    public function provideAliasClassMap()
    {
        $values = [];

        foreach (static::$classes as $select => $class) {
            $values[] = [
                $select,
                $class
            ];
        }

        return $values;
    }

    /**
     * Test if the deprecated classes are aliased to the new one.
     *
     * @param string $oldClass Old class name.
     * @param string $newClass New class name.
     *
     * @dataProvider provideAliasClassMap
     */
    public function testDeprecatedClassesAreAliased($oldClass, $newClass)
    {
        $this->assertTrue(\class_exists($oldClass), \sprintf('Class select "%s" is not found.', $oldClass));

        $oldClassReflection = new \ReflectionClass($oldClass);
        $newClassReflection = new \ReflectionClass($newClass);

        $this->assertSame($newClassReflection->getFileName(), $oldClassReflection->getFileName());
    }
}
