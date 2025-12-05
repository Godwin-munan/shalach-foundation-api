<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('donation_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['donor_receipt', 'admin_alert', 'webhook_received', 'bank_transfer_thank_you', 'bank_transfer_admin_alert']);
            $table->string('recipient_email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['donation_id', 'type']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('donation_notifications');
    }
};
