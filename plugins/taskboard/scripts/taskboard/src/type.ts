/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

export interface Mapping {
    tracker_id: number;
    accepts: Array<ListValue>;
}

export interface ListValue {
    id: number;
}

export interface ColumnDefinition {
    id: number;
    label: string;
    color: string;
    mappings: Array<Mapping>;
    is_collapsed: boolean;
}

export interface Swimlane {
    card: Card;
    children_cards: Array<Card>;
    is_loading_children_cards: boolean;
}

export interface MappedListValue extends ListValue {
    label: string;
}

export interface Card {
    id: number;
    tracker_id: number;
    label: string;
    xref: string;
    rank: number;
    color: string;
    background_color: string;
    artifact_html_uri: string;
    assignees: Array<User>;
    has_children: boolean;
    mapped_list_value: MappedListValue | null;
    initial_effort: number | null;
    remaining_effort: number | null;
    is_open: boolean;
    is_collapsed: boolean;
}

export interface User {
    id: number;
    avatar_url: string;
    display_name: string;
}

export interface CardPosition {
    ids: number[];
    direction: Direction;
    compared_to: number;
}

export enum Direction {
    BEFORE = "before",
    AFTER = "after"
}