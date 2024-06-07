<template>
  <ul>
    <li class="sharing-entry sharing-entry__guard">
      <div class="avatar-external icon-public-white"></div>
      <div class="sharing-entry__summary">
        <div class="sharing-entry__desc">
          <span class="sharing-entry__title">
            {{ t("nc-guard-sharing", "Authorized access by link") }}
          </span>
        </div>
      </div>
      <NcActions class="sharing-entry__actions" menu-align="right">
        <!-- Create new share -->
        <NcActionButton
          class="new-share-link"
          :title="t('nc-guard-sharing', 'Create a new share link')"
          :aria-label="t('nc-guard-sharing', 'Create a new share link')"
          :icon="loading ? 'icon-loading-small' : 'icon-add'"
          @click.prevent.stop="onNewLinkShare"
        />
      </NcActions>
    </li>
  </ul>
</template>

<script>
import { NcActionButton, NcActions } from "@nextcloud/vue";
import RequestMixin from "../mixins/RequestMixin";
import { generateUrl } from "@nextcloud/router";
import { showSuccess } from "@nextcloud/dialogs";

export default {
  name: "GuardLink",
  components: {
    NcActions,
    NcActionButton,
  },
  mixins: [RequestMixin],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true,
    },
  },
  data() {
    return {
      loading: false,
    };
  },
  computed: {
    getFullPath() {
      if (this.fileInfo) {
        if (this.fileInfo.path.endsWith("/")) {
          return this.fileInfo.path.concat(this.fileInfo.name);
        } else {
          return this.fileInfo.path.concat("/", this.fileInfo.name);
        }
      } else {
        return "None";
      }
    },
  },
  methods: {
    /**
     * Create a new share link and append it to the list
     */
    async onNewLinkShare() {
      // do not run again if already loading
      if (this.loading) {
        return;
      }

      this.loading = true;
      const response = await this.createLink(this.getFullPath);
      this.loading = false;

      let resultToken = "";

      if (response.data && response.data.token) {
        resultToken = response.data.token;
      }

      const shareLink =
        window.location.protocol +
        "//" +
        window.location.host +
        generateUrl("/s/") +
        resultToken;

      if (!navigator.clipboard) {
        this.fallbackCopyTextToClipboard(shareLink);
      } else {
        await navigator.clipboard
          .writeText(shareLink)
          .then(() => {
            console.debug("NcGuardSharing: Link copied");
            showSuccess(t("files_sharing", "Link copied"));
          })
          .catch((reason) => {
            console.debug("NcGuardSharing: Could not copy");
            console.debug(reason);
          });
      }

      this.refreshSidebar(this.fileInfo);
    },

    fallbackCopyTextToClipboard(text) {
      const textArea = document.createElement("textarea");
      textArea.value = text;

      // Avoid scrolling to bottom
      textArea.style.top = "0";
      textArea.style.left = "0";
      textArea.style.position = "fixed";

      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();

      try {
        const successful = document.execCommand("copy");
        const msg = successful ? "successful" : "unsuccessful";
        console.debug(
          "NcGuardSharing_Fallback: Copying text command was " + msg,
        );
      } catch (err) {
        console.error("NcGuardSharing_Fallback: Oops, unable to copy", err);
      }

      document.body.removeChild(textArea);
    },
  },
};
</script>

<style lang="scss">
.sharing-entry__guard {
  .avatar-link-share {
    background-color: #009e2d !important;
  }
}
</style>

<style lang="scss" scoped>
ul {
  padding: 0 6px;
}

.sharing-entry {
  display: flex;
  align-items: center;
  min-height: 44px;

  &__summary {
    padding: 8px;
    padding-left: 10px;
    display: flex;
    justify-content: space-between;
    flex: 1 0;
    min-width: 0;
  }

  &__desc {
    p {
      color: var(--color-text-maxcontrast);
    }
  }

  &__title {
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    max-width: inherit;
  }

  &__actions {
    margin-left: auto !important;
  }

  .avatar-external {
    width: 32px;
    height: 32px;
    line-height: 32px;
    font-size: 18px;
    background-color: #009e2d;
    border-radius: 50%;
    flex-shrink: 0;
  }
}
</style>
