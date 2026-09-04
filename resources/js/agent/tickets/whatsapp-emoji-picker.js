/**
 * Picker de emojis para o chat WhatsApp dentro do ticket.
 *
 * - Renderiza um popover ancorado ao botão (data-whatsapp-emoji).
 * - Categorias agrupadas (tabs no topo) e grid clicável.
 * - Insere o emoji selecionado na posição do cursor do textarea.
 * - Fecha ao perder foco (click fora) ou ao pressionar Esc.
 */

const CATEGORIES = [
    {
        key: 'smileys',
        label: 'Rostos',
        icon: '😀',
        emojis: [
            '😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇',
            '🥰','😍','🤩','😘','😗','😚','😙','😋','😛','😜','🤪','😝','🤑',
            '🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬',
            '🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵',
            '🥶','🥴','😵','🤯','🤠','🥳','😎','🤓','🧐','😕','😟','🙁','☹️',
            '😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱',
            '😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿',
            '💀','☠️','👻','👽','👾','🤖',
        ],
    },
    {
        key: 'gestures',
        label: 'Gestos',
        icon: '👍',
        emojis: [
            '👋','🤚','🖐️','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙',
            '👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏',
            '🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','🦵','🦶','👂',
            '🦻','👃','🧠','🫀','🫁','🦷','🦴','👀','👁️','👅','👄','💋',
        ],
    },
    {
        key: 'hearts',
        label: 'Corações',
        icon: '❤️',
        emojis: [
            '❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞',
            '💓','💗','💖','💘','💝','💟','💌','💋','💯','💢','💥','💫','💦',
            '💨','🕳️','💣','💬','👁️‍🗨️','🗨️','🗯️','💭','💤',
        ],
    },
    {
        key: 'animals',
        label: 'Animais',
        icon: '🐶',
        emojis: [
            '🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷',
            '🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🐣','🐥','🦆',
            '🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜',
            '🪲','🦗','🕷️','🦂','🐢','🐍','🦎','🐙','🦑','🦐','🦞','🦀','🐡',
            '🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🐘','🦏',
            '🦛','🐪','🐫','🦒','🐃','🐂','🐄','🐎','🐖','🐏','🐑','🐐','🦌',
            '🐕','🐩','🐈','🐓','🦃','🦚','🦜','🦢','🕊️','🐇','🐁','🐀','🐿️',
            '🦔','🐾',
        ],
    },
    {
        key: 'food',
        label: 'Comida',
        icon: '🍔',
        emojis: [
            '🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑',
            '🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🌽','🥕',
            '🧄','🧅','🥔','🍠','🥐','🍞','🥖','🥨','🥯','🧀','🥚','🍳','🧈',
            '🥞','🧇','🥓','🥩','🍗','🍖','🌭','🍔','🍟','🍕','🥪','🥙','🧆',
            '🌮','🌯','🥗','🥘','🥫','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🍤',
            '🍙','🍚','🍘','🍥','🥮','🥠','🍢','🍡','🍧','🍨','🍦','🥧','🧁',
            '🍰','🎂','🍮','🍭','🍬','🍫','🍿','🧂','🍩','🍪','🌰','🥜','🍯',
            '🥛','🍼','☕','🍵','🧉','🍶','🍺','🍻','🥂','🍷','🥃','🍸','🍹',
            '🍾','🧊','🥄','🍴','🍽️','🥣','🥡','🥢',
        ],
    },
    {
        key: 'activities',
        label: 'Atividades',
        icon: '⚽',
        emojis: [
            '⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🪀','🏓','🏸',
            '🏒','🏑','🥍','🏏','🥅','⛳','🏹','🎣','🤿','🥊','🥋','🎽','🛹',
            '🛼','🛷','⛸️','🥌','🎿','⛷️','🏂','🪂','🏋️','🤼','🤸','⛹️','🤺',
            '🤾','🏌️','🏇','🧘','🏄','🏊','🤽','🚣','🧗','🚵','🚴','🏆','🥇',
            '🥈','🥉','🏅','🎖️','🏵️','🎗️','🎫','🎟️','🎪','🤹','🎭','🩰','🎨',
            '🎬','🎤','🎧','🎼','🎹','🥁','🎷','🎺','🎸','🎻','🎲','♟️','🎯',
            '🎳','🎮','🎰','🧩',
        ],
    },
    {
        key: 'objects',
        label: 'Objetos',
        icon: '💼',
        emojis: [
            '⌚','📱','💻','⌨️','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿',
            '📀','📼','📷','📸','📹','🎥','📽️','🎞️','📞','☎️','📟','📠','📺',
            '📻','🎙️','🎚️','🎛️','🧭','⏱️','⏲️','⏰','🕰️','⌛','⏳','📡','🔋',
            '🔌','💡','🔦','🕯️','🪔','🧯','🛢️','💸','💵','💴','💶','💷','💰',
            '💳','💎','⚖️','🧰','🔧','🔨','⚒️','🛠️','⛏️','🔩','⚙️','🧱','⛓️',
            '🧲','🔫','💣','🧨','🪓','🔪','🗡️','⚔️','🛡️','🚬','⚰️','⚱️','🏺',
            '🔮','📿','🧿','💈','⚗️','🔭','🔬','🕳️','🩹','🩺','💊','💉','🩸',
            '🧬','🦠','🧫','🧪','🌡️','🧹','🧺','🧻','🚽','🚰','🚿','🛁','🛀',
            '🧼','🪒','🧽','🧴','🛎️','🔑','🗝️','🚪','🛋️','🛏️','🛌','🧸','🖼️',
            '🛍️','🛒','🎁','🎈','🎏','🎀','🎊','🎉','🎎','🏮','🎐','🧧','✉️',
            '📩','📨','📧','📥','📤','📦','🏷️','📪','📫','📬','📭','📮','📯',
            '📜','📃','📄','📑','🧾','📊','📈','📉','🗒️','🗓️','📆','📅','🗑️',
            '📇','🗃️','🗳️','🗄️','📋','📁','📂','🗂️','🗞️','📰','📓','📔','📒',
            '📕','📗','📘','📙','📚','📖','🔖','🧷','🔗','📎','🖇️','📐','📏',
            '🧮','📌','📍','✂️','🖊️','🖋️','✒️','🖌️','🖍️','📝','✏️','🔍','🔎',
            '🔏','🔐','🔒','🔓',
        ],
    },
    {
        key: 'symbols',
        label: 'Símbolos',
        icon: '⭐',
        emojis: [
            '⭐','🌟','✨','⚡','🔥','💥','☄️','☀️','🌤️','⛅','🌥️','☁️','🌦️',
            '🌧️','⛈️','🌩️','🌨️','❄️','☃️','⛄','🌬️','💨','💧','💦','☔','☂️',
            '🌊','🌫️','✅','☑️','✔️','❌','❎','⭕','🛑','⛔','📛','🚫','🚷',
            '🚯','🚳','🚱','🔞','📵','🚭','❗','❕','❓','❔','‼️','⁉️','💯',
            '🔅','🔆','〽️','⚠️','🚸','🔱','⚜️','🔰','♻️','🈯','💹','❇️','✳️',
            '🌐','💠','Ⓜ️','🌀','🏧','🚾','♿','🅿️','🛗','🛂','🛃','🛄','🛅',
            '🚹','🚺','🚼','🚻','🚮','🎦','📶','🆖','🆗','🆙','🆒','🆕','🆓',
            '0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟',
            '🔢','#️⃣','*️⃣','⏏️','▶️','⏸️','⏯️','⏹️','⏺️','⏭️','⏮️','⏩','⏪',
            '⏫','⏬','◀️','🔼','🔽','➡️','⬅️','⬆️','⬇️','↗️','↘️','↙️','↖️',
            '↕️','↔️','↪️','↩️','⤴️','⤵️','🔀','🔁','🔂','🔄','🔃','🎵','🎶',
            '➕','➖','➗','✖️','♾️','💲','💱','™️','©️','®️','〰️','➰','➿',
            '🔚','🔙','🔛','🔝','🔜','♈','♉','♊','♋','♌','♍','♎','♏','♐',
            '♑','♒','♓',
        ],
    },
];

