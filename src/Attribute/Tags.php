<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2022 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_tags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Andreas Nölke <zero@brothers-project.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     Patrick Kahl <kahl.patrick@googlemail.com>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Exception;

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
        if (empty($varValue)) {
            return null;
        }

        $strColNameAlias = $this->getAliasColumn();

        $arrResult = [];
        foreach ($varValue as $arrValue) {
            if (!\is_array($arrValue) || !\array_key_exists($strColNameAlias, $arrValue)) {
                continue;
            }
            $arrResult[] = $arrValue[$strColNameAlias];
        }
        if (empty($arrResult)) {
            return null;
        }

        // We must use string keys.
        return \array_map('strval', $arrResult);
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
            ->select('t.*')
            ->from($this->getTagSource(), 't')
            ->where('t.' . $alias . ' IN (:aliases)')
            ->setParameter('aliases', $varValue, Connection::PARAM_STR_ARRAY)
            ->orderBy('t.' . $this->getSortingColumn())
            ->execute();

        $result = [];
        foreach ($values->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            // Adding the sorting from widget.
            $result[$value[$idname]]                      = $value;
            $result[$value[$idname]]['tag_value_sorting'] = \array_search($value[$alias], $varValue);
        }
        \uasort(
            $result,
            function ($value1, $value2) {
                return ($value1['tag_value_sorting'] - $value2['tag_value_sorting']);
            }
        );

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilterOptionsForDcGeneral(): array
    {
        if (!$this->isFilterOptionRetrievingPossible(null)) {
            return [];
        }

        $values = $this->getOptionStatement(null, false);

        return $this->convertOptionsList($values, $this->getAliasColumn(), $this->getValueColumn());
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        if (!$this->isFilterOptionRetrievingPossible($idList)) {
            return [];
        }

        $values = $this->getOptionStatement($idList, $usedOnly);

        return $this->convertOptionsList($values, $this->getAliasColumn(), $this->getValueColumn(), $arrCount);
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
            ->select('t.*')
            ->addSelect('r.item_id AS ' . $itemIdColumn)
            ->from($strTableName, 't')
            ->leftJoin(
                't',
                'tl_metamodel_tag_relation',
                'r',
                '(r.att_id=:attId) AND (r.value_id=t.' . $strColNameId . ')'
            )
            ->setParameter('attId', $this->get('id'))
            ->where('r.item_id IN (:itemIds)')
            ->setParameter('itemIds', $arrIds, Connection::PARAM_STR_ARRAY)
            ->orderBy('r.value_sorting');

        if ($additionalWhere = $this->getWhereColumn()) {
            $builder->andWhere($additionalWhere);
        }

        $statement = $builder->execute();

        $result = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $value) {
            if (!isset($result[$value[$itemIdColumn]])) {
                $result[$value[$itemIdColumn]] = [];
            }
            $valueTmp = $value;
            unset($valueTmp[$itemIdColumn]);
            $result[$value[$itemIdColumn]][$value[$strColNameId]] = $valueTmp;
        }

        return $result;
    }

    /**
     * Get statement of options list.
     *
     * @param array|null $idList   The value id list.
     * @param bool       $usedOnly The flag if only used values shall be returned.
     *
     * @return ResultStatement
     */
    private function getOptionStatement(?array $idList, bool $usedOnly): ResultStatement
    {
        $idColumn = $this->getIdColumn();
        $builder  = $this->getConnection()->createQueryBuilder();
        $builder
            ->setParameter('attId', $this->get('id'))
            ->from($this->getTagSource(), 't')
            ->leftJoin(
                't',
                'tl_metamodel_tag_relation',
                'r',
                '(r.att_id=:attId) AND (r.value_id=t.' . $idColumn . ')'
            )
            ->groupBy('t.' . $idColumn)
            ->orderBy('t.' . $this->getSortingColumn());

        if ($usedOnly) {
            $builder->select('COUNT(t.' . $idColumn . ') AS mm_count');
            if (!empty($idList)) {
                $builder
                    ->where('r.item_id IN (:valueIds)')
                    ->setParameter('valueIds', $idList, Connection::PARAM_STR_ARRAY);
            }
        } else {
            $builder->select('COUNT(r.value_id) AS mm_count');
            if (!empty($idList)) {
                $builder
                    ->where('t.' . $idColumn . ' IN (:valueIds)')
                    ->setParameter('valueIds', $idList, Connection::PARAM_STR_ARRAY);
            }
        }
        $builder->addSelect('t.*');

        if ($additionalWhere = $this->getWhereColumn()) {
            $builder->andWhere($additionalWhere);
        }

        return $builder->execute();
    }

    /**
     * Convert the database result into a proper result array.
     *
     * @param ResultStatement $statement   The database result statement.
     * @param string          $aliasColumn The name of the alias column to be used.
     * @param string          $valueColumn The name of the value column.
     * @param array|null      $count       The optional count array.
     *
     * @return array
     */
    private function convertOptionsList(
        ResultStatement $statement,
        string $aliasColumn,
        string $valueColumn,
        ?array &$count = null
    ): array {
        $return = [];
        while ($values = $statement->fetchAssociative()) {
            if (is_array($count)) {
                $count[$values[$aliasColumn]] = $values['mm_count'];
            }

            $return[$values[$aliasColumn]] = $values[$valueColumn];
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdForAlias(string $alias, string $language): ?string
    {
        $strAliasColumn = $this->getAliasColumn();
        $strColNameId   = $this->getIdColumn();

        return $this->getSearchedValue($strColNameId, $strAliasColumn, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasForId(string $id, string $language): ?string
    {
        $strAliasColumn = $this->getAliasColumn();
        $strColNameId   = $this->getIdColumn();

        return $this->getSearchedValue($strAliasColumn, $strColNameId, $id);
    }

    /**
     * Helper function for getting a value for a searched value.
     *
     * @param string $returnColumn The column for the return.
     * @param string $searchColumn The column for the search.
     * @param string $search       The searched value.
     *
     * @return string|null
     */
    private function getSearchedValue(string $returnColumn, string $searchColumn, string $search): ?string
    {
        if (!$this->isProperlyConfigured()) {
            return null;
        }

        $strTableNameId = $this->getTagSource();

        try {
            $builder = $this->getConnection()->createQueryBuilder()
                ->select('t.' . $returnColumn)
                ->from($strTableNameId, 't')
                ->where('t.' . $searchColumn . ' = :search')
                ->setMaxResults(1)
                ->setParameter('search', $search)
                ->execute();

            if ($builder->rowCount() == 0) {
                return null;
            }

            return (string)$builder->fetchOne();
        } catch (Exception|DbalDriverException $e) {
            return null;
        }
    }
}
