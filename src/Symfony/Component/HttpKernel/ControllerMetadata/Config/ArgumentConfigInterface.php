<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\ControllerMetadata\Config;

/**
 * @author Valentin Udaltsov <udaltsov.valentin@gmail.com>
 */
interface ArgumentConfigInterface
{
    /**
     * Returns the FQCN of the argument value resolver that is able to resolve
     * the argument with this config.
     */
    public static function resolvedBy(): string;
}
