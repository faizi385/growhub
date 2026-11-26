
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();

            $table->string('seller_type', 20)->default('online');

            $table->string('city', 100);
            $table->string('province', 100)->nullable();

            $table->string('whatsapp', 20);
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();

            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();

            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'city']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
