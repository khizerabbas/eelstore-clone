<?php

namespace Tests\Feature;

use App\Filament\Pages\Backups;
use App\Models\ErrorLog;
use App\Models\User;
use App\Support\Content;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CspAndBackupsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_pages_send_a_content_security_policy(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString('frame-src \'self\' https://www.google.com', $csp);
        $this->assertStringContainsString('report-uri ' . route('csp-report'), $csp);
        $this->assertStringNotContainsString('googletagmanager', $csp); // analytics hosts only when configured

        $this->get('/admin/login')->assertHeader('Content-Security-Policy');
    }

    public function test_analytics_hosts_are_added_when_configured(): void
    {
        Content::saveGroup('seo', ['ga4_id' => 'G-TEST123', 'meta_pixel_id' => '1234567890']);

        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://connect.facebook.net', $csp);
    }

    public function test_report_only_mode(): void
    {
        config(['security.csp_report_only' => true]);

        $this->get('/')
            ->assertHeader('Content-Security-Policy-Report-Only')
            ->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_violation_reports_go_to_the_error_log(): void
    {
        $report = fn (string $blocked) => $this->call('POST', route('csp-report'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => ['blocked-uri' => $blocked, 'effective-directive' => 'script-src-elem', 'document-uri' => 'https://shop.pk/checkout'],
        ]));

        $report('https://evil.example/x.js')->assertNoContent();
        $report('chrome-extension://abc/inject.js')->assertNoContent(); // extension noise ignored

        $log = ErrorLog::sole();
        $this->assertSame('CSP violation', $log->type);
        $this->assertSame('warning', $log->level);
        $this->assertSame('Blocked https://evil.example/x.js (script-src-elem)', $log->message);
        $this->assertSame('https://shop.pk/checkout', $log->url);
    }

    public function test_backups_page_is_owner_only_and_lists_downloads(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('site-backups/2026-10-24-01-30-00.zip', 'zip-content');

        $this->actingAs(User::factory()->create());
        $this->get(Backups::getUrl())->assertForbidden();

        $this->actingAs(User::factory()->owner()->create());
        $this->get(Backups::getUrl())->assertOk()->assertSee('2026-10-24-01-30-00.zip');

        Livewire::test(Backups::class)
            ->call('download', 'site-backups/2026-10-24-01-30-00.zip')
            ->assertFileDownloaded('2026-10-24-01-30-00.zip');

        Livewire::test(Backups::class)
            ->call('download', '../../.env')
            ->assertNotFound();
    }

    public function test_back_up_now_queues_a_backup(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(Backups::class)->callAction('backupNow');

        Queue::assertPushed(\Illuminate\Foundation\Console\QueuedCommand::class);
    }
}
