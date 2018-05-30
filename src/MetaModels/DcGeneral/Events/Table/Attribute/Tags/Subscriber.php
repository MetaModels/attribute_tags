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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\Table\Attribute\Tags;

use Contao\Database;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionChainInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ConditionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\NotCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use MetaModels\DcGeneral\DataDefinition\Palette\Condition\Property\ConditionTableNameIsMetaModel;
use MetaModels\DcGeneral\Events\BaseSubscriber;

/**
 * Handle events for tl_metamodel_attribute for tag attributes.
 */
class Subscriber extends BaseSubscriber
{
    /**
     * Boot the system in the backend.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function registerEventsInDispatcher()
    {
        $this
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                [$this, 'handleTableNames']
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                [$this, 'getColumnNames']
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                [$this, 'getIntColumnNames']
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                [$this, 'getFilters']
            )
            ->addListener(
                BuildWidgetEvent::NAME,
                [$this, 'getFiltersParams']
            )
            ->addListener(
                BuildDataDefinitionEvent::NAME,
                [$this, 'buildPaletteRestrictions']
            )
            ->addListener(
                EncodePropertyValueFromWidgetEvent::NAME,
                [$this, 'checkQuery']
            );
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
        $factory = $this->getServiceContainer()->getFactory();
        $result  = [];
        $tables  = $factory->collectNames();

        foreach ($tables as $table) {
            $metaModel = $factory->getMetaModel($table);
            if ($metaModel->isTranslated()) {
                $result[$keyTranslated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            } else {
                $result[$keyUntranslated][$table] = sprintf('%s (%s)', $metaModel->get('name'), $table);
            }
        }

        return $result;
    }

    /**
     * Retrieve all database table names and store them into the event.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function getTableNames(GetPropertyOptionsEvent $event)
    {
        $database     = $this->getServiceContainer()->getDatabase();
        $sqlTable     = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table_type']['sql-table'];
        $translated   = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table_type']['translated'];
        $untranslated = $GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_table_type']['untranslated'];

        $result = $this->getMetaModelTableNames($translated, $untranslated);

        foreach ($database->listTables() as $table) {
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
     * Retrieve all database table names.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function handleTableNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'tag_table')) {
            return;
        }

        $this->getTableNames($event);
    }

    /**
     * Retrieve all attribute names from a given MetaModel name.
     *
     * @param string $metaModelName The name of the MetaModel.
     *
     * @return string[]
     */
    protected function getAttributeNamesFrom($metaModelName)
    {
        $metaModel = $this->getServiceContainer()->getFactory()->getMetaModel($metaModelName);
        $result    = [];

        if ($metaModel === null) {
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
     * Retrieve all columns from a database table.
     *
     * @param string $tableName The database table name.
     *
     * @return string[]
     */
    protected function getColumnNamesFrom($tableName)
    {
        $database = $this->getServiceContainer()->getDatabase();

        if (!$tableName || !$database->tableExists($tableName)) {
            return [];
        }

        $result = [];

        foreach ($database->listFields($tableName) as $arrInfo) {
            if ($arrInfo['type'] != 'index') {
                $result[$arrInfo['name']] = $arrInfo['name'];
            }
        }

        return $result;
    }

    /**
     * Retrieve all column names for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @param string                  $table The table to retrieve the columns from.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function handleColumnNames(GetPropertyOptionsEvent $event, $table)
    {
        if (substr($table, 0, 3) === 'mm_') {
            $attributes = self::getAttributeNamesFrom($table);
            asort($attributes);

            $event->setOptions(
                [
                    $GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_column_type']['sql']
                    => array_diff_key($this->getColumnNamesFrom($table), array_flip(array_keys($attributes))),
                    $GLOBALS['TL_LANG']['tl_metamodel_attribute']['tag_column_type']['attribute']
                    => $attributes
                ]
            );

            return;
        }

        $result = $this->getColumnNamesFrom($table);

        if (!empty($result)) {
            asort($result);
            $event->setOptions($result);
        }
    }

    /**
     * Retrieve all column names for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getColumnNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || (
                ($event->getPropertyName() !== 'tag_column')
                && ($event->getPropertyName() !== 'tag_alias')
                && ($event->getPropertyName() !== 'tag_sorting')
            )
        ) {
            return;
        }

        $this->handleColumnNames($event, $event->getModel()->getProperty('tag_table'));
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
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'tag_filter')
        ) {
            return;
        }

        $model     = $event->getModel();
        $metaModel = $this->getServiceContainer()->getFactory()->getMetaModel($model->getProperty('tag_table'));

        if ($metaModel) {
            $filter = $this
                ->getServiceContainer()
                ->getDatabase()
                ->prepare('SELECT id,name FROM tl_metamodel_filter WHERE pid=? ORDER BY name')
                ->execute($metaModel->get('id'));

            $result = [];
            while ($filter->next()) {
                $result[$filter->id] = $filter->name;
            }

            $event->setOptions($result);
        }
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
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getProperty()->getName() !== 'tag_filterparams')
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
        $filterSettings = $this->getServiceContainer()->getFilterFactory()->createCollection($filterId);
        if ($filterSettings == null) {
            return;
        }

        // Set the subfields.
        $arrExtra['subfields'] = $filterSettings->getParameterDCA();
        $properties->setExtra($arrExtra);
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
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'tag_id')
        ) {
            return;
        }

        $model    = $event->getModel();
        $table    = $model->getProperty('tag_table');
        $database = $this->getServiceContainer()->getDatabase();

        if (!$table || !$database->tableExists($table)) {
            return;
        }

        $result = [];

        foreach ($database->listFields($table) as $arrInfo) {
            if ($arrInfo['type'] != 'index' && $arrInfo['type'] == 'int') {
                $result[$arrInfo['name']] = $arrInfo['name'];
            }
        }

        $event->setOptions($result);
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
    public function addCondition($property, $condition)
    {
        $currentCondition = $property->getVisibleCondition();
        if ((!($currentCondition instanceof ConditionChainInterface))
            || ($currentCondition->getConjunction() != ConditionChainInterface::OR_CONJUNCTION)
        ) {
            if ($currentCondition === null) {
                $currentCondition = new PropertyConditionChain([$condition]);
            } else {
                $currentCondition = new PropertyConditionChain([$currentCondition, $condition]);
            }
            $currentCondition->setConjunction(ConditionChainInterface::OR_CONJUNCTION);
            $property->setVisibleCondition($currentCondition);
        } else {
            $currentCondition->addCondition($condition);
        }
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
    public function buildConditions($propertyNames, PalettesDefinitionInterface $palettes)
    {
        foreach ($palettes->getPalettes() as $palette) {
            foreach ($propertyNames as $propertyName => $mask) {
                foreach ($palette->getProperties() as $property) {
                    if ($property->getName() === $propertyName) {
                        // Show the widget when we are editing a select attribute.
                        $condition = new PropertyConditionChain(
                            [
                                new PropertyConditionChain(
                                    [
                                        new PropertyValueCondition('type', 'tags'),
                                        new ConditionTableNameIsMetaModel('tag_table', $mask)
                                    ]
                                )
                            ],
                            ConditionChainInterface::OR_CONJUNCTION
                        );
                        // If we want to hide the widget for metamodel tables, do so only when editing a select
                        // attribute.
                        if (!$mask) {
                            $condition->addCondition(new NotCondition(new PropertyValueCondition('type', 'tags')));
                        }

                        self::addCondition($property, $condition);
                    }
                }
            }
        }
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
        if ($event->getContainer()->getName() !== 'tl_metamodel_attribute') {
            return;
        }

        $this->buildConditions(
            [
                'tag_id'           => false,
                'tag_where'        => false,
                'tag_filter'       => true,
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
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getProperty() !== 'tag_where')
        ) {
            return;
        }

        $where  = $event->getValue();
        $values = $event->getPropertyValueBag();

        if ($where) {
            $objDB = Database::getInstance();

            $strTableName  = $values->getPropertyValue('tag_table');
            $strColNameId  = $values->getPropertyValue('tag_id');
            $strSortColumn = $values->getPropertyValue('tag_sorting') ?: $strColNameId;

            $query = sprintf(
                'SELECT COUNT(rel.value_id) as mm_count, %1$s.*
                FROM %1$s
                LEFT JOIN tl_metamodel_tag_relation as rel
                ON (
                    (rel.att_id="0") AND (rel.value_id=%1$s.%3$s)
                )
                %2$s
                GROUP BY %1$s.%3$s
                ORDER BY %1$s.%4$s',
                // @codingStandardsIgnoreStart - We want to keep the numbers as comment at the end of the following lines.
                $strTableName,                            // 1
                ($where ? ' WHERE ('.$where.')' : false), // 2
                $strColNameId,                            // 3
                $strSortColumn                            // 4
            // @codingStandardsIgnoreEnd
            );

            try {
                $objDB
                    ->prepare($query)
                    ->execute();
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    sprintf(
                        '%s %s',
                        $GLOBALS['TL_LANG']['tl_metamodel_attribute']['sql_error'],
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
