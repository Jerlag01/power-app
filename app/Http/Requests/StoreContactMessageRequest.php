<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'privacy' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => __('site.validation.required'),
            'email' => __('site.validation.email'),
            'max' => __('site.validation.max'),
            'privacy.accepted' => __('site.validation.privacy'),
            'website.prohibited' => __('site.validation.spam'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => mb_strtolower(__('site.contact.fields.name')),
            'email' => mb_strtolower(__('site.contact.fields.email')),
            'phone' => mb_strtolower(__('site.contact.fields.phone')),
            'subject' => mb_strtolower(__('site.contact.fields.subject')),
            'message' => mb_strtolower(__('site.contact.fields.message')),
            'privacy' => mb_strtolower(__('site.privacy.title')),
        ];
    }
}
