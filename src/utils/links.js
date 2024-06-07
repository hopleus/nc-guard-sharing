function isOnlyForAuthUser(attributes) {
  return !!attributes.find(
    (attr) => attr.key === "only_for_auth_user" && attr.enabled === true,
  );
}

export function checkLinks() {
  const list = document.querySelectorAll(
    ".sharing-link-list > .sharing-entry__link",
  );

  for (const link of list) {
    const { __vue__ } = link;
    const { share, index } = __vue__ ?? {};
    const { attributes } = share ?? [];

    if (isOnlyForAuthUser(attributes ?? [])) {
      const elTitle = link.querySelector(".sharing-entry__title");

      if (elTitle) {
        if (!index || index === 1) {
          elTitle.innerText = t("nc-guard-sharing", "Share link");
        } else {
          elTitle.innerText = t("nc-guard-sharing", "Share link ({index})", {
            index,
          });
        }
      }

      link.classList.add("sharing-entry__guard");
    }
  }
}
