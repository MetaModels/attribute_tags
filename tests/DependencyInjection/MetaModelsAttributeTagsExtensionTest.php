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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Test\DependencyInjection;

use MetaModels\AttributeTagsBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTagsBundle\DependencyInjection\MetaModelsAttributeTagsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * This test case test the extension.
 *
 * @covers \MetaModels\AttributeTagsBundle\DependencyInjection\MetaModelsAttributeTagsExtension
 */
class MetaModelsAttributeTagsExtensionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $extension = new MetaModelsAttributeTagsExtension();

        $this->assertInstanceOf(MetaModelsAttributeTagsExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    public function testFactoryIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new MetaModelsAttributeTagsExtension();
        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('metamodels.attribute_tags.factory'));
        $definition = $container->getDefinition('metamodels.attribute_tags.factory');
        self::assertCount(1, $definition->getTag('metamodels.attribute_factory'));
    }
}
