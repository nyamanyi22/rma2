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
    Schema::create('admins', function (Blueprint $table) {
    $table->id();
    $table->string('first_name');  // First name (if replacing 'name')
    $table->string('last_name');   // Added last name
    $table->string('email')->unique();
    $table->string('password');
    $table->string('role')->default('super_admin'); // Role-based access
    $table->timestamps();
});
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins'); // ✅ correct table name
    }
};
