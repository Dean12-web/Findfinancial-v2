<?php

namespace Tests\Feature;

use App\Models\Organization\User;
use App\Services\BalanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AcceptPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestingSchema();

        $balanceMock = Mockery::mock(BalanceService::class);
        $balanceMock->shouldReceive('syncBankBalance')->byDefault();
        $balanceMock->shouldReceive('syncCustomerDeposit')->byDefault();
        $balanceMock->shouldReceive('syncCustomerPoints')->byDefault();
        $this->app->instance(BalanceService::class, $balanceMock);
    }

    private function createTestingSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('tb_user', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name')->nullable();
            $table->string('password');
            $table->string('role_user')->default('superadmin');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->boolean('can_bypass_date')->default(false);
        });

        Schema::create('tb_limitation_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->string('invoice')->nullable();
        });

        Schema::create('tb_store', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
        });

        Schema::create('tb_customer', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('deposit')->default(0);
        });

        Schema::create('tb_bank', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('Kas & Bank');
            $table->integer('balance')->default(0);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
        });

        Schema::create('tb_format_transaction', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('param_1')->nullable();
            $table->string('param_2')->nullable();
            $table->integer('start')->default(0);
            $table->integer('status')->default(1);
        });

        Schema::create('tb_store_order', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('customer_id')->default(0);
            $table->string('role_project')->default('seller');
            $table->string('role_store')->default('seller');
            $table->integer('total_amount')->default(0);
            $table->integer('poin')->default(0);
            $table->string('status')->default('OPEN');
            $table->date('billing_due_date')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('tags')->nullable();
        });

        Schema::create('view_store_order', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('customer_id')->default(0);
            $table->string('customer_name')->nullable();
            $table->string('role_project')->default('seller');
            $table->string('role_store')->default('seller');
            $table->integer('total_amount')->default(0);
            $table->float('paid')->default(0);
            $table->string('status')->default('OPEN');
            $table->date('billing_due_date')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('tags')->nullable();
        });

        Schema::create('tb_store_order_payment', function (Blueprint $table) {
            $table->id();
            $table->string('store_order_id');
            $table->string('order_id');
            $table->integer('amount')->default(0);
            $table->integer('sisa')->default(0);
            $table->string('notes', 30)->nullable();
            $table->unsignedBigInteger('id_bank_destination')->default(0);
            $table->unsignedBigInteger('id_bank')->default(0);
            $table->string('status')->default('approved');
            $table->date('date')->nullable();
            $table->string('updated_by', 50)->nullable();
        });

        Schema::create('tb_cash_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('code');
            $table->integer('debit')->default(0);
            $table->integer('credit')->default(0);
            $table->integer('balance')->default(0);
            $table->string('title', 100)->nullable();
            $table->string('memo', 50)->nullable();
            $table->unsignedBigInteger('id_bank')->default(0);
            $table->date('date')->nullable();
            $table->string('updated_by', 10)->nullable();
        });

        Schema::create('tb_deposit_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->unsignedBigInteger('project_id');
            $table->integer('debit')->default(0);
            $table->integer('credit')->default(0);
            $table->integer('balance')->default(0);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('id_bank')->default(0);
            $table->date('date')->nullable();
            $table->string('memo')->nullable();
            $table->string('tags')->nullable();
            $table->string('role_user')->nullable();
            $table->unsignedBigInteger('id_user')->default(0);
            $table->string('updated_by', 50)->nullable();
        });

        Schema::create('tb_trx_point', function (Blueprint $table) {
            $table->id();
            $table->string('transid', 50);
            $table->unsignedBigInteger('memberid');
            $table->string('status', 5);
            $table->integer('amount')->default(0);
        });
    }

    private function createSuperadminUser(int $projectId = 1): User
    {
        return User::create([
            'username' => 'admin_test',
            'name' => 'Admin Test',
            'password' => Hash::make('password'),
            'role_user' => 'superadmin',
            'project_id' => $projectId,
            'store_id' => null,
        ]);
    }

    public function test_partial_payment_via_bank_records_accurate_remaining_and_cash_balance(): void
    {
        $user = $this->createSuperadminUser(1);

        DB::table('tb_bank')->insert([
            'id' => 1,
            'name' => 'Kas Utama',
            'category' => 'Kas & Bank',
            'balance' => 500000,
            'project_id' => 1,
            'store_id' => null,
        ]);

        DB::table('tb_store_order')->insert([
            'order_id' => 'INV-001',
            'project_id' => 1,
            'customer_id' => 0,
            'role_project' => 'seller',
            'total_amount' => 100000,
            'status' => 'OPEN',
        ]);

        DB::table('view_store_order')->insert([
            'order_id' => 'INV-001',
            'project_id' => 1,
            'customer_id' => 0,
            'role_project' => 'seller',
            'total_amount' => 100000,
            'paid' => 0,
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($user)->post(route('invoice.accept-payment.store'), [
            'order_id' => 'INV-001',
            'debit_deposit' => false,
            'id_bank_destination' => 1,
            'paid' => 40000,
            'billing_date' => '2026-11-26',
            'memo' => 'Cicilan pertama',
        ]);

        $response->assertRedirect(route('invoice.show', 'INV-001'));

        $payment = DB::table('tb_store_order_payment')->where('store_order_id', 'INV-001')->first();
        $this->assertNotNull($payment);
        $this->assertSame(40000, (int) $payment->amount);
        $this->assertSame(60000, (int) $payment->sisa);
        $this->assertSame(1, (int) $payment->id_bank);
        $this->assertSame(1, (int) $payment->id_bank_destination);
        $this->assertSame('Cicilan ke-1', $payment->notes);

        $cash = DB::table('tb_cash_transaction')->where('order_id', 'INV-001')->first();
        $this->assertNotNull($cash);
        $this->assertSame(40000, (int) $cash->credit);
        $this->assertSame(540000, (int) $cash->balance);

        $order = DB::table('tb_store_order')->where('order_id', 'INV-001')->first();
        $this->assertSame('OPEN', $order->status);
    }

    public function test_full_payment_with_overpaid_clamps_cash_balance_and_awards_points(): void
    {
        $user = $this->createSuperadminUser(1);

        DB::table('tb_customer')->insert([
            'id' => 10,
            'name' => 'Budi',
            'deposit' => 0,
        ]);

        DB::table('tb_bank')->insert([
            'id' => 1,
            'name' => 'Kas Utama',
            'category' => 'Kas & Bank',
            'balance' => 100000,
            'project_id' => 1,
            'store_id' => null,
        ]);

        DB::table('tb_store_order')->insert([
            'order_id' => 'INV/11/2026',
            'project_id' => 1,
            'customer_id' => 10,
            'role_project' => 'seller',
            'total_amount' => 100000,
            'poin' => 25,
            'status' => 'OPEN',
        ]);

        DB::table('view_store_order')->insert([
            'order_id' => 'INV/11/2026',
            'project_id' => 1,
            'customer_id' => 10,
            'role_project' => 'seller',
            'total_amount' => 100000,
            'paid' => 40000,
            'status' => 'OPEN',
        ]);

        DB::table('tb_store_order_payment')->insert([
            'store_order_id' => 'INV/11/2026',
            'order_id' => '1',
            'amount' => 40000,
            'sisa' => 60000,
            'notes' => 'Cicilan ke-1',
            'id_bank_destination' => 1,
            'id_bank' => 1,
            'status' => 'approved',
            'date' => '2026-11-25',
        ]);

        // Bayar 100.000 untuk sisa 60.000 (kembalian 40.000)
        $response = $this->actingAs($user)->post(route('invoice.accept-payment.store'), [
            'order_id' => 'INV/11/2026',
            'debit_deposit' => false,
            'id_bank_destination' => 1,
            'paid' => 100000,
            'billing_date' => '2026-11-26',
        ]);

        $response->assertRedirect(route('invoice.show', 'INV/11/2026'));

        $latestPayment = DB::table('tb_store_order_payment')
            ->where('store_order_id', 'INV/11/2026')
            ->orderByDesc('id')
            ->first();

        $this->assertSame(60000, (int) $latestPayment->amount);
        $this->assertSame(0, (int) $latestPayment->sisa);
        $this->assertSame('Dibayar Lunas', $latestPayment->notes);

        $cash = DB::table('tb_cash_transaction')->where('order_id', 'INV/11/2026')->first();
        $this->assertSame(60000, (int) $cash->credit);
        $this->assertSame(160000, (int) $cash->balance);

        $order = DB::table('tb_store_order')->where('order_id', 'INV/11/2026')->first();
        $this->assertSame('PAID', $order->status);

        $point = DB::table('tb_trx_point')->where('transid', 'INV/11/2026')->first();
        $this->assertNotNull($point);
        $this->assertSame(25, (int) $point->amount);
        $this->assertSame('C', $point->status);
    }

    public function test_payment_via_customer_deposit_and_insufficient_deposit_rejection(): void
    {
        $user = $this->createSuperadminUser(1);

        DB::table('tb_customer')->insert([
            'id' => 12,
            'name' => 'Siti',
            'deposit' => 50000,
        ]);

        DB::table('tb_store_order')->insert([
            'order_id' => 'INV-DEP',
            'project_id' => 1,
            'customer_id' => 12,
            'role_project' => 'seller',
            'total_amount' => 100000,
            'status' => 'OPEN',
        ]);

        DB::table('view_store_order')->insert([
            'order_id' => 'INV-DEP',
            'project_id' => 1,
            'customer_id' => 12,
            'role_project' => 'seller',
            'total_amount' => 100000,
            'paid' => 0,
            'status' => 'OPEN',
        ]);

        // Melebihi saldo deposit -> ditolak
        $failResponse = $this->actingAs($user)->post(route('invoice.accept-payment.store'), [
            'order_id' => 'INV-DEP',
            'debit_deposit' => true,
            'paid' => 75000,
            'billing_date' => '2026-11-26',
        ]);
        $failResponse->assertSessionHasErrors('debit_deposit');

        // Sesuai saldo deposit -> berhasil
        $okResponse = $this->actingAs($user)->post(route('invoice.accept-payment.store'), [
            'order_id' => 'INV-DEP',
            'debit_deposit' => true,
            'paid' => 50000,
            'billing_date' => '2026-11-26',
        ]);
        $okResponse->assertRedirect(route('invoice.show', 'INV-DEP'));

        $payment = DB::table('tb_store_order_payment')->where('store_order_id', 'INV-DEP')->first();
        $this->assertSame(0, (int) $payment->id_bank);
        $this->assertSame(0, (int) $payment->id_bank_destination);
        $this->assertSame(50000, (int) $payment->sisa);

        $depositTrx = DB::table('tb_deposit_transaction')->where('order_id', 'INV-DEP')->first();
        $this->assertNotNull($depositTrx);
        $this->assertSame(50000, (int) $depositTrx->debit);
        $this->assertSame(0, (int) $depositTrx->balance);
    }

    public function test_rejects_bank_from_another_project(): void
    {
        $user = $this->createSuperadminUser(1);

        DB::table('tb_bank')->insert([
            'id' => 99,
            'name' => 'Kas Proyek Lain',
            'category' => 'Kas & Bank',
            'balance' => 100000,
            'project_id' => 2,
            'store_id' => null,
        ]);

        $response = $this->actingAs($user)->post(route('invoice.accept-payment.store'), [
            'order_id' => 'INV-001',
            'debit_deposit' => false,
            'id_bank_destination' => 99,
            'paid' => 50000,
            'billing_date' => '2026-11-26',
        ]);

        $response->assertSessionHasErrors('id_bank_destination');
    }
}
