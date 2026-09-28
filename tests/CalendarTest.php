<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Calendar\Calendar;

/**
 * Tests for the main Calendar class
 */
class CalendarTest extends TestCase
{
    private Calendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        // Fix "current" date so HTML highlighting is deterministic (2024-06-15)
        $this->calendar = new Calendar([
            'local_time' => mktime(12, 0, 0, 6, 15, 2024),
        ]);
    }

    public function testGetMonthNameLong(): void
    {
        $this->assertSame('January', $this->calendar->getMonthName(1));
        $this->assertSame('December', $this->calendar->getMonthName(12));
    }

    public function testGetMonthNameSnakeCaseAlias(): void
    {
        $this->assertSame('June', $this->calendar->get_month_name(6));
    }

    public function testGetMonthNameShortType(): void
    {
        $cal = new Calendar(['month_type' => 'short']);
        $this->assertSame('Feb', $cal->getMonthName(2));
    }

    public function testGetDayNames(): void
    {
        $days = $this->calendar->getDayNames('abr');
        $this->assertCount(7, $days);
        $this->assertSame('Su', $days[0]);
        $this->assertSame('Sa', $days[6]);

        $longDays = $this->calendar->getDayNames('long');
        $this->assertSame('Sunday', $longDays[0]);
    }

    public function testGetDayNamesSnakeCaseAlias(): void
    {
        $days = $this->calendar->get_day_names('long');
        $this->assertSame('Monday', $days[1]);
    }

    public function testAdjustDateOverflow(): void
    {
        $result = $this->calendar->adjustDate(13, 2024);
        $this->assertSame(['month' => '01', 'year' => 2025], $result);

        $result = $this->calendar->adjustDate(0, 2024);
        $this->assertSame(['month' => '12', 'year' => 2023], $result);
    }

    public function testAdjustDateSnakeCaseAlias(): void
    {
        $result = $this->calendar->adjust_date(25, 2024);
        $this->assertSame(['month' => '01', 'year' => 2026], $result);
    }

    public function testGetTotalDays(): void
    {
        $this->assertSame(31, $this->calendar->getTotalDays(1, 2024));
        $this->assertSame(29, $this->calendar->getTotalDays(2, 2024)); // leap year
        $this->assertSame(28, $this->calendar->getTotalDays(2, 2023)); // non-leap year
        $this->assertSame(30, $this->calendar->getTotalDays(4, 2024));
    }

    public function testGetLastDay(): void
    {
        $this->assertSame(31, $this->calendar->getLastDay(12, 2024));
        $this->assertSame(29, $this->calendar->get_last_day(2, 2024));
    }

    public function testGetTotalWeeks(): void
    {
        // June 2024 starts on a Saturday (start_day sunday) -> 5 weeks
        $weeks = $this->calendar->getTotalWeeks(6, 2024);
        $this->assertTrue($weeks >= 5 && $weeks <= 6);
        $this->assertTrue($this->calendar->get_total_weeks(2, 2024) >= 4);
    }

    public function testGenerateProducesHtmlTable(): void
    {
        $html = $this->calendar->generate(2024, 6);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('June&nbsp;2024', $html);
        $this->assertStringContainsString('</table>', $html);
        // Days of month should appear
        $this->assertStringContainsString('>1<', $html);
        $this->assertStringContainsString('>30<', $html);
    }

    public function testGenerateWithEventData(): void
    {
        // HtmlTableRenderer expects day => content (link URL or plain string)
        $html = $this->calendar->generate(2024, 6, [
            14 => 'https://example.com/event',
            20 => 'Simple event',
        ]);

        $this->assertStringContainsString('https://example.com/event', $html);
        $this->assertStringContainsString('Simple event', $html);
        $this->assertStringContainsString('<a href="https://example.com/event">14</a>', $html);
    }

    public function testAsJsonReturnsValidJson(): void
    {
        $json = $this->calendar->asJson(2024, 6, [
            1 => ['title' => 'Launch day', 'url' => 'https://example.com'],
        ]);

        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertSame(2024, $decoded['year']);
        $this->assertSame(6, $decoded['month']);
        $this->assertSame(30, $decoded['total_days']);
        $this->assertCount(1, $decoded['events']);
        $this->assertSame('2024-06-01', $decoded['events'][0]['date']);
        $this->assertSame('Launch day', $decoded['events'][0]['title']);
    }

    public function testDefaultTemplateIsNotEmpty(): void
    {
        $template = $this->calendar->default_template();
        $this->assertNotEmpty($template);
        $this->assertTrue(is_array($template));
    }

    public function testParseTemplateReturnsSelf(): void
    {
        $result = $this->calendar->parse_template();
        $this->assertSame($this->calendar, $result);
    }

    public function testShowNextPrevLinks(): void
    {
        $cal = new Calendar([
            'show_next_prev' => true,
            'next_prev_url'  => 'http://example.com/calendar/',
            'local_time'     => mktime(12, 0, 0, 6, 15, 2024),
        ]);

        $html = $cal->generate(2024, 6);
        $this->assertStringContainsString('http://example.com/calendar/2024/05', $html);
        $this->assertStringContainsString('http://example.com/calendar/2024/07', $html);
    }

    public function testConfigAccessors(): void
    {
        $this->assertSame('sunday', $this->calendar->getConfig('start_day'));
        $this->calendar->setConfig('start_day', 'monday');
        $this->assertSame('monday', $this->calendar->getConfig('start_day'));
        $this->assertNull($this->calendar->getConfig('does_not_exist'));
        $this->assertSame('fallback', $this->calendar->getConfig('does_not_exist', 'fallback'));
    }
}
