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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Role\Type;

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Role\RoleInterface;
use OAT\Library\Lti1p3Core\Role\Type\ContextRole;
use PHPUnit\Framework\TestCase;

class ContextRoleTest extends TestCase
{
    /**
     * @dataProvider provideValidRolesMap
     */
    public function testValidRoles(string $roleName, ?string $subName, bool $isCore): void
    {
        $subject = new ContextRole($roleName);

        $this->assertEquals(RoleInterface::TYPE_CONTEXT, $subject::getType());
        $this->assertEquals(RoleInterface::NAMESPACE_CONTEXT, $subject::getNameSpace());
        $this->assertEquals($roleName, $subject->getName());
        $this->assertEquals($subName, $subject->getSubName());
        $this->assertEquals($isCore, $subject->isCore());
    }

    /**
     * @dataProvider provideInvalidRoles
     */
    public function testInvalidRole(string $roleName): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('Role %s is invalid for type context', $roleName));

        new ContextRole($roleName);
    }

    public function provideValidRolesMap(): array
    {
        return [
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager',
                'subName' => null,
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Member',
                'subName' => null,
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer',
                'subName' => null,
                'isCore' => false
            ],
            [
                'roleName' => 'Administrator',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'ContentDeveloper',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'Instructor',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'Learner',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'Mentor',
                'subName' => null,
                'isCore' => true
            ],
            [
                'roleName' => 'Manager',
                'subName' => null,
                'isCore' => false
            ],
            [
                'roleName' => 'Member',
                'subName' => null,
                'isCore' => false
            ],
            [
                'roleName' => 'Officer',
                'subName' => null,
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator',
                'subName' => 'Administrator',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Developer',
                'subName' => 'Developer',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalDeveloper',
                'subName' => 'ExternalDeveloper',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSupport',
                'subName' => 'ExternalSupport',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSystemAdministrator',
                'subName' => 'ExternalSystemAdministrator',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Support',
                'subName' => 'Support',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#SystemAdministrator',
                'subName' => 'SystemAdministrator',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentDeveloper',
                'subName' => 'ContentDeveloper',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentExpert',
                'subName' => 'ContentExpert',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ExternalContentExpert',
                'subName' => 'ExternalContentExpert',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#Librarian',
                'subName' => 'Librarian',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#ExternalInstructor',
                'subName' => 'ExternalInstructor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Grader',
                'subName' => 'Grader',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#GuestInstructor',
                'subName' => 'GuestInstructor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer',
                'subName' => 'Lecturer',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor',
                'subName' => 'PrimaryInstructor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor',
                'subName' => 'SecondaryInstructor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant',
                'subName' => 'TeachingAssistant',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantGroup',
                'subName' => 'TeachingAssistantGroup',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantOffering',
                'subName' => 'TeachingAssistantOffering',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSection',
                'subName' => 'TeachingAssistantSection',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSectionAssociation',
                'subName' => 'TeachingAssistantSectionAssociation',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantTemplate',
                'subName' => 'TeachingAssistantTemplate',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#ExternalLearner',
                'subName' => 'ExternalLearner',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#GuestLearner',
                'subName' => 'GuestLearner',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Instructor',
                'subName' => 'Instructor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Learner',
                'subName' => 'Learner',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#NonCreditLearner',
                'subName' => 'NonCreditLearner',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#AreaManager',
                'subName' => 'AreaManager',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#CourseCoordinator',
                'subName' => 'CourseCoordinator',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#ExternalObserver',
                'subName' => 'ExternalObserver',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Manager',
                'subName' => 'Manager',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Observer',
                'subName' => 'Observer',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Member#Member',
                'subName' => 'Member',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Advisor',
                'subName' => 'Advisor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Auditor',
                'subName' => 'Auditor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAdvisor',
                'subName' => 'ExternalAdvisor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAuditor',
                'subName' => 'ExternalAuditor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalLearningFacilitator',
                'subName' => 'ExternalLearningFacilitator',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalMentor',
                'subName' => 'ExternalMentor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalReviewer',
                'subName' => 'ExternalReviewer',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalTutor',
                'subName' => 'ExternalTutor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#LearningFacilitator',
                'subName' => 'LearningFacilitator',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Mentor',
                'subName' => 'Mentor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Reviewer',
                'subName' => 'Reviewer',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Tutor',
                'subName' => 'Tutor',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Chair',
                'subName' => 'Chair',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Communications',
                'subName' => 'Communications',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Secretary',
                'subName' => 'Secretary',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Treasurer',
                'subName' => 'Treasurer',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Vice-Chair',
                'subName' => 'Vice-Chair',
                'isCore' => false
            ],
        ];
    }

    public function provideInvalidRoles(): array
    {
        return [
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#invalid',
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Invalid',
            ],
            [
                'roleName' => 'Invalid',
            ],
        ];
    }
}
