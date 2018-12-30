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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\TableManipulator;
use MetaModels\IFactory;

/**
 * Attribute type factory for tags attributes.
 */
class AttributeTypeFactory implements IAttributeTypeFactory
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Table manipulator.
     *
     * @var TableManipulator
     */
    protected $tableManipulator;

    /**
     * MetaModels factory.
     *
     * @var IFactory
     */
    protected $factory;

    /**
     * Filter setting factory.
     *
     * @var IFilterSettingFactory
     */
    protected $filterSettingFactory;

    /**
     * Construct.
     *
     * @param Connection            $connection           Database connection.
     * @param TableManipulator      $tableManipulator     Table manipulator.
     * @param IFactory              $factory              MetaModels factory.
     * @param IFilterSettingFactory $filterSettingFactory Filter setting factory.
     */
    public function __construct(
        Connection $connection,
        TableManipulator $tableManipulator,
        IFactory $factory,
        IFilterSettingFactory $filterSettingFactory
    ) {
        $this->connection           = $connection;
        $this->tableManipulator     = $tableManipulator;
        $this->factory              = $factory;
        $this->filterSettingFactory = $filterSettingFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeName()
    {
        return 'tags';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeIcon()
    {
        return 'bundles/metamodelsattributetags/tags.png';
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($information, $metaModel)
    {
        if (\substr($information['tag_table'], 0, 3) === 'mm_') {
            return new MetaModelTags(
                $metaModel,
                $information,
                $this->connection,
                $this->factory,
                $this->filterSettingFactory
            );
        }

        return new Tags($metaModel, $information, $this->connection);
    }

    /**
     * Check if the type is translated.
     *
     * @return bool
     */
    public function isTranslatedType()
    {
        return false;
    }

    /**
     * Check if the type is of simple nature.
     *
     * @return bool
     */
    public function isSimpleType()
    {
        return false;
    }

    /**
     * Check if the type is of complex nature.
     *
     * @return bool
     */
    public function isComplexType()
    {
        return true;
    }
}
