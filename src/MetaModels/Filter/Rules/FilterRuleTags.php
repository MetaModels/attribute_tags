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
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     David Maack <david.maack@arcor.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Martin Treml <github@r2pi.net>
 * @author     Patrick Heller <ph@wacg.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Filter\Rules;

use MetaModels\Attribute\Tags\AbstractTags;
use MetaModels\Attribute\Tags\MetaModelTags;
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
     * @var AbstractTags
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
            $this->objSelectMetaModel = $this
                ->objAttribute
                ->getMetaModel()
                ->getServiceContainer()
                ->getFactory()
                ->getMetaModel($this->objAttribute->get('tag_table'));
        }

        return $this->objSelectMetaModel;
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(AbstractTags $objAttribute, $strValue)
    {
        parent::__construct();

        $this->objAttribute = $objAttribute;
        $this->value        = $strValue;
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
                $strTableNameId = $this->objAttribute->get('tag_table');
                $objDB          = $this->objAttribute->getMetaModel()->getServiceContainer()->getDatabase();
                $objSelectIds   = $objDB
                    ->prepare(
                        \sprintf(
                            'SELECT %1$s FROM %2$s WHERE %3$s',
                            $strColNameId,
                            $strTableNameId,
                            \implode(' OR ', \array_fill(0, \count($arrValues), $strColNameAlias . ' LIKE ?'))
                        )
                    )
                    ->execute(
                        \array_map(
                            function ($value) {
                                return \str_replace(['*', '?'], ['%', '_'], $value);
                            },
                            $arrValues
                        )
                    );

                $arrValues = $objSelectIds->fetchEach($strColNameId);
            } else {
                $arrValues = \array_map('intval', $arrValues);
            }
        } else {
            if ($strColNameAlias == 'id') {
                $values = $arrValues;
            } else {
                $values = [];
                foreach ($arrValues as $value) {
                    $values[] = \array_values(
                        $this->getTagMetaModel()->getAttribute($strColNameAlias)->searchFor($value)
                    );
                }
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

        $objMatches = $this
            ->objAttribute
            ->getMetaModel()
            ->getServiceContainer()
            ->getDatabase()
            ->prepare(
                'SELECT item_id as id
                FROM tl_metamodel_tag_relation
                WHERE value_id IN (' . \implode(',', $arrValues) . ')
                AND att_id = ?'
            )
            ->execute($this->objAttribute->get('id'));

        return $objMatches->fetchEach('id');
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
