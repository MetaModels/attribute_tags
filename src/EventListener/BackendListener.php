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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionChainInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\NotCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use MetaModels\DcGeneral\DataDefinition\Palette\Condition\Property\ConditionTableNameIsMetaModel;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IFactory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handle events for tl_metamodel_attribute for tag attributes.
 */
class BackendListener
{
    /**
     * Request scope determinator.
     *
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * MetaModels factory.
     *
     * @var IFactory
     */
    private $factory;

    /**
     * Translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Filter setting factory.
     *
     * @var IFilterSettingFactory
     */
    private $filterSettingFactory;

    /**
     * EventListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher         Request scope determinator.
     * @param Connection               $connection           Database connection.
     * @param IFactory                 $factory              MetaModels factory.
     * @param IFilterSettingFactory    $filterSettingFactory Filter setting factory.
     * @param TranslatorInterface      $translator           Translator.
     */
    public function __construct(
        RequestScopeDeterminator $scopeMatcher,
        Connection $connection,
        IFactory $factory,
        IFilterSettingFactory $filterSettingFactory,
        TranslatorInterface $translator
    ) {
        $this->scopeMatcher         = $scopeMatcher;
        $this->connection           = $connection;
        $this->factory              = $factory;
        $this->translator           = $translator;
        $this->filterSettingFactory = $filterSettingFactory;
    }

    /**
     * Retrieve all database table names and store them into the event.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getTableNames(GetPropertyOptionsEvent $event)
    {
        if (!$this->isBackendOptionRequestFor($event, ['tag_table'])) {
            return;
        }

        $sqlTable     = $this->translator->trans('tag_table_type.sql-table', [], 'contao_tl_metamodel_attribute');
        $translated   = $this->translator->trans('tag_table_type.translated', [], 'contao_tl_metamodel_attribute');
        $untranslated = $this->translator->trans('tag_table_type.untranslated', [], 'contao_tl_metamodel_attribute');

        $result = $this->getMetaModelTableNames($translated, $untranslated);
        foreach ($this->connection->getSchemaManager()->listTableNames() as $table) {
            if ((substr($table, 0, 3) !== 'mm_')) {
                $result[$sqlTable][$table] = $table;
            }
        }

        if (is_array($result[$translated])) {
            asort($result[$translated]);
        }

        if (is_array($result[$untranslated])) {
            asort($result[$untranslated]);
        }

        if (is_array($result[$sqlTable])) {
            asort($result[$sqlTable]);
        }

        $event->setOptions($result);
    }

    /**
     * Retrieve all column names for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getColumnNames(GetPropertyOptionsEvent $event)
    {
        if (!$this->isBackendOptionRequestFor($event, ['tag_column', 'tag_alias', 'tag_sorting'])) {
            return;
        }

        $result = $this->getColumnNamesFrom($event->getModel()->getProperty('tag_table'));

        if (!empty($result)) {
            asort($result);
            $event->setOptions($result);
        }
    }

    /**
     * Retrieve all column names of type int for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getIntColumnNames(GetPropertyOptionsEvent $event)
    {
        if (!$this->isBackendOptionRequestFor($event, ['tag_id'])) {
            return;
        }

        $result = $this->getColumnNamesFromTable(
            $event->getModel()->getProperty('tag_table'),
            [Type::INTEGER, Type::BIGINT, Type::SMALLINT]
        );

        $event->setOptions($result);
    }

    /**
     * Retrieve all filter names for the currently selected MetaModel.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getFilters(GetPropertyOptionsEvent $event)
    {
        if (!$this->isBackendOptionRequestFor($event, ['tag_filter'])) {
            return;
        }

        $model = $event->getModel();
        if (null === $metaModel = $this->factory->getMetaModel($model->getProperty('tag_table'))) {
            return;
        }
        $filters = $this->connection->createQueryBuilder()
            ->select('id', 'name')
            ->from('tl_metamodel_filter')
            ->where('pid=:pid')
            ->setParameter('pid', $metaModel->get('id'))
            ->orderBy('name')
            ->execute();

        $result = [];
        foreach ($filters->fetchAll(\PDO::FETCH_ASSOC) as $filter) {
            $result[$filter['id']] = $filter['name'];
        }

        $event->setOptions($result);
    }

    /**
     * Set the sub fields for the sub-dca based in the mm_filter selection.
     *
     * @param BuildWidgetEvent $event The event.
     *
     * @return void
     */
    public function getFiltersParams(BuildWidgetEvent $event)
    {
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }
        if (('tl_metamodel_attribute' !== $event->getEnvironment()->getDataDefinition()->getName())
            || ('tag_filterparams' !== $event->getProperty()->getName())
        ) {
            return;
        }

