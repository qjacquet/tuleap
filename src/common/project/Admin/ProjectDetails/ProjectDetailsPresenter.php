<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 */

namespace Tuleap\Project\Admin\ProjectDetails;

use Project;
use TemplateSingleton;
use Tuleap\Project\Admin\ProjectGlobalVisibilityPresenter;

class ProjectDetailsPresenter
{
    public $group_id;
    public $group_name;
    public $project_short_description;
    public $short_description_label;
    public $group_info;
    public $description_fields_representation;
    public $public_information_label;
    public $project_name_label;
    public $project_name_placeholder;
    public $short_description_info;
    public $update_button;
    public $project_type_label;
    public $project_type;

    /**
     * @var ProjectHierarchyPresenter
     */
    public $project_hierarchy_presenter;
    public $project_trove_category_label;
    /**
     * @var array
     */
    public $project_trove_categories;
    public $are_project_categories_used;
    public $empty_project_trove_categories;
    public $project_global_visibility_presenter;

    public function __construct(
        Project $project,
        array $group_info,
        array $description_fields_representation,
        ProjectHierarchyPresenter $project_hierarchy_presenter,
        ProjectGlobalVisibilityPresenter $project_global_visibility_presenter,
        $are_project_categories_used,
        array $project_trove_categories
    ) {
        $this->group_id                            = $project->getID();
        $this->group_info                          = $group_info;
        $this->description_fields_representation   = $description_fields_representation;
        $this->project_hierarchy_presenter         = $project_hierarchy_presenter;
        $this->project_global_visibility_presenter = $project_global_visibility_presenter;
        $this->are_project_categories_used         = $are_project_categories_used;
        $this->project_trove_categories            = $project_trove_categories;
        $this->group_name                          = $group_info['group_name'];
        $this->project_short_description           = $group_info['short_description'];

        $this->project_type                      = $this->getLocalizedType($project->getType());

        $this->public_information_label       = _('Public information');
        $this->project_name_label             = _('Project name');
        $this->project_name_placeholder       = _('Project name...');
        $this->short_description_label        = _('Short description');
        $this->short_description_info         = _("Short description shouldn't exceed 255 characters.");
        $this->project_type_label             = _('Project type');
        $this->update_button                  = _('Save information');
        $this->project_trove_category_label   = _('Project trove categories');
        $this->empty_project_trove_categories = _('No project trove categories');
    }

    private function getLocalizedType($project_type_id)
    {
        $localized_types = TemplateSingleton::instance()->getLocalizedTypes();
        return $localized_types[$project_type_id];
    }
}
