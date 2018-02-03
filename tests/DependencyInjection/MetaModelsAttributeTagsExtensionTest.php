<?php

/**
 * * This file is part of MetaModels/attribute_tags.
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
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Test\DependencyInjection;

use MetaModels\AttributeTagsBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTagsBundle\DependencyInjection\MetaModelsAttributeTagsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * This test case test the extension.
 */
class MetaModelsAttributeTagsExtensionTest extends TestCase
{
    /**
     * Test that extension can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $extension = new MetaModelsAttributeTagsExtension();

        $this->assertInstanceOf(MetaModelsAttributeTagsExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    /**
     * Test that the services are loaded.
     *
     * @return void
     */
    public function testFactoryIsRegistered()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();

        $container
            ->expects($this->atLeastOnce())
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'metamodels.attribute_tags.factory',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(AttributeTypeFactory::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('metamodels.attribute_factory'));

                            return true;
                        }
                    )
                ]
            );

        $extension = new MetaModelsAttributeTagsExtension();
        $extension->load([], $container);
    }
}
