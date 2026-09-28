<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Calendar\Localization\LexiconRepository;
use Kodhe\Framework\Calendar\Localization\LocalLexicon;

/**
 * Tests for localization classes
 */
class LocalizationTest extends TestCase
{
    public function testSingletonInstance(): void
    {
        $a = LexiconRepository::getInstance();
        $b = LexiconRepository::getInstance();
        $this->assertSame($a, $b);
    }

    public function testMonthNameEnglish(): void
    {
        $repo = LexiconRepository::getInstance();
        $this->assertSame('March', $repo->monthName(3, 'en', 'long'));
        $this->assertSame('Mar', $repo->monthName(3, 'en', 'short'));
    }

    public function testMonthNameIndonesian(): void
    {
        $repo = LexiconRepository::getInstance();
        $this->assertSame('Januari', $repo->monthName(1, 'id', 'long'));
        $this->assertSame('Agustus', $repo->monthName(8, 'id', 'long'));
    }

    public function testMonthNameOutOfRange(): void
    {
        $repo = LexiconRepository::getInstance();
        $this->assertSame('', $repo->monthName(13, 'en', 'long'));
        $this->assertSame('', $repo->monthName(0, 'en', 'long'));
    }

    public function testDayNames(): void
    {
        $repo = LexiconRepository::getInstance();
        $days = $repo->dayNames('long', 'en');
        $this->assertCount(7, $days);
        $this->assertSame('Sunday', $days[0]);

        $frenchDays = $repo->dayNames('long', 'fr');
        $this->assertSame('dimanche', $frenchDays[0]);
    }

    public function testUnknownLocaleFallsBackToEnglish(): void
    {
        $lexicon = new LocalLexicon('xx');
        $months = $lexicon->months('long');
        $this->assertSame('January', $months[0]);
    }

    public function testLocalLexiconTypes(): void
    {
        $lexicon = new LocalLexicon('de');
        $this->assertSame('Montag', $lexicon->days('long')[1]);
        $this->assertSame('Mo', $lexicon->days('abr')[1]);
        // Unknown type falls back to default list
        $this->assertNotEmpty($lexicon->days('unknown-type'));
    }

    public function testClearResetsCache(): void
    {
        $repo = LexiconRepository::getInstance();
        $repo->monthName(5, 'es', 'long'); // loads 'es'
        $repo->clear();
        // After clear, loading again still works
        $this->assertSame('mayo', $repo->monthName(5, 'es', 'long'));
    }
}
