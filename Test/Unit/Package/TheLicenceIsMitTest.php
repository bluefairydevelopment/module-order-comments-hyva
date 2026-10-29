<?php

/**
 * Copyright (c) 2026 Blue Fairy Development LLC. MIT licence; see LICENSE.txt.
 */

declare(strict_types=1);

namespace BlueFairy\OrderCommentsHyva\Test\Unit\Package;

use PHPUnit\Framework\TestCase;

/**
 * The Hyvä companions are free and open: the package ships the MIT licence and composer.json
 * says so. The base module it extends stays under the house EULA.
 */
class TheLicenceIsMitTest extends TestCase
{
    private const MIT_SENTENCES = [
        'MIT License',
        'Copyright (c) 2026 Blue Fairy Development LLC',
        'Permission is hereby granted, free of charge, to any person obtaining a copy',
        'The above copyright notice and this permission notice shall be included in all',
        'THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR',
    ];

    private const EULA_MARKERS = ['END USER LICENSE AGREEMENT', 'It is licensed, not sold.', 'proprietary'];

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function licence(): string
    {
        $found = glob(self::root() . '/LICEN[CS]E*') ?: [];
        self::assertSame(['LICENSE.txt'], array_map('basename', $found), 'expected exactly one licence file, LICENSE.txt');

        return (string) file_get_contents($found[0]);
    }

    /** @param list<string> $required @param list<string> $forbidden */
    private static function isMit(string $text, array $required = self::MIT_SENTENCES, array $forbidden = self::EULA_MARKERS): bool
    {
        foreach ($required as $sentence) {
            if (!str_contains($text, $sentence)) {
                return false;
            }
        }
        foreach ($forbidden as $marker) {
            if (stripos($text, $marker) !== false) {
                return false;
            }
        }

        return true;
    }

    public function testComposerDeclaresMit(): void
    {
        $composer = json_decode((string) file_get_contents(self::root() . '/composer.json'), true);
        self::assertSame(['MIT'], (array) ($composer['license'] ?? []));
    }

    public function testTheShippedTextIsTheMitLicence(): void
    {
        self::assertTrue(self::isMit(self::licence()), 'LICENSE.txt is not the MIT licence for Blue Fairy Development LLC');
    }

    /** No shipped or test file carries a notice that contradicts the MIT grant. */
    public function testNoFileReservesAllRights(): void
    {
        $checked = 0;
        $walk = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root(), \FilesystemIterator::SKIP_DOTS));
        foreach ($walk as $entry) {
            $path = $entry->getPathname();
            if (!$entry->isFile() || str_contains($path, '/.git/') || str_contains($path, '/vendor/')) {
                continue;
            }
            $checked++;
            self::assertStringNotContainsStringIgnoringCase(
                'all rights ' . 'reserved',
                (string) file_get_contents($path),
                substr($path, strlen(self::root()) + 1) . ' reserves all rights, contradicting LICENSE.txt'
            );
        }
        self::assertGreaterThanOrEqual(15, $checked, 'the walk examined too few files to mean anything');
    }

    public function testControlsTheEulaAndAnEmptyFileAreNotMit(): void
    {
        self::assertFalse(self::isMit(''));
        self::assertFalse(self::isMit(self::licence() . "\nEND USER LICENSE AGREEMENT"));
        self::assertFalse(self::isMit(str_replace('Blue Fairy Development LLC', 'Someone Else', self::licence())));
    }
}
