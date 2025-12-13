<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {



    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->decimal('percentage', 5, 2);
            $table->boolean('active')->default(true);
            $table->dateTime('expires_at')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->default(0);
            $table->timestamps();
        });
        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('discount_id')->constrained();
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });

        Schema::create('discount_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('discount_id')->constrained();
            $table->decimal('applied_percentage', 5, 2);
            $table->decimal('amount_before', 10, 2);
            $table->decimal('amount_after', 10, 2);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('user_discounts');
        Schema::dropIfExists('discount_audits');
    }
};
