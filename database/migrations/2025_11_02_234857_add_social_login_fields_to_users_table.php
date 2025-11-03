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
        Schema::table('users', function (Blueprint $table) {
            // Google OAuth
            $table->string('google_id')->nullable()->after('email');
            $table->text('google_token')->nullable()->after('google_id');
            $table->text('google_refresh_token')->nullable()->after('google_token');
            
            // Microsoft OAuth
            $table->string('microsoft_id')->nullable()->after('google_refresh_token');
            $table->text('microsoft_token')->nullable()->after('microsoft_id');
            $table->text('microsoft_refresh_token')->nullable()->after('microsoft_token');
            
            // Azure AD
            $table->string('azure_id')->nullable()->after('microsoft_refresh_token');
            $table->text('azure_token')->nullable()->after('azure_id');
            $table->text('azure_refresh_token')->nullable()->after('azure_token');
            
            // GitHub (opcional)
            $table->string('github_id')->nullable()->after('azure_refresh_token');
            $table->text('github_token')->nullable()->after('github_id');
            
            // Avatar desde provider
            $table->string('avatar')->nullable()->after('github_token');
            
            // Índices para búsquedas rápidas
            $table->index('google_id');
            $table->index('microsoft_id');
            $table->index('azure_id');
            $table->index('github_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['microsoft_id']);
            $table->dropIndex(['azure_id']);
            $table->dropIndex(['github_id']);
            
            $table->dropColumn([
                'google_id', 'google_token', 'google_refresh_token',
                'microsoft_id', 'microsoft_token', 'microsoft_refresh_token',
                'azure_id', 'azure_token', 'azure_refresh_token',
                'github_id', 'github_token',
                'avatar',
            ]);
        });
    }
};
