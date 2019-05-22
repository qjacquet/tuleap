/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";

import localVue from "../../../support/local-vue.js";
import { createStoreMock } from "../../../support/store-wrapper.spec-helper.js";
import { create } from "../../../support/factories.js";
import PostAction from "./PostAction.vue";

describe("PostAction", () => {
    let store;
    const date_field_id = 43;
    const date_field = create("field", { field_id: date_field_id, type: "date" });
    const int_field_id = 44;
    const int_field = create("field", { field_id: int_field_id, type: "int" });
    const float_field_id = 45;
    const float_field = create("field", { field_id: float_field_id, type: "float" });
    const status_field_id = 46;
    const status_field = create("field", { field_id: status_field_id, type: "sb" });
    let wrapper;

    beforeEach(() => {
        const current_tracker = {
            fields: [date_field, int_field, float_field, status_field]
        };

        const store_options = {
            state: {
                transitionModal: {
                    current_transition: create("transition"),
                    is_modal_save_running: false
                },
                current_tracker: current_tracker
            },
            getters: {
                "transitionModal/set_value_action_fields": [date_field, int_field, float_field],
                "transitionModal/post_actions": [],
                current_workflow_field: status_field,
                is_workflow_advanced: false
            }
        };

        store = createStoreMock(store_options);

        wrapper = shallowMount(PostAction, {
            mocks: { $store: store },
            propsData: { post_action: create("post_action", "presented") },
            localVue
        });
    });

    afterEach(() => store.reset());

    describe("Frozen field is valid", () => {
        it("should be false if workflow is advanced", () => {
            store.getters.is_workflow_advanced = true;
            expect(wrapper.vm.frozen_fields_is_valid).toBe(false);
        });

        it("should be false if there are no writable fields available", () => {
            store.state.current_tracker.fields = [];
            expect(wrapper.vm.frozen_fields_is_valid).toBe(false);
        });

        it("should be false if the post action is already present once", () => {
            store.getters["transitionModal/post_actions"] = [
                create("post_action", { type: "frozen_fields" })
            ];
            expect(wrapper.vm.frozen_fields_is_valid).toBe(false);
        });

        it("should be true otherwise", () => {
            expect(wrapper.vm.frozen_fields_is_valid).toBe(true);
        });
    });

    describe("set field value is valid", () => {
        it("should be false if there are no fields available", () => {
            store.getters["transitionModal/set_value_action_fields"] = [];
            expect(wrapper.vm.set_field_value_is_valid).toBe(false);
        });

        it("should be true otherwise", () => {
            expect(wrapper.vm.set_field_value_is_valid).toBe(true);
        });
    });
});