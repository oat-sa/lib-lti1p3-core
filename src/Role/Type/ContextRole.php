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

namespace OAT\Library\Lti1p3Core\Role\Type;

use OAT\Library\Lti1p3Core\Role\AbstractRole;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lis-vocabulary-for-context-roles
 */
class ContextRole extends AbstractRole
{
    public static function getType(): string
    {
        return static::TYPE_CONTEXT;
    }

    public static function getNameSpace(): string
    {
        return static::NAMESPACE_CONTEXT;
    }

    public function getSubName(): ?string
    {
        if (strpos($this->name, static::getNamespace() . '/') === 0) {
            $exp = explode('#', $this->name);

            if (sizeof($exp) === 2) {
                return end($exp);
            }
        }

        return null;
    }

    public function isCore(): bool
    {
        if (strpos($this->name, static::getNamespace() . '/') === 0) {
            return false;
        }

        if (strpos($this->name, static::getNamespace()) === 0) {
            $exp = explode('#', $this->name);

            return $this->getMap()[end($exp)];
        }

        return $this->getMap()[$this->name];
    }

    protected function isValid(): bool
    {
        if (strpos($this->name, static::getNamespace() . '/') === 0) {
            $exp = explode('#', substr($this->name, strlen(static::getNamespace() . '/')));

            $mainName = current($exp);
            $subName = end($exp);

            return array_key_exists($mainName, $this->getMap()) && in_array($subName, $this->getSubMap()[$mainName]);
        }

        if (strpos($this->name, static::getNamespace()) === 0) {
            $exp = explode('#', $this->name);

            return array_key_exists(end($exp), $this->getMap());
        }

        return array_key_exists($this->name, $this->getMap());
    }

    protected function getMap(): array
    {
        return [
            'Administrator' => true,
            'ContentDeveloper' => true,
            'Instructor' => true,
            'Learner' => true,
            'Mentor' => true,
            'Manager' => false,
            'Member' => false,
            'Officer' => false,
        ];
    }

    private function getSubMap(): array
    {
       return [
           'Administrator' => [
               'Administrator',
               'Developer',
               'ExternalDeveloper',
               'ExternalSupport',
               'ExternalSystemAdministrator',
               'Support',
               'SystemAdministrator',
           ],
           'ContentDeveloper' => [
               'ContentDeveloper',
               'ContentExpert',
               'ExternalContentExpert',
               'Librarian',
           ],
           'Instructor' => [
               'ExternalInstructor',
               'Grader',
               'GuestInstructor',
               'Lecturer',
               'PrimaryInstructor',
               'SecondaryInstructor',
               'TeachingAssistant',
               'TeachingAssistantGroup',
               'TeachingAssistantOffering',
               'TeachingAssistantSection',
               'TeachingAssistantSectionAssociation',
               'TeachingAssistantTemplate',
           ],
           'Learner' => [
               'ExternalLearner',
               'GuestLearner',
               'Instructor',
               'Learner',
               'NonCreditLearner',
           ],
           'Manager' => [
               'AreaManager',
               'CourseCoordinator',
               'ExternalObserver',
               'Manager',
               'Observer',
           ],
           'Member' => [
               'Member'
           ],
           'Mentor' => [
               'Advisor',
               'Auditor',
               'ExternalAdvisor',
               'ExternalAuditor',
               'ExternalLearningFacilitator',
               'ExternalMentor',
               'ExternalReviewer',
               'ExternalTutor',
               'LearningFacilitator',
               'Mentor',
               'Reviewer',
               'Tutor',
           ],
           'Officer' => [
               'Chair',
               'Communications',
               'Secretary',
               'Treasurer',
               'Vice-Chair',
           ],
       ];
    }
}
