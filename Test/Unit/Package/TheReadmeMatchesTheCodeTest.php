<?php

/**
 * Copyright (c) 2026 Blue Fairy Development LLC. MIT licence; see LICENSE.txt.
 */

declare(strict_types=1);

namespace BlueFairy\OrderCommentsHyva\Test\Unit\Package;

use PHPUnit\Framework\TestCase;

/**
 * The README's factual claims, read from the files they describe: the package and base
 * constraints from composer.json, the block and container names from the parsed layouts,
 * the save endpoint from the cart template, the licence from LICENSE.txt. The admin path
 * and field labels belong to the base module and are not in this repository.
 */
class TheReadmeMatchesTheCodeTest extends TestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function read(string $relative): string
    {
        $path = self::root() . '/' . $relative;
        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }

    /** @return array<string, mixed> */
    private static function composer(): array
    {
        return (array) json_decode(self::read('composer.json'), true);
    }

    public function testInstallCommandNamesThisPackage(): void
    {
        self::assertStringContainsString(
            'composer require ' . self::composer()['name'] . "\n",
            self::read('README.md')
        );
    }

    public function testRequirementsQuoteComposerConstraints(): void
    {
        $require = (array) self::composer()['require'];
        $readme = self::read('README.md');
        foreach (['bluefairy/module-order-comments', 'hyva-themes/magento2-theme-module', 'magento/framework', 'php'] as $package) {
            self::assertArrayHasKey($package, $require);
            self::assertStringContainsString('`' . $require[$package] . '`', $readme, $package . ' constraint is not quoted in README.md');
        }
        self::assertSame('^1.2.1', $require['bluefairy/module-order-comments']);
        self::assertStringContainsString('**1.2.1 or later**', $readme);
    }

    /**
     * Each element the README names, as the layout really declares it: an XPath over the
     * parsed XML, so a name that survives only in a comment does not count.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function namesInLayouts(): array
    {
        $cart = 'view/frontend/layout/hyva_checkout_cart_index.xml';
        $checkout = 'view/frontend/layout/hyva_checkout_index_index.xml';

        return [
            'cart container' => ['checkout.cart.form.before', $cart, '//referenceContainer[@name="checkout.cart.form.before"]'],
            'cart block' => ['bluefairy.ordercomments.cart', $cart, '//referenceContainer[@name="checkout.cart.form.before"]/block[@name="bluefairy.ordercomments.cart"]'],
            'checkout parent' => ['hyva.checkout.payment.method.list', $checkout, '//referenceBlock[@name="hyva.checkout.payment.method.list"]'],
            'checkout block' => ['bluefairy.ordercomments.hyva.checkout', $checkout, '//referenceBlock[@name="hyva.checkout.payment.method.list"]/block[@name="bluefairy.ordercomments.hyva.checkout"]'],
        ];
    }

    /** @dataProvider namesInLayouts */
    #[\PHPUnit\Framework\Attributes\DataProvider('namesInLayouts')]
    public function testEveryLayoutNameTheReadmeQuotesIsDeclared(string $name, string $file, string $xpath): void
    {
        self::assertStringContainsString('`' . $name . '`', self::read('README.md'), "README.md does not quote $name");
        $xml = simplexml_load_string(self::read($file));
        self::assertNotFalse($xml, "$file does not parse");
        self::assertCount(1, (array) $xml->xpath($xpath), "$file does not declare $xpath");
    }

    public function testTheSaveEndpointTheReadmeQuotesIsTheOneTheCartTemplatePosts(): void
    {
        $endpoint = 'bluefairy_ordercomments/cart/saveComment';
        self::assertStringContainsString('`' . $endpoint . '`', self::read('README.md'));
        self::assertStringContainsString(
            "\$block->getUrl('" . $endpoint . "')",
            self::read('view/frontend/templates/cart/order-comment.phtml')
        );
    }

    public function testTheTailwindSourceTheReadmeDescribesIsAnActiveDirective(): void
    {
        self::assertStringContainsString('`@source`', self::read('README.md'));
        self::assertMatchesRegularExpression(
            '#^@source\s+"\.\./templates/\*\*/\*\.phtml";#m',
            self::read('view/frontend/tailwind/module.css')
        );
    }

    public function testTheReadmeLicenceIsTheShippedLicence(): void
    {
        $readme = self::read('README.md');
        self::assertStringContainsString('MIT', $readme);
        self::assertSame(['MIT'], (array) self::composer()['license']);
        self::assertStringStartsWith('MIT License', self::read('LICENSE.txt'));
    }
}
