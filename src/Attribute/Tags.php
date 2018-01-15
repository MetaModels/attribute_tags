<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2017 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Andreas Nölke <zero@brothers-project.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     Patrick Kahl <kahl.patrick@googlemail.com>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2017 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Attribute;

use Doctrine\DBAL\Connection;

/**
 * This is the MetaModelAttribute class for handling tag attributes.
 */
class Tags extends AbstractTags
{
    /**
     * {@inheritDoc}
     */
    protected function checkConfiguration()
    {
        return parent::checkConfiguration()
            && $this->getConnection()->getSchemaManager()->tablesExist([$this->getTagSource()]);
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        $strColNameAlias = $this->getAliasColumn();

        $arrResult = array();
        if ($varValue) {
            foreach ($varValue as $arrValue) {
                $arrResult[] = $arrValue[$strColNameAlias];
            }
        }

        // If we have a tree picker, the value must be a comma separated string.
        if ($this->isTreePicker() && !empty($arrResult)) {
            return implode(',', $arrResult);
        }

        // We must use string keys.
        return array_map('strval', $arrResult);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When values could not be translated.
     */
    protected function getValuesFromWidget($varValue)
    {
        $alias  = $this->getAliasColumn();
        $idname = $this->get('tag_id');
        $values = $this
            ->getConnection()
            ->createQueryBuilder()
            ->select('v.*')
            ->from($this->getTagSource(), 'v')
            ->where('v.' . $alias . ' IN (:aliases)')
            ->setParameter('aliases', $varValue, Connection::PARAM_STR_ARRAY)
            ->orderBy('v.' . $this->getSortingColumn())
            ->execute();

        $result = [];
        foreach ($values->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            // Adding the sorting from widget.
            $result[$value[$idname]]                      = $value;
            $result[$value[$idname]]['tag_value_sorting'] = array_search($value[$alias], $varValue);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        if (!$this->isFilterOptionRetrievingPossible($idList)) {
            return array();
        }

        $idColumn = $this->getIdColumn();
        $builder  = $this->getConnection()->createQueryBuilder();
        $builder
            ->setParameter('attId', $this->get('id'))
            ->from($this->getTagSource(), 'v')
            ->leftJoin(
                'v',
                'tl_metamodel_tag_relation',
                'r',
                '(r.att_id=:attId) AND (r.value_id=v.' . $idColumn . ')'
            )
            ->groupBy('v.' . $idColumn)
            ->orderBy('v.' . $this->getSortingColumn());

        if ($usedOnly) {
            $builder->select('COUNT(v.' . $idColumn . ') AS mm_count');
            if (!empty($idList)) {
                $builder
                    ->where('r.item_id IN (:valueIds)')
                    ->setParameter('valueIds', $idList, Connection::PARAM_STR_ARRAY);
            }
        } else {
            $builder->select('COUNT(r.value_id) AS mm_count');
            if (!empty($idList)) {
                $builder
                    ->where('v.' . $idColumn . ' IN (:valueIds)')
                    ->setParameter('valueIds', $idList, Connection::PARAM_STR_ARRAY);
            }
        }
        $builder->addSelect('v.*');

        if ($additionalWhere = $this->getWhereColumn()) {
            $builder->andWhere($additionalWhere);
        }

        $aliasColumn = $this->getAliasColumn();
        $valueColumn = $this->getValueColumn();
        $result      = [];
        foreach ($builder->execute()->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            if ($arrCount !== null) {
                $arrCount[$value[$aliasColumn]] = $value['mm_count'];
            }
            $result[$value[$aliasColumn]] = $value[$valueColumn];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        if (!$this->isProperlyConfigured()) {
            return [];
        }

        $strTableName = $this->getTagSource();
        $strColNameId = $this->getIdColumn();
        $itemIdColumn = $this->getMetaModel()->getTableName() . '_id';

        $builder = $this->getConnection()->createQueryBuilder()
            ->select('v.*')
            ->addSelect('r.item_id AS ' . $itemIdColumn)
            ->from($strTableName, 'v')
            ->leftJoin('v', 'tl_metamodel_tag_relation', 'r',
                '(r.att_id=:attId) AND (r.value_id=v.' . $strColNameId . ')')
            ->setParameter('attId', $this->get('id'))
            ->where('r.item_id IN (:itemIds)')
            ->setParameter('itemIds', $arrIds, Connection::PARAM_STR_ARRAY)
            ->orderBy('r.value_sorting')
            ->execute();

        $result  = [];
        foreach ($builder->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            if (!isset($result[$value[$itemIdColumn]])) {
                $result[$value[$itemIdColumn]] = [];
            }
            $idValue = $value;
            unset($value[$itemIdColumn]);
            $result[$idValue][$value[$strColNameId]] = $value;
        }

        return $result;
    }
}
