<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Calendar\Renderers\JsonRenderer;
use Kodhe\Framework\Calendar\Renderers\HtmlTableRenderer;

/**
 * Tests for calendar renderers
 */
class RendererTest extends TestCase
{
    private function structure(int $year = 2024, int $month = 6): array
    {
        $gen = new \Kodhe\Framework\Calendar\Generators\MonthGenerator();
        return $gen->build($year, $month, ['start_day' => 'sunday']);
    }

    public function testJsonRendererEncodesStructure(): void
    {
        $renderer = new JsonRenderer();
        $json = $renderer->render($this->structure(), [], ['start_day' => 'sunday']);

        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertSame(2024, $decoded['year']);
        $this->assertSame(6, $decoded['month']);
        $this->assertSame(30, $decoded['total_days']);
        $this->assertTrue($decoded['weeks'] >= 4);
        $this->assertSame([], $decoded['events']);
    }

    public function testJsonRendererFormatsArrayEvents(): void
    {
        $renderer = new JsonRenderer();
        $json = $renderer->render($this->structure(), [
            5 => ['title' => 'Meeting', 'url' => 'https://example.com/meeting', 'description' => 'Weekly sync'],
        ], []);

        $decoded = json_decode($json, true);
        $event = $decoded['events'][0];
        $this->assertSame('2024-06-05', $event['date']);
        $this->assertSame('Meeting', $event['title']);
        $this->assertSame('https://example.com/meeting', $event['url']);
        $this->assertSame('Weekly sync', $event['description']);
    }

    public function testJsonRendererFormatsScalarEvents(): void
    {
        $renderer = new JsonRenderer();
        $json = $renderer->render($this->structure(), [7 => 'Holiday'], []);

        $decoded = json_decode($json, true);
        $this->assertSame('Holiday', $decoded['events'][0]['title']);
        $this->assertNull($decoded['events'][0]['url']);
    }

    public function testJsonRendererDefaultTemplateIsEmpty(): void
    {
        $renderer = new JsonRenderer();
        $this->assertSame([], $renderer->defaultTemplate());
    }

    public function testHtmlRendererProducesTable(): void
    {
        $renderer = new HtmlTableRenderer();
        $html = $renderer->render(
            $this->structure(),
            [],
            ['local_time' => mktime(12, 0, 0, 6, 15, 2024), 'start_day' => 'sunday']
        );

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('June', $html);
        $this->assertStringContainsString('2024', $html);
        // Day 15 is the "current" day -> rendered with today template (strong tag)
        $this->assertStringContainsString('<strong>15</strong>', $html);
    }

    public function testHtmlRendererCustomArrayTemplate(): void
    {
        $renderer = new HtmlTableRenderer();
        $html = $renderer->render(
            $this->structure(),
            [],
            [
                'local_time' => mktime(12, 0, 0, 6, 15, 2024),
                'template'   => ['table_open' => '<table class="my-cal">'],
            ]
        );

        $this->assertStringContainsString('<table class="my-cal">', $html);
    }

    public function testHtmlRendererDefaultTemplateHasKeys(): void
    {
        $renderer = new HtmlTableRenderer();
        $template = $renderer->defaultTemplate();

        $this->assertNotEmpty($template);
        $this->assertTrue(isset($template['table_open']));
        $this->assertTrue(isset($template['heading_title_cell']));
    }
}
