<?php

declare(strict_types=1);

namespace Oxrun\Core;

use OxidEsales\Eshop\Core\ShopVersion;

class OxidVersion
{
    /**
     * @var null|bool
     */
    private static $oxidVersionV7 = null;

    /**
     * @param callable $fnc
     * @return static
     */
    public static function on70(callable $fnc)
    {
        if (static::isVersion7()) {
            call_user_func($fnc);
        }

        return new static();
    }

    /**
     * @param callable $fnc
     * @return static
     */
    public static function on61(callable $fnc)
    {
        if (static::isVersion7() === false) {
            call_user_func($fnc);
        }

        return new static();
    }

    /**
     * @return bool
     */
    private static function isVersion7(): bool
    {
        if (static::$oxidVersionV7 === null) {
            static::$oxidVersionV7 = version_compare(ShopVersion::getVersion(), '7.0', '>=');
        }
        return static::$oxidVersionV7;
    }
}
