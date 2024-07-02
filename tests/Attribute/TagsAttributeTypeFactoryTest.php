<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2021 The MetaModels team.
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
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\AttributeTagsBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTagsBundle\Attribute\MetaModelTags;
use MetaModels\AttributeTagsBundle\Attribute\Tags;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test the attribute factory.
 *
 * @covers \MetaModels\AttributeTagsBundle\Attribute\AttributeTypeFactory
 */
class TagsAttributeTypeFactoryTest extends TestCase
{
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
        $metaModel = $this->getMockBuilder(IMetaModel::class)->getMock();

        $metaModel
            ->expects(self::any())
            ->method('getTableName')
            ->willReturn($tableName);

        $metaModel
            ->expects(self::any())
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->expects(self::any())
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock the table manipulator.
     *
     * @param Connection $connection The database connection mock.
     *
     * @return TableManipulator|MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Test creation of an plain SQL tags attribute.
     *
     * @return void
     */
    public function testCreateTags()
    {
        $connection    = $this->mockConnection();
        $manipulator   = $this->mockTableManipulator($connection);
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);
        $factory       = new AttributeTypeFactory($connection, $manipulator, $factory, $filterFactory);

        $values = [
            'tag_table'  => 'tl_page',
            'tag_column' => 'pid',
            'tag_alias'  => 'alias',
        ];
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf(Tags::class, $attribute);

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $attribute->get($key), $key);
        }
    }

    /**
     * Test creation of an MetaModel referencing tags attribute.
     *
     * @return void
     */
    public function testCreateMetaModelTags()
    {
        $connection    = $this->mockConnection();
        $manipulator   = $this->mockTableManipulator($connection);
        $factory       = $this->getMockForAbstractClass(IFactory::class);
        $filterFactory = $this->getMockForAbstractClass(IFilterSettingFactory::class);
        $factory       = new AttributeTypeFactory($connection, $manipulator, $factory, $filterFactory);

        $values = [
            'tag_table'  => 'mm_page',
            'tag_column' => 'pid',
            'tag_alias'  => 'alias',
        ];
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf(MetaModelTags::class, $attribute);

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $attribute->get($key), $key);
        }
    }
}
