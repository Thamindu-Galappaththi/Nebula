(function () {
  const SELECTOR = 'select.form-select, select.form-control';

  function shouldEnhance(select) {
    if (!select || select.tagName !== 'SELECT') {
      return false;
    }
    if (select.dataset.nebulaSelect === '1') {
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
      select.id === 'perPageSelect'
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
    wrap.appendChild(menu);

    function close() {
      wrap.classList.remove('is-open', 'drop-up');
      toggle.setAttribute('aria-expanded', 'false');
    }

    function open() {
      document.querySelectorAll('.nebula-select.is-open').forEach(function (el) {
        if (el !== wrap) {
          el.classList.remove('is-open', 'drop-up');
        }
      });
      wrap.classList.add('is-open');
      wrap.classList.remove('drop-up');
      toggle.setAttribute('aria-expanded', 'true');

      const rect = menu.getBoundingClientRect();
      if (rect.bottom > window.innerHeight - 12 && toggle.getBoundingClientRect().top > rect.height + 24) {
        wrap.classList.add('drop-up');
      }

      const active = menu.querySelector('.is-selected');
      if (active) {
        active.scrollIntoView({ block: 'nearest' });
      }
    }

    function render() {
      const selected = select.options[select.selectedIndex];
      toggle.textContent = selected ? selected.text : '';
      toggle.title = toggle.textContent;
      toggle.disabled = select.disabled;
      wrap.classList.toggle('is-disabled', select.disabled);

      menu.replaceChildren();
      Array.from(select.options).forEach(function (opt, index) {
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
          if (opt.disabled) {
            return;
          }
          select.selectedIndex = index;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          close();
          render();
        });
        menu.appendChild(optionBtn);
      });
    }

    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      if (select.disabled) {
        return;
      }
      if (wrap.classList.contains('is-open')) {
        close();
      } else {
        open();
      }
    });

    select.addEventListener('change', render);

    new MutationObserver(render).observe(select, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['disabled']
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

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.nebula-select')) {
      document.querySelectorAll('.nebula-select.is-open').forEach(function (el) {
        el.classList.remove('is-open', 'drop-up');
      });
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.nebula-select.is-open').forEach(function (el) {
        el.classList.remove('is-open', 'drop-up');
      });
    }
  });

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
