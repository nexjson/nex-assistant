<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton settings row (id=1) controlling the OpenRouter-powered
 * WhatsApp auto-reply feature.
 *
 * @property int $id
 * @property bool $auto_reply_enabled
 * @property string $openrouter_model
 * @property string|null $openrouter_api_key
 */
#[Fillable(['auto_reply_enabled', 'openrouter_model', 'openrouter_api_key'])]
#[Hidden(['openrouter_api_key'])]
class WhatsappAiSetting extends Model
{
    protected function casts(): array
    {
        return [
            'auto_reply_enabled' => 'boolean',
            'openrouter_api_key' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
