<?php

/**
 * This file is part of MetaModels/attribute_tags.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_tags
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Attribute;

use Contao\System;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\BaseComplex;
use MetaModels\AttributeTagsBundle\FilterRule\FilterRuleTags;
use MetaModels\IMetaModel;
use MetaModels\Render\Template;

/**
 * This is the MetaModelAttribute class for handling tag attributes.
 */
abstract class AbstractTags extends BaseComplex
{
    /**
     * The widget mode to use.
     *
     * @var int
     */
    private $widgetMode;

    /**
     * Local cached flag if the attribute has been properly configured.
     *
     * @var bool
     */
    private $isProperlyConfigured;

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel $objMetaModel The MetaModel instance this attribute belongs to.
     * @param array      $arrData      The information array, for attribute information, refer to documentation of
     *                                 table tl_metamodel_attribute and documentation of the certain attribute
     *                                 classes for information what values are understood.
     * @param Connection $connection   The database connection.
     */
    public function __construct(IMetaModel $objMetaModel, array $arrData = [], Connection $connection = null)
    {
        parent::__construct($objMetaModel, $arrData);

        if (null === $connection) {
            // @codingStandardsIgnoreStart
            @\trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
        }

        $this->connection = $connection;
    }

    /**
     * Retrieve connection.
     *
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Retrieve the database instance.
     *
     * @return \Database
     *
     * @deprecated Use getConnection()
     */
    protected function getDatabase()
    {
        return $this->getMetaModel()->getServiceContainer()->getDatabase();
    }

    /**
     * Determine if this widget a checkbox wizard.
     *
     * @return bool
     */
    protected function isCheckboxWizard()
    {
        return $this->widgetMode == 1;
    }

