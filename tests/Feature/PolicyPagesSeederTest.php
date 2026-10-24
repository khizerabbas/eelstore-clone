<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PolicyPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyPagesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_pages_are_published_and_linked_in_the_footer(): void
    {
        $this->seed(PolicyPagesSeeder::class);

        $this->assertSame(3, Page::where('is_published', true)->where('show_in_footer', true)->count());

        $this->get('/')->assertSee(route('pages.show', 'privacy-policy'), false);
        $this->get('/pages/delivery-and-returns')->assertOk()->assertSee('cash on delivery');
        $this->get('/pages/terms-and-conditions')->assertOk()->assertSee('href="delivery-and-returns"', false);
    }

    public function test_running_it_again_keeps_the_owners_edits(): void
    {
        $this->seed(PolicyPagesSeeder::class);
        Page::where('slug', 'privacy-policy')->update(['body' => '<p>Edited by the owner</p>']);

        $this->seed(PolicyPagesSeeder::class);

        $this->assertSame('<p>Edited by the owner</p>', Page::where('slug', 'privacy-policy')->value('body'));
        $this->assertSame(3, Page::count());
    }
}
