<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Exception;

use RuntimeException;

final class CannotBeWidened extends RuntimeException implements ContainerException
{
}
