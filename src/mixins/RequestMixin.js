import axios from "@nextcloud/axios";
import { showError, showSuccess } from "@nextcloud/dialogs";
import { generateUrl } from "@nextcloud/router";
import { checkLinks } from "../utils/links";

export default {
  created() {
    const elShareTab = document.querySelector("#tab-sharing > .sharingTab");
    const shareTab = elShareTab.__vue__;

    shareTab.$watch("loading", function (newVal) {
      if (newVal !== false) return;
      checkLinks();
    });
  },
  methods: {
    refreshSidebar(fileInfo) {
      const shareTab = OCA.Files.Sidebar.state.tabs.find(
        (e) => e.id === "sharing",
      );
      if (shareTab) {
        shareTab.update(fileInfo);
        console.debug("NcGuardSharing: Updated share tab");
      } else {
        console.debug("NcGuardSharing: No share tab to update");
      }
    },
    async createLink(path) {
      const data = {
        path,
        shareType: 3,
      };

      try {
        const response = await axios.post(
          generateUrl("/apps/nc-guard-sharing/new"),
          data,
        );

        const returnValue = { ret: 0, data: response.data };
        console.debug("NcGuardSharing: Guard link created");
        showSuccess(t("nc-guard-sharing", "Guard link created"));

        return returnValue;
      } catch (e) {
        const returnValue = { ret: 1, data: e.response.data };

        if (e.response.data && e.response.data.message) {
          showError(t("nc-guard-sharing", e.response.data.message));
        } else {
          showError(
            t("nc-guard-sharing", "Error occurred while creating guard link"),
          );
        }

        console.error(
          "NcGuardSharing: Error occurred while creating guard link",
        );
        console.error(e.response);

        return returnValue;
      }
    },
  },
};
