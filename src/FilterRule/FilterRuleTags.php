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
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Martin Treml <github@r2pi.net>
 * @author     Patrick Heller <ph@wacg.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\FilterRule;

use Contao\System;
use Doctrine\DBAL\Connection;
use MetaModels\AttributeTagsBundle\Attribute\AbstractTags;
use MetaModels\AttributeTagsBundle\Attribute\MetaModelTags;
use MetaModels\AttributeTagsBundle\Attribute\Tags;
use MetaModels\Filter\FilterRule;
use MetaModels\IMetaModel;

/**
 * This is the MetaModelFilterRule class for handling select fields.
 */
class FilterRuleTags extends FilterRule
{
    /**
     * The attribute to filter.
     *
     * @var AbstractTags|Tags|MetaModelTags
     */
    protected $objAttribute;

    /**
     * The filter value.
     *
     * @var string
     */
    protected $value;

    /**
     * The MetaModel we are referencing on.
     *
     * @var IMetaModel
     */
    protected $objSelectMetaModel;

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Check if the reference is a MetaModel.
     *
     * @return bool
     */
    protected function isMetaModel()
    {
        return $this->objAttribute instanceof MetaModelTags;
    }

    /**
     * Retrieve the linked MetaModel instance.
     *
     * @return IMetaModel
     */
    protected function getTagMetaModel()
    {
        if (empty($this->objSelectMetaModel)) {
            $this->objSelectMetaModel = $this->objAttribute->getTagMetaModel();
        }

        return $this->objSelectMetaModel;
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(AbstractTags $objAttribute, $strValue, Connection $connection = null)
    {
        parent::__construct();

        $this->objAttribute = $objAttribute;
        $this->value        = $strValue;

        if (null === $connection) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
        }

        $this->connection = $connection;
    }

    /**
     * Ensure the value is either a proper id or array od ids - converts aliases to ids.
     *
     * @return array
     */
    public function sanitizeValue()
    {
        $strColNameId    = $this->objAttribute->get('tag_id') ?: 'id';
        $strColNameAlias = $this->objAttribute->get('tag_alias');
        $arrValues       = \is_array($this->value) ? $this->value : \explode(',', $this->value);

        if (!$this->isMetaModel()) {
            if ($strColNameAlias) {
                $builder = $this->connection->createQueryBuilder()
                    ->select('t.' . $strColNameId)
                    ->from($this->objAttribute->get('tag_table'), 't');
                foreach ($arrValues as $index => $value) {
                    $builder
                        ->orWhere('t.' . $strColNameAlias . ' LIKE :value_' . $index)
                        ->setParameter('value_' . $index, $value);
                }
                $arrValues = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                $arrValues = \array_map('intval', $arrValues);
            }
        } else {
            if ($strColNameAlias == 'id') {
                return $this->flatten($arrValues);
            }
            $attribute = $this->getTagMetaModel()->getAttribute($strColNameAlias);
            if (null === $attribute) {
                return $this->flatten($arrValues);
            }

            $values = [];
            foreach ($arrValues as $value) {
                $values[] = \array_values($attribute->searchFor($value));
            }

            $arrValues = $this->flatten($values);
        }

        return $arrValues;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingIds()
    {
        $arrValues = $this->sanitizeValue();

        // Get out when no values are available.
        if (!$arrValues) {
            return [];
        }

        return $this->connection
            ->createQueryBuilder()
            ->select('t.item_id')
            ->from('tl_metamodel_tag_relation', 't')
            ->where('t.att_id=:att_id')
            ->setParameter('att_id', $this->objAttribute->get('id'))
            ->andWhere('t.value_id IN (:values)')
            ->setParameter('values', $arrValues, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Flatten the value id array.
     *
     * @param array $array The array which should be flattened.
     *
     * @return array
     */
    public function flatten(array $array)
    {
        $return = [];
        \array_walk_recursive($array, function ($item) use (&$return) {
            $return[] = $item;
        });
        return $return;
    }
}
