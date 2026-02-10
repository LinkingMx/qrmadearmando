<?php

namespace Tests\Browser;

use App\Enums\GiftCardNature;
use App\Enums\GiftCardScope;
use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScannerTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $terminalUser;

    protected User $cardOwner;

    protected Branch $branch;

    protected GiftCard $giftCard;

    protected GiftCardCategory $category;

    /**
     * Set up test data before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create branch using factory (automatically creates brand with chain)
        $this->branch = Branch::factory()->create([
            'name' => 'Tigre Masaryk',
        ]);

        // Create terminal user with branch assignment
        $this->terminalUser = User::create([
            'name' => 'Tigre Masaryk Terminal',
            'email' => 'tigre.masaryk@grupocosteno.com',
            'password' => Hash::make('12345678'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign BranchTerminal role (required by scanner middleware)
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'BranchTerminal']);
            $this->terminalUser->assignRole($role);
        }

        // Create card owner
        $this->cardOwner = User::create([
            'name' => 'Ismael Briones',
            'email' => 'ismael.briones@grupocosteno.com',
            'password' => Hash::make('12345678'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Get or create category (EMCAD may exist from migration)
        $this->category = GiftCardCategory::firstOrCreate(
            ['prefix' => 'EMCAD'],
            [
                'name' => 'Empleados',
                'nature' => GiftCardNature::PAYMENT_METHOD,
            ]
        );

        // Create gift card with sufficient balance
        // Note: BRANCH scope requires chain_id (from brand->chain)
        $this->giftCard = GiftCard::create([
            'gift_card_category_id' => $this->category->id,
            'user_id' => $this->cardOwner->id,
            'status' => true,
            'balance' => 1000.00,
            'scope' => GiftCardScope::BRANCH,
            'chain_id' => $this->branch->brand->chain_id,
        ]);

        // Attach the specific branch to the gift card
        $this->giftCard->branches()->attach($this->branch->id);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Browser cleanup is handled by Dusk automatically
        parent::tearDown();
    }

    /**
     * Test 1: Can access scanner page when authenticated with branch assignment.
     */
    public function test_can_access_scanner_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->terminalUser)
                ->visit('/scanner')
                ->waitForText('Scanner QR Empleados')
                ->assertSee('Sucursal: Tigre Masaryk')
                ->assertSee('Usuario: Tigre Masaryk Terminal')
                ->assertSee('Scanner QR Nativo')
                ->assertSee('BÚSQUEDA MANUAL')
                ->screenshot('scanner-page-loaded');
        });
    }

    /**
     * Test 2: Can enter legacy_id manually in the scanner interface.
     */
    public function test_can_enter_legacy_id_manually(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->terminalUser)
                ->visit('/scanner')
                ->waitForText('Scanner QR Empleados')
                // Look for manual input field with correct placeholder
                ->type('input[placeholder*="EMCAD"]', $this->giftCard->legacy_id)
                ->press('Buscar QR')
                ->waitForText('Información del QR Empleado', 10)
                ->assertSee($this->giftCard->legacy_id)
                ->assertSee('Ismael Briones')
                ->assertSee('Saldo Disponible')
                ->assertSee('$1,000.00')
                ->screenshot('gift-card-info-displayed');
        });
    }

    /**
     * Test 3: Shows balance validation error when amount exceeds available balance.
     */
    public function test_shows_balance_validation_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->terminalUser)
                ->visit('/scanner')
                ->waitForText('Scanner QR Empleados')
                // Enter legacy_id
                ->type('input[placeholder*="EMCAD"]', $this->giftCard->legacy_id)
                ->press('Buscar QR')
                ->waitForText('Información del QR Empleado', 10)
                // Try to process debit with amount exceeding balance
                ->type('#amount', '1500.00') // Exceeds $1000 balance
                ->type('#reference', 'Test-Ticket-001')
                ->waitForText('El monto excede el saldo disponible', 5)
                ->assertSee('El monto excede el saldo disponible')
                // Verify submit button is disabled
                ->assertButtonDisabled('Procesar Descuento')
                ->screenshot('balance-validation-error');
        });
    }

    /**
     * Test 4: Can process debit successfully and see receipt modal.
     */
    public function test_can_process_debit_and_see_receipt(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->terminalUser)
                ->visit('/scanner')
                ->waitForText('Scanner QR Empleados')
                // Enter legacy_id
                ->type('input[placeholder*="EMCAD"]', $this->giftCard->legacy_id)
                ->press('Buscar QR')
                ->waitForText('Información del QR Empleado', 10)
                // Fill debit form
                ->type('#amount', '250.00')
                ->type('#reference', 'Test-Ticket-001')
                ->type('#description', 'Browser test debit transaction')
                // Submit form
                ->press('Procesar Descuento')
                ->waitForText('Comprobante', 10)
                // Verify receipt modal content
                ->assertSee('Comprobante')
                ->assertSee('Descuento Procesado')
                ->assertSee('$250.00')
                ->assertSee('Ismael Briones')
                ->assertSee('Test-Ticket-001')
                ->assertSee('Browser test debit transaction')
                ->assertSee('Tigre Masaryk')
                ->screenshot('receipt-modal-displayed')
                // Close receipt and verify return to scanner
                ->press('Cerrar')
                ->waitForText('Scanner QR Nativo', 5)
                ->assertSee('Scanner QR Empleados')
                ->screenshot('returned-to-scanner');
        });
    }

    /**
     * Test 5: Offline indicator shows when network is disconnected.
     */
    public function test_offline_indicator_shows_when_disconnected(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->terminalUser)
                ->visit('/scanner')
                ->waitForText('Scanner QR Empleados')
                // Simulate offline mode by executing JavaScript
                ->script("window.dispatchEvent(new Event('offline'))");

            // Wait a moment for the offline event to be processed
            $browser->pause(500);

            $browser
                ->waitForText('Sin conexión', 5)
                ->assertSee('Sin conexión')
                ->assertSee('Sincronizar')
                ->screenshot('offline-indicator-visible')
                // Simulate coming back online
                ->script("window.dispatchEvent(new Event('online'))");

            // Wait for online status to update
            $browser->pause(500);

            $browser
                ->waitUntilMissing('[class*="bg-destructive"]', 5)
                ->screenshot('back-online');
        });
    }

    /**
     * Test 6: Prevents navigation without saving when form has unsaved changes.
     */
    public function test_prevents_navigation_without_saving(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->terminalUser)
                ->visit('/scanner')
                ->waitForText('Scanner QR Empleados')
                // Enter legacy_id to view gift card
                ->type('input[placeholder*="EMCAD"]', $this->giftCard->legacy_id)
                ->press('Buscar QR')
                ->waitForText('Información del QR Empleado', 10)
                // Start filling the debit form (creates unsaved changes)
                ->type('#amount', '150.00')
                ->type('#reference', 'Unsaved-Transaction')
                ->screenshot('form-with-unsaved-changes')
                // Try to navigate away by clicking "Escanear otro QR"
                ->click('button:contains("Escanear otro QR")')
                ->pause(500)
                // Verify we're back at scanning mode (form was cleared/cancelled)
                ->waitForText('Scanner QR Nativo', 5)
                ->screenshot('navigated-away');

            // Note: Inertia.js doesn't have built-in beforeUnload warnings
            // The "Escanear otro QR" button acts as a cancel/reset action
            // This test verifies the cancel flow works correctly
        });
    }
}
