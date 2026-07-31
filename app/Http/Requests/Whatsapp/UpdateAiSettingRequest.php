<?php

namespace App\Http\Requests\Whatsapp;

use App\Models\WhatsappAiSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAiSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'auto_reply_enabled' => ['required', 'boolean'],
            'openrouter_model' => ['required', 'string', 'max:255'],
            'openrouter_api_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->boolean('auto_reply_enabled') && blank($this->resolvedApiKey())) {
                $validator->errors()->add(
                    'openrouter_api_key',
                    'An OpenRouter API key is required to enable auto-reply.'
                );
            }
        });
    }

    /**
     * The API key to persist: the newly submitted key, or the currently
     * stored one when the field was left blank.
     */
    public function resolvedApiKey(): ?string
    {
        return filled($this->input('openrouter_api_key'))
            ? $this->input('openrouter_api_key')
            : WhatsappAiSetting::current()->openrouter_api_key;
    }
}
