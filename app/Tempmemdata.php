<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tempmemdata extends Model
{
    public function user() {
      return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function position() {
      return $this->belongsTo('App\Position');
    }

    public function branch() {
      return $this->belongsTo('App\Branch');
    }
}
