<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('rma_requests', function (Blueprint $table) {
        $table->id();
          $table->string('rma_number')->unique(); 
        $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
        $table->string('product_code');
        $table->string('product_name')->nullable();
        $table->string('serial_number');
        $table->integer('quantity')->default(1);
        $table->date('invoice_date');
        $table->string('sales_document_no');
        $table->string('return_reason');
        $table->text('problem_description');
        $table->string('photo_path')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rma_requests');
    }
};
