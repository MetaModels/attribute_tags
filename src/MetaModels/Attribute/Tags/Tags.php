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
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Andreas Nölke <zero@brothers-project.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     Patrick Kahl <kahl.patrick@googlemail.com>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later-or-later
 * @filesource
 */

namespace MetaModels\Attribute\Tags;

use Contao\Database\Result;

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
            && $this->getDatabase()->tableExists($this->getTagSource());
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        $strColNameAlias = $this->getAliasColumn();

        $arrResult = [];
        if ($varValue) {
            foreach ($varValue as $arrValue) {
                $arrResult[] = $arrValue[$strColNameAlias];
            }
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
        $arrParams = [];
        foreach ($varValue as $strValue) {
            $arrParams[] = $strValue;
        }

        $objValue = $this
            ->getDatabase()
            ->prepare(
                sprintf(
                    'SELECT %1$s.*
                    FROM %1$s
                    WHERE %2$s IN (%3$s)
                    ORDER BY %4$s',
                    $this->getTagSource(),
                    $this->getAliasColumn(),
                    implode(',', array_fill(0, count($arrParams), '?')),
                    $this->getSortingColumn()
                )
            )
            ->execute($arrParams);

        $strColNameId = $this->get('tag_id');
        $arrResult    = [];

        while ($objValue->next()) {
            // Adding the sorting from widget.
            $strAlias                                                 = $this->getAliasColumn();
            $arrResult[$objValue->$strColNameId]                      = $objValue->row();
            $arrResult[$objValue->$strColNameId]['tag_value_sorting'] = array_search($objValue->$strAlias, $varValue);
        }

        return $arrResult;
    }

    /**
     * Retrieve the filter options for items with the given ids.
     *
     * @param array $arrIds   The ids for which the options shall be retrieved.
     *
     * @param bool  $usedOnly Flag if only used options shall be retrieved.
     *
     * @return Result
     */
    protected function retrieveFilterOptionsForIds($arrIds, $usedOnly)
    {
        if ($usedOnly) {
            $sqlQuery = '
                    SELECT COUNT(%1$s.%2$s) as mm_count, %1$s.*
                    FROM %1$s
                    LEFT JOIN tl_metamodel_tag_relation ON (
                        (tl_metamodel_tag_relation.att_id=?)
                        AND (tl_metamodel_tag_relation.value_id=%1$s.%2$s)
                    )
                    WHERE (tl_metamodel_tag_relation.item_id IN (%3$s)%5$s)
                    GROUP BY %1$s.%2$s
                    ORDER BY %1$s.%4$s
                ';
        } else {
            $sqlQuery = '
                    SELECT COUNT(rel.value_id) as mm_count, %1$s.*
                    FROM %1$s
                    LEFT JOIN tl_metamodel_tag_relation as rel ON (
                        (rel.att_id=?) AND (rel.value_id=%1$s.%2$s)
                    )
                    WHERE %1$s.%2$s IN (%3$s)%5$s
                    GROUP BY %1$s.%2$s
                    ORDER BY %1$s.%4$s';
        }

        return $this
            ->getDatabase()
            ->prepare(
                sprintf(
                    $sqlQuery,
                    // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                    $this->getTagSource(),                                                    // 1
                    $this->getIdColumn(),                                                     // 2
                    implode(',', $arrIds),                                                    // 3
                    $this->getSortingColumn(),                                                // 4
                    ($this->getWhereColumn() ? ' AND (' . $this->getWhereColumn() . ')' : '') // 5
                // @codingStandardsIgnoreEnd
                )
            )
            ->execute($this->get('id'));
    }

    /**
     * Retrieve the filter options for items with the given ids.
     *
     * @param bool $usedOnly Flag if only used options shall be retrieved.
     *
     * @return Result
     */
    protected function retrieveFilterOptionsWithoutIds($usedOnly)
    {
        if ($usedOnly) {
            $sqlQuery = '
                    SELECT COUNT(%1$s.%3$s) as mm_count, %1$s.*
                    FROM %1$s
                    INNER JOIN tl_metamodel_tag_relation as rel
                    ON (
                        (rel.att_id="%4$s") AND (rel.value_id=%1$s.%3$s)
                    )
                    WHERE rel.att_id=%4$s'
                . ($this->getWhereColumn() ? ' AND %5$s' : '') . '
                    GROUP BY %1$s.%3$s
                    ORDER BY %1$s.%2$s';
        } else {
            $sqlQuery = '
                    SELECT COUNT(rel.value_id) as mm_count, %1$s.*
                    FROM %1$s
                    LEFT JOIN tl_metamodel_tag_relation as rel
                    ON (
                        (rel.att_id="%4$s") AND (rel.value_id=%1$s.%3$s)
                    )'
                . ($this->getWhereColumn() ? ' WHERE %5$s' : '') . '
                    GROUP BY %1$s.%3$s
                    ORDER BY %1$s.%2$s';
        }

        return $this
            ->getDatabase()
            ->prepare(
                sprintf(
                    $sqlQuery,
                    // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                    $this->getTagSource(),       // 1
                    $this->getSortingColumn(),   // 2
                    $this->getIdColumn(),        // 3
                    $this->get('id'),            // 4
                    $this->getWhereColumn()      // 5
                // @codingStandardsIgnoreEnd
                )
            )
            ->execute();
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

        if ($idList) {
            $objValue = $this->retrieveFilterOptionsForIds($idList, $usedOnly);
        } else {
            $objValue = $this->retrieveFilterOptionsWithoutIds($usedOnly);
        }

        $result      = [];
        $valueColumn = $this->getValueColumn();
        $aliasColumn = $this->getAliasColumn();
        while ($objValue->next()) {
            if ($arrCount !== null) {
                /** @noinspection PhpUndefinedFieldInspection */
                $arrCount[$objValue->$aliasColumn] = $objValue->mm_count;
            }

            $result[$objValue->$aliasColumn] = $objValue->$valueColumn;
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
        $objDB        = $this->getDatabase();
        $arrReturn    = [];
        $itemIdColumn = $this->getMetaModel()->getTableName() . '_id';

        $objValue = $objDB
            ->prepare(
                sprintf(
                    'SELECT %1$s.*, tl_metamodel_tag_relation.item_id AS %2$s
                    FROM %1$s
                    LEFT JOIN tl_metamodel_tag_relation ON (
                        (tl_metamodel_tag_relation.att_id=?)
                        AND (tl_metamodel_tag_relation.value_id=%1$s.%3$s)
                    )
                    WHERE tl_metamodel_tag_relation.item_id IN (%4$s)
                    ORDER BY tl_metamodel_tag_relation.value_sorting',
                    // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                    $strTableName,            // 1
                    $itemIdColumn,            // 2
                    $strColNameId,            // 3
                    implode(',', $arrIds)     // 4
                // @codingStandardsIgnoreEnd
                )
            )
            ->execute($this->get('id'));

        while ($objValue->next()) {
            if (!isset($arrReturn[$objValue->$itemIdColumn])) {
                $arrReturn[$objValue->$itemIdColumn] = [];
            }
            $arrData = $objValue->row();
            unset($arrData[$itemIdColumn]);
            $arrReturn[$objValue->$itemIdColumn][$objValue->$strColNameId] = $arrData;
        }

        return $arrReturn;
    }
}
