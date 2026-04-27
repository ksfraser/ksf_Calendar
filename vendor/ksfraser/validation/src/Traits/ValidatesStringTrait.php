<?php

namespace Ksfraser\Validation\Traits;

use Ksfraser\Validation\Assert;

trait ValidatesStringTrait
{
    /**
     * @param mixed $value
     * @param string|null $field
     */
    protected function assertNotEmptyString($value, $field = null)
    {
        Assert::notEmptyString($value, $field);
    }

    /**
     * @param mixed $value
     * @param int $max
     * @param string|null $field
     */
    protected function assertStringMaxLen($value, $max, $field = null)
    {
        Assert::stringMaxLen($value, $max, $field);
    }

    /**
     * @param mixed $value
     * @param int $min
     * @param string|null $field
     */
    protected function assertStringMinLen($value, $min, $field = null)
    {
        Assert::stringMinLen($value, $min, $field);
    }
}
