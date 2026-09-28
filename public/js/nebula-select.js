(function () {
  const SELECTOR = 'select.form-select, select.form-control';

  function shouldEnhance(select) {
    if (!select || select.tagName !== 'SELECT') {
      return false;
    }
    if (select.dataset.nebulaSelect === '1' || select.dataset.nebulaSelect === 'off') {
      return false;
    }
    if (select.multiple || Number(select.getAttribute('size') || 1) > 1) {
      return false;
    }
    if (select.closest('.nebula-select')) {
      return false;
    }
    return select.matches(SELECTOR);
  }

  function isNativeHidden(select) {
    if (select.hidden) {
      return true;
    }
    const style = select.style;
    return style.display === 'none' || style.visibility === 'hidden';
  }

  function triggerChange(select) {
    select.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function enhance(select) {
    if (!shouldEnhance(select)) {
      return;
    }

    select.dataset.nebulaSelect = '1';

    const wrap = document.createElement('div');
    wrap.className = 'nebula-select';
    if (
      select.classList.contains('form-select-sm') ||
      select.classList.contains('page-size-select') ||
      select.id === 'perPageSelect' ||
      select.id === 'terminationPerPage'
    ) {
      wrap.classList.add('nebula-select-sm');
    }

    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('nebula-select-native');
    select.setAttribute('aria-hidden', 'true');
    select.tabIndex = -1;

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'form-select nebula-select-toggle';
    if (select.classList.contains('form-select-sm')) {
      toggle.classList.add('form-select-sm');
    }
    toggle.setAttribute('aria-haspopup', 'listbox');
    toggle.setAttribute('aria-expanded', 'false');
    wrap.appendChild(toggle);

    const menu = document.createElement('div');
    menu.className = 'nebula-select-menu';
    menu.setAttribute('role', 'listbox');

    const searchWrap = document.createElement('div');
    searchWrap.className = 'nebula-select-search';
    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.className = 'form-control form-control-sm nebula-select-search-input';
    searchInput.placeholder = 'Search...';
    searchInput.setAttribute('aria-label', 'Search options');
    searchInput.autocomplete = 'off';
    searchWrap.appendChild(searchInput);

    const optionsWrap = document.createElement('div');
    optionsWrap.className = 'nebula-select-options';

    menu.appendChild(searchWrap);
    menu.appendChild(optionsWrap);
    wrap.appendChild(menu);

    function syncVisibility() {
      wrap.style.display = isNativeHidden(select) ? 'none' : '';
      if (isNativeHidden(select)) {
        close();
      }
    }

    function close() {
      wrap.classList.remove('is-open', 'drop-up');
      toggle.setAttribute('aria-expanded', 'false');
      menu.style.top = '';
      menu.style.left = '';
      menu.style.bottom = '';
      menu.style.width = '';
      menu.style.maxWidth = '';
      menu.style.maxHeight = '';
      searchInput.value = '';
    }

    function positionMenu() {
      const pad = 12;
      const toggleRect = toggle.getBoundingClientRect();
      const width = Math.max(120, Math.min(toggleRect.width, window.innerWidth - pad * 2));
      let left = toggleRect.left;
      if (left + width > window.innerWidth - pad) {
        left = window.innerWidth - pad - width;
      }
      if (left < pad) {
        left = pad;
      }

      menu.style.position = 'fixed';
      menu.style.left = left + 'px';
      menu.style.width = width + 'px';
      menu.style.maxWidth = width + 'px';
      menu.style.right = 'auto';
      menu.style.zIndex = '2000';

      const spaceBelow = window.innerHeight - toggleRect.bottom - pad;
      const spaceAbove = toggleRect.top - pad;
      const dropUp = spaceBelow < 160 && spaceAbove > spaceBelow;
      const maxHeight = Math.max(160, dropUp ? spaceAbove - 8 : spaceBelow - 8);

      if (dropUp) {
        wrap.classList.add('drop-up');
        menu.style.top = 'auto';
        menu.style.bottom = (window.innerHeight - toggleRect.top + 4) + 'px';
      } else {
        wrap.classList.remove('drop-up');
        menu.style.top = (toggleRect.bottom + 4) + 'px';
        menu.style.bottom = 'auto';
      }
      menu.style.maxHeight = maxHeight + 'px';
    }

    function shouldShowSearch() {
      return !wrap.classList.contains('nebula-select-sm');
    }

    function open() {
      if (select.disabled || isNativeHidden(select)) {
        return;
      }
      document.querySelectorAll('.nebula-select.is-open').forEach(function (el) {
        if (el !== wrap) {
          el.classList.remove('is-open', 'drop-up');
        }
      });
      wrap.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      searchInput.value = '';
      renderOptions();
      positionMenu();
      if (shouldShowSearch() && window.innerWidth > 767) {
        requestAnimationFrame(function () {
          searchInput.focus();
        });
      }
    }

    function renderOptions() {
      const query = String(searchInput.value || '').trim().toLowerCase();
      const showSearch = shouldShowSearch();
      searchWrap.style.display = showSearch ? '' : 'none';
      optionsWrap.replaceChildren();

      let visibleCount = 0;
      Array.from(select.options).forEach(function (opt, index) {
        if (opt.hidden) {
          return;
        }
        if (query && String(opt.text || '').toLowerCase().indexOf(query) === -1) {
          return;
        }
        visibleCount += 1;
        const optionBtn = document.createElement('button');
        optionBtn.type = 'button';
        optionBtn.className = 'nebula-select-option';
        if (opt.selected) {
          optionBtn.classList.add('is-selected');
        }
        if (opt.disabled) {
          optionBtn.classList.add('is-disabled');
        }
        optionBtn.textContent = opt.text;
        optionBtn.title = opt.text;
        optionBtn.disabled = opt.disabled;
        optionBtn.setAttribute('role', 'option');
        optionBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          if (opt.disabled) {
            return;
          }
          select.selectedIndex = index;
          close();
          render();
          triggerChange(select);
        });
        optionsWrap.appendChild(optionBtn);
      });

      if (!visibleCount) {
        const empty = document.createElement('div');
        empty.className = 'nebula-select-empty';
        empty.textContent = 'No matching options';
        optionsWrap.appendChild(empty);
      }
    }

    function render() {
      const selected = select.options[select.selectedIndex];
      toggle.textContent = selected ? selected.text : '';
      toggle.title = toggle.textContent;
      toggle.disabled = select.disabled;
      wrap.classList.toggle('is-disabled', select.disabled);
      syncVisibility();
      renderOptions();
    }

    function hookValueSync() {
      const valueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
      const indexDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');

      if (valueDesc && valueDesc.get && valueDesc.set) {
        Object.defineProperty(select, 'value', {
          configurable: true,
          enumerable: true,
          get() {
            return valueDesc.get.call(this);
          },
          set(next) {
            valueDesc.set.call(this, next);
            render();
          }
        });
      }

      if (indexDesc && indexDesc.get && indexDesc.set) {
        Object.defineProperty(select, 'selectedIndex', {
          configurable: true,
          enumerable: true,
          get() {
            return indexDesc.get.call(this);
          },
          set(next) {
            indexDesc.set.call(this, next);
            render();
          }
        });
      }
    }

    wrap._nebulaPosition = positionMenu;
    hookValueSync();

    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (select.disabled) {
        return;
      }
      if (wrap.classList.contains('is-open')) {
        close();
      } else {
        open();
      }
    });

    searchInput.addEventListener('click', function (e) {
      e.stopPropagation();
    });
    searchInput.addEventListener('input', function () {
      renderOptions();
    });
    searchInput.addEventListener('keydown', function (e) {
      e.stopPropagation();
      if (e.key === 'Enter') {
        e.preventDefault();
        const first = optionsWrap.querySelector('.nebula-select-option:not(.is-disabled)');
        if (first) {
          first.click();
        }
      }
    });

    select.addEventListener('change', function () {
      const selected = select.options[select.selectedIndex];
      toggle.textContent = selected ? selected.text : '';
      toggle.title = toggle.textContent;
    });

    new MutationObserver(render).observe(select, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['disabled', 'style', 'hidden', 'class']
    });

    render();
  }

  function enhanceAll(root) {
    if (!root) {
      return;
    }
    if (root.matches && root.matches(SELECTOR)) {
      enhance(root);
    }
    if (root.querySelectorAll) {
      root.querySelectorAll(SELECTOR).forEach(enhance);
    }
  }

  function closeAll() {
    document.querySelectorAll('.nebula-select.is-open').forEach(function (el) {
      el.classList.remove('is-open', 'drop-up');
    });
  }

  function repositionOpen() {
    document.querySelectorAll('.nebula-select.is-open').forEach(function (el) {
      if (typeof el._nebulaPosition === 'function') {
        el._nebulaPosition();
      }
    });
  }

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.nebula-select')) {
      closeAll();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeAll();
    }
  });

  window.addEventListener('resize', repositionOpen);
  window.addEventListener('scroll', function (e) {
    if (e.target && e.target.closest && e.target.closest('.nebula-select-menu')) {
      return;
    }
    repositionOpen();
  }, true);

  document.addEventListener('DOMContentLoaded', function () {
    enhanceAll(document);
    new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            enhanceAll(node);
          }
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  });
})();