const POPOVER_WIDTH = 304;

let openInstance = null;

/**
 * Vincula um picker ao botão e textarea informados.
 * Retorna uma função de cleanup que remove o picker do DOM.
 */
export function setupWhatsAppEmojiPicker(button, textarea) {
    if (!button || !textarea) return () => {};

    if (button.dataset.emojiPickerBound === '1') return () => {};
    button.dataset.emojiPickerBound = '1';

    const wrapper = ensureWrapper(button);
    const popover = createPopover();
    wrapper.appendChild(popover);

    let activeCategory = CATEGORIES[0].key;

    const renderGrid = () => {
        const grid = popover.querySelector('[data-emoji-grid]');
        const category = CATEGORIES.find((c) => c.key === activeCategory) ?? CATEGORIES[0];

        grid.innerHTML = category.emojis.map((emoji) =>
            `<button type="button" class="text-xl leading-none p-1 rounded hover:bg-gray-100 focus:bg-gray-100 focus:outline-none" data-emoji="${emoji}" aria-label="${emoji}">${emoji}</button>`
        ).join('');
    };

    const renderTabs = () => {
        const tabs = popover.querySelector('[data-emoji-tabs]');
        tabs.innerHTML = CATEGORIES.map((category) => {
            const isActive = category.key === activeCategory;
            const base = 'flex-1 px-2 py-1.5 text-lg text-center transition-colors';
            const cls = isActive
                ? 'bg-green-50 text-green-700 border-b-2 border-green-500'
                : 'text-gray-500 hover:bg-gray-50 border-b-2 border-transparent';
            return `<button type="button" class="${base} ${cls}" data-emoji-tab="${category.key}" title="${category.label}" aria-label="${category.label}">${category.icon}</button>`;
        }).join('');
    };

    const open = () => {
        if (openInstance && openInstance !== close) openInstance();
        openInstance = close;

        renderTabs();
        renderGrid();
        popover.classList.remove('hidden');

        document.addEventListener('mousedown', onDocumentMouseDown, true);
        document.addEventListener('keydown', onKeyDown, true);
    };

    const close = () => {
        popover.classList.add('hidden');
        document.removeEventListener('mousedown', onDocumentMouseDown, true);
        document.removeEventListener('keydown', onKeyDown, true);
        if (openInstance === close) openInstance = null;
    };

    const onDocumentMouseDown = (event) => {
        if (wrapper.contains(event.target)) return;
        close();
    };

    const onKeyDown = (event) => {
        if (event.key === 'Escape') close();
    };

    const insertEmoji = (emoji) => {
        const start = textarea.selectionStart ?? textarea.value.length;
        const end = textarea.selectionEnd ?? textarea.value.length;
        const before = textarea.value.slice(0, start);
        const after = textarea.value.slice(end);
        textarea.value = `${before}${emoji}${after}`;
        const caret = start + emoji.length;

        textarea.focus();
        textarea.setSelectionRange(caret, caret);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    };

    button.addEventListener('click', (event) => {
        event.preventDefault();
        if (popover.classList.contains('hidden')) {
            open();
        } else {
            close();
        }
    });

    popover.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-emoji-tab]');
        if (tab) {
            activeCategory = tab.dataset.emojiTab;
            renderTabs();
            renderGrid();
            return;
        }

        const emojiBtn = event.target.closest('[data-emoji]');
        if (emojiBtn) {
            insertEmoji(emojiBtn.dataset.emoji);
        }
    });

    return () => {
        close();
        popover.remove();
        delete button.dataset.emojiPickerBound;
    };
}

function ensureWrapper(button) {
    if (button.parentElement?.dataset.emojiWrapper === '1') {
        return button.parentElement;
    }

    const wrapper = document.createElement('div');
    wrapper.dataset.emojiWrapper = '1';
    wrapper.className = 'relative inline-block';
    button.parentElement.insertBefore(wrapper, button);
    wrapper.appendChild(button);
    return wrapper;
}

function createPopover() {
    const popover = document.createElement('div');
    popover.className = [
        'hidden absolute bottom-full right-0 mb-2 z-50',
        'bg-white border border-gray-200 rounded-2xl shadow-xl',
        'flex flex-col overflow-hidden',
    ].join(' ');
    popover.style.width = `${POPOVER_WIDTH}px`;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', 'Selecionar emoji');

    popover.innerHTML = `
        <div class="flex border-b border-gray-100 bg-gray-50" data-emoji-tabs></div>
        <div class="p-2 max-h-64 overflow-y-auto">
            <div class="grid grid-cols-8 gap-0.5" data-emoji-grid></div>
        </div>
    `;

    return popover;
}
