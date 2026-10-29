<?php

/**
 * Copyright (c) 2026 Blue Fairy Development LLC. MIT licence; see LICENSE.txt.
 */

declare(strict_types=1);

namespace BlueFairy\OrderCommentsHyva\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Tell Hyvä's `hyva-config` generator where this module's Tailwind sources live.
 *
 * Mirrors Hyva\Theme\Observer\RegisterModuleForHyvaConfig, which registers only
 * Hyva_Theme itself. Hyvä stores then pick up
 * view/frontend/tailwind/module.css and the templates it points at, so the utility
 * classes used only in this module survive the theme build.
 *
 * Without this, the cart-page comment panel loses every
 * utility that appears nowhere else in the theme — spacing, borders, the character
 * counter's colour states — and render unstyled. Silently: no console error, no 404.
 *
 * A "Tailwind safelist" comment in a template does nothing on its own; nothing reads
 * one. This observer is what makes the scanning happen.
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    private const MODULE_NAME = 'BlueFairy_OrderCommentsHyva';

    /**
     * @param ComponentRegistrar $componentRegistrar
     */
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar
    ) {
    }

    /**
     * Append this module's root-relative path to the Hyvä extension list.
     *
     * @param Observer $event
     * @return void
     */
    public function execute(Observer $event): void
    {
        $config = $event->getData('config');
        if ($config === null) {
            return;
        }

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        if ($path === null) {
            return;
        }

        // BP is Magento's root-path constant, defined at bootstrap; without it a
        // relative path cannot be formed, and a wrong one is worse than none.
        if (!defined('BP')) {
            return;
        }

        $root = (string) constant('BP');
        $extensions = $config->hasData('extensions') ? (array) $config->getData('extensions') : [];
        $extensions[] = ['src' => substr($path, strlen($root) + 1)];
        $config->setData('extensions', $extensions);
    }
}
