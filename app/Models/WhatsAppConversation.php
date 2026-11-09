<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $guarded = ['id'];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
