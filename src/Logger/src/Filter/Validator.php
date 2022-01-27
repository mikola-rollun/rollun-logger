<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Filter;

use Traversable;
use rollun\logger\Exception\InvalidArgumentException;
use Laminas\Validator\ValidatorInterface as ZendValidator;

class Validator implements FilterInterface
{
    /**
     * Regex to match
     *
     * @var ZendValidator
     */
    protected $validator;

    /**
     * Filter out any log messages not matching the validator
     *
     * @param  ZendValidator|array|Traversable $validator
     * @throws InvalidArgumentException
     */
    public function __construct($validator)
    {
        if ($validator instanceof Traversable) {
            $validator = iterator_to_array($validator);
        }
        if (is_array($validator)) {
            $validator = isset($validator['validator']) ? $validator['validator'] : null;
        }
        if (! $validator instanceof ZendValidator) {
            throw new InvalidArgumentException(sprintf(
                'Parameter of type %s is invalid; must implement Laminas\Validator\ValidatorInterface',
                (is_object($validator) ? get_class($validator) : gettype($validator))
            ));
        }
        $this->validator = $validator;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array $event event data
     * @return bool
     */
    public function filter(array $event)
    {
        return $this->validator->isValid($event['message']);
    }
}
