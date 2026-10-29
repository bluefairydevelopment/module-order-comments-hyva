<?php

/**
 * Copyright (c) 2026 Blue Fairy Development LLC. MIT licence; see LICENSE.txt.
 */

declare(strict_types=1);

namespace BlueFairy\OrderCommentsHyva\Test\Unit\Template;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Alpine state must be seeded from the quote, not from a literal empty string.
 *
 * This asserts on template SOURCE, which is a weaker instrument than executing the template -
 * and it is here because the alternative was nothing. Rendering a .phtml needs a booted
 * Magento, so it belongs to the integration suite; this catches the one regression that has
 * actually happened, in the unit suite, at no cost.
 *
 * WHAT IT IS GUARDING. Both templates hardcoded `commentDropdown: ''` and `commentText: ''`,
 * so a field always rendered empty however much the quote held. Because an empty field
 * means "the shopper cleared this", an unseeded field DELETES a comment entered on the cart
 * page the moment the payment step opens.

 */
class AlpineStateSeedingTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function templateProvider(): array
    {
        return [
            'cart' => [__DIR__ . '/../../../view/frontend/templates/cart/order-comment.phtml'],
            'checkout' => [__DIR__ . '/../../../view/frontend/templates/checkout/order-comment.phtml'],
        ];
    }

    /**
     * @dataProvider templateProvider
     * @param string $template
     */
    #[DataProvider('templateProvider')]
    public function testTheAlpineStateIsSeededFromTheQuote(string $template): void
    {
        self::assertFileExists($template, 'the template moved - this test is measuring nothing');
        $source = (string) file_get_contents($template);

        self::assertStringContainsString(
            "\$commentValues = \$block->getData('comment_values');",
            $source,
            'the template must take the current quote values as a layout argument'
        );
        // Null-safe, so a third-party layout override that omits the argument degrades to an
        // unseeded field instead of fatalling the page.
        self::assertStringContainsString('$commentValues?->getDropdown() ?? \'\'', $source);
        self::assertStringContainsString('$commentValues?->getText() ?? \'\'', $source);

        foreach (['commentDropdown' => 'currentDropdown', 'commentText' => 'currentText'] as $state => $var) {
            self::assertMatchesRegularExpression(
                '/' . $state . ":\\s*'<\\?=\\s*\\\$escaper->escapeJs\\(\\\$" . $var . '\)\s*\?>\'/',
                $source,
                sprintf(
                    '%s must be seeded from $%s, escaped - a literal empty string here erases'
                    . ' a comment the shopper entered on the cart page',
                    $state,
                    $var
                )
            );
        }
    }

    /**
     * The layout has to pass the argument the template reads, or the template fatals.
     *
     * @return array<string, array{0: string}>
     */
    public static function layoutProvider(): array
    {
        return [
            'cart' => [__DIR__ . '/../../../view/frontend/layout/hyva_checkout_cart_index.xml'],
            'checkout' => [__DIR__ . '/../../../view/frontend/layout/hyva_checkout_index_index.xml'],
        ];
    }

    /**
     * @dataProvider layoutProvider
     * @param string $layout
     */
    #[DataProvider('layoutProvider')]
    public function testTheLayoutSuppliesTheCommentValuesArgument(string $layout): void
    {
        self::assertFileExists($layout, 'the layout moved - this test is measuring nothing');
        $source = (string) file_get_contents($layout);

        self::assertStringContainsString('name="comment_values"', $source);
        self::assertStringContainsString(
            'BlueFairy\OrderComments\Model\Quote\CurrentComment',
            $source,
            'the argument must resolve to the class the template calls getDropdown()/getText() on'
        );
    }
}
