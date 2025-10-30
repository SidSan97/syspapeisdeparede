<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Configurações/preferências da aplicação
    |--------------------------------------------------------------------------
    */


    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('val');
            $table->string('type', 20)->default('string');
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
