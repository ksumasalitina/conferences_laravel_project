<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $slots = array(
            array('start'=>'10:00', 'end'=>'11:00'),
            array('start'=>'11:00', 'end'=>'12:00'),
            array('start'=>'12:00', 'end'=>'13:00'),
            array('start'=>'13:00', 'end'=>'14:00'),
            array('start'=>'14:00', 'end'=>'15:00'),
            array('start'=>'15:00', 'end'=>'16:00'),
            array('start'=>'16:00', 'end'=>'17:00'),
            array('start'=>'17:00', 'end'=>'18:00'),
            array('start'=>'18:00', 'end'=>'19:00'),
            array('start'=>'19:00', 'end'=>'20:00'),
        );

        DB::table('slots')->insert($slots);
    }
}
