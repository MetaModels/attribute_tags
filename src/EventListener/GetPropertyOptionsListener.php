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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_tags/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTagsBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use MetaModels\AttributeTagsBundle\Attribute\AbstractTags;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\IItem;

/**
 * The subscriber for the get filter options call.
 */
class GetPropertyOptionsListener
{
    /**
     * Retrieve the property options.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public static function getPropertyOptions(GetPropertyOptionsEvent $event)
    {
        if (null !== $event->getOptions()) {
            return;
        }

        $model = $event->getModel();

        if (!($model instanceof Model)) {
            return;
        }

        $item = $model->getItem();
        assert($item instanceof IItem);
        $propertyName = $event->getPropertyName();
        assert(\is_string($propertyName));
        $attribute = $item->getAttribute($propertyName);

        if (!($attribute instanceof AbstractTags)) {
            return;
        }

        try {
            $options = $attribute->getFilterOptionsForDcGeneral();
        } catch (\Exception $exception) {
            $options = ['Error: ' . $exception->getMessage()];
        }

        $event->setOptions($options);
    }
}
