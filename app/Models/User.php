<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'deleted_at',
    ];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isMarketing()
    {
        return $this->level_id == 2;
    }

    public function isSuperAdmin()
    {
        return $this->level_id == 1;
    }

    public function isCustomer()
    {
        return $this->level_id == 3;
    }
}
