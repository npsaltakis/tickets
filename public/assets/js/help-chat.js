// Help chat: the visitor picks a question, the server answers (HelpController). Everything is rendered as text.
(function () {
    const root = document.querySelector('[data-help-chat]');
    if (!root) {
        return;
    }

    const toggle = root.querySelector('[data-help-toggle]');
    const panel = root.querySelector('.help-chat-panel');
    const log = root.querySelector('[data-help-log]');
    const questionsBox = root.querySelector('[data-help-questions]');
    let questions = null;

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }
        return node;
    }

    function scrollDown() {
        log.scrollTop = log.scrollHeight;
    }

    function addMessage(node) {
        log.appendChild(node);
        scrollDown();
    }

    function renderQuestions() {
        questionsBox.textContent = '';
        (questions || []).forEach(function (q) {
            const button = el('button', 'help-chat-question', q.label);
            button.type = 'button';
            button.dataset.q = q.id;
            questionsBox.appendChild(button);
        });
    }

    async function loadQuestions() {
        try {
            const resp = await fetch(root.dataset.menuUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = await resp.json();
            questions = data.questions || [];
        } catch {
            questions = [];
            addMessage(el('div', 'help-chat-msg help-chat-msg--bot', root.dataset.errorText));
        }
        renderQuestions();
    }

    function renderAnswer(data) {
        const msg = el('div', 'help-chat-msg help-chat-msg--bot');
        msg.appendChild(el('p', 'help-chat-text', data.text || ''));

        if (Array.isArray(data.items) && data.items.length) {
            const list = el('ul', 'help-chat-items');
            data.items.forEach(function (item) {
                const li = el('li');
                const link = el('a', 'help-chat-item-title', item.title || '');
                link.href = item.url || '#';
                li.appendChild(link);
                if (item.meta) {
                    li.appendChild(el('span', 'help-chat-item-meta', item.meta));
                }
                list.appendChild(li);
            });
            msg.appendChild(list);
        }

        if (Array.isArray(data.links) && data.links.length) {
            const links = el('div', 'help-chat-links');
            data.links.forEach(function (l) {
                const a = el('a', 'help-chat-link', l.label || '');
                a.href = l.url || '#';
                links.appendChild(a);
            });
            msg.appendChild(links);
        }

        addMessage(msg);
    }

    async function ask(id, label) {
        addMessage(el('div', 'help-chat-msg help-chat-msg--user', label));
        const typing = el('div', 'help-chat-msg help-chat-msg--bot help-chat-typing', '…');
        addMessage(typing);
        questionsBox.querySelectorAll('button').forEach(function (b) { b.disabled = true; });

        try {
            const url = root.dataset.answerUrl + '?q=' + encodeURIComponent(id);
            const resp = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = await resp.json();
            typing.remove();
            if (!resp.ok) {
                addMessage(el('div', 'help-chat-msg help-chat-msg--bot', data.error || root.dataset.errorText));
            } else {
                renderAnswer(data);
            }
        } catch {
            typing.remove();
            addMessage(el('div', 'help-chat-msg help-chat-msg--bot', root.dataset.errorText));
        }

        questionsBox.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
        scrollDown();
    }

    function setOpen(open) {
        panel.hidden = !open;
        root.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && questions === null) {
            loadQuestions();
        }
        if (open) {
            const first = questionsBox.querySelector('button');
            if (first) {
                first.focus();
            }
        }
    }

    toggle.addEventListener('click', function () {
        setOpen(panel.hidden);
    });

    questionsBox.addEventListener('click', function (e) {
        const button = e.target.closest('button[data-q]');
        if (button && !button.disabled) {
            ask(button.dataset.q, button.textContent);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });
})();
