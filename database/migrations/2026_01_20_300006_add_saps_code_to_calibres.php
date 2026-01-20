<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds SAPS CFR calibre code for official firearm registration
     */
    public function up(): void
    {
        Schema::table('calibres', function (Blueprint $table) {
            // SAPS CFR calibre code (e.g., 9PAR, 223REM, 308WIN)
            $table->string('saps_code', 20)->nullable()->after('name')->index();
        });

        // Populate common SAPS codes
        $codes = [
            // Handgun calibres
            '.22 LR' => '22LR',
            '.22 Long Rifle' => '22LR',
            '9mm Parabellum' => '9PAR',
            '9mm Luger' => '9PAR',
            '9x19mm' => '9PAR',
            '.38 Special' => '38SPL',
            '.357 Magnum' => '357MAG',
            '.40 S&W' => '40SW',
            '.45 ACP' => '45ACP',
            '.45 Auto' => '45ACP',
            '10mm Auto' => '10MM',
            '.380 ACP' => '380ACP',
            '9mm Short' => '380ACP',
            
            // Rifle calibres
            '.223 Remington' => '223REM',
            '5.56x45mm NATO' => '556NATO',
            '.308 Winchester' => '308WIN',
            '7.62x51mm NATO' => '762NATO',
            '.30-06 Springfield' => '3006',
            '.270 Winchester' => '270WIN',
            '.243 Winchester' => '243WIN',
            '6.5 Creedmoor' => '65CREED',
            '6.5x55 Swedish' => '65X55',
            '.300 Winchester Magnum' => '300WM',
            '.375 H&H Magnum' => '375HH',
            '.338 Lapua Magnum' => '338LAP',
            '.7mm Remington Magnum' => '7REMMAG',
            '.303 British' => '303BRIT',
            '7.62x39mm' => '762X39',
            '7.62x54R' => '762X54R',
            '.300 Blackout' => '300BLK',
            '.22-250 Remington' => '22250',
            '.204 Ruger' => '204RUG',
            '.17 HMR' => '17HMR',
            '.22 WMR' => '22WMR',
            '.22 Magnum' => '22WMR',
            
            // Shotgun gauges
            '12 Gauge' => '12GA',
            '12 Bore' => '12GA',
            '20 Gauge' => '20GA',
            '16 Gauge' => '16GA',
            '28 Gauge' => '28GA',
            '.410 Bore' => '410',
            '.410' => '410',
        ];

        foreach ($codes as $name => $sapsCode) {
            \DB::table('calibres')
                ->where('name', $name)
                ->update(['saps_code' => $sapsCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibres', function (Blueprint $table) {
            $table->dropColumn('saps_code');
        });
    }
};
