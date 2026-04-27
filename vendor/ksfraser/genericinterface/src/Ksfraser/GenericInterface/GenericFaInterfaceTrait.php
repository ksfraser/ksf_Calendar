<?php
namespace Ksfraser\GenericInterface;

trait GenericFaInterfaceTrait
{
    use GenericObjectMappingTrait;

    /** @var array<int, array<string, mixed>> */
    public $fields_array = [];

    /** @var string */
    public $company_prefix = '';

    /** @var string */
    public $iam = '';

    /** @var array<string, mixed> */
    public $table_details = [];

    public function set($field, $value = null, $enforce = true)
    {
        try{ 
            $ret =$this->validate_field($field, $value);
            if( ! $ret) {
                throw new \Exception("Validation failed for field $field with value $value");
            }
        } catch (\Exception $e) {
            throw $e;
        }
        if ($enforce) {
            //Field must belong to the class so we aren't dynamically creating properties that don't exist in the database or aren't defined in the class.
		if (!property_exists($this, $field)) {
                	throw new \Exception("Field $field is not a valid property of " . get_class($this));
            	}
	}
        $this->$field = $value;
        return true;
    }
    public function validate_field($field, $value)
    {
        // This method can be overridden in the class using the trait to add validation logic for specific fields.
        // By default, it does nothing and allows all fields and values.
        return true;
    }

    public function get($field)
    {
        return $this->$field ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert_data(array $data): bool
    {
        return true;
    }
}
