<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->text('password'); // Encrypted
            $table->string('host');
            $table->integer('port')->default(993);
            $table->string('encryption')->default('ssl');
            $table->string('protocol')->default('imap');
            $table->boolean('auto_capture')->default(true);
            $table->json('folders')->nullable(); // ['INBOX', 'Archivo']
            $table->json('filters')->nullable();
            $table->unsignedBigInteger('serie_documental_id')->nullable(); // Foreign key sin constraint por orden de migraciones
            $table->boolean('active')->default(true);
            $table->timestamp('last_capture_at')->nullable();
            $table->integer('total_captured')->default(0);
            $table->timestamps();
        });

        Schema::create('email_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->string('message_id');
            $table->string('subject');
            $table->text('from');
            $table->text('to')->nullable();
            $table->text('cc')->nullable();
            $table->text('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->integer('attachments_count')->default(0);
            $table->timestamp('email_date');
            $table->string('status')->default('captured'); // captured, processed, error
            $table->unsignedBigInteger('documento_id')->nullable(); // Foreign key sin constraint por orden de migraciones
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['email_account_id', 'message_id']);
        });

        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_capture_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('path');
            $table->unsignedBigInteger('documento_id')->nullable(); // Foreign key sin constraint por orden de migraciones
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
        Schema::dropIfExists('email_captures');
        Schema::dropIfExists('email_accounts');
    }
};
