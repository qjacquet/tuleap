<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepositoryFactory;
use PFUser;
use PHPUnit\Framework\TestCase;
use ReferenceManager;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use GitRepository;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDAO;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;

class PullRequestUpdaterTest extends TestCase
{

    /**
     * @var PullRequestUpdater
     */
    private $pull_request_updater;

    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|InlineCommentDAO
     */
    private $inline_comments_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $git_repository_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|GitExecFactory
     */
    private $git_exec_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|GitExec
     */
    private $git_exec;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|GitPullRequestReferenceUpdater
     */
    private $pr_reference_updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PullRequestMerger
     */
    private $pr_merger;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TimelineEventCreator
     */
    private $timeline_event_creator;

    protected function setUp(): void
    {
        $reference_manager = \Mockery::mock(ReferenceManager::class);

        $this->dao = new Dao();
        $this->inline_comments_dao    = \Mockery::spy(InlineCommentDAO::class);
        $this->git_repository_factory = \Mockery::mock(GitRepositoryFactory::class);
        $this->git_exec_factory       = \Mockery::mock(GitExecFactory::class);
        $this->pr_reference_updater   = \Mockery::mock(GitPullRequestReferenceUpdater::class);
        $this->pr_merger              = \Mockery::mock(PullRequestMerger::class);
        $this->timeline_event_creator = \Mockery::mock(TimelineEventCreator::class);
        $this->pull_request_updater   = new PullRequestUpdater(
            new Factory($this->dao, $reference_manager),
            $this->pr_merger,
            $this->inline_comments_dao,
            \Mockery::mock(InlineCommentUpdater::class),
            new FileUniDiffBuilder(),
            $this->timeline_event_creator,
            $this->git_repository_factory,
            $this->git_exec_factory,
            $this->pr_reference_updater
        );

        $this->git_exec = \Mockery::mock(GitExec::class);
        $this->user     = \Mockery::mock(PFUser::class, ['getId' => 1337]);
    }

    public function testItUpdatesSourceBranchInPRs()
    {
        $this->pr_reference_updater->shouldReceive('updatePullRequestReference');
        $this->git_exec->shouldReceive('getCommonAncestor');
        $this->pr_merger->shouldReceive('detectMergeabilityStatus');
        $this->timeline_event_creator->shouldReceive('storeUpdateEvent');

        $pr1_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'master', 'sha2', 0);
        $pr2_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'other', 'sha2', 0);
        $pr3_id = $this->dao->create(1, 'title', 'description', 1, 0, 'master', 'sha1', 1, 'other', 'sha2', 0);

        $git_repo = \Mockery::mock(GitRepository::class, ['getId' => 1]);

        $this->inline_comments_dao->shouldReceive('searchUpToDateByPullRequestId')->andReturns([]);

        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($git_repo);
        $this->git_exec_factory->shouldReceive('getGitExec')->with($git_repo)->andReturns($this->git_exec);

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);
        $pr3 = $this->dao->searchByPullRequestId($pr3_id);

        $this->assertEquals('sha1new', $pr1['sha1_src']);
        $this->assertEquals('sha1new', $pr2['sha1_src']);
        $this->assertEquals('sha1', $pr3['sha1_src']);
    }

    public function testItDoesNotUpdateSourceBranchOfOtherRepositories()
    {
        $this->pr_reference_updater->shouldReceive('updatePullRequestReference');
        $this->git_exec->shouldReceive('getCommonAncestor');
        $this->pr_merger->shouldReceive('detectMergeabilityStatus');
        $this->timeline_event_creator->shouldReceive('storeUpdateEvent');

        $pr1_id = $this->dao->create(2, 'title', 'description', 1, 0, 'dev', 'sha1', 2, 'master', 'sha2', 0);
        $pr2_id = $this->dao->create(2, 'title', 'description', 1, 0, 'master', 'sha1', 2, 'dev', 'sha2', 0);

        $git_repo = \Mockery::mock(GitRepository::class, ['getId' => 1]);

        $this->inline_comments_dao->shouldReceive('searchUpToDateByPullRequestId')->andReturns([]);

        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($git_repo);
        $this->git_exec_factory->shouldReceive('getGitExec')->with($git_repo)->andReturns($this->git_exec);

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);

        $this->assertEquals('sha1', $pr1['sha1_src']);
        $this->assertEquals('sha1', $pr2['sha1_src']);
    }

    public function testItDoesNotUpdateClosedPRs()
    {
        $this->pr_reference_updater->shouldReceive('updatePullRequestReference');
        $this->git_exec->shouldReceive('getCommonAncestor');
        $this->pr_merger->shouldReceive('detectMergeabilityStatus');
        $this->timeline_event_creator->shouldReceive('storeUpdateEvent');

        $pr1_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'master', 'sha2', 0);
        $pr2_id = $this->dao->create(1, 'title', 'description', 1, 0, 'master', 'sha1', 1, 'dev', 'sha2', 0);

        $this->dao->markAsMerged($pr1_id);
        $this->dao->markAsAbandoned($pr2_id);

        $git_repo = \Mockery::mock(GitRepository::class, ['getId' => 1]);

        $this->inline_comments_dao->shouldReceive('searchUpToDateByPullRequestId')->andReturns([]);

        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($git_repo);
        $this->git_exec_factory->shouldReceive('getGitExec')->with($git_repo)->andReturns($this->git_exec);

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);

        $this->assertEquals('sha1', $pr1['sha1_src']);
        $this->assertEquals('sha1', $pr2['sha1_src']);
    }
}