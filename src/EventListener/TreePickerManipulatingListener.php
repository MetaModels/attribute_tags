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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ManipulateWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\TreePicker;
use MetaModels\AttributeTagsBundle\Attribute\AbstractTags;
use MetaModels\DcGeneral\Data\Model;

/**
 * The subscriber for the get filter options call.
 */
class TreePickerManipulatingListener
{
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
        if (0 === \count($options)) {
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
