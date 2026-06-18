<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set session variables for migration
        DB::statement('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0');
        DB::statement('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0');
        DB::statement('SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\'');
        
        // Create domains table
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('domain_id');
            $table->string('domain', 255)->default('');
            $table->string('maildir', 4096)->default('');
            $table->smallInteger('uid')->unsigned()->default(65534);
            $table->smallInteger('gid')->unsigned()->default(65534);
            $table->integer('max_accounts')->unsigned()->default(0);
            $table->integer('quotas')->unsigned()->default(0);
            $table->string('type', 5)->nullable();
            $table->boolean('avscan')->default(0);
            $table->boolean('blocklists')->default(0);
            $table->boolean('enabled')->default(1);
            $table->boolean('mailinglists')->default(0);
            $table->mediumInteger('maxmsgsize')->unsigned()->default(0);
            $table->boolean('pipe')->default(0);
            $table->boolean('spamassassin')->default(0);
            $table->smallInteger('sa_tag')->unsigned()->default(0);
            $table->smallInteger('sa_refuse')->unsigned()->default(0);
            
            $table->unique('domain');
        });
        
        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->integer('domain_id')->unsigned();
            $table->string('localpart', 64)->default('');
            $table->string('username', 255)->default('');
            $table->string('crypt', 255)->nullable();
            $table->smallInteger('uid')->unsigned()->default(65534);
            $table->smallInteger('gid')->unsigned()->default(65534);
            $table->string('smtp', 4096)->nullable();
            $table->string('pop', 4096)->nullable();
            $table->enum('type', ['local', 'alias', 'catch', 'fail', 'piped', 'admin', 'site'])->default('local');
            $table->boolean('admin')->default(0);
            $table->boolean('on_avscan')->default(0);
            $table->boolean('on_blocklist')->default(0);
            $table->boolean('on_forward')->default(0);
            $table->boolean('on_piped')->default(0);
            $table->boolean('on_spamassassin')->default(0);
            $table->boolean('on_vacation')->default(0);
            $table->boolean('spam_drop')->default(0);
            $table->boolean('enabled')->default(1);
            $table->string('flags', 16)->nullable();
            $table->string('forward', 4096)->nullable();
            $table->boolean('unseen')->default(0);
            $table->mediumInteger('maxmsgsize')->unsigned()->default(0);
            $table->integer('quota')->unsigned()->default(0);
            $table->string('realname', 255)->nullable();
            $table->smallInteger('sa_tag')->unsigned()->default(0);
            $table->smallInteger('sa_refuse')->unsigned()->default(0);
            $table->string('tagline', 255)->nullable();
            $table->text('vacation')->nullable();
            
            $table->unique(['localpart', 'domain_id']);
            $table->index('localpart');
            $table->index('domain_id');
            
            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('domains')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
        
        // Create blocklists table
        Schema::create('blocklists', function (Blueprint $table) {
            $table->increments('block_id');
            $table->integer('domain_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('blockhdr', 192)->default('');
            $table->string('blockval', 255)->default('');
            $table->string('color', 8)->default('');
            
            $table->index('domain_id');
            $table->index('user_id');
            
            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('domains')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
        
        // Create domainalias table
        Schema::create('domainalias', function (Blueprint $table) {
            $table->integer('domain_id')->unsigned();
            $table->string('alias', 255);
            
            $table->primary('alias');
            $table->index('domain_id');
            
            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('domains')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
        
        // Create groups table
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('domain_id')->unsigned();
            $table->string('name', 64);
            $table->char('is_public', 1)->default('Y');
            $table->boolean('enabled')->default(1);
            
            $table->unique(['domain_id', 'name']);
            $table->index('domain_id');
            
            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('domains')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
        
        // Create group_contents table
        Schema::create('group_contents', function (Blueprint $table) {
            $table->integer('group_id')->unsigned();
            $table->integer('member_id')->unsigned();
            
            $table->primary(['group_id', 'member_id']);
            $table->index('group_id');
            $table->index('member_id');
            
            $table->foreign('group_id')
                  ->references('id')
                  ->on('groups')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->foreign('member_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
        
        // Create dkim table        
        Schema::create('dkim', function (Blueprint $table) {
            $table->unsignedInteger('dkim_id')->autoIncrement();

            $table->unsignedInteger('domain_id');
            $table->string('selector', 63)->default('default');

            $table->text('private_key');
            $table->text('public_key');

            $table->enum('canonical', ['simple', 'relaxed'])
                ->default('relaxed');

            // Optional: custom headers to sign
            $table->string('sign_headers', 255)->nullable();

            $table->boolean('enabled')->default(true);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->unique(['domain_id', 'selector'], 'domain_selector');

            $table->foreign('domain_id')
                ->references('domain_id')
                ->on('domains')
                ->cascadeOnDelete();
        });
        
        // Create view to simplify dkim lookups from Exim
        DB::statement(<<<SQL
            CREATE VIEW vw_dkim_active AS
            SELECT
                dom.domain,
                d.selector,
                d.private_key,
                d.canonical,
                d.enabled
            FROM dkim d
            JOIN domains dom
                ON d.domain_id = dom.domain_id
            WHERE d.enabled = 1
        SQL);        
        
        // Restore session variables
        DB::statement('SET SQL_MODE=@OLD_SQL_MODE');
        DB::statement('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS');
        DB::statement('SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS');
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_contents');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('domainalias');
        Schema::dropIfExists('blocklists');
        Schema::dropIfExists('users');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('dkim');
        DB::statement('DROP VIEW IF EXISTS vw_dkim_active');
    }
};