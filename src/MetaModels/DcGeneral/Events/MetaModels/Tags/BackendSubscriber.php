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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\MetaModels\Tags;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ManipulateWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\TreePicker;
use MetaModels\Attribute\Tags\AbstractTags;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\Events\BaseSubscriber;

/**
 * The subscriber for the get filter options call.
 */
class BackendSubscriber extends BaseSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function registerEventsInDispatcher()
    {
        $this
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                array($this, 'getPropertyOptions')
            )
            ->addListener(
                ManipulateWidgetEvent::NAME,
                array($this, 'manipulateTreePrickerForSortOrder')
            );
    }

    /**
     * Retrieve the property options.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getPropertyOptions(GetPropertyOptionsEvent $event)
    {
        if ($event->getOptions() !== null) {
            return;
        }

        $model = $event->getModel();

        if (!($model instanceof Model)) {
            return;
        }
        $attribute = $model->getItem()->getAttribute($event->getPropertyName());

        if (!($attribute instanceof AbstractTags)) {
            return;
        }

        try {
            $options = $attribute->getFilterOptions(null, false);
        } catch (\Exception $exception) {
            $options = array('Error: ' . $exception->getMessage());
        }

        $event->setOptions($options);
    }

    /**
     * Manipulate the tree picker for sort order.
     *
     * @param ManipulateWidgetEvent $event The event.
     *
     * @return void
     */
    public function manipulateTreePrickerForSortOrder(ManipulateWidgetEvent $event)
    {
        $widget = $event->getWidget();
        if (!($widget instanceof TreePicker)) {
            return;
        }

        $options = (array) $widget->options;
        if (0 === count($options)) {
            return;
        }

        $model = $event->getModel();
        if (!($model instanceof Model)) {
            return;
        }

        $attribute = $model->getItem()->getAttribute($widget->strField);
        if (!($attribute instanceof AbstractTags)) {
            return;
        }

        $widget->orderField = $widget->orderField . '__ordered';

        $ordered = \array_flip(\array_merge([], (array) $model->getProperty($widget->strField)));
        foreach ($options as $option) {
            $ordered[$option['value']] = $option['value'];
        }

        $widget->{$widget->orderField} = $ordered;
    }
}
