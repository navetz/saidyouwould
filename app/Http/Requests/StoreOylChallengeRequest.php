<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOylChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $latestDate = now()->addYears(config('one-year-later.max_delivery_years'))->toDateString();

        return [
            'video_storage_key' => ['required', 'string', 'max:500'],
            'video_upload_token' => ['required', 'string'],
            'mode' => ['required', Rule::in(['friend', 'self'])],
            'notify_recipient' => ['required_if:mode,friend', 'nullable', 'boolean'],
            'anonymous' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_-]+$/i'],
            'recipient_email' => ['required_if:mode,friend', 'nullable', 'email:rfc', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:100'],
            'goal_title' => ['nullable', 'string', 'max:160'],
            'goal_description' => ['nullable', 'string', 'max:3000'],
            'delivery_date' => ['required', 'date_format:Y-m-d', 'after:today', 'before_or_equal:'.$latestDate],
            'written_terms' => ['nullable', 'string', 'max:3000'],
            'sender_email' => ['required', 'email:rfc', 'max:255'],
            'sender_name' => ['nullable', 'string', 'max:100'],
            'terms_accepted' => ['accepted'],
            'website' => ['nullable', 'size:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_date.after' => 'Choose a delivery date after today.',
            'terms_accepted.accepted' => 'Accept the terms before sealing your message.',
            'recipient_email.required_if' => 'Add your friend\'s email so we know where the video lands.',
        ];
    }
}
