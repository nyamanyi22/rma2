<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('customers', function (Blueprint $table) {
        $table->id(); // Auto-increment primary key
       $table->string('company_name')->nullable();
$table->string('website')->nullable();
$table->string('first_name');
$table->string('last_name');
$table->string('email')->unique();
$table->string('password');
$table->string('phone');
$table->string('fax')->nullable();
$table->string('shipping_address1');
$table->string('shipping_address2')->nullable();
$table->string('shipping_city');
$table->string('shipping_state');
$table->string('shipping_zipcode');
$table->string('shipping_country');
$table->string('billing_address1');
$table->string('billing_address2')->nullable();
$table->string('billing_city');
$table->string('billing_state');
$table->string('billing_zipcode');
$table->string('billing_country');
$table->string('verification_key')->nullable();
$table->timestamps();

    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
