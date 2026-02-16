(function () {
    'use strict';

    function getPageSlug() {
        var fileName = window.location.pathname.split('/').pop() || 'index.html';
        if (fileName === '' || fileName === '/') {
            fileName = 'index.html';
        }

        if (fileName.toLowerCase() === 'index.html') {
            return 'index';
        }

        return fileName.replace(/\.html$/i, '');
    }

    function applyItem(item) {
        if (!item || !item.selector || !item.field) {
            return;
        }

        var targets = document.querySelectorAll(item.selector);
        if (!targets.length) {
            return;
        }

        targets.forEach(function (target) {
            if (item.field === 'html') {
                target.innerHTML = item.value;
                return;
            }

            if (item.field === 'url') {
                var attr = item.attribute || 'href';
                target.setAttribute(attr, item.value);
                return;
            }

            target.textContent = item.value;
        });
    }

    function loadCmsContent() {
        var page = getPageSlug();
        var endpoint = 'api/contenuti.php?action=public&page=' + encodeURIComponent(page);

        fetch(endpoint, { method: 'GET' })
            .then(function (response) {
                if (!response.ok) {
                    return null;
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success || !Array.isArray(data.items)) {
                    return;
                }

                data.items.forEach(applyItem);
            })
            .catch(function () {
                // CMS override non disponibile: lascia i contenuti statici.
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadCmsContent);
    } else {
        loadCmsContent();
    }
})();
