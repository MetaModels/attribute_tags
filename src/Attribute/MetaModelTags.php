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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\Attribute;

use Contao\System;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttribute;
use MetaModels\Attribute\ITranslated;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SearchAttribute;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\Helper\LocaleUtil;
use MetaModels\IFactory;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\IMetaModel;
use MetaModels\Items;
use MetaModels\ITranslatedMetaModel;

/**
 * This is the MetaModelAttribute class for handling tag attributes.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetaModelTags extends AbstractTags
{
    /**
     * The key in the result array where the RAW values shall be stored.
     */
    private const TAGS_RAW = '__TAGS_RAW__';

    /**
     * The MetaModel we are referencing on.
     *
     * @var IMetaModel|null
     */
    private IMetaModel|null $objSelectMetaModel = null;

    /**
     * The factory.
     *
     * @var IFactory
     */
    private IFactory $factory;

    /**
     * Filter setting factory.
     *
     * @var IFilterSettingFactory
     */
    private IFilterSettingFactory $filterSettingFactory;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel                 $objMetaModel         The MetaModel instance this attribute belongs to.
     * @param array                      $arrData              The information array, for attribute information, refer
     *                                                         to documentation of table tl_metamodel_attribute and
     *                                                         documentation of the certain attribute classes for
     *                                                         information what values are understood.
     * @param Connection|null            $connection           The database connection.
     * @param IFactory|null              $factory              MetaModel factory.
     * @param IFilterSettingFactory|null $filterSettingFactory Filter setting factory.
     */
    public function __construct(
        IMetaModel $objMetaModel,
        array $arrData = [],
        Connection $connection = null,
        IFactory $factory = null,
        IFilterSettingFactory $filterSettingFactory = null
    ) {
        parent::__construct($objMetaModel, $arrData, $connection);

        if (null === $factory) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Factory is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $factory = System::getContainer()->get('metamodels.factory');
            assert($factory instanceof IFactory);
        }
        $this->factory              = $factory;

        if (null === $filterSettingFactory) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'FilterSettingFactory is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $filterSettingFactory = System::getContainer()->get('metamodels.filter_setting_factory');
            assert($filterSettingFactory instanceof IFilterSettingFactory);
        }
        $this->filterSettingFactory = $filterSettingFactory;
    }

    /**
     * {@inheritDoc}
     */
    protected function checkConfiguration()
    {
        return parent::checkConfiguration();
    }

    /**
     * Retrieve the linked MetaModel instance.
     *
     * @return IMetaModel
     */
    public function getTagMetaModel()
    {
        if (null === $this->objSelectMetaModel) {
            $this->objSelectMetaModel = $this->factory->getMetaModel($this->getTagSource());
            assert($this->objSelectMetaModel instanceof IMetaModel);
        }

        return $this->objSelectMetaModel;
    }

    /**
     * Retrieve the values with the given ids.
     *
     * @param list<string> $valueIds The ids of the values to retrieve.
     * @param list<string> $attrOnly The attribute names to fetch or empty to fetch all.
     *
     * @return array
     */
    protected function getValuesById($valueIds, $attrOnly = [])
    {
        if (empty($valueIds)) {
            return [];
        }

        $recursionKey = $this->getMetaModel()->getTableName();
        $metaModel    = $this->getTagMetaModel();
        $filter       = $metaModel->getEmptyFilter();
        $this->buildFilterRulesForFilterSetting($filter);
        $filter->addFilterRule(new StaticIdList($valueIds));

        // Prevent recursion.
        static $tables = [];
        if (isset($tables[$recursionKey])) {
            return [];
        }
        $tables[$recursionKey] = $recursionKey;

        $items =
            $metaModel->findByFilter($filter, $this->getSortingColumn(), 0, 0, $this->getSortDirection(), $attrOnly);
        unset($tables[$recursionKey]);

        // Sort items manually for checkbox wizard.
        if ($this->isCheckboxWizard() || $this->isTreePicker()) {
            // Remove deleted referenced items and flip.
            $orderIds = \array_flip(\array_filter($valueIds));

            foreach ($items as $item) {
                $orderIds[$item->get('id')] = $item;
            }
            $items = new Items(
                \array_values(
                    \array_filter(
                        $orderIds,
                        static function ($itemOrId) {
                            return $itemOrId instanceof IItem;
                        }
                    )
                )
            );
        }

        $values = [];
        $count  = 0;
        foreach ($items as $item) {
            $valueId    = $item->get('id');
            $parsedItem = $item->parseValue();

            $values[$valueId] = \array_merge(
                [
                    self::TAGS_RAW      => $parsedItem['raw'],
                    'tag_value_sorting' => $count++
                ],
                $parsedItem['text']
            );
        }

        return $values;
    }

    /**
     * Sort a list of value ids by the option column (non-existent ids will get moved to the end).
     *
     * @param list<string> $idList The value id list to sort.
     *
     * @return array
     */
    private function sortIdsBySortingColumn(array $idList): array
    {
        // Only one item, what shall we sort here then?
        if (1 === \count($idList)) {
            return $idList;
        }

        static $sorting;
        if (isset($sorting[$cacheKey = $this->get('id') . \implode(',', $idList)])) {
            return $sorting[$cacheKey];
        }

        // Now sort the values according to our sorting column.
        $filter = $this
            ->getTagMetaModel()
            ->getEmptyFilter()
            ->addFilterRule(new StaticIdList($idList));

        $itemIds = $this->getTagMetaModel()->getIdsFromFilter(
            $filter,
            $this->getSortingColumn(),
            0,
            0,
            $this->getSortDirection()
        );

        // Manual sorting of items for checkbox wizard.
        if ($this->isCheckboxWizard() || $this->isTreePicker()) {
            // Keep order from input array, and add non-existent ids to the end.
            return $sorting[$cacheKey] = \array_merge(
            // Keep order from input array...
                \array_intersect($idList, $itemIds),
                // ... and add non existent ids to the end.
                \array_diff($idList, $itemIds)
            );
        }
        // Use new order and add non-existent ids to the end.
        return $sorting[$cacheKey] = \array_merge($itemIds, \array_diff($idList, $itemIds));
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (empty($varValue)) {
            return null;
        }

        $alias = $this->convertValuesToIds($varValue);
        // Sort the values now.
        $sortedIds = $this->sortIdsBySortingColumn(\array_keys($varValue));
        $result    = [];
        foreach ($sortedIds as $id) {
            if (!\array_key_exists($id, $alias)) {
                continue;
            }
            $result[] = $alias[$id];
        }

        if (empty($result)) {
            return null;
        }

        // We must use string keys.
        return \array_map('strval', $result);
    }

    /**
     * Convert the passed values to a value id list.
     *
     * @param array $varValue The values to convert.
     *
     * @return array
     */
    private function convertValuesToIds(array $varValue): array
    {
        $aliasColumn = $this->getAliasColumn();
        $alias       = [];
        foreach ($varValue as $valueId => $value) {
            if (!\is_array($value)) {
                continue;
            }
            if (\array_key_exists($aliasColumn, $value)) {
                $alias[$valueId] = $value[$aliasColumn];
                continue;
            }
            if (\array_key_exists(self::TAGS_RAW, $value) && \array_key_exists($aliasColumn, $value[self::TAGS_RAW])) {
                $alias[$valueId] = $value[self::TAGS_RAW][$aliasColumn];
            }
        }

        return $alias;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When values could not be translated.
     */
    protected function getValuesFromWidget($varValue)
    {
        $model    = $this->getTagMetaModel();
        $alias    = $this->getAliasColumn();
        $valueIds = [];

        if ($model->hasAttribute($alias)) {
            $attribute = $model->getAttribute($alias);
            assert($attribute instanceof IAttribute);
            // If translated, we need to determine the languages.
            $languages = [];
            if ($attribute instanceof ITranslated) {
                if ($model instanceof ITranslatedMetaModel) {
                    $languages = [$model->getLanguage(), $model->getMainLanguage()];
                } else {
                    /** @psalm-suppress DeprecatedMethod */
                    $languages = [$model->getActiveLanguage(), (string) $model->getFallbackLanguage()];
                }
                $languages = \array_values(\array_filter($languages, fn(string $value): bool => '' !== $value));
            }

            // It is an attribute, we may search for it.
            foreach ($varValue as $value) {
                if ($attribute instanceof ITranslated) {
                    $ids = $attribute->searchForInLanguages($value, $languages);
                } else {
                    $ids = $attribute->searchFor($value);
                }

                // If all match, return all.
                if (null === $ids) {
                    $valueIds = $model->getIdsFromFilter($model->getEmptyFilter(), $alias);
                    break;
                }
                if ($ids) {
                    $valueIds = \array_merge($valueIds, $ids);
                }
            }
        } else {
            // Must be a system column then.
            // Translate the alias values to the item ids.
            $result = $this
                ->getConnection()
                ->createQueryBuilder()
                ->select('t.id')
                ->from($this->getTagSource(), 't')
                ->where('t.' . $alias . ' IN (:values)')
                ->setParameter('values', $varValue, ArrayParameterType::STRING)
                ->orderBy('FIELD(t.' . $alias . ',:values)')
                ->executeQuery();

            $valueIds = $result->fetchFirstColumn();

            if (empty($valueIds)) {
                throw new \RuntimeException('Could not translate value ' . \var_export($varValue, true));
            }
        }

        return $this->getValuesById(
            $valueIds,
            [$this->getIdColumn(), $this->getAliasColumn(), $this->getValueColumn()]
        );
    }

    /**
     * Calculate the amount how often each value has been assigned.
     *
     * @param IItems $items       The item list containing the values.
     * @param array  $amountArray The target array to where the counters shall be stored to.
     * @param array  $idList      The ids of items.
     *
     * @return void
     */
    protected function calculateFilterOptionsCount($items, &$amountArray, $idList)
    {
        $builder = $this
            ->getConnection()
            ->createQueryBuilder()
            ->select('t.value_id')
            ->addSelect('COUNT(t.item_id) AS amount')
            ->from('tl_metamodel_tag_relation', 't')
            ->where('t.att_id=:attId')
            ->setParameter('attId', $this->get('id'))
            ->groupBy('t.value_id');

        if (0 < $items->getCount()) {
            $ids = [];
            foreach ($items as $item) {
                $ids[] = $item->get('id');
            }
            $builder
                ->andWhere('t.value_id IN (:valueIds)')
                ->setParameter('valueIds', $ids, ArrayParameterType::STRING);
            if ([] !== $idList) {
                $builder
                    ->andWhere('t.item_id IN (:itemIds)')
                    ->setParameter('itemIds', $idList, ArrayParameterType::STRING);
            }
        }

        $counts = $builder->executeQuery();
        foreach ($counts->fetchAllAssociative() as $count) {
            $amountArray[$count['value_id']] = $count['amount'];
        }
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

        if ([] === $idList) {
            return [];
        }

        $filter = $this->getTagMetaModel()->getEmptyFilter();

        $this->buildFilterRulesForFilterSetting($filter);

        // If used only or id list given, select only the options that are assigned or assigned to only these items.
        if ($usedOnly || \is_array($idList)) {
            $this->buildFilterRulesForUsedOnly($filter, $idList ?? []);
        }

        $objItems = $this->getTagMetaModel()->findByFilter(
            $filter,
            $this->getSortingColumn(),
            0,
            0,
            $this->getSortDirection(),
            [$this->getAliasColumn(), $this->getValueColumn()]
        );

        if ($arrCount !== null) {
            $this->calculateFilterOptionsCount($objItems, $arrCount, $idList ?? []);
        }

        return $this->convertItemsToFilterOptions(
            $objItems,
            $this->getValueColumn(),
            $this->getAliasColumn(),
            $arrCount
        );
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getFilterOptionsForDcGeneral(): array
    {
        if (!$this->isFilterOptionRetrievingPossible(null)) {
            return [];
        }

        $metaModel = $this->getTagMetaModel();
        if (!$metaModel instanceof ITranslatedMetaModel) {
            // @deprecated usage of TL_LANGUAGE - remove for Contao 5.0.
            $originalLanguage = LocaleUtil::formatAsLocale($GLOBALS['TL_LANGUAGE']);
            /** @psalm-suppress DeprecatedMethod */
            $GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($this->getMetaModel()->getActiveLanguage());
        }

        $filter = $this->getTagMetaModel()->getEmptyFilter();

        $this->buildFilterRulesForFilterSetting($filter);

        $objItems = $this->getTagMetaModel()->findByFilter(
            $filter,
            $this->getSortingColumn(),
            0,
            0,
            $this->getSortDirection(),
            [$this->getAliasColumn(), $this->getValueColumn()]
        );

        if (isset($originalLanguage)) {
            // @deprecated usage of TL_LANGUAGE - remove for Contao 5.0.
            $GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($originalLanguage);
        }

        return $this->convertItemsToFilterOptions($objItems, $this->getValueColumn(), $this->getAliasColumn());
    }

    /**
     * Fetch filter options from foreign table taking the given flag into account.
     *
     * @param IFilter $filter The filter to which the rules shall be added to.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function buildFilterRulesForFilterSetting($filter)
    {
        if (!$this->get('tag_filter')) {
            return;
        }

        // Set Filter and co.
        $filterSettings = $this->filterSettingFactory->createCollection($this->get('tag_filter'));

        $values       = $_GET;
        $presets      = (array) $this->get('tag_filterparams');
        $presetNames  = $filterSettings->getParameters();
        $filterParams = \array_keys($filterSettings->getParameterFilterNames());
        $processed    = [];

        // We have to use all the preset values we want first.
        foreach ($presets as $presetName => $preset) {
            if (\in_array($presetName, $presetNames)) {
                $processed[$presetName] = $preset['value'];
            }
        }

        // Now we have to use all FrontEnd filter params, that are either:
        // * not contained within the presets
        // * or are overridable.
        foreach ($filterParams as $parameter) {
            // Unknown parameter? - next please.
            if (!\array_key_exists($parameter, $values)) {
                continue;
            }

            // Not a preset or allowed to override? - use value.
            if ((!\array_key_exists($parameter, $presets)) || $presets[$parameter]['use_get']) {
                $processed[$parameter] = $values[$parameter];
            }
        }

        $filterSettings->addRules($filter, $processed);
    }

    /**
     * Fetch filter options from foreign table taking the given flag into account.
     *
     * @param IFilter $filter The filter to which the rules shall be added to.
     * @param array   $idList The list of ids of items for which the rules shall be added.
     *
     * @return void
     */
    public function buildFilterRulesForUsedOnly($filter, $idList = [])
    {
        $result = $this
            ->getConnection()
            ->createQueryBuilder()
            ->select('t.value_id AS id')
            ->from('tl_metamodel_tag_relation', 't')
            ->where('t.att_id=:attId')
            ->groupBy('t.value_id')
            ->setParameter('attId', $this->get('id'));

        if (!empty($idList)) {
            $result
                ->andWhere('t.item_id IN (:itemIds)')
                ->setParameter('itemIds', $idList, ArrayParameterType::STRING);
        }

        $filter->addFilterRule(SimpleQuery::createFromQueryBuilder($result));
    }

    /**
     * Convert a collection of items into a proper filter option list.
     *
     * @param IItems|IItem[] $items        The item collection to convert.
     * @param string         $displayValue The name of the attribute to use as value.
     * @param string         $aliasColumn  The name of the attribute to use as alias.
     * @param null|string[]  $count        The counter array.
     *
     * @return array
     */
    protected function convertItemsToFilterOptions($items, $displayValue, $aliasColumn, &$count = null)
    {
        $result = [];
        foreach ($items as $item) {
            $parsedDisplay = $item->parseAttribute($displayValue);
            $parsedAlias   = $item->parseAttribute($aliasColumn);

            $textValue  = $parsedDisplay['text'] ?? $item->get($displayValue);
            $aliasValue = $parsedAlias['text'] ?? $item->get($aliasColumn);

            $result[$aliasValue] = $textValue;

            // Clean the count array if alias is different from id value.
            if (null !== $count && isset($count[$item->get('id')]) && $aliasValue !== $item->get('id')) {
                $count[$aliasValue] = $count[$item->get('id')];
                unset($count[$item->get('id')]);
            }
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

        $rows = $this
            ->getConnection()
            ->createQueryBuilder()
            ->select('t.item_id AS id')
            ->addSelect('t.value_id AS value')
            ->from('tl_metamodel_tag_relation', 't')
            ->where('t.item_id IN (:itemIds)')
            ->setParameter('itemIds', $arrIds, ArrayParameterType::STRING)
            ->andWhere('t.att_id=:attId')
            ->setParameter('attId', $this->get('id'))
            ->orderBy('t.value_sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        $valueIds     = [];
        $referenceIds = [];

        foreach ($rows as $row) {
            $referenceIds[] = $valueIds[$row['id']][] = $row['value'];
        }

        $values = $this->getValuesById($referenceIds);
        $result = [];
        foreach ($valueIds as $itemId => $tagIds) {
            foreach ($tagIds as $tagId) {
                // Value might have been deleted in referenced table and therefore return null here.
                $value = ($values[$tagId] ?? null);
                if (null === $value) {
                    @trigger_error(
                        \sprintf(
                            'Warning, skipping unknown tl_metamodel_tag_relation item value %1$s.%2$s.%3$s %4$s',
                            $this->getMetaModel()->getTableName(),
                            $itemId,
                            $this->getColName(),
                            $tagId
                        ),
                        E_USER_NOTICE
                    );
                    continue;
                }
                $result[$itemId][$tagId] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getIdForAlias(string $alias, string $language): ?string
    {
        if (!$this->isProperlyConfigured()) {
            return null;
        }

        $aliasColumn  = $this->getAliasColumn();
        $relatedModel = $this->getTagMetaModel();

        // Check first, if alias column a system column.
        if (!$relatedModel->hasAttribute($aliasColumn)) {
            $result  = $this
                ->getConnection()
                ->createQueryBuilder()
                ->select('t.id')
                ->from($this->getTagSource(), 't')
                ->where('t.' . $aliasColumn . '=:value')
                ->setParameter('value', $alias)
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->executeQuery();
            $idValue = $result->fetchOne();

            return ($idValue === false) ? null : (string) $idValue;
        }

        // Check if the current MM has translations.
        $currentLanguage    = null;
        $supportedLanguages = null;

        /**
         * @psalm-suppress DeprecatedMethod
         * @psalm-suppress TooManyArguments
         */
        if ($relatedModel instanceof ITranslatedMetaModel) {
            $supportedLanguages = $relatedModel->getLanguages();
            $fallbackLanguage   = $relatedModel->getMainLanguage();
        } elseif ($relatedModel->isTranslated(false)) {
            $backendLanguage    = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
            $supportedLanguages = $relatedModel->getAvailableLanguages();
            $fallbackLanguage   = ($relatedModel->getFallbackLanguage() ?? $backendLanguage);
        }

        if (\is_array($supportedLanguages) && !empty($supportedLanguages)) {
            if (\in_array($language, $supportedLanguages, false)) {
                $currentLanguage = $language;
            } else {
                $currentLanguage = $fallbackLanguage ?? null;
            }
        }

        // Retrieve original language only if target language is set.
        if (null !== $currentLanguage) {
            $this->selectLanguage($relatedModel, $language);
        }
        try {
            // Find the alias in the related metamodels, if there is no found return null.
            // On more than one result return the first one.
            $filter = $relatedModel->getEmptyFilter();
            $attribute = $relatedModel->getAttribute($aliasColumn);
            assert($attribute instanceof IAttribute);
            $filter->addFilterRule(new SearchAttribute($attribute, $alias));
            $items = $relatedModel->findByFilter($filter);
            if (false === $items->first()) {
                return null;
            }
            return $items->current()->get('id');
        } finally {
            if (null !== $currentLanguage) {
                $this->selectLanguage($relatedModel, $currentLanguage);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getAliasForId(string $id, string $language): ?string
    {
        if (!$this->isProperlyConfigured()) {
            return null;
        }

        // Check if the current MM has translations.
        $aliasColumn        = $this->getAliasColumn();
        $relatedModel       = $this->getTagMetaModel();
        $currentLanguage    = null;
        $supportedLanguages = null;
        $fallbackLanguage   = null;

        /**
         * @psalm-suppress DeprecatedMethod
         * @psalm-suppress TooManyArguments
         */
        if ($relatedModel instanceof ITranslatedMetaModel) {
            $supportedLanguages = $relatedModel->getLanguages();
            $fallbackLanguage   = $relatedModel->getMainLanguage();
        } elseif ($relatedModel->isTranslated(false)) {
            $backendLanguage = \str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
            assert(\is_string($backendLanguage));
            $supportedLanguages = $relatedModel->getAvailableLanguages();
            $fallbackLanguage   = ($relatedModel->getFallbackLanguage() ?? $backendLanguage);
        }

        if (\is_array($supportedLanguages) && !empty($supportedLanguages)) {
            if (\in_array($language, $supportedLanguages, false)) {
                $currentLanguage = $language;
            } else {
                $currentLanguage = $fallbackLanguage;
            }
        }

        // Retrieve original language only if target language is set.
        if (null !== $currentLanguage) {
            $this->selectLanguage($relatedModel, $language);
        }
        try {
            $item = $relatedModel->findById($id, [$aliasColumn]);
            if ($item === null) {
                return null;
            }
            return ($item->parseAttribute($aliasColumn)['text'] ?? null);
        } finally {
            if (null !== $currentLanguage) {
                $this->selectLanguage($relatedModel, $currentLanguage);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function selectLanguage(IMetaModel $relatedModel, string $language): void
    {
        if ($relatedModel instanceof ITranslatedMetaModel) {
            $relatedModel->selectLanguage($language);
            return;
        }
        /**
         * @psalm-suppress DeprecatedMethod
         * @psalm-suppress TooManyArguments
         */
        if ($relatedModel->isTranslated(false)) {
            $GLOBALS['TL_LANGUAGE'] = \str_replace('_', '-', $language);
        }
    }
}
