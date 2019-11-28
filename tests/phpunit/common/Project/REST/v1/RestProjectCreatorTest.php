<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types = 1);

namespace Tuleap\Project\REST\v1;

use ForgeAccess;
use ForgeConfig;
use Logger;
use Luracast\Restler\RestException;
use Mockery as M;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ProjectCreator;
use ProjectManager;
use ProjectXMLImporter;
use Service;
use ServiceManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\Template\InvalidTemplateException;
use Tuleap\Project\Registration\Template\ScrumTemplate;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\XML\ProjectXMLMerger;

class RestProjectCreatorTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $project_manager;
    private $creator;
    private $user;
    private $project;
    private $project_creator;
    private $service_manager;
    private $project_XML_importer;
    /**
     * @var string
     */
    private $base_dir;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permissions_checker;

    protected function setUp(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup('RestProjectCreatorTest')->url());

        $this->project_manager      = M::mock(ProjectManager::class);
        $this->project_creator      = M::mock(ProjectCreator::class);
        $this->service_manager      = M::mock(ServiceManager::class);
        $this->project_XML_importer = M::mock(ProjectXMLImporter::class);
        $this->permissions_checker  = M::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permissions_checker->shouldReceive('checkUserCreateAProject')->byDefault();

        $this->creator              = new RestProjectCreator(
            $this->project_manager,
            $this->project_creator,
            new XMLFileContentRetriever(),
            $this->service_manager,
            M::spy(Logger::class),
            new \XML_RNGValidator(),
            $this->project_XML_importer,
            new TemplateFactory(
                new GlyphFinder(
                    new \EventManager()
                ),
                new ProjectXMLMerger(),
                new ConsistencyChecker(
                    $this->service_manager,
                    new XMLFileContentRetriever(),
                )
            ),
            $this->permissions_checker
        );

        $this->user = new \PFUser(['language_id' => 'en_US']);
        $this->project_manager->shouldReceive('userCanCreateProject')->with($this->user)->andReturnTrue()->byDefault();
        $this->project = new ProjectPostRepresentation();
    }

    public function testCreateThrowExceptionWhenUserCannotCreateProjects()
    {
        $this->permissions_checker->shouldReceive('checkUserCreateAProject')->with($this->user)->andThrow(new MaxNumberOfProjectReachedException());

        $this->expectException(RestException::class);

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateThrowExceptionWhenNeitherTemplateIdNorTemplateNameIsProvided()
    {
        $this->expectException(InvalidTemplateException::class);

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateWithDefaultProjectTemplate()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;

        $this->project_creator->shouldReceive('createFromRest')->with(
            'gpig',
            'Guinea Pig',
            [
                'project' => [
                    'form_short_description' => 'foo',
                    'is_test' => false,
                    'is_public' => false,
                    'built_from_template' => 100,
                ]
            ],
        );

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateWithDefaultProjectTemplateAndExcludeRestrictedUsers()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;
        $this->project->allow_restricted = false;

        $this->project_creator->shouldReceive('createFromRest')->with(
            'gpig',
            'Guinea Pig',
            [
                'project' => [
                    'form_short_description' => 'foo',
                    'is_test' => false,
                    'is_public' => false,
                    'built_from_template' => 100,
                    'allow_restricted' => false,
                ]
            ],
        );

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateFromXMLTemplate()
    {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->project->xml_template_name = ScrumTemplate::NAME;
        $this->project->shortname         = 'gpig';
        $this->project->label             = 'Guinea Pig';
        $this->project->description       = 'foo';
        $this->project->is_public         = false;
        $this->project->allow_restricted  = false;

        $template_project = new \Project(['group_id' => 100]);
        $this->project_manager->shouldReceive('getProject')->with(100)->andReturn($template_project);

        $services = [
            M::mock(Service::class, ['getShortName' => \trackerPlugin::SERVICE_SHORTNAME, 'getId' => 12]),
            M::mock(Service::class, ['getShortName' => \GitPlugin::SERVICE_SHORTNAME, 'getId' => 13]),
            M::mock(Service::class, ['getShortName' => \AgileDashboardPlugin::PLUGIN_SHORTNAME, 'getId' => 14]),
        ];

        $this->service_manager->shouldReceive('getListOfServicesAvailableAtSiteLevel')->andReturns($services);
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($template_project)->andReturns($services);

        $this->project_XML_importer->shouldReceive('importWithProjectData')->with(
            \Hamcrest\Core\IsEqual::equalTo(new ImportConfig()),
            M::on(static function (ArchiveInterface $archive) {
                return realpath($archive->getExtractionPath()) === realpath(dirname((new ScrumTemplate(M::mock(GlyphFinder::class), new ProjectXMLMerger(), M::mock(ConsistencyChecker::class)))->getXMLPath()));
            }),
            \Hamcrest\Core\IsEqual::equalTo(new SystemEventRunnerForProjectCreationFromXMLTemplate()),
            M::on(static function (\ProjectCreationData $data) {
                return $data->getUnixName() === 'gpig' &&
                    $data->getFullName() === 'Guinea Pig' &&
                    $data->getShortDescription() === 'foo' &&
                    $data->getAccess() === \Project::ACCESS_PRIVATE_WO_RESTRICTED;
            })
        );

        $this->creator->create($this->user, $this->project);
    }
}