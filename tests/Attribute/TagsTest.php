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
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\AttributeTagsBundle\Attribute\MetaModelTags;
use MetaModels\AttributeTagsBundle\Attribute\Tags;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class Tags.
 */
class TagsTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     *
     * @param string $language         The language.
     *
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass('MetaModels\IMetaModel');

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue($tableName));

        $metaModel
            ->expects($this->any())
            ->method('getActiveLanguage')
            ->will($this->returnValue($language));

        $metaModel
            ->expects($this->any())
            ->method('getFallbackLanguage')
            ->will($this->returnValue($fallbackLanguage));

        return $metaModel;
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $text = new Tags(
            $this->mockMetaModel('mm_unittest', 'en', 'en'),
            [],
            $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock()
        );
        $this->assertInstanceOf('MetaModels\AttributeTagsBundle\Attribute\Tags', $text);
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationMetaModelSelect()
    {
        $text = new MetaModelTags(
            $this->mockMetaModel('mm_unittest', 'en', 'en'),
            [],
            $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock(),
            $this->getMockForAbstractClass(IFactory::class),
            $this->getMockForAbstractClass(IFilterSettingFactory::class)

        );
        $this->assertInstanceOf('MetaModels\AttributeTagsBundle\Attribute\MetaModelTags', $text);
    }

    /**
     * Test the value to widget method in tree picker mode.
     *
     * @return void
     */
    public function testValueToWidgetForTreePicker()
    {
        $tags = new Tags(
            $this->mockMetaModel('mm_unittest', 'en', 'en'),
            [
                'colname' => 'tags',
                'tag_alias' => 'alias'
            ],
            $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock()
        );

        // Trick attribute into thinking we have a backend running and enable tree picker.
        $tags->getFieldDefinition([
            'tag_as_wizard' => 2,
            'tag_minLevel' => null,
            'tag_maxLevel' => null
        ]);

        $result = $tags->valueToWidget([['alias' => 'alias-value', 'id' => 1], ['alias' => 'alias-value2', 'id' => 2]]);

        // It should return the ids instead of the alias.
        $this->assertSame('1,2', $result);
    }
}
