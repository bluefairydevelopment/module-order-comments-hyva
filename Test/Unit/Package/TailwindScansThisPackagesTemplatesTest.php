<?php

/**
 * Copyright (c) 2026 Blue Fairy Development LLC. MIT licence; see LICENSE.txt.
 */

declare(strict_types=1);

namespace BlueFairy\OrderCommentsHyva\Test\Unit\Package;

use PHPUnit\Framework\TestCase;

/**
 * Hyvä's Tailwind build scans the THEME. A module's utility classes reach the
 * compiled stylesheet only if the module's own `view/frontend/tailwind/module.css`
 * declares an `@source` glob over its templates.
 *
 * Without it the store's stylesheet has no rule for the classes this package's
 * templates use: the comment panel loses its padding, border and field spacing, and
 * the character counter its colour states - with no console error and no 404.
 *
 * Nothing else in this package's gates can see this. phpcs and phpstan do not read
 * CSS; phpunit is green either way; the storefront returns 200 with the right
 * markup either way. So the check has to exist, and it has to be able to fail -
 * every assertion below is written against a specimen this test plants itself.
 */
class TailwindScansThisPackagesTemplatesTest extends TestCase
{
    private const SOURCE_FILE = '/view/frontend/tailwind/module.css';

    /** A class bound through Alpine or set from script - forms the Tailwind scanner cannot read. */
    private const COMPOSED_CLASS = '/(?:\s:class=|x-bind:class=|classList\.)/';

    private static function packageRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function moduleCss(): string
    {
        $path = self::packageRoot() . self::SOURCE_FILE;
        self::assertFileExists($path, 'the Tailwind registration file is missing entirely');
        $css = file_get_contents($path);
        self::assertIsString($css, 'the Tailwind registration file is unreadable');

        return $css;
    }

    /**
     * Strip block comments. The defect was a file whose ONLY mention of `@source`
     * was inside a comment describing what `@source` does, so a plain substring
     * search over the raw file would have passed on the broken state.
     */
    private static function directives(string $css): string
    {
        // The opener must be `/**`, a doc comment. The obvious `!/\*.*?\*/!s` EATS
        // THE GLOB: `../templates/**/*.phtml` contains `/*` ... `*/`, so a general
        // block-comment strip rewrites it to `../templates*.phtml` and the test
        // then reports the package's own templates as uncovered. It did exactly
        // that on the first run - a red from the instrument, dressed as a red from
        // the subject. testTheStripperDoesNotEatAGlobstarPath is the control.
        // Strip either opener, but only when it is followed by whitespace - `/**/` inside a
        // globstar path is followed by `/`, and survives.
        return (string) preg_replace('!/\*\*?(?=\s)[\s\S]*?\*/!', '', $css);
    }

    public function testTheRegistrationFileDeclaresAtLeastOneSourceDirective(): void
    {
        $directives = self::directives(self::moduleCss());

        self::assertMatchesRegularExpression(
            '/@source\b/',
            $directives,
            'view/frontend/tailwind/module.css declares no @source outside its comments, so '
            . 'Hyvä imports the file and scans none of this package\'s templates'
        );
    }

    public function testTheSourceGlobReachesEveryTemplateThisPackageShips(): void
    {
        $directives = self::directives(self::moduleCss());

        preg_match_all('/@source\s+"([^"]+)"/', $directives, $m);
        $globs = $m[1];
        self::assertNotEmpty($globs, 'no quoted @source glob to check');

        $templates = glob(self::packageRoot() . '/view/frontend/templates/*/*.phtml') ?: [];
        // A floor, not an exact count: the point is that an empty search space
        // cannot pass. Two templates ship in this package (cart/ and checkout/);
        // if the count ever drops to zero the loop below is trivially satisfied.
        self::assertGreaterThanOrEqual(
            2,
            count($templates),
            'fewer templates found than this package ships - the search space is wrong'
        );

        $base = self::packageRoot() . '/view/frontend/tailwind/';
        foreach ($templates as $template) {
            $covered = false;
            foreach ($globs as $glob) {
                foreach (glob($base . $glob) ?: [] as $hit) {
                    if (realpath($hit) === realpath($template)) {
                        $covered = true;
                        break 2;
                    }
                }
            }
            self::assertTrue(
                $covered,
                sprintf(
                    '%s is not reached by any @source glob (%s), so every utility used only there '
                    . 'is absent from the compiled stylesheet',
                    basename($template),
                    implode(', ', $globs)
                )
            );
        }
    }

