<?php
namespace Ksfraser\GenericInterface;

/**
 * Provides generic object mapping utilities for models.
 * - arr2obj: Map array to object properties
 * - obj2obj: Map object to object properties
 */
trait GenericObjectMappingTrait
{
    /**
     * Map array or object to this object's properties.
     * @param array|object $data
     * @return int Number of fields copied
     */
    public function arr2obj($data)
    {
        if (is_object($data)) {
            return $this->obj2obj($data);
        }
        if (!is_array($data)) {
            throw new \Exception("Passed in data is neither an array nor an object. We can't handle here!");
        }
        $cnt = 0;
        foreach (get_object_vars($this) as $key => $value) {
            if (isset($data[$key])) {
                $this->set($key, $data[$key]);
                $cnt++;
            }
        }
        return $cnt;
    }

    /**
     * Map object to this object's properties.
     * @param object $obj
     * @return int Number of fields copied
     */
    public function obj2obj($obj)
    {
        if (is_array($obj)) {
            return $this->arr2obj($obj);
        }
        if (!is_object($obj)) {
            throw new \Exception("Passed in data is neither an array nor an object. We can't handle here!");
        }
        $cnt = 0;
        foreach (get_object_vars($this) as $key => $value) {
            if (isset($obj->$key)) {
                $this->set($key, $obj->$key);
                $cnt++;
            }
        }
        return $cnt;
    }

    /**
     * Convert transaction array or object to this object (alias for obj2obj).
     * @param array|object $trz
     * @return int Number of fields copied
     */
    public function trz2obj($trz)
    {
        return $this->obj2obj($trz);
    }
}
