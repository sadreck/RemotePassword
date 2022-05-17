<?php

namespace Tests\Feature;

use App\Services\Core\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_settings()
    {
        /** @var Settings $settings */
        $settings = app()->make('settings');

        $settings->set('hello', 'goodbye');
        $settings->set('number', 1234);
        $settings->set('bool_true', true);
        $settings->set('bool_false', false);
        $settings->set('float', 3.14159);
        $settings->setMultiple([
            'one' => 1,
            'two' => 2,
            'three' => 3
        ]);

        $this->assertEquals('goodbye', $settings->get('hello'));

        $this->assertEquals(1234, $settings->get('number'));
        $this->assertTrue(is_int($settings->get('number', null, 'int')));
        $this->assertTrue(is_int($settings->get('number', null, 'integer')));

        $this->assertEquals(true, $settings->get('bool_true'));
        $this->assertTrue(is_bool($settings->get('bool_true', null, 'bool')));
        $this->assertTrue(is_bool($settings->get('bool_true', null, 'boolean')));

        $this->assertEquals(0, $settings->get('bool_false'));
        $this->assertEquals(false, $settings->get('bool_false', null, 'bool'));
        $this->assertTrue(is_bool($settings->get('bool_false', null, 'bool')));
        $this->assertTrue(is_bool($settings->get('bool_false', null, 'boolean')));

        $this->assertEquals(3.14159, $settings->get('float'));
        $this->assertEquals(3.14159, $settings->get('float', null, 'float'));
        $this->assertTrue(is_float($settings->get('float', null, 'float')));

        $this->assertEquals(1, $settings->get('one', null, 'int'));
        $this->assertEquals(2, $settings->get('two', null, 'int'));
        $this->assertEquals(3, $settings->get('three', null, 'int'));

        $this->assertEquals(123, $settings->get('does_not_exist', 123));
    }
}
// phpcs:ignoreFile