    /**
     * module.css says the glob is SUFFICIENT because every class in these templates is written
     * literally in a class="..." attribute - no `:class`, no `x-bind:class`, no `classList` - and
     * so no `@source inline()` entries are needed. That is a claim about the templates, made in
     * a CSS comment nobody re-reads when a template changes. A class composed inside an Alpine
     * expression is invisible to the scanner and renders unstyled, exactly like a missing
     * `@source`. Pinned here so the comment cannot silently become false.
     */
    public function testEveryClassIsLiteralSoTheGlobAloneIsSufficient(): void
    {
        $templates = glob(self::packageRoot() . '/view/frontend/templates/*/*.phtml') ?: [];
        self::assertGreaterThanOrEqual(2, count($templates), 'the template search space is empty');

        $literal = 0;
        foreach ($templates as $template) {
            $markup = (string) file_get_contents($template);
            $literal += preg_match_all('/\sclass="/', $markup);
            self::assertDoesNotMatchRegularExpression(
                self::COMPOSED_CLASS,
                $markup,
                basename(dirname($template)) . '/' . basename($template) . ' composes a class '
                . 'dynamically. The scanner cannot see it: add an @source inline() for it in '
                . 'module.css and correct that file\'s comment, which says there are none.'
            );
        }

        // CONTROL on the search space: the templates must actually carry literal classes, or
        // "none composed" is a statement about files with no markup in them.
        self::assertGreaterThanOrEqual(10, $literal, 'fewer literal class attributes than these templates carry');

        // CONTROL on the predicate: each composed form must be caught.
        foreach (['<div :class="{ \'x\': y }">', '<p x-bind:class="z">', 'el.classList.add(\'b\')'] as $specimen) {
            self::assertMatchesRegularExpression(self::COMPOSED_CLASS, $specimen, "CONTROL: {$specimen} was not caught");
        }
    }

    /** Tailwind's own palette. A class built on any other colour name needs a theme to define it. */
    private const CORE_COLOUR_FAMILIES = [
        'slate', 'gray', 'zinc', 'neutral', 'stone', 'red', 'orange', 'amber', 'yellow', 'lime', 'green',
        'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose',
    ];

    private const CORE_COLOUR_KEYWORDS = ['white', 'black', 'transparent', 'current', 'inherit'];

    /** First words after a colour-capable prefix that name something other than a colour (text-sm, border-t). */
    private const NOT_A_COLOUR = [
        'xs', 'sm', 'base', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', '8xl', '9xl',
        'left', 'center', 'right', 'justify', 'start', 'end', 't', 'b', 'l', 'r', 'x', 'y', 's', 'e',
        'none', 'solid', 'dashed', 'dotted', 'double', 'hidden', 'collapse', 'separate', 'wrap', 'nowrap',
        'balance', 'pretty', 'ellipsis', 'clip', 'auto', 'fixed', 'local', 'scroll', 'cover', 'contain',
        'repeat', 'no', 'top', 'bottom', 'inner', 'opacity', 'linear', 'radial', 'conic', 'gradient', 'offset',
    ];

    private const CLASS_ATTRIBUTE = '/\sclass=(["\'])(.*?)\1/s';

    private const COLOUR_PREFIX = '/^(?:bg|border|text|ring|divide|outline|fill|stroke|from|via|to|placeholder|accent|caret|decoration|shadow)-(.+)$/';

    /**
     * Colour utilities in $tokens that core Tailwind does not define. 1.1.1's cart panel used
     * `bg-container-lighter` / `border-container-lighter`. Hyvä's default theme defines `container-*` only
     * in its deprecated fallback.css, and a child theme that does not import that file has no rule for them. The panel then rendered with no background or border colour, and nothing
     * reported an error. A module sold for any Hyvä theme may use only colours every Tailwind build has.
     *
     * @param list<string> $tokens
     * @return list<string>
     */
    private static function nonCoreColourUtilities(array $tokens): array
    {
        $bad = [];
        foreach ($tokens as $token) {
            // Drop variants (hover:, md:), the important marker and an opacity modifier (/50).
            $bare = (string) preg_replace(['/^(?:[a-z0-9-]+:)+/', '/^!/', '/\/(?:\d+|\[[^\]]+\])$/'], '', $token);
            if (!preg_match(self::COLOUR_PREFIX, $bare, $m)) {
                continue;
            }
            $rest = $m[1];
            if (str_contains($rest, 'container')) {
                $bad[] = $token;
                continue;
            }
            if (preg_match('/^\d|^\[/', $rest) === 1) {
                continue;
            }
            $word = explode('-', $rest)[0];
            if (in_array($word, self::NOT_A_COLOUR, true) || in_array($rest, self::CORE_COLOUR_KEYWORDS, true)) {
                continue;
            }
            if (in_array($word, self::CORE_COLOUR_FAMILIES, true) && preg_match('/^[a-z]+-\d{2,3}$/', $rest) === 1) {
                continue;
            }
            $bad[] = $token;
        }

        return $bad;
    }

