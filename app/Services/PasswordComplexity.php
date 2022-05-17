<?php
namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordComplexity
{
    /** @var bool */
    protected bool $disabled = false;

    /**
     * @return void
     */
    public function disable(): void
    {
        $this->disabled = true;
    }

    /**
     * @return void
     */
    public function enable() : void
    {
        $this->disabled = false;
    }

    public function __construct(
        protected int $length,
        protected bool $letters,
        protected bool $mixedCase,
        protected bool $numbers,
        protected bool $symbols,
        protected bool $uncompromised
    ) {
        // Nothing.
    }

    /**
     * @param string $password
     * @return bool
     */
    public function validate(string $password) : bool
    {
        // Should only be used for testing.
        if ($this->disabled) {
            return true;
        }

        $passwordRule = Password::min($this->length)->letters();

        if ($this->letters) {
            $passwordRule->letters();
        }

        if ($this->mixedCase) {
            $passwordRule->mixedCase();
        }

        if ($this->numbers) {
            $passwordRule->numbers();
        }

        if ($this->symbols) {
            $passwordRule->symbols();
        }

        if ($this->uncompromised) {
            $passwordRule->uncompromised();
        }

        $validator = Validator::make([$password], [$passwordRule]);
        return !$validator->fails();
    }
}
