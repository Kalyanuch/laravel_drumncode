<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    const STATUS_TODO = 'todo';
    const STATUS_DONE = 'done';

    protected $fillable = [
        'title',
        'description',
        'priority',
        'finish_at',
        'status',
        'parent_id',
        'user_id',
    ];

    public function getChildList()
    {
        return $this->where('parent_id', $this->id)->get();
    }

    public function getChildActiveList()
    {
        return $this->where('parent_id', $this->id)->where('status', self::STATUS_TODO)->get();
    }
}
