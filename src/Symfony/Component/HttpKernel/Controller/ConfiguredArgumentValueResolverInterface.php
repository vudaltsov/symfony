<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\ControllerMetadata\Config\ArgumentConfigInterface;

/**
 * @author Valentin Udaltsov <udaltsov.valentin@gmail.com>
 */
interface ConfiguredArgumentValueResolverInterface
{
    /**
     * Returns possible value(s).
     */
    public function resolveConfigured(Request $request, ArgumentMetadata $argument, ArgumentConfigInterface $config): \Generator;
}
