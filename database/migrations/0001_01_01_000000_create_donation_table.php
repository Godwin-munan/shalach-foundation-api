<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('donor_name');
            $table->string('donor_email');
            $table->string('donor_phone')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_channel')->nullable(); // card, bank, ussd, etc
            $table->enum('status', ['pending', 'pending_confirmation' ,'success', 'failed', 'abandoned'])->default('pending');
            $table->string('paystack_reference')->nullable();
            $table->string('paystack_access_code')->nullable();
            $table->text('purpose')->nullable(); // What the donation is for
            $table->boolean('is_anonymous')->default(false);
            $table->json('metadata')->nullable(); // Store extra info from Paystack
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('donor_email');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('donations');
    }
};
