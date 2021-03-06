<?php
/**
 * Webiny Framework (http://www.webiny.com/framework)
 *
 * @copyright Copyright Webiny LTD
 */

namespace Webiny\Component\ServiceManager;

use Webiny\Component\StdLib\StdLibTrait;

/**
 * Argument class serves as a wrapper for service arguments.<br />
 * It contains the value from config file and knows how to get the real value of it, like including other services,
 * instantiating classes, etc.
 *
 * @package         Webiny\Component\ServiceManager
 */
class Argument
{
    use StdLibTrait;

    /**
     * Simple value, class name or service name
     */
    private $value;

    /**
     * Create Argument instance
     *
     * @param mixed $argument Value to wrap into Argument instance
     */
    public function __construct($argument)
    {
        $this->value = $argument;
    }

    /**
     * Get real Argument value
     * @return mixed
     */
    public function value()
    {
        /**
         * If 'object' key exists - it's either a class or service
         **/
        if ($this->isArray($this->value) && $this->arr($this->value)->keyExists('Object')) {
            $this->value = $this->arr($this->value);
            $this->value = $this->createValue($this->value->key('Object'),
                                                $this->value->key('ObjectArguments', [], true)
            );
        } else {
            $this->value = $this->createValue($this->value);
        }

        return $this->value;
    }

    /**
     * Create proper argument value
     *
     * @param mixed $object
     * @param array $arguments
     *
     * @throws ServiceManagerException
     *
     * @return mixed|object
     */
    private function createValue($object, $arguments = [])
    {

        if ($this->isInstanceOf($arguments, '\Webiny\Component\Config\ConfigObject')) {
            $arguments = $arguments->toArray();
        }

        if (!$this->isArray($arguments)) {
            throw new ServiceManagerException(ServiceManagerException::INVALID_SERVICE_ARGUMENTS_TYPE, [$object]);
        }

        if (!$this->isString($object)) {
            return $object;
        }

        $object = $this->str($object);

        if ($object->startsWith('@')) {
            $arguments = $this->arr($arguments)->count() > 0 ? $arguments : null;

            return ServiceManager::getInstance()->getService($object->val(), $arguments);
        } else {
            $value = $object->val();
            if (class_exists($value)) {
                $reflection = new \ReflectionClass($value);

                return $reflection->newInstanceArgs($arguments);
            }

            return $value;
        }
    }
}