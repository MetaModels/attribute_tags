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

namespace MetaModels\Test\Attribute\Tags;

use MetaModels\Attribute\Tags\MetaModelTags;
use MetaModels\Attribute\Tags\Tags;
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
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue('mm_unittest'));

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
        $text = new Tags($this->mockMetaModel('en', 'en'));
        $this->assertInstanceOf(Tags::class, $text);
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationMetaModelSelect()
    {
        $text = new MetaModelTags($this->mockMetaModel('en', 'en'));
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
            $this->mockMetaModel('en', 'en'),
            [
                'colname' => 'tags',
                'tag_alias' => 'alias'
            ]
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
}
