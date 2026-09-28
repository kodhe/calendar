<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Calendar\Generators\MonthGenerator;

/**
 * Tests for MonthGenerator
 */
class MonthGeneratorTest extends TestCase
{
    private MonthGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new MonthGenerator();
    }

    public function testAdjustDateKeepsValidRange(): void
    {
        [$year, $month] = $this->generator->adjustDate(6, 2024);
        $this->assertSame(2024, $year);
        $this->assertSame(6, $month);
    }

    public function testAdjustDateWrapsForward(): void
    {
        [$year, $month] = $this->generator->adjustDate(13, 2024);
        $this->assertSame(2025, $year);
        $this->assertSame(1, $month);

        [$year, $month] = $this->generator->adjustDate(24, 2024);
        $this->assertSame(2025, $year);
        $this->assertSame(12, $month);
    }

    public function testAdjustDateWrapsBackward(): void
    {
        [$year, $month] = $this->generator->adjustDate(0, 2024);
        $this->assertSame(2023, $year);
        $this->assertSame(12, $month);

        [$year, $month] = $this->generator->adjustDate(-11, 2024);
        $this->assertSame(2023, $year);
        $this->assertSame(1, $month);
    }

    public function testTotalDays(): void
    {
        $this->assertSame(31, $this->generator->totalDays(1, 2024));
        $this->assertSame(29, $this->generator->totalDays(2, 2024));
        $this->assertSame(28, $this->generator->totalDays(2, 2023));
        $this->assertSame(30, $this->generator->totalDays(9, 2024));
    }

    public function testGetStartDaySundayWeek(): void
    {
        // June 1, 2024 is a Saturday (wday = 6): start offset = 0 + 1 - 6 = -5
        $start = $this->generator->getStartDay(6, 2024, 'sunday');
        $this->assertSame(-5, $start);
    }

    public function testGetStartDayMondayWeek(): void
    {
        // With monday-based week the offset shifts by one: 1 + 1 - 6 = -4
        $start = $this->generator->getStartDay(6, 2024, 'monday');
        $this->assertSame(-4, $start);
    }

    public function testGetStartDayWhenMonthStartsOnWeekStart(): void
    {
        // July 1, 2024 is a Monday -> with sunday start: 0 + 1 - 1 = 0
        $this->assertSame(0, $this->generator->getStartDay(7, 2024, 'sunday'));
        // August 1, 2024 is a Thursday; with tuesday start: 2 + 1 - 4 = -1 => +6? no: while > 1 loop keeps -1
        $this->assertSame(-1, $this->generator->getStartDay(8, 2024, 'tuesday'));
    }

    public function testBuildReturnsStructure(): void
    {
        $structure = $this->generator->build(2024, 6, ['start_day' => 'sunday']);

        $this->assertSame(2024, $structure['year']);
        $this->assertSame(6, $structure['month']);
        $this->assertSame(30, $structure['total_days']);
        $this->assertTrue(isset($structure['weeks']));
        $this->assertTrue(count($structure['weeks']) >= 4);
    }

    public function testBuildCachesStructure(): void
    {
        $a = $this->generator->build(2024, 3, []);
        $b = $this->generator->build(2024, 3, []);
        $this->assertSame($a, $b);
    }

    public function testBuildWeeksContainsAllDays(): void
    {
        // February 2024 starts on a Thursday -> weeks[0] holds days 1..4, then full weeks of 7
        $structure = $this->generator->build(2024, 2, ['start_day' => 'sunday']);

        $found = [];
        foreach ($structure['weeks'] as $week) {
            foreach ($week as $cell) {
                if (($cell['type'] ?? '') === 'current') {
                    $found[] = $cell['day'];
                }
            }
        }

        sort($found);
        $expected = range(1, 29);
        $this->assertEquals($expected, array_values(array_unique($found)));
    }

    public function testClearCacheAllowsRebuild(): void
    {
        MonthGenerator::clearCache();
        $a = $this->generator->build(2024, 5, []);
        MonthGenerator::clearCache();
        $b = $this->generator->build(2024, 5, []);
        $this->assertSame($a, $b);
    }
}
