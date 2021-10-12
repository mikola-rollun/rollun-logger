<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger;

use Serializable;

class LifeCycleToken implements Serializable
{
    // For system token
    const KEY_LIFECYCLE_TOKEN = "lifecycle_token";
    // If sent token not equals to system, system token write with this name
    const KEY_ORIGINAL_LIFECYCLE_TOKEN = "original_lifecycle_token";
    // For parent token
    const KEY_PARENT_LIFECYCLE_TOKEN = "parent_lifecycle_token";
    // If sent parent token not equals to system, system patent token write with this name
    const KEY_ORIGINAL_PARENT_LIFECYCLE_TOKEN = "original_parent_lifecycle_token";

    /**
     * @var string
     */
    private $token;

    /**
     * @var self
     */
    private $parentToken;

    /**
     * Token constructor.
     * @param string $token
     * @param LifeCycleToken|null $parentToken
     */
    public function __construct(string $token, LifeCycleToken $parentToken = null)
    {
        $this->token = $token;
        $this->parentToken = $parentToken;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * Generate token with 30 chars length.
     */
    public static function generateToken()
    {
        $token = new LifeCycleToken(self::IdGenerate(30));

        return $token;
    }

    /**
     * IdGenerator from pollun/utils replacement:
     * Generate id.
     * Generates an arbitrary length string of cryptographic random
     * @param int $nums = 8;
     * @return string
     * @throws \Exception
     */
    public static function IdGenerate($nums = 8)
    {
        /**
         * @var string
         */
        $idCharSet = "QWERTYUIOPASDFGHJKLZXCVBNM0123456789";

        $id = [];
        $idCharSetArray = str_split($idCharSet);
        $charArrayCount = count($idCharSetArray) - 1;

        for ($i = 0; $i < $nums; $i++) {
            $id[$i] = $idCharSetArray[random_int(0, $charArrayCount)];
        }

        $id = implode("", $id);

        return $id;
    }

    /**
     * @see get_all_getders
     * @deprecated will be removed in version 6. Use createFromHeaders
     */
    public static function getAllHeaders()
    {
        $arh = [];
        $rx_http = '/\AHTTP_/';

        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val) {
                        $rx_matches[$ak_key] = ucfirst($ak_val);
                    }
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }

        return ($arh);
    }

    /**
     * @return bool
     */
    public function hasParentToken()
    {
        return isset($this->parentToken);
    }

    /**
     * @return LifeCycleToken
     */
    public function getParentToken()
    {
        return $this->parentToken;
    }

    /**
     * Serialize only own token. Parent token not saved (serialized) and not accessibly after serialization.
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return $this->token;
    }

    /**
     * After unserialize token object has changes struct.
     * Serialized token is becoming parent token, and generate new token for onw lifeCycleToken
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $this->__construct(
            self::generateToken()->toString(),
            new self($serialized)
        );
    }

    /**
     * Creates a token by getting parent token from cli arguments
     *
     * @return static
     */
    public static function createFromArgv(): self
    {
        if($parentToken = self::findTokenInArgv()) {
            return new self(self::generateToken()->toString(), new self($parentToken));
        }

        return self::generateToken();
    }

    /**
     * Creates a token by getting parent token from headers
     *
     * @return static
     */
    public static function createFromHeaders(): self
    {
        $parentToken = self::findTokenInHeaders();
        if ($parentToken !== null) {
            return new self(self::generateToken()->toString(), new self($parentToken));
        }

        return self::generateToken();
    }

    /**
     * Finds parent token in headers and returns it or null if nothing was found
     *
     * @return string|null
     */
    protected static function findTokenInHeaders(): ?string
    {
        $allowedKeys = [
            'HTTP_LIFECYCLETOKEN',
            'HTTP_LIFE_CYCLE_TOKEN',
            'HTTP_LIFECYCLE_TOKEN'
        ];

        foreach ($allowedKeys as $allowedKey) {
            if (!empty($_SERVER[$allowedKey])) {
                return $_SERVER[$allowedKey];
            }
        }

        return null;
    }

    /**
     * Find parent token in cli arguments
     *
     * @return string|null
     */
    protected static function findTokenInArgv(): ?string
    {
        if ($GLOBALS['argv']) {
            foreach ($GLOBALS['argv'] as $value) {
                if (strpos($value, 'lifecycleToken') === 0) {
                    return explode(':', $value, 2)[1] ?? null;
                }
            }
        }

        return null;
    }
}
