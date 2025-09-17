class WPCommentsRepliesModal {
  constructor() {
    this.modal = null;
    this.searchInput = null;
    this.repliesList = null;
    this.selectedReply = null;
    this.replies = wpcommentsReplies.replies || [];
    this.filteredReplies = [...this.replies];

    this.init();
  }

  init() {
    this.createModal();
    this.bindEvents();
    this.addQuickTagButton();
  }

  createModal() {
    const modalHTML = `
            <div id="wpcomments-replies-modal" class="wpcomments-modal" style="display: none;">
                <div class="wpcomments-modal-backdrop"></div>
                <div class="wpcomments-modal-content">
                    <div class="wpcomments-modal-header">
                        <h3>${wpcommentsReplies.i18n.btn}</h3>
                        <button type="button" class="wpcomments-modal-close" aria-label="关闭">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="wpcomments-modal-body">
                        <div class="wpcomments-search-container">
                            <input type="text" id="wpcomments-search" placeholder="搜索回复模板..." class="wpcomments-search-input">
                        </div>
                        <div class="wpcomments-replies-container">
                            <div id="wpcomments-replies-list" class="wpcomments-replies-list">
                                ${this.renderRepliesList()}
                            </div>
                            <div class="wpcomments-no-results" style="display: none;">
                                <p>${wpcommentsReplies.i18n.noItem}</p>
                                <a href="${wpcommentsReplies.i18n.optionsUrl}" target="_blank">
                                    ${wpcommentsReplies.i18n.pleaseAdd} ${wpcommentsReplies.i18n.here}
                                </a>
                            </div>
                        </div>
                        <div class="wpcomments-preview-container" style="display: none;">
                            <h4>预览</h4>
                            <div class="wpcomments-preview-content"></div>
                        </div>
                    </div>
                    <div class="wpcomments-modal-footer">
                        <button type="button" class="button button-secondary wpcomments-cancel">
                            ${wpcommentsReplies.i18n.cancel}
                        </button>
                        <button type="button" class="button button-primary wpcomments-insert" disabled>
                            ${wpcommentsReplies.i18n.insert}
                        </button>
                    </div>
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
    this.modal = document.getElementById("wpcomments-replies-modal");
    this.searchInput = document.getElementById("wpcomments-search");
    this.repliesList = document.getElementById("wpcomments-replies-list");
  }

  renderRepliesList() {
    if (this.filteredReplies.length === 0) {
      return "";
    }

    return this.filteredReplies
      .map(
        (reply, index) => `
            <div class="wpcomments-reply-item" data-index="${index}" data-content="${this.escapeHtml(reply.content)}">
                <div class="wpcomments-reply-title">${this.escapeHtml(reply.title)}</div>
                <div class="wpcomments-reply-preview">${this.truncateText(reply.content, 100)}</div>
            </div>
        `,
      )
      .join("");
  }

  bindEvents() {
    const modal = this.modal;
    const closeBtn = modal.querySelector(".wpcomments-modal-close");
    const backdrop = modal.querySelector(".wpcomments-modal-backdrop");
    const cancelBtn = modal.querySelector(".wpcomments-cancel");
    const insertBtn = modal.querySelector(".wpcomments-insert");

    closeBtn.addEventListener("click", () => this.close());
    backdrop.addEventListener("click", () => this.close());
    cancelBtn.addEventListener("click", () => this.close());
    insertBtn.addEventListener("click", () => this.insertReply());

    this.searchInput.addEventListener("input", (e) =>
      this.handleSearch(e.target.value),
    );

    this.repliesList.addEventListener("click", (e) => {
      const replyItem = e.target.closest(".wpcomments-reply-item");
      if (replyItem) {
        this.selectReply(replyItem);
      }
    });

    this.repliesList.addEventListener("dblclick", (e) => {
      const replyItem = e.target.closest(".wpcomments-reply-item");
      if (replyItem) {
        this.selectReply(replyItem);
        this.insertReply();
      }
    });

    modal.addEventListener("keydown", (e) => this.handleKeydown(e));
  }

  addQuickTagButton() {
    if (typeof QTags !== "undefined") {
      QTags.addButton(
        "wpcomments_frequently_replies",
        wpcommentsReplies.i18n.btn,
        () => this.open(),
        "",
        "",
        wpcommentsReplies.i18n.tip,
      );
    }
  }

  open() {
    this.modal.style.display = "block";
    document.body.classList.add("wpcomments-modal-open");
    this.searchInput.focus();
    this.resetSelection();
  }

  close() {
    this.modal.style.display = "none";
    document.body.classList.remove("wpcomments-modal-open");
    this.resetSelection();
    this.clearSearch();
  }

  selectReply(replyItem) {
    this.repliesList
      .querySelectorAll(".wpcomments-reply-item")
      .forEach((item) => {
        item.classList.remove("selected");
      });

    replyItem.classList.add("selected");
    this.selectedReply = replyItem;

    const insertBtn = this.modal.querySelector(".wpcomments-insert");
    insertBtn.disabled = false;

    this.showPreview(replyItem.dataset.content);
  }

  showPreview(content) {
    const previewContainer = this.modal.querySelector(
      ".wpcomments-preview-container",
    );
    const previewContent = this.modal.querySelector(
      ".wpcomments-preview-content",
    );

    previewContent.innerHTML = content;
    previewContainer.style.display = "block";
  }

  hidePreview() {
    const previewContainer = this.modal.querySelector(
      ".wpcomments-preview-container",
    );
    previewContainer.style.display = "none";
  }

  insertReply() {
    if (this.selectedReply) {
      const content = this.selectedReply.dataset.content;
      if (typeof QTags !== "undefined") {
        QTags.insertContent(content);
      }
      this.close();
    }
  }

  handleSearch(query) {
    const searchTerm = query.toLowerCase().trim();

    if (searchTerm === "") {
      this.filteredReplies = [...this.replies];
    } else {
      this.filteredReplies = this.replies.filter(
        (reply) =>
          reply.title.toLowerCase().includes(searchTerm) ||
          reply.content.toLowerCase().includes(searchTerm),
      );
    }

    this.updateRepliesList();
  }

  updateRepliesList() {
    const noResults = this.modal.querySelector(".wpcomments-no-results");

    if (this.filteredReplies.length === 0) {
      this.repliesList.innerHTML = "";
      noResults.style.display = "block";
    } else {
      this.repliesList.innerHTML = this.renderRepliesList();
      noResults.style.display = "none";
    }

    this.resetSelection();
  }

  resetSelection() {
    this.selectedReply = null;
    const insertBtn = this.modal.querySelector(".wpcomments-insert");
    insertBtn.disabled = true;
    this.hidePreview();
  }

  clearSearch() {
    this.searchInput.value = "";
    this.filteredReplies = [...this.replies];
    this.updateRepliesList();
  }

  handleKeydown(e) {
    const items = this.repliesList.querySelectorAll(".wpcomments-reply-item");
    let currentIndex = -1;

    if (this.selectedReply) {
      currentIndex = Array.from(items).indexOf(this.selectedReply);
    }

    switch (e.key) {
      case "Escape":
        e.preventDefault();
        this.close();
        break;

      case "Enter":
        e.preventDefault();
        if (this.selectedReply) {
          this.insertReply();
        }
        break;

      case "ArrowDown":
        e.preventDefault();
        if (currentIndex < items.length - 1) {
          this.selectReply(items[currentIndex + 1]);
        } else if (items.length > 0) {
          this.selectReply(items[0]);
        }
        break;

      case "ArrowUp":
        e.preventDefault();
        if (currentIndex > 0) {
          this.selectReply(items[currentIndex - 1]);
        } else if (items.length > 0) {
          this.selectReply(items[items.length - 1]);
        }
        break;
    }
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  truncateText(text, maxLength) {
    const stripped = text.replace(/<[^>]*>/g, "");
    if (stripped.length <= maxLength) {
      return stripped;
    }
    return stripped.substring(0, maxLength) + "...";
  }
}

document.addEventListener("DOMContentLoaded", function () {
  if (typeof wpcommentsReplies !== "undefined") {
    new WPCommentsRepliesModal();
  }
});
