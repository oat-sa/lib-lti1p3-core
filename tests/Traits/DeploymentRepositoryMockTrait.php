<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Traits;

use Exception;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use PHPUnit\Framework\TestCase;

trait DeploymentRepositoryMockTrait
{
    use DeploymentMockTrait;

    public function getDeploymentRepositoryMock(): DeploymentRepositoryInterface
    {
        /** @var TestCase $this */
        $mockDeploymentRepository = $this->createMock(DeploymentRepositoryInterface::class);
        $mockDeploymentRepository->method('findByIssuer')->with('issuer')->willReturn($this->getDeploymentMock());

        return $mockDeploymentRepository;
    }

    public function getEmptyDeploymentRepository(): DeploymentRepositoryInterface
    {
        $mockDeploymentRepository = $this->createMock(DeploymentRepositoryInterface::class);
        $mockDeploymentRepository->method('findByIssuer')->with('issuer')->willReturn(null);

        return $mockDeploymentRepository;
    }

    public function getDeploymentRepositoryExceptionOnMethod(string $method): DeploymentRepositoryInterface
    {
        $mockDeploymentRepository = $this->getDeploymentRepositoryMock();
        $mockDeploymentRepository->method($method)->willThrowException(new Exception('custom error'));

        return $mockDeploymentRepository;
    }
}
