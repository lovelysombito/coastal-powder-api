<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('comments')->truncate();
        for ($i = 1; $i <= 5; $i++) {
            DB::table('comments')->insert([
                'comment_id' => 'comment' . $i,
                'user_id' => 'user' . $i,
                'object_id' => 'job' . $i,
                'object_type' => 'JOB',
                'comment' => Str::random(10),
            ]);
        }
        for ($i = 6; $i <= 10; $i++) {
            DB::table('comments')->insert([
                'comment_id' => 'comment' . $i,
                'user_id' => 'user' . $i,
                'object_id' => 'line' . $i,
                'object_type' => 'LINE_ITEM',
                'comment' => Str::random(10),
            ]);
        }
    }
}
