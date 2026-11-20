<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ip_address',
        'username',
        'password',
        'email',
        'activation_selector',
        'activation_code',
        'forgotten_password_selector',
        'forgotten_password_code',
        'forgotten_password_time',
        'remember_selector',
        'remember_code',
        'created_on',
        'last_login',
        'active',
        'first_name',
        'last_name',
        'company',
        'phone',
        'foto',
        'jabatan',
        'face_descriptor',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'activation_selector',
        'activation_code',
        'forgotten_password_selector',
        'forgotten_password_code',
        'remember_selector',
        'remember_code',
        'face_descriptor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'integer',
            'created_on' => 'integer',
            'last_login' => 'integer',
            'forgotten_password_time' => 'integer',
        ];
    }

    protected function role(): Attribute
    {
        return Attribute::get(function (): ?int {
            $groupId = DB::table('users_groups')
                ->where('user_id', $this->getKey())
                ->value('group_id');

            return $groupId === null ? null : (int) $groupId;
        });
    }

    protected function roleName(): Attribute
    {
        return Attribute::get(function (): ?string {
            return DB::table('groups')
                ->join('users_groups', 'groups.id', '=', 'users_groups.group_id')
                ->where('users_groups.user_id', $this->getKey())
                ->value('groups.name');
        });
    }

    protected function nama(): Attribute
    {
        return Attribute::get(function (): string {
            return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        });
    }

    public function isActive(): bool
    {
        return (string) $this->active === '1';
    }
}
