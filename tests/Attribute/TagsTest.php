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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2024 The MetaModels team.
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
 *
 * @covers \MetaModels\AttributeTagsBundle\Attribute\Tags
 */
class TagsTest extends TestCase
{
    /** @SuppressWarnings(PHPMD.Superglobals) */
    protected function setUp(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'en';
        parent::setUp();
    }

    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel
            ->method('getTableName')
            ->willReturn($tableName);

        $metaModel
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

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
            $this->mockConnection()
        );
        $this->assertInstanceOf(Tags::class, $text);
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
            $this->mockConnection(),
            $this->getMockForAbstractClass(IFactory::class),
            $this->getMockForAbstractClass(IFilterSettingFactory::class)
        );
        $this->assertInstanceOf(MetaModelTags::class, $text);
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
            $this->mockConnection()
        );

        // Trick attribute into thinking we have a backend running and enable tree picker.
        $tags->getFieldDefinition([
            'tag_as_wizard' => 2,
            'tag_minLevel' => null,
            'tag_maxLevel' => null
        ]);

        $result = $tags->valueToWidget([['alias' => 'alias-value', 'id' => 1], ['alias' => 'alias-value2', 'id' => 2]]);

        // It should return the ids instead of the alias.
        $this->assertSame(['1','2'], $result);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function valueToWidgetProvider(): array
    {
        return [
            'null returns null' => [
                'expected'    => null,
                'value'       => null,
                'attr_config' => ['id' => uniqid('', false)],
            ],
            'empty array returns null' => [
                'expected'    => null,
                'value'       => [],
                'attr_config' => ['id' => uniqid('', false)],
            ],
            'empty string returns empty string' => [
                'expected'    => [''],
                'value'       => [['id' => '']],
                'attr_config' => ['id' => uniqid('', false)],
            ],
            'value without row value null' => [
                'expected'    => null,
                'value'       => [['foo' => 'bar']],
                'attr_config' => ['id' => uniqid('', false)],
            ],
            'numeric id is returned' => [
                'expected'    => ['10'],
                'value'       => [['id' => 10]],
                'attr_config' => ['id' => uniqid('', false)],
            ],
        ];
    }

    /**
     * Test the value to widget method.
     *
     * @param mixed $expected   The expected value.
     * @param mixed $value      The input value (native value).
     * @param array $attrConfig The attribute config.
     *
     * @return void
     *
     * @dataProvider valueToWidgetProvider
     */
    public function testValueToWidget($expected, $value, $attrConfig): void
    {
        $connection = $this->mockConnection();

        $tags = new Tags(
            $this->mockMetaModel('mm_unittest', 'en', 'en'),
            $attrConfig,
            $connection
        );

        $this->assertSame($expected, $tags->valueToWidget($value));
    }

    /**
     * Mock the database connection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
