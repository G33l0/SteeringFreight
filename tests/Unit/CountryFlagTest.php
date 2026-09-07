<?php

namespace Tests\Unit;

use App\Support\Countries;
use PHPUnit\Framework\TestCase;

class CountryFlagTest extends TestCase
{
    public function test_a_country_name_resolves_to_its_code_and_flag(): void
    {
        $this->assertSame('NG', Countries::code('Nigeria'));
        $this->assertSame('🇳🇬', Countries::flag('Nigeria'));
        $this->assertSame('🇯🇵', Countries::flag('  japan  '));
    }

    public function test_common_short_forms_are_understood(): void
    {
        $this->assertSame('AE', Countries::code('UAE'));
        $this->assertSame('GB', Countries::code('UK'));
        $this->assertSame('US', Countries::code('usa'));
        $this->assertSame('TR', Countries::code('Turkey'));
        $this->assertSame('CI', Countries::code('Ivory Coast'));
    }

    public function test_text_that_is_not_a_country_has_no_flag(): void
    {
        $this->assertNull(Countries::code('Rotterdam'));
        $this->assertNull(Countries::flag('Rotterdam'));
        $this->assertNull(Countries::flag(''));
        $this->assertNull(Countries::flag(null));
    }

    public function test_every_country_in_the_list_produces_a_flag(): void
    {
        foreach (Countries::names() as $name) {
            $this->assertNotNull(Countries::flag($name), $name.' has no flag');
        }
    }
}
