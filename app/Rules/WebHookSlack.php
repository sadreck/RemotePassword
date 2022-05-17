<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class WebHookSlack implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $url = parse_url(strtolower($value));
        $isValidHost = isset($url['host']) && $url['host'] == 'hooks.slack.com';
        $isValidPath = isset($url['path']) && str_starts_with($url['path'], '/services/');
        return $isValidHost && $isValidPath;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('The :attribute must be a valid :name web hook url.', ['name' => 'Slack']);
    }
}