    /**
     * Determine if we want to use tree selection.
     *
     * @return bool
     */
    protected function isTreePicker()
    {
        return $this->widgetMode == 2;
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getTagSource()
    {
        return $this->get('tag_table');
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getIdColumn()
    {
        return $this->get('tag_id') ?: 'id';
    }

    /**
     * Determine the correct sort direction to use.
     *
     * @return string
     */
    protected function getSortDirection()
    {
        return $this->get('tag_sort');
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getSortingColumn()
    {
        return $this->get('tag_sorting') ?: $this->getIdColumn();
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getValueColumn()
    {
        return $this->get('tag_column');
    }

    /**
     * Determine the correct alias column to use.
     *
     * @return string
     */
    protected function getAliasColumn()
    {
        $strColNameAlias = $this->get('tag_alias');
        if ($this->isTreePicker() || !$strColNameAlias) {
            $strColNameAlias = $this->getIdColumn();
        }
        return $strColNameAlias;
    }

    /**
     * Determine the correct alias column to use.
     *
     * @return string
     *
     * @deprecated Use the getAliasColumn function instead.
     */
    protected function getAliasCol()
    {
        return $this->getAliasColumn();
    }

    /**
     * Determine the correct where column to use.
     *
     * @return string
     */
    protected function getWhereColumn()
    {
        return $this->get('tag_where') ? \html_entity_decode($this->get('tag_where')) : null;
    }

    /**
     * Return the name of the table with the references. (m:n).
     *
     * @return string
     *
     * @deprecated This was non functional anyway as we had many hardcoded references to 'tl_metamodel_tag_relation'.
     */
    protected function getReferenceTable()
    {
        return 'tl_metamodel_tag_relation';
    }

    /**
     * Ensure the attribute has been configured correctly.
     *
     * @return bool
     */
    protected function isProperlyConfigured()
    {
        if (isset($this->isProperlyConfigured)) {
            return $this->isProperlyConfigured;
        }

        return $this->isProperlyConfigured = $this->checkConfiguration();
    }

    /**
     * Check the configuration of the attribute.
     *
     * @return bool
     */
    protected function checkConfiguration()
    {
        return $this->getTagSource()
            && $this->getValueColumn()
            && $this->getAliasColumn()
            && $this->getIdColumn()
            && $this->getSortingColumn();
    }

    /**
     * Test that we can create the filter options.
     *
     * @param string[]|null $idList The ids of items that the values shall be fetched from
     *                              (If empty or null, all items).
     *
     * @return bool
     */
    protected function isFilterOptionRetrievingPossible($idList)
    {
        return $this->isProperlyConfigured() && (($idList === null) || !empty($idList));
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings)
    {
        parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);
        $objTemplate->alias = $this->getAliasColumn();
        $objTemplate->value = $this->getValueColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(
            parent::getAttributeSettingNames(),
            [
                'tag_table',
                'tag_column',
                'tag_id',
                'tag_alias',
                'tag_where',
                'tag_filter',
                'tag_filterparams',
                'tag_sort',
                'tag_sorting',
                'tag_as_wizard',
                'tag_minLevel',
                'tag_maxLevel',
                'mandatory',
                'submitOnChange',
                'filterable',
                'searchable',
            ]
        );
    }

    /**
     * Get the picker input type.
     *
     * @return string
     */
    private function getPickerType()
    {
        $sourceName = $this->getTagSource();
        if (!\in_array($sourceName, ['tl_page', 'tl_files'])) {
            return 'DcGeneralTreePicker';
        }

        return $sourceName === 'tl_page' ? 'pageTree' : 'fileTree';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = [])
    {
        $arrFieldDef      = parent::getFieldDefinition($arrOverrides);
        $this->widgetMode = $arrOverrides['tag_as_wizard'];
        if ($this->isTreePicker()) {
            $arrFieldDef['inputType']                   = $this->getPickerType();
            $arrFieldDef['eval']['sourceName']          = $this->getTagSource();
            $arrFieldDef['eval']['fieldType']           = 'checkbox';
            $arrFieldDef['eval']['idProperty']          = $this->getAliasColumn();
            $arrFieldDef['eval']['orderName']           = $this->getColName();
            $arrFieldDef['eval']['orderField']          = $this->getColName();
            $arrFieldDef['eval']['minLevel']            = $arrOverrides['tag_minLevel'];
            $arrFieldDef['eval']['maxLevel']            = $arrOverrides['tag_maxLevel'];
            $arrFieldDef['eval']['pickerOrderProperty'] = $this->getSortingColumn();
            $arrFieldDef['eval']['pickerSortDirection'] = \strtoupper($this->getSortDirection());
        } elseif ($this->widgetMode == 1) {
            // If tag as wizard is true, change the input type.
            $arrFieldDef['inputType'] = 'checkboxWizard';
        } elseif ($this->widgetMode == 3) {
            $arrFieldDef['inputType']      = 'select';
            $arrFieldDef['eval']['chosen'] = true;
        } else {
            $arrFieldDef['inputType'] = 'checkbox';
        }

        $arrFieldDef['eval']['includeBlankOption'] = true;
        $arrFieldDef['eval']['multiple']           = true;
        $arrFieldDef['empty_value']                = null;

        return $arrFieldDef;
    }

    /**
     * Translate the values from the widget.
     *
     * @param array $varValue The values.
     *
     * @return array
     */
    abstract protected function getValuesFromWidget($varValue);

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When the passed value is not an array or null.
     */
    public function widgetToValue($varValue, $itemId)
    {
        if (null === $varValue || [] === $varValue) {
            return null;
        }

        if ((!\is_array($varValue))) {
            throw new \InvalidArgumentException('Value must be an array or null');
        }

        return $this->getValuesFromWidget($varValue);
    }

    /**
     * {@inheritdoc}
     */
    public function searchFor($strPattern)
    {
        $objFilterRule = new FilterRuleTags($this, $strPattern);
        return $objFilterRule->getMatchingIds();
    }

    /**
     * Update the tag ids for a given item.
     *
     * @param int   $itemId       The item for which data shall be set for.
     * @param array $tags         The tag ids that shall be set for the item.
     * @param array $thisExisting The existing item ids.
     *
     * @return array
     */
    private function setDataForItem($itemId, $tags, $thisExisting)
    {
        if ($tags === null) {
            $tagIds = [];
        } else {
            $tagIds = \array_keys($tags);
        }

        // First pass, delete all not mentioned anymore.
        $valuesToRemove = \array_diff($thisExisting, $tagIds);
        if ($valuesToRemove) {
            $this->connection
                ->createQueryBuilder()
                ->delete('tl_metamodel_tag_relation')
                ->where('att_id=:attId')
                ->andWhere('item_id=:itemId')
                ->andWhere('value_id IN (:valueIds)')
                ->setParameter('attId', $this->get('id'))
                ->setParameter('itemId', $itemId)
                ->setParameter('valueIds', $valuesToRemove, Connection::PARAM_STR_ARRAY)
                ->execute();
        }

        // Second pass, add all new values in a row.
        $valuesToAdd  = \array_diff($tagIds, $thisExisting);
        $insertValues = [];
        if ($valuesToAdd) {
            foreach ($valuesToAdd as $valueId) {
                $insertValues[] = [
                    'attId'   => $this->get('id'),
                    'itemId'  => $itemId,
                    'sorting' => (int) $tags[$valueId]['tag_value_sorting'],
                    'valueId' => $valueId
                ];
            }
        }

        // Third pass, update all sorting values.
        $valuesToUpdate = \array_diff($tagIds, $valuesToAdd);
        if ($valuesToUpdate) {
            $query = $this->connection
                ->createQueryBuilder()
                ->update('tl_metamodel_tag_relation')
                ->set('value_sorting', ':sorting')
                ->where('att_id=:attId')
                ->andWhere('item_id=:itemId')
                ->andWhere('value_id=:valueId')
                ->setParameter('attId', $this->get('id'))
                ->setParameter('itemId', $itemId);

            foreach ($valuesToUpdate as $valueId) {
                if (!array_key_exists('tag_value_sorting', $tags[$valueId])) {
                    continue;
                }
                $query
                    ->setParameter('sorting', (int) $tags[$valueId]['tag_value_sorting'])
                    ->setParameter('valueId', $valueId)
                    ->execute();
            }
        }

        return $insertValues;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFor($arrValues)
    {
        if (!$this->isProperlyConfigured()) {
            return;
        }

        $itemIds = \array_keys($arrValues);
        \sort($itemIds);

        // Load all existing tags for all items to be updated, keep the ordering to item Id
        // so we can benefit from the batch deletion and insert algorithm.
        $existingTagQuery = $this
            ->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('tl_metamodel_tag_relation')
            ->where('att_id=:attId')->setParameter('attId', $this->get('id'))
            ->andWhere('item_id IN (:valueIds)')->setParameter('valueIds', $itemIds, Connection::PARAM_STR_ARRAY)
            ->orderBy('item_id', 'ASC')
            ->execute();

        $existingTagIds = [];
        while ($tag = $existingTagQuery->fetch(\PDO::FETCH_ASSOC)) {
            $existingTagIds[$tag['item_id']][] = $tag['value_id'];
        }

        // Now loop over all items and update the values for them.
        // NOTE: we can not loop over the original array, as the item ids are not neccessarily
        // sorted ascending by item id.
        $insertValues = [];
        foreach ($itemIds as $itemId) {
            $insertValues[] = $this->setDataForItem($itemId, $arrValues[$itemId], ($existingTagIds[$itemId] ?? []));
        }
        $insertValues = \array_merge(...$insertValues);

        if ([] !== $insertValues) {
            $builder = $this
                ->connection
                ->createQueryBuilder()
                    ->insert('tl_metamodel_tag_relation')
                    ->values([
                        'att_id'        => ':attId',
                        'item_id'       => ':itemId',
                        'value_sorting' => ':sorting',
                        'value_id'      => ':valueId',
                    ]);

            foreach ($insertValues as $value) {
                $builder
                    ->setParameter('attId', $value['attId'])
                    ->setParameter('itemId', $value['itemId'])
                    ->setParameter('sorting', $value['sorting'])
                    ->setParameter('valueId', $value['valueId'])
                    ->execute();
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException When an invalid id array has been passed.
     */
    public function unsetDataFor($arrIds)
    {
        if (!\is_array($arrIds)) {
            throw new \RuntimeException(
                __METHOD__ . '() invalid parameter given! Array of ids is needed.',
                1
            );
        }
        if (empty($arrIds)) {
            return;
        }

        $this->connection
            ->createQueryBuilder()
            ->delete('tl_metamodel_tag_relation')
            ->where('att_id=:attId')
            ->andWhere('item_id IN (:itemIds)')
            ->setParameter('attId', $this->get('id'))
            ->setParameter('itemIds', $arrIds, Connection::PARAM_STR_ARRAY)
            ->execute();
    }
}
