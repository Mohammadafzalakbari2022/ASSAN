<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The real shop keeps its orders in Aimeos tables that are created by the
 * "aimeos:setup" command, not by a migration. The automated test database does
 * not run that command, so this trait creates the few order columns the
 * delivery board reads. When the tables already exist (for example after a real
 * setup) nothing is created.
 */
trait CreatesShopOrderTables
{
    protected function createShopOrderTables(): void
    {
        if (!Schema::hasTable('mshop_order')) {
            Schema::create('mshop_order', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('siteid')->default('');
                $table->string('invoiceno')->default('');
                $table->string('customerid', 36)->default('');
                $table->smallInteger('statuspayment')->default(-1);
                $table->smallInteger('statusdelivery')->default(-1);
                $table->decimal('price', 12, 2)->default(0);
                $table->string('currencyid', 3)->default('');
                $table->timestamp('ctime')->nullable();
                $table->timestamp('mtime')->nullable();
                $table->string('editor')->default('');
            });
        }

        if (!Schema::hasTable('mshop_order_address')) {
            Schema::create('mshop_order_address', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('siteid')->default('');
                $table->unsignedBigInteger('parentid');
                $table->string('type')->default('');
                $table->string('company')->default('');
                $table->string('firstname')->default('');
                $table->string('lastname')->default('');
                $table->string('address1')->default('');
                $table->string('address2')->default('');
                $table->string('address3')->default('');
                $table->string('city')->default('');
                $table->string('state')->default('');
                $table->string('postal')->default('');
                $table->string('countryid', 2)->default('');
                $table->string('email')->default('');
                $table->string('telephone')->default('');
                $table->string('mobile')->default('');
                $table->double('longitude')->nullable();
                $table->double('latitude')->nullable();
            });
        }
        if (!Schema::hasTable('mshop_order_product')) {
            Schema::create('mshop_order_product', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('siteid')->default('');
                $table->unsignedBigInteger('parentid');
                $table->string('name')->default('');
                $table->double('quantity')->default(1);
                $table->decimal('price', 12, 2)->default(0);
                $table->string('currencyid', 3)->default('');
                $table->integer('pos')->default(0);
            });
        }

        if (!Schema::hasTable('mshop_order_status')) {
            Schema::create('mshop_order_status', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('siteid')->default('');
                $table->unsignedBigInteger('parentid');
                $table->string('type', 32)->default('');
                $table->string('value', 64);
                $table->timestamp('mtime')->nullable();
                $table->timestamp('ctime')->nullable();
                $table->string('editor')->default('');
            });
        }
    }
}
