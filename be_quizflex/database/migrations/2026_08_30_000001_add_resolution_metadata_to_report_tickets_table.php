<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('report_tickets')) {
            Schema::table('report_tickets', function (Blueprint $table) {
                if (!Schema::hasColumn('report_tickets', 'resolution_source')) {
                    $table->string('resolution_source', 50)->nullable()->after('status');
                }
                if (!Schema::hasColumn('report_tickets', 'resolution_action')) {
                    $table->string('resolution_action', 50)->nullable()->after('resolution_source');
                }
                if (!Schema::hasColumn('report_tickets', 'resolved_at')) {
                    $table->timestamp('resolved_at')->nullable()->after('resolution_action');
                }
                if (!Schema::hasColumn('report_tickets', 'resolved_by')) {
                    $table->foreignId('resolved_by')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('report_tickets', 'resolution_note')) {
                    $table->text('resolution_note')->nullable()->after('resolved_by');
                }
            });

            // Backfill existing resolved / dismissed tickets safely and non-destructively
            $resolvedTickets = DB::table('report_tickets')
                ->where('status', 'resolved')
                ->whereNull('resolution_source')
                ->get();

            foreach ($resolvedTickets as $ticket) {
                $source = 'admin';
                $action = 'approved';

                // Check if the question had an auto_approved review request
                if ($ticket->question_id) {
                    $latestReview = DB::table('question_review_requests')
                        ->where('question_id', $ticket->question_id)
                        ->where('status', 'approved')
                        ->latest('id')
                        ->first();

                    if ($latestReview && !empty($latestReview->snapshot_metadata)) {
                        $meta = json_decode($latestReview->snapshot_metadata, true);
                        if (!empty($meta['auto_approved'])) {
                            $source = 'auto_review';
                        }
                    }
                }

                DB::table('report_tickets')
                    ->where('id', $ticket->id)
                    ->update([
                        'resolution_source' => $source,
                        'resolution_action' => $action,
                        'resolved_at' => $ticket->updated_at ?? now(),
                    ]);
            }

            $dismissedTickets = DB::table('report_tickets')
                ->where('status', 'dismissed')
                ->whereNull('resolution_source')
                ->get();

            foreach ($dismissedTickets as $ticket) {
                DB::table('report_tickets')
                    ->where('id', $ticket->id)
                    ->update([
                        'resolution_source' => 'admin',
                        'resolution_action' => 'dismissed',
                        'resolved_at' => $ticket->updated_at ?? now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_tickets')) {
            Schema::table('report_tickets', function (Blueprint $table) {
                if (Schema::hasColumn('report_tickets', 'resolved_by')) {
                    try {
                        $table->dropForeign(['resolved_by']);
                    } catch (\Throwable $e) {
                        // Ignore if FK does not exist
                    }
                }

                $columnsToDrop = [];
                if (Schema::hasColumn('report_tickets', 'resolution_source')) {
                    $columnsToDrop[] = 'resolution_source';
                }
                if (Schema::hasColumn('report_tickets', 'resolution_action')) {
                    $columnsToDrop[] = 'resolution_action';
                }
                if (Schema::hasColumn('report_tickets', 'resolved_at')) {
                    $columnsToDrop[] = 'resolved_at';
                }
                if (Schema::hasColumn('report_tickets', 'resolved_by')) {
                    $columnsToDrop[] = 'resolved_by';
                }
                if (Schema::hasColumn('report_tickets', 'resolution_note')) {
                    $columnsToDrop[] = 'resolution_note';
                }

                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
