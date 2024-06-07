import Vue from "vue";
import { translate as t, translatePlural as n } from "@nextcloud/l10n";
import GuardLink from "./components/GuardLink.vue";
import { checkLinks } from "./utils/links";

Vue.prototype.OCA = window.OCA;
Vue.mixin({
  methods: {
    t,
    n,
  },
});

console.debug("NcGuardSharing: main init");

// Add new section
let sectionInstance = null;
let props = null;
const View = Vue.extend(GuardLink);

window.addEventListener("DOMContentLoaded", function () {
  if (!OCA.Sharing || !OCA.Sharing.ShareTabSections) return;

  OCA.Sharing.ShareTabSections.registerSection((el, fileInfo) => {
    if (typeof fileInfo === "undefined" || typeof el === "undefined") return;

    // if instance exists, just update props
    if (sectionInstance && window.document.contains(sectionInstance.$el)) {
      props.fileInfo = fileInfo;
    } else {
      // create new instance
      if (sectionInstance) {
        // if sectionInstance.$el doesn't exist anymore (after changing folder for example)
        sectionInstance.$destroy();
      }

      sectionInstance = new View({
        props: { fileInfo },
      });

      props = Vue.observable({
        ...sectionInstance._props,
        ...{ fileInfo },
      });
      sectionInstance._props = props;

      const component = sectionInstance.$mount();
      el[0].appendChild(component.$el);

      setTimeout(() => {
        checkLinks();
      }, 200);
    }
  });
});
