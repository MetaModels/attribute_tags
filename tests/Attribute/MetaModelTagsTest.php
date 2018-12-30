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
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use MetaModels\Attribute\IAttribute;
use MetaModels\AttributeTagsBundle\Attribute\MetaModelTags;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class MetaModelTags.
 */
class MetaModelTagsTest extends TestCase
{
    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationMetaModelSelect()
    {
        $connection    = $this->mockConnection();
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);

        $text = new MetaModelTags(
            $this->mockMetaModel('en', 'en'),
            ['id' => uniqid('', false)],
            $connection,
            $factory,
            $filterFactory
        );

        $this->assertInstanceOf('MetaModels\AttributeTagsBundle\Attribute\MetaModelTags', $text);
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
        $connection    = $this->mockConnection();
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);

        $tags = new MetaModelTags(
            $this->mockMetaModel('en', 'en'),
            $attrConfig,
            $connection,
            $factory,
            $filterFactory
        );

        $this->assertSame($expected, $tags->valueToWidget($value));
    }

    /**
     * Test the widget to value method.
     *
     * @return void
     */
    public function testWidgetToValueForNull(): void
    {
        $connection    = $this->mockConnection();
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);
        $tags          = new MetaModelTags(
            $this->mockMetaModel('en', 'en'),
            [
                'id'        => uniqid('', false),
                'tag_table' => 'mm_test_tags',
            ],
            $connection,
            $factory,
            $filterFactory
        );

        $factory->expects($this->never())->method('getMetaModel');

        $this->assertNull($tags->widgetToValue(null, 23));
    }

    /**
     * Test the widget to value method.
     *
     * @return void
     */
    public function testWidgetToValueForNonNullWithId(): void
    {
        $connection    = $this->mockConnection();
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);
        $tags          = $this
            ->getMockBuilder(MetaModelTags::class)
            ->setConstructorArgs([
                $this->mockMetaModel('en', 'en'),
                [
                    'id'        => uniqid('', false),
                    'tag_table' => 'mm_test_tags',
                ],
                $connection,
                $factory,
                $filterFactory
            ])
            ->setMethods(['getValuesById'])
            ->getMock();

        $tags->expects($this->once())->method('getValuesById')->with([10], ['id', 'id', ''])->willReturn([10 => [
            'id'      => 10,
            'pid'     => 0,
            'sorting' => 1,
            'tstamp'  => 343094400,
        ]]);

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN)
            ->willReturn([10]);

        $builder = $this
            ->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->once())->method('select')->with('id')->willReturn($builder);
        $builder->expects($this->once())->method('from')->with('mm_test_tags')->willReturn($builder);
        $builder->expects($this->once())->method('where')->with('id IN (:values)')->willReturn($builder);
        $builder
            ->expects($this->once())
            ->method('setParameter')
            ->with('values', [10], Connection::PARAM_STR_ARRAY)
            ->willReturn($builder);
        $builder->expects($this->once())->method('execute')->willReturn($statement);

        $connection->expects($this->once())->method('createQueryBuilder')->willReturn($builder);

        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $factory
            ->expects($this->once())
            ->method('getMetaModel')
            ->with('mm_test_tags')
            ->willReturn($metaModel);

        $this->assertSame([10 => [
            'id'      => 10,
            'pid'     => 0,
            'sorting' => 1,
            'tstamp'  => 343094400,
        ]], $tags->widgetToValue([10], 23));
    }

    /**
     * Test the widget to value method.
     *
     * @return void
     */
    public function testWidgetToValueForNonNullWithAttribute(): void
    {
        $connection    = $this->mockConnection();
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);
        $tags          = $this
            ->getMockBuilder(MetaModelTags::class)
            ->setConstructorArgs([
                $this->mockMetaModel('en', 'en'),
                [
                    'tag_table' => 'mm_test_tags',
                    'tag_id'    => 'attribute',
                ],
                $connection,
                $factory,
                $filterFactory
            ])
            ->setMethods(['getValuesById'])
            ->getMock();

        $tags->expects($this->once())->method('getValuesById')->willReturn([0 => [
            'id'      => 10,
            'pid'     => 0,
            'sorting' => 1,
            'tstamp'  => 343094400,
        ]]);

        $attribute = $this->getMockForAbstractClass(IAttribute::class);
        $attribute->expects($this->once())->method('searchFor')->with(10)->willReturn([10]);

        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);
        $metaModel->expects($this->once())->method('hasAttribute')->with('attribute')->willReturn(true);
        $metaModel->expects($this->once())->method('getAttribute')->with('attribute')->willReturn($attribute);

        $factory
            ->expects($this->once())
            ->method('getMetaModel')
            ->with('mm_test_tags')
            ->willReturn($metaModel);

        $this->assertSame([[
            'id'      => 10,
            'pid'     => 0,
            'sorting' => 1,
            'tstamp'  => 343094400,
        ]], $tags->widgetToValue([10], 23));
    }

    /**
     * Mock a MetaModel.
     *
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    private function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass('MetaModels\IMetaModel');

        $metaModel
            ->method('getTableName')
            ->willReturn('mm_unittest');

        $metaModel
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
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