        $model      = $event->getModel();
        $properties = $event->getProperty();
        $arrExtra   = $properties->getExtra();
        $filterId   = $model->getProperty('tag_filter');
        // Check if we have a filter, if not return.
        if (empty($filterId)) {
            return;
        }
        // Get the filter with the given id and check if we got it.
        // If not return.
        if (null === $filterSettings = $this->filterSettingFactory->createCollection($filterId)) {
            return;
        }
        // Set the subfields.
        $arrExtra['subfields'] = $filterSettings->getParameterDCA();
        $properties->setExtra($arrExtra);
    }

    /**
     * Build the data definition palettes.
     *
     * @param BuildDataDefinitionEvent $event The event.
     *
     * @return void
     */
    public function buildPaletteRestrictions(BuildDataDefinitionEvent $event)
    {
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }
        if (('tl_metamodel_attribute' !== $event->getContainer()->getName())) {
            return;
        }

        $this->buildConditions(
            [
                'tag_id'     => false,
                'tag_where'  => false,
                'tag_filter' => true,
                'tag_filterparams' => true,
            ],
            $event->getContainer()->getPalettesDefinition()
        );
    }

    /**
     * Check if the select_where value is valid by firing a test query.
     *
     * @param EncodePropertyValueFromWidgetEvent $event The event.
     *
     * @return void
     *
     * @throws \RuntimeException When the where condition is invalid.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function checkQuery(EncodePropertyValueFromWidgetEvent $event)
    {
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

        if (('tl_metamodel_attribute' !== $event->getEnvironment()->getDataDefinition()->getName())
            || ('tag_where' !== $event->getProperty())
        ) {
            return;
        }

        $where  = $event->getValue();
        $values = $event->getPropertyValueBag();

        if ($where) {
            $query = $this->connection->createQueryBuilder()
                ->select($values->getPropertyValue('tag_table') . '.*')
                ->from($values->getPropertyValue('tag_table'))
                ->where($where)
                ->orderBy($values->getPropertyValue('tag_sorting') ?: $values->getPropertyValue('tag_id'));

            try {
                $query->execute();
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    sprintf(
                        '%s %s',
                        $this->translator->trans('sql_error', [], 'contao_tl_metamodel_attribute'),
                        $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * Retrieve all MetaModels table names.
     *
     * @param string $keyTranslated   The array key to use for translated MetaModels.
     *
     * @param string $keyUntranslated The array key to use for untranslated MetaModels.
     *
     * @return array
     */
    private function getMetaModelTableNames($keyTranslated, $keyUntranslated)
    {
        $result = [];
        $tables = $this->factory->collectNames();

        foreach ($tables as $table) {
            $metaModel = $this->factory->getMetaModel($table);
            if ($metaModel->isTranslated()) {
                $result[$keyTranslated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            } else {
                $result[$keyUntranslated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            }
        }

        return $result;
    }

    /**
     * Retrieve all column names for the given table.
     *
     * @param string $table The table name.
     *
     * @return array
     */
    private function getColumnNamesFrom($table)
    {
        if (substr($table, 0, 3) === 'mm_') {
            $attributes = $this->getAttributeNamesFrom($table);
            asort($attributes);

            return [
                $this->translator->trans('tag_column_type.sql', [], 'contao_tl_metamodel_attribute') =>
                    array_diff_key(
                        $this->getColumnNamesFromTable($table),
                        array_flip(array_keys($attributes))
                    ),
                $this->translator->trans('tag_column_type.attribute', [], 'contao_tl_metamodel_attribute') =>
                    $attributes
            ];
        }

        return $this->getColumnNamesFromTable($table);
    }

    /**
     * Retrieve all columns from a database table.
     *
     * @param string     $tableName  The database table name.
     *
     * @param array|null $typeFilter Optional of types to filter for.
     *
     * @return string[]
     */
    private function getColumnNamesFromTable($tableName, $typeFilter = null)
    {
        if (!$this->connection->getSchemaManager()->tablesExist([$tableName])) {
            return [];
        }

        $result    = [];
        $fieldList = $this->connection->getSchemaManager()->listTableColumns($tableName);

        foreach ($fieldList as $column) {
            if (($typeFilter === null) || in_array($column->getType()->getName(), $typeFilter)) {
                $result[$column->getName()] = $column->getName();
            }
        }

        if (!empty($result)) {
            asort($result);
            return $result;
        }

        return $result;
    }

    /**
     * Retrieve all attribute names from a given MetaModel name.
     *
     * @param string $metaModelName The name of the MetaModel.
     *
     * @return string[]
     */
    private function getAttributeNamesFrom($metaModelName)
    {
        $metaModel = $this->factory->getMetaModel($metaModelName);
        $result    = [];
        if (null === $metaModel) {
            return $result;
        }

        foreach ($metaModel->getAttributes() as $attribute) {
            $name   = $attribute->getName();
            $column = $attribute->getColName();
            $type   = $attribute->get('type');

            $result[$column] = sprintf('%s (%s - %s)', $name, $column, $type);
        }

        return $result;
    }

    /**
     * Build the data definition palettes.
     *
     * @param string[]                    $propertyNames The property names which shall be masked.
     *
     * @param PalettesDefinitionInterface $palettes      The palette definition.
     *
     * @return void
     */
    private function buildConditions($propertyNames, PalettesDefinitionInterface $palettes)
    {
        foreach ($palettes->getPalettes() as $palette) {
            foreach ($propertyNames as $propertyName => $mask) {
                foreach ($palette->getProperties() as $property) {
                    if ($property->getName() === $propertyName) {
                        // Show the widget when we are editing a select attribute.
                        $condition = new PropertyConditionChain(
                            [
                                new PropertyConditionChain([
                                    new PropertyValueCondition('type', 'tags'),
                                    new ConditionTableNameIsMetaModel('tag_table', $mask)
                                ])
                            ],
                            ConditionChainInterface::OR_CONJUNCTION
                        );
                        // If we want to hide the widget for metamodel tables, do so only when editing a select
                        // attribute.
                        if (!$mask) {
                            $condition->addCondition(new NotCondition(new PropertyValueCondition('type', 'tags')));
                        }

                        $this->addCondition($property, $condition);
                    }
                }
            }
        }
    }


    /**
     * Add a condition to a property.
     *
     * @param PropertyInterface  $property  The property.
     *
     * @param ConditionInterface $condition The condition to add.
     *
     * @return void
     */
    private function addCondition($property, $condition)
    {
        $currentCondition = $property->getVisibleCondition();
        if ((!($currentCondition instanceof ConditionChainInterface))
            || ($currentCondition->getConjunction() != ConditionChainInterface::OR_CONJUNCTION)
        ) {
            if ($currentCondition === null) {
                $currentCondition = new PropertyConditionChain(array($condition));
            } else {
                $currentCondition = new PropertyConditionChain(array($currentCondition, $condition));
            }
            $currentCondition->setConjunction(ConditionChainInterface::OR_CONJUNCTION);
            $property->setVisibleCondition($currentCondition);
        } else {
            $currentCondition->addCondition($condition);
        }
    }

    /**
     * Test if the event is an option request for any of the passed fields.
     *
     * @param GetPropertyOptionsEvent $event  The event.
     * @param string[]                $fields The field names.
     *
     * @return bool
     */
    private function isBackendOptionRequestFor($event, $fields)
    {
        if (!$this->scopeMatcher->currentScopeIsBackend()) {
            return false;
        }

        return ('tl_metamodel_attribute' === $event->getEnvironment()->getDataDefinition()->getName())
            && in_array($event->getPropertyName(), $fields);
    }
}
