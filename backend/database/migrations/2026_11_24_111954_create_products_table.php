
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name', 150);
            $table->string('slug', 180)->unique();

            $table->text('description')->nullable();

            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();

            $table->string('image')->nullable();

            $table->string('status', 20)->default('draft');

            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
