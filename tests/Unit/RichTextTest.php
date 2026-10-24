<?php

namespace Tests\Unit;

use App\Support\RichText;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    public function test_plain_text_keeps_line_breaks_and_is_escaped(): void
    {
        $this->assertSame('a &amp; b<br />' . "\n" . 'c', RichText::render("a & b\nc"));
    }

    public function test_html_is_sanitised(): void
    {
        $html = RichText::render('<p>Good <strong>wire</strong></p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">x</a>');

        $this->assertStringContainsString('<strong>wire</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }
}
