<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // HR-এর কোনো employee_id নেই, তাই এটাকে nullable করা হলো
            $table->foreignId('employee_id')->nullable()->change();

            // কে leave রিকুয়েস্ট করেছে তা ট্র্যাক করার জন্য (employee / tl / hr)
            $table->string('requester_type')->default('employee')->after('employee_id');
            $table->unsignedBigInteger('requester_id')->nullable()->after('requester_type');

            // TL-এর leave এর ক্ষেত্রে HR এর recommendation ট্র্যাক করার জন্য
            $table->enum('hr_status', ['pending', 'recommended', 'rejected'])
                  ->nullable()
                  ->after('tl_status');
        });

        // পুরনো (existing) রো-গুলোর জন্য requester_id ব্যাকফিল করা,
        // যেহেতু এতদিন সব leave employee_id দিয়েই সাবমিট হয়েছে
        DB::table('leaves')
            ->where('requester_type', 'employee')
            ->whereNotNull('employee_id')
            ->update(['requester_id' => DB::raw('employee_id')]);
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['requester_type', 'requester_id', 'hr_status']);

           
        });
    }
};