    public function testEveryColourUtilityIsOneEveryTailwindBuildDefines(): void
    {
        $templates = glob(self::packageRoot() . '/view/frontend/templates/*/*.phtml') ?: [];
        self::assertGreaterThanOrEqual(2, count($templates), 'the template search space is empty');

        $tokens = [];
        foreach ($templates as $template) {
            // Either quote style: a single-quoted attribute was invisible to the first version of this scan.
            preg_match_all(self::CLASS_ATTRIBUTE, (string) file_get_contents($template), $m);
            foreach ($m[2] as $attr) {
                foreach (preg_split('/\s+/', trim($attr)) ?: [] as $token) {
                    if ($token !== '') {
                        $tokens[] = $token;
                    }
                }
            }
        }

        // CONTROL on the search space: the templates carry colour utilities, so "none bad" means something.
        $colour = array_filter($tokens, static fn (string $t): bool => preg_match(self::COLOUR_PREFIX, $t) === 1);
        self::assertGreaterThanOrEqual(8, count($colour), 'fewer colour-prefixed utilities than these templates carry');

        self::assertSame(
            [],
            self::nonCoreColourUtilities($tokens),
            'these colour utilities exist only in themes that define them. Use a core Tailwind '
            . 'colour (gray-300, white, ...) so the class has a rule in every Hyvä theme.'
        );
    }

    /** The predicate's control: theme tokens are caught, core utilities and non-colour utilities are not. */
    public function testTheColourCheckTellsThemeTokensFromCoreUtilities(): void
    {
        $themeTokens = ['bg-container-lighter', 'border-container-lighter', 'hover:bg-container', 'bg-primary', 'text-fg', 'border-mist'];
        self::assertSame($themeTokens, self::nonCoreColourUtilities($themeTokens), 'CONTROL: a theme-only colour was not caught');

        $core = [
            'bg-white', 'border-gray-300', 'text-red-600', 'hover:text-green-600', 'bg-black/50', 'border-transparent',
            'text-sm', 'text-xs', 'text-left', 'border', 'border-t', 'border-2', 'ring-2', 'rounded-lg', 'p-5',
            'bg-[#fff]', 'text-current', 'bg-white/[.15]',
        ];
        self::assertSame([], self::nonCoreColourUtilities($core), 'CONTROL: a core or non-colour utility was rejected');

        // CONTROL on the attribute scan: both quote styles are read.
        preg_match_all(self::CLASS_ATTRIBUTE, '<div class="bg-a"><p class=\'bg-b\'>', $m);
        self::assertSame(['bg-a', 'bg-b'], $m[2], 'CONTROL: a class attribute in one of the two quote styles was not read');
    }

    /**
     * The control. A glob is a claim about a search space, and a claim about a
     * search space is worth nothing until it has been seen to exclude something.
     */
    public function testTheGlobCheckRejectsATemplateOutsideIt(): void
    {
        $directives = self::directives(self::moduleCss());
        preg_match_all('/@source\s+"([^"]+)"/', $directives, $m);
        $base = self::packageRoot() . '/view/frontend/tailwind/';

        $planted = self::packageRoot() . '/view/frontend/templates/../layout';
        $outside = realpath($planted . '/hyva_checkout_cart_index.xml');
        self::assertIsString($outside, 'the specimen this control needs does not exist');

        $reached = false;
        foreach ($m[1] as $glob) {
            foreach (glob($base . $glob) ?: [] as $hit) {
                if (realpath($hit) === $outside) {
                    $reached = true;
                }
            }
        }

        self::assertFalse(
            $reached,
            'the @source glob matches a layout XML file, so it is broader than intended and '
            . 'this test cannot distinguish a covered template from an uncovered one'
        );
    }

    /**
     * The comment-stripping control: prove the stripper removes a planted
     * `@source` inside a comment, so the first test above cannot pass on prose.
     */
    public function testACommentedOutSourceDoesNotCountAsADeclaration(): void
    {
        $specimen = "/*\n * See the @source directive.\n */\n";
        self::assertDoesNotMatchRegularExpression(
            '/@source\b/',
            self::directives($specimen),
            'the comment stripper leaves @source mentions inside comments, which is exactly how '
            . 'the original defect read as fixed'
        );

        $specimen .= '@source "../templates/**/*.phtml";' . "\n";
        self::assertMatchesRegularExpression(
            '/@source\b/',
            self::directives($specimen),
            'the comment stripper removed a real directive'
        );
    }

    /**
     * The control for the control. A globstar path contains `/*` and `*​/`, so a
     * naive block-comment strip silently truncates it - and the truncated glob
     * still matches SOMETHING, which is why the damage reads as a finding about
     * the package rather than about the stripper.
     */
    public function testTheStripperDoesNotEatAGlobstarPath(): void
    {
        $specimen = '@source "../templates/**/*.phtml";';

        self::assertStringContainsString(
            '../templates/**/*.phtml',
            self::directives($specimen),
            'the comment stripper mangled a globstar path'
        );

        // And the naive form, kept here so the difference is demonstrated rather
        // than asserted: this is what the first version of this test did.
        self::assertSame(
            '@source "../templates*.phtml";',
            (string) preg_replace('!/\*.*?\*/!s', '', $specimen),
            'the naive stripper no longer truncates the glob, so this control has '
            . 'stopped demonstrating anything and should be re-aimed or removed'
        );
    }
}
