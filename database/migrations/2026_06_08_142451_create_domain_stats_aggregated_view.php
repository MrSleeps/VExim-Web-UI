<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateDomainStatsAggregatedView extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW domain_stats_aggregated AS
            SELECT 
                CONCAT(domain, '_', date) as id,
                domain,
                date,
                SUM(incoming_count) as incoming_count,
                SUM(spam_count) as spam_count,
                SUM(virus_count) as virus_count,
                AVG(avg_spam_score) as avg_spam_score,
                MAX(max_spam_score) as max_spam_score,
                SUM(total_size_bytes) as total_size_bytes,
                CASE 
                    WHEN SUM(incoming_count) > 0 
                    THEN (SUM(spam_count) / SUM(incoming_count)) * 100 
                    ELSE 0 
                END as spam_percentage
            FROM vw_domain_stats
            GROUP BY domain, date
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS domain_stats_aggregated');
    }
}